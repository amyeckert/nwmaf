<?php

// Customize WooCommerce
add_theme_support('woocommerce');

// Make var_dumps human readable
function pretty( $var ) {
    echo '<div class="u-text-left">';
    highlight_string( "<?php\n " . var_export( $var, true ) . "?>" );
    echo '</div>';
    echo '<script>document.getElementsByTagName("code")[0].getElementsByTagName("span")[1].remove() ;document.getElementsByTagName("code")[0].getElementsByTagName("span")[document.getElementsByTagName("code")[0].getElementsByTagName("span").length - 1].remove() ; </script>';
}

// get parent theme styles
add_action( 'wp_enqueue_scripts', 'enqueue_parent_styles' );

function enqueue_parent_styles() {
   wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css' );
}

function nwmaf_script_enqueue() {
   wp_enqueue_script( 'bootstrap_js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array('jquery'), NULL, true );

   wp_enqueue_style( 'bootstrap_css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', false, NULL, 'all' );

   wp_enqueue_style( 'styles.min_css', get_template_directory_uri() . '/dist/css/styles.min.css', array(), '1.0.0', 'all');

   wp_enqueue_script( 'custom_js', get_template_directory_uri() . '/js/custom.js' );
}

add_action( 'wp_enqueue_scripts', 'nwmaf_script_enqueue' );

// add menus
// This theme uses wp_nav_menu() in two locations.
register_nav_menus( array(
  'primary' => __( 'Primary Navigation', 'nwmaf' ),
  'footer' => __('footer', 'nwmaf')
) );

// show shipping address in checkout by default

// add_filter( 'woocommerce_ship_to_different_address_checked', '__return_true' );
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_true', 50 );

/* Remove the message 'You'll receive your tickets in another email' from the Woo Order email */
add_filter( 'wootickets_email_message', '__return_empty_string' );

/* show the event calendar category legend on all views */
// teccc_add_legend_view( 'week' );
//teccc_add_legend_view( 'list' );
//teccc_add_legend_view( 'day' );

// mobile menu toggle svg theme override
if ( ! function_exists( ( 'ct_period_svg_output' ) ) ) {
	function ct_period_svg_output( $type ) {

		$svg = '';

		if ( $type == 'toggle-navigation' ) {

			$svg = '<p class="mobile-menu-toggle">Menu</p><svg width="36px" height="23px" viewBox="0 0 36 23" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
				    <desc>mobile menu toggle button</desc>
				    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
				        <g transform="translate(-142.000000, -104.000000)" fill="#1c1c1a">
				            <g transform="translate(142.000000, 104.000000)">
				                <rect x="0" y="20" width="36" height="3"></rect>
				                <rect x="0" y="10" width="36" height="3"></rect>
				                <rect x="0" y="0" width="36" height="3"></rect>
				            </g>
				        </g>
				    </g>
				</svg>';
		}

		return $svg;
	}
}

// bug fix from Tibe to fix GForms Promoter logo issue
add_action('admin_head', 'tribe_fix_promoter_logo');
add_action('wp_head', 'tribe_fix_promoter_logo');
function tribe_fix_promoter_logo() {
  ?>
  <style>
      #wp-admin-bar-promoter-admin-bar a.ab-item {
          display: flex;
          flex-wrap: wrap;
          align-items: center;
      }
      #wp-admin-bar-promoter-admin-bar a.ab-item .promoter-admin-bar__icon {
          margin-right: 5px;
          width: 20px;
          height: 20px;
          flex: none;
      }
      #wp-admin-bar-promoter-admin-bar a.ab-item .promoter-admin-bar__text {
          flex: none;
      }
  </style>
  <?php
}

/* =============
 * Custom Blocks
================*/

function acf_callout_block() {

	// check function exists
	if( function_exists('acf_register_block') ) {

		// register a portfolio item block
		acf_register_block(array(
			'name'				=> 'block-callout',
			'title'				=> __('Callout'),
			'description'		=> __('A custom block for portfolio items.'),
			'render_template'	=> 'template-parts/blocks/portfolio-item/block-portfolio-item.php',
			'category'			=> 'layout',
			'icon'				=> 'excerpt-view',
			'keywords'			=> array( 'portfolio' ),
		));
	}
}

add_action('acf/init', 'acf_callout_block');

// function add_organizer_image_to_single_event_page() {
// 	$size = 'thumb';
// 	$link = false;
// 	$organizer_id = tribe_get_organizer_id();
// 	echo tribe_event_featured_image( $organizer_id, $size, $link );
// 	}
// add_action ('tribe_events_single_meta_organizer_section_end', 'add_organizer_image_to_single_event_page');


/* ==== DO NOT EDIT Member Directory ============*/

include('member-directory.php');


/* ==== DO NOT EDIT Logged in members pricing ============*/

// Variable and simple product displayed prices (removing sale price range)
add_filter( 'woocommerce_get_price_html', 'custom_get_price_html', 20, 2 );
function custom_get_price_html( $price, $product ) {

    if( $product->is_type('variable') )
    {
        if( is_user_logged_in() ){
            $price_min  = wc_get_price_to_display( $product, array( 'price' => $product->get_variation_sale_price('min') ) );
            $price_max  = wc_get_price_to_display( $product, array( 'price' => $product->get_variation_sale_price('max') ) );
        } else {
            $price_min  = wc_get_price_to_display( $product, array( 'price' => $product->get_variation_regular_price('min') ) );
            $price_max  = wc_get_price_to_display( $product, array( 'price' => $product->get_variation_regular_price('max') ) );
        }

        if( $price_min != $price_max ){
            if( $price_min == 0 && $price_max > 0 )
                $price = wc_price( $price_max );
            elseif( $price_min > 0 && $price_max == 0 )
                $price = wc_price( $price_min );
            else
                $price = wc_format_price_range( $price_min, $price_max );
        } else {
            if( $price_min > 0 )
                $price = wc_price( $price_min);
        }
    }
    elseif( $product->is_type('simple') )
    {
        if( is_user_logged_in() )
            $active_price = wc_get_price_to_display( $product, array( 'price' => $product->get_sale_price() ) );
        else
            $active_price = wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) );

        if( $active_price > 0 )
            $price = wc_price($active_price);
    }
    return $price;
}

// Product Variation displayed prices
add_filter( 'woocommerce_available_variation', 'custom_variation_price', 10, 3);
function custom_variation_price( $data, $product, $variation ) {

    $reg_price = wc_get_price_to_display( $variation, array( 'price' => $variation->get_regular_price() ) );
    $sale_price = wc_get_price_to_display( $variation, array( 'price' => $variation->get_sale_price() ) );

    if( is_user_logged_in() )
        $data['price_html'] = wc_price( $sale_price );
    else
        $data['price_html'] = wc_price( $reg_price );

    return $data;
}

// Set the correct prices in cart
add_action( 'woocommerce_before_calculate_totals', 'set_item_cart_prices', 20, 1 );
function set_item_cart_prices( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;

    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
        return;

    // Loop through cart items
    foreach ( $cart->get_cart() as $cart_item ){
        if( ! is_user_logged_in() ){
            $cart_item['data']->set_price( $cart_item['data']->get_regular_price() );
        }
    }
}

// Remove sale badge
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
