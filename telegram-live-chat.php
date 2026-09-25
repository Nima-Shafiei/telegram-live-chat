<?php
/**
 * Plugin Name: TLC - Live chat with Telegram
 * Description: Connect your WordPress website chat with Telegram for real-time customer support.
 * Version: 1.1.0
 * Author: Nima Shafiee
 * Author URI: https://www.linkedin.com/in/nima-shafiee/
 * Text Domain: tlc-live-chat-with-telegram
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TLCWT_VERSION', '1.1.0' );
define( 'TLCWT_FILE', __FILE__ );
define( 'TLCWT_PATH', plugin_dir_path( __FILE__ ) );
define( 'TLCWT_URL', plugin_dir_url( __FILE__ ) );

require_once TLCWT_PATH . 'includes/class-tlc-activator.php';
require_once TLCWT_PATH . 'includes/class-tlc-deactivator.php';
require_once TLCWT_PATH . 'includes/class-tlc-plugin.php';
require_once TLCWT_PATH . 'includes/class-tlc-database.php';
require_once TLCWT_PATH . 'includes/class-tlc-cleanup.php';

register_activation_hook(
	__FILE__,
	array( 'TLCWT_Activator', 'activate' )
);

register_deactivation_hook(
	__FILE__,
	array( 'TLCWT_Deactivator', 'deactivate' )
);

TLCWT_Plugin::instance();
