<?php
/* Plugin Name: Demo head cleanup (optimized build only) */
if ( 'naive' === get_option( 'hp_mode' ) ) return;
remove_action( 'wp_head', 'rest_output_link_wp_head' );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'template_redirect', 'rest_output_link_header', 11 );
add_filter( 'emoji_svg_url', '__return_false' );
add_filter( 'wp_resource_hints', fn( $u, $t ) => 'dns-prefetch' === $t ? [] : $u, 10, 2 );
remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
