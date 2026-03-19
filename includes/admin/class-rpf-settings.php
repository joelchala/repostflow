<?php
/**
 * Settings API registration.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Settings
 */
class RPF_Settings {

	/**
	 * Initialize settings hooks.
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting( 'rpf_settings_group', 'rpf_settings', array(
			'sanitize_callback' => array( $this, 'sanitize' ),
		) );
	}

	/**
	 * Sanitize settings on save.
	 *
	 * @param array $input Raw input.
	 * @return array Sanitized output.
	 */
	public function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}
		return $input;
	}
}
