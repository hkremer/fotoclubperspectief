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
?>
<div class="fcp-agenda-home">
	<?php if ( empty( $items ) ) : ?>
		<p class="fcp-agenda-empty"><?php esc_html_e( 'Geen geplande activiteiten.', 'fotoclubperspectief' ); ?></p>
	<?php else : ?>
		<?php
		foreach ( $items as $post ) {
			$post_id     = (int) $post->ID;
			$datum       = get_post_meta( $post_id, '_fcp_datum', true );
			$beschrijving = get_post_meta( $post_id, '_fcp_beschrijving', true );
			$club        = get_post_meta( $post_id, '_fcp_clubavond', true ) === '1';
			$item_class  = $club ? 'fcp-agenda-item fcp-agenda--club' : 'fcp-agenda-item fcp-agenda--other';
			?>
			<article class="<?php echo esc_attr( $item_class ); ?>">
				<div class="fcp-agenda-item__head">
					<time class="fcp-agenda-date" datetime="<?php echo esc_attr( $datum ); ?>">
						<?php echo esc_html( FCP_Agenda::format_date_display( $datum ) ); ?>
					</time>
					<?php if ( $club ) : ?>
						<span class="fcp-agenda-kind"><?php echo esc_html__( 'CLUBAVOND', 'fotoclubperspectief' ); ?></span>
					<?php endif; ?>
				</div>
				<div class="fcp-agenda-desc">
					<?php echo apply_filters( 'the_content', $beschrijving ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</article>
			<?php
		}
		?>
	<?php endif; ?>
</div>
