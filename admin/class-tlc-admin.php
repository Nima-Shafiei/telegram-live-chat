<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TLC_Admin {

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
	'admin_post_tlc_add_administrator',
	array( __CLASS__, 'add_administrator' )
);

		add_action(
			'admin_post_tlc_connect_telegram',
			array( __CLASS__, 'connect_telegram' )
		);
		add_action(
	'admin_post_tlc_remove_administrator',
	array( __CLASS__, 'remove_administrator' )
);

		add_action(
			'admin_post_tlc_run_cleanup',
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

		if ( ! in_array( $hook_suffix, array( 'toplevel_page_telegram-live-chat', 'telegram-live-chat_page_tlc-administrators' ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'tlc-admin',
			TLC_URL . 'admin/css/admin.css',
			array(),
			TLC_VERSION
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
		'tlc_settings',
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

	?>

	<div class="wrap tlc-admin-page">

		<h1 class="tlc-admin-title">
			<img src="<?php echo esc_url( TLC_URL . 'assets/telegram-icon.svg' ); ?>" alt="" aria-hidden="true">
			<?php
			esc_html_e(
				'Administrators',
				'telegram-live-chat'
			);
			?>
		</h1>

		<p>
			<?php
			esc_html_e(
				'Add another Telegram administrator to receive and reply to customer messages.',
				'telegram-live-chat'
			);
			?>
		</p>

		<form class="tlc-pairing-form"
			method="post"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		>

			<input
				type="hidden"
				name="action"
				value="tlc_add_administrator"
			>

			<?php
			wp_nonce_field(
				'tlc_add_administrator'
			);
			?>

			<?php
				submit_button(
				__( 'Generate Pairing Link', 'telegram-live-chat' ),
				'primary tlc-telegram-button'
			);
			?>

		</form>

				<?php if ( ! empty( $pairing_url ) ) : ?>

			<hr>

			<h2>
				<?php esc_html_e( 'Administrator Pairing', 'telegram-live-chat' ); ?>
			</h2>

			<p>
				<?php esc_html_e( 'Send this pairing link to the Telegram administrator you want to add. It expires in 10 minutes.', 'telegram-live-chat' ); ?>
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
					<?php esc_html_e( 'Connect your Telegram bot in Settings first, then generate the pairing link.', 'telegram-live-chat' ); ?>
				</p>
			</div>

		<?php endif; ?>
		<hr>

		<h2>
			<?php esc_html_e( 'Telegram Administrators', 'telegram-live-chat' ); ?>
		</h2>

		<table class="widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Chat ID', 'telegram-live-chat' ); ?></th>
					<th><?php esc_html_e( 'Role', 'telegram-live-chat' ); ?></th>
					<th><?php esc_html_e( 'Action', 'telegram-live-chat' ); ?></th>
				</tr>
			</thead>
			<tbody>

				<?php if ( ! empty( $owner_chat_id ) ) : ?>
					<tr>
						<td><?php echo esc_html( $owner_chat_id ); ?></td>
						<td><?php esc_html_e( 'Owner', 'telegram-live-chat' ); ?></td>
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
								'action'  => 'tlc_remove_administrator',
								'chat_id' => $admin_chat_id,
							),
							admin_url( 'admin-post.php' )
						),
						'tlc_remove_administrator'
					);
					?>
					<tr>
						<td><?php echo esc_html( $admin_chat_id ); ?></td>
						<td><?php esc_html_e( 'Administrator', 'telegram-live-chat' ); ?></td>
						<td>
	
		<a href="<?php echo esc_url( $remove_url ); ?>"
		class="button button-secondary"
		onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to remove this administrator?', 'telegram-live-chat' ) ); ?>');"
	>
		<?php esc_html_e( 'Remove', 'telegram-live-chat' ); ?>
	</a>
</td>
					</tr>
				<?php endforeach; ?>

				<?php if ( empty( $owner_chat_id ) && empty( $admins ) ) : ?>
					<tr>
						<td colspan="3">
							<?php esc_html_e( 'No administrators paired yet.', 'telegram-live-chat' ); ?>
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
				'telegram-live-chat'
			)
		);
	}

	check_admin_referer(
		'tlc_add_administrator'
	);

	$settings = get_option(
		'tlc_settings',
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
		'tlc_settings',
		$settings
	);

	wp_safe_redirect(
		add_query_arg(
			array(
				'page' => 'tlc-administrators',
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
				'telegram-live-chat'
			)
		);
	}

	check_admin_referer( 'tlc_remove_administrator' );

	$chat_id = sanitize_text_field(
		(string) filter_input( INPUT_GET, 'chat_id', FILTER_UNSAFE_RAW )
	);

	if ( empty( $chat_id ) ) {

		wp_safe_redirect(
			admin_url( 'admin.php?page=tlc-administrators' )
		);

		exit;
	}

	$settings = get_option(
		'tlc_settings',
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
				'telegram-live-chat'
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
		'tlc_settings',
		$settings
	);

	wp_safe_redirect(
		admin_url(
			'admin.php?page=tlc-administrators&removed=1'
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
			__( 'TLC - Live chat with Telegram', 'telegram-live-chat' ),
			__( 'TLC - Live chat with Telegram', 'telegram-live-chat' ),
			'manage_options',
			'telegram-live-chat',
			array( __CLASS__, 'render_settings_page' ),
			TLC_URL . 'assets/telegram-icon.svg',
			30
		);

		add_submenu_page(
			'telegram-live-chat',
			__( 'Settings', 'telegram-live-chat' ),
			__( 'Settings', 'telegram-live-chat' ),
			'manage_options',
			'telegram-live-chat',
			array( __CLASS__, 'render_settings_page' )
		);

		add_submenu_page(
			'telegram-live-chat',
			__( 'Administrators', 'telegram-live-chat' ),
			__( 'Administrators', 'telegram-live-chat' ),
			'manage_options',
			'tlc-administrators',
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
			'tlc_settings_group',
			'tlc_settings',
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
			'tlc_telegram_section',
			__( 'Telegram Settings', 'telegram-live-chat' ),
			array( __CLASS__, 'render_telegram_section' ),
			'telegram-live-chat'
		);

		add_settings_field(
			'tlc_bot_token',
			__( 'Bot Token', 'telegram-live-chat' ),
			array( __CLASS__, 'render_bot_token_field' ),
			'telegram-live-chat',
			'tlc_telegram_section'
		);

		add_settings_field(
			'tlc_connect_telegram',
			__( 'Telegram Connection', 'telegram-live-chat' ),
			array( __CLASS__, 'render_connect_telegram_field' ),
			'telegram-live-chat',
			'tlc_telegram_section'
		);

		/**
		 * Chat section.
		 */
		add_settings_section(
			'tlc_chat_section',
			__( 'Chat Settings', 'telegram-live-chat' ),
			array( __CLASS__, 'render_chat_section' ),
			'telegram-live-chat'
		);

		add_settings_field(
			'tlc_welcome_message',
			__( 'Welcome Message', 'telegram-live-chat' ),
			array( __CLASS__, 'render_welcome_message_field' ),
			'telegram-live-chat',
			'tlc_chat_section'
		);

		add_settings_field(
			'tlc_offline_message',
			__( 'Offline Message', 'telegram-live-chat' ),
			array( __CLASS__, 'render_offline_message_field' ),
			'telegram-live-chat',
			'tlc_chat_section'
		);

		add_settings_field(
			'tlc_enabled',
			__( 'Enable Chat', 'telegram-live-chat' ),
			array( __CLASS__, 'render_enabled_field' ),
			'telegram-live-chat',
			'tlc_chat_section'
		);

		add_settings_field(
			'tlc_admin_status',
			__( 'Admin Status', 'telegram-live-chat' ),
			array( __CLASS__, 'render_admin_status_field' ),
			'telegram-live-chat',
			'tlc_chat_section'
		);

		/**
		 * Data management section.
		 */
		add_settings_section(
			'tlc_data_section',
			__( 'Data Management', 'telegram-live-chat' ),
			array( __CLASS__, 'render_data_section' ),
			'telegram-live-chat'
		);

		add_settings_field(
			'tlc_delete_data',
			__( 'Delete Data on Uninstall', 'telegram-live-chat' ),
			array( __CLASS__, 'render_delete_data_field' ),
			'telegram-live-chat',
			'tlc_data_section'
		);

		add_settings_field(
			'tlc_cleanup_frequency',
			__( 'Cleanup Frequency', 'telegram-live-chat' ),
			array( __CLASS__, 'render_cleanup_frequency_field' ),
			'telegram-live-chat',
			'tlc_data_section'
		);

		add_settings_field(
			'tlc_cleanup_retention',
			__( 'Data Retention', 'telegram-live-chat' ),
			array( __CLASS__, 'render_cleanup_retention_field' ),
			'telegram-live-chat',
			'tlc_data_section'
		);

		add_settings_field(
			'tlc_cleanup_manual',
			__( 'Manual Cleanup', 'telegram-live-chat' ),
			array( __CLASS__, 'render_cleanup_manual_field' ),
			'telegram-live-chat',
			'tlc_data_section'
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
			'tlc_settings',
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
TLC_Cleanup::reschedule();


		/**
		 * Reschedule cleanup after settings are saved.
		 */
		add_action(
			'shutdown',
			array( 'TLC_Cleanup', 'reschedule' )
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
		'tlc_settings',
		array()
	);

	$settings = is_array( $settings )
		? $settings
		: array();

	$settings['pairing_token_hash'] = hash(
		'sha256',
		$token
	);

	$settings['pairing_expires_at'] = time() + ( 10 * MINUTE_IN_SECONDS );

	update_option(
		'tlc_settings',
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
				'telegram-live-chat'
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
				'telegram-live-chat'
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
				'telegram-live-chat'
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
			'tlc_settings',
			array()
		);

		$value = isset( $settings['bot_token'] )
			? $settings['bot_token']
			: '';
		?>

		<input
			type="password"
			name="tlc_settings[bot_token]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			autocomplete="new-password"
		>

		<p class="description">
			<?php
			esc_html_e(
				'Enter the token provided by BotFather.',
				'telegram-live-chat'
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

		$connect_url = wp_nonce_url(
			admin_url(
				'admin-post.php?action=tlc_connect_telegram'
			),
			'tlc_connect_telegram'
		);

		$settings = get_option(
			'tlc_settings',
			array()
		);

		$connected = ! empty(
			$settings['telegram_connected']
		);
		?>

		<a
			href="<?php echo esc_url( $connect_url ); ?>"
			class="button button-primary"
		>
			<?php
			esc_html_e(
				'Connect Telegram',
				'telegram-live-chat'
			);
			?>
		</a>

		<?php if ( $connected ) : ?>

			<p
				class="description"
				style="color:#008a20;font-weight:600;"
			>
				<?php
				esc_html_e(
					'Telegram Connected',
					'telegram-live-chat'
				);
				?>
			</p>

		<?php endif; ?>

		<p class="description">
			<?php
			esc_html_e(
				'Connect your Telegram bot and configure the webhook automatically.',
				'telegram-live-chat'
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
			'tlc_settings',
			array()
		);

		$value = isset( $settings['welcome_message'] )
			? $settings['welcome_message']
			: __( 'Hello! How can we help you?', 'telegram-live-chat' );
		?>

		<textarea
			name="tlc_settings[welcome_message]"
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
			'tlc_settings',
			array()
		);

		$value = isset( $settings['offline_message'] )
			? $settings['offline_message']
			: __( 'We received your message and will reply as soon as possible.', 'telegram-live-chat' );
		?>

		<textarea
			name="tlc_settings[offline_message]"
			rows="4"
			class="large-text"
		><?php echo esc_textarea( $value ); ?></textarea>

		<?php
	}

	/**
	 * Enable chat field.
	 *
	 * @return void
	 */
	public static function render_enabled_field() {

		$settings = get_option(
			'tlc_settings',
			array()
		);

		$enabled = isset( $settings['enabled'] )
			? $settings['enabled']
			: true;
		?>

		<label>
			<input
				type="checkbox"
				name="tlc_settings[enabled]"
				value="1"
				<?php checked( $enabled, true ); ?>
			>

			<?php
			esc_html_e(
				'Enable the chat widget on the website.',
				'telegram-live-chat'
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
			'tlc_settings',
			array()
		);

		$status = isset( $settings['admin_status'] )
			? $settings['admin_status']
			: 'offline';
		?>

		<select name="tlc_settings[admin_status]">

			<option
				value="online"
				<?php selected( $status, 'online' ); ?>
			>
				<?php
				esc_html_e(
					'Online',
					'telegram-live-chat'
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
					'telegram-live-chat'
				);
				?>
			</option>

		</select>

		<p class="description">
			<?php
			esc_html_e(
				'Set whether the administrator is currently available to respond to visitors.',
				'telegram-live-chat'
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
			'tlc_settings',
			array()
		);

		$delete_data = ! empty(
			$settings['delete_data_on_uninstall']
		);
		?>

		<label>
			<input
				type="checkbox"
				name="tlc_settings[delete_data_on_uninstall]"
				value="1"
				<?php checked( $delete_data, true ); ?>
			>

			<?php
			esc_html_e(
				'Delete all plugin data when the plugin is uninstalled.',
				'telegram-live-chat'
			);
			?>
		</label>

		<p class="description">
			<?php
			esc_html_e(
				'This permanently deletes conversations, messages, settings, and plugin database tables.',
				'telegram-live-chat'
			);
			?>
		</p>

		<?php if ( $delete_data ) : ?>

			<p style="color:#b32d2e;font-weight:600;">
				<?php
				esc_html_e(
					'Warning: Plugin data will be permanently deleted when the plugin is uninstalled.',
					'telegram-live-chat'
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
			'tlc_settings',
			array()
		);

		$value = isset( $settings['cleanup_frequency'] )
			? $settings['cleanup_frequency']
			: 'weekly';
		?>

		<select name="tlc_settings[cleanup_frequency]">

			<option value="daily" <?php selected( $value, 'daily' ); ?>>
				<?php esc_html_e( 'Every 24 hours', 'telegram-live-chat' ); ?>
			</option>

			<option value="weekly" <?php selected( $value, 'weekly' ); ?>>
				<?php esc_html_e( 'Every 7 days', 'telegram-live-chat' ); ?>
			</option>

			<option value="monthly" <?php selected( $value, 'monthly' ); ?>>
				<?php esc_html_e( 'Monthly', 'telegram-live-chat' ); ?>
			</option>

			<option value="never" <?php selected( $value, 'never' ); ?>>
				<?php esc_html_e( 'Never', 'telegram-live-chat' ); ?>
			</option>

		</select>

		<p class="description">
			<?php
			esc_html_e(
				'Choose how often old closed conversations should be cleaned.',
				'telegram-live-chat'
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
			'tlc_settings',
			array()
		);

		$value = isset( $settings['cleanup_retention'] )
			? $settings['cleanup_retention']
			: '30';
		?>

		<select name="tlc_settings[cleanup_retention]">

			<option value="7" <?php selected( $value, '7' ); ?>>
				<?php esc_html_e( '7 days', 'telegram-live-chat' ); ?>
			</option>

			<option value="30" <?php selected( $value, '30' ); ?>>
				<?php esc_html_e( '30 days', 'telegram-live-chat' ); ?>
			</option>

			<option value="90" <?php selected( $value, '90' ); ?>>
				<?php esc_html_e( '90 days', 'telegram-live-chat' ); ?>
			</option>

			<option value="180" <?php selected( $value, '180' ); ?>>
				<?php esc_html_e( '6 months', 'telegram-live-chat' ); ?>
			</option>

			<option value="365" <?php selected( $value, '365' ); ?>>
				<?php esc_html_e( '1 year', 'telegram-live-chat' ); ?>
			</option>

		</select>

		<p class="description">
			<?php
			esc_html_e(
				'Only old closed conversations will be removed. Active conversations are never removed by this cleanup.',
				'telegram-live-chat'
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
				'admin-post.php?action=tlc_run_cleanup'
			),
			'tlc_run_cleanup'
		);
		?>

		<a
			href="<?php echo esc_url( $url ); ?>"
			class="button"
		>
			<?php
			esc_html_e(
				'Run Cleanup Now',
				'telegram-live-chat'
			);
			?>
		</a>

		<p class="description">
			<?php
			esc_html_e(
				'This removes only old closed conversations according to your retention setting.',
				'telegram-live-chat'
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
					'telegram-live-chat'
				)
			);
		}

		check_admin_referer(
			'tlc_run_cleanup'
		);

		TLC_Cleanup::run();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'telegram-live-chat',
					'tlc_cleaned' => '1',
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
					'telegram-live-chat'
				)
			);
		}

		check_admin_referer(
			'tlc_connect_telegram'
		);

		$settings = get_option(
			'tlc_settings',
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
				'tlc_settings',
				$settings
			);

			self::redirect_connection_error(
				'missing_bot_token'
			);
		}
		
		/**
 * Retrieve Telegram bot information.
 */
$bot_info = TLC_Telegram::get_me();

if ( is_wp_error( $bot_info ) ) {

	$settings['telegram_connected'] = false;

	update_option(
		'tlc_settings',
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
		'tlc_settings',
		$settings
	);

	self::redirect_connection_error(
		'tlc_bot_username_missing'
	);
}

$settings['bot_username'] = $bot_username;

update_option(
	'tlc_settings',
	$settings
);
/**
 * Generate a new one-time pairing token.
 */
$pairing_token = self::generate_pairing_token();
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
				'tlc_settings',
				$settings
			);
		}

		/**
		 * Register webhook.
		 */
		$result = TLC_Telegram::set_webhook();

		if ( is_wp_error( $result ) ) {

			$settings['telegram_connected'] = false;

			update_option(
				'tlc_settings',
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
				'tlc_settings',
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
				'tlc_settings',
				$settings
			);

			self::redirect_connection_error(
				'webhook_not_registered'
			);
		}

		$expected_webhook_url = rest_url(
			'tlc/v1/telegram/webhook'
		);

		if (
			untrailingslashit( $webhook_url ) !==
			untrailingslashit( $expected_webhook_url )
		) {

			$settings['telegram_connected'] = false;

			update_option(
				'tlc_settings',
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
			'tlc_settings',
			$settings
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => 'telegram-live-chat',
					'tlc_connected' => '1',
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
					'page'      => 'telegram-live-chat',
					'tlc_error' => sanitize_key( $error ),
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
			'tlc_settings',
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
				'tlc_missing_bot_token',
				__(
					'Telegram bot token is not configured.',
					'telegram-live-chat'
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
				'tlc_telegram_connection_error',
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
					'telegram-live-chat'
				);

			return new WP_Error(
				'tlc_telegram_webhook_info_error',
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

		<div class="wrap tlc-admin-page">

			<h1 class="tlc-admin-title">
				<img src="<?php echo esc_url( TLC_URL . 'assets/telegram-icon.svg' ); ?>" alt="" aria-hidden="true">
				<?php
				esc_html_e(
					'TLC - Live chat with Telegram',
					'telegram-live-chat'
				);
				?>
			</h1>

			<?php if ( filter_input( INPUT_GET, 'tlc_connected', FILTER_VALIDATE_INT ) ) : ?>

				<div class="notice notice-success is-dismissible">
					<p>
						<strong>
							<?php
							esc_html_e(
								'Telegram connected successfully.',
								'telegram-live-chat'
							);
							?>
						</strong>
					</p>
				</div>

			<?php endif; ?>

			<?php if ( filter_input( INPUT_GET, 'tlc_cleaned', FILTER_VALIDATE_INT ) ) : ?>

				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						esc_html_e(
							'Cleanup completed successfully.',
							'telegram-live-chat'
						);
						?>
					</p>
				</div>

			<?php endif; ?>

			<?php if ( filter_input( INPUT_GET, 'tlc_error', FILTER_UNSAFE_RAW ) ) : ?>

				<div class="notice notice-error is-dismissible">
					<p>

						<?php

						$error = sanitize_key( (string) filter_input( INPUT_GET, 'tlc_error', FILTER_UNSAFE_RAW ) );

						switch ( $error ) {

							case 'missing_bot_token':

								esc_html_e(
									'Bot Token is required.',
									'telegram-live-chat'
								);

								break;

							case 'missing_chat_id':

								esc_html_e(
									'Admin Chat ID is required.',
									'telegram-live-chat'
								);

								break;

							case 'tlc_missing_webhook_secret':

								esc_html_e(
									'Webhook Secret is missing.',
									'telegram-live-chat'
								);

								break;

							case 'tlc_invalid_webhook_secret':

								esc_html_e(
									'Webhook Secret contains invalid characters.',
									'telegram-live-chat'
								);

								break;

							case 'tlc_telegram_connection_error':

								esc_html_e(
									'Could not connect to Telegram.',
									'telegram-live-chat'
								);

								break;

							case 'tlc_telegram_webhook_error':

								esc_html_e(
									'Telegram rejected the webhook registration.',
									'telegram-live-chat'
								);

								break;

							case 'tlc_telegram_webhook_info_error':

								esc_html_e(
									'Could not verify the Telegram webhook.',
									'telegram-live-chat'
								);

								break;

							case 'webhook_not_registered':

								esc_html_e(
									'Telegram webhook was not registered.',
									'telegram-live-chat'
								);

								break;

							case 'webhook_url_mismatch':

								esc_html_e(
									'Telegram webhook URL does not match this website.',
									'telegram-live-chat'
								);

								break;

							default:

								esc_html_e(
									'Could not connect to Telegram. Please check your Bot Token and settings.',
									'telegram-live-chat'
								);

								break;
						}

						?>

					</p>
				</div>

			<?php endif; ?>

			<form
				method="post"
				action="options.php"
			>

				<?php

				settings_fields(
					'tlc_settings_group'
				);

				do_settings_sections(
					'telegram-live-chat'
				);

				submit_button();

				?>

			</form>

		</div>

		<?php
	}
}
