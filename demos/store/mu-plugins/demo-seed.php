<?php
/**
 * Plugin Name: Store demo seed (infrastructure)
 * Description: WooCommerce baseline: store settings, offline demo payment, no onboarding, one test product. Catalog content follows design/store/DESIGN.md.
 */
function st_wc_baseline() {
	update_option( 'woocommerce_onboarding_profile', [ 'completed' => true, 'skipped' => true ] );
	update_option( 'woocommerce_task_list_hidden', 'yes' );
	update_option( 'woocommerce_show_marketplace_suggestions', 'no' );
	update_option( 'woocommerce_allow_tracking', 'no' );
	update_option( 'woocommerce_currency', 'USD' );
	update_option( 'woocommerce_default_country', 'US:CA' );
	update_option( 'woocommerce_store_address', '100 Example Street' );
	update_option( 'woocommerce_store_city', 'Sampletown' );
	update_option( 'woocommerce_store_postcode', '94000' );
	update_option( 'woocommerce_calc_taxes', 'no' );
	update_option( 'woocommerce_enable_guest_checkout', 'yes' );
	update_option( 'woocommerce_coming_soon', 'no' );
	// Offline "demo payment": the built-in Check payments gateway, relabelled. No keys, nothing is charged.
	update_option( 'woocommerce_cheque_settings', [ 'enabled' => 'yes', 'title' => 'Demo payment (nothing is charged)', 'description' => 'This is a demo store. Your order is recorded in this sandbox only and no payment is taken.', 'instructions' => 'Demo order: no payment is taken and nothing will ship.' ] );
	foreach ( [ 'bacs', 'cod', 'paypal' ] as $g ) update_option( "woocommerce_{$g}_settings", [ 'enabled' => 'no' ] );
	// Flat-rate shipping for the whole world so checkout totals are realistic.
	if ( class_exists( 'WC_Shipping_Zones' ) ) {
		$zone = new WC_Shipping_Zone( 0 );
		if ( ! $zone->get_shipping_methods() ) {
			$id = $zone->add_shipping_method( 'flat_rate' );
			update_option( "woocommerce_flat_rate_{$id}_settings", [ 'title' => 'Standard shipping', 'cost' => '6', 'tax_status' => 'none' ] );
		}
	}
}
function demo_seed() {
	if ( get_option( 'demo_seeded' ) ) return;
	kses_remove_filters();
	st_wc_baseline();
	if ( function_exists( 'wc_get_product' ) && ! get_option( 'st_test_product' ) ) {
		$p = new WC_Product_Simple();
		$p->set_name( 'Test product' ); $p->set_regular_price( '12' ); $p->set_status( 'publish' );
		update_option( 'st_test_product', $p->save() );
	}
	update_option( 'demo_seeded', 1 );
	flush_rewrite_rules();
}
