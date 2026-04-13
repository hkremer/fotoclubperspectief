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
		add_shortcode( 'fcp_agenda', array( __CLASS__, 'agenda' ) );
		add_action( 'wp', array( __CLASS__, 'maybe_mark_ledenlijst_noindex' ) );
	}

	/**
	 * Add noindex on pages using the ledenlijst shortcode.
	 */
	public static function maybe_mark_ledenlijst_noindex() {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}

		if ( ! has_shortcode( (string) $post->post_content, 'fcp_ledenlijst' ) ) {
			return;
		}

		add_action( 'wp_head', array( __CLASS__, 'print_noindex_meta' ), 1 );
	}

	/**
	 * Print robots noindex meta tag.
	 */
	public static function print_noindex_meta() {
		echo "<meta name=\"robots\" content=\"noindex, nofollow\" />\n";
	}

	/**
	 * Shortcode [fcp_agenda]
	 *
	 * Volledige agenda (alle gepubliceerde items, op datum gesorteerd).
	 *
	 * @param array       $atts    Shortcode attributes (geen attributen; gereserveerd).
	 * @param string|null $content Ingesloten shortcode-inhoud (ongebruikt).
	 * @return string
	 */
	public static function agenda( $atts = array(), $content = null ) {
		if ( ! class_exists( 'FCP_Agenda' ) ) {
			return '<p class="fcp-agenda-missing">' . esc_html__( 'Activeer de plugin Fotoclub Perspectief.', 'fotoclubperspectief' ) . '</p>';
		}

		$items = FCP_Agenda::get_all_by_date();

		return FCP_Agenda::render_agenda_items_html( $items, 'fcp-agenda--shortcode' );
	}

	/**
	 * Shortcode [fcp_ledenlijst show_contact="1" sort="voornaam"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function ledenlijst( $atts ) {
		if ( ! is_user_logged_in() ) {
			$login_url = wp_login_url( get_permalink() );
			return '<p class="fcp-ledenlijst-login-required">' . sprintf(
				/* translators: %s login URL */
				esc_html__( 'De ledenlijst is alleen beschikbaar voor ingelogde leden. %s', 'fotoclubperspectief' ),
				'<a href="' . esc_url( $login_url ) . '">' . esc_html__( 'Log hier in', 'fotoclubperspectief' ) . '</a>'
			) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'show_contact' => '1',
				'sort'         => 'voornaam',
			),
			$atts,
			'fcp_ledenlijst'
		);

		$show_contact = ( '0' === $atts['show_contact'] || 'false' === $atts['show_contact'] ) ? false : true;
		$sort_by      = ( 'achternaam' === strtolower( (string) $atts['sort'] ) ) ? 'achternaam' : 'voornaam';

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

		$members = FCP_Member::get_members_sorted( $sort_by );
		if ( empty( $members ) ) {
			return '<p class="fcp-ledenlijst-empty">' . esc_html__( 'Geen leden gevonden.', 'fotoclubperspectief' ) . '</p>';
		}

		$bool_keys = self::bool_column_keys();

		ob_start();
		?>
		<div class="fcp-ledenlijst-wrapper">
			<table class="fcp-ledenlijst">
				<thead>
					<tr>
						<?php
						foreach ( $columns as $key => $label ) :
							$th_class = 'fcp-ledenlijst-col fcp-ledenlijst-col--text';
							if ( in_array( $key, $bool_keys, true ) ) {
								$th_class = 'fcp-ledenlijst-col fcp-ledenlijst-col--bool';
							}
							?>
							<th scope="col" class="<?php echo esc_attr( $th_class ); ?>"><?php echo esc_html( $label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $members as $m ) {
						echo self::render_member_row( (int) $m->ID, $columns, $bool_keys );
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Kolommen die als boolean (Ja/—) worden weergegeven.
	 *
	 * @return string[]
	 */
	private static function bool_column_keys() {
		return array(
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
	}

	/**
	 * One table row.
	 *
	 * @param int      $post_id  Post ID.
	 * @param array    $columns  Columns.
	 * @param string[] $bool_keys Bool column keys.
	 */
	private static function render_member_row( $post_id, $columns, $bool_keys ) {
		$html = '<tr>';
		foreach ( $columns as $key => $label ) {
			$td_class = 'fcp-ledenlijst-col fcp-ledenlijst-col--text';
			if ( in_array( $key, $bool_keys, true ) ) {
				$td_class = 'fcp-ledenlijst-col fcp-ledenlijst-col--bool';
			}
			$html .= '<td class="' . esc_attr( $td_class ) . '">' . self::cell_value( $post_id, $key ) . '</td>';
		}
		$html .= '</tr>';
		return $html;
	}

	/**
	 * Alle witruimte (inclusief regeleinden) tot één spatie voor weergave op één regel.
	 *
	 * @param string $s Raw.
	 * @return string
	 */
	private static function collapse_whitespace_one_line( $s ) {
		$s = trim( (string) $s );
		if ( '' === $s ) {
			return '';
		}
		return (string) preg_replace( '/\s+/u', ' ', $s );
	}

	/**
	 * Cell HTML for key.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Column key.
	 * @return string
	 */
	private static function cell_value( $post_id, $key ) {
		$bool_keys = self::bool_column_keys();

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
				$raw   = self::collapse_whitespace_one_line( (string) $raw );
				$email = sanitize_email( $raw );
				return $email ? '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>' : '—';
			}
			$raw = self::collapse_whitespace_one_line( (string) $raw );
			return $raw !== '' ? esc_html( $raw ) : '—';
		}

		return '—';
	}
}
