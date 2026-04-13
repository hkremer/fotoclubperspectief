<?php
/**
 * Front page — 3×3 grid (plugin-opties + agenda).
 *
 * @package FotoclubPerspectief_Child
 */

defined( 'ABSPATH' ) || exit;

get_header();

$opt = function_exists( 'fcp_get_home_options' ) ? fcp_get_home_options() : array();
$use_fcp_home = function_exists( 'fcp_is_homepage_enabled' ) ? fcp_is_homepage_enabled() : false;
$defaults = array(
	'featured_image_id' => 0,
	'featured_caption'  => '',
	'ct1_title'         => '',
	'ct1_content'       => '',
	'ct1_image_id'      => 0,
	'ct2_title'         => '',
	'ct2_content'       => '',
	'ct2_image_id'      => 0,
	'mededelingen'      => array(),
);
$opt = array_merge( $defaults, is_array( $opt ) ? $opt : array() );

$card_links = function_exists( 'fcp_get_card_links' ) ? fcp_get_card_links() : array();
$card_images = function_exists( 'fcp_get_card_images' ) ? fcp_get_card_images() : array();
$card_labels = array(
	'portret'      => 'PORTRET',
	'natuur'       => 'NATUUR',
	'straat'       => 'STRAAT',
	'architectuur' => 'ARCHITECTUUR',
);
?>

<main id="site-content" role="main">
	<?php if ( have_posts() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class(); ?> id="post-<?php the_ID(); ?>">
				<header class="entry-header has-text-align-center">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</header>
				<?php if ( $use_fcp_home ) : ?>
				<div class="fcp-home-grid">
					<section class="fcp-cell fcp-mededelingen" aria-labelledby="fcp-mededelingen-heading">
						<h2 id="fcp-mededelingen-heading" class="fcp-block-title"><?php esc_html_e( 'Mededelingen', 'fotoclubperspectief' ); ?></h2>
						<?php
						if ( ! empty( $opt['mededelingen'] ) && is_array( $opt['mededelingen'] ) ) {
							foreach ( $opt['mededelingen'] as $block ) {
								$t = isset( $block['title'] ) ? $block['title'] : '';
								$c = isset( $block['content'] ) ? $block['content'] : '';
								if ( '' === $t && '' === $c ) {
									continue;
								}
								echo '<div class="fcp-mededeling-block">';
								if ( $t ) {
									echo '<h3 class="fcp-mededeling-kop">' . esc_html( $t ) . '</h3>';
								}
								if ( $c ) {
									echo '<div class="fcp-mededeling-tekst">' . wp_kses_post( wpautop( $c ) ) . '</div>';
								}
								echo '</div>';
							}
						} else {
							echo '<p>' . esc_html__( 'Nog geen mededelingen.', 'fotoclubperspectief' ) . '</p>';
						}
						?>
					</section>

					<section class="fcp-cell fcp-uitgelicht" aria-labelledby="fcp-uitgelicht-heading">
						<h2 id="fcp-uitgelicht-heading" class="screen-reader-text"><?php esc_html_e( 'Uitgelicht', 'fotoclubperspectief' ); ?></h2>
						<?php
						$fid = (int) $opt['featured_image_id'];
						if ( $fid && wp_attachment_is_image( $fid ) ) {
							$img = wp_get_attachment_image( $fid, 'large', false, array( 'class' => 'fcp-featured-img' ) );
							echo '<figure class="fcp-uitgelicht-fig">';
							echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							if ( ! empty( $opt['featured_caption'] ) ) {
								echo '<figcaption>' . esc_html( $opt['featured_caption'] ) . '</figcaption>';
							}
							echo '</figure>';
						} else {
							echo '<p class="fcp-placeholder">' . esc_html__( 'Geen uitgelichte afbeelding ingesteld.', 'fotoclubperspectief' ) . '</p>';
						}
						?>
					</section>

					<section class="fcp-cell fcp-custom1" aria-labelledby="fcp-ct1-heading">
						<h2 id="fcp-ct1-heading" class="fcp-block-title"><?php echo $opt['ct1_title'] ? esc_html( $opt['ct1_title'] ) : esc_html__( 'Linker blok', 'fotoclubperspectief' ); ?></h2>
						<div class="fcp-custom-body"><?php echo $opt['ct1_content'] ? apply_filters( 'the_content', $opt['ct1_content'] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<?php
						$i1 = (int) $opt['ct1_image_id'];
						if ( $i1 && wp_attachment_is_image( $i1 ) ) {
							echo '<div class="fcp-custom-image">' . wp_get_attachment_image( $i1, 'medium' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</section>

					<section class="fcp-cell fcp-custom2" aria-labelledby="fcp-ct2-heading">
						<h2 id="fcp-ct2-heading" class="fcp-block-title"><?php echo $opt['ct2_title'] ? esc_html( $opt['ct2_title'] ) : esc_html__( 'Midden blok', 'fotoclubperspectief' ); ?></h2>
						<div class="fcp-custom-body"><?php echo $opt['ct2_content'] ? apply_filters( 'the_content', $opt['ct2_content'] ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<?php
						$i2 = (int) $opt['ct2_image_id'];
						if ( $i2 && wp_attachment_is_image( $i2 ) ) {
							echo '<div class="fcp-custom-image">' . wp_get_attachment_image( $i2, 'medium' ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</section>

					<section class="fcp-cell fcp-agenda" aria-labelledby="fcp-agenda-heading">
						<div class="fcp-agenda-section__head">
							<h2 id="fcp-agenda-heading" class="fcp-block-title"><?php esc_html_e( 'Agenda', 'fotoclubperspectief' ); ?></h2>
						</div>
						<?php get_template_part( 'parts/agenda', 'home' ); ?>
						<p class="fcp-agenda-program-foot">
							<a class="fcp-agenda-program-link" href="<?php echo esc_url( home_url( '/programma' ) ); ?>">
								<?php echo esc_html__( '>>> Volledig programma', 'fotoclubperspectief' ); ?>
							</a>
						</p>
					</section>

					<section class="fcp-cell fcp-cards" aria-label="<?php esc_attr_e( 'Thema\'s', 'fotoclubperspectief' ); ?>">
						<h2 id="fcp-cards-heading" class="fcp-block-title"><?php esc_html_e( 'WERKGROEPEN', 'fotoclubperspectief' ); ?></h2>
						<div class="fcp-cards-inner">
							<?php
							foreach ( $card_labels as $key => $label ) {
								$url     = isset( $card_links[ $key ] ) ? $card_links[ $key ] : '';
								$att_id  = isset( $card_images[ $key ] ) ? (int) $card_images[ $key ] : 0;
								$has_img = $att_id && wp_attachment_is_image( $att_id );
								$inner   = '';
								if ( $has_img ) {
									$inner .= '<span class="fcp-card__media">' . wp_get_attachment_image(
										$att_id,
										'medium_large',
										false,
										array(
											'class' => 'fcp-card__img',
											'alt'   => $label,
										)
									) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								$inner .= '<span class="fcp-card__label">' . esc_html( $label ) . '</span>';

								if ( $url ) {
									printf(
										'<a class="fcp-card" href="%s">%s</a>',
										esc_url( $url ),
										$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									);
								} else {
									printf(
										'<span class="fcp-card fcp-card--static">%s</span>',
										$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									);
								}
							}
							?>
						</div>
					</section>
				</div>
				<?php endif; ?>
				<div class="post-inner">
					<div class="entry-content">
						<?php the_content(); ?>
					</div>
				</div>
			</article>
			<?php
		endwhile;
		?>
	<?php endif; ?>
</main>

<?php
get_footer();
