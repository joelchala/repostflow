<?php
/**
 * Queue manager for scheduled shares.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Queue_Manager
 */
class RPF_Queue_Manager {

	/**
	 * Logger instance.
	 *
	 * @var RPF_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param RPF_Logger $logger Logger instance.
	 */
	public function __construct( RPF_Logger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Add a post to the sharing queue for a specific network.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $network  Network slug.
	 * @param string $message  Rendered message.
	 * @param string $image    Image URL.
	 * @param string $schedule When to share (MySQL datetime or null for immediate).
	 * @return int|false Log entry ID or false.
	 */
	public function enqueue( $post_id, $network, $message, $image = '', $schedule = null ) {
		return $this->logger->log( array(
			'post_id'      => $post_id,
			'network'      => $network,
			'status'       => 'queued',
			'message'      => $message,
			'image_url'    => $image,
			'scheduled_at' => $schedule,
		) );
	}

	/**
	 * Get the next queued item ready for processing.
	 *
	 * @return object|null
	 */
	public function dequeue_next() {
		global $wpdb;

		$table = $this->logger->get_table();

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE status = 'queued' AND (scheduled_at IS NULL OR scheduled_at <= %s) ORDER BY created_at ASC LIMIT 1",
				current_time( 'mysql' )
			)
		);
	}

	/**
	 * Mark a queue item as processing.
	 *
	 * @param int $log_id Log entry ID.
	 * @return bool
	 */
	public function mark_processing( $log_id ) {
		return $this->logger->update_status( $log_id, 'processing' );
	}

	/**
	 * Mark a queue item as published.
	 *
	 * @param int    $log_id       Log entry ID.
	 * @param string $external_id  External post ID.
	 * @param string $external_url External post URL.
	 * @param string $response     API response (JSON).
	 * @return bool
	 */
	public function mark_published( $log_id, $external_id = '', $external_url = '', $response = '' ) {
		return $this->logger->update_status( $log_id, 'published', array(
			'external_id'  => $external_id,
			'external_url' => $external_url,
			'response'     => $response,
			'shared_at'    => current_time( 'mysql' ),
		) );
	}

	/**
	 * Mark a queue item as failed.
	 *
	 * @param int    $log_id   Log entry ID.
	 * @param string $response Error response.
	 * @return bool
	 */
	public function mark_failed( $log_id, $response = '' ) {
		return $this->logger->update_status( $log_id, 'failed', array(
			'response'  => $response,
			'shared_at' => current_time( 'mysql' ),
		) );
	}

	/**
	 * Get all queued items.
	 *
	 * @param int $limit Max items to return.
	 * @return array
	 */
	public function get_queue( $limit = 50 ) {
		return $this->logger->get_history( array(
			'status'   => 'queued',
			'per_page' => $limit,
			'orderby'  => 'scheduled_at',
			'order'    => 'ASC',
		) );
	}

	/**
	 * Clear all queued items.
	 *
	 * @return int Number of deleted rows.
	 */
	public function clear_queue() {
		global $wpdb;
		$table = $this->logger->get_table();
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `$table` WHERE status = %s",
				'queued'
			)
		);
	}

	/**
	 * Delete a specific queue item.
	 *
	 * @param int $log_id Log entry ID.
	 * @return bool
	 */
	public function delete( $log_id ) {
		global $wpdb;
		$table = $this->logger->get_table();
		return (bool) $wpdb->delete( $table, array( 'id' => $log_id ), array( '%d' ) );
	}
}
