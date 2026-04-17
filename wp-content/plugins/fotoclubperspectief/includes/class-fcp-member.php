<?php
/**
 * Leden (CPT fcp_member).
 *
 * @package FotoclubPerspectief
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class FCP_Member
 */
class FCP_Member {

	const POST_TYPE = 'fcp_member';

	/**
	 * Meta field definitions: key => [ type string|bool ]
	 *
	 * @var array<string, string>
	 */
	public static function meta_keys() {
		$commissie = self::commissie_werkgroep_field_names();
		$trekker   = array();
		foreach ( $commissie as $field ) {
			$trekker[ '_' . $field . '_trekker' ] = 'bool';
		}
		return array_merge(
			array(
				'_fcp_voornaam'              => 'string',
				'_fcp_achternaam'            => 'string',
				'_fcp_lidnr_fotobond'        => 'string',
				'_fcp_bar'                   => 'bool',
				'_fcp_adres'                 => 'string',
				'_fcp_postcode'              => 'string',
				'_fcp_plaats'                => 'string',
				'_fcp_telefoon'              => 'string',
				'_fcp_email'                 => 'string',
				'_fcp_bestuur'               => 'bool',
				'_fcp_programma_cie'         => 'bool',
				'_fcp_tentoonstelling_cie'   => 'bool',
				'_fcp_wedstrijden_cie'       => 'bool',
				'_fcp_archief_foto_cie'      => 'bool',
				'_fcp_website_cie'           => 'bool',
				'_fcp_redactie_cie'          => 'bool',
				'_fcp_natuur_werkgroep'      => 'bool',
				'_fcp_portret_werkgroep'     => 'bool',
				'_fcp_straat_werkgroep'      => 'bool',
				'_fcp_architectuur_werkgroep' => 'bool',
				'_fcp_laptop_bediening'      => 'bool',
			),
			$trekker
		);
	}

	/**
	 * Commissie- en werkgroep-velden (boolean lidmaatschap), zonder bar.
	 *
	 * @return string[] POST/meta basenamen, bv. fcp_bestuur.
	 */
	public static function commissie_werkgroep_field_names() {
		return array(
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
	}

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_post' ), 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'posts_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'posts_custom_column' ), 10, 2 );
		add_filter( 'manage_edit-' . self::POST_TYPE . '_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'admin_sort_list' ) );
		add_filter( 'default_hidden_columns', array( __CLASS__, 'default_hidden_list_columns' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_list_styles' ) );
	}

	/**
	 * Styling voor T / vinkje in het leden-overzicht (wp-admin).
	 *
	 * @param string $hook_suffix Huidig scherm.
	 */
	public static function enqueue_admin_list_styles( $hook_suffix ) {
		if ( 'edit.php' !== $hook_suffix ) {
			return;
		}
		if ( ! isset( $_GET['post_type'] ) || self::POST_TYPE !== sanitize_key( wp_unslash( $_GET['post_type'] ) ) ) {
			return;
		}
		wp_register_style( 'fcp-member-admin-list', false, array(), '1.0' );
		wp_enqueue_style( 'fcp-member-admin-list' );
		wp_add_inline_style(
			'fcp-member-admin-list',
			'.fcp-member-bool-t{color:#1d8f3a;font-weight:700;font-size:1.08em}.fcp-member-bool-ok{color:#1d8f3a;font-weight:700;font-size:1.08em}'
		);
	}

	/**
	 * Standaard alle extra leden-kolommen en de datumkolom verborgen; titel blijft zichtbaar.
	 * Geldt voor gebruikers die nog geen kolomvoorkeur hebben opgeslagen.
	 *
	 * @param string[]  $hidden Standaard verborgen kolommen.
	 * @param WP_Screen $screen Huidig scherm.
	 * @return string[]
	 */
	public static function default_hidden_list_columns( $hidden, $screen ) {
		if ( ! $screen instanceof WP_Screen || 'edit-' . self::POST_TYPE !== $screen->id ) {
			return $hidden;
		}
		$hide = array_merge( array_keys( self::admin_list_columns() ), array( 'date' ) );
		return array_merge( (array) $hidden, $hide );
	}

	/**
	 * Kolommen voor de ledenlijst (behalve titel).
	 *
	 * @return array<string,string> Kolom-ID => label.
	 */
	private static function admin_list_columns() {
		$cols = array(
			'fcp_voornaam'   => __( 'Voornaam', 'fotoclubperspectief' ),
			'fcp_achternaam' => __( 'Achternaam', 'fotoclubperspectief' ),
			'fcp_lidnr'      => __( 'Lidnr fotobond', 'fotoclubperspectief' ),
			'fcp_bar'        => __( 'Bar', 'fotoclubperspectief' ),
			'fcp_adres'      => __( 'Adres', 'fotoclubperspectief' ),
			'fcp_postcode'   => __( 'Postcode', 'fotoclubperspectief' ),
			'fcp_plaats'     => __( 'Plaats', 'fotoclubperspectief' ),
			'fcp_telefoon'   => __( 'Telefoon', 'fotoclubperspectief' ),
			'fcp_email'      => __( 'E-mail', 'fotoclubperspectief' ),
		);
		foreach ( self::commissie_werkgroep_labels() as $id => $label ) {
			$cols[ $id ] = $label;
		}
		return $cols;
	}

	/**
	 * Commissie/werkgroep: kolom-id (fcp_…) => korte label.
	 *
	 * @return array<string,string>
	 */
	public static function commissie_werkgroep_labels() {
		return array(
			'fcp_bestuur'                => __( 'Bestuur', 'fotoclubperspectief' ),
			'fcp_programma_cie'          => __( 'Programma cie', 'fotoclubperspectief' ),
			'fcp_tentoonstelling_cie'    => __( 'Tentoonstelling cie', 'fotoclubperspectief' ),
			'fcp_wedstrijden_cie'        => __( 'Wedstrijden cie', 'fotoclubperspectief' ),
			'fcp_archief_foto_cie'       => __( 'Archief foto cie', 'fotoclubperspectief' ),
			'fcp_website_cie'            => __( 'Website cie', 'fotoclubperspectief' ),
			'fcp_redactie_cie'           => __( 'Redactie cie', 'fotoclubperspectief' ),
			'fcp_natuur_werkgroep'       => __( 'Natuur werkgroep', 'fotoclubperspectief' ),
			'fcp_portret_werkgroep'      => __( 'Portret werkgroep', 'fotoclubperspectief' ),
			'fcp_straat_werkgroep'       => __( 'Straat werkgroep', 'fotoclubperspectief' ),
			'fcp_architectuur_werkgroep' => __( 'Architectuur werkgroep', 'fotoclubperspectief' ),
			'fcp_laptop_bediening'       => __( 'Laptop bediening', 'fotoclubperspectief' ),
		);
	}

	/**
	 * Admin ledenlijst: standaard op titel A–Z; bij klik op een sorteerbare kolom op meta sorteren.
	 *
	 * @param WP_Query $query Query.
	 */
	public static function admin_sort_list( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );
		if ( ! $orderby ) {
			$query->set( 'orderby', 'title' );
			$query->set( 'order', 'ASC' );
			return;
		}

		$map = array(
			'fcp_voornaam'   => '_fcp_voornaam',
			'fcp_achternaam' => '_fcp_achternaam',
			'fcp_lidnr'      => '_fcp_lidnr_fotobond',
			'fcp_plaats'     => '_fcp_plaats',
			'fcp_email'      => '_fcp_email',
		);
		if ( isset( $map[ $orderby ] ) ) {
			$query->set( 'meta_key', $map[ $orderby ] );
			$query->set( 'orderby', 'meta_value' );
		}
	}

	/**
	 * Register CPT.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Leden', 'fotoclubperspectief' ),
			'singular_name'      => __( 'Lid', 'fotoclubperspectief' ),
			'add_new'            => __( 'Nieuw lid', 'fotoclubperspectief' ),
			'add_new_item'       => __( 'Nieuw lid toevoegen', 'fotoclubperspectief' ),
			'edit_item'          => __( 'Lid bewerken', 'fotoclubperspectief' ),
			'new_item'           => __( 'Nieuw lid', 'fotoclubperspectief' ),
			'view_item'          => __( 'Lid bekijken', 'fotoclubperspectief' ),
			'search_items'       => __( 'Leden zoeken', 'fotoclubperspectief' ),
			'not_found'          => __( 'Geen leden gevonden', 'fotoclubperspectief' ),
			'not_found_in_trash' => __( 'Geen leden in prullenbak', 'fotoclubperspectief' ),
			'menu_name'          => __( 'Leden', 'fotoclubperspectief' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-groups',
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'hierarchical'        => false,
				'supports'            => array( 'title' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'exclude_from_search' => true,
			)
		);
	}

	/**
	 * Meta boxes.
	 */
	public static function add_meta_boxes() {
		add_meta_box(
			'fcp_member_details',
			__( 'Gegevens lid', 'fotoclubperspectief' ),
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render admin form.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'fcp_member_save', 'fcp_member_nonce' );

		$keys = self::meta_keys();
		?>
		<table class="form-table fcp-member-fields">
			<tbody>
				<tr>
					<th><label for="fcp_voornaam"><?php esc_html_e( 'Voornaam', 'fotoclubperspectief' ); ?></label></th>
					<td><input type="text" class="regular-text" id="fcp_voornaam" name="fcp_voornaam" value="<?php echo esc_attr( get_post_meta( $post->ID, '_fcp_voornaam', true ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="fcp_achternaam"><?php esc_html_e( 'Achternaam', 'fotoclubperspectief' ); ?></label></th>
					<td><input type="text" class="regular-text" id="fcp_achternaam" name="fcp_achternaam" value="<?php echo esc_attr( get_post_meta( $post->ID, '_fcp_achternaam', true ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="fcp_lidnr_fotobond"><?php esc_html_e( 'Lidnr fotobond', 'fotoclubperspectief' ); ?></label></th>
					<td><input type="text" class="regular-text" id="fcp_lidnr_fotobond" name="fcp_lidnr_fotobond" value="<?php echo esc_attr( get_post_meta( $post->ID, '_fcp_lidnr_fotobond', true ) ); ?>" /></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Bar', 'fotoclubperspectief' ); ?></th>
					<td><label><input type="checkbox" name="fcp_bar" value="1" <?php checked( get_post_meta( $post->ID, '_fcp_bar', true ), '1' ); ?> /> <?php esc_html_e( 'Ja', 'fotoclubperspectief' ); ?></label></td>
				</tr>
				<tr>
					<th><label for="fcp_adres"><?php esc_html_e( 'Adres', 'fotoclubperspectief' ); ?></label></th>
					<td><input type="text" class="large-text" id="fcp_adres" name="fcp_adres" value="<?php echo esc_attr( get_post_meta( $post->ID, '_fcp_adres', true ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="fcp_postcode"><?php esc_html_e( 'Postcode', 'fotoclubperspectief' ); ?></label></th>
					<td><input type="text" class="regular-text" id="fcp_postcode" name="fcp_postcode" value="<?php echo esc_attr( get_post_meta( $post->ID, '_fcp_postcode', true ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="fcp_plaats"><?php esc_html_e( 'Plaats', 'fotoclubperspectief' ); ?></label></th>
					<td><input type="text" class="regular-text" id="fcp_plaats" name="fcp_plaats" value="<?php echo esc_attr( get_post_meta( $post->ID, '_fcp_plaats', true ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="fcp_telefoon"><?php esc_html_e( 'Telefoon', 'fotoclubperspectief' ); ?></label></th>
					<td><input type="text" class="regular-text" id="fcp_telefoon" name="fcp_telefoon" value="<?php echo esc_attr( get_post_meta( $post->ID, '_fcp_telefoon', true ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="fcp_email"><?php esc_html_e( 'E-mail', 'fotoclubperspectief' ); ?></label></th>
					<td><input type="email" class="regular-text" id="fcp_email" name="fcp_email" value="<?php echo esc_attr( get_post_meta( $post->ID, '_fcp_email', true ) ); ?>" /></td>
				</tr>
			</tbody>
		</table>
		<h4><?php esc_html_e( 'Commissies en werkgroepen', 'fotoclubperspectief' ); ?></h4>
		<p class="description"><?php esc_html_e( 'Per rij: Lid van de groep, en optioneel Trekker (alleen zinvol als iemand lid is).', 'fotoclubperspectief' ); ?></p>
		<table class="form-table">
			<tbody>
				<?php
				foreach ( self::commissie_werkgroep_labels() as $name => $label ) {
					$key_lid = '_' . $name;
					$key_tr  = '_' . $name . '_trekker';
					?>
					<tr>
						<th><?php echo esc_html( $label ); ?></th>
						<td>
							<p class="fcp-member-role-checks">
								<label>
									<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( get_post_meta( $post->ID, $key_lid, true ), '1' ); ?> />
									<?php esc_html_e( 'Lid', 'fotoclubperspectief' ); ?>
								</label><br />
								<label>
									<input type="checkbox" name="<?php echo esc_attr( $name ); ?>_trekker" value="1" <?php checked( get_post_meta( $post->ID, $key_tr, true ), '1' ); ?> />
									<?php esc_html_e( 'Trekker', 'fotoclubperspectief' ); ?>
								</label>
							</p>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_post( $post_id, $post ) {
		if ( ! isset( $_POST['fcp_member_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fcp_member_nonce'] ) ), 'fcp_member_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$voornaam   = isset( $_POST['fcp_voornaam'] ) ? sanitize_text_field( wp_unslash( $_POST['fcp_voornaam'] ) ) : '';
		$achternaam = isset( $_POST['fcp_achternaam'] ) ? sanitize_text_field( wp_unslash( $_POST['fcp_achternaam'] ) ) : '';

		update_post_meta( $post_id, '_fcp_voornaam', $voornaam );
		update_post_meta( $post_id, '_fcp_achternaam', $achternaam );
		update_post_meta( $post_id, '_fcp_lidnr_fotobond', isset( $_POST['fcp_lidnr_fotobond'] ) ? sanitize_text_field( wp_unslash( $_POST['fcp_lidnr_fotobond'] ) ) : '' );
		update_post_meta( $post_id, '_fcp_bar', ! empty( $_POST['fcp_bar'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_fcp_adres', isset( $_POST['fcp_adres'] ) ? sanitize_text_field( wp_unslash( $_POST['fcp_adres'] ) ) : '' );
		update_post_meta( $post_id, '_fcp_postcode', isset( $_POST['fcp_postcode'] ) ? sanitize_text_field( wp_unslash( $_POST['fcp_postcode'] ) ) : '' );
		update_post_meta( $post_id, '_fcp_plaats', isset( $_POST['fcp_plaats'] ) ? sanitize_text_field( wp_unslash( $_POST['fcp_plaats'] ) ) : '' );
		update_post_meta( $post_id, '_fcp_telefoon', isset( $_POST['fcp_telefoon'] ) ? sanitize_text_field( wp_unslash( $_POST['fcp_telefoon'] ) ) : '' );
		update_post_meta( $post_id, '_fcp_email', isset( $_POST['fcp_email'] ) ? sanitize_email( wp_unslash( $_POST['fcp_email'] ) ) : '' );

		foreach ( self::commissie_werkgroep_field_names() as $field ) {
			$lid = ! empty( $_POST[ $field ] );
			update_post_meta( $post_id, '_' . $field, $lid ? '1' : '0' );
			$trek_field = $field . '_trekker';
			$trek       = $lid && ! empty( $_POST[ $trek_field ] );
			update_post_meta( $post_id, '_' . $trek_field, $trek ? '1' : '0' );
		}

		$title = trim( $voornaam . ' ' . $achternaam );
		if ( '' === $title ) {
			$title = __( '(Naamloos lid)', 'fotoclubperspectief' );
		}
		remove_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_post' ), 10 );
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => $title,
			)
		);
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_post' ), 10, 2 );
	}

	/**
	 * Admin columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function posts_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				foreach ( self::admin_list_columns() as $col_id => $col_label ) {
					$new[ $col_id ] = $col_label;
				}
			}
		}
		return $new;
	}

	/**
	 * Column content.
	 *
	 * @param string $column Column.
	 * @param int    $post_id Post ID.
	 */
	public static function posts_custom_column( $column, $post_id ) {
		$string_map = array(
			'fcp_voornaam'   => '_fcp_voornaam',
			'fcp_achternaam' => '_fcp_achternaam',
			'fcp_lidnr'      => '_fcp_lidnr_fotobond',
			'fcp_adres'      => '_fcp_adres',
			'fcp_postcode'   => '_fcp_postcode',
			'fcp_plaats'     => '_fcp_plaats',
			'fcp_telefoon'   => '_fcp_telefoon',
			'fcp_email'      => '_fcp_email',
		);
		if ( isset( $string_map[ $column ] ) ) {
			$v = get_post_meta( $post_id, $string_map[ $column ], true );
			echo $v !== '' && null !== $v ? esc_html( (string) $v ) : '—';
			return;
		}

		if ( 'fcp_bar' === $column ) {
			$v = get_post_meta( $post_id, '_fcp_bar', true );
			echo '1' === $v ? esc_html__( 'Ja', 'fotoclubperspectief' ) : '—';
			return;
		}

		foreach ( self::commissie_werkgroep_labels() as $col_id => $_label ) {
			if ( $column !== $col_id ) {
				continue;
			}
			$lid  = get_post_meta( $post_id, '_' . $col_id, true ) === '1';
			$trek = get_post_meta( $post_id, '_' . $col_id . '_trekker', true ) === '1';
			if ( $trek ) {
				echo '<span class="fcp-member-bool-t" role="img" aria-label="' . esc_attr__( 'Trekker', 'fotoclubperspectief' ) . '">T</span>';
				return;
			}
			if ( $lid ) {
				echo '<span class="fcp-member-bool-ok" role="img" aria-label="' . esc_attr__( 'Lid', 'fotoclubperspectief' ) . '">' . esc_html( "\u{2713}" ) . '</span>';
				return;
			}
			echo '—';
			return;
		}
	}

	/**
	 * Sortable columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function sortable_columns( $columns ) {
		$columns['fcp_voornaam']   = 'fcp_voornaam';
		$columns['fcp_achternaam'] = 'fcp_achternaam';
		$columns['fcp_lidnr']      = 'fcp_lidnr';
		$columns['fcp_plaats']     = 'fcp_plaats';
		$columns['fcp_email']      = 'fcp_email';
		return $columns;
	}

	/**
	 * Get display name (voornaam) for agenda selects.
	 *
	 * @param int $post_id Member post ID.
	 * @return string
	 */
	public static function get_voornaam( $post_id ) {
		$v = get_post_meta( $post_id, '_fcp_voornaam', true );
		if ( $v ) {
			return $v;
		}
		$t = get_the_title( $post_id );
		return $t ? $t : '';
	}

	/**
	 * Alle leden ophalen, alfabetisch gesorteerd.
	 *
	 * @param string $sort_by 'achternaam' (standaard) of 'voornaam'.
	 * @return WP_Post[]
	 */
	public static function get_members_sorted( $sort_by = 'voornaam' ) {
		$keys = array(
			'achternaam' => '_fcp_achternaam',
			'voornaam'   => '_fcp_voornaam',
		);
		$meta_key = isset( $keys[ $sort_by ] ) ? $keys[ $sort_by ] : '_fcp_achternaam';

		$q = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'meta_value',
				'meta_key'       => $meta_key,
				'order'          => 'ASC',
			)
		);
		return $q->posts;
	}
}
