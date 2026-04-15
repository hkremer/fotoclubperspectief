<?php
/**
 * Homepage-opties (Settings API).
 *
 * @package FotoclubPerspectief
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class FCP_Home_Settings
 */
class FCP_Home_Settings {

	const OPTION_NAME = 'fcp_home_options';

	/**
	 * Default option structure.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'enable_homepage'   => 0,
			'featured_image_id' => 0,
			'featured_caption'  => '',
			'ct1_title'         => '',
			'ct1_content'       => '',
			'ct1_image_id'      => 0,
			'ct2_title'         => '',
			'ct2_content'       => '',
			'ct2_image_id'      => 0,
			'mededelingen_content' => '',
			/** @deprecated Oude repeater-structuur; wordt bij migrate leeggemaakt. */
			'mededelingen'      => array(),
			'card_portret_url'          => '',
			'card_portret_image_id'     => 0,
			'card_natuur_url'           => '',
			'card_natuur_image_id'      => 0,
			'card_straat_url'           => '',
			'card_straat_image_id'      => 0,
			'card_architectuur_url'     => '',
			'card_architectuur_image_id' => 0,
		);
	}

	/**
	 * Get merged options.
	 *
	 * @return array
	 */
	public static function get_options() {
		$o = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $o ) ) {
			$o = array();
		}
		$out = array_merge( self::defaults(), $o );
		self::maybe_migrate_mededelingen_repeater( $out );
		return $out;
	}

	/**
	 * Eenmalig: oude mededelingen (meerdere blokken) → één HTML-veld.
	 *
	 * @param array $out Options (by ref updated when migration runs).
	 */
	private static function maybe_migrate_mededelingen_repeater( array &$out ) {
		$has_new = isset( $out['mededelingen_content'] ) && is_string( $out['mededelingen_content'] ) && '' !== trim( $out['mededelingen_content'] );
		if ( $has_new ) {
			return;
		}
		if ( empty( $out['mededelingen'] ) || ! is_array( $out['mededelingen'] ) ) {
			return;
		}
		$parts = array();
		foreach ( $out['mededelingen'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$t = isset( $row['title'] ) ? trim( (string) $row['title'] ) : '';
			$c = isset( $row['content'] ) ? trim( (string) $row['content'] ) : '';
			if ( '' === $t && '' === $c ) {
				continue;
			}
			$block = '';
			if ( '' !== $t ) {
				$block .= '<h3 class="fcp-mededeling-kop">' . esc_html( $t ) . '</h3>';
			}
			if ( '' !== $c ) {
				$block .= wp_kses_post( $c );
			}
			if ( '' !== $block ) {
				$parts[] = $block;
			}
		}
		if ( empty( $parts ) ) {
			return;
		}
		$out['mededelingen_content'] = implode( "\n\n", $parts );
		$out['mededelingen']           = array();
		update_option( self::OPTION_NAME, $out );
	}

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		// Vroeg: wp_enqueue_editor() vóór andere plugins die de editor-actie al hebben getriggerd.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ), 1 );
	}

	/**
	 * Register setting.
	 */
	public static function register_settings() {
		register_setting(
			'fcp_home_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitize all home options.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$prev  = self::get_options();
		$clean = self::defaults();

		if ( ! is_array( $input ) ) {
			return $prev;
		}

		$clean['enable_homepage'] = isset( $input['enable_homepage'] ) ? 1 : 0;
		$clean['featured_image_id'] = isset( $input['featured_image_id'] ) ? absint( $input['featured_image_id'] ) : 0;
		$clean['featured_caption']  = isset( $input['featured_caption'] ) ? sanitize_text_field( wp_unslash( $input['featured_caption'] ) ) : '';

		$clean['ct1_title']    = isset( $input['ct1_title'] ) ? sanitize_text_field( wp_unslash( $input['ct1_title'] ) ) : '';
		$clean['ct1_content']  = isset( $input['ct1_content'] ) ? wp_kses_post( wp_unslash( $input['ct1_content'] ) ) : '';
		$clean['ct1_image_id'] = isset( $input['ct1_image_id'] ) ? absint( $input['ct1_image_id'] ) : 0;

		$clean['ct2_title']    = isset( $input['ct2_title'] ) ? sanitize_text_field( wp_unslash( $input['ct2_title'] ) ) : '';
		$clean['ct2_content']  = isset( $input['ct2_content'] ) ? wp_kses_post( wp_unslash( $input['ct2_content'] ) ) : '';
		$clean['ct2_image_id'] = isset( $input['ct2_image_id'] ) ? absint( $input['ct2_image_id'] ) : 0;

		$clean['mededelingen_content'] = isset( $input['mededelingen_content'] )
			? self::mededeling_h3_ensure_class( wp_kses_post( wp_unslash( $input['mededelingen_content'] ) ) )
			: '';
		$clean['mededelingen']           = array();

		$url_fields = array( 'card_portret_url', 'card_natuur_url', 'card_straat_url', 'card_architectuur_url' );
		foreach ( $url_fields as $uf ) {
			$clean[ $uf ] = isset( $input[ $uf ] ) ? esc_url_raw( wp_unslash( $input[ $uf ] ) ) : '';
		}

		$img_fields = array( 'card_portret_image_id', 'card_natuur_image_id', 'card_straat_image_id', 'card_architectuur_image_id' );
		foreach ( $img_fields as $imf ) {
			$clean[ $imf ] = isset( $input[ $imf ] ) ? absint( $input[ $imf ] ) : 0;
		}

		return $clean;
	}

	/**
	 * Admin menu.
	 */
	public static function add_menu() {
		add_options_page(
			__( 'Fotoclub homepage', 'fotoclubperspectief' ),
			__( 'Fotoclub homepage', 'fotoclubperspectief' ),
			'manage_options',
			'fcp-home',
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue media + scripts voor afbeelding-velden.
	 *
	 * @param string $hook Hook.
	 */
	public static function enqueue_admin( $hook ) {
		if ( 'settings_page_fcp-home' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_editor();
		wp_enqueue_script(
			'fcp-home-admin',
			FCP_PLUGIN_URL . 'assets/admin-home.js',
			array( 'jquery' ),
			FCP_VERSION,
			true
		);
		wp_enqueue_style(
			'fcp-admin',
			FCP_PLUGIN_URL . 'assets/admin.css',
			array(),
			FCP_VERSION
		);
	}

	/**
	 * Zet class fcp-mededeling-kop op elke h3 in mededelingen-HTML (bij opslaan).
	 *
	 * @param string $html Sanitized HTML.
	 * @return string
	 */
	private static function mededeling_h3_ensure_class( $html ) {
		$html = (string) $html;
		if ( '' === trim( $html ) ) {
			return $html;
		}
		$out = preg_replace_callback(
			'/<h3(\s[^>]*)?>/i',
			function ( $m ) {
				$attrs = isset( $m[1] ) ? $m[1] : '';
				if ( preg_match( '/\sclass\s*=\s*([\'"])(.*?)\1/i', $attrs, $c ) ) {
					$classes = $c[2];
					if ( preg_match( '/(^|\s)fcp-mededeling-kop(\s|$)/', $classes ) ) {
						return '<h3' . $attrs . '>';
					}
					$merged    = trim( $classes . ' fcp-mededeling-kop' );
					$new_attrs = preg_replace(
						'/\sclass\s*=\s*([\'"])(.*?)\1/i',
						' class="' . esc_attr( $merged ) . '"',
						$attrs,
						1
					);
					return '<h3' . $new_attrs . '>';
				}
				$attrs = trim( (string) $attrs );
				if ( '' === $attrs ) {
					return '<h3 class="fcp-mededeling-kop">';
				}
				return '<h3 class="fcp-mededeling-kop" ' . $attrs . '>';
			},
			$html
		);
		return is_string( $out ) ? $out : $html;
	}

	/**
	 * wp_editor-instellingen: teeny + formatselect (alleen paragraaf en H3).
	 *
	 * @param string $textarea_name Name-attribuut van het veld.
	 * @param int    $rows          Aantal rijen.
	 * @return array
	 */
	private static function homepage_wp_editor_settings( $textarea_name, $rows = 6 ) {
		$toolbar = 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink';
		$tinymce = array(
			'toolbar1'      => $toolbar,
			'block_formats' => 'Paragraph=p;' . __( 'Kop 3', 'fotoclubperspectief' ) . '=h3',
		);
		return array(
			'textarea_name' => $textarea_name,
			'textarea_rows' => (int) $rows,
			'teeny'         => true,
			'media_buttons' => false,
			'tinymce'       => $tinymce,
		);
	}

	/**
	 * Image field helper.
	 *
	 * @param string $field_id   Field id prefix.
	 * @param string $input_name Input name for hidden id.
	 * @param int    $image_id   Attachment id.
	 */
	private static function image_field( $field_id, $input_name, $image_id ) {
		$url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
		?>
		<div class="fcp-image-field" data-field="<?php echo esc_attr( $field_id ); ?>">
			<input type="hidden" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $input_name ); ?>]" id="<?php echo esc_attr( $field_id ); ?>_id" value="<?php echo esc_attr( (string) $image_id ); ?>" />
			<div class="fcp-image-preview" style="margin:8px 0;">
				<?php if ( $url ) : ?>
					<img src="<?php echo esc_url( $url ); ?>" alt="" style="max-width:240px;height:auto;" />
				<?php endif; ?>
			</div>
			<button type="button" class="button fcp-select-image" data-target="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Afbeelding kiezen', 'fotoclubperspectief' ); ?></button>
			<button type="button" class="button fcp-clear-image" data-target="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Verwijderen', 'fotoclubperspectief' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Render settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$o = self::get_options();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Fotoclub homepage', 'fotoclubperspectief' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'fcp_home_group' ); ?>
				<h2><?php esc_html_e( 'Activatie', 'fotoclubperspectief' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Nieuwe homepage activeren', 'fotoclubperspectief' ); ?></th>
						<td>
							<label for="fcp_enable_homepage">
								<input
									type="checkbox"
									id="fcp_enable_homepage"
									name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enable_homepage]"
									value="1"
									<?php checked( ! empty( $o['enable_homepage'] ) ); ?>
								/>
								<?php esc_html_e( 'Gebruik de nieuwe homepage-secties van de plugin op de voorpagina.', 'fotoclubperspectief' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Standaard uit, zodat installeren van de plugin de huidige homepage niet direct wijzigt.', 'fotoclubperspectief' ); ?></p>
						</td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Uitgelicht', 'fotoclubperspectief' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Afbeelding', 'fotoclubperspectief' ); ?></th>
						<td><?php self::image_field( 'fcp_feat', 'featured_image_id', (int) $o['featured_image_id'] ); ?></td>
					</tr>
					<tr>
						<th><label for="fcp_feat_cap"><?php esc_html_e( 'Onderschrift', 'fotoclubperspectief' ); ?></label></th>
						<td><input type="text" class="large-text" id="fcp_feat_cap" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[featured_caption]" value="<?php echo esc_attr( $o['featured_caption'] ); ?>" /></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Linker blok', 'fotoclubperspectief' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="fcp_ct1t"><?php esc_html_e( 'Kop', 'fotoclubperspectief' ); ?></label></th>
						<td><input type="text" class="large-text" id="fcp_ct1t" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ct1_title]" value="<?php echo esc_attr( $o['ct1_title'] ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="fcp_ct1c"><?php esc_html_e( 'Tekst', 'fotoclubperspectief' ); ?></label></th>
						<td><?php wp_editor( $o['ct1_content'], 'fcp_ct1_content', self::homepage_wp_editor_settings( self::OPTION_NAME . '[ct1_content]', 6 ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Optionele afbeelding', 'fotoclubperspectief' ); ?></th>
						<td><?php self::image_field( 'fcp_ct1', 'ct1_image_id', (int) $o['ct1_image_id'] ); ?></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Midden blok', 'fotoclubperspectief' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="fcp_ct2t"><?php esc_html_e( 'Kop', 'fotoclubperspectief' ); ?></label></th>
						<td><input type="text" class="large-text" id="fcp_ct2t" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ct2_title]" value="<?php echo esc_attr( $o['ct2_title'] ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="fcp_ct2c"><?php esc_html_e( 'Tekst', 'fotoclubperspectief' ); ?></label></th>
						<td><?php wp_editor( $o['ct2_content'], 'fcp_ct2_content', self::homepage_wp_editor_settings( self::OPTION_NAME . '[ct2_content]', 6 ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Optionele afbeelding', 'fotoclubperspectief' ); ?></th>
						<td><?php self::image_field( 'fcp_ct2', 'ct2_image_id', (int) $o['ct2_image_id'] ); ?></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Mededelingen', 'fotoclubperspectief' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="fcp_mededelingen_content"><?php esc_html_e( 'Tekst', 'fotoclubperspectief' ); ?></label></th>
						<td>
							<p class="description"><?php esc_html_e( 'Zelfde editor als bij de linker- en middenblokken. In het eerste menu: alleen Paragraaf en Kop 3. Bij opslaan krijgt elke Kop 3 automatisch de class voor de opmaak op de site.', 'fotoclubperspectief' ); ?></p>
							<?php
							$med_c = isset( $o['mededelingen_content'] ) ? $o['mededelingen_content'] : '';
							wp_editor(
								$med_c,
								'fcp_mededelingen_content',
								self::homepage_wp_editor_settings( self::OPTION_NAME . '[mededelingen_content]', 8 )
							);
							?>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Homepage: vier cards (links en afbeeldingen)', 'fotoclubperspectief' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Per card een link en optioneel een afbeelding voor op de homepage.', 'fotoclubperspectief' ); ?></p>
				<?php
				$card_defs = array(
					array(
						'title'    => 'PORTRET',
						'url_key'  => 'card_portret_url',
						'img_key'  => 'card_portret_image_id',
						'field_id' => 'fcp_card_portret',
					),
					array(
						'title'    => 'NATUUR',
						'url_key'  => 'card_natuur_url',
						'img_key'  => 'card_natuur_image_id',
						'field_id' => 'fcp_card_natuur',
					),
					array(
						'title'    => 'STRAAT',
						'url_key'  => 'card_straat_url',
						'img_key'  => 'card_straat_image_id',
						'field_id' => 'fcp_card_straat',
					),
					array(
						'title'    => 'ARCHITECTUUR',
						'url_key'  => 'card_architectuur_url',
						'img_key'  => 'card_architectuur_image_id',
						'field_id' => 'fcp_card_architectuur',
					),
				);
				foreach ( $card_defs as $def ) {
					$url_key = $def['url_key'];
					$img_key = $def['img_key'];
					$fid     = $def['field_id'];
					$img_id  = isset( $o[ $img_key ] ) ? (int) $o[ $img_key ] : 0;
					?>
					<h3><?php echo esc_html( $def['title'] ); ?></h3>
					<table class="form-table">
						<tr>
							<th><label for="<?php echo esc_attr( $url_key ); ?>"><?php esc_html_e( 'URL', 'fotoclubperspectief' ); ?></label></th>
							<td><input type="url" class="large-text" id="<?php echo esc_attr( $url_key ); ?>" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $url_key ); ?>]" value="<?php echo esc_attr( isset( $o[ $url_key ] ) ? $o[ $url_key ] : '' ); ?>" placeholder="https://"/></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Afbeelding', 'fotoclubperspectief' ); ?></th>
							<td><?php self::image_field( $fid, $img_key, $img_id ); ?></td>
						</tr>
					</table>
					<?php
				}
				?>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
