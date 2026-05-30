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
	 * Login error message after a failed attempt.
	 *
	 * @var string
	 */
	private static $login_error = '';

	/**
	 * Init.
	 */
	public static function init() {
		add_shortcode( 'fcp_ledenlijst', array( __CLASS__, 'ledenlijst' ) );
		add_shortcode( 'fcp_agenda', array( __CLASS__, 'agenda' ) );
		add_shortcode( 'fcp_login', array( __CLASS__, 'login_form' ) );
		add_shortcode( 'fcp_werkgroep', array( __CLASS__, 'werkgroep_leden' ) );
		add_action( 'init', array( __CLASS__, 'maybe_process_login' ), 20 );
		add_action( 'wp', array( __CLASS__, 'maybe_redirect_logged_in_from_login_page' ) );
		add_action( 'wp', array( __CLASS__, 'maybe_mark_ledenlijst_noindex' ) );
	}

	/**
	 * Default redirect URL after login.
	 *
	 * @return string
	 */
	private static function default_login_redirect_url() {
		return (string) home_url( '/info' );
	}

	/**
	 * Sanitize a redirect target from shortcode attributes or POST data.
	 *
	 * @param string $redirect Raw redirect value.
	 * @return string
	 */
	private static function sanitize_login_redirect( $redirect ) {
		$redirect = trim( (string) $redirect );
		if ( '' === $redirect ) {
			return self::default_login_redirect_url();
		}

		if ( 0 !== strpos( $redirect, 'http' ) ) {
			$redirect = home_url( $redirect );
		}

		$validated = wp_validate_redirect( $redirect, self::default_login_redirect_url() );
		return $validated ? $validated : self::default_login_redirect_url();
	}

	/**
	 * Redirect logged-in users away from pages that only show the login form.
	 */
	public static function maybe_redirect_logged_in_from_login_page() {
		if ( ! is_user_logged_in() || ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! ( $post instanceof WP_Post ) || ! has_shortcode( (string) $post->post_content, 'fcp_login' ) ) {
			return;
		}

		wp_safe_redirect( self::default_login_redirect_url() );
		exit;
	}

	/**
	 * Process login form submission.
	 */
	public static function maybe_process_login() {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		if ( empty( $_POST['fcp_login'] ) || empty( $_POST['fcp_login_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fcp_login_nonce'] ) ), 'fcp_login' ) ) {
			self::$login_error = __( 'Beveiligingscontrole mislukt. Probeer het opnieuw.', 'fotoclubperspectief' );
			return;
		}

		if ( is_user_logged_in() ) {
			wp_safe_redirect( self::default_login_redirect_url() );
			exit;
		}

		// Honeypot: bots that fill hidden fields are rejected silently.
		if ( ! empty( $_POST['fcp_login_url'] ) ) {
			return;
		}

		$username = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '';
		$password = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '';
		$remember = ! empty( $_POST['rememberme'] );

		if ( '' === $username || '' === $password ) {
			self::$login_error = __( 'Vul gebruikersnaam en wachtwoord in.', 'fotoclubperspectief' );
			return;
		}

		$redirect = self::sanitize_login_redirect(
			isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : ''
		);

		$user = wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $remember,
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			self::$login_error = wp_strip_all_tags( $user->get_error_message() );
			return;
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Shortcode [fcp_login redirect="/info"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function login_form( $atts ) {
		if ( is_user_logged_in() ) {
			return '<p class="fcp-login-logged-in">' . esc_html__( 'Je bent al ingelogd.', 'fotoclubperspectief' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'redirect' => '/info',
			),
			$atts,
			'fcp_login'
		);

		$redirect = self::sanitize_login_redirect( $atts['redirect'] );
		$error    = self::$login_error;

		ob_start();
		?>
		<form class="fcp-login-form" method="post" action="<?php echo esc_url( get_permalink() ); ?>" novalidate>
			<?php if ( $error ) : ?>
				<p class="fcp-login-error" role="alert"><?php echo esc_html( $error ); ?></p>
			<?php endif; ?>

			<p class="fcp-login-field">
				<label for="fcp-login-user"><?php esc_html_e( 'Gebruikersnaam of e-mailadres', 'fotoclubperspectief' ); ?></label>
				<input type="text" name="log" id="fcp-login-user" class="fcp-login-input" autocomplete="username" required />
			</p>

			<p class="fcp-login-field">
				<label for="fcp-login-pass"><?php esc_html_e( 'Wachtwoord', 'fotoclubperspectief' ); ?></label>
				<input type="password" name="pwd" id="fcp-login-pass" class="fcp-login-input" autocomplete="current-password" required />
			</p>

			<p class="fcp-login-field fcp-login-field--remember">
				<label class="fcp-login-remember">
					<input type="checkbox" name="rememberme" value="forever" />
					<?php esc_html_e( 'Onthoud mij', 'fotoclubperspectief' ); ?>
				</label>
			</p>

			<p class="fcp-login-field fcp-login-field--honeypot" aria-hidden="true">
				<label for="fcp-login-url"><?php esc_html_e( 'Laat dit veld leeg', 'fotoclubperspectief' ); ?></label>
				<input type="text" name="fcp_login_url" id="fcp-login-url" class="fcp-login-input" tabindex="-1" autocomplete="off" />
			</p>

			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect ); ?>" />
			<input type="hidden" name="fcp_login" value="1" />
			<?php wp_nonce_field( 'fcp_login', 'fcp_login_nonce' ); ?>

			<p class="fcp-login-actions">
				<button type="submit" class="fcp-login-submit button"><?php esc_html_e( 'Inloggen', 'fotoclubperspectief' ); ?></button>
			</p>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Shortcode [fcp_werkgroep groep="natuur" sort="achternaam"]
	 *
	 * Toont voornaam + achternaam van leden in een werkgroep.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function werkgroep_leden( $atts ) {
		if ( ! class_exists( 'FCP_Member' ) ) {
			return '<p class="fcp-werkgroep-missing">' . esc_html__( 'Activeer de plugin Fotoclub Perspectief.', 'fotoclubperspectief' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'groep' => '',
				'sort'  => 'achternaam',
			),
			$atts,
			'fcp_werkgroep'
		);

		$field = FCP_Member::resolve_werkgroep_field( $atts['groep'] );
		if ( '' === $field ) {
			return '<p class="fcp-werkgroep-error">' . esc_html__( 'Onbekende werkgroep. Gebruik bijvoorbeeld: [fcp_werkgroep groep="natuur"]', 'fotoclubperspectief' ) . '</p>';
		}

		$sort_by = ( 'voornaam' === strtolower( (string) $atts['sort'] ) ) ? 'voornaam' : 'achternaam';
		$members = FCP_Member::get_members_by_group( $field, $sort_by );

		if ( empty( $members ) ) {
			return '<p class="fcp-werkgroep-empty">' . esc_html__( 'Geen leden gevonden in deze werkgroep.', 'fotoclubperspectief' ) . '</p>';
		}

		$labels = FCP_Member::werkgroep_labels();
		$label  = isset( $labels[ $field ] ) ? $labels[ $field ] : '';

		ob_start();
		?>
		<div class="fcp-werkgroep-leden" data-fcp-werkgroep="<?php echo esc_attr( preg_replace( '/^fcp_/', '', $field ) ); ?>">
			<?php if ( $label ) : ?>
				<p class="fcp-werkgroep-leden-heading screen-reader-text"><?php echo esc_html( $label ); ?></p>
			<?php endif; ?>
			<ul class="fcp-werkgroep-leden-list">
				<?php
				foreach ( $members as $member ) {
					$voornaam   = trim( (string) get_post_meta( $member->ID, '_fcp_voornaam', true ) );
					$achternaam = trim( (string) get_post_meta( $member->ID, '_fcp_achternaam', true ) );
					$name       = trim( $voornaam . ' ' . $achternaam );
					if ( '' === $name ) {
						$name = get_the_title( $member );
					}
					if ( '' === $name ) {
						continue;
					}
					echo '<li>' . esc_html( $name ) . '</li>';
				}
				?>
			</ul>
		</div>
		<?php
		return (string) ob_get_clean();
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
			'voornaam'       => __( 'Voornaam', 'fotoclubperspectief' ),
			'achternaam'     => __( 'Achternaam', 'fotoclubperspectief' ),
			'lidnr_fotobond' => __( 'Lidnr fotobond', 'fotoclubperspectief' ),
			'bar'            => __( 'Bar', 'fotoclubperspectief' ),
			'adres'          => __( 'Adres', 'fotoclubperspectief' ),
			'postcode'       => __( 'Postcode', 'fotoclubperspectief' ),
			'plaats'         => __( 'Plaats', 'fotoclubperspectief' ),
			'telefoon'       => __( 'Telefoon', 'fotoclubperspectief' ),
			'email'          => __( 'E-mail', 'fotoclubperspectief' ),
		);
		foreach ( FCP_Member::commissie_werkgroep_labels() as $fcp_id => $label ) {
			$sk             = preg_replace( '/^fcp_/', '', $fcp_id );
			$columns[ $sk ] = $label;
		}

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
		$filter_keys         = self::bool_filter_column_keys();
		foreach ( $columns as $key => $label ) {
			if ( in_array( $key, $filter_keys, true ) ) {
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
	 * Boolean kolommen (vinkje, T voor trekker, of —).
	 *
	 * @return string[]
	 */
	private static function bool_column_keys() {
		$out = array( 'bar' );
		foreach ( FCP_Member::commissie_werkgroep_field_names() as $f ) {
			$out[] = preg_replace( '/^fcp_/', '', $f );
		}
		return $out;
	}

	/**
	 * Alleen lidmaatschap (geen trekker): gebruikt voor rij-filter “Filter op rol”.
	 *
	 * @return string[]
	 */
	private static function bool_filter_column_keys() {
		$out = array( 'bar' );
		foreach ( FCP_Member::commissie_werkgroep_field_names() as $f ) {
			$out[] = preg_replace( '/^fcp_/', '', $f );
		}
		return $out;
	}

	/**
	 * Boolean flags per member (alle vaste boolean-kolommen).
	 *
	 * @param int $post_id Member post ID.
	 * @return array<string, bool>
	 */
	private static function member_bool_map( $post_id ) {
		$out = array();
		foreach ( self::bool_filter_column_keys() as $key ) {
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
		foreach ( self::bool_filter_column_keys() as $bk ) {
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
		if ( 'bar' === $key ) {
			$v = get_post_meta( $post_id, '_fcp_bar', true );
			if ( $v === '1' ) {
				return '<span class="fcp-ledenlijst-bool-check" role="img" aria-label="' . esc_attr__( 'Ja', 'fotoclubperspectief' ) . '">' . esc_html( "\u{2713}" ) . '</span>';
			}
			return '—';
		}

		$commissie_short = array();
		foreach ( FCP_Member::commissie_werkgroep_field_names() as $f ) {
			$commissie_short[] = preg_replace( '/^fcp_/', '', $f );
		}
		if ( in_array( $key, $commissie_short, true ) ) {
			$trek = get_post_meta( $post_id, '_fcp_' . $key . '_trekker', true ) === '1';
			$lid  = get_post_meta( $post_id, '_fcp_' . $key, true ) === '1';
			if ( $trek ) {
				return '<span class="fcp-ledenlijst-bool-trekker" role="img" aria-label="' . esc_attr__( 'Trekker', 'fotoclubperspectief' ) . '">T</span>';
			}
			if ( $lid ) {
				return '<span class="fcp-ledenlijst-bool-check" role="img" aria-label="' . esc_attr__( 'Lid', 'fotoclubperspectief' ) . '">' . esc_html( "\u{2713}" ) . '</span>';
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
