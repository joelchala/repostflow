<?php
/**
 * RePostFlow uninstall handler.
 *
 * Removes all plugin data when the plugin is deleted.
 *
 * @package RePostFlow
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove options.
delete_option( 'rpf_settings' );
delete_option( 'rpf_version' );

// Remove custom table.
global $wpdb;
$table_name = $wpdb->prefix . 'rpf_share_log';
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange

// Clear scheduled hooks.
wp_clear_scheduled_hook( 'rpf_process_queue' );
wp_clear_scheduled_hook( 'rpf_cleanup_log' );
