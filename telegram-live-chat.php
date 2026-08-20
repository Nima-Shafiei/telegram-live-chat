<?php
/**
 * Plugin Name: TLC - Telegram Live Chat
 * Description: Connect your WordPress website chat with Telegram for real-time customer support.
 * Version: 1.0.0
 * Author: Nima Shafiee
 * Text Domain: telegram-live-chat
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TLC_VERSION', '1.0.0' );
define( 'TLC_FILE', __FILE__ );
define( 'TLC_PATH', plugin_dir_path( __FILE__ ) );
define( 'TLC_URL', plugin_dir_url( __FILE__ ) );

require_once TLC_PATH . 'includes/class-tlc-activator.php';
require_once TLC_PATH . 'includes/class-tlc-deactivator.php';
require_once TLC_PATH . 'includes/class-tlc-plugin.php';
require_once TLC_PATH . 'includes/class-tlc-database.php';
require_once TLC_PATH . 'includes/class-tlc-cleanup.php';

register_activation_hook(
	__FILE__,
	array( 'TLC_Activator', 'activate' )
);

register_deactivation_hook(
	__FILE__,
	array( 'TLC_Deactivator', 'deactivate' )
);

TLC_Plugin::instance();
