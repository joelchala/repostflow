<?php
/**
 * Network registry for managing social network adapters.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Network_Registry
 */
class RPF_Network_Registry {

	/**
	 * Registered networks.
	 *
	 * @var RPF_Network_Interface[]
	 */
	private $networks = array();

	/**
	 * Register a network adapter.
	 *
	 * @param RPF_Network_Interface $network Network instance.
	 */
	public function register( RPF_Network_Interface $network ) {
		$this->networks[ $network->get_slug() ] = $network;
	}

	/**
	 * Get all registered networks.
	 *
	 * @return RPF_Network_Interface[]
	 */
	public function get_all() {
		return $this->networks;
	}

	/**
	 * Get a network by slug.
	 *
	 * @param string $slug Network slug.
	 * @return RPF_Network_Interface|null
	 */
	public function get_by_slug( $slug ) {
		return isset( $this->networks[ $slug ] ) ? $this->networks[ $slug ] : null;
	}

	/**
	 * Get all networks that are enabled and configured.
	 *
	 * @return RPF_Network_Interface[]
	 */
	public function get_configured() {
		$configured = array();
		foreach ( $this->networks as $slug => $network ) {
			if ( $network->is_enabled() && $network->is_configured() ) {
				$configured[ $slug ] = $network;
			}
		}
		return $configured;
	}

	/**
	 * Get all enabled networks (may not be fully configured).
	 *
	 * @return RPF_Network_Interface[]
	 */
	public function get_enabled() {
		$enabled = array();
		foreach ( $this->networks as $slug => $network ) {
			if ( $network->is_enabled() ) {
				$enabled[ $slug ] = $network;
			}
		}
		return $enabled;
	}

	/**
	 * Get count of configured networks.
	 *
	 * @return int
	 */
	public function count_configured() {
		return count( $this->get_configured() );
	}
}
