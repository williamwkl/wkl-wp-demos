<?php
/** Plugin Name: Harbor & Pine structured data (LegalService, fictional business). */
add_action( 'wp_head', function () {
	if ( ! is_front_page() && ! is_page( 'contact' ) ) return;
	$o = [ '@context' => 'https://schema.org', '@type' => 'LegalService', 'name' => 'Harbor & Pine Law', 'description' => 'Personal injury and family law firm. Demo business for a portfolio site.',
		'url' => home_url( '/' ), 'email' => 'intake@harborpinelaw.example.com', 'telephone' => '+1-555-010-0100',
		'address' => [ '@type' => 'PostalAddress', 'addressLocality' => 'Harborview', 'addressCountry' => 'US' ],
		'openingHours' => 'Mo-Fr 08:00-18:00', 'areaServed' => 'Harborview',
		'knowsAbout' => [ 'Personal injury law', 'Family law', 'Divorce', 'Child custody' ] ];
	echo '<script type="application/ld+json">' . wp_json_encode( $o, JSON_UNESCAPED_SLASHES ) . "</script>\n";
} );
