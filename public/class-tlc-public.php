<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TLCWT_Public {

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

		$settings = get_option( 'tlcwt_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		if ( ! self::is_enabled( $settings ) ) {
			return;
		}

		wp_enqueue_style( 'tlcwt-chat', TLCWT_URL . 'public/css/chat.css', array(), TLCWT_VERSION );
		wp_enqueue_script( 'tlcwt-chat', TLCWT_URL . 'public/js/chat.js', array(), TLCWT_VERSION, true );

		wp_localize_script(
			'tlcwt-chat',
			'TLCWT_DATA',
			array(
				'restUrl'      => esc_url_raw( rest_url( 'tlcwt/v1/' ) ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'status'       => isset( $settings['admin_status'] ) ? $settings['admin_status'] : 'offline',
				'errorMessage' => __( 'Message could not be sent. Please try again.', 'tlc-live-chat-with-telegram' ),
			)
		);
	}

	/**
	 * Render the chat widget.
	 *
	 * @return void
	 */
	public static function render_chat_widget() {

		$settings = get_option( 'tlcwt_settings', array() );
		$settings = is_array( $settings ) ? $settings : array();

		if ( ! self::is_enabled( $settings ) ) {
			return;
		}

		$welcome_message = isset( $settings['welcome_message'] )
			? $settings['welcome_message']
			: __( 'Hello! How can we help you?', 'tlc-live-chat-with-telegram' );
		$appearance = isset( $settings['appearance'] ) && is_array( $settings['appearance'] ) ? $settings['appearance'] : array();
		$position = isset( $appearance['widget_position'] ) && 'left' === $appearance['widget_position'] ? 'left' : 'right';
		$primary = isset( $appearance['primary_color'] ) ? sanitize_hex_color( $appearance['primary_color'] ) : '#2aabee';
		$panel = isset( $appearance['panel_color'] ) ? sanitize_hex_color( $appearance['panel_color'] ) : '#ffffff';
		$primary = $primary ? $primary : '#2aabee';
		$panel = $panel ? $panel : '#ffffff';
		$number = static function ( $key, $default, $min, $max ) use ( $appearance ) {
			$value = isset( $appearance[ $key ] ) ? absint( $appearance[ $key ] ) : $default;
			return min( $max, max( $min, $value ) );
		};
		$style = sprintf( '--tlcwt-primary:%1$s;--tlcwt-panel:%2$s;--tlcwt-desktop-width:%3$dpx;--tlcwt-desktop-height:%4$dpx;--tlcwt-mobile-width:%5$dpx;--tlcwt-mobile-height:%6$dpx;--tlcwt-desktop-bottom:%7$dpx;--tlcwt-mobile-bottom:%8$dpx;', esc_attr( $primary ), esc_attr( $panel ), $number( 'desktop_width', 340, 280, 600 ), $number( 'desktop_height', 480, 320, 800 ), $number( 'mobile_width', 340, 260, 600 ), $number( 'mobile_height', 420, 300, 800 ), $number( 'desktop_bottom', 24, 0, 500 ), $number( 'mobile_bottom', 60, 0, 500 ) );
		$icon_url = ! empty( $appearance['icon_id'] ) ? wp_get_attachment_image_url( absint( $appearance['icon_id'] ), 'thumbnail' ) : '';
		$icon_url = $icon_url ? $icon_url : TLCWT_URL . 'assets/telegram-icon.svg';
		$title = isset( $appearance['header_text'] ) ? $appearance['header_text'] : __( 'Support', 'tlc-live-chat-with-telegram' );
		$direction = isset( $appearance['header_direction'] ) && in_array( $appearance['header_direction'], array( 'auto', 'ltr', 'rtl' ), true ) ? $appearance['header_direction'] : 'auto';
		?>
		<div id="tlcwt-chat-widget" class="tlcwt-position-<?php echo esc_attr( $position ); ?>" style="<?php echo esc_attr( $style ); ?>">
			<button type="button" id="tlcwt-chat-toggle" aria-label="<?php esc_attr_e( 'Open chat', 'tlc-live-chat-with-telegram' ); ?>" aria-expanded="false" aria-controls="tlcwt-chat-window">
				<img src="<?php echo esc_url( $icon_url ); ?>" alt="" aria-hidden="true">
			</button>

			<div id="tlcwt-chat-window" dir="ltr" hidden>
				<div class="tlcwt-chat-header">
					<div>
						<strong id="tlcwt-chat-title" dir="<?php echo esc_attr( $direction ); ?>"><?php echo esc_html( $title ); ?></strong>
						<span class="tlcwt-status" id="tlcwt-chat-status">
							<span class="tlcwt-status-dot"></span>
							<span id="tlcwt-chat-status-text"><?php esc_html_e( 'Offline', 'tlc-live-chat-with-telegram' ); ?></span>
						</span>
					</div>
					<button type="button" id="tlcwt-chat-close" aria-label="<?php esc_attr_e( 'Close chat', 'tlc-live-chat-with-telegram' ); ?>">&times;</button>
				</div>

				<div id="tlcwt-chat-messages">
					<div class="tlcwt-message tlcwt-message-admin"><?php echo esc_html( $welcome_message ); ?></div>
				</div>

				<form id="tlcwt-chat-form">
					<textarea id="tlcwt-chat-input" dir="auto" maxlength="2000" placeholder="<?php esc_attr_e( 'Type your message…', 'tlc-live-chat-with-telegram' ); ?>" rows="1"></textarea>
					<button type="submit" id="tlcwt-chat-send" aria-label="<?php esc_attr_e( 'Send message', 'tlc-live-chat-with-telegram' ); ?>">
						<img src="<?php echo esc_url( $icon_url ); ?>" alt="" aria-hidden="true">
					</button>
				</form>
			</div>
		</div>
		<?php
	}
}
