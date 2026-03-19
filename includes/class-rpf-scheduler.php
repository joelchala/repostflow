<?php
/**
 * WP-Cron scheduler for automated sharing.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Scheduler
 */
class RPF_Scheduler {

	/** @var RPF_Post_Selector */
	private $selector;

	/** @var RPF_Queue_Manager */
	private $queue;

	/** @var RPF_Network_Registry */
	private $networks;

	/** @var RPF_Template_Engine */
	private $templates;

	/** @var RPF_Logger */
	private $logger;

	/**
	 * Constructor.
	 */
	public function __construct( $selector, $queue, $networks, $templates, $logger ) {
		$this->selector  = $selector;
		$this->queue     = $queue;
		$this->networks  = $networks;
		$this->templates = $templates;
		$this->logger    = $logger;
	}

	/**
	 * Initialize scheduler hooks.
	 */
	public function init() {
		add_filter( 'cron_schedules', array( $this, 'add_custom_intervals' ) );
		add_action( 'rpf_process_queue', array( $this, 'process' ) );
		add_action( 'rpf_cleanup_log', array( $this, 'cleanup' ) );
	}

	/**
	 * Register custom cron intervals.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function add_custom_intervals( $schedules ) {
		$schedules['rpf_every_2_hours'] = array(
			'interval' => 7200,
			'display'  => __( 'Every 2 Hours', 'repostflow' ),
		);
		$schedules['rpf_every_4_hours'] = array(
			'interval' => 14400,
			'display'  => __( 'Every 4 Hours', 'repostflow' ),
		);
		$schedules['rpf_every_6_hours'] = array(
			'interval' => 21600,
			'display'  => __( 'Every 6 Hours', 'repostflow' ),
		);
		$schedules['rpf_every_8_hours'] = array(
			'interval' => 28800,
			'display'  => __( 'Every 8 Hours', 'repostflow' ),
		);
		$schedules['rpf_every_12_hours'] = array(
			'interval' => 43200,
			'display'  => __( 'Every 12 Hours', 'repostflow' ),
		);
		$schedules['rpf_every_2_days'] = array(
			'interval' => 172800,
			'display'  => __( 'Every 2 Days', 'repostflow' ),
		);
		$schedules['rpf_every_3_days'] = array(
			'interval' => 259200,
			'display'  => __( 'Every 3 Days', 'repostflow' ),
		);
		$schedules['rpf_weekly'] = array(
			'interval' => 604800,
			'display'  => __( 'Once Weekly', 'repostflow' ),
		);

		return $schedules;
	}

	/**
	 * Get available frequency options for settings.
	 *
	 * @return array
	 */
	public static function get_frequency_options() {
		return array(
			'rpf_every_2_hours'  => __( 'Every 2 Hours', 'repostflow' ),
			'rpf_every_4_hours'  => __( 'Every 4 Hours', 'repostflow' ),
			'rpf_every_6_hours'  => __( 'Every 6 Hours', 'repostflow' ),
			'rpf_every_8_hours'  => __( 'Every 8 Hours', 'repostflow' ),
			'rpf_every_12_hours' => __( 'Every 12 Hours', 'repostflow' ),
			'daily'              => __( 'Once Daily', 'repostflow' ),
			'rpf_every_2_days'   => __( 'Every 2 Days', 'repostflow' ),
			'rpf_every_3_days'   => __( 'Every 3 Days', 'repostflow' ),
			'rpf_weekly'         => __( 'Once Weekly', 'repostflow' ),
		);
	}

	/**
	 * Main processing function — triggered by WP-Cron.
	 */
	public function process() {
		$enabled = RePostFlow::get_option( 'general.enabled', false );
		if ( ! $enabled ) {
			return;
		}

		$posts_per_run = (int) RePostFlow::get_option( 'general.posts_per_run', 1 );

		// First, process any existing queued items.
		$this->process_queue();

		// Then, select new posts and enqueue them.
		$configured_networks = $this->networks->get_configured();
		if ( empty( $configured_networks ) ) {
			return;
		}

		$posts = $this->selector->pick_multiple( $posts_per_run );

		foreach ( $posts as $post ) {
			foreach ( $configured_networks as $network ) {
				$slug     = $network->get_slug();
				$template = $this->get_network_template( $slug );
				$message  = $this->templates->render( $template, $post->ID, $slug );
				$image    = $this->get_post_image( $post->ID );

				$this->queue->enqueue( $post->ID, $slug, $message, $image );
			}
		}

		// Process the newly enqueued items.
		$this->process_queue();

		/**
		 * Fires after the scheduled processing run.
		 *
		 * @param array $posts  Posts that were selected.
		 * @param array $configured_networks Networks that received shares.
		 */
		do_action( 'rpf_after_process', $posts, $configured_networks );
	}

	/**
	 * Process queued items by sending them to networks.
	 */
	private function process_queue() {
		$max_per_run = 10;
		$processed   = 0;

		while ( $processed < $max_per_run ) {
			$item = $this->queue->dequeue_next();
			if ( ! $item ) {
				break;
			}

			$this->queue->mark_processing( $item->id );

			$network = $this->networks->get_by_slug( $item->network );
			if ( ! $network || ! $network->is_configured() ) {
				$this->queue->mark_failed( $item->id, __( 'Network not configured', 'repostflow' ) );
				$processed++;
				continue;
			}

			/**
			 * Allow skipping specific shares.
			 *
			 * @param bool   $should_share Whether to share.
			 * @param object $item         Queue item.
			 */
			$should_share = apply_filters( 'rpf_should_share_post', true, $item );
			if ( ! $should_share ) {
				$this->queue->mark_failed( $item->id, __( 'Skipped by filter', 'repostflow' ) );
				$processed++;
				continue;
			}

			// Publish to network.
			$result = $network->publish( (int) $item->post_id, $item->message, $item->image_url );

			if ( $result['success'] ) {
				$this->queue->mark_published(
					$item->id,
					$result['external_id'],
					isset( $result['external_url'] ) ? $result['external_url'] : '',
					wp_json_encode( $result )
				);

				/**
				 * Fires after a successful share.
				 *
				 * @param object $item   Queue item.
				 * @param array  $result API result.
				 */
				do_action( 'rpf_after_share_success', $item, $result );
			} else {
				$this->queue->mark_failed( $item->id, $result['error'] );

				/**
				 * Fires after a failed share.
				 *
				 * @param object $item   Queue item.
				 * @param array  $result API result.
				 */
				do_action( 'rpf_after_share_failure', $item, $result );
			}

			$processed++;
		}
	}

	/**
	 * Get the template for a specific network.
	 *
	 * @param string $network Network slug.
	 * @return string
	 */
	private function get_network_template( $network ) {
		$template = RePostFlow::get_option( "networks.{$network}.template", '' );

		if ( empty( $template ) ) {
			$defaults = array(
				'twitter'   => "🎮 {title}\n\n{url}\n\n{hashtags}",
				'facebook'  => "🎮 {title}\n\n{excerpt}\n\n👉 {url}",
				'instagram' => "🎮 {title}\n\n{excerpt}\n\n{hashtags}\n\n🔗 Link in bio",
				'linkedin'  => "{title}\n\n{excerpt}\n\n{url}",
				'telegram'  => "🎮 <b>{title}</b>\n\n{excerpt}\n\n👉 {url}",
				'pinterest' => "{title}",
				'threads'   => "{title}\n\n{url}\n\n{hashtags}",
				'bluesky'   => "{title}\n\n{url}\n\n{hashtags}",
				'discord'   => "**{title}**\n\n{url}\n\n{hashtags}",
				'mastodon'  => "{title}\n\n{url}\n\n{hashtags}",
			);
			$template = isset( $defaults[ $network ] ) ? $defaults[ $network ] : '{title} - {url}';
		}

		return $template;
	}

	/**
	 * Get featured image URL for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_post_image( $post_id ) {
		$include_image = RePostFlow::get_option( 'advanced.include_image', true );
		if ( ! $include_image ) {
			return '';
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( ! $thumbnail_id ) {
			return '';
		}

		$image = wp_get_attachment_image_url( $thumbnail_id, 'large' );
		return $image ? $image : '';
	}

	/**
	 * Cleanup old log entries.
	 */
	public function cleanup() {
		$retention = (int) RePostFlow::get_option( 'advanced.log_retention_days', 90 );
		$this->logger->cleanup( $retention );
	}

	/**
	 * Reschedule the cron event with new frequency.
	 *
	 * @param string $frequency New frequency slug.
	 */
	public static function reschedule( $frequency ) {
		wp_clear_scheduled_hook( 'rpf_process_queue' );
		wp_schedule_event( time(), $frequency, 'rpf_process_queue' );
	}

	/**
	 * Get next scheduled run info.
	 *
	 * @return array
	 */
	public static function get_next_run() {
		$timestamp = wp_next_scheduled( 'rpf_process_queue' );
		if ( ! $timestamp ) {
			return array(
				'timestamp' => 0,
				'human'     => __( 'Not scheduled', 'repostflow' ),
			);
		}

		return array(
			'timestamp' => $timestamp,
			'human'     => human_time_diff( time(), $timestamp ),
		);
	}
}
