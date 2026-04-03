<?php
/**
 * Shortcodes.
 *
 * @package FotoclubPerspectief
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class FCP_Shortcodes
 */
class FCP_Shortcodes {

	/**
	 * Init.
	 */
	public static function init() {
		add_shortcode( 'fcp_ledenlijst', array( __CLASS__, 'ledenlijst' ) );
	}

	/**
	 * Shortcode [fcp_ledenlijst show_contact="1"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function ledenlijst( $atts ) {
		$atts = shortcode_atts(
			array(
				'show_contact' => '1',
			),
			$atts,
			'fcp_ledenlijst'
		);

		$show_contact = ( '0' === $atts['show_contact'] || 'false' === $atts['show_contact'] ) ? false : true;

		$columns = array(
			'voornaam'              => __( 'Voornaam', 'fotoclubperspectief' ),
			'achternaam'            => __( 'Achternaam', 'fotoclubperspectief' ),
			'lidnr_fotobond'        => __( 'Lidnr fotobond', 'fotoclubperspectief' ),
			'bar'                   => __( 'Bar', 'fotoclubperspectief' ),
			'adres'                 => __( 'Adres', 'fotoclubperspectief' ),
			'postcode'              => __( 'Postcode', 'fotoclubperspectief' ),
			'plaats'                => __( 'Plaats', 'fotoclubperspectief' ),
			'telefoon'              => __( 'Telefoon', 'fotoclubperspectief' ),
			'email'                 => __( 'E-mail', 'fotoclubperspectief' ),
			'bestuur'               => __( 'Bestuur', 'fotoclubperspectief' ),
			'programma_cie'         => __( 'Programma cie', 'fotoclubperspectief' ),
			'tentoonstelling_cie'   => __( 'Tentoonstelling cie', 'fotoclubperspectief' ),
			'wedstrijden_cie'       => __( 'Wedstrijden cie', 'fotoclubperspectief' ),
			'archief_foto_cie'      => __( 'Archief foto cie', 'fotoclubperspectief' ),
			'website_cie'           => __( 'Website cie', 'fotoclubperspectief' ),
			'redactie_cie'          => __( 'Redactie cie', 'fotoclubperspectief' ),
			'natuur_werkgroep'      => __( 'Natuur werkgroep', 'fotoclubperspectief' ),
			'portret_werkgroep'     => __( 'Portret werkgroep', 'fotoclubperspectief' ),
			'straat_werkgroep'      => __( 'Straat werkgroep', 'fotoclubperspectief' ),
			'architectuur_werkgroep' => __( 'Architectuur werkgroep', 'fotoclubperspectief' ),
			'laptop_bediening'      => __( 'Laptop bediening', 'fotoclubperspectief' ),
		);

		if ( ! $show_contact ) {
			unset( $columns['telefoon'], $columns['email'] );
		}

		/**
		 * Filter which columns appear on the public ledenlijst table.
		 *
		 * @param array $columns Column key => label.
		 */
		$columns = apply_filters( 'fcp_ledenlijst_columns', $columns );

		$members = FCP_Member::get_members_sorted();
		if ( empty( $members ) ) {
			return '<p class="fcp-ledenlijst-empty">' . esc_html__( 'Geen leden gevonden.', 'fotoclubperspectief' ) . '</p>';
		}

		ob_start();
		?>
		<div class="fcp-ledenlijst-wrapper">
			<table class="fcp-ledenlijst">
				<thead>
					<tr>
						<?php foreach ( $columns as $key => $label ) : ?>
							<th scope="col"><?php echo esc_html( $label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $members as $m ) {
						echo self::render_member_row( (int) $m->ID, $columns );
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One table row.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $columns Columns.
	 */
	private static function render_member_row( $post_id, $columns ) {
		$html = '<tr>';
		foreach ( $columns as $key => $label ) {
			$html .= '<td>' . self::cell_value( $post_id, $key ) . '</td>';
		}
		$html .= '</tr>';
		return $html;
	}

	/**
	 * Cell HTML for key.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Column key.
	 * @return string
	 */
	private static function cell_value( $post_id, $key ) {
		$bool_keys = array(
			'bar',
			'bestuur',
			'programma_cie',
			'tentoonstelling_cie',
			'wedstrijden_cie',
			'archief_foto_cie',
			'website_cie',
			'redactie_cie',
			'natuur_werkgroep',
			'portret_werkgroep',
			'straat_werkgroep',
			'architectuur_werkgroep',
			'laptop_bediening',
		);

		if ( in_array( $key, $bool_keys, true ) ) {
			$v = get_post_meta( $post_id, '_fcp_' . $key, true );
			return $v === '1' ? esc_html__( 'Ja', 'fotoclubperspectief' ) : '—';
		}

		$meta_map = array(
			'voornaam'       => '_fcp_voornaam',
			'achternaam'     => '_fcp_achternaam',
			'lidnr_fotobond' => '_fcp_lidnr_fotobond',
			'adres'          => '_fcp_adres',
			'postcode'       => '_fcp_postcode',
			'plaats'         => '_fcp_plaats',
			'telefoon'       => '_fcp_telefoon',
			'email'          => '_fcp_email',
		);

		if ( isset( $meta_map[ $key ] ) ) {
			$raw = get_post_meta( $post_id, $meta_map[ $key ], true );
			if ( 'email' === $key ) {
				$raw = sanitize_email( $raw );
				return $raw ? '<a href="mailto:' . esc_attr( $raw ) . '">' . esc_html( $raw ) . '</a>' : '—';
			}
			return $raw !== '' && $raw !== null ? esc_html( (string) $raw ) : '—';
		}

		return '—';
	}
}
