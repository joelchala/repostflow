<?php
/**
 * Plugin activator.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Activator
 */
class RPF_Activator {

	/**
	 * Run activation tasks.
	 */
	public static function activate() {
		self::create_tables();
		self::set_defaults();
		self::schedule_cron();

		// Store version for future migrations.
		update_option( 'rpf_version', RPF_VERSION );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Create custom database table.
	 */
	private static function create_tables() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'rpf_share_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) UNSIGNED NOT NULL,
			network VARCHAR(50) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'queued',
			message TEXT NOT NULL,
			image_url VARCHAR(500) DEFAULT NULL,
			response TEXT DEFAULT NULL,
			external_id VARCHAR(255) DEFAULT NULL,
			external_url VARCHAR(500) DEFAULT NULL,
			scheduled_at DATETIME DEFAULT NULL,
			shared_at DATETIME DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY idx_post_network (post_id, network),
			KEY idx_status (status),
			KEY idx_shared_at (shared_at),
			KEY idx_scheduled_at (scheduled_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Set default options.
	 */
	private static function set_defaults() {
		$defaults = array(
			'general'  => array(
				'enabled'             => false,
				'post_types'          => array( 'post' ),
				'categories_include'  => array(),
				'categories_exclude'  => array(),
				'min_post_age_days'   => 0,
				'cooldown_days'       => 7,
				'posts_per_run'       => 1,
			),
			'schedule' => array(
				'frequency'    => 'daily',
				'custom_hours' => 24,
				'start_time'   => '09:00',
			),
			'networks' => array(),
			'advanced' => array(
				'include_image'      => true,
				'utm_enabled'        => false,
				'utm_source'         => 'repostflow',
				'utm_medium'         => 'social',
				'utm_campaign'       => '',
				'log_retention_days' => 90,
			),
		);

		if ( ! get_option( 'rpf_settings' ) ) {
			add_option( 'rpf_settings', $defaults );
		}
	}

	/**
	 * Schedule the main cron event.
	 */
	private static function schedule_cron() {
		if ( ! wp_next_scheduled( 'rpf_process_queue' ) ) {
			wp_schedule_event( time(), 'daily', 'rpf_process_queue' );
		}

		if ( ! wp_next_scheduled( 'rpf_cleanup_log' ) ) {
			wp_schedule_event( time(), 'daily', 'rpf_cleanup_log' );
		}
	}
}
