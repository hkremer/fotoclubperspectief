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
		return array_merge( self::defaults(), $o );
	}

	/**
	 * Init.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin' ) );
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

		$meds = array();
		if ( ! empty( $input['mededelingen'] ) && is_array( $input['mededelingen'] ) ) {
			foreach ( $input['mededelingen'] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$t = isset( $row['title'] ) ? sanitize_text_field( wp_unslash( $row['title'] ) ) : '';
				$c = isset( $row['content'] ) ? wp_kses_post( wp_unslash( $row['content'] ) ) : '';
				if ( '' === $t && '' === $c ) {
					continue;
				}
				$meds[] = array(
					'title'   => $t,
					'content' => $c,
				);
			}
		}
		$clean['mededelingen'] = $meds;

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
	 * Enqueue media + small script for mededelingen rows.
	 *
	 * @param string $hook Hook.
	 */
	public static function enqueue_admin( $hook ) {
		if ( 'settings_page_fcp-home' !== $hook ) {
			return;
		}
		wp_enqueue_media();
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
						<td><?php wp_editor( $o['ct1_content'], 'fcp_ct1_content', array( 'textarea_name' => self::OPTION_NAME . '[ct1_content]', 'textarea_rows' => 6, 'teeny' => true, 'media_buttons' => false ) ); ?></td>
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
						<td><?php wp_editor( $o['ct2_content'], 'fcp_ct2_content', array( 'textarea_name' => self::OPTION_NAME . '[ct2_content]', 'textarea_rows' => 6, 'teeny' => true, 'media_buttons' => false ) ); ?></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Optionele afbeelding', 'fotoclubperspectief' ); ?></th>
						<td><?php self::image_field( 'fcp_ct2', 'ct2_image_id', (int) $o['ct2_image_id'] ); ?></td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Mededelingen', 'fotoclubperspectief' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Een of meer blokken met kop en tekst.', 'fotoclubperspectief' ); ?></p>
				<div id="fcp-mededelingen">
					<?php
					$meds = ! empty( $o['mededelingen'] ) && is_array( $o['mededelingen'] ) ? $o['mededelingen'] : array( array( 'title' => '', 'content' => '' ) );
					$i    = 0;
					foreach ( $meds as $row ) {
						$t = isset( $row['title'] ) ? $row['title'] : '';
						$c = isset( $row['content'] ) ? $row['content'] : '';
						?>
						<div class="fcp-mededeling-row" style="border:1px solid #ccd0d4;padding:12px;margin-bottom:12px;background:#fff;">
							<p><label><?php esc_html_e( 'Kop', 'fotoclubperspectief' ); ?><br />
							<input type="text" class="large-text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[mededelingen][<?php echo (int) $i; ?>][title]" value="<?php echo esc_attr( $t ); ?>" /></label></p>
							<p><label><?php esc_html_e( 'Tekst', 'fotoclubperspectief' ); ?><br />
							<textarea class="large-text" rows="5" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[mededelingen][<?php echo (int) $i; ?>][content]"><?php echo esc_textarea( $c ); ?></textarea></label></p>
							<p><button type="button" class="button fcp-remove-med"><?php esc_html_e( 'Verwijder dit blok', 'fotoclubperspectief' ); ?></button></p>
						</div>
						<?php
						++$i;
					}
					?>
				</div>
				<p><button type="button" class="button" id="fcp-add-med"><?php esc_html_e( '+ Mededeling toevoegen', 'fotoclubperspectief' ); ?></button></p>

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
