<?php
/**
 * Share history logger.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Custom plugin table; results are not suitable for object caching.

/**
 * Class RPF_Logger
 */
class RPF_Logger {

	/**
	 * Get the table name.
	 *
	 * @return string
	 */
	public function get_table() {
		global $wpdb;
		return $wpdb->prefix . 'rpf_share_log';
	}

	/**
	 * Log a share entry.
	 *
	 * @param array $data Log data.
	 * @return int|false Insert ID or false on failure.
	 */
	public function log( $data ) {
		global $wpdb;

		$defaults = array(
			'post_id'      => 0,
			'network'      => '',
			'status'       => 'queued',
			'message'      => '',
			'image_url'    => '',
			'response'     => '',
			'external_id'  => '',
			'external_url' => '',
			'scheduled_at' => null,
			'shared_at'    => null,
			'created_at'   => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			$this->get_table(),
			$data,
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- only when WP_DEBUG is on.
					 '[RePostFlow] Logger insert failed: ' . $wpdb->last_error );
			}
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Update a log entry status.
	 *
	 * @param int    $log_id   Log entry ID.
	 * @param string $status   New status.
	 * @param array  $extra    Extra data to update.
	 * @return bool
	 */
	public function update_status( $log_id, $status, $extra = array() ) {
		global $wpdb;

		$data   = array_merge( array( 'status' => $status ), $extra );
		$format = array( '%s' );

		foreach ( $extra as $key => $value ) {
			$format[] = '%s';
		}

		return (bool) $wpdb->update(
			$this->get_table(),
			$data,
			array( 'id' => $log_id ),
			$format,
			array( '%d' )
		);
	}

	/**
	 * Get recent share history.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_history( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'per_page' => 20,
			'page'     => 1,
			'status'   => '',
			'network'  => '',
			'orderby'  => 'created_at',
			'order'    => 'DESC',
		);

		$args     = wp_parse_args( $args, $defaults );
		$table    = $this->get_table();
		$status   = (string) $args['status'];
		$network  = (string) $args['network'];
		$per_page = max( 1, absint( $args['per_page'] ) );
		$offset   = ( max( 1, absint( $args['page'] ) ) - 1 ) * $per_page;
		// Column names come from a fixed list, never from user input.
		$column = in_array( $args['orderby'], array( 'created_at', 'scheduled_at', 'shared_at' ), true ) ? $args['orderby'] : 'created_at';

		if ( 'ASC' === strtoupper( (string) $args['order'] ) ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM %i WHERE ( %s = '' OR status = %s ) AND ( %s = '' OR network = %s ) ORDER BY %i ASC LIMIT %d OFFSET %d",
					$table,
					$status,
					$status,
					$network,
					$network,
					$column,
					$per_page,
					$offset
				)
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i WHERE ( %s = '' OR status = %s ) AND ( %s = '' OR network = %s ) ORDER BY %i DESC LIMIT %d OFFSET %d",
				$table,
				$status,
				$status,
				$network,
				$network,
				$column,
				$per_page,
				$offset
			)
		);
	}

	/**
	 * Get total count for pagination.
	 *
	 * @param array $args Filter arguments.
	 * @return int
	 */
	public function get_total_count( $args = array() ) {
		global $wpdb;

		$table   = $this->get_table();
		$status  = isset( $args['status'] ) ? (string) $args['status'] : '';
		$network = isset( $args['network'] ) ? (string) $args['network'] : '';

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE ( %s = '' OR status = %s ) AND ( %s = '' OR network = %s )",
				$table,
				$status,
				$status,
				$network,
				$network
			)
		);
	}

	/**
	 * Get IDs of recently shared posts.
	 *
	 * @param int    $days    Cooldown period in days.
	 * @param string $network Optional specific network.
	 * @return array Post IDs.
	 */
	public function get_recently_shared_ids( $days = 7, $network = '' ) {
		global $wpdb;

		$table = $this->get_table();

		if ( ! empty( $network ) ) {
			return $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT post_id FROM %i WHERE shared_at > DATE_SUB(NOW(), INTERVAL %d DAY) AND status = 'published' AND network = %s",
					$table,
					$days,
					$network
				)
			);
		}

		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT post_id FROM %i WHERE shared_at > DATE_SUB(NOW(), INTERVAL %d DAY) AND status = 'published'",
				$table,
				$days
			)
		);
	}

	/**
	 * Get stats summary.
	 *
	 * @return array
	 */
	public function get_stats() {
		global $wpdb;
		$table = $this->get_table();

		$total     = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = 'published'", $table ) );
		$this_week = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = 'published' AND shared_at > DATE_SUB(NOW(), INTERVAL 7 DAY)", $table ) );
		$this_month = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = 'published' AND shared_at > DATE_SUB(NOW(), INTERVAL 30 DAY)", $table ) );
		$failed    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = 'failed' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)", $table ) );
		$queued    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE status = 'queued'", $table ) );

		return array(
			'total_published' => $total,
			'this_week'       => $this_week,
			'this_month'      => $this_month,
			'failed_week'     => $failed,
			'queued'          => $queued,
		);
	}

	/**
	 * Clean up old log entries.
	 *
	 * @param int $days Keep entries for this many days.
	 * @return int Number of deleted rows.
	 */
	public function cleanup( $days = 90 ) {
		global $wpdb;
		$table = $this->get_table();

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM %i WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$table,
				$days
			)
		);
	}
}
