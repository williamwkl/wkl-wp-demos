<?php
/**
 * Plugin Name: Saltline structured data
 * Description: LocalBusiness on the home page, TouristTrip entries on the Tours page (JSON-LD). Fictional business.
 */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'sl_tours' ) ) return;
	$home = home_url( '/' );
	$biz  = [
		'@context' => 'https://schema.org', '@type' => 'LocalBusiness', '@id' => $home . '#business',
		'name' => 'Saltline Kayak Tours', 'description' => 'Guided small-group sea kayak tours on the North Shore. Demo business for a portfolio site.',
		'url' => $home, 'email' => 'hello@saltlinekayak.example.com', 'telephone' => '+1-555-010-0100', 'priceRange' => '$65-$110',
		'address' => [ '@type' => 'PostalAddress', 'streetAddress' => 'Marrow Cove public launch', 'addressLocality' => 'Marrow Cove', 'addressRegion' => 'North Shore', 'addressCountry' => 'US' ],
		'openingHours' => 'Mo-Su 06:00-21:00',
	];
	$out = [];
	if ( is_front_page() ) $out[] = $biz;
	if ( is_page( 'tours' ) ) {
		foreach ( sl_tours() as $t ) {
			$out[] = [ '@context' => 'https://schema.org', '@type' => 'TouristTrip', 'name' => $t['name'], 'description' => $t['short'],
				'url' => home_url( '/tours/#' . $t['slug'] ), 'touristType' => 'Beginner and intermediate paddlers',
				'provider' => [ '@id' => $home . '#business', '@type' => 'LocalBusiness', 'name' => 'Saltline Kayak Tours' ],
				'offers' => [ '@type' => 'Offer', 'price' => (string) $t['price'], 'priceCurrency' => 'USD', 'availability' => 'https://schema.org/InStock' ],
				'itinerary' => [ '@type' => 'Place', 'name' => 'Marrow Cove public launch' ] ];
		}
	}
	foreach ( $out as $o ) echo '<script type="application/ld+json">' . wp_json_encode( $o, JSON_UNESCAPED_SLASHES ) . "</script>\n";
} );
