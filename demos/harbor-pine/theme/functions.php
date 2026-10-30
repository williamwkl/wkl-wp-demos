<?php
/**
 * Harbor & Pine child theme (Hello Elementor).
 *
 * The demo is built twice from the same content to document an optimization pass:
 *   hp_mode = naive      typical first build: Google Fonts via Elementor, Font Awesome icon font,
 *                        full-size JPEGs, every default stylesheet and script left on.
 *   hp_mode = optimized  self-hosted fonts, inline SVG icons, sized WebP + lazy loading,
 *                        inline critical CSS, unused Elementor/Hello/Fluent assets removed.
 */
function hp_optimized() { return 'naive' !== get_option( 'hp_mode', 'optimized' ); }

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'harbor-pine', get_stylesheet_uri(), [], '1.0.0' );
	wp_enqueue_script( 'harbor-pine', get_stylesheet_directory_uri() . '/assets/js/site.js', [], '1.0.0', [ 'strategy' => 'defer', 'in_footer' => true ] );
	if ( hp_optimized() ) {
		wp_style_add_data( 'harbor-pine', 'path', get_stylesheet_directory() . '/style.css' );
		foreach ( [ 'hello-elementor', 'hello-elementor-theme-style', 'hello-elementor-header-footer', 'elementor-icons', 'fluent-form-styles', 'fluentform-public-default', 'wp-block-library', 'classic-theme-styles', 'global-styles' ] as $h ) {
			wp_dequeue_style( $h );
		}
	}
}, 20 );

// Fonts: naive mode lets Elementor pull Newsreader from Google; optimized mode self-hosts it (child CSS @font-face).
add_action( 'wp_head', function () {
	if ( hp_optimized() ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_stylesheet_directory_uri() . '/assets/fonts/newsreader-var.woff2' ) );
		printf( '<style id="hp-fonts">@font-face{font-family:"Newsreader";src:url(%1$s) format("woff2");font-weight:400 500;font-style:normal;font-display:swap}@font-face{font-family:"Newsreader";src:url(%2$s) format("woff2");font-weight:400;font-style:italic;font-display:swap}</style>' . "\n",
			esc_url( get_stylesheet_directory_uri() . '/assets/fonts/newsreader-var.woff2' ), esc_url( get_stylesheet_directory_uri() . '/assets/fonts/newsreader-italic.woff2' ) );
	}
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( get_stylesheet_directory_uri() . '/assets/favicon.svg' ) );
}, 1 );

add_action( 'wp_print_scripts', function () {
	if ( ! hp_optimized() ) return;
	// Every widget on these pages is static (containers, headings, text, buttons, icon lists, shortcodes),
	// so Elementor's frontend runtime and jQuery are not needed. Fluent Forms submission JS is replaced by site.js.
	foreach ( [ 'elementor-frontend', 'elementor-webpack-runtime', 'elementor-frontend-modules', 'elementor-waypoints', 'swiper', 'hello-theme-frontend', 'fluent-form-submission', 'fluentform-advanced', 'fluentform-elementor', 'jquery', 'jquery-core', 'jquery-migrate' ] as $h ) {
		wp_dequeue_script( $h );
	}
}, 100 );
add_action( 'wp_footer', function () {
	if ( ! hp_optimized() ) return;
	foreach ( [ 'elementor-frontend', 'elementor-webpack-runtime', 'elementor-frontend-modules', 'fluent-form-submission', 'jquery', 'jquery-core', 'jquery-migrate' ] as $h ) wp_dequeue_script( $h );
}, 1 );

add_filter( 'styles_inline_size_limit', fn( $l ) => hp_optimized() ? 120000 : $l );
add_filter( 'wp_omit_loading_attr_threshold', fn( $n ) => hp_optimized() ? 0 : $n );
add_filter( 'wp_editor_set_quality', fn( $q, $mime ) => 'image/webp' === $mime ? 60 : $q, 10, 2 );
add_action( 'after_setup_theme', fn() => add_image_size( 'demo-600', 600, 0 ) );

// Fluent Forms enqueues its stylesheets late (when the shortcode renders), so drop them at print time.
// The form is styled entirely by the child theme tokens.
add_action( 'wp_print_styles', function () {
	if ( ! hp_optimized() ) return;
	foreach ( [ 'fluent-form-styles', 'fluentform-public-default', 'fluentform-elementor-widget' ] as $h ) wp_dequeue_style( $h );
}, 100 );
add_action( 'wp_print_footer_scripts', function () {
	if ( ! hp_optimized() ) return;
	foreach ( [ 'fluent-form-styles', 'fluentform-public-default', 'fluentform-elementor-widget' ] as $h ) wp_dequeue_style( $h );
}, 1 );

// Optimized build: give every local stylesheet a file path so core can inline it (no render-blocking CSS
// requests on a static host). On a live host with page caching, external files plus HTTP caching is also fine.
// Elementor enqueues during wp_head, after core's own inline pass, so tag paths and run the pass again.
add_action( 'wp_head', function () {
	if ( ! hp_optimized() ) return;
	$styles = wp_styles();
	foreach ( $styles->queue as $h ) {
		$src = $styles->registered[ $h ]->src ?? '';
		if ( ! is_string( $src ) || ! $src || $styles->get_data( $h, 'path' ) ) continue;
		$rel = str_replace( [ content_url(), includes_url() ], [ WP_CONTENT_DIR, ABSPATH . WPINC . '/' ], strtok( $src, '?' ) );
		if ( $rel !== $src && is_readable( $rel ) ) $styles->add_data( $h, 'path', $rel );
	}
	wp_maybe_inline_styles();
}, 2 );
