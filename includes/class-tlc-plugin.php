<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TLC_Plugin {

	private static $instance = null;

	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {

		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_dependencies() {

	require_once TLC_PATH . 'admin/class-tlc-admin.php';
	require_once TLC_PATH . 'public/class-tlc-public.php';
	require_once TLC_PATH . 'rest-api/class-tlc-rest.php';
	require_once TLC_PATH . 'telegram/class-tlc-telegram.php';
}
	private function init_hooks() {

		add_action(
			'plugins_loaded',
			array( 'TLC_Database', 'maybe_upgrade' )
		);

		add_action(
			'plugins_loaded',
			array( __CLASS__, 'migrate_default_messages' ),
			20
		);

		if ( is_admin() ) {
		TLC_Admin::init();
	}

	TLC_Public::init();
	TLC_REST::init();
		TLC_Telegram::init();
			TLC_Cleanup::init();

	}

	/**
	 * Replace only the previous built-in Persian messages for existing sites.
	 * Custom administrator messages are left unchanged.
	 *
	 * @return void
	 */
	public static function migrate_default_messages() {

		$settings = get_option( 'tlc_settings', array() );

		if ( ! is_array( $settings ) ) {
			return;
		}

		$changed = false;

			if (
		isset( $settings['welcome_message'] ) &&
		'Hello!👋 How can we help you?' !== $settings['welcome_message']
	) {
		$settings['welcome_message'] = 'Hello! How can we help you?';
		$changed                     = true;
	}

	if (
		isset( $settings['offline_message'] ) &&
		'We received your message and will reply as soon as possible.' !== $settings['offline_message']
	) {
		$settings['offline_message'] = 'We received your message and will reply as soon as possible.';
		$changed                     = true;
	}

		if ( $changed ) {
			update_option( 'tlc_settings', $settings );
		}
	}

}
