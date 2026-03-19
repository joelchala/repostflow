<?php
/**
 * Main RePostFlow plugin class.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RePostFlow
 *
 * Singleton orchestrator for the entire plugin.
 */
final class RePostFlow {

	/**
	 * Plugin instance.
	 *
	 * @var RePostFlow|null
	 */
	private static $instance = null;

	/**
	 * Network registry.
	 *
	 * @var RPF_Network_Registry
	 */
	public $networks;

	/**
	 * Post selector.
	 *
	 * @var RPF_Post_Selector
	 */
	public $selector;

	/**
	 * Queue manager.
	 *
	 * @var RPF_Queue_Manager
	 */
	public $queue;

	/**
	 * Scheduler.
	 *
	 * @var RPF_Scheduler
	 */
	public $scheduler;

	/**
	 * Template engine.
	 *
	 * @var RPF_Template_Engine
	 */
	public $templates;

	/**
	 * Logger.
	 *
	 * @var RPF_Logger
	 */
	public $logger;

	/**
	 * Get singleton instance.
	 *
	 * @return RePostFlow
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_components();
		$this->register_networks();
		$this->init_hooks();
	}

	/**
	 * Initialize plugin components.
	 */
	private function init_components() {
		$this->logger    = new RPF_Logger();
		$this->templates = new RPF_Template_Engine();
		$this->networks  = new RPF_Network_Registry();
		$this->selector  = new RPF_Post_Selector();
		$this->queue     = new RPF_Queue_Manager( $this->logger );
		$this->scheduler = new RPF_Scheduler( $this->selector, $this->queue, $this->networks, $this->templates, $this->logger );
	}

	/**
	 * Register built-in social networks.
	 */
	private function register_networks() {
		$this->networks->register( new RPF_Twitter() );
		$this->networks->register( new RPF_Facebook() );
		$this->networks->register( new RPF_Instagram() );
		$this->networks->register( new RPF_LinkedIn() );
		$this->networks->register( new RPF_Telegram() );
		$this->networks->register( new RPF_Pinterest() );
		$this->networks->register( new RPF_Threads() );
		$this->networks->register( new RPF_Bluesky() );
		$this->networks->register( new RPF_Discord() );
		$this->networks->register( new RPF_Mastodon() );

		/**
		 * Allow third-party plugins to register additional networks.
		 *
		 * @param RPF_Network_Registry $networks Network registry instance.
		 */
		do_action( 'rpf_register_networks', $this->networks );
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		// Scheduler hooks.
		$this->scheduler->init();

		// Admin initialization.
		if ( is_admin() ) {
			$admin = new RPF_Admin( $this );
			$admin->init();

			$settings = new RPF_Settings();
			$settings->init();

			$ajax = new RPF_Ajax_Handler( $this );
			$ajax->init();

			$dashboard = new RPF_Dashboard_Widget( $this );
			$dashboard->init();
		}

		// Plugin action links.
		add_filter( 'plugin_action_links_' . RPF_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Add settings link to plugins page.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=repostflow' ) ),
			esc_html__( 'Settings', 'repostflow' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Get a plugin option.
	 *
	 * @param string $key     Option key (dot notation: 'general.post_types').
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function get_option( $key, $default = null ) {
		$options = get_option( 'rpf_settings', array() );

		$keys  = explode( '.', $key );
		$value = $options;

		foreach ( $keys as $k ) {
			if ( is_array( $value ) && isset( $value[ $k ] ) ) {
				$value = $value[ $k ];
			} else {
				return $default;
			}
		}

		return $value;
	}

	/**
	 * Update a plugin option.
	 *
	 * @param string $key   Option key (dot notation).
	 * @param mixed  $value New value.
	 */
	public static function update_option( $key, $value ) {
		$options = get_option( 'rpf_settings', array() );
		$keys    = explode( '.', $key );
		$ref     = &$options;

		foreach ( $keys as $i => $k ) {
			if ( $i === count( $keys ) - 1 ) {
				$ref[ $k ] = $value;
			} else {
				if ( ! isset( $ref[ $k ] ) || ! is_array( $ref[ $k ] ) ) {
					$ref[ $k ] = array();
				}
				$ref = &$ref[ $k ];
			}
		}

		update_option( 'rpf_settings', $options );
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
