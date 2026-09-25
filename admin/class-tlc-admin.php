<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TLCWT_Admin {

	/**
	 * Initialize admin functionality.
	 *
	 * @return void
	 */
	public static function init() {

		add_action(
			'admin_menu',
			array( __CLASS__, 'add_menu' )
		);

		add_action(
			'admin_init',
			array( __CLASS__, 'register_settings' )
		);

		add_action(
			'admin_enqueue_scripts',
			array( __CLASS__, 'enqueue_assets' )
		);
		add_action(
	'admin_post_tlcwt_add_administrator',
	array( __CLASS__, 'add_administrator' )
);

		add_action(
			'admin_post_tlcwt_connect_telegram',
			array( __CLASS__, 'connect_telegram' )
		);
		add_action(
	'admin_post_tlcwt_remove_administrator',
	array( __CLASS__, 'remove_administrator' )
);

		add_action(
			'admin_post_tlcwt_run_cleanup',
			array( __CLASS__, 'run_cleanup_now' )
		);
	}

	/**
	 * Enqueue styles for the plugin administration screens.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {

		if ( ! in_array( $hook_suffix, array( 'toplevel_page_tlc-live-chat-with-telegram', 'tlc-live-chat-with-telegram_page_tlcwt-administrators' ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'tlcwt-admin',
			TLCWT_URL . 'admin/css/admin.css',
			array(),
			TLCWT_VERSION
		);

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();

		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_script(
			'tlcwt-admin',
			TLCWT_URL . 'admin/js/admin.js',
			array( 'wp-color-picker', 'media-editor', 'media-views' ),
			TLCWT_VERSION,
			true
		);
	}
	/**
 * Render administrators page.
 *
 * @return void
 */
public static function render_administrators_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = get_option(
		'tlcwt_settings',
		array()
	);

	$settings = is_array( $settings )
		? $settings
		: array();
	
	$admins = isset( $settings['admins'] )
	&& is_array( $settings['admins'] )
	? $settings['admins']
	: array();

$owner_chat_id = isset( $settings['admin_chat_id'] )
	? (string) $settings['admin_chat_id']
	: '';

		$pairing_token = isset( $settings['pairing_token_plain'] )
		? $settings['pairing_token_plain']
		: '';

	$bot_username = isset( $settings['bot_username'] )
		? $settings['bot_username']
		: '';

		$pairing_url = ( ! empty( $pairing_token ) && ! empty( $bot_username ) )
		? sprintf(
			'https://t.me/%s?start=%s',
			$bot_username,
			rawurlencode( $pairing_token )
		)
		: '';
	$just_connected = filter_input( INPUT_GET, 'tlcwt_connected', FILTER_VALIDATE_INT );

	?>

	<div class="wrap tlcwt-admin-page">

		<h1 class="tlcwt-admin-title">
			<img src="<?php echo esc_url( TLCWT_URL . 'assets/telegram-icon.svg' ); ?>" alt="" aria-hidden="true">
			<?php
			esc_html_e(
				'Administrators',
				'tlc-live-chat-with-telegram'
			);
			?>
		</h1>

		<?php if ( $just_connected ) : ?>
			<div class="notice notice-success is-dismissible"><p><strong><?php esc_html_e( 'Telegram connected successfully. A pairing link is ready below.', 'tlc-live-chat-with-telegram' ); ?></strong></p></div>
		<?php endif; ?>

		<p>
			<?php
			esc_html_e(
				'Add another Telegram administrator to receive and reply to customer messages.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</p>

		<form class="tlcwt-pairing-form"
			method="post"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		>

			<input
				type="hidden"
				name="action"
				value="tlcwt_add_administrator"
			>

			<?php
			wp_nonce_field(
				'tlcwt_add_administrator'
			);
			?>

			<?php
				submit_button(
				__( 'Generate Pairing Link', 'tlc-live-chat-with-telegram' ),
				'primary tlcwt-telegram-button'
			);
			?>

		</form>

				<?php if ( ! empty( $pairing_url ) ) : ?>

			<hr>

			<h2>
				<?php esc_html_e( 'Administrator Pairing', 'tlc-live-chat-with-telegram' ); ?>
			</h2>

			<p>
				<?php esc_html_e( 'Send this pairing link to the Telegram administrator you want to add. It expires in 10 minutes.', 'tlc-live-chat-with-telegram' ); ?>
			</p>

			<p>
				<a href="<?php echo esc_url( $pairing_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $pairing_url ); ?>
				</a>
			</p>

		<?php elseif ( ! empty( $pairing_token ) && empty( $bot_username ) ) : ?>

			<hr>

			<div class="notice notice-warning inline">
				<p>
					<?php esc_html_e( 'Connect your Telegram bot in Settings first, then generate the pairing link.', 'tlc-live-chat-with-telegram' ); ?>
				</p>
			</div>

		<?php endif; ?>
		<hr>

		<h2>
			<?php esc_html_e( 'Telegram Administrators', 'tlc-live-chat-with-telegram' ); ?>
		</h2>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Chat ID', 'tlc-live-chat-with-telegram' ); ?></th>
					<th><?php esc_html_e( 'Role', 'tlc-live-chat-with-telegram' ); ?></th>
					<th><?php esc_html_e( 'Action', 'tlc-live-chat-with-telegram' ); ?></th>
				</tr>
			</thead>
			<tbody>

				<?php if ( ! empty( $owner_chat_id ) ) : ?>
					<tr>
						<td><?php echo esc_html( $owner_chat_id ); ?></td>
						<td><?php esc_html_e( 'Owner', 'tlc-live-chat-with-telegram' ); ?></td>
						<td>&mdash;</td>
					</tr>
				<?php endif; ?>

				<?php foreach ( $admins as $admin ) :

					$admin_chat_id = isset( $admin['chat_id'] )
						? (string) $admin['chat_id']
						: '';

					if ( '' === $admin_chat_id || $admin_chat_id === $owner_chat_id ) {
						continue;
					}

					$remove_url = wp_nonce_url(
						add_query_arg(
							array(
								'action'  => 'tlcwt_remove_administrator',
								'chat_id' => $admin_chat_id,
							),
							admin_url( 'admin-post.php' )
						),
						'tlcwt_remove_administrator'
					);
					?>
					<tr>
						<td><?php echo esc_html( $admin_chat_id ); ?></td>
						<td><?php esc_html_e( 'Administrator', 'tlc-live-chat-with-telegram' ); ?></td>
						<td>
	
		<a href="<?php echo esc_url( $remove_url ); ?>"
		class="button button-secondary"
		onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to remove this administrator?', 'tlc-live-chat-with-telegram' ) ); ?>');"
	>
		<?php esc_html_e( 'Remove', 'tlc-live-chat-with-telegram' ); ?>
	</a>
</td>
					</tr>
				<?php endforeach; ?>

				<?php if ( empty( $owner_chat_id ) && empty( $admins ) ) : ?>
					<tr>
						<td colspan="3">
							<?php esc_html_e( 'No administrators paired yet.', 'tlc-live-chat-with-telegram' ); ?>
						</td>
					</tr>
				<?php endif; ?>

			</tbody>
		</table>

	</div>

	<?php
}
/**
 * Generate pairing token for a new administrator.
 *
 * @return void
 */
public static function add_administrator() {

	if ( ! current_user_can( 'manage_options' ) ) {

		wp_die(
			esc_html__(
				'You do not have permission to perform this action.',
				'tlc-live-chat-with-telegram'
			)
		);
	}

	check_admin_referer(
		'tlcwt_add_administrator'
	);

	$settings = get_option(
		'tlcwt_settings',
		array()
	);

	$settings = is_array( $settings )
		? $settings
		: array();

	/**
	 * Generate a short-lived pairing token.
	 */
	$pairing_token = wp_generate_password(
		12,
		false,
		false
	);

	$settings['pairing_token_hash'] = wp_hash_password(
		$pairing_token
	);

	$settings['pairing_expires_at'] = time() + 600;

	/**
	 * Store the temporary token only for
	 * displaying it to the administrator.
	 */
	$settings['pairing_token_plain'] = $pairing_token;

	update_option(
		'tlcwt_settings',
		$settings
	);

	wp_safe_redirect(
		add_query_arg(
			array(
				'page' => 'tlcwt-administrators',
			),
			admin_url( 'admin.php' )
		)
	);

	exit;
}

/**
 * Remove a Telegram administrator.
 *
 * @return void
 */
public static function remove_administrator() {

	if ( ! current_user_can( 'manage_options' ) ) {

		wp_die(
			esc_html__(
				'You do not have permission to perform this action.',
				'tlc-live-chat-with-telegram'
			)
		);
	}

	check_admin_referer( 'tlcwt_remove_administrator' );

	$chat_id = sanitize_text_field(
		(string) filter_input( INPUT_GET, 'chat_id', FILTER_UNSAFE_RAW )
	);

	if ( empty( $chat_id ) ) {

		wp_safe_redirect(
			admin_url( 'admin.php?page=tlcwt-administrators' )
		);

		exit;
	}

	$settings = get_option(
		'tlcwt_settings',
		array()
	);

	$settings = is_array( $settings )
		? $settings
		: array();

	$admins = isset( $settings['admins'] )
		&& is_array( $settings['admins'] )
		? $settings['admins']
		: array();

	/*
	 * Get Owner Chat ID.
	 */
	$owner_chat_id = isset( $settings['admin_chat_id'] )
		? (string) $settings['admin_chat_id']
		: '';

	/*
	 * Owner cannot be removed.
	 */
	if ( (string) $chat_id === $owner_chat_id ) {

		wp_die(
			esc_html__(
				'The Owner administrator cannot be removed.',
				'tlc-live-chat-with-telegram'
			)
		);
	}

	$remaining_admins = array();

	foreach ( $admins as $admin ) {

		if (
			! isset( $admin['chat_id'] ) ||
			(string) $admin['chat_id'] !== $chat_id
		) {

			$remaining_admins[] = $admin;
		}
	}

	$settings['admins'] = $remaining_admins;

	update_option(
		'tlcwt_settings',
		$settings
	);

	wp_safe_redirect(
		admin_url(
			'admin.php?page=tlcwt-administrators&removed=1'
		)
	);

	exit;
}


	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
		public static function add_menu() {

		add_menu_page(
			__( 'TLC - Live chat with Telegram', 'tlc-live-chat-with-telegram' ),
			__( 'TLC - Live chat with Telegram', 'tlc-live-chat-with-telegram' ),
			'manage_options',
			'tlc-live-chat-with-telegram',
			array( __CLASS__, 'render_settings_page' ),
			TLCWT_URL . 'assets/telegram-icon.svg',
			30
		);

		add_submenu_page(
			'tlc-live-chat-with-telegram',
			__( 'Settings', 'tlc-live-chat-with-telegram' ),
			__( 'Settings', 'tlc-live-chat-with-telegram' ),
			'manage_options',
			'tlc-live-chat-with-telegram',
			array( __CLASS__, 'render_settings_page' )
		);

		add_submenu_page(
			'tlc-live-chat-with-telegram',
			__( 'Administrators', 'tlc-live-chat-with-telegram' ),
			__( 'Administrators', 'tlc-live-chat-with-telegram' ),
			'manage_options',
			'tlcwt-administrators',
			array( __CLASS__, 'render_administrators_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public static function register_settings() {

		register_setting(
			'tlcwt_settings_group',
			'tlcwt_settings',
			array(
				'sanitize_callback' => array(
					__CLASS__,
					'sanitize_settings',
				),
			)
		);

		/**
		 * Telegram section.
		 */
		add_settings_section(
			'tlcwt_telegram_section',
			__( 'Telegram Settings', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_telegram_section' ),
			'tlc-live-chat-with-telegram'
		);

		add_settings_field(
			'tlcwt_bot_token',
			__( 'Bot Token', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_bot_token_field' ),
			'tlc-live-chat-with-telegram',
			'tlcwt_telegram_section'
		);

		add_settings_field(
			'tlcwt_connect_telegram',
			__( 'Telegram Connection', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_connect_telegram_field' ),
			'tlc-live-chat-with-telegram',
			'tlcwt_telegram_section'
		);

		/**
		 * Chat section.
		 */
		add_settings_section(
			'tlcwt_chat_section',
			__( 'Chat Settings', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_chat_section' ),
			'tlc-live-chat-with-telegram'
		);

		add_settings_field(
			'tlcwt_welcome_message',
			__( 'Welcome Message', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_welcome_message_field' ),
			'tlc-live-chat-with-telegram',
			'tlcwt_chat_section'
		);

		add_settings_field(
			'tlcwt_offline_message',
			__( 'Offline Message', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_offline_message_field' ),
			'tlc-live-chat-with-telegram',
			'tlcwt_chat_section'
		);

		add_settings_field(
			'tlcwt_enabled',
			__( 'Enable Chat', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_enabled_field' ),
			'tlc-live-chat-with-telegram',
			'tlcwt_chat_section'
		);

		add_settings_field(
			'tlcwt_appearance',
			__( 'Widget Appearance', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_appearance_field' ),
			'tlc-live-chat-with-telegram',
			'tlcwt_chat_section'
		);

		add_settings_field(
			'tlcwt_admin_status',
			__( 'Admin Status', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_admin_status_field' ),
			'tlc-live-chat-with-telegram',
			'tlcwt_chat_section'
		);

		/**
		 * Data management section.
		 */
		add_settings_section(
			'tlcwt_data_section',
			__( 'Data Management', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_data_section' ),
			'tlc-live-chat-with-telegram'
		);

		add_settings_field(
			'tlcwt_delete_data',
			__( 'Delete Data on Uninstall', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_delete_data_field' ),
			'tlc-live-chat-with-telegram',
			'tlcwt_data_section'
		);

		add_settings_field(
			'tlcwt_cleanup_frequency',
			__( 'Cleanup Frequency', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_cleanup_frequency_field' ),
			'tlc-live-chat-with-telegram',
			'tlcwt_data_section'
		);

		add_settings_field(
			'tlcwt_cleanup_retention',
			__( 'Data Retention', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_cleanup_retention_field' ),
			'tlc-live-chat-with-telegram',
			'tlcwt_data_section'
		);

		add_settings_field(
			'tlcwt_cleanup_manual',
			__( 'Manual Cleanup', 'tlc-live-chat-with-telegram' ),
			array( __CLASS__, 'render_cleanup_manual_field' ),
			'tlc-live-chat-with-telegram',
			'tlcwt_data_section'
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Settings input.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {

		$input = is_array( $input )
			? $input
			: array();

		$existing_settings = get_option(
			'tlcwt_settings',
			array()
		);

		$existing_settings = is_array( $existing_settings )
			? $existing_settings
			: array();

				$output = array_merge( $existing_settings, $input );

		/**
		 * Bot token.
		 */
		if ( isset( $input['bot_token'] ) ) {

			$bot_token = sanitize_text_field(
				$input['bot_token']
			);

			$old_token = isset(
				$existing_settings['bot_token']
			)
				? $existing_settings['bot_token']
				: '';

			if ( $bot_token !== $old_token ) {

				$output['admin_chat_id']      = '';
				$output['admins']             = array();
				$output['telegram_connected'] = false;

				unset(
					$output['pairing_token_hash'],
					$output['pairing_expires_at'],
					$output['pairing_token_plain'],
					$output['bot_username']
				);
			}

			$output['bot_token'] = $bot_token;
		}
		/**
		 * Webhook secret.
		 */
		if ( empty( $output['webhook_secret'] ) ) {

			$output['webhook_secret'] = wp_generate_password(
				32,
				false,
				false
			);
		}

		/**
		 * Welcome message.
		 */
		if ( isset( $input['welcome_message'] ) ) {

			$output['welcome_message'] = sanitize_textarea_field(
				$input['welcome_message']
			);
		}

		/**
		 * Offline message.
		 */
		if ( isset( $input['offline_message'] ) ) {

			$output['offline_message'] = sanitize_textarea_field(
				$input['offline_message']
			);
		}

		$appearance_defaults = array(
			'widget_position' => 'right',
			'primary_color' => '#2aabee',
			'panel_color' => '#ffffff',
			'desktop_width' => 340,
			'desktop_height' => 480,
			'mobile_width' => 340,
			'mobile_height' => 420,
			'desktop_bottom' => 24,
			'mobile_bottom' => 60,
			'header_text' => __( 'Support', 'tlc-live-chat-with-telegram' ),
			'header_direction' => 'auto',
			'icon_id' => 0,
		);
		$appearance = isset( $input['appearance'] ) && is_array( $input['appearance'] ) ? $input['appearance'] : array();
		$output['appearance'] = isset( $existing_settings['appearance'] ) && is_array( $existing_settings['appearance'] )
			? array_merge( $appearance_defaults, $existing_settings['appearance'] )
			: $appearance_defaults;
		if ( isset( $input['appearance'] ) && is_array( $input['appearance'] ) ) {
			$a = $appearance;
			$output['appearance']['widget_position'] = isset( $a['widget_position'] ) && in_array( $a['widget_position'], array( 'left', 'right' ), true ) ? $a['widget_position'] : 'right';
			$primary_color = isset( $a['primary_color'] ) && is_string( $a['primary_color'] ) ? sanitize_hex_color( $a['primary_color'] ) : false;
			$panel_color = isset( $a['panel_color'] ) && is_string( $a['panel_color'] ) ? sanitize_hex_color( $a['panel_color'] ) : false;
			$output['appearance']['primary_color'] = $primary_color ? $primary_color : '#2aabee';
			$output['appearance']['panel_color'] = $panel_color ? $panel_color : '#ffffff';
			foreach ( array( 'desktop_width' => array( 280, 600, 340 ), 'desktop_height' => array( 320, 800, 480 ), 'mobile_width' => array( 260, 600, 340 ), 'mobile_height' => array( 300, 800, 420 ), 'desktop_bottom' => array( 0, 500, 24 ), 'mobile_bottom' => array( 0, 500, 60 ) ) as $key => $limits ) {
				$value = isset( $a[ $key ] ) && is_scalar( $a[ $key ] ) ? absint( $a[ $key ] ) : $limits[2];
				$output['appearance'][ $key ] = min( $limits[1], max( $limits[0], $value ) );
			}
			$output['appearance']['header_text'] = isset( $a['header_text'] ) && is_scalar( $a['header_text'] ) ? sanitize_text_field( (string) $a['header_text'] ) : 'Support';
			$output['appearance']['header_direction'] = isset( $a['header_direction'] ) && in_array( $a['header_direction'], array( 'auto', 'ltr', 'rtl' ), true ) ? $a['header_direction'] : 'auto';
			$icon_id = isset( $a['icon_id'] ) && is_scalar( $a['icon_id'] ) ? absint( $a['icon_id'] ) : 0;
			$output['appearance']['icon_id'] = $icon_id && wp_attachment_is_image( $icon_id ) ? $icon_id : 0;
		}

		/**
		 * Admin status.
		 */
		if (
			isset( $input['admin_status'] ) &&
			in_array(
				$input['admin_status'],
				array( 'online', 'offline' ),
				true
			)
		) {

			$output['admin_status'] = $input['admin_status'];

		} else {

			$output['admin_status'] = isset(
				$existing_settings['admin_status']
			)
				? $existing_settings['admin_status']
				: 'offline';
		}

		/**
		 * Enable chat.
		 */
		$output['enabled'] = ! empty(
			$input['enabled']
		);

		/**
		 * Delete data on uninstall.
		 */
		$output['delete_data_on_uninstall'] = ! empty(
			$input['delete_data_on_uninstall']
		);

		/**
		 * Cleanup frequency.
		 */
		$allowed_frequencies = array(
			'daily',
			'weekly',
			'monthly',
			'never',
		);

		$output['cleanup_frequency'] = isset(
			$input['cleanup_frequency']
		) &&
		in_array(
			$input['cleanup_frequency'],
			$allowed_frequencies,
			true
		)
			? $input['cleanup_frequency']
			: 'weekly';

		/**
		 * Cleanup retention.
		 */
		$allowed_retention = array(
			'7',
			'30',
			'90',
			'180',
			'365',
		);

		$output['cleanup_retention'] = isset(
			$input['cleanup_retention']
		) &&
		in_array(
			(string) $input['cleanup_retention'],
			$allowed_retention,
			true
		)
			? (string) $input['cleanup_retention']
			: '30';
			/**
 * Reschedule cleanup when settings change.
 */
TLCWT_Cleanup::reschedule();


		/**
		 * Reschedule cleanup after settings are saved.
		 */
		add_action(
			'shutdown',
			array( 'TLCWT_Cleanup', 'reschedule' )
		);

		return $output;
	}
	private static function generate_pairing_token() {

	$token = wp_generate_password(
		16,
		false,
		false
	);

	$settings = get_option(
		'tlcwt_settings',
		array()
	);

	$settings = is_array( $settings )
		? $settings
		: array();

	$settings['pairing_token_hash'] = wp_hash_password( $token );

	$settings['pairing_expires_at'] = time() + ( 10 * MINUTE_IN_SECONDS );

	update_option(
		'tlcwt_settings',
		$settings
	);

	return $token;
}

	/**
	 * Telegram section description.
	 *
	 * @return void
	 */
	public static function render_telegram_section() {
		?>
		<p>
			<?php
			esc_html_e(
				'Connect your Telegram bot to receive customer messages.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Chat section description.
	 *
	 * @return void
	 */
	public static function render_chat_section() {
		?>
		<p>
			<?php
			esc_html_e(
				'Configure the website chat widget.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Data management section description.
	 *
	 * @return void
	 */
	public static function render_data_section() {
		?>
		<p>
			<?php
			esc_html_e(
				'Control how TLC stores and removes temporary conversation data.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Bot token field.
	 *
	 * @return void
	 */
	public static function render_bot_token_field() {

		$settings = get_option(
			'tlcwt_settings',
			array()
		);

		$value = isset( $settings['bot_token'] )
			? $settings['bot_token']
			: '';
		?>

		<input
			type="password"
			name="tlcwt_settings[bot_token]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			autocomplete="new-password"
		>

		<p class="description">
			<?php
			esc_html_e(
				'Enter the token provided by BotFather.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</p>

		<?php
	}

	/**
	 * Telegram connection field.
	 *
	 * @return void
	 */
	public static function render_connect_telegram_field() {

		$connect_post_url = admin_url( 'admin-post.php' );

		$settings = get_option(
			'tlcwt_settings',
			array()
		);

		$connected = ! empty(
			$settings['telegram_connected']
		);
		?>

		<?php
		/**
		 * Dedicated nonce for the connect action, kept under its own field
		 * name so it never collides with the settings-group nonce that
		 * settings_fields() already prints in this same form.
		 */
		wp_nonce_field(
			'tlcwt_connect_telegram',
			'tlcwt_connect_nonce',
			false
		);
		?>

		<button
			type="button"
			id="tlcwt-connect-telegram"
			class="button button-primary"
			data-connect-url="<?php echo esc_url( $connect_post_url ); ?>"
		>
			<?php
			esc_html_e(
				'Connect Telegram',
				'tlc-live-chat-with-telegram'
			);
			?>
		</button>

		<?php if ( $connected ) : ?>

			<p
				class="description"
				style="color:#008a20;font-weight:600;"
			>
				<?php
				esc_html_e(
					'Telegram Connected',
					'tlc-live-chat-with-telegram'
				);
				?>
			</p>

		<?php endif; ?>

		<p class="description">
			<?php
			esc_html_e(
				'Connect your Telegram bot and configure the webhook automatically.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</p>

		<?php
	}

	/**
	 * Welcome message field.
	 *
	 * @return void
	 */
	public static function render_welcome_message_field() {

		$settings = get_option(
			'tlcwt_settings',
			array()
		);

		$value = isset( $settings['welcome_message'] )
			? $settings['welcome_message']
			: __( 'Hello! How can we help you?', 'tlc-live-chat-with-telegram' );
		?>

		<textarea
			name="tlcwt_settings[welcome_message]"
			rows="4"
			class="large-text"
		><?php echo esc_textarea( $value ); ?></textarea>

		<?php
	}

	/**
	 * Offline message field.
	 *
	 * @return void
	 */
	public static function render_offline_message_field() {

		$settings = get_option(
			'tlcwt_settings',
			array()
		);

		$value = isset( $settings['offline_message'] )
			? $settings['offline_message']
			: __( 'We received your message and will reply as soon as possible.', 'tlc-live-chat-with-telegram' );
		?>

		<textarea
			name="tlcwt_settings[offline_message]"
			rows="4"
			class="large-text"
		><?php echo esc_textarea( $value ); ?></textarea>

		<?php
	}

	/** Render widget design controls. */
	public static function render_appearance_field() {
		$settings = get_option( 'tlcwt_settings', array() );
		$a = isset( $settings['appearance'] ) && is_array( $settings['appearance'] ) ? $settings['appearance'] : array();
		$defaults = array( 'widget_position' => 'right', 'primary_color' => '#2aabee', 'panel_color' => '#ffffff', 'desktop_width' => 340, 'desktop_height' => 480, 'mobile_width' => 340, 'mobile_height' => 420, 'desktop_bottom' => 24, 'mobile_bottom' => 60, 'header_text' => __( 'Support', 'tlc-live-chat-with-telegram' ), 'header_direction' => 'auto', 'icon_id' => 0 );
		$a = array_merge( $defaults, $a );
		$icon_url = ! empty( $a['icon_id'] ) ? wp_get_attachment_image_url( absint( $a['icon_id'] ), 'thumbnail' ) : '';
		$icon_url = $icon_url ? $icon_url : TLCWT_URL . 'assets/telegram-icon.svg';
		$prefix = 'tlcwt_settings[appearance]';
		?>
		<div class="tlcwt-appearance-layout">
			<div class="tlcwt-appearance-controls">
				<label><?php esc_html_e( 'Widget position', 'tlc-live-chat-with-telegram' ); ?><br><select name="<?php echo esc_attr( $prefix ); ?>[widget_position]"><option value="right" <?php selected( $a['widget_position'], 'right' ); ?>><?php esc_html_e( 'Bottom right', 'tlc-live-chat-with-telegram' ); ?></option><option value="left" <?php selected( $a['widget_position'], 'left' ); ?>><?php esc_html_e( 'Bottom left', 'tlc-live-chat-with-telegram' ); ?></option></select></label>
				<label><?php esc_html_e( 'Main color', 'tlc-live-chat-with-telegram' ); ?><br><input class="tlcwt-color" type="text" name="<?php echo esc_attr( $prefix ); ?>[primary_color]" value="<?php echo esc_attr( $a['primary_color'] ); ?>"></label>
				<label><?php esc_html_e( 'Chat background', 'tlc-live-chat-with-telegram' ); ?><br><input class="tlcwt-color" type="text" name="<?php echo esc_attr( $prefix ); ?>[panel_color]" value="<?php echo esc_attr( $a['panel_color'] ); ?>"></label>
				<label><?php esc_html_e( 'Header title', 'tlc-live-chat-with-telegram' ); ?><br><input type="text" class="regular-text" name="<?php echo esc_attr( $prefix ); ?>[header_text]" value="<?php echo esc_attr( $a['header_text'] ); ?>"></label>
				<label><?php esc_html_e( 'Title direction', 'tlc-live-chat-with-telegram' ); ?><br><select name="<?php echo esc_attr( $prefix ); ?>[header_direction]"><option value="auto" <?php selected( $a['header_direction'], 'auto' ); ?>><?php esc_html_e( 'Automatic', 'tlc-live-chat-with-telegram' ); ?></option><option value="rtl" <?php selected( $a['header_direction'], 'rtl' ); ?>>RTL</option><option value="ltr" <?php selected( $a['header_direction'], 'ltr' ); ?>>LTR</option></select></label>
				<?php foreach ( array( 'desktop_width' => __( 'Desktop width (px)', 'tlc-live-chat-with-telegram' ), 'desktop_height' => __( 'Desktop height (px)', 'tlc-live-chat-with-telegram' ), 'mobile_width' => __( 'Mobile width (px)', 'tlc-live-chat-with-telegram' ), 'mobile_height' => __( 'Mobile height (px)', 'tlc-live-chat-with-telegram' ), 'desktop_bottom' => __( 'Desktop distance from bottom (px)', 'tlc-live-chat-with-telegram' ), 'mobile_bottom' => __( 'Mobile distance from bottom (px)', 'tlc-live-chat-with-telegram' ) ) as $key => $label ) : ?>
				<label><?php echo esc_html( $label ); ?><br><input type="number" min="<?php echo false !== strpos( $key, 'bottom' ) ? '0' : '260'; ?>" max="<?php echo false !== strpos( $key, 'bottom' ) ? '500' : '800'; ?>" step="1" name="<?php echo esc_attr( $prefix . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $a[ $key ] ); ?>"></label>
				<?php endforeach; ?>
				<div class="tlcwt-icon-picker"><span><?php esc_html_e( 'Chat icon', 'tlc-live-chat-with-telegram' ); ?></span><input type="hidden" name="<?php echo esc_attr( $prefix ); ?>[icon_id]" value="<?php echo esc_attr( absint( $a['icon_id'] ) ); ?>"><img class="tlcwt-icon-preview" src="<?php echo esc_url( $icon_url ); ?>" alt=""><button type="button" class="button" id="tlcwt-select-icon"><?php esc_html_e( 'Choose image', 'tlc-live-chat-with-telegram' ); ?></button><button type="button" class="button-link-delete" id="tlcwt-remove-icon" data-default-icon="<?php echo esc_url( TLCWT_URL . 'assets/telegram-icon.svg' ); ?>" <?php disabled( empty( $a['icon_id'] ) ); ?>><?php esc_html_e( 'Use default', 'tlc-live-chat-with-telegram' ); ?></button></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Enable chat field.
	 *
	 * @return void
	 */
	public static function render_enabled_field() {

		$settings = get_option(
			'tlcwt_settings',
			array()
		);

		$enabled = isset( $settings['enabled'] )
			? $settings['enabled']
			: true;
		?>

		<label>
			<input
				type="checkbox"
				name="tlcwt_settings[enabled]"
				value="1"
				<?php checked( $enabled, true ); ?>
			>

			<?php
			esc_html_e(
				'Enable the chat widget on the website.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</label>

		<?php
	}

	/**
	 * Admin status field.
	 *
	 * @return void
	 */
	public static function render_admin_status_field() {

		$settings = get_option(
			'tlcwt_settings',
			array()
		);

		$status = isset( $settings['admin_status'] )
			? $settings['admin_status']
			: 'offline';
		?>

		<select name="tlcwt_settings[admin_status]">

			<option
				value="online"
				<?php selected( $status, 'online' ); ?>
			>
				<?php
				esc_html_e(
					'Online',
					'tlc-live-chat-with-telegram'
				);
				?>
			</option>

			<option
				value="offline"
				<?php selected( $status, 'offline' ); ?>
			>
				<?php
				esc_html_e(
					'Offline',
					'tlc-live-chat-with-telegram'
				);
				?>
			</option>

		</select>

		<p class="description">
			<?php
			esc_html_e(
				'Set whether the administrator is currently available to respond to visitors.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</p>

		<?php
	}

	/**
	 * Delete data on uninstall field.
	 *
	 * @return void
	 */
	public static function render_delete_data_field() {

		$settings = get_option(
			'tlcwt_settings',
			array()
		);

		$delete_data = ! empty(
			$settings['delete_data_on_uninstall']
		);
		?>

		<label>
			<input
				type="checkbox"
				name="tlcwt_settings[delete_data_on_uninstall]"
				value="1"
				<?php checked( $delete_data, true ); ?>
			>

			<?php
			esc_html_e(
				'Delete all plugin data when the plugin is uninstalled.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</label>

		<p class="description">
			<?php
			esc_html_e(
				'This permanently deletes conversations, messages, settings, and plugin database tables.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</p>

		<?php if ( $delete_data ) : ?>

			<p style="color:#b32d2e;font-weight:600;">
				<?php
				esc_html_e(
					'Warning: Plugin data will be permanently deleted when the plugin is uninstalled.',
					'tlc-live-chat-with-telegram'
				);
				?>
			</p>

		<?php endif; ?>

		<?php
	}

	/**
	 * Cleanup frequency field.
	 *
	 * @return void
	 */
	public static function render_cleanup_frequency_field() {

		$settings = get_option(
			'tlcwt_settings',
			array()
		);

		$value = isset( $settings['cleanup_frequency'] )
			? $settings['cleanup_frequency']
			: 'weekly';
		?>

		<select name="tlcwt_settings[cleanup_frequency]">

			<option value="daily" <?php selected( $value, 'daily' ); ?>>
				<?php esc_html_e( 'Every 24 hours', 'tlc-live-chat-with-telegram' ); ?>
			</option>

			<option value="weekly" <?php selected( $value, 'weekly' ); ?>>
				<?php esc_html_e( 'Every 7 days', 'tlc-live-chat-with-telegram' ); ?>
			</option>

			<option value="monthly" <?php selected( $value, 'monthly' ); ?>>
				<?php esc_html_e( 'Monthly', 'tlc-live-chat-with-telegram' ); ?>
			</option>

			<option value="never" <?php selected( $value, 'never' ); ?>>
				<?php esc_html_e( 'Never', 'tlc-live-chat-with-telegram' ); ?>
			</option>

		</select>

		<p class="description">
			<?php
			esc_html_e(
				'Choose how often old closed conversations should be cleaned.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</p>

		<?php
	}

	/**
	 * Cleanup retention field.
	 *
	 * @return void
	 */
	public static function render_cleanup_retention_field() {

		$settings = get_option(
			'tlcwt_settings',
			array()
		);

		$value = isset( $settings['cleanup_retention'] )
			? $settings['cleanup_retention']
			: '30';
		?>

		<select name="tlcwt_settings[cleanup_retention]">

			<option value="7" <?php selected( $value, '7' ); ?>>
				<?php esc_html_e( '7 days', 'tlc-live-chat-with-telegram' ); ?>
			</option>

			<option value="30" <?php selected( $value, '30' ); ?>>
				<?php esc_html_e( '30 days', 'tlc-live-chat-with-telegram' ); ?>
			</option>

			<option value="90" <?php selected( $value, '90' ); ?>>
				<?php esc_html_e( '90 days', 'tlc-live-chat-with-telegram' ); ?>
			</option>

			<option value="180" <?php selected( $value, '180' ); ?>>
				<?php esc_html_e( '6 months', 'tlc-live-chat-with-telegram' ); ?>
			</option>

			<option value="365" <?php selected( $value, '365' ); ?>>
				<?php esc_html_e( '1 year', 'tlc-live-chat-with-telegram' ); ?>
			</option>

		</select>

		<p class="description">
			<?php
			esc_html_e(
				'Only old closed conversations will be removed. Active conversations are never removed by this cleanup.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</p>

		<?php
	}

	/**
	 * Manual cleanup field.
	 *
	 * @return void
	 */
	public static function render_cleanup_manual_field() {

		$url = wp_nonce_url(
			admin_url(
				'admin-post.php?action=tlcwt_run_cleanup'
			),
			'tlcwt_run_cleanup'
		);
		?>

		<a
			href="<?php echo esc_url( $url ); ?>"
			class="button"
		>
			<?php
			esc_html_e(
				'Run Cleanup Now',
				'tlc-live-chat-with-telegram'
			);
			?>
		</a>

		<p class="description">
			<?php
			esc_html_e(
				'This removes only old closed conversations according to your retention setting.',
				'tlc-live-chat-with-telegram'
			);
			?>
		</p>

		<?php
	}

	/**
	 * Run cleanup manually.
	 *
	 * @return void
	 */
	public static function run_cleanup_now() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__(
					'You do not have permission to perform this action.',
					'tlc-live-chat-with-telegram'
				)
			);
		}

		check_admin_referer(
			'tlcwt_run_cleanup'
		);

		TLCWT_Cleanup::run();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'tlc-live-chat-with-telegram',
					'tlcwt_cleaned' => '1',
				),
				admin_url( 'admin.php' )
			)
		);

		exit;
	}


	/**
	 * Handle Telegram connection request.
	 *
	 * @return void
	 */
	public static function connect_telegram() {

		if ( ! current_user_can( 'manage_options' ) ) {

			wp_die(
				esc_html__(
					'You do not have permission to perform this action.',
					'tlc-live-chat-with-telegram'
				)
			);
		}

		check_admin_referer(
			'tlcwt_connect_telegram',
			'tlcwt_connect_nonce'
		);

		/**
		 * The Connect Telegram button submits the whole settings form.
		 * If it carried field values (e.g. a bot token the admin just
		 * typed but never explicitly saved), persist them first using
		 * the same sanitize callback the normal Save Changes flow uses,
		 * so connecting never runs against a stale saved token.
		 */
		if ( isset( $_POST['tlcwt_settings'] ) && is_array( $_POST['tlcwt_settings'] ) ) {

			$submitted_settings = wp_unslash(
				$_POST['tlcwt_settings']
			);

			$sanitized_settings = self::sanitize_settings(
				$submitted_settings
			);

			update_option(
				'tlcwt_settings',
				$sanitized_settings
			);
		}

		$settings = get_option(
			'tlcwt_settings',
			array()
		);

		$settings = is_array( $settings )
			? $settings
			: array();

		$settings['telegram_connected'] = false;

		$bot_token = isset(
			$settings['bot_token']
		)
			? trim(
				(string) $settings['bot_token']
			)
			: '';

		

		/**
		 * Validate bot token.
		 */
		if ( empty( $bot_token ) ) {

			update_option(
				'tlcwt_settings',
				$settings
			);

			self::redirect_connection_error(
				'missing_bot_token'
			);
		}
		
		/**
 * Retrieve Telegram bot information.
 */
$bot_info = TLCWT_Telegram::get_me();

if ( is_wp_error( $bot_info ) ) {

	$settings['telegram_connected'] = false;

	update_option(
		'tlcwt_settings',
		$settings
	);

	self::redirect_connection_error(
		$bot_info->get_error_code()
	);
}

/**
 * Extract and store bot username.
 */
$bot_username = isset( $bot_info['username'] )
	? sanitize_user(
		(string) $bot_info['username'],
		true
	)
	: '';

if ( empty( $bot_username ) ) {

	$settings['telegram_connected'] = false;

	update_option(
		'tlcwt_settings',
		$settings
	);

	self::redirect_connection_error(
		'tlcwt_bot_username_missing'
	);
}

$settings['bot_username'] = $bot_username;

update_option(
	'tlcwt_settings',
	$settings
);
/**
 * Generate a new one-time pairing token.
 */
$pairing_token = self::generate_pairing_token();
$settings = get_option( 'tlcwt_settings', array() );
$settings = is_array( $settings ) ? $settings : array();
$settings['pairing_token_plain'] = $pairing_token;
update_option( 'tlcwt_settings', $settings );
/**
 * Build Telegram pairing deep link.
 */
$pairing_url = sprintf(
	'https://t.me/%s?start=%s',
	$bot_username,
	rawurlencode( $pairing_token )
);
	

		/**
		 * Ensure webhook secret exists.
		 */
		if ( empty( $settings['webhook_secret'] ) ) {

			$settings['webhook_secret'] = wp_generate_password(
				32,
				false,
				false
			);

			update_option(
				'tlcwt_settings',
				$settings
			);
		}

		/**
		 * Register webhook.
		 */
		$result = TLCWT_Telegram::set_webhook();

		if ( is_wp_error( $result ) ) {

			$settings['telegram_connected'] = false;

			update_option(
				'tlcwt_settings',
				$settings
			);

			self::redirect_connection_error(
				$result->get_error_code()
			);
		}

		/**
		 * Verify webhook.
		 */
		$webhook_info = self::get_webhook_info();

		if ( is_wp_error( $webhook_info ) ) {

			$settings['telegram_connected'] = false;

			update_option(
				'tlcwt_settings',
				$settings
			);

			self::redirect_connection_error(
				$webhook_info->get_error_code()
			);
		}

		$webhook_url = isset(
			$webhook_info['result']['url']
		)
			? (string) $webhook_info['result']['url']
			: '';

		if ( empty( $webhook_url ) ) {

			$settings['telegram_connected'] = false;

			update_option(
				'tlcwt_settings',
				$settings
			);

			self::redirect_connection_error(
				'webhook_not_registered'
			);
		}

		$expected_webhook_url = rest_url(
			'tlcwt/v1/telegram/webhook'
		);

		if (
			untrailingslashit( $webhook_url ) !==
			untrailingslashit( $expected_webhook_url )
		) {

			$settings['telegram_connected'] = false;

			update_option(
				'tlcwt_settings',
				$settings
			);

			self::redirect_connection_error(
				'webhook_url_mismatch'
			);
		}

		/**
		 * Connected successfully.
		 */
		$settings['telegram_connected'] = true;

		update_option(
			'tlcwt_settings',
			$settings
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'tlcwt-administrators',
					'tlcwt_connected' => '1',
				),
				admin_url( 'admin.php' )
			)
		);

		exit;
	}

	/**
	 * Redirect connection error.
	 *
	 * @param string $error Error code.
	 * @return void
	 */
	private static function redirect_connection_error( $error ) {

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => 'tlc-live-chat-with-telegram',
					'tlcwt_error' => sanitize_key( $error ),
				),
				admin_url( 'admin.php' )
			)
		);

		exit;
	}

	/**
	 * Get Telegram webhook information.
	 *
	 * @return array|WP_Error
	 */
	public static function get_webhook_info() {

		$settings = get_option(
			'tlcwt_settings',
			array()
		);

		$settings = is_array( $settings )
			? $settings
			: array();

		$token = isset(
			$settings['bot_token']
		)
			? trim(
				(string) $settings['bot_token']
			)
			: '';

		if ( empty( $token ) ) {

			return new WP_Error(
				'tlcwt_missing_bot_token',
				__(
					'Telegram bot token is not configured.',
					'tlc-live-chat-with-telegram'
				)
			);
		}

		$url = sprintf(
			'https://api.telegram.org/bot%s/getWebhookInfo',
			$token
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {

			return new WP_Error(
				'tlcwt_telegram_connection_error',
				$response->get_error_message()
			);
		}

		$status_code = wp_remote_retrieve_response_code(
			$response
		);

		$body = json_decode(
			wp_remote_retrieve_body( $response ),
			true
		);

		if (
			200 !== $status_code ||
			! is_array( $body ) ||
			empty( $body['ok'] )
		) {

			$error_message = isset(
				$body['description']
			)
				? $body['description']
				: __(
					'Could not retrieve Telegram webhook information.',
					'tlc-live-chat-with-telegram'
				);

			return new WP_Error(
				'tlcwt_telegram_webhook_info_error',
				$error_message,
				array(
					'status' => $status_code,
					'body'   => $body,
				)
			);
		}

		return $body;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>

		<div class="wrap tlcwt-admin-page">

			<h1 class="tlcwt-admin-title">
				<img src="<?php echo esc_url( TLCWT_URL . 'assets/telegram-icon.svg' ); ?>" alt="" aria-hidden="true">
				<?php
				esc_html_e(
					'TLC - Live chat with Telegram',
					'tlc-live-chat-with-telegram'
				);
				?>
			</h1>

			<?php if ( filter_input( INPUT_GET, 'tlcwt_connected', FILTER_VALIDATE_INT ) ) : ?>

				<div class="notice notice-success is-dismissible">
					<p>
						<strong>
							<?php
							esc_html_e(
								'Telegram connected successfully.',
								'tlc-live-chat-with-telegram'
							);
							?>
						</strong>
					</p>
				</div>

			<?php endif; ?>

			<?php if ( filter_input( INPUT_GET, 'tlcwt_cleaned', FILTER_VALIDATE_INT ) ) : ?>

				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						esc_html_e(
							'Cleanup completed successfully.',
							'tlc-live-chat-with-telegram'
						);
						?>
					</p>
				</div>

			<?php endif; ?>

			<?php if ( filter_input( INPUT_GET, 'tlcwt_error', FILTER_UNSAFE_RAW ) ) : ?>

				<div class="notice notice-error is-dismissible">
					<p>

						<?php

						$error = sanitize_key( (string) filter_input( INPUT_GET, 'tlcwt_error', FILTER_UNSAFE_RAW ) );

						switch ( $error ) {

							case 'missing_bot_token':

								esc_html_e(
									'Bot Token is required.',
									'tlc-live-chat-with-telegram'
								);

								break;

							case 'missing_chat_id':

								esc_html_e(
									'Admin Chat ID is required.',
									'tlc-live-chat-with-telegram'
								);

								break;

							case 'tlcwt_missing_webhook_secret':

								esc_html_e(
									'Webhook Secret is missing.',
									'tlc-live-chat-with-telegram'
								);

								break;

							case 'tlcwt_invalid_webhook_secret':

								esc_html_e(
									'Webhook Secret contains invalid characters.',
									'tlc-live-chat-with-telegram'
								);

								break;

							case 'tlcwt_telegram_connection_error':

								esc_html_e(
									'Could not connect to Telegram.',
									'tlc-live-chat-with-telegram'
								);

								break;

							case 'tlcwt_telegram_webhook_error':

								esc_html_e(
									'Telegram rejected the webhook registration.',
									'tlc-live-chat-with-telegram'
								);

								break;

							case 'tlcwt_telegram_webhook_info_error':

								esc_html_e(
									'Could not verify the Telegram webhook.',
									'tlc-live-chat-with-telegram'
								);

								break;

							case 'webhook_not_registered':

								esc_html_e(
									'Telegram webhook was not registered.',
									'tlc-live-chat-with-telegram'
								);

								break;

							case 'webhook_url_mismatch':

								esc_html_e(
									'Telegram webhook URL does not match this website.',
									'tlc-live-chat-with-telegram'
								);

								break;

							default:

								esc_html_e(
									'Could not connect to Telegram. Please check your Bot Token and settings.',
									'tlc-live-chat-with-telegram'
								);

								break;
						}

						?>

					</p>
				</div>

			<?php endif; ?>

			<form
				id="tlcwt-settings-form"
				method="post"
				action="options.php"
			>

				<?php

				settings_fields(
					'tlcwt_settings_group'
				);

				do_settings_sections(
					'tlc-live-chat-with-telegram'
				);

				submit_button();

				?>

			</form>

		</div>

		<?php
	}
}
