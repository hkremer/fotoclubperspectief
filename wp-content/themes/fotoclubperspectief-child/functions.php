<?php
/**
 * Fotoclub Perspectief child theme (Twenty Twenty).
 *
 * @package FotoclubPerspectief_Child
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue parent + child styles.
 */
function fotoclubperspectief_child_enqueue_styles() {
	$parent = wp_get_theme( get_template() );
	wp_enqueue_style(
		'twentytwenty-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		$parent->get( 'Version' )
	);
	wp_enqueue_style(
		'fotoclubperspectief-child',
		get_stylesheet_uri(),
		array( 'twentytwenty-parent-style' ),
		(string) filemtime( get_stylesheet_directory() . '/style.css' )
	);
}

add_action( 'wp_enqueue_scripts', 'fotoclubperspectief_child_enqueue_styles', 20 );

function my_child_theme_setup() {
	
    $black     = '#000000';
    $dark_gray = '#28303D';
    $gray      = '#39414D';
    $green     = '#D1E4DD';
    $blue      = '#D1DFE4';
    $purple    = '#D1D1E4';
    $red       = '#E4D1D1';
    $orange    = '#E4DAD1';
    $yellow    = '#EEEADD';
    $white     = '#FFFFFF';

    // Block Editor Palette.
    $editor_color_palette = array(
      array(
        'name'  => __( 'Accent Color', 'twentytwentychild' ),
        'slug'  => 'accent',
        'color' => '#cd2653',
      ),
      array(
        'name'  => __( 'Accent Color FC', 'twentytwentychild' ),
        'slug'  => 'accentfc',
        'color' => '#008b8b',
      ),
      array(
        'name'  => _x( 'Primary', 'color', 'twentytwentychild' ),
        'slug'  => 'primary',
        'color' => '#000000',
      ),
      array(
        'name'  => _x( 'Secondary', 'color', 'twentytwentychild' ),
        'slug'  => 'secondary',
        'color' => '#6d6d6d',
      ),
      array(
        'name'  => __( 'Subtle Background', 'twentytwentychild' ),
        'slug'  => 'subtle-background',
        'color' => '#dbdbdb', //  #dcd7ca
      ),
      array(
        'name'  => __( 'Background Color', 'twentytwentychild' ),
        'slug'  => 'background',
        'color' => '#ffffff',
      ),
      array(
        'name'  => esc_html__( 'Green', 'twentytwentychild' ),
        'slug' => 'green',
        'color' => '#b1ead4',
      ),
      array(
        'name'  => esc_html__( 'Yellow', 'twentytwentychild' ),
        'slug' => 'yellow',
        'color' => '#fee95c',
      ),
      array(
        'name'  => esc_html__( 'Lightyellow', 'twentytwentychild' ),
        'slug' => 'lightyellow',
        'color' => '#FCFBDE',
      )
    );

    // If we have accent colors, add them to the block editor palette.
    if ( $editor_color_palette ) {
      add_theme_support( 'editor-color-palette', $editor_color_palette );
    }

  }
  add_action( 'after_setup_theme', 'my_child_theme_setup', 30);

  add_action( 'enqueue_block_editor_assets', function() {
    wp_enqueue_style( 'twentytwentychild', get_stylesheet_directory_uri() . "/block-editor.css", false, '1.1', 'all' );

    add_action( 'wp_enqueue_scripts', function() {
      $styles = wp_styles();
      $styles->add_data( 'twentytwenty-style', 'after', array() );
    }, 20 );
} );

function display_user_bio($atts, $content = null) {
  $user_id = $atts['user_id'];
  $bio = '';$site = '';$camera = '';
  $bio = get_user_meta($user_id, 'description', true);
  $site = get_user_meta($user_id, 'prive_website', true);
  // error_log('site: ' . $site);
  $camera =  get_user_meta($user_id, 'camera', true);
  // error_log('camera: ' . $camera);
  if ($user_id != '') {
    $info = '<h5 class="wp-block-heading">';
    if ($bio != '') {
      $info .= $bio;
      $info .= '</h5>';
    }
    if ($site != '') {
      $info .= '<h5 class="wp-block-heading">Website: <a href="';
      $info .= $site;
      $info .= '" target="_blank">';
      $info .= $site;
      $info .= '</a></h5>';
    }
    if ($camera != '') {
      $info .= '<h5 class="wp-block-heading">Camera: ';
      $info .= $camera;
      $info .= '</h5>';
    }
    return $info;
  } else {
    error_log('geen user id');
  }
}
add_shortcode('user_bio', 'display_user_bio');
