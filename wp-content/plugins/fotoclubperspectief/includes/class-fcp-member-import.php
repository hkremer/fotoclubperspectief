<?php
/**
 * CSV-import voor leden (CPT fcp_member).
 *
 * @package FotoclubPerspectief
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class FCP_Member_Import
 */
class FCP_Member_Import {

	/**
	 * Importeer leden uit een CSV-bestand (puntkomma of komma als scheidingsteken).
	 *
	 * @param string $file_path Pad naar bestand.
	 * @param array  $args {
	 *     @type bool $update_existing Bestaande leden bijwerken op lidnr of e-mail.
	 *     @type bool $dry_run         Alleen tellen, geen database wijzigingen.
	 * }
	 * @return array{ created: int, updated: int, skipped: int, errors: string[] }
	 */
	public static function import_file( $file_path, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'update_existing' => true,
				'dry_run'         => false,
			)
		);

		$result = array(
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'errors'  => array(),
		);

		if ( ! is_readable( $file_path ) ) {
			$result['errors'][] = __( 'Bestand is niet leesbaar.', 'fotoclubperspectief' );
			return $result;
		}

		$handle = fopen( $file_path, 'rb' );
		if ( false === $handle ) {
			$result['errors'][] = __( 'Kon bestand niet openen.', 'fotoclubperspectief' );
			return $result;
		}

		$first = fgets( $handle );
		if ( false === $first ) {
			fclose( $handle );
			$result['errors'][] = __( 'Bestand is leeg.', 'fotoclubperspectief' );
			return $result;
		}

		$delimiter = self::detect_delimiter( $first );
		rewind( $handle );
		$bom = fread( $handle, 3 );
		if ( "\xEF\xBB\xBF" !== $bom ) {
			rewind( $handle );
		}

		$header_row = fgetcsv( $handle, 0, $delimiter );
		if ( empty( $header_row ) ) {
			fclose( $handle );
			$result['errors'][] = __( 'Geen kopregel gevonden.', 'fotoclubperspectief' );
			return $result;
		}

		$map = self::build_column_map( $header_row );
		// Gebruik isset, niet empty(): kolomindex 0 is geldig maar empty(0) is true in PHP.
		if ( ! isset( $map['voornaam'], $map['achternaam'] ) ) {
			fclose( $handle );
			$result['errors'][] = __( 'CSV moet minimaal kolommen Voornaam en Naam (achternaam) hebben.', 'fotoclubperspectief' );
			return $result;
		}

		$line = 1;
		while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
			++$line;
			if ( self::row_is_empty( $row ) ) {
				continue;
			}

			$data = self::row_to_member_data( $row, $map );
			if ( '' === $data['voornaam'] && '' === $data['achternaam'] ) {
				$result['skipped']++;
				continue;
			}

			$r = self::upsert_member( $data, $args );
			if ( is_wp_error( $r ) ) {
				$result['errors'][] = sprintf(
					/* translators: 1: line number, 2: error message */
					__( 'Regel %1$d: %2$s', 'fotoclubperspectief' ),
					$line,
					$r->get_error_message()
				);
				continue;
			}

			if ( 'created' === $r['action'] ) {
				$result['created']++;
			} elseif ( 'updated' === $r['action'] ) {
				$result['updated']++;
			} else {
				$result['skipped']++;
			}
		}

		fclose( $handle );
		return $result;
	}

	/**
	 * @param string $first_line Eerste regel van het bestand.
	 * @return string
	 */
	private static function detect_delimiter( $first_line ) {
		$semi = substr_count( $first_line, ';' );
		$comma = substr_count( $first_line, ',' );
		return $semi >= $comma ? ';' : ',';
	}

	/**
	 * @param array<int,string> $header_row Raw header cells.
	 * @return array<string,int> field key => column index.
	 */
	private static function build_column_map( $header_row ) {
		$map = array();
		foreach ( $header_row as $i => $cell ) {
			$key = self::header_to_field_key( $cell );
			if ( $key ) {
				$map[ $key ] = $i;
			}
		}
		return $map;
	}

	/**
	 * Map normalized header to internal key.
	 *
	 * @param string $header Header cell.
	 * @return string|null
	 */
	private static function header_to_field_key( $header ) {
		$h = self::normalize_header_label( $header );
		if ( '' === $h ) {
			return null;
		}

		$exact = array(
			'voornaam'                 => 'voornaam',
			'naam'                     => 'achternaam',
			'achternaam'               => 'achternaam',
			'lidnr fotobond'           => 'lidnr_fotobond',
			'lidnr'                    => 'lidnr_fotobond',
			'bar'                      => 'bar',
			'adres'                    => 'adres',
			'pc'                       => 'postcode',
			'postcode'                 => 'postcode',
			'plaats'                   => 'plaats',
			'telefoon'                 => 'telefoon',
			'email'                    => 'email',
			'e-mail'                   => 'email',
			'bestuur'                  => 'fcp_bestuur',
			'programma'                => 'fcp_programma_cie',
			'tentoonstelling'          => 'fcp_tentoonstelling_cie',
			'wedstrijden'              => 'fcp_wedstrijden_cie',
			"archief foto's"           => 'fcp_archief_foto_cie',
			'archief fotos'            => 'fcp_archief_foto_cie',
			'archief foto'             => 'fcp_archief_foto_cie',
			'website cie'              => 'fcp_website_cie',
			'redactie perspectiefje'   => 'fcp_redactie_cie',
			'redactie'                 => 'fcp_redactie_cie',
			'natuur werkgr'            => 'fcp_natuur_werkgroep',
			'portret werkgr'           => 'fcp_portret_werkgroep',
			'straat werkgr'            => 'fcp_straat_werkgroep',
			'architectuur'             => 'fcp_architectuur_werkgroep',
			'laptop bedienen'          => 'fcp_laptop_bediening',
		);

		if ( isset( $exact[ $h ] ) ) {
			return $exact[ $h ];
		}

		if ( 0 === strpos( $h, 'lidnr' ) && false !== strpos( $h, 'fotobond' ) ) {
			return 'lidnr_fotobond';
		}

		return null;
	}

	/**
	 * @param string $header Raw header.
	 * @return string Normalized slug-like string.
	 */
	private static function normalize_header_label( $header ) {
		$s = trim( wp_strip_all_tags( (string) $header ) );
		$s = preg_replace( '/^\xEF\xBB\xBF/', '', $s );
		$s = str_replace( array( "\xC2\xA0" ), ' ', $s );
		$s = preg_replace( '/[*]+$/u', '', $s );
		$s = strtolower( trim( $s ) );
		$s = preg_replace( '/\s+/u', ' ', $s );
		return $s;
	}

	/**
	 * @param array<int,string|null> $row CSV row.
	 * @return bool
	 */
	private static function row_is_empty( $row ) {
		foreach ( $row as $cell ) {
			if ( null !== $cell && '' !== trim( (string) $cell ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Build member data array from row + map.
	 *
	 * @param array<int,string|null> $row  Row.
	 * @param array<string,int>      $map  Field => index.
	 * @return array<string,mixed>
	 */
	private static function row_to_member_data( $row, $map ) {
		$get = function ( $key ) use ( $row, $map ) {
			if ( ! isset( $map[ $key ] ) ) {
				return '';
			}
			$i = $map[ $key ];
			return isset( $row[ $i ] ) ? (string) $row[ $i ] : '';
		};

		$voornaam   = sanitize_text_field( $get( 'voornaam' ) );
		$achternaam = sanitize_text_field( $get( 'achternaam' ) );
		$lidnr      = sanitize_text_field( trim( $get( 'lidnr_fotobond' ) ) );
		$email_raw  = $get( 'email' );
		$email      = self::first_email_from_cell( $email_raw );

		$data = array(
			'voornaam'              => $voornaam,
			'achternaam'            => $achternaam,
			'lidnr_fotobond'        => $lidnr,
			'bar'                   => self::parse_bool_cell( $get( 'bar' ) ),
			'adres'                 => sanitize_text_field( $get( 'adres' ) ),
			'postcode'              => sanitize_text_field( trim( $get( 'postcode' ) ) ),
			'plaats'                => sanitize_text_field( $get( 'plaats' ) ),
			'telefoon'              => sanitize_text_field( trim( $get( 'telefoon' ) ) ),
			'email'                 => $email,
			'fcp_bestuur'           => self::parse_bool_cell( $get( 'fcp_bestuur' ) ),
			'fcp_programma_cie'     => self::parse_bool_cell( $get( 'fcp_programma_cie' ) ),
			'fcp_tentoonstelling_cie' => self::parse_bool_cell( $get( 'fcp_tentoonstelling_cie' ) ),
			'fcp_wedstrijden_cie'   => self::parse_bool_cell( $get( 'fcp_wedstrijden_cie' ) ),
			'fcp_archief_foto_cie'  => self::parse_bool_cell( $get( 'fcp_archief_foto_cie' ) ),
			'fcp_website_cie'       => self::parse_bool_cell( $get( 'fcp_website_cie' ) ),
			'fcp_redactie_cie'      => self::parse_bool_cell( $get( 'fcp_redactie_cie' ) ),
			'fcp_natuur_werkgroep'  => self::parse_bool_cell( $get( 'fcp_natuur_werkgroep' ) ),
			'fcp_portret_werkgroep' => self::parse_bool_cell( $get( 'fcp_portret_werkgroep' ) ),
			'fcp_straat_werkgroep'  => self::parse_bool_cell( $get( 'fcp_straat_werkgroep' ) ),
			'fcp_architectuur_werkgroep' => self::parse_bool_cell( $get( 'fcp_architectuur_werkgroep' ) ),
			'fcp_laptop_bediening'  => self::parse_bool_cell( $get( 'fcp_laptop_bediening' ) ),
		);

		return $data;
	}

	/**
	 * Eerste geldige e-mail uit een cel (meerdere adressen of rommel mogelijk).
	 *
	 * @param string $cell Raw.
	 * @return string
	 */
	public static function first_email_from_cell( $cell ) {
		$cell = trim( (string) $cell );
		if ( '' === $cell ) {
			return '';
		}
		if ( preg_match_all( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/u', $cell, $m ) && ! empty( $m[0] ) ) {
			foreach ( $m[0] as $addr ) {
				$e = sanitize_email( $addr );
				if ( $e && is_email( $e ) ) {
					return $e;
				}
			}
		}
		$one = sanitize_email( $cell );
		return ( $one && is_email( $one ) ) ? $one : '';
	}

	/**
	 * @param string $cell Raw cell.
	 * @return bool
	 */
	public static function parse_bool_cell( $cell ) {
		$s = strtolower( trim( wp_strip_all_tags( (string) $cell ) ) );
		if ( '' === $s ) {
			return false;
		}
		if ( in_array( $s, array( '1', 'x', 'j', 'ja', 'y', 'yes', 'v', 'bar' ), true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Upsert één lid.
	 *
	 * @param array<string,mixed> $data Member data.
	 * @param array               $args Import args.
	 * @return array{ action: string, post_id: int }|WP_Error
	 */
	private static function upsert_member( $data, $args ) {
		$post_id = 0;
		if ( ! empty( $args['update_existing'] ) ) {
			$post_id = self::find_existing_member_id( $data );
		}

		if ( $args['dry_run'] ) {
			if ( $post_id ) {
				return array( 'action' => 'updated', 'post_id' => $post_id );
			}
			if ( '' === trim( $data['voornaam'] . $data['achternaam'] ) ) {
				return array( 'action' => 'skipped', 'post_id' => 0 );
			}
			return array( 'action' => 'created', 'post_id' => 0 );
		}

		$title = trim( $data['voornaam'] . ' ' . $data['achternaam'] );
		if ( '' === $title ) {
			$title = __( '(Naamloos lid)', 'fotoclubperspectief' );
		}

		if ( $post_id ) {
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_title'  => $title,
					'post_status' => 'publish',
				)
			);
			self::write_member_meta( $post_id, $data );
			return array( 'action' => 'updated', 'post_id' => $post_id );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'   => FCP_Member::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $title,
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			return $new_id;
		}

		self::write_member_meta( (int) $new_id, $data );
		return array( 'action' => 'created', 'post_id' => (int) $new_id );
	}

	/**
	 * Zoek bestaand lid op lidnr, anders e-mail, anders naam.
	 *
	 * @param array<string,mixed> $data Data.
	 * @return int Post ID of 0.
	 */
	private static function find_existing_member_id( $data ) {
		global $wpdb;

		if ( ! empty( $data['lidnr_fotobond'] ) ) {
			$pid = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
					'_fcp_lidnr_fotobond',
					$data['lidnr_fotobond']
				)
			);
			if ( $pid ) {
				return (int) $pid;
			}
		}

		if ( ! empty( $data['email'] ) ) {
			$pid = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
					'_fcp_email',
					$data['email']
				)
			);
			if ( $pid ) {
				return (int) $pid;
			}
		}

		if ( '' !== $data['voornaam'] || '' !== $data['achternaam'] ) {
			$q = new WP_Query(
				array(
					'post_type'      => FCP_Member::POST_TYPE,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'key'   => '_fcp_voornaam',
							'value' => $data['voornaam'],
						),
						array(
							'key'   => '_fcp_achternaam',
							'value' => $data['achternaam'],
						),
					),
				)
			);
			if ( $q->have_posts() ) {
				return (int) $q->posts[0]->ID;
			}
		}

		return 0;
	}

	/**
	 * @param int                   $post_id Post ID.
	 * @param array<string,mixed> $data    Data.
	 */
	private static function write_member_meta( $post_id, $data ) {
		update_post_meta( $post_id, '_fcp_voornaam', $data['voornaam'] );
		update_post_meta( $post_id, '_fcp_achternaam', $data['achternaam'] );
		update_post_meta( $post_id, '_fcp_lidnr_fotobond', $data['lidnr_fotobond'] );
		update_post_meta( $post_id, '_fcp_bar', ! empty( $data['bar'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_fcp_adres', $data['adres'] );
		update_post_meta( $post_id, '_fcp_postcode', $data['postcode'] );
		update_post_meta( $post_id, '_fcp_plaats', $data['plaats'] );
		update_post_meta( $post_id, '_fcp_telefoon', $data['telefoon'] );
		update_post_meta( $post_id, '_fcp_email', $data['email'] );

		$bool_fields = array(
			'fcp_bestuur',
			'fcp_programma_cie',
			'fcp_tentoonstelling_cie',
			'fcp_wedstrijden_cie',
			'fcp_archief_foto_cie',
			'fcp_website_cie',
			'fcp_redactie_cie',
			'fcp_natuur_werkgroep',
			'fcp_portret_werkgroep',
			'fcp_straat_werkgroep',
			'fcp_architectuur_werkgroep',
			'fcp_laptop_bediening',
		);
		foreach ( $bool_fields as $field ) {
			update_post_meta( $post_id, '_' . $field, ! empty( $data[ $field ] ) ? '1' : '0' );
		}
	}

	/**
	 * Admin: submenu + upload handler.
	 */
	public static function init_admin() {
		add_action( 'admin_menu', array( __CLASS__, 'register_submenu' ), 20 );
		add_action( 'admin_post_fcp_import_members', array( __CLASS__, 'handle_admin_post' ) );
	}

	/**
	 * Submenu onder Leden.
	 */
	public static function register_submenu() {
		add_submenu_page(
			'edit.php?post_type=' . FCP_Member::POST_TYPE,
			__( 'Leden CSV importeren', 'fotoclubperspectief' ),
			__( 'CSV importeren', 'fotoclubperspectief' ),
			'manage_options',
			'fcp-member-import',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Verwerk upload (admin-post.php).
	 */
	public static function handle_admin_post() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Geen rechten.', 'fotoclubperspectief' ) );
		}
		check_admin_referer( 'fcp_import_members', 'fcp_import_nonce' );

		$url = admin_url( 'edit.php?post_type=' . FCP_Member::POST_TYPE . '&page=fcp-member-import' );

		if ( empty( $_FILES['fcp_csv'] ) || ! isset( $_FILES['fcp_csv']['tmp_name'] ) ) {
			wp_safe_redirect( add_query_arg( 'fcp_import', 'no_file', $url ) );
			exit;
		}

		if ( UPLOAD_ERR_OK !== (int) $_FILES['fcp_csv']['error'] ) {
			wp_safe_redirect( add_query_arg( 'fcp_import', 'upload_error', $url ) );
			exit;
		}

		$tmp = sanitize_text_field( wp_unslash( $_FILES['fcp_csv']['tmp_name'] ) );
		$args = array(
			'update_existing' => ! empty( $_POST['fcp_update_existing'] ),
			'dry_run'         => ! empty( $_POST['fcp_dry_run'] ),
		);

		$result = self::import_file( $tmp, $args );
		set_transient( 'fcp_import_result_' . get_current_user_id(), $result, 120 );

		wp_safe_redirect( add_query_arg( 'fcp_import', 'done', $url ) );
		exit;
	}

	/**
	 * Adminpagina.
	 */
	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice = isset( $_GET['fcp_import'] ) ? sanitize_text_field( wp_unslash( $_GET['fcp_import'] ) ) : '';
		$result = get_transient( 'fcp_import_result_' . get_current_user_id() );
		if ( $result && is_array( $result ) ) {
			delete_transient( 'fcp_import_result_' . get_current_user_id() );
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Leden importeren uit CSV', 'fotoclubperspectief' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Scheidingsteken: puntkomma of komma. Eerste regel = kolomkoppen (zoals Voornaam, Naam, …). Bestaande leden worden herkend op lidnr fotobond, anders e-mail, anders voornaam+achternaam.', 'fotoclubperspectief' ); ?>
			</p>

			<?php if ( 'no_file' === $notice ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Geen bestand geüpload.', 'fotoclubperspectief' ); ?></p></div>
			<?php elseif ( 'upload_error' === $notice ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'Upload mislukt.', 'fotoclubperspectief' ); ?></p></div>
			<?php elseif ( 'done' === $notice && ! empty( $result ) && is_array( $result ) ) : ?>
				<div class="notice notice-success">
					<p>
						<?php
						printf(
							/* translators: 1: created count, 2: updated, 3: skipped */
							esc_html__( 'Klaar: %1$d nieuw, %2$d bijgewerkt, %3$d overgeslagen.', 'fotoclubperspectief' ),
							(int) $result['created'],
							(int) $result['updated'],
							(int) $result['skipped']
						);
						?>
					</p>
					<?php if ( ! empty( $result['errors'] ) ) : ?>
						<ul>
							<?php foreach ( $result['errors'] as $err ) : ?>
								<li><?php echo esc_html( $err ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="fcp_import_members" />
				<?php wp_nonce_field( 'fcp_import_members', 'fcp_import_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="fcp_csv"><?php esc_html_e( 'CSV-bestand', 'fotoclubperspectief' ); ?></label></th>
						<td><input type="file" id="fcp_csv" name="fcp_csv" accept=".csv,text/csv" required /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Opties', 'fotoclubperspectief' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="fcp_update_existing" value="1" checked />
								<?php esc_html_e( 'Bestaande leden bijwerken (op lidnr / e-mail / naam)', 'fotoclubperspectief' ); ?>
							</label><br />
							<label>
								<input type="checkbox" name="fcp_dry_run" value="1" />
								<?php esc_html_e( 'Alleen testen (geen database wijzigingen)', 'fotoclubperspectief' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Importeren', 'fotoclubperspectief' ) ); ?>
			</form>
		</div>
		<?php
	}
}
