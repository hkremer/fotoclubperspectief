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

		return FCP_Agenda::render_agenda_items_html( $items, 'fcp-agenda--shortcode', 'table' );
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

		$bool_filter_columns = array();
		foreach ( $columns as $key => $label ) {
			if ( in_array( $key, $bool_keys, true ) ) {
				$bool_filter_columns[ $key ] = $label;
			}
		}

		wp_enqueue_script( 'fcp-ledenlijst' );

		$role_filter_name = wp_unique_id( 'fcp_ll_role_' );

		ob_start();
		?>
		<div class="fcp-ledenlijst-wrapper" data-fcp-ledenlijst-root>
			<div class="fcp-ledenlijst-toolbar">
				<div class="fcp-ledenlijst-toolbar-section fcp-ledenlijst-toolbar-section--display">
					<span class="fcp-ledenlijst-toolbar-heading"><?php esc_html_e( 'Weergave', 'fotoclubperspectief' ); ?></span>
					<label class="fcp-ledenlijst-toggle">
						<input type="checkbox" class="fcp-ledenlijst-toggle-address" />
						<?php esc_html_e( 'Adres (straat, postcode, plaats)', 'fotoclubperspectief' ); ?>
					</label>
					<?php if ( $show_contact ) : ?>
						<label class="fcp-ledenlijst-toggle">
							<input type="checkbox" class="fcp-ledenlijst-toggle-phone" />
							<?php esc_html_e( 'Telefoon', 'fotoclubperspectief' ); ?>
						</label>
						<label class="fcp-ledenlijst-toggle">
							<input type="checkbox" class="fcp-ledenlijst-toggle-email" />
							<?php esc_html_e( 'E-mail', 'fotoclubperspectief' ); ?>
						</label>
					<?php endif; ?>
					<?php if ( isset( $columns['lidnr_fotobond'] ) ) : ?>
						<label class="fcp-ledenlijst-toggle">
							<input type="checkbox" class="fcp-ledenlijst-toggle-lidnr" />
							<?php esc_html_e( 'Lidnr fotobond', 'fotoclubperspectief' ); ?>
						</label>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $bool_filter_columns ) ) : ?>
					<div class="fcp-ledenlijst-toolbar-section fcp-ledenlijst-toolbar-section--filters">
						<span class="fcp-ledenlijst-toolbar-heading"><?php esc_html_e( 'Filter op rol', 'fotoclubperspectief' ); ?></span>
						<div class="fcp-ledenlijst-filter-chips fcp-ledenlijst-filter-chips--exclusive" role="radiogroup" aria-label="<?php echo esc_attr__( 'Filter op rol', 'fotoclubperspectief' ); ?>">
							<label class="fcp-ledenlijst-filter-chip fcp-ledenlijst-filter-chip--neutral">
								<input type="radio" name="<?php echo esc_attr( $role_filter_name ); ?>" class="fcp-ledenlijst-filter-role" value="" checked="checked" />
								<span><?php esc_html_e( 'Alle leden', 'fotoclubperspectief' ); ?></span>
							</label>
							<?php foreach ( $bool_filter_columns as $fkey => $flabel ) : ?>
								<label class="fcp-ledenlijst-filter-chip">
									<input type="radio" name="<?php echo esc_attr( $role_filter_name ); ?>" class="fcp-ledenlijst-filter-role" value="<?php echo esc_attr( $fkey ); ?>" />
									<span><?php echo esc_html( $flabel ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
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
							<th scope="col" class="<?php echo esc_attr( $th_class ); ?>" data-fcp-col="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $members as $m ) {
						$bool_map = self::member_bool_map( (int) $m->ID );
						echo self::render_member_row( (int) $m->ID, $columns, $bool_keys, $bool_map );
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Kolommen die als boolean (vinkje/—) worden weergegeven.
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
	 * Boolean flags per member (alle vaste boolean-kolommen).
	 *
	 * @param int $post_id Member post ID.
	 * @return array<string, bool>
	 */
	private static function member_bool_map( $post_id ) {
		$out = array();
		foreach ( self::bool_column_keys() as $key ) {
			$out[ $key ] = get_post_meta( $post_id, '_fcp_' . $key, true ) === '1';
		}
		return $out;
	}

	/**
	 * One table row.
	 *
	 * @param int              $post_id   Post ID.
	 * @param array            $columns   Columns.
	 * @param string[]         $bool_keys Bool column keys.
	 * @param array<string,bool> $bool_map  Flags for data-fcp-bool-* (row filter).
	 */
	private static function render_member_row( $post_id, $columns, $bool_keys, $bool_map ) {
		$html = '<tr';
		foreach ( self::bool_column_keys() as $bk ) {
			$on = ! empty( $bool_map[ $bk ] );
			$html .= ' data-fcp-bool-' . esc_attr( $bk ) . '="' . ( $on ? '1' : '0' ) . '"';
		}
		$html .= '>';
		foreach ( $columns as $key => $label ) {
			$td_class = 'fcp-ledenlijst-col fcp-ledenlijst-col--text';
			if ( in_array( $key, $bool_keys, true ) ) {
				$td_class = 'fcp-ledenlijst-col fcp-ledenlijst-col--bool';
			}
			$html .= '<td class="' . esc_attr( $td_class ) . '" data-fcp-col="' . esc_attr( $key ) . '">' . self::cell_value( $post_id, $key ) . '</td>';
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
			if ( $v === '1' ) {
				return '<span class="fcp-ledenlijst-bool-check" role="img" aria-label="' . esc_attr__( 'Ja', 'fotoclubperspectief' ) . '">' . esc_html( "\u{2713}" ) . '</span>';
			}
			return '—';
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
