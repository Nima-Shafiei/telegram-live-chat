<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Get plugin settings.
 */
$settings = get_option(
	'tlcwt_settings',
	array()
);

$settings = is_array( $settings )
	? $settings
	: array();

	wp_clear_scheduled_hook( 'tlcwt_cleanup_event' );

delete_option( 'tlcwt_cleanup_interval_seconds' );

/**
 * Delete plugin data only if
 * the administrator explicitly enabled it.
 */
if ( empty( $settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;


/**
 * Delete plugin database tables.
 */
$conversations_table = $wpdb->prefix . 'tlcwt_conversations';
$messages_table      = $wpdb->prefix . 'tlcwt_messages';

call_user_func(
	array( $wpdb, 'query' ),
	$wpdb->prepare( 'DROP TABLE IF EXISTS %i', $messages_table )
);

call_user_func(
	array( $wpdb, 'query' ),
	$wpdb->prepare( 'DROP TABLE IF EXISTS %i', $conversations_table )
);

/**
 * Delete plugin options.
 */
delete_option( 'tlcwt_settings' );
delete_option( 'tlcwt_db_version' );

/**
 * Delete plugin transients.
 */
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Removes plugin-owned transient records during uninstall.
$wpdb->query(
	$wpdb->prepare(
		'DELETE FROM %i WHERE option_name LIKE %s OR option_name LIKE %s',
		$wpdb->options,
		$wpdb->esc_like( '_transient_tlcwt_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_tlcwt_' ) . '%'
	)
);
