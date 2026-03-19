<?php
/**
 * Network interface contract.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface RPF_Network_Interface
 *
 * All social network adapters must implement this interface.
 */
interface RPF_Network_Interface {

	/**
	 * Get network unique slug.
	 *
	 * @return string
	 */
	public function get_slug();

	/**
	 * Get network display label.
	 *
	 * @return string
	 */
	public function get_label();

	/**
	 * Get network icon (dashicon or SVG).
	 *
	 * @return string
	 */
	public function get_icon();

	/**
	 * Get network brand color (hex).
	 *
	 * @return string
	 */
	public function get_color();

	/**
	 * Check if the network has valid credentials configured.
	 *
	 * @return bool
	 */
	public function is_configured();

	/**
	 * Publish a post to this network.
	 *
	 * @param int    $post_id   WordPress post ID.
	 * @param string $message   Rendered message text.
	 * @param string $image_url Featured image URL (optional).
	 * @return array {
	 *     @type bool   $success      Whether the publish was successful.
	 *     @type string $external_id  ID of the post on the external network.
	 *     @type string $external_url URL of the post on the external network.
	 *     @type string $error        Error message if failed.
	 * }
	 */
	public function publish( $post_id, $message, $image_url = '' );

	/**
	 * Get the settings fields for this network's configuration.
	 *
	 * @return array Array of field definitions.
	 */
	public function get_settings_fields();

	/**
	 * Validate the current credentials by making a test API call.
	 *
	 * @return array {
	 *     @type bool   $valid   Whether credentials are valid.
	 *     @type string $message Status message.
	 * }
	 */
	public function validate_credentials();
}
