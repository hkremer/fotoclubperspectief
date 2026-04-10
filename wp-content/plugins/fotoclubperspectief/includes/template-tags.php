<?php
/**
 * Template helpers (theme / front-end).
 *
 * @package FotoclubPerspectief
 */

defined( 'ABSPATH' ) || exit;

/**
 * Homepage options (merged defaults).
 *
 * @return array
 */
function fcp_get_home_options() {
	if ( ! class_exists( 'FCP_Home_Settings' ) ) {
		return array();
	}
	return FCP_Home_Settings::get_options();
}

/**
 * Whether plugin homepage layout is enabled.
 *
 * @return bool
 */
function fcp_is_homepage_enabled() {
	$o       = fcp_get_home_options();
	$enabled = ! empty( $o['enable_homepage'] );

	/**
	 * Filter whether the plugin homepage layout is active.
	 *
	 * @param bool  $enabled Current state.
	 * @param array $options Full homepage options.
	 */
	return (bool) apply_filters( 'fcp_enable_homepage_layout', $enabled, $o );
}

/**
 * Card links for homepage row (filterable).
 *
 * @return array<string,string> Keys: portret, natuur, straat, architectuur.
 */
function fcp_get_card_links() {
	$o = fcp_get_home_options();
	$links = array(
		'portret'       => isset( $o['card_portret_url'] ) ? $o['card_portret_url'] : '',
		'natuur'        => isset( $o['card_natuur_url'] ) ? $o['card_natuur_url'] : '',
		'straat'        => isset( $o['card_straat_url'] ) ? $o['card_straat_url'] : '',
		'architectuur'  => isset( $o['card_architectuur_url'] ) ? $o['card_architectuur_url'] : '',
	);
	/**
	 * Filter card URLs on the Fotoclub homepage.
	 *
	 * @param array $links Keys portret, natuur, straat, architectuur.
	 */
	return apply_filters( 'fcp_card_links', $links );
}

/**
 * Card image attachment IDs for homepage (filterable).
 *
 * @return array<string,int> Keys: portret, natuur, straat, architectuur.
 */
function fcp_get_card_images() {
	$o = fcp_get_home_options();
	$images = array(
		'portret'      => isset( $o['card_portret_image_id'] ) ? (int) $o['card_portret_image_id'] : 0,
		'natuur'       => isset( $o['card_natuur_image_id'] ) ? (int) $o['card_natuur_image_id'] : 0,
		'straat'       => isset( $o['card_straat_image_id'] ) ? (int) $o['card_straat_image_id'] : 0,
		'architectuur' => isset( $o['card_architectuur_image_id'] ) ? (int) $o['card_architectuur_image_id'] : 0,
	);
	/**
	 * Filter card image attachment IDs on the Fotoclub homepage.
	 *
	 * @param array $images Keys portret, natuur, straat, architectuur; values are attachment IDs.
	 */
	return apply_filters( 'fcp_card_images', $images );
}
