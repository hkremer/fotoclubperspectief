<?php
/**
 * Plugin Name:       Fotoclub Perspectief
 * Description:       Ledenlijst, agenda en homepage-instellingen voor Fotoclub Perspectief.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Fotoclub Perspectief
 * Text Domain:       fotoclubperspectief
 * Domain Path:       /languages
 *
 * @package FotoclubPerspectief
 */

defined( 'ABSPATH' ) || exit;

define( 'FCP_VERSION', '1.0.0' );
define( 'FCP_PLUGIN_FILE', __FILE__ );
define( 'FCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once FCP_PLUGIN_DIR . 'includes/class-fcp-member.php';
require_once FCP_PLUGIN_DIR . 'includes/class-fcp-agenda.php';
require_once FCP_PLUGIN_DIR . 'includes/class-fcp-home-settings.php';
require_once FCP_PLUGIN_DIR . 'includes/class-fcp-shortcodes.php';
require_once FCP_PLUGIN_DIR . 'includes/template-tags.php';

/**
 * Flush rewrite rules on activation after CPTs are registered.
 */
function fcp_activate() {
	FCP_Member::register_post_type();
	FCP_Agenda::register_post_type();
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'fcp_activate' );

/**
 * Deactivate: flush rules.
 */
function fcp_deactivate() {
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'fcp_deactivate' );

/**
 * Bootstrap plugin.
 */
function fcp_bootstrap() {
	load_plugin_textdomain( 'fotoclubperspectief', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	FCP_Member::init();
	FCP_Agenda::init();
	FCP_Home_Settings::init();
	FCP_Shortcodes::init();
}

add_action( 'plugins_loaded', 'fcp_bootstrap' );

/**
 * Public assets.
 */
function fcp_enqueue_public_assets() {
	wp_register_style(
		'fcp-public',
		FCP_PLUGIN_URL . 'assets/public.css',
		array(),
		FCP_VERSION
	);
	wp_enqueue_style( 'fcp-public' );
}

add_action( 'wp_enqueue_scripts', 'fcp_enqueue_public_assets' );
