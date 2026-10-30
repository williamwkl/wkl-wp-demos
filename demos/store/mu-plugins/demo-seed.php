<?php
/**
 * Plugin Name: Lantern Hill Roasters demo
 * Description: WooCommerce baseline (offline "Test order" gateway, no keys), catalog seed from data/products.json,
 *              server-rendered storefront (home, shop, product, bundle builder) that works live and as a static copy,
 *              live bundle add-to-cart with a 12 percent fee discount, and the simulated cart/checkout pages for the static copy.
 */

const LH_BUNDLE_RATE = 0.12;
const LH_SIZES       = [ '12 oz', '2 lb', '5 lb' ];
const LH_GRINDS      = [ 'Whole bean', 'Filter', 'Espresso', 'French press' ];

function lh_data() {
	static $d = null;
	if ( null === $d ) $d = json_decode( file_get_contents( WP_CONTENT_DIR . '/demo-data/products.json' ), true );
	return $d;
}

/* ------------------------------------------------------------------ WooCommerce baseline */
function lh_wc_baseline() {
	update_option( 'woocommerce_onboarding_profile', [ 'completed' => true, 'skipped' => true ] );
	update_option( 'woocommerce_task_list_hidden', 'yes' );
	update_option( 'woocommerce_show_marketplace_suggestions', 'no' );
	update_option( 'woocommerce_allow_tracking', 'no' );
	update_option( 'woocommerce_currency', 'USD' );
	update_option( 'woocommerce_default_country', 'US:CA' );
	update_option( 'woocommerce_store_address', '14 Mill Street' );
	update_option( 'woocommerce_store_city', 'Ashgrove' );
	update_option( 'woocommerce_store_postcode', '94000' );
	update_option( 'woocommerce_calc_taxes', 'no' );
	update_option( 'woocommerce_enable_guest_checkout', 'yes' );
	update_option( 'woocommerce_coming_soon', 'no' );
	update_option( 'woocommerce_manage_stock', 'no' );
	// Offline gateway only: Cash on delivery, renamed. No payment keys exist anywhere in this demo.
	update_option( 'woocommerce_cod_settings', [ 'enabled' => 'yes', 'title' => 'Test order (no payment taken)', 'description' => 'This is a demo store. Your order is saved in this sandbox only; nothing is charged and nothing ships.', 'instructions' => 'Demo order: no payment is taken and nothing will ship.', 'enable_for_methods' => [], 'enable_for_virtual' => 'yes' ] );
	foreach ( [ 'bacs', 'cheque', 'paypal' ] as $g ) update_option( "woocommerce_{$g}_settings", [ 'enabled' => 'no' ] );
	if ( class_exists( 'WC_Shipping_Zone' ) ) {
		$zone = new WC_Shipping_Zone( 0 );
		if ( ! $zone->get_shipping_methods() ) {
			$id = $zone->add_shipping_method( 'free_shipping' );
			update_option( "woocommerce_free_shipping_{$id}_settings", [ 'title' => 'Standard, 2 to 4 days, free over $40', 'requires' => 'min_amount', 'min_amount' => '40' ] );
			$id = $zone->add_shipping_method( 'flat_rate' );
			update_option( "woocommerce_flat_rate_{$id}_settings", [ 'title' => 'Standard, 2 to 4 days', 'cost' => '6', 'tax_status' => 'none' ] );
		}
	}
}

/* ------------------------------------------------------------------ catalog seed */
function lh_media( $key ) {
	static $cache = [];
	if ( isset( $cache[ $key ] ) ) return $cache[ $key ];
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$file = glob( get_stylesheet_directory() . "/assets/img/$key-*.webp" )[0] ?? '';
	if ( ! $file ) return $cache[ $key ] = 0;
	$up = wp_upload_dir(); $dest = trailingslashit( $up['path'] ) . basename( $file );
	copy( $file, $dest );
	$id = wp_insert_attachment( [ 'post_mime_type' => 'image/webp', 'post_title' => $key, 'post_status' => 'inherit' ], $dest );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
	return $cache[ $key ] = $id;
}

function lh_seed_catalog() {
	$d = lh_data();
	$cats = [];
	foreach ( $d['categories'] as $i => $c ) {
		$t = term_exists( $c['slug'], 'product_cat' ) ?: wp_insert_term( $c['name'], 'product_cat', [ 'slug' => $c['slug'] ] );
		$cats[ $c['slug'] ] = (int) $t['term_id'];
		update_term_meta( $cats[ $c['slug'] ], 'order', $i );
	}
	wp_insert_term( 'Bundle', 'product_tag', [ 'slug' => 'bundle' ] );
	foreach ( $d['products'] as $order => $p ) {
		if ( ! empty( $p['sizes'] ) ) {
			$prod = new WC_Product_Variable();
			$attr = new WC_Product_Attribute();
			$attr->set_name( 'Size' ); $attr->set_options( LH_SIZES ); $attr->set_visible( true ); $attr->set_variation( true );
			$prod->set_attributes( [ $attr ] );
		} else {
			$prod = new WC_Product_Simple();
			$prod->set_regular_price( (string) $p['price'] );
		}
		$prod->set_name( $p['name'] ); $prod->set_slug( $p['slug'] ); $prod->set_status( 'publish' );
		$prod->set_short_description( $p['short'] ); $prod->set_description( $p['desc'] );
		$prod->set_category_ids( [ $cats[ $p['cat'] ] ] ); $prod->set_menu_order( $order );
		$prod->set_featured( ! empty( $p['featured'] ) );
		$prod->set_image_id( lh_media( $p['img'] ) );
		$id = $prod->save();
		if ( ! empty( $p['bundle'] ) ) wp_set_object_terms( $id, 'bundle', 'product_tag', true );
		update_post_meta( $id, '_lh', wp_slash( wp_json_encode( $p ) ) );
		if ( ! empty( $p['sizes'] ) ) {
			foreach ( LH_SIZES as $i => $size ) {
				$v = new WC_Product_Variation();
				$v->set_parent_id( $id ); $v->set_attributes( [ 'size' => $size ] ); $v->set_regular_price( (string) $p['sizes'][ $i ] ); $v->set_menu_order( $i );
				$v->save();
			}
			WC_Product_Variable::sync( $id );
		}
	}
	// FIRSTBAG: 10 percent off, the same code the static cart accepts.
	if ( ! wc_get_coupon_id_by_code( 'firstbag' ) ) {
		$c = new WC_Coupon(); $c->set_code( 'firstbag' ); $c->set_discount_type( 'percent' ); $c->set_amount( 10 ); $c->set_description( 'Demo coupon: 10 percent off' ); $c->save();
	}
}

function lh_page( $slug, $title, $content ) {
	$p = get_page_by_path( $slug );
	$arr = [ 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content ];
	if ( $p ) { $arr['ID'] = $p->ID; return wp_update_post( wp_slash( $arr ) ); }
	return wp_insert_post( wp_slash( $arr ) );
}

function demo_seed() {
	if ( get_option( 'demo_seeded' ) ) return;
	kses_remove_filters();
	lh_wc_baseline();
	lh_seed_catalog();
	$home = lh_page( 'home', 'Home', '<!-- wp:shortcode -->[lh_home]<!-- /wp:shortcode -->' );
	lh_page( 'bundle', 'Build your own bundle', '<!-- wp:shortcode -->[lh_bundle]<!-- /wp:shortcode -->' );
	lh_page( 'demo-cart', 'Your cart', '<!-- wp:shortcode -->[lh_sim_cart]<!-- /wp:shortcode -->' );
	lh_page( 'demo-checkout', 'Checkout', '<!-- wp:shortcode -->[lh_sim_checkout]<!-- /wp:shortcode -->' );
	lh_page( 'demo-order', 'Test order placed', '<!-- wp:shortcode -->[lh_sim_confirm]<!-- /wp:shortcode -->' );
	lh_page( 'demo-form', 'Demo form', '<!-- wp:shortcode -->[lh_demo_form]<!-- /wp:shortcode -->' );
	lh_page( 'credits', 'Photo credits', '<!-- wp:shortcode -->[lh_credits]<!-- /wp:shortcode -->' );
	foreach ( [ 'hello-world', 'sample-page', 'privacy-policy' ] as $s ) { $x = get_page_by_path( $s, OBJECT, [ 'post', 'page' ] ); if ( $x ) wp_delete_post( $x->ID, true ); }
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );
	update_option( 'blogname', 'Lantern Hill Roasters' );
	update_option( 'blogdescription', 'Coffee roasted this week, not this quarter. (Demo store)' );
	update_option( 'wpseo_titles', array_merge( (array) get_option( 'wpseo_titles' ), [ 'company_or_person' => 'company', 'company_name' => 'Lantern Hill Roasters', 'website_name' => 'Lantern Hill Roasters', 'separator' => 'sc-pipe' ] ) );
	update_option( 'demo_seeded', 1 );
	flush_rewrite_rules();
}
