<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TLC_Telegram {

	/**
	 * Initialize Telegram functionality.
	 *
	 * @return void
	 */
	public static function init() {

		// Telegram functionality does not need
		// global initialization at the moment.
	}

	/**
	 * Get plugin settings.
	 *
	 * @return array
	 */
	private static function get_settings() {

		$settings = get_option(
			'tlc_settings',
			array()
		);

		return is_array( $settings )
			? $settings
			: array();
	}

	/**
	 * Get Telegram bot token.
	 *
	 * @return string
	 */
	public static function get_bot_token() {

		$settings = self::get_settings();

		return isset( $settings['bot_token'] )
			? trim( (string) $settings['bot_token'] )
			: '';
	}
	/**
 * Get Telegram bot information.
 *
 * @return array|WP_Error
 */
public static function get_me() {

	$token = self::get_bot_token();

	if ( empty( $token ) ) {
		return new WP_Error(
			'tlc_missing_bot_token',
			__(
				'Telegram bot token is not configured.',
				'telegram-live-chat'
			)
		);
	}

	$url = self::get_api_url( 'getMe' );

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
		empty( $body['ok'] ) ||
		empty( $body['result'] )
	) {
		$error_message = isset( $body['description'] )
			? $body['description']
			: __(
				'Could not retrieve Telegram bot information.',
				'telegram-live-chat'
			);

		return new WP_Error(
			'tlc_telegram_get_me_error',
			$error_message,
			array(
				'status' => $status_code,
				'body'   => $body,
			)
		);
	}

	return $body['result'];
}

	/**
	 * Get Telegram API URL.
	 *
	 * @param string $method Telegram API method.
	 * @return string
	 */
	private static function get_api_url( $method ) {

		$token = self::get_bot_token();

		return sprintf(
			'https://api.telegram.org/bot%s/%s',
			$token,
			$method
		);
	}

	/**
	 * Register Telegram webhook.
	 *
	 * @return array|WP_Error
	 */
	public static function set_webhook() {

		$token = self::get_bot_token();

		if ( empty( $token ) ) {
			return new WP_Error(
				'tlc_missing_bot_token',
				__(
					'Telegram bot token is not configured.',
					'telegram-live-chat'
				)
			);
		}

		$settings = self::get_settings();

		$webhook_secret = isset( $settings['webhook_secret'] )
			? trim( (string) $settings['webhook_secret'] )
			: '';

		if ( empty( $webhook_secret ) ) {
			return new WP_Error(
				'tlc_missing_webhook_secret',
				__(
					'Webhook secret is not configured.',
					'telegram-live-chat'
				)
			);
		}

		/**
		 * Telegram secret token supports:
		 * A-Z, a-z, 0-9, _ and -
		 */
		if (
			! preg_match(
				'/^[A-Za-z0-9_-]{1,256}$/',
				$webhook_secret
			)
		) {
			return new WP_Error(
				'tlc_invalid_webhook_secret',
				__(
					'The webhook secret contains invalid characters.',
					'telegram-live-chat'
				)
			);
		}

		/**
		 * Generate WordPress REST webhook URL.
		 */
		$webhook_url = rest_url(
			'tlc/v1/telegram/webhook'
		);

		if ( empty( $webhook_url ) ) {
			return new WP_Error(
				'tlc_invalid_webhook_url',
				__(
					'Could not generate the Telegram webhook URL.',
					'telegram-live-chat'
				)
			);
		}

		/**
		 * Telegram setWebhook endpoint.
		 */
		$url = self::get_api_url(
			'setWebhook'
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'body'    => array(
					'url'             => $webhook_url,
					'secret_token'    => $webhook_secret,
					'allowed_updates' => wp_json_encode(
						array(
							'message',
						)
					),
				),
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

			$error_message = isset( $body['description'] )
				? $body['description']
				: __(
					'Telegram webhook registration failed.',
					'telegram-live-chat'
				);

			return new WP_Error(
				'tlc_telegram_webhook_error',
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
	 * Get Telegram webhook information.
	 *
	 * @return array|WP_Error
	 */
	public static function get_webhook_info() {

		$token = self::get_bot_token();

		if ( empty( $token ) ) {
			return new WP_Error(
				'tlc_missing_bot_token',
				__(
					'Telegram bot token is not configured.',
					'telegram-live-chat'
				)
			);
		}

		$url = self::get_api_url(
			'getWebhookInfo'
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

			$error_message = isset( $body['description'] )
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
	 * Verify Telegram webhook configuration.
	 *
	 * @return true|WP_Error
	 */
	public static function verify_webhook() {

		$expected_url = rest_url(
			'tlc/v1/telegram/webhook'
		);

		$webhook_info = self::get_webhook_info();

		if ( is_wp_error( $webhook_info ) ) {
			return $webhook_info;
		}

		$result = isset( $webhook_info['result'] )
			? $webhook_info['result']
			: array();

		$actual_url = isset( $result['url'] )
			? (string) $result['url']
			: '';

		if ( empty( $actual_url ) ) {
			return new WP_Error(
				'tlc_webhook_not_registered',
				__(
					'Telegram webhook is not registered.',
					'telegram-live-chat'
				)
			);
		}

		/**
		 * Compare Telegram's registered URL
		 * with the current WordPress REST URL.
		 */
		if (
			untrailingslashit( $actual_url ) !==
			untrailingslashit( $expected_url )
		) {
			return new WP_Error(
				'tlc_webhook_url_mismatch',
				__(
					'Telegram webhook URL does not match this website.',
					'telegram-live-chat'
				),
				array(
					'expected_url' => $expected_url,
					'actual_url'   => $actual_url,
				)
			);
		}

		/**
		 * Check whether Telegram reports a webhook error.
		 */
		if (
			isset( $result['last_error_message'] ) &&
			! empty( $result['last_error_message'] )
		) {

			return new WP_Error(
				'tlc_webhook_last_error',
				sprintf(
					/* translators: %s: Telegram error message. */
					__(
						'Telegram reported a webhook error: %s',
						'telegram-live-chat'
					),
					sanitize_text_field(
						$result['last_error_message']
					)
				),
				array(
					'last_error_date' => isset(
						$result['last_error_date']
					)
						? absint(
							$result['last_error_date']
						)
						: 0,
				)
			);
		}

		return true;
	}

	/**
	 * Send a message to Telegram.
	 *
	 * @param string $chat_id Telegram chat ID.
	 * @param string $message Message content.
	 * @return array|WP_Error
	 */
	public static function send_message(
		$chat_id,
		$message
	) {

		$token = self::get_bot_token();

		if ( empty( $token ) ) {
			return new WP_Error(
				'tlc_missing_bot_token',
				__(
					'Telegram bot token is not configured.',
					'telegram-live-chat'
				)
			);
		}

		$chat_id = trim(
			(string) $chat_id
		);

		if ( empty( $chat_id ) ) {
			return new WP_Error(
				'tlc_missing_chat_id',
				__(
					'Telegram chat ID is missing.',
					'telegram-live-chat'
				)
			);
		}

		$message = (string) $message;

		if ( '' === trim( $message ) ) {
			return new WP_Error(
				'tlc_empty_message',
				__(
					'Telegram message cannot be empty.',
					'telegram-live-chat'
				)
			);
		}

		/**
		 * Telegram sendMessage endpoint.
		 */
		$url = self::get_api_url(
			'sendMessage'
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'body'    => array(
					'chat_id' => $chat_id,
					'text'    => $message,
				),
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

			$telegram_error = isset(
				$body['description']
			)
				? $body['description']
				: __(
					'Unknown Telegram API error.',
					'telegram-live-chat'
				);

			return new WP_Error(
				'tlc_telegram_api_error',
				$telegram_error,
				array(
					'status' => $status_code,
					'body'   => $body,
				)
			);
		}

		return $body;
	}
}

