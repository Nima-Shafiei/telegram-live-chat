<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TLC_Public {

	/**
	 * Register public hooks.
	 *
	 * @return void
	 */
	public static function init() {

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'render_chat_widget' ) );
	}

	/**
	 * Whether the widget is enabled. It is enabled by default for new installs.
	 *
	 * @param array $settings Plugin settings.
	 * @return bool
	 */
	private static function is_enabled( $settings ) {

		return ! isset( $settings['enabled'] ) || (bool) $settings['enabled'];
	}

	/**
	 * Enqueue the widget assets.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {

		$settings = get_option( 'tlc_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		if ( ! self::is_enabled( $settings ) ) {
			return;
		}

		wp_enqueue_style( 'tlc-chat', TLC_URL . 'public/css/chat.css', array(), TLC_VERSION );
		wp_enqueue_script( 'tlc-chat', TLC_URL . 'public/js/chat.js', array(), TLC_VERSION, true );

		wp_localize_script(
			'tlc-chat',
			'TLC_DATA',
			array(
				'restUrl'      => esc_url_raw( rest_url( 'tlc/v1/' ) ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'status'       => isset( $settings['admin_status'] ) ? $settings['admin_status'] : 'offline',
				'errorMessage' => __( 'Message could not be sent. Please try again.', 'telegram-live-chat' ),
			)
		);
	}

	/**
	 * Render the chat widget.
	 *
	 * @return void
	 */
	public static function render_chat_widget() {

		$settings = get_option( 'tlc_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		if ( ! self::is_enabled( $settings ) ) {
			return;
		}

		$welcome_message = isset( $settings['welcome_message'] )
			? $settings['welcome_message']
			: __( 'Hello! How can we help you?', 'telegram-live-chat' );
		?>
		<div id="tlc-chat-widget">
			<button type="button" id="tlc-chat-toggle" aria-label="<?php esc_attr_e( 'Open chat', 'telegram-live-chat' ); ?>" aria-expanded="false" aria-controls="tlc-chat-window">
				<img src="<?php echo esc_url( TLC_URL . 'assets/telegram-icon.svg' ); ?>" alt="" aria-hidden="true">
			</button>

			<div id="tlc-chat-window" dir="ltr" hidden>
				<div class="tlc-chat-header">
					<div>
						<strong id="tlc-chat-title"><?php esc_html_e( 'Support', 'telegram-live-chat' ); ?></strong>
						<span class="tlc-status" id="tlc-chat-status">
							<span class="tlc-status-dot"></span>
							<span id="tlc-chat-status-text"><?php esc_html_e( 'Offline', 'telegram-live-chat' ); ?></span>
						</span>
					</div>
					<button type="button" id="tlc-chat-close" aria-label="<?php esc_attr_e( 'Close chat', 'telegram-live-chat' ); ?>">&times;</button>
				</div>

				<div id="tlc-chat-messages">
					<div class="tlc-message tlc-message-admin"><?php echo esc_html( $welcome_message ); ?></div>
				</div>

				<form id="tlc-chat-form">
					<textarea id="tlc-chat-input" dir="auto" maxlength="2000" placeholder="<?php esc_attr_e( 'Type your message…', 'telegram-live-chat' ); ?>" rows="1"></textarea>
					<button type="submit" id="tlc-chat-send" aria-label="<?php esc_attr_e( 'Send message', 'telegram-live-chat' ); ?>">
						<img src="<?php echo esc_url( TLC_URL . 'assets/telegram-icon.svg' ); ?>" alt="" aria-hidden="true">
					</button>
				</form>
			</div>
		</div>
		<?php
	}
}
