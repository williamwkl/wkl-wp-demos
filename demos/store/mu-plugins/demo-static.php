<?php
/**
 * Plugin Name: Static-host helpers
 * Description: Keeps the static export honest: no Yoast SearchAction pointing at a server search that does not exist,
 *              Contact Form 7 posts to a /demo-form/ notice page and uses native browser validation.
 */
add_filter( 'disable_wpseo_json_ld_search', '__return_true' );
add_filter( 'wpcf7_form_action_url', fn() => home_url( '/demo-form/' ) );
add_filter( 'wpcf7_form_novalidate', '__return_false' );
add_filter( 'wpcf7_form_elements', fn( $html ) => str_replace( 'aria-required="true"', 'aria-required="true" required', $html ) );
// Utility pages stay out of search results (the static exporter also leaves noindex pages out of sitemap.xml).
add_filter( 'wp_robots', function ( $r ) {
	if ( is_page( [ 'demo-form', 'credits' ] ) ) { $r['noindex'] = true; $r['follow'] = true; unset( $r['max-image-preview'] ); }
	return $r;
} );
add_filter( 'wpseo_robots', fn( $v ) => is_page( [ 'demo-form', 'credits' ] ) ? 'noindex, follow' : $v );
