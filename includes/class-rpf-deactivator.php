<?php
/**
 * Plugin deactivator.
 *
 * @package RePostFlow
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RPF_Deactivator
 */
class RPF_Deactivator {

	/**
	 * Run deactivation tasks.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'rpf_process_queue' );
		wp_clear_scheduled_hook( 'rpf_cleanup_log' );
	}
}
