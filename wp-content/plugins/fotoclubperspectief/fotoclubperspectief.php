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
require_once FCP_PLUGIN_DIR . 'includes/class-fcp-member-import.php';
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
	FCP_Member_Import::init_admin();
	FCP_Agenda::init();
	FCP_Home_Settings::init();
	FCP_Shortcodes::init();
}

add_action( 'plugins_loaded', 'fcp_bootstrap' );

if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
	/**
	 * WP-CLI: wp fcp import-leden /pad/naar/leden.csv [--dry-run] [--no-update]
	 */
	WP_CLI::add_command(
		'fcp import-leden',
		function ( $args, $assoc_args ) {
			if ( empty( $args[0] ) ) {
				WP_CLI::error( 'Gebruik: wp fcp import-leden /pad/naar/bestand.csv' );
			}
			$path = realpath( $args[0] );
			if ( ! $path || ! is_readable( $path ) ) {
				WP_CLI::error( 'Bestand niet gevonden of niet leesbaar.' );
			}
			$import_args = array(
				'update_existing' => ! isset( $assoc_args['no-update'] ),
				'dry_run'         => isset( $assoc_args['dry-run'] ),
			);
			$result = FCP_Member_Import::import_file( $path, $import_args );
			WP_CLI::success(
				sprintf(
					'Nieuw: %d, bijgewerkt: %d, overgeslagen: %d',
					$result['created'],
					$result['updated'],
					$result['skipped']
				)
			);
			if ( ! empty( $result['errors'] ) ) {
				foreach ( $result['errors'] as $e ) {
					WP_CLI::warning( $e );
				}
			}
		},
		array(
			'shortdesc' => 'Importeer leden uit een CSV-bestand.',
			'synopsis'  => array(
				array(
					'type'        => 'positional',
					'name'        => 'file',
					'description' => 'Pad naar het .csv-bestand',
					'optional'    => false,
				),
				array(
					'type'        => 'flag',
					'name'        => 'dry-run',
					'description' => 'Alleen tellen, geen wijzigingen',
					'optional'    => true,
				),
				array(
					'type'        => 'flag',
					'name'        => 'no-update',
					'description' => 'Geen bestaande leden bijwerken (alleen nieuwe aanmaken)',
					'optional'    => true,
				),
			),
		)
	);
}

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

	wp_register_script(
		'fcp-ledenlijst',
		FCP_PLUGIN_URL . 'assets/ledenlijst.js',
		array(),
		FCP_VERSION,
		true
	);
}

add_action( 'wp_enqueue_scripts', 'fcp_enqueue_public_assets' );
