<?php
/**
 * Agenda-blok homepage (komende 4 items).
 *
 * @package FotoclubPerspectief_Child
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'FCP_Agenda' ) ) {
	echo '<p class="fcp-agenda-missing">' . esc_html__( 'Activeer de plugin Fotoclub Perspectief.', 'fotoclubperspectief' ) . '</p>';
	return;
}

$items = FCP_Agenda::get_upcoming( 4 );
echo FCP_Agenda::render_agenda_items_html( $items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
