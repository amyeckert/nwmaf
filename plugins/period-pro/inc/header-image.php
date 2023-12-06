<?php
defined( 'ABSPATH' ) OR exit;

function ct_period_pro_output_header_image() {

	$header_image_type = get_theme_mod( 'header_image_type' );
	$header_image  		 = get_theme_mod( 'header_image_upload' );
	$header_video  		 = get_theme_mod( 'header_image_video' );
	$homepage_only 		 = get_theme_mod( 'header_image_homepage' );
	$link 				 		 = get_theme_mod( 'header_image_link' );

	if ( $homepage_only == 'yes' && ! is_front_page() ) {
		return;
	}
	if ( $header_image && $header_image_type != 'video' ) {
		echo '<div id="header-image" class="header-image" style="background-image: url(\'' . esc_url( $header_image ) . '\')" >';
			if ( $link != '' ) {
				echo '<a href="'. esc_url( $link ) .'">'. esc_html__( "Visit Page", "period-pro" ) .'</a>';
			}
		echo '</div>';
	} elseif ( $header_video && $header_image_type == 'video' ) {
		echo '<div id="header-image" class="header-image video">';
			echo wp_oembed_get( esc_url( $header_video ) );
		echo '</div>';
	}
}
add_action( 'body_top', 'ct_period_pro_output_header_image' );

function ct_period_pro_header_image_css() {

	$header_image_type = get_theme_mod( 'header_image_type' );
	$header_image 		 = get_theme_mod( 'header_image_upload' );

	if ( !empty( $header_image ) && $header_image_type != 'video' ) {

		$height_type = get_theme_mod( 'header_image_height_type' );
		$height      = get_theme_mod( 'header_image_height' );

		if ( empty( $height ) ) {
			$height = 20;
		}

		if ( $height_type == 'fixed' ) {
			$custom_css = "#header-image { height: " . $height * 5 . "px; padding-bottom: 0; }";
		} else {
			$custom_css = "#header-image { padding-bottom: $height%; }";
		}

		$custom_css = ct_period_pro_sanitize_css( $custom_css );

		wp_add_inline_style( 'ct-period-style', $custom_css );
		wp_add_inline_style( 'ct-period-style-rtl', $custom_css );
	}
}
add_action( 'wp_enqueue_scripts', 'ct_period_pro_header_image_css', 99 );

//----------------------------------------------------------------------------------
// Transition old yes/no header home link option to new custom URL option
//----------------------------------------------------------------------------------
function ct_period_pro_set_header_image_link() {
	if ( get_option( 'ct_period_pro_header_image_link_check' ) != 'yes' ) {
		if ( get_theme_mod( 'header_image_link_home' ) == 'yes' ) {
			set_theme_mod( 'header_image_link', esc_url(site_url()) );
		}
		update_option( 'ct_period_pro_header_image_link_check', 'yes' );
	}
}
add_action( 'admin_init', 'ct_period_pro_set_header_image_link' );