<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TLC_REST {

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public static function init() {

		add_action(
			'rest_api_init',
			array( __CLASS__, 'register_routes' )
		);
	}
	/**
 * Validate visitor ID.
 *
 * @param string $visitor_id Visitor ID.
 * @return bool
 */
private static function is_valid_visitor_id( $visitor_id ) {

	return (bool) preg_match(
		'/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i',
		$visitor_id
	);
}

	/**
	 * Register plugin REST routes.
	 *
	 * @return void
	 */
	public static function register_routes() {

		/**
		 * Create visitor message.
		 */
		register_rest_route(
			'tlc/v1',
			'/messages',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_message' ),
				'permission_callback' => '__return_true',
			)
		);
/**
 * Get open conversation by visitor ID.
 */
register_rest_route(
	'tlc/v1',
	'/conversations/by-visitor',
	array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => array( __CLASS__, 'get_conversation_by_visitor' ),
		'permission_callback' => '__return_true',
		'args'                => array(
			'visitor_id' => array(
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	)
);
		/**
		 * Get conversation messages.
		 */
		register_rest_route(
			'tlc/v1',
			'/conversations/(?P<conversation_id>[0-9]+)/messages',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_messages' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'conversation_id' => array(
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
					'visitor_id' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'after_id' => array(
						'required'          => false,
						'default'           => 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		/**
		 * Telegram webhook.
		 */
		register_rest_route(
			'tlc/v1',
			'/telegram/webhook',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'telegram_webhook' ),
				'permission_callback' => '__return_true',
			)
		);

	}

	/**
	 * Create a visitor message.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_message( WP_REST_Request $request ) {

		$params = $request->get_json_params();

		$settings = get_option(
			'tlc_settings',
			array()
		);

		$settings = is_array( $settings ) ? $settings : array();

		if ( isset( $settings['enabled'] ) && ! $settings['enabled'] ) {
			return new WP_Error(
				'tlc_chat_disabled',
				__( 'Chat is currently unavailable.', 'telegram-live-chat' ),
				array( 'status' => 403 )
			);
		}

		$admin_status = isset( $settings['admin_status'] )
			? $settings['admin_status']
			: 'offline';

		$visitor_id = isset( $params['visitor_id'] )
			? sanitize_text_field( $params['visitor_id'] )
			: '';
			

		$message = isset( $params['message'] )
			? sanitize_textarea_field( $params['message'] )
			: '';

		$page_url = isset( $params['page_url'] )
			? esc_url_raw( $params['page_url'] )
			: '';

			

if ( empty( $visitor_id ) ) {
	return new WP_Error(
		'tlc_missing_visitor',
		__( 'Visitor ID is required.', 'telegram-live-chat' ),
		array( 'status' => 400 )
	);
}
if ( ! self::is_valid_visitor_id( $visitor_id ) ) {
	return new WP_Error(
		'tlc_invalid_visitor',
		__( 'Invalid visitor ID.', 'telegram-live-chat' ),
		array( 'status' => 400 )
	);
}

$rate_limit = self::check_rate_limit(
	'message',
	$visitor_id,
	10,
	60
);

if ( is_wp_error( $rate_limit ) ) {
	return $rate_limit;
}

		if ( empty( $message ) ) {
			return new WP_Error(
				'tlc_empty_message',
				__( 'Message cannot be empty.', 'telegram-live-chat' ),
				array( 'status' => 400 )
			);
		}

		$message_length = function_exists( 'mb_strlen' )
			? mb_strlen( $message )
			: strlen( $message );

		if ( $message_length > 2000 ) {
			return new WP_Error(
				'tlc_message_too_long',
				__( 'Message is too long.', 'telegram-live-chat' ),
				array( 'status' => 400 )
			);
		}

		/**
		 * Get or create conversation.
		 */
		$conversation_id = self::get_or_create_conversation(
			$visitor_id,
			$page_url
		);

		if ( is_wp_error( $conversation_id ) ) {
			return $conversation_id;
		}

		/**
		 * Save visitor message.
		 */
		$message_id = self::save_message(
			$conversation_id,
			$message
		);

		if ( is_wp_error( $message_id ) ) {
			return $message_id;
		}

		/**
		 * Send message to Telegram.
		 */
		$admin_chat_ids = self::get_administrator_chat_ids( $settings );

		if ( ! empty( $admin_chat_ids ) ) {

			/*
			 * Keep a human-readable conversation reference in Telegram. Besides
			 * helping the administrator, this lets the webhook recover the target
			 * conversation if an older host/plugin update did not persist the
			 * outgoing Telegram message ID.
			 */
			$telegram_message  = __( 'New message from website', 'telegram-live-chat' );
			$telegram_message .= sprintf(
				"\n[%s #%d]\n\n",
				__( 'Conversation', 'telegram-live-chat' ),
				$conversation_id
			);
			$telegram_message .= $message;

			if ( ! empty( $page_url ) ) {
				$telegram_message .= "\n\n" . __( 'Page:', 'telegram-live-chat' ) . "\n" . $page_url;
			}

			$telegram_message_id = 0;

			foreach ( $admin_chat_ids as $admin_chat_id ) {
				$telegram_result = TLC_Telegram::send_message(
					$admin_chat_id,
					$telegram_message
				);

				if (
					! $telegram_message_id &&
					! is_wp_error( $telegram_result ) &&
					! empty( $telegram_result['result']['message_id'] )
				) {
					$telegram_message_id = absint(
						$telegram_result['result']['message_id']
					);
				}
			}

			/**
			 * Save Telegram message ID.
			 */
			if ( $telegram_message_id ) {

				global $wpdb;

				$messages_table = TLC_Database::messages_table();

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Updates a row in this plugin's private table.
				$wpdb->update(
					$messages_table,
					array(
						'telegram_message_id' => $telegram_message_id,
					),
					array(
						'id' => $message_id,
					),
					array(
						'%d',
					),
					array(
						'%d',
					)
				);
			}
		}

		/**
		 * Offline system message.
		 */
		if ( 'offline' === $admin_status ) {

			if ( ! self::has_offline_message( $conversation_id ) ) {

				$offline_message = isset(
					$settings['offline_message']
				)
					? $settings['offline_message']
					: 'We received your message and will reply as soon as possible.';

				if ( ! isset( $settings['offline_message'] ) ) {
					$offline_message = __( 'We received your message and will reply as soon as possible.', 'telegram-live-chat' );
				}

				self::save_message(
					$conversation_id,
					$offline_message,
					'system'
				);
			}
		}

		return new WP_REST_Response(
			array(
				'success'         => true,
				'conversation_id' => $conversation_id,
				'message_id'      => $message_id,
			),
			201
		);
	}
/**
 * Get the latest open conversation for a visitor.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response|WP_Error
 */
public static function get_conversation_by_visitor( WP_REST_Request $request ) {

	global $wpdb;

	$visitor_id = sanitize_text_field(
		$request->get_param( 'visitor_id' )
	);

	if ( empty( $visitor_id ) ) {
		return new WP_Error(
			'tlc_missing_visitor',
			__( 'Visitor ID is required.', 'telegram-live-chat' ),
			array( 'status' => 400 )
		);
	}
	if ( ! self::is_valid_visitor_id( $visitor_id ) ) {
	return new WP_Error(
		'tlc_invalid_visitor',
		__( 'Invalid visitor ID.', 'telegram-live-chat' ),
		array( 'status' => 400 )
	);
}
$rate_limit = self::check_rate_limit(
	'conversation_lookup',
	'',
	20,
	60
);

if ( is_wp_error( $rate_limit ) ) {
	return $rate_limit;
}

	$table = TLC_Database::conversations_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Live chat data must be read directly from this plugin's private table.
	$conversation = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT
				id,
				status,
				page_url,
				created_at,
				updated_at
			FROM %i
			WHERE visitor_id = %s
			AND status = 'open'
			ORDER BY id DESC
			LIMIT 1",
			$table,
			$visitor_id
		),
		ARRAY_A
	);

	if ( empty( $conversation ) ) {
		$response = new WP_REST_Response(
			array(
				'success'         => true,
				'conversation_id' => null,
				'conversation'    => null,
			),
			200
		);

		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'Expires', '0' );

		return $response;
	}

	$response = new WP_REST_Response(
		array(
			'success'         => true,
			'conversation_id' => (int) $conversation['id'],
			'conversation'    => $conversation,
		),
		200
	);

	$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
	$response->header( 'Pragma', 'no-cache' );
	$response->header( 'Expires', '0' );

	return $response;
}
	/**
	 * Get messages for a conversation.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_messages( WP_REST_Request $request ) {

		global $wpdb;

		$conversation_id = absint(
			$request->get_param( 'conversation_id' )
		);

		$visitor_id = sanitize_text_field(
			$request->get_param( 'visitor_id' )
		);

		$after_id = absint(
			$request->get_param( 'after_id' )
		);

		if ( ! $conversation_id || empty( $visitor_id ) ) {
			return new WP_Error(
				'tlc_invalid_request',
				__( 'Invalid conversation request.', 'telegram-live-chat' ),
				array( 'status' => 400 )
			);
		}
		if ( ! self::is_valid_visitor_id( $visitor_id ) ) {
	return new WP_Error(
		'tlc_invalid_visitor',
		__( 'Invalid visitor ID.', 'telegram-live-chat' ),
		array( 'status' => 400 )
	);
}
		$rate_limit = self::check_rate_limit(
	'history',
	'',
	120,
	60
);

if ( is_wp_error( $rate_limit ) ) {
	return $rate_limit;
}

		$conversations_table = TLC_Database::conversations_table();
		$messages_table      = TLC_Database::messages_table();

		/**
		 * Verify conversation ownership.
		 */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Verifies ownership against this plugin's private table.
		$conversation_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id
				FROM %i
				WHERE id = %d
				AND visitor_id = %s
				LIMIT 1",
				$conversations_table,
				$conversation_id,
				$visitor_id
			)
		);

		if ( ! $conversation_exists ) {
			return new WP_Error(
				'tlc_conversation_not_found',
				__( 'Conversation not found.', 'telegram-live-chat' ),
				array( 'status' => 404 )
			);
		}

		/**
		 * Get messages.
		 */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- The visitor's current messages must not be served from cache.
		$messages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					id,
					sender,
					message,
					created_at
				FROM %i
				WHERE conversation_id = %d
				AND id > %d
				ORDER BY id ASC
				LIMIT 100",
				$messages_table,
				$conversation_id,
				$after_id
			),
			ARRAY_A
		);

		$response = new WP_REST_Response(
			array(
				'success'  => true,
				'messages' => $messages,
			),
			200
		);

		/*
		 * Chat polling must always receive the latest administrator replies.
		 * Some page/CDN caches also cache public REST GET requests, even when a
		 * cache-busting query argument is present, so make this explicit here.
		 */
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'Expires', '0' );

		return $response;
	}

	/**
 * Rate limit public chat requests.
 *
 * @param string $action     Rate-limit bucket name.
 * @param string $visitor_id Visitor ID.
 * @param int    $limit      Maximum requests allowed.
 * @param int    $window     Window length in seconds.
 * @return true|WP_Error
 */
private static function check_rate_limit(
	$action,
	$visitor_id = '',
	$limit = 10,
	$window = 60
) {

	$ip = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: 'unknown';

	$key_parts = array(
		'tlc_rate',
		$action,
		$ip,
		$visitor_id,
	);

	$key = 'tlc_' . md5( implode( '|', $key_parts ) );

	$count = get_transient( $key );

	if ( false === $count ) {

		set_transient(
			$key,
			1,
			$window
		);

		return true;
	}

	$count = (int) $count + 1;

	if ( $count > $limit ) {

		set_transient(
			$key,
			$count,
			$window
		);

		return new WP_Error(
			'tlc_rate_limit',
			__(
				'Too many requests. Please try again later.',
				'telegram-live-chat'
			),
			array(
				'status'      => 429,
				'retry_after' => $window,
			)
		);
	}

	set_transient(
		$key,
		$count,
		$window
	);

	return true;
}

	/**
	 * Get existing conversation or create a new one.
	 *
	 * @param string $visitor_id Visitor ID.
	 * @param string $page_url Page URL.
	 * @return int|WP_Error
	 */
	private static function get_or_create_conversation(
		$visitor_id,
		$page_url
	) {

		global $wpdb;

		$table = TLC_Database::conversations_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reads the current open conversation from this plugin's private table.
		$conversation_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id
				FROM %i
				WHERE visitor_id = %s
				AND status = 'open'
				ORDER BY id DESC
				LIMIT 1",
				$table,
				$visitor_id
			)
		);

		if ( $conversation_id ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Updates a row in this plugin's private table.
			$wpdb->update(
				$table,
				array(
					'page_url'   => $page_url,
					'updated_at' => current_time( 'mysql', true ),
				),
				array(
					'id' => $conversation_id,
				),
				array(
					'%s',
					'%s',
				),
				array(
					'%d',
				)
			);

			return (int) $conversation_id;
		}

		$now = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Inserts a conversation into this plugin's private table.
		$inserted = $wpdb->insert(
			$table,
			array(
				'visitor_id' => $visitor_id,
				'status'     => 'open',
				'page_url'   => $page_url,
				'created_at' => $now,
				'updated_at' => $now,
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		if ( false === $inserted ) {
			return new WP_Error(
				'tlc_conversation_error',
				__( 'Could not create conversation.', 'telegram-live-chat' ),
				array( 'status' => 500 )
			);
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Save message.
	 *
	 * @param int         $conversation_id Conversation ID.
	 * @param string      $message Message content.
	 * @param string      $sender Message sender.
	 * @param int|null    $telegram_message_id Telegram message ID.
	 * @return int|WP_Error
	 */
	private static function save_message(
		$conversation_id,
		$message,
		$sender = 'user',
		$telegram_message_id = null
	) {

		global $wpdb;

		$table = TLC_Database::messages_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Inserts a message into this plugin's private table.
		$inserted = $wpdb->insert(
			$table,
			array(
				'conversation_id'     => $conversation_id,
				'sender'              => $sender,
				'message'             => $message,
				'telegram_message_id' => $telegram_message_id,
				'created_at'          => current_time( 'mysql', true ),
			),
			array(
				'%d',
				'%s',
				'%s',
				'%d',
				'%s',
			)
		);

		if ( false === $inserted ) {
			return new WP_Error(
				'tlc_message_error',
				__( 'Could not save message.', 'telegram-live-chat' ),
				array( 'status' => 500 )
			);
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Check whether an offline system message already exists.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return bool
	 */
	private static function has_offline_message(
		$conversation_id
	) {

		global $wpdb;

		$table = TLC_Database::messages_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checks transient chat state in this plugin's private table.
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*)
				FROM %i
				WHERE conversation_id = %d
				AND sender = 'system'",
				$table,
				$conversation_id
			)
		);

		return (int) $count > 0;
	}

	/**
	 * Return the owner and all paired administrator chat IDs.
	 *
	 * @param array $settings Plugin settings.
	 * @return string[]
	 */
	private static function get_administrator_chat_ids( $settings ) {

		$chat_ids = array();
		$owner_id = isset( $settings['admin_chat_id'] )
			? trim( (string) $settings['admin_chat_id'] )
			: '';

		if ( '' !== $owner_id ) {
			$chat_ids[] = $owner_id;
		}

		$admins = isset( $settings['admins'] ) && is_array( $settings['admins'] )
			? $settings['admins']
			: array();

		foreach ( $admins as $admin ) {
			$chat_id = isset( $admin['chat_id'] )
				? trim( (string) $admin['chat_id'] )
				: '';

			if ( '' !== $chat_id ) {
				$chat_ids[] = $chat_id;
			}
		}

		return array_values( array_unique( $chat_ids ) );
	}


/**
 * Handle incoming Telegram webhook updates.
 *
 * Admin must reply to a Telegram message that was
 * originally sent by the website.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response
 */
public static function telegram_webhook( WP_REST_Request $request ) {

	/**
	 * Get plugin settings.
	 */
	$settings = get_option(
		'tlc_settings',
		array()
	);

	$settings = is_array( $settings ) ? $settings : array();

	/**
	 * Verify Telegram webhook secret.
	 */
	$webhook_secret = isset( $settings['webhook_secret'] )
		? trim( (string) $settings['webhook_secret'] )
		: '';

	$received_secret = $request->get_header(
		'X-Telegram-Bot-Api-Secret-Token'
	);

	if (
		empty( $webhook_secret ) ||
		empty( $received_secret ) ||
		! hash_equals(
			$webhook_secret,
			$received_secret
		)
	) {

		return new WP_REST_Response(
			array(
				'success' => false,
			),
			403
		);
	}

	/**
	 * Get Telegram update.
	 */
	$update = $request->get_json_params();

	if (
		empty( $update ) ||
		! is_array( $update )
	) {

		return new WP_REST_Response(
			array(
				'success' => false,
			),
			400
		);
	}
	/**
 * Handle Telegram pairing command.
 */
if (
	isset( $update['message']['text'] ) &&
	is_string( $update['message']['text'] )
) {

	$text = trim( $update['message']['text'] );

	if (
		preg_match(
			'/^\/start(?:@\w+)?\s+([A-Za-z0-9]+)$/',
			$text,
			$matches
		)
	) {

		$pairing_token = $matches[1];

		/**
		 * Get Telegram chat ID.
		 */
		$pairing_chat_id = isset(
			$update['message']['chat']['id']
		)
			? (string) $update['message']['chat']['id']
			: '';

		if ( empty( $pairing_chat_id ) ) {

			return new WP_REST_Response(
				array(
					'success' => false,
				),
				400
			);
		}

		/**
		 * Pair administrator using the temporary token.
		 */
		$settings = get_option(
			'tlc_settings',
			array()
		);

		$settings = is_array( $settings )
			? $settings
			: array();

		$pairing_token_hash = isset(
			$settings['pairing_token_hash']
		)
			? (string) $settings['pairing_token_hash']
			: '';

		$pairing_expires_at = isset(
			$settings['pairing_expires_at']
		)
			? (int) $settings['pairing_expires_at']
			: 0;

		/**
		 * Validate pairing token.
		 */
		if (
			empty( $pairing_token_hash ) ||
			empty( $pairing_expires_at ) ||
			time() > $pairing_expires_at ||
			! wp_check_password(
				$pairing_token,
				$pairing_token_hash
			)
		) {

			return new WP_REST_Response(
				array(
					'success' => false,
				),
				200
			);
		}

		$owner_chat_id = isset( $settings['admin_chat_id'] )
			? trim( (string) $settings['admin_chat_id'] )
			: '';

		/* The first paired chat is the owner. Later links only add admins. */
		if ( '' === $owner_chat_id ) {
			$settings['admin_chat_id'] = $pairing_chat_id;
		} elseif ( $owner_chat_id !== $pairing_chat_id ) {
			$admins = isset( $settings['admins'] ) && is_array( $settings['admins'] )
				? $settings['admins']
				: array();
			$already_added = false;

			foreach ( $admins as $admin ) {
				if ( isset( $admin['chat_id'] ) && $pairing_chat_id === (string) $admin['chat_id'] ) {
					$already_added = true;
					break;
				}
			}

			if ( ! $already_added ) {
				$admins[] = array(
					'chat_id'  => $pairing_chat_id,
					'added_at' => current_time( 'mysql', true ),
				);
			}

			$settings['admins'] = $admins;
		}

		/**
		 * Mark Telegram connection as active.
		 */
		$settings['telegram_connected'] = true;

		/**
		 * Consume pairing token.
		 */
		unset(
			$settings['pairing_token_hash'],
			$settings['pairing_expires_at'],
			$settings['pairing_token_plain']
		);

		update_option(
			'tlc_settings',
			$settings
		);

		return new WP_REST_Response(
			array(
				'success' => true,
			),
			200
		);
	}
}

	/**
	 * Only process normal Telegram messages.
	 */
	if (
		empty( $update['message'] ) ||
		! is_array( $update['message'] )
	) {

		return new WP_REST_Response(
			array(
				'success' => true,
			),
			200
		);
	}

	$telegram_message = $update['message'];

	$incoming_telegram_message_id = isset( $telegram_message['message_id'] )
		? absint( $telegram_message['message_id'] )
		: 0;

	if ( ! $incoming_telegram_message_id ) {
		return new WP_REST_Response(
			array( 'success' => true ),
			200
		);
	}

	/**
	 * Only process text messages.
	 */
	if (
		empty( $telegram_message['text'] ) ||
		! is_string( $telegram_message['text'] )
	) {

		return new WP_REST_Response(
			array(
				'success' => true,
			),
			200
		);
	}

	/**
	 * Sanitize message text.
	 */
	$text = sanitize_textarea_field(
		$telegram_message['text']
	);

	if ( '' === trim( $text ) ) {

		return new WP_REST_Response(
			array(
				'success' => true,
			),
			200
		);
	}

	/**
	 * Get Telegram chat ID.
	 */
	$telegram_chat_id = isset(
		$telegram_message['chat']['id']
	)
		? (string) $telegram_message['chat']['id']
		: '';

	if ( empty( $telegram_chat_id ) ) {

		return new WP_REST_Response(
			array(
				'success' => true,
			),
			200
		);
	}

	$administrator_chat_ids = self::get_administrator_chat_ids( $settings );

	/**
	 * Ignore messages from unknown Telegram chats.
	 */
	if (
		empty( $administrator_chat_ids ) ||
		! in_array( $telegram_chat_id, $administrator_chat_ids, true )
	) {

		return new WP_REST_Response(
			array(
				'success' => true,
			),
			200
		);
	}

	/**
	 * We only accept replies to messages
	 * originally sent by the website.
	 */
	$reply_to_message_id = isset(
		$telegram_message['reply_to_message']['message_id']
	)
		? absint(
			$telegram_message['reply_to_message']['message_id']
		)
		: 0;

	if ( ! $reply_to_message_id ) {

		return new WP_REST_Response(
			array(
				'success' => true,
			),
			200
		);
	}

	/**
	 * Find the website message that
	 * this Telegram reply references.
	 */
	global $wpdb;

	$messages_table = TLC_Database::messages_table();

	/* Telegram retries webhooks until it receives a 2xx response. Avoid
	 * writing the same administrator reply more than once on such retries. */
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Checks webhook delivery state in this plugin's private table.
	$already_processed = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT id FROM %i WHERE telegram_message_id = %d LIMIT 1',
			$messages_table,
			$incoming_telegram_message_id
		)
	);

	if ( $already_processed ) {
		return new WP_REST_Response(
			array( 'success' => true ),
			200
		);
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Resolves the reply against this plugin's private message table.
	$conversation_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT conversation_id
			FROM %i
			WHERE telegram_message_id = %d
			LIMIT 1",
			$messages_table,
			$reply_to_message_id
		)
	);

	/*
	 * Fallback for hosts where an outgoing Telegram message was delivered but
	 * its ID was not saved locally. The reference is part of the message the
	 * administrator replied to, not of their answer.
	 */
	if ( ! $conversation_id ) {
		$reply_text = isset( $telegram_message['reply_to_message']['text'] )
			? (string) $telegram_message['reply_to_message']['text']
			: '';

		if ( preg_match( '/\[[^\]\r\n]*#(\d+)\]/u', $reply_text, $matches ) ) {
			$candidate_conversation_id = absint( $matches[1] );
			$conversations_table       = TLC_Database::conversations_table();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Verifies a conversation in this plugin's private table.
			$conversation_id = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT id FROM %i WHERE id = %d LIMIT 1',
					$conversations_table,
					$candidate_conversation_id
				)
			);
		}
	}

	if ( ! $conversation_id ) {

		return new WP_REST_Response(
			array(
				'success' => true,
			),
			200
		);
	}

	$conversation_id = absint(
		$conversation_id
	);

	if ( ! $conversation_id ) {

		return new WP_REST_Response(
			array(
				'success' => true,
			),
			200
		);
	}

	/**
	 * Save administrator reply.
	 */
	$message_id = self::save_message(
		$conversation_id,
		$text,
		'admin',
		$incoming_telegram_message_id
	);

	if ( is_wp_error( $message_id ) ) {

		return new WP_REST_Response(
			array(
				'success' => false,
			),
			500
		);
	}

	/**
	 * Update conversation timestamp.
	 */
	$conversations_table = TLC_Database::conversations_table();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Updates a conversation in this plugin's private table.
	$wpdb->update(
		$conversations_table,
		array(
			'updated_at' => current_time(
				'mysql',
				true
			),
		),
		array(
			'id' => $conversation_id,
		),
		array(
			'%s',
		),
		array(
			'%d',
		)
	);

	/**
	 * Return successful response to Telegram.
	 */
	return new WP_REST_Response(
		array(
			'success'         => true,
			'conversation_id' => $conversation_id,
			'message_id'      => (int) $message_id,
		),
		200
	);
}


}
