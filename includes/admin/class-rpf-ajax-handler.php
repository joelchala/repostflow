<?php
/**
 * AJAX request handler for admin actions.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Ajax_Handler
 */
class RPF_Ajax_Handler {

	/** @var RePostFlow */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param RePostFlow $plugin Main plugin instance.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize AJAX hooks.
	 */
	public function init() {
		add_action( 'wp_ajax_rpf_test_network', array( $this, 'test_network' ) );
		add_action( 'wp_ajax_rpf_share_now', array( $this, 'share_now' ) );
		add_action( 'wp_ajax_rpf_clear_queue', array( $this, 'clear_queue' ) );
		add_action( 'wp_ajax_rpf_delete_log', array( $this, 'delete_log_entry' ) );
		add_action( 'wp_ajax_rpf_toggle_enabled', array( $this, 'toggle_enabled' ) );
	}

	/**
	 * Test a network connection.
	 */
	public function test_network() {
		check_ajax_referer( 'rpf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'repostflow' ) );
		}

		$network_slug = isset( $_POST['network'] ) ? sanitize_text_field( $_POST['network'] ) : '';
		$network      = $this->plugin->networks->get_by_slug( $network_slug );

		if ( ! $network ) {
			wp_send_json_error( __( 'Unknown network', 'repostflow' ) );
		}

		$result = $network->validate_credentials();
		wp_send_json_success( $result );
	}

	/**
	 * Force share a post immediately.
	 */
	public function share_now() {
		check_ajax_referer( 'rpf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'repostflow' ) );
		}

		// Pick a random post and share to all configured networks.
		$post = $this->plugin->selector->pick();
		if ( ! $post ) {
			wp_send_json_error( __( 'No eligible posts found', 'repostflow' ) );
		}

		$configured = $this->plugin->networks->get_configured();
		if ( empty( $configured ) ) {
			wp_send_json_error( __( 'No networks configured', 'repostflow' ) );
		}

		$results = array();
		foreach ( $configured as $slug => $network ) {
			$template = RePostFlow::get_option( "networks.{$slug}.template", '{title} - {url}' );
			$message  = $this->plugin->templates->render( $template, $post->ID, $slug );

			// Get image.
			$image = '';
			if ( RePostFlow::get_option( 'advanced.include_image', true ) && has_post_thumbnail( $post->ID ) ) {
				$image = get_the_post_thumbnail_url( $post->ID, 'large' );
			}

			// Enqueue and immediately process.
			$log_id = $this->plugin->queue->enqueue( $post->ID, $slug, $message, $image );

			if ( $log_id ) {
				$this->plugin->queue->mark_processing( $log_id );
				$result = $network->publish( $post->ID, $message, $image );

				if ( $result['success'] ) {
					$this->plugin->queue->mark_published( $log_id, $result['external_id'], isset( $result['external_url'] ) ? $result['external_url'] : '', wp_json_encode( $result ) );
				} else {
					$this->plugin->queue->mark_failed( $log_id, $result['error'] );
				}

				$results[ $slug ] = $result;
			}
		}

		wp_send_json_success( array(
			'post_title' => get_the_title( $post->ID ),
			'post_id'    => $post->ID,
			'results'    => $results,
		) );
	}

	/**
	 * Clear the queue.
	 */
	public function clear_queue() {
		check_ajax_referer( 'rpf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'repostflow' ) );
		}

		$deleted = $this->plugin->queue->clear_queue();
		wp_send_json_success( array( 'deleted' => $deleted ) );
	}

	/**
	 * Delete a log entry.
	 */
	public function delete_log_entry() {
		check_ajax_referer( 'rpf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'repostflow' ) );
		}

		$log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;
		if ( ! $log_id ) {
			wp_send_json_error( __( 'Invalid ID', 'repostflow' ) );
		}

		$this->plugin->queue->delete( $log_id );
		wp_send_json_success();
	}

	/**
	 * Toggle plugin enabled/disabled state.
	 */
	public function toggle_enabled() {
		check_ajax_referer( 'rpf_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized', 'repostflow' ) );
		}

		$current = RePostFlow::get_option( 'general.enabled', false );
		RePostFlow::update_option( 'general.enabled', ! $current );

		wp_send_json_success( array( 'enabled' => ! $current ) );
	}
}
