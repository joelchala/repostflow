<?php
/**
 * Plugin Name: RePostFlow
 * Plugin URI:  https://github.com/joelchala/repostflow
 * Description: Automatically reshare your evergreen WordPress content to social media networks on a smart schedule. Supports X/Twitter, Facebook, Instagram, LinkedIn, Telegram, Pinterest, Threads, Bluesky, Discord & Mastodon.
 * Version:     1.1.0
 * Author:      Joel Chala
 * Author URI:  https://kaggchala.com/
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: repostflow
 * Domain Path: /languages
 * Requires at least: 6.2
 * Requires PHP: 7.4
 *
 * @package RePostFlow
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'RPF_VERSION', '1.1.0' );
define( 'RPF_PLUGIN_FILE', __FILE__ );
define( 'RPF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RPF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RPF_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load plugin textdomain for translations.
 */
function rpf_load_textdomain() {
	load_plugin_textdomain( 'repostflow', false, dirname( RPF_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'plugins_loaded', 'rpf_load_textdomain' );

// Include core files.
require_once RPF_PLUGIN_DIR . 'includes/class-rpf-encryption.php';
require_once RPF_PLUGIN_DIR . 'includes/class-rpf-logger.php';
require_once RPF_PLUGIN_DIR . 'includes/class-rpf-template-engine.php';
require_once RPF_PLUGIN_DIR . 'includes/class-rpf-post-selector.php';
require_once RPF_PLUGIN_DIR . 'includes/class-rpf-queue-manager.php';
require_once RPF_PLUGIN_DIR . 'includes/class-rpf-scheduler.php';

// Include network files.
require_once RPF_PLUGIN_DIR . 'includes/networks/interface-rpf-network.php';
require_once RPF_PLUGIN_DIR . 'includes/networks/abstract-rpf-network.php';
require_once RPF_PLUGIN_DIR . 'includes/networks/class-rpf-network-registry.php';
require_once RPF_PLUGIN_DIR . 'includes/networks/class-rpf-twitter.php';
require_once RPF_PLUGIN_DIR . 'includes/networks/class-rpf-facebook.php';
require_once RPF_PLUGIN_DIR . 'includes/networks/class-rpf-instagram.php';
require_once RPF_PLUGIN_DIR . 'includes/networks/class-rpf-linkedin.php';
require_once RPF_PLUGIN_DIR . 'includes/networks/class-rpf-telegram.php';
require_once RPF_PLUGIN_DIR . 'includes/networks/class-rpf-pinterest.php';
require_once RPF_PLUGIN_DIR . 'includes/networks/class-rpf-threads.php';
require_once RPF_PLUGIN_DIR . 'includes/networks/class-rpf-bluesky.php';
require_once RPF_PLUGIN_DIR . 'includes/networks/class-rpf-discord.php';
require_once RPF_PLUGIN_DIR . 'includes/networks/class-rpf-mastodon.php';

// Include admin files.
if ( is_admin() ) {
	require_once RPF_PLUGIN_DIR . 'includes/admin/class-rpf-admin.php';
	require_once RPF_PLUGIN_DIR . 'includes/admin/class-rpf-settings.php';
	require_once RPF_PLUGIN_DIR . 'includes/admin/class-rpf-ajax-handler.php';
	require_once RPF_PLUGIN_DIR . 'includes/admin/class-rpf-dashboard-widget.php';
	require_once RPF_PLUGIN_DIR . 'includes/admin/class-rpf-list-table.php';
}

// Include main plugin class.
require_once RPF_PLUGIN_DIR . 'includes/class-repostflow.php';

/**
 * Activation hook.
 */
function rpf_activate() {
	require_once RPF_PLUGIN_DIR . 'includes/class-rpf-activator.php';
	RPF_Activator::activate();
}
register_activation_hook( __FILE__, 'rpf_activate' );

/**
 * Deactivation hook.
 */
function rpf_deactivate() {
	require_once RPF_PLUGIN_DIR . 'includes/class-rpf-deactivator.php';
	RPF_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'rpf_deactivate' );

/**
 * Initialize the plugin.
 */
function rpf_init() {
	return RePostFlow::instance();
}
add_action( 'plugins_loaded', 'rpf_init', 20 );
