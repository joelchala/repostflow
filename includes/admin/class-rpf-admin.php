<?php
/**
 * Admin pages controller.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Admin
 */
class RPF_Admin {

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
	 * Initialize admin hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_menus() {
		add_menu_page(
			__( 'RePostFlow', 'repostflow' ),
			__( 'RePostFlow', 'repostflow' ),
			'manage_options',
			'repostflow',
			array( $this, 'render_dashboard' ),
			'dashicons-share-alt2',
			75
		);

		add_submenu_page(
			'repostflow',
			__( 'Dashboard', 'repostflow' ),
			__( 'Dashboard', 'repostflow' ),
			'manage_options',
			'repostflow',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'repostflow',
			__( 'Settings', 'repostflow' ),
			__( 'Settings', 'repostflow' ),
			'manage_options',
			'repostflow-settings',
			array( $this, 'render_settings' )
		);

		add_submenu_page(
			'repostflow',
			__( 'Queue', 'repostflow' ),
			__( 'Queue', 'repostflow' ),
			'manage_options',
			'repostflow-queue',
			array( $this, 'render_queue' )
		);

		add_submenu_page(
			'repostflow',
			__( 'History', 'repostflow' ),
			__( 'History', 'repostflow' ),
			'manage_options',
			'repostflow-history',
			array( $this, 'render_history' )
		);
	}

	/**
	 * Enqueue admin CSS and JS.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_assets( $hook_suffix ) {
		// Only load on our plugin pages.
		if ( false === strpos( $hook_suffix, 'repostflow' ) ) {
			return;
		}

		wp_enqueue_style(
			'rpf-admin',
			RPF_PLUGIN_URL . 'assets/css/rpf-admin.css',
			array(),
			RPF_VERSION
		);

		wp_enqueue_script(
			'rpf-admin',
			RPF_PLUGIN_URL . 'assets/js/rpf-admin.js',
			array( 'jquery' ),
			RPF_VERSION,
			true
		);

		// Get network labels for JavaScript.
		$networks       = $this->plugin->networks->get_all();
		$network_labels = array();
		foreach ( $networks as $slug => $network ) {
			$network_labels[ $slug ] = $network->get_label();
		}

		wp_localize_script( 'rpf-admin', 'rpfAdmin', array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'rpf_admin_nonce' ),
			'networkLabels'  => $network_labels,
			'strings'        => array(
				'testing'     => __( 'Testing connection...', 'repostflow' ),
				'connected'   => __( 'Connected!', 'repostflow' ),
				'failed'      => __( 'Connection failed', 'repostflow' ),
				'sharing'     => __( 'Sharing...', 'repostflow' ),
				'shared'      => __( 'Shared successfully!', 'repostflow' ),
				'confirm'     => __( 'Are you sure?', 'repostflow' ),
				'shareNow'    => __( 'Share Now', 'repostflow' ),
				'post'        => __( 'Post', 'repostflow' ),
			),
		) );
	}

	/**
	 * Render dashboard page.
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$stats      = $this->plugin->logger->get_stats();
		$next_run   = RPF_Scheduler::get_next_run();
		$networks   = $this->plugin->networks->get_all();
		$configured = $this->plugin->networks->get_configured();
		$enabled    = RePostFlow::get_option( 'general.enabled', false );
		$eligible   = $this->plugin->selector->get_eligible_count();
		$recent     = $this->plugin->logger->get_history( array( 'per_page' => 10 ) );

		include RPF_PLUGIN_DIR . 'templates/admin/dashboard-page.php';
	}

	/**
	 * Render settings page.
	 */
	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$networks   = $this->plugin->networks->get_all();
		$frequencies = RPF_Scheduler::get_frequency_options();
		$variables  = RPF_Template_Engine::get_available_variables();

		// Handle form submission.
		if ( isset( $_POST['rpf_save_settings'] ) && check_admin_referer( 'rpf_settings_nonce' ) ) {
			$this->save_settings( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per field in save_settings().
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'repostflow' ) . '</p></div>';
		}

		include RPF_PLUGIN_DIR . 'templates/admin/settings-page.php';
	}

	/**
	 * Render queue page.
	 */
	public function render_queue() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$queue_items = $this->plugin->queue->get_queue( 50 );
		include RPF_PLUGIN_DIR . 'templates/admin/queue-page.php';
	}

	/**
	 * Render history page.
	 */
	public function render_history() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Read-only list filters; no state is changed, so no nonce is needed.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$current_page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		$per_page     = 20;
		$filter_args  = array(
			'per_page' => $per_page,
			'page'     => $current_page,
		);

		if ( ! empty( $_GET['network'] ) ) {
			$filter_args['network'] = sanitize_text_field( wp_unslash( $_GET['network'] ) );
		}
		if ( ! empty( $_GET['status'] ) ) {
			$filter_args['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
		}

		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$history     = $this->plugin->logger->get_history( $filter_args );
		$total_items = $this->plugin->logger->get_total_count( $filter_args );
		$total_pages = ceil( $total_items / $per_page );
		$networks    = $this->plugin->networks->get_all();

		include RPF_PLUGIN_DIR . 'templates/admin/history-page.php';
	}

	/**
	 * Save settings from form POST.
	 *
	 * @param array $data POST data.
	 */
	private function save_settings( $data ) {
		$settings = get_option( 'rpf_settings', array() );

		// General settings.
		$settings['general']['enabled']           = isset( $data['rpf_enabled'] );
		$settings['general']['post_types']        = isset( $data['rpf_post_types'] ) ? array_map( 'sanitize_text_field', (array) $data['rpf_post_types'] ) : array( 'post' );
		$settings['general']['min_post_age_days'] = isset( $data['rpf_min_age'] ) ? absint( $data['rpf_min_age'] ) : 0;
		$settings['general']['cooldown_days']     = isset( $data['rpf_cooldown'] ) ? absint( $data['rpf_cooldown'] ) : 7;
		$settings['general']['posts_per_run']     = isset( $data['rpf_posts_per_run'] ) ? absint( $data['rpf_posts_per_run'] ) : 1;

		// Categories.
		$settings['general']['categories_include'] = isset( $data['rpf_cat_include'] ) ? array_map( 'absint', (array) $data['rpf_cat_include'] ) : array();
		$settings['general']['categories_exclude'] = isset( $data['rpf_cat_exclude'] ) ? array_map( 'absint', (array) $data['rpf_cat_exclude'] ) : array();

		// Schedule.
		$old_frequency = isset( $settings['schedule']['frequency'] ) ? $settings['schedule']['frequency'] : 'daily';
		$new_frequency = isset( $data['rpf_frequency'] ) ? sanitize_text_field( $data['rpf_frequency'] ) : 'daily';
		$settings['schedule']['frequency'] = $new_frequency;

		if ( $old_frequency !== $new_frequency ) {
			RPF_Scheduler::reschedule( $new_frequency );
		}

		// Network settings.
		if ( ! isset( $settings['networks'] ) ) {
			$settings['networks'] = array();
		}

		$all_networks = $this->plugin->networks->get_all();
		foreach ( $all_networks as $slug => $network ) {
			if ( ! isset( $settings['networks'][ $slug ] ) ) {
				$settings['networks'][ $slug ] = array();
			}

			$settings['networks'][ $slug ]['enabled'] = isset( $data[ "rpf_network_{$slug}_enabled" ] );

			// Save network-specific fields.
			foreach ( $network->get_settings_fields() as $field_key => $field ) {
				$post_key = "rpf_network_{$slug}_{$field_key}";
				if ( isset( $data[ $post_key ] ) ) {
					$value = sanitize_textarea_field( $data[ $post_key ] );

					// Encrypt sensitive fields.
					if ( $field['type'] === 'password' && ! empty( $value ) ) {
						// Only update if value changed (not the masked placeholder).
						if ( $value !== '••••••••' ) {
							$value = RPF_Encryption::encrypt( $value );
						} else {
							// Keep existing value.
							continue;
						}
					}

					$settings['networks'][ $slug ][ $field_key ] = $value;
				}
			}
		}

		// Advanced settings.
		$settings['advanced']['include_image']      = isset( $data['rpf_include_image'] );
		$settings['advanced']['utm_enabled']        = isset( $data['rpf_utm_enabled'] );
		$settings['advanced']['utm_source']         = isset( $data['rpf_utm_source'] ) ? sanitize_text_field( $data['rpf_utm_source'] ) : 'repostflow';
		$settings['advanced']['utm_medium']         = isset( $data['rpf_utm_medium'] ) ? sanitize_text_field( $data['rpf_utm_medium'] ) : 'social';
		$settings['advanced']['utm_campaign']       = isset( $data['rpf_utm_campaign'] ) ? sanitize_text_field( $data['rpf_utm_campaign'] ) : '';
		$settings['advanced']['log_retention_days'] = isset( $data['rpf_log_retention'] ) ? absint( $data['rpf_log_retention'] ) : 90;

		update_option( 'rpf_settings', $settings );
	}
}
