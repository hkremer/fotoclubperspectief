<?php
/**
 * Agenda (CPT fcp_agenda).
 *
 * @package FotoclubPerspectief
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class FCP_Agenda
 */
class FCP_Agenda {

	const POST_TYPE = 'fcp_agenda';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_post' ), 10, 2 );
	}

	/**
	 * Register CPT.
	 */
	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Agenda', 'fotoclubperspectief' ),
			'singular_name'      => __( 'Agenda-item', 'fotoclubperspectief' ),
			'add_new'            => __( 'Nieuw item', 'fotoclubperspectief' ),
			'add_new_item'       => __( 'Agenda-item toevoegen', 'fotoclubperspectief' ),
			'edit_item'          => __( 'Agenda-item bewerken', 'fotoclubperspectief' ),
			'new_item'           => __( 'Nieuw agenda-item', 'fotoclubperspectief' ),
			'view_item'          => __( 'Agenda-item bekijken', 'fotoclubperspectief' ),
			'search_items'       => __( 'Agenda doorzoeken', 'fotoclubperspectief' ),
			'not_found'          => __( 'Geen agenda-items', 'fotoclubperspectief' ),
			'menu_name'          => __( 'Agenda', 'fotoclubperspectief' ),
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => $labels,
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_icon'           => 'dashicons-calendar-alt',
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
			'fcp_agenda_details',
			__( 'Agenda-gegevens', 'fotoclubperspectief' ),
			array( __CLASS__, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Member dropdown HTML.
	 *
	 * @param string $name    Field name.
	 * @param string $current Current member ID.
	 * @param bool   $empty   Include empty option.
	 */
	private static function member_select( $name, $current, $empty = true ) {
		$members = FCP_Member::get_members_sorted();
		echo '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" class="fcp-member-select">';
		if ( $empty ) {
			echo '<option value="">' . esc_html__( '— Kies —', 'fotoclubperspectief' ) . '</option>';
		}
		foreach ( $members as $m ) {
			$vid   = (int) $m->ID;
			$label = FCP_Member::get_voornaam( $vid );
			printf(
				'<option value="%d"%s>%s</option>',
				$vid,
				selected( (string) $current, (string) $vid, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
	}

	/**
	 * Render meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_meta_box( $post ) {
		wp_nonce_field( 'fcp_agenda_save', 'fcp_agenda_nonce' );

		$datum       = get_post_meta( $post->ID, '_fcp_datum', true );
		$beschrijving = get_post_meta( $post->ID, '_fcp_beschrijving', true );
		$avond        = get_post_meta( $post->ID, '_fcp_avondleiding_id', true );
		$bar1         = get_post_meta( $post->ID, '_fcp_bardienst_id_1', true );
		$bar2         = get_post_meta( $post->ID, '_fcp_bardienst_id_2', true );
		$laptop       = get_post_meta( $post->ID, '_fcp_laptop_id', true );
		$clubavond    = get_post_meta( $post->ID, '_fcp_clubavond', true );

		if ( empty( $datum ) ) {
			$datum = gmdate( 'Y-m-d' );
		}
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="fcp_datum"><?php esc_html_e( 'Datum', 'fotoclubperspectief' ); ?></label></th>
					<td><input type="date" id="fcp_datum" name="fcp_datum" value="<?php echo esc_attr( $datum ); ?>" required /></td>
				</tr>
				<tr>
					<th><label for="fcp_beschrijving"><?php esc_html_e( 'Beschrijving', 'fotoclubperspectief' ); ?></label></th>
					<td>
						<?php
						wp_editor(
							$beschrijving,
							'fcp_beschrijving',
							array(
								'textarea_name' => 'fcp_beschrijving',
								'textarea_rows' => 8,
								'media_buttons' => false,
								'teeny'         => true,
							)
						);
						?>
						<p class="description"><?php esc_html_e( 'Gebruik een lijst voor opsommingen.', 'fotoclubperspectief' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="fcp_avondleiding_id"><?php esc_html_e( 'Avondleiding', 'fotoclubperspectief' ); ?></label></th>
					<td><?php self::member_select( 'fcp_avondleiding_id', $avond ); ?></td>
				</tr>
				<tr>
					<th><label for="fcp_bardienst_id_1"><?php esc_html_e( 'Bardienst', 'fotoclubperspectief' ); ?> (1)</label></th>
					<td><?php self::member_select( 'fcp_bardienst_id_1', $bar1 ); ?></td>
				</tr>
				<tr>
					<th><label for="fcp_bardienst_id_2"><?php esc_html_e( 'Bardienst', 'fotoclubperspectief' ); ?> (2)</label></th>
					<td><?php self::member_select( 'fcp_bardienst_id_2', $bar2 ); ?></td>
				</tr>
				<tr>
					<th><label for="fcp_laptop_id"><?php esc_html_e( 'Laptop', 'fotoclubperspectief' ); ?></label></th>
					<td><?php self::member_select( 'fcp_laptop_id', $laptop ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Clubavond', 'fotoclubperspectief' ); ?></th>
					<td><label><input type="checkbox" name="fcp_clubavond" value="1" <?php checked( $clubavond, '1' ); ?> /> <?php esc_html_e( 'Ja', 'fotoclubperspectief' ); ?></label></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save post.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function save_post( $post_id, $post ) {
		if ( ! isset( $_POST['fcp_agenda_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fcp_agenda_nonce'] ) ), 'fcp_agenda_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$datum = isset( $_POST['fcp_datum'] ) ? sanitize_text_field( wp_unslash( $_POST['fcp_datum'] ) ) : '';
		if ( $datum && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $datum ) ) {
			$datum = gmdate( 'Y-m-d' );
		}

		update_post_meta( $post_id, '_fcp_datum', $datum );
		$beschrijving = isset( $_POST['fcp_beschrijving'] ) ? wp_kses_post( wp_unslash( $_POST['fcp_beschrijving'] ) ) : '';
		update_post_meta( $post_id, '_fcp_beschrijving', $beschrijving );

		$int_or_zero = static function ( $key ) {
			if ( ! isset( $_POST[ $key ] ) || '' === $_POST[ $key ] ) {
				return '';
			}
			return (string) absint( $_POST[ $key ] );
		};

		update_post_meta( $post_id, '_fcp_avondleiding_id', $int_or_zero( 'fcp_avondleiding_id' ) );
		update_post_meta( $post_id, '_fcp_bardienst_id_1', $int_or_zero( 'fcp_bardienst_id_1' ) );
		update_post_meta( $post_id, '_fcp_bardienst_id_2', $int_or_zero( 'fcp_bardienst_id_2' ) );
		update_post_meta( $post_id, '_fcp_laptop_id', $int_or_zero( 'fcp_laptop_id' ) );
		update_post_meta( $post_id, '_fcp_clubavond', ! empty( $_POST['fcp_clubavond'] ) ? '1' : '0' );

		$title = $datum ? mysql2date( 'j F Y', $datum . ' 00:00:00', true ) : __( 'Agenda-item', 'fotoclubperspectief' );
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
	 * Upcoming agenda items (by event date meta).
	 *
	 * @param int $limit Max items.
	 * @return WP_Post[]
	 */
	public static function get_upcoming( $limit = 4 ) {
		$today = gmdate( 'Y-m-d' );

		$q = new WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => (int) $limit,
				'meta_key'       => '_fcp_datum',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'     => '_fcp_datum',
						'value'   => $today,
						'compare' => '>=',
						'type'    => 'DATE',
					),
				),
			)
		);

		return $q->posts;
	}

	/**
	 * Format date for display (localized).
	 *
	 * @param string $ymd Y-m-d.
	 * @return string
	 */
	public static function format_date_display( $ymd ) {
		if ( ! $ymd || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
			return '';
		}
		return date_i18n( get_option( 'date_format' ), strtotime( $ymd . ' 00:00:00' ) );
	}
}
