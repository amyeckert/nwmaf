<!DOCTYPE html>

<html <?php language_attributes(); ?>>

<head>
	<?php wp_head(); ?>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

	<!-- Google Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

	<link href="https://fonts.googleapis.com/css2?family=Hind:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous"></script>

	<!-- MailChimp -->
	<script id="mcjs">!function(c,h,i,m,p){m=c.createElement(h),p=c.getElementsByTagName(h)[0],m.async=1,m.src=i,p.parentNode.insertBefore(m,p)}(document,"script","https://chimpstatic.com/mcjs-connected/js/users/ebc94e4da6e7e66f8cd42253b/7f671f5a4c1494e24ce9c739e.js");</script>
</head>

<body id="<?php print get_stylesheet(); ?>" <?php body_class(); ?>>
<?php do_action( 'body_top' ); ?>
<?php
if ( function_exists( 'wp_body_open' ) ) {
			wp_body_open();
	} else {
			do_action( 'wp_body_open' );
} ?>
<a class="skip-content" href="#main"><?php esc_html_e( 'Press "Enter" to skip to content', 'period' ); ?></a>
<div id="overflow-container" class="overflow-container">
	<?php do_action( 'before_header' ); ?>
	<?php
	// Elementor `header` location
	if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'header' ) ) :
	?>
	<header class="site-header" id="site-header" role="banner">
		<div class="max-width">
			<div id="title-container" class="title-container">
				<?php get_template_part( 'logo' ) ?>
				<?php if ( get_bloginfo( 'description' ) ) {
					echo '<p class="tagline">' . esc_html( get_bloginfo( 'description' ) ) . '</p>';
				} ?>
			</div>
			<div id="toggle-navigation" class="toggle-navigation" name="toggle-navigation" aria-expanded="false">
				<span class="screen-reader-text"><?php echo esc_html_x( 'open menu', 'verb: open the menu', 'period' ); ?></span>
				<?php echo ct_period_svg_output( 'toggle-navigation' ); ?>
			</div>
			<div id="menu-primary-container" class="menu-primary-container">
				<?php get_template_part( 'menu', 'primary' ); ?>
				<?php get_template_part( 'menu', 'secondary' ); ?>
			</div>
            <?php echo do_shortcode( '[flexy_breadcrumb]'); ?>
		</div>
	</header>
	<?php endif; ?>
	<?php do_action( 'after_header' ); ?>
	<main id="primary-container" class="primary-container">
		<div class="max-width">
			<section id="main" class="main" role="main">
				<?php do_action( 'main_top' );

