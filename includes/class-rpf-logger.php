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
				error_log( '[RePostFlow] Logger insert failed: ' . $wpdb->last_error );
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

		$args   = wp_parse_args( $args, $defaults );
		$table  = $this->get_table();
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		if ( ! empty( $args['network'] ) ) {
			$where[]  = 'network = %s';
			$values[] = $args['network'];
		}

		$where_clause = implode( ' AND ', $where );
		$orderby      = sanitize_sql_orderby( $args['orderby'] . ' ' . $args['order'] );
		$offset       = ( $args['page'] - 1 ) * $args['per_page'];

		if ( ! empty( $values ) ) {
			// $where_clause is built from literal fragments and $orderby is sanitized; values are placeholders.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$query = $wpdb->prepare(
				"SELECT * FROM %i WHERE $where_clause ORDER BY $orderby LIMIT %d OFFSET %d",
				array_merge( array( $table ), $values, array( $args['per_page'], $offset ) )
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $orderby is sanitized with sanitize_sql_orderby().
			$query = $wpdb->prepare(
				"SELECT * FROM %i WHERE 1=1 ORDER BY $orderby LIMIT %d OFFSET %d",
				$table,
				$args['per_page'],
				$offset
			);
		}

		return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- prepared above.
	}

	/**
	 * Get total count for pagination.
	 *
	 * @param array $args Filter arguments.
	 * @return int
	 */
	public function get_total_count( $args = array() ) {
		global $wpdb;

		$table  = $this->get_table();
		$where  = array( '1=1' );
		$values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = $args['status'];
		}

		if ( ! empty( $args['network'] ) ) {
			$where[]  = 'network = %s';
			$values[] = $args['network'];
		}

		$where_clause = implode( ' AND ', $where );

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- $where_clause holds literal fragments only.
			return (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE $where_clause", array_merge( array( $table ), $values ) )
			);
		}

		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
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
