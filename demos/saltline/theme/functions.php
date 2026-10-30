<?php
/**
 * Saltline child theme.
 */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'saltline', get_stylesheet_uri(), [], wp_get_theme()->get( 'Version' ) );
	// Lets core inline this small stylesheet (see styles_inline_size_limit below): no render-blocking request.
	wp_style_add_data( 'saltline', 'path', get_stylesheet_directory() . '/style.css' );
	if ( is_page( 'contact' ) ) {
		wp_enqueue_script( 'saltline-demo-form', get_stylesheet_directory_uri() . '/assets/js/demo-form.js', [], '1.0.0', [ 'strategy' => 'defer', 'in_footer' => true ] );
	}
}, 20 );

// Parent Twenty Twenty-Five style.css is not needed (all styling lives in theme.json + child CSS).
add_action( 'wp_enqueue_scripts', fn() => wp_dequeue_style( 'twentytwentyfive-style' ), 30 );

// No font preload: the hero photo is the LCP element and should not compete with the font for bandwidth.
// The font is still self-hosted with font-display: swap (theme.json).

// Contact Form 7 assets only where a form is rendered.
// CF7's own JS fetches a REST schema on load, which does not exist on the static copy. The demo
// handler (assets/js/demo-form.js) validates and shows the success state instead.
add_filter( 'wpcf7_load_js', '__return_false' );
add_filter( 'wpcf7_load_css', '__return_false' );
add_filter( 'wpcf7_autop_or_not', '__return_false' );

// Inline small stylesheets (theme CSS + per-block core CSS) instead of render-blocking requests.
add_filter( 'styles_inline_size_limit', fn() => 60000 );

add_action( 'wp_head', function () {
	if ( ! has_site_icon() ) {
		printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( get_stylesheet_directory_uri() . '/assets/favicon.svg' ) );
	}
}, 2 );

// Responsive image sizes + lighter WebP re-encodes for generated sizes.
add_action( 'after_setup_theme', function () {
	add_image_size( 'demo-480', 480, 0 );
	add_image_size( 'demo-600', 600, 0 );
} );
add_filter( 'wp_editor_set_quality', fn( $q, $mime ) => 'image/webp' === $mime ? 60 : $q, 10, 2 );
add_filter( 'wp_omit_loading_attr_threshold', fn() => 0 ); // only the hero image loads eagerly

// The hero image renders at most ~470px wide on desktop and full width minus gutters on phones.
add_filter( 'wp_content_img_tag', function ( $html ) {
	if ( str_contains( $html, 'fetchpriority="high"' ) ) {
		$html = preg_replace( '/sizes="[^"]*"/', 'sizes="(max-width: 860px) calc(100vw - 40px), 480px"', $html );
	}
	return $html;
}, 20 );
