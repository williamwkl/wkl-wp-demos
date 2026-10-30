<?php
/** Fernwood Makers child theme (Twenty Twenty-Five). */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'fernwood', get_stylesheet_uri(), [], '1.0.0' );
	wp_style_add_data( 'fernwood', 'path', get_stylesheet_directory() . '/style.css' );
	wp_enqueue_script( 'fernwood', get_stylesheet_directory_uri() . '/assets/js/directory.js', [], '1.0.0', [ 'strategy' => 'defer', 'in_footer' => true ] );
	wp_dequeue_style( 'twentytwentyfive-style' );
}, 20 );
add_filter( 'styles_inline_size_limit', fn() => 60000 );
add_filter( 'wp_omit_loading_attr_threshold', fn() => 0 );
add_filter( 'wp_editor_set_quality', fn( $q, $mime ) => 'image/webp' === $mime ? 62 : $q, 10, 2 );
add_action( 'wp_head', function () {
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( get_stylesheet_directory_uri() . '/assets/favicon.svg' ) );
}, 2 );
// Directory pages: the listing grid is rendered by the [fernwood_directory] shortcode; give archives a clear title.
add_filter( 'get_the_archive_title', fn( $t ) => is_post_type_archive( 'maker' ) ? 'Browse the directory' : $t );
