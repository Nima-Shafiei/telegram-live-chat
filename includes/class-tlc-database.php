<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TLCWT_Database {

	/**
	 * Current database schema version.
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Get conversations table name.
	 *
	 * @return string
	 */
	public static function conversations_table() {
		global $wpdb;

		return $wpdb->prefix . 'tlcwt_conversations';
	}

	/**
	 * Get messages table name.
	 *
	 * @return string
	 */
	public static function messages_table() {
		global $wpdb;

		return $wpdb->prefix . 'tlcwt_messages';
	}

	/**
	 * Create or update database tables.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$conversations_table = self::conversations_table();
		$messages_table      = self::messages_table();

		$sql_conversations = "CREATE TABLE {$conversations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			visitor_id varchar(64) NOT NULL,
			telegram_chat_id bigint(20) DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'open',
			user_name varchar(100) DEFAULT NULL,
			user_email varchar(190) DEFAULT NULL,
			page_url text DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY visitor_id (visitor_id),
			KEY telegram_chat_id (telegram_chat_id),
			KEY status (status),
			KEY user_email (user_email)
		) {$charset_collate};";

		$sql_messages = "CREATE TABLE {$messages_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			sender varchar(20) NOT NULL,
			message longtext NOT NULL,
			telegram_message_id bigint(20) DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY conversation_id (conversation_id),
			KEY telegram_message_id (telegram_message_id),
			KEY sender (sender),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql_conversations );
		dbDelta( $sql_messages );

		update_option(
			'tlcwt_db_version',
			self::DB_VERSION
		);
	}

	/**
	 * Run database migrations if necessary.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {

		$installed_version = get_option( 'tlcwt_db_version', '0.0.0' );

		if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
			self::install();
		}
	}
}
