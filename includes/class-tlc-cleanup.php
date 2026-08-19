<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TLC_Cleanup {

	/**
	 * Initialize cleanup system.
	 *
	 * @return void
	 */
	public static function init() {

		add_filter(
			'cron_schedules',
			array( __CLASS__, 'add_cron_schedules' )
		);

		add_action(
			'tlc_cleanup_event',
			array( __CLASS__, 'run' )
		);

		self::schedule_event();
	}
    /**
 * Reschedule cleanup event.
 *
 * @return void
 */
public static function reschedule() {

	self::schedule_event();
}

	/**
	 * Add custom cron interval.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array
	 */
	public static function add_cron_schedules( $schedules ) {

		$interval = (int) get_option(
			'tlc_cleanup_interval_seconds',
			7 * DAY_IN_SECONDS
		);

		if ( $interval < DAY_IN_SECONDS ) {
			$interval = DAY_IN_SECONDS;
		}

		$schedules['tlc_cleanup_interval'] = array(
			'interval' => $interval,
			'display'  => __(
				'Telegram Live Chat Cleanup',
				'telegram-live-chat'
			),
		);

		return $schedules;
	}

	/**
	 * Remove all scheduled cleanup events.
	 *
	 * @return void
	 */
	private static function unschedule_event() {

		$timestamp = wp_next_scheduled(
			'tlc_cleanup_event'
		);

		while ( $timestamp ) {

			wp_unschedule_event(
				$timestamp,
				'tlc_cleanup_event'
			);

			$timestamp = wp_next_scheduled(
				'tlc_cleanup_event'
			);
		}
	}

	/**
	 * Schedule cleanup event according to settings.
	 *
	 * @return void
	 */
	private static function schedule_event() {

		$settings = get_option(
			'tlc_settings',
			array()
		);

		$settings = is_array( $settings )
			? $settings
			: array();

		$frequency = isset(
			$settings['cleanup_frequency']
		)
			? $settings['cleanup_frequency']
			: 'weekly';

		/**
		 * Never schedule cleanup when disabled.
		 */
		if ( 'never' === $frequency ) {

			self::unschedule_event();

			return;
		}

		/**
		 * Determine cleanup interval.
		 */
		switch ( $frequency ) {

			case 'daily':
				$interval = DAY_IN_SECONDS;
				break;

			case 'monthly':
				$interval = 30 * DAY_IN_SECONDS;
				break;

			case 'weekly':
			default:
				$interval = 7 * DAY_IN_SECONDS;
				break;
		}

		/**
		 * Store selected interval BEFORE
		 * registering the cron event.
		 */
		update_option(
			'tlc_cleanup_interval_seconds',
			$interval
		);

		/**
		 * Remove existing events.
		 *
		 * This prevents duplicate cron jobs.
		 */
		self::unschedule_event();

		/**
		 * Schedule a new cleanup event.
		 *
		 * First execution will happen
		 * one hour from now.
		 */
		wp_schedule_event(
			time() + HOUR_IN_SECONDS,
			'tlc_cleanup_interval',
			'tlc_cleanup_event'
		);
	}

	/**
	 * Run cleanup.
	 *
	 * @return void
	 */
	public static function run() {

		$settings = get_option(
			'tlc_settings',
			array()
		);

		$settings = is_array( $settings )
			? $settings
			: array();

		$frequency = isset(
			$settings['cleanup_frequency']
		)
			? $settings['cleanup_frequency']
			: 'weekly';

		$retention = isset(
			$settings['cleanup_retention']
		)
			? absint(
				$settings['cleanup_retention']
			)
			: 30;

		/**
		 * Cleanup disabled.
		 */
		if ( 'never' === $frequency ) {
			return;
		}

		/**
		 * Validate retention period.
		 */
		if ( ! in_array(
			$retention,
			array( 7, 30, 90, 180, 365 ),
			true
		) ) {
			$retention = 30;
		}

		self::delete_old_conversations(
			$retention
		);
	}

	/**
	 * Delete old closed conversations.
	 *
	 * Only closed conversations are removed.
	 *
	 * @param int $days Retention period.
	 * @return void
	 */
	private static function delete_old_conversations(
		$days
	) {

		global $wpdb;

		$conversations_table = TLC_Database::conversations_table();
		$messages_table      = TLC_Database::messages_table();

		$cutoff = gmdate(
			'Y-m-d H:i:s',
			time() - ( $days * DAY_IN_SECONDS )
		);

		/**
		 * Get old closed conversations.
		 *
		 * Limit deletion to 500 conversations per run
		 * to avoid heavy database operations.
		 */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup must read the current expired records from this plugin's private table.
		$conversation_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id
				FROM %i
				WHERE status = 'closed'
				AND updated_at < %s
				LIMIT 500",
				$conversations_table,
				$cutoff
			)
		);

		if ( empty( $conversation_ids ) ) {
			return;
		}

		$conversation_ids = array_map(
			'absint',
			$conversation_ids
		);

		/**
		 * Delete messages belonging
		 * to old conversations.
		 */
		foreach ( $conversation_ids as $conversation_id ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Deletes expired messages from this plugin's private table.
			$wpdb->delete(
				$messages_table,
				array( 'conversation_id' => $conversation_id ),
				array( '%d' )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Deletes the corresponding expired conversation from this plugin's private table.
			$wpdb->delete(
				$conversations_table,
				array( 'id' => $conversation_id ),
				array( '%d' )
			);
		}
	}
}
