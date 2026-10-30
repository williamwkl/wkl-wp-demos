<?php
/** Lantern Hill Roasters child theme (Twenty Twenty-Five). */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'lantern', get_stylesheet_uri(), [], '1.0.0' );
	wp_style_add_data( 'lantern', 'path', get_stylesheet_directory() . '/style.css' );
	wp_enqueue_script( 'lantern', get_stylesheet_directory_uri() . '/assets/js/store.js', [], '1.0.0', [ 'strategy' => 'defer', 'in_footer' => true ] );
	wp_dequeue_style( 'twentytwentyfive-style' );
	// Catalog pages are server-rendered by the theme: WooCommerce's legacy scripts are only needed on cart/checkout.
	if ( ! is_cart() && ! is_checkout() ) {
		foreach ( [ 'wc-add-to-cart', 'woocommerce', 'wc-cart-fragments', 'sourcebuster-js', 'wc-order-attribution', 'jquery-blockui', 'js-cookie' ] as $h ) wp_dequeue_script( $h );
		foreach ( [ 'woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'wc-blocks-style' ] as $h ) wp_dequeue_style( $h );
	}
}, 100 );
add_filter( 'styles_inline_size_limit', fn() => 80000 );
add_filter( 'wp_omit_loading_attr_threshold', fn() => 0 );
add_filter( 'wp_editor_set_quality', fn( $q, $mime ) => 'image/webp' === $mime ? 62 : $q, 10, 2 );
add_filter( 'woocommerce_enqueue_styles', fn( $s ) => ( is_cart() || is_checkout() ) ? $s : [] );
add_action( 'wp_head', function () {
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( get_stylesheet_directory_uri() . '/assets/favicon.svg' ) );
}, 2 );

add_shortcode( 'lh_header', function () {
	$count = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$cart  = function_exists( 'wc_get_cart_url' ) ? wp_make_link_relative( wc_get_cart_url() ) : '/cart/';
	$nav   = [ 'Shop' => '/shop/', 'Build a bundle' => '/bundle/', 'Subscriptions' => '/product/roasters-choice-monthly/', 'Our roastery' => '/#roastery' ];
	$req   = trailingslashit( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ) );
	$links = '';
	foreach ( $nav as $l => $h ) $links .= '<li><a href="' . esc_attr( $h ) . '"' . ( $req === $h ? ' aria-current="page"' : '' ) . '>' . esc_html( $l ) . '</a></li>';
	ob_start(); ?>
<a class="sr skip skip-link" href="#main">Skip to content</a>
<div class="announce">Free shipping on orders over <b>$40</b>. Roasted Mondays and Thursdays, shipped the same day.</div>
<header class="header">
	<div class="wrap nav">
		<a class="brand" href="/"><svg viewBox="0 0 30 30" width="30" height="30" aria-hidden="true"><path d="M15 3c-1 4-5 6-5 11a5 5 0 0 0 10 0c0-5-4-7-5-11Z" fill="none" stroke="#1F1813" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 25h12M11 28h8" stroke="#8E2F3F" stroke-width="1.8" stroke-linecap="round"/></svg>Lantern Hill</a>
		<nav aria-label="Primary"><ul class="nav-links"><?php echo $links; // phpcs:ignore ?></ul></nav>
		<div class="nav-actions">
			<a class="iconbtn" href="/shop/" aria-label="Browse the shop"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></a>
			<a class="iconbtn cart-link" href="<?php echo esc_attr( $cart ); ?>" data-cart-link><span class="sr" data-cart-label>Cart, <?php echo (int) $count; ?> items</span><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg><span class="count" data-cart-count aria-hidden="true"<?php echo $count ? '' : ' hidden'; ?>><?php echo (int) $count; ?></span></a>
			<button class="iconbtn burger" type="button" aria-expanded="false" aria-controls="menu"><span class="sr">Menu</span><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path class="i-open" d="M4 7h16M4 12h16M4 17h16"/><path class="i-close" d="M6 6l12 12M18 6 6 18"/></svg></button>
		</div>
	</div>
	<nav id="menu" class="menu-sheet" aria-label="Menu" hidden><ul><?php echo $links; // phpcs:ignore ?><li><a href="<?php echo esc_attr( $cart ); ?>" data-cart-link>Cart</a></li></ul></nav>
</header>
	<?php return ob_get_clean();
} );
add_action( 'wp_head', fn() => print( "<script>document.documentElement.classList.add('js-on')</script>\n" ), 0 );
// Preload the two small display-font files so headings do not reflow (layout shift) when the font arrives.
add_action( 'wp_head', function () {
	foreach ( [ 'instrument-serif.woff2', 'instrument-serif-italic.woff2' ] as $f ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_stylesheet_directory_uri() . '/assets/fonts/' . $f ) );
	}
}, 1 );
// Meta descriptions for the storefront pages (Yoast falls back to these).
add_filter( 'wpseo_metadesc', function ( $d ) {
	if ( $d ) return $d;
	if ( is_front_page() ) return 'Lantern Hill Roasters: small-batch coffee roasted this week in Ashgrove. Six coffees, brewing gear, and a build-your-own 3-item bundle. Demo store.';
	if ( function_exists( 'is_shop' ) && is_shop() ) return 'Shop Lantern Hill coffee, brewing gear, mugs and the monthly roaster\'s choice. Demo WooCommerce store.';
	if ( function_exists( 'is_product_category' ) && is_product_category() ) return single_term_title( '', false ) . ' from Lantern Hill Roasters, a demo WooCommerce store.';
	if ( function_exists( 'is_product' ) && is_product() ) return wp_strip_all_tags( get_the_excerpt() ) . ' Demo product.';
	if ( is_page( 'bundle' ) ) return 'Build your own bundle: pick any three coffees, gear or mugs and save 12 percent. Demo store.';
	if ( is_page() ) return get_the_title() . ' at Lantern Hill Roasters (demo store).';
	return $d;
} );
