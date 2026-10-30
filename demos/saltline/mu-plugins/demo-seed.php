<?php
/**
 * Plugin Name: Saltline demo seed
 * Description: Builds the demo content (media, pages, form, settings) once. Called from blueprint.json via runPHP.
 */

/** Import a theme asset image into the media library, return attachment ID. */
function sl_media( $file, $alt, $caption ) {
	$existing = get_posts( [ 'post_type' => 'attachment', 'name' => sanitize_title( $file ), 'numberposts' => 1 ] );
	if ( $existing ) return $existing[0]->ID;
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$src  = get_stylesheet_directory() . '/assets/img/' . $file;
	$up   = wp_upload_dir();
	$dest = trailingslashit( $up['path'] ) . $file;
	copy( $src, $dest );
	$id = wp_insert_attachment( [ 'post_mime_type' => 'image/webp', 'post_title' => sanitize_title( $file ), 'post_name' => sanitize_title( $file ), 'post_excerpt' => $caption, 'post_status' => 'inherit' ], $dest );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return $id;
}

/** Core image block markup for an attachment. */
function sl_img( $id, $class = '', $eager = false ) {
	$url  = wp_get_attachment_url( $id );
	$alt  = esc_attr( get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	$attr = [ 'id' => $id, 'sizeSlug' => 'full', 'linkDestination' => 'none' ];
	if ( $class ) $attr['className'] = $class;
	$cls = trim( 'wp-block-image size-full ' . $class );
	$prio = $eager ? ' fetchpriority="high"' : '';
	return '<!-- wp:image ' . wp_json_encode( $attr ) . ' --><figure class="' . $cls . '"><img src="' . esc_url( $url ) . '" alt="' . $alt . '" class="wp-image-' . $id . '"' . $prio . '/></figure><!-- /wp:image -->';
}

function sl_p( $html, $class = '' ) {
	$a = $class ? ' ' . wp_json_encode( [ 'className' => $class ] ) : '';
	$c = $class ? ' class="' . $class . '"' : '';
	return "<!-- wp:paragraph$a --><p$c>$html</p><!-- /wp:paragraph -->";
}
function sl_h( $text, $level = 2, $class = '' ) {
	$attr = [];
	if ( 2 !== $level ) $attr['level'] = $level;
	if ( $class ) $attr['className'] = $class;
	$a = $attr ? ' ' . wp_json_encode( $attr ) : '';
	$cls = trim( 'wp-block-heading ' . $class );
	return "<!-- wp:heading$a --><h$level class=\"$cls\">$text</h$level><!-- /wp:heading -->";
}
function sl_group( $inner, $class = '', $tag = 'div', $anchor = '' ) {
	$attr = [ 'layout' => [ 'type' => 'default' ] ];
	if ( 'div' !== $tag ) $attr['tagName'] = $tag;
	if ( $class ) $attr['className'] = $class;
	if ( $anchor ) $attr['anchor'] = $anchor;
	$id = $anchor ? ' id="' . $anchor . '"' : '';
	$cls = trim( 'wp-block-group ' . $class );
	return '<!-- wp:group ' . wp_json_encode( $attr ) . " --><$tag class=\"$cls\"$id>$inner</$tag><!-- /wp:group -->";
}
function sl_buttons( $buttons, $class = '' ) {
	$out = '';
	foreach ( $buttons as $b ) {
		[ $label, $href, $outline ] = array_pad( $b, 3, false );
		$ba = $outline ? ' {"className":"is-style-outline"}' : '';
		$bc = $outline ? ' is-style-outline' : '';
		$out .= "<!-- wp:button$ba --><div class=\"wp-block-button$bc\"><a class=\"wp-block-button__link wp-element-button\" href=\"$href\">$label</a></div><!-- /wp:button -->";
	}
	$a = $class ? ' ' . wp_json_encode( [ 'className' => $class ] ) : '';
	$c = $class ? ' ' . $class : '';
	return "<!-- wp:buttons$a --><div class=\"wp-block-buttons$c\">$out</div><!-- /wp:buttons -->";
}
function sl_list( $items, $class ) {
	$li = '';
	foreach ( $items as $i ) $li .= "<!-- wp:list-item --><li>$i</li><!-- /wp:list-item -->";
	return '<!-- wp:list ' . wp_json_encode( [ 'className' => $class ] ) . " --><ul class=\"wp-block-list $class\">$li</ul><!-- /wp:list -->";
}
function sl_html( $html ) { return "<!-- wp:html -->$html<!-- /wp:html -->"; }

function sl_tours() {
	return [
		[ 'slug' => 'sunrise-harbor-paddle', 'name' => 'Sunrise Harbor Paddle', 'dur' => '2 hours', 'iso' => 'PT2H', 'level' => 'Easy', 'price' => 65, 'img' => 'sunrise',
			'short' => 'Glassy water, seals on the breakwater, coffee on the beach after.',
			'long' => 'We launch at first light while the harbor is still flat and quiet. Your guide leads a slow loop past the fishing boats and out to the breakwater, where harbor seals haul out on the rocks most mornings. We keep a respectful distance, drift for a while, then paddle back for hot coffee and pastries on the beach. A good first trip for families and anyone new to kayaks.',
			'season' => 'Daily, May to October', 'alt' => 'A harbor seal resting on a mossy rock above dark water' ],
		[ 'slug' => 'sea-cave-explorer', 'name' => 'Sea Cave Explorer', 'dur' => '3.5 hours', 'iso' => 'PT3H30M', 'level' => 'Moderate', 'price' => 110, 'img' => 'cave',
			'short' => 'Paddle into the Hollow Cliffs caves at low tide with a guide who knows every swell.',
			'long' => 'Timed to the low tide, this trip follows the sandstone wall of the Hollow Cliffs to a string of sea caves and arches. Your guide reads the swell at each entrance and only takes the group in when it is calm enough to enjoy. Expect about five miles of paddling with a snack break on a pocket beach. Some paddling fitness helps, no experience required.',
			'season' => 'June to September, tide dependent', 'alt' => 'Kayakers paddling below red sandstone cliffs with sea cave openings' ],
		[ 'slug' => 'night-glow-paddle', 'name' => 'Night Glow Paddle', 'dur' => '2 hours', 'iso' => 'PT2H', 'level' => 'Easy', 'price' => 95, 'img' => 'night',
			'short' => 'Summer nights when the bay lights up with bioluminescence. Blankets and cocoa provided.',
			'long' => 'We push off at dusk as the last light fades over the cove, then paddle into the sheltered inner bay once it is fully dark. On the right summer nights every paddle stroke sparks blue green light in the water. Kayaks carry small lights for safety and your guide stays close the whole time. Back on the beach there are blankets and hot cocoa waiting.',
			'season' => 'July to September, moon dependent', 'alt' => 'A lone kayaker paddling toward a low sun on calm water at dusk' ],
	];
}

function demo_seed() {
	if ( get_option( 'demo_seeded' ) ) return;
	kses_remove_filters(); // seed runs without a user; keep inline SVG in Custom HTML blocks

	$m = [
		'hero'    => sl_media( 'hero-560.webp', 'A kayaker in a red and white sea kayak paddling along a rocky shoreline, seen from behind', 'Photo: Kayaking in Brofjorden at Govik, CC0, Wikimedia Commons' ),
		'sunrise' => sl_media( 'sunrise-900.webp', 'A harbor seal resting on a mossy rock above dark water', 'Photo: Harbor seal on the rocks, public domain (NPS), Wikimedia Commons' ),
		'cave'    => sl_media( 'cave-900.webp', 'Kayakers paddling below red sandstone cliffs with sea cave openings', 'Photo: Sea caves on Lake Superior, CC0, Wikimedia Commons' ),
		'night'   => sl_media( 'night-900.webp', 'A lone kayaker paddling toward a low sun on calm water at dusk', 'Photo: Sunset kayak (Unsplash), CC0, Wikimedia Commons' ),
		'lesson'  => sl_media( 'lesson-900.webp', 'A paddler settling into a sea kayak at the water edge before setting off', 'Photo: Getting into the kayak in Govik, CC0, Wikimedia Commons' ),
		'shore'   => sl_media( 'shore-1200.webp', 'A kayaker on calm open water near a rocky point in early morning light', 'Photo: Kayak sunset bay, Oregon, public domain, Wikimedia Commons' ),
		'gear'    => sl_media( 'gear-1200.webp', 'Touring kayaks pulled up side by side on a quiet beach', 'Photo: Kayaks beached on Yellowstone Lake, public domain (NPS), Wikimedia Commons' ),
	];
	$tours = sl_tours();

	/* ---------- Home ---------- */
	$tour_cards = '';
	foreach ( $tours as $t ) {
		$tour_cards .= sl_group(
			sl_img( $m[ $t['img'] ] ) .
			sl_h( $t['name'], 3 ) .
			sl_p( "<span>{$t['dur']}</span><span>{$t['level']}</span><span class=\"price\">\${$t['price']} per person</span>", 'meta' ) .
			sl_p( $t['short'] ) .
			sl_p( "<a href=\"/tours/#{$t['slug']}\">Check dates<span class=\"screen-reader-text\"> for {$t['name']}</span></a>" ),
			'tour', 'article'
		);
	}
	$icons = [
		'star'  => '<path d="M12 2l3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z" stroke-linejoin="round"/>',
		'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="17" cy="9" r="3"/><path d="M16 14.2c3.2.3 5.5 2.5 5.5 5.8"/>',
		'award' => '<circle cx="12" cy="9" r="6"/><path d="M8.5 14.5L7 22l5-3 5 3-1.5-7.5"/>',
		'box'   => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z" stroke-linejoin="round"/><path d="M3 7l9 4 9-4M12 11v10"/>',
	];
	$svg = fn( $k ) => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">' . $icons[ $k ] . '</svg>';
	$home =
		sl_group( sl_group(
			sl_group(
				sl_p( 'Guided sea kayak tours, Marrow Cove', 'eyebrow' ) .
				sl_h( 'Paddle the coast at its own pace.', 1 ) .
				sl_p( 'Small-group tours along the North Shore, led by guides who grew up on this water. No experience needed, every bit of gear included.', 'lead' ) .
				sl_buttons( [ [ 'Book a tour', '/contact/' ], [ 'See the three tours', '#tours', true ] ], 'hero-actions' ),
				'hero-copy'
			) . sl_img( $m['hero'], 'hero-photo', true ),
			'wrap hero-grid'
		), 'hero', 'section' ) .
		sl_group( sl_group(
			sl_html( '<div class="strip-grid"><div><p class="lbl">Tide</p><p class="val">High 7:42 am</p></div><div><p class="lbl">Water</p><p class="val">58 F</p></div><div><p class="lbl">Wind</p><p class="val">6 kt NW</p></div><div><p class="lbl">Sunrise</p><p class="val">6:51 am</p></div></div>' ) .
			sl_p( 'Today at the boathouse. Sample data for the demo.', 'strip-note' ),
			'wrap'
		), 'strip', 'section', 'conditions' ) .
		sl_group( sl_html( '<ul class="proof" aria-label="Why paddle with us">'
			. '<li>' . $svg( 'star' ) . '<span><strong>4.9 average</strong> from 312 reviews (sample)</span></li>'
			. '<li>' . $svg( 'users' ) . '<span><strong>Small groups,</strong> 8 paddlers max</span></li>'
			. '<li>' . $svg( 'award' ) . '<span><strong>Certified</strong> sea kayak guides</span></li>'
			. '<li>' . $svg( 'box' ) . '<span><strong>Dry bags, spray skirts</strong> and wetsuits included</span></li></ul>' ), 'wrap' ) .
		sl_group( sl_group(
			sl_group( sl_group( sl_p( 'Tours', 'eyebrow' ) . sl_h( 'Three tours, one coast.' ) ) . sl_p( '<a href="/tours/">Compare all three</a>' ), 'sec-head' ) .
			sl_group( $tour_cards, 'tours' ),
			'wrap'
		), 'section tours-sec', 'section', 'tours' ) .
		sl_group( sl_group(
			sl_img( $m['lesson'] ) .
			sl_group(
				sl_h( 'Never kayaked? Good, most of our guests haven\'t.' ) .
				sl_p( 'Every tour starts with a 20-minute lesson on the beach. You will paddle a wide touring kayak that is hard to tip, with a guide close enough to talk to the whole way. Most people stop thinking about balance within ten minutes and start looking at the cliffs.' ) .
				sl_list( [ '1 guide for every 4 paddlers', 'Wetsuits for every size, kids 8 and up', 'Free reschedule if the forecast turns' ], 'facts' )
			),
			'wrap split'
		), 'section split-sec', 'section' ) .
		sl_group( sl_group(
			'<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p>"My 9-year-old asked to go again before we even got the boats out of the water."</p><!-- /wp:paragraph --><cite>Priya R., Sunrise Harbor Paddle (sample review)</cite></blockquote><!-- /wp:quote -->' .
			'<!-- wp:quote --><blockquote class="wp-block-quote"><!-- wp:paragraph --><p>"Jonah read the swell like a weather report and got us into the far cave. Unreal."</p><!-- /wp:paragraph --><cite>Marcus T., Sea Cave Explorer (sample review)</cite></blockquote><!-- /wp:quote -->',
			'wrap quotes'
		), 'section sand2', 'section' ) .
		sl_cta_band();

	/* ---------- Tours ---------- */
	$rows = '';
	foreach ( $tours as $i => $t ) {
		$rows .= sl_group(
			sl_img( $m[ $t['img'] ] ) .
			sl_group(
				sl_h( $t['name'] ) .
				sl_p( $t['long'] ) .
				sl_html( "<dl class=\"tour-facts\"><dt>Duration</dt><dd>{$t['dur']}</dd><dt>Level</dt><dd>{$t['level']}</dd><dt>Group size</dt><dd>Up to 8 paddlers</dd><dt>Price</dt><dd>\${$t['price']} per person</dd><dt>Included</dt><dd>Kayak, paddle, spray skirt, wetsuit, dry bag, guide</dd><dt>Season</dt><dd>{$t['season']}</dd></dl>" ) .
				sl_buttons( [ [ 'Book this tour<span class="screen-reader-text">: ' . $t['name'] . '</span>', '/contact/?tour=' . $t['slug'] ] ] )
			),
			'tour-row' . ( $i % 2 ? ' flip' : '' ), 'article', $t['slug']
		);
	}
	$tours_page =
		sl_group( sl_group( sl_p( 'Tours', 'eyebrow' ) . sl_h( 'Three ways to see the North Shore.', 1 ) . sl_p( 'Every tour is guided, small, and runs from the boathouse at Marrow Cove. Pick the one that fits your morning, or your night.', 'lead' ), 'wrap' ), 'page-hero', 'section' ) .
		sl_group( sl_group( $rows, 'wrap' ), 'tour-list', 'section' ) .
		sl_cta_band();

	/* ---------- About ---------- */
	$about =
		sl_group( sl_group(
			sl_group( sl_p( 'About Saltline', 'eyebrow' ) . sl_h( 'Two guides, one boathouse, fifteen seasons.', 1 ) . sl_p( 'Saltline started with two borrowed kayaks and a hand-painted sign at the Marrow Cove launch. It is still small on purpose.', 'lead' ), 'hero-copy' ) .
			sl_img( $m['shore'], 'ratio-32', true ),
			'wrap two-col'
		), 'page-hero', 'section' ) .
		sl_group( sl_group(
			sl_h( 'How it started' ) .
			sl_p( 'Jonah and Lise grew up a mile apart on the North Shore and spent their teenage summers guiding for other outfitters. In the first season they ran one tour a day and answered the phone from the beach. Fifteen seasons later there are six guides and a proper boathouse, but every tour still leaves from the same launch, and one of the two founders still leads most of the sea cave trips.' ) .
			sl_p( 'We keep groups small because the coast is better that way. Eight paddlers is the most we take on any trip, with one guide for every four people.' ) .
			sl_h( 'Certified, and still training' ) .
			sl_list( [ 'Every guide holds a current sea kayak leader certification', 'Wilderness first aid and CPR renewed every two years', 'Annual rescue drills before the season opens', 'Local knowledge of tides, swell and wildlife rules on the North Shore' ], 'cert-list' ),
			'wrap prose'
		), 'section', 'section' ) .
		sl_group( sl_group(
			sl_img( $m['gear'], 'ratio-32' ) .
			sl_group(
				sl_h( 'Safety is the first thing we pack.' ) .
				sl_p( 'Every boat carries a pump, a paddle float and a spare paddle. Guides carry a radio, a first aid kit and a tow line, and we check the marine forecast twice before every launch. If the conditions are not right, we reschedule you for free.' ) .
				sl_list( [ 'Stable touring kayaks, checked every morning', 'Life jackets and wetsuits fitted on the beach', 'Weather call made 2 hours before launch' ], 'facts' )
			),
			'wrap split'
		), 'section sand2', 'section', 'safety' ) .
		sl_cta_band();

	/* ---------- FAQ ---------- */
	$faqs = [
		[ 'Do I need kayaking experience?', 'No. Most guests have never paddled before. Every tour starts with a 20-minute lesson on the beach and our touring kayaks are wide and stable.' ],
		[ 'Is there a minimum age?', 'Kids 8 and up are welcome when paddling with an adult in a double kayak. Paddlers 14 and up can take a single kayak.' ],
		[ 'What should I wear?', 'Swimwear or quick-dry layers under the wetsuit we provide, plus shoes that can get wet. Bring a towel, a warm layer for after, and sunglasses with a strap.' ],
		[ 'What is included?', 'Kayak, paddle, spray skirt, wetsuit, life jacket, dry bag and a certified guide. Coffee on the sunrise tour and cocoa on the night tour.' ],
		[ 'What happens with bad weather or cancellations?', 'You can reschedule for free up to 24 hours before your tour. If we cancel for weather, you choose a full refund or a new date.', 'weather-and-cancellations' ],
		[ 'Where do I park?', 'Free parking at the Marrow Cove public launch lot, about a two minute walk from the boathouse.' ],
		[ 'Are there restrooms?', 'Yes, there are restrooms and changing rooms at the boathouse, open from 30 minutes before the first tour.' ],
		[ 'Can I bring my phone or camera?', 'Yes, in the dry bag we provide. Your guide also shoots photos on every tour and shares them free within 48 hours.' ],
		[ 'Do you take group bookings?', 'Yes. We run private tours for groups of 6 to 16, including birthdays and team days. Send us a request with your dates.' ],
		[ 'Do you sell gift cards?', 'Yes. Gift cards are available for any tour and never expire. Ask through the contact form and we will send one by email.', 'gift-cards' ],
	];
	$details = '';
	foreach ( $faqs as $f ) {
		$anchor = $f[2] ?? '';
		$a = $anchor ? ' ' . wp_json_encode( [ 'anchor' => $anchor ] ) : '';
		$id = $anchor ? ' id="' . $anchor . '"' : '';
		$details .= "<!-- wp:details$a --><details class=\"wp-block-details\"$id><summary>{$f[0]}</summary>" . sl_p( $f[1] ) . '</details><!-- /wp:details -->';
	}
	$faq_page =
		sl_group( sl_group( sl_p( 'FAQ', 'eyebrow' ) . sl_h( 'Questions before you paddle.', 1 ) . sl_p( 'The short answers to what people ask us most. If yours is not here, send us a note.', 'lead' ), 'wrap' ), 'page-hero', 'section' ) .
		sl_group( sl_group(
			sl_group( $details, 'faq-list' ) .
			sl_group( sl_h( 'Still have a question?' ) . sl_p( 'Write to us and a guide will reply within one business day.' ) . sl_p( '<a href="mailto:hello@saltlinekayak.example.com">hello@saltlinekayak.example.com</a>' ) . sl_buttons( [ [ 'Send a message', '/contact/' ] ] ), 'aside-box', 'aside' ),
			'wrap two-col'
		), 'section', 'section' );

	/* ---------- Contact ---------- */
	$form_id = sl_form();
	$contact =
		sl_group( sl_group( sl_p( 'Contact and booking', 'eyebrow' ) . sl_h( 'Request a tour.', 1 ) . sl_p( 'Tell us the tour and date you have in mind. We confirm availability by email, usually the same day.', 'lead' ), 'wrap' ), 'page-hero', 'section' ) .
		sl_group( sl_group(
			sl_group( sl_p( '<strong>Demo form.</strong> This is a portfolio sample: submissions are checked in your browser and nothing is sent.', 'demo-note' ) . "<!-- wp:shortcode -->[contact-form-7 id=\"$form_id\" title=\"Booking request\"]<!-- /wp:shortcode -->", 'form-col' ) .
			sl_group(
				sl_h( 'Meeting point', 2 ) .
				sl_p( 'The boathouse at the Marrow Cove public launch, North Shore. Arrive 15 minutes before your tour.' ) .
				sl_html( '<div class="map-ph" role="img" aria-label="Map placeholder showing the Marrow Cove public launch"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s7-6.2 7-12a7 7 0 10-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/></svg><span>Map placeholder</span></div>' ) .
				sl_h( 'Hours', 3 ) .
				sl_p( 'Boathouse open 6 am to 9 pm, May to October.<br>Office replies Monday to Saturday.' ) .
				sl_h( 'Reach us', 3 ) .
				sl_p( '<a href="mailto:hello@saltlinekayak.example.com">hello@saltlinekayak.example.com</a><br>(555) 010-0100' ),
				'aside-box', 'aside'
			),
			'wrap two-col'
		), 'section contact-sec', 'section' );

	$pages = [
		'home'    => [ 'Home', $home, 'Guided sea kayak tours on the North Shore: small groups, certified guides, all gear included. Sunrise, sea cave and night glow paddles from Marrow Cove.' ],
		'tours'   => [ 'Tours', $tours_page, 'Compare the three Saltline tours: Sunrise Harbor Paddle, Sea Cave Explorer and Night Glow Paddle. Prices, durations, levels and seasons.' ],
		'about'   => [ 'About', $about, 'Saltline is a guide-owned sea kayak outfitter at Marrow Cove. Meet the founders, our certifications and how we keep every trip safe.' ],
		'faq'     => [ 'FAQ', $faq_page, 'Answers about experience, age limits, what to wear, weather cancellations, parking, photos, group bookings and gift cards.' ],
		'contact' => [ 'Contact', $contact, 'Request a guided kayak tour at Marrow Cove. Pick a tour and date and we will confirm availability by email.' ],
	];
	$ids = [];
	foreach ( $pages as $slug => [ $title, $content, $desc ] ) {
		$ids[ $slug ] = wp_insert_post( wp_slash( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content ] ) );
		update_post_meta( $ids[ $slug ], '_yoast_wpseo_metadesc', $desc );
	}
	update_post_meta( $ids['home'], '_yoast_wpseo_title', 'Saltline Kayak Tours | Guided sea kayak tours in Marrow Cove' );

	// Remove default content.
	foreach ( [ 'hello-world', 'sample-page', 'privacy-policy' ] as $s ) {
		$p = get_page_by_path( $s, OBJECT, [ 'post', 'page' ] );
		if ( $p ) wp_delete_post( $p->ID, true );
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids['home'] );
	update_option( 'wpseo_titles', array_merge( (array) get_option( 'wpseo_titles' ), [ 'company_or_person' => 'company', 'company_name' => 'Saltline Kayak Tours', 'website_name' => 'Saltline Kayak Tours', 'separator' => 'sc-pipe' ] ) );
	update_option( 'blog_public', 1 );
	update_option( 'demo_seeded', 1 );
	flush_rewrite_rules();
}

function sl_cta_band() {
	return sl_group( sl_group(
		sl_p( 'Plan your trip', 'eyebrow' ) .
		sl_h( 'Plan your morning on the water.' ) .
		sl_html( '<div class="mini-faq"><div><h3>What to wear</h3><p>Swimwear or quick-dry layers under the wetsuit we give you. Bring a towel and sunglasses with a strap.</p></div><div><h3>Cancellations</h3><p>Free changes up to 24 hours before. If we call it off for weather, you get a full refund or a new date.</p></div><div><h3>Where to meet</h3><p>The boathouse at the Marrow Cove public launch, 15 minutes before your tour. Free parking.</p></div></div>' ) .
		sl_buttons( [ [ 'Check availability', '/contact/' ], [ 'Read all FAQs', '/faq/', true ] ], 'hero-actions' ),
		'wrap'
	), 'section dark', 'section' );
}

/** Contact Form 7 booking form. Labels wrap every field (accessible names). */
function sl_form() {
	$body = <<<'FORM'
<p><label for="bk-name">Full name</label>[text* your-name id:bk-name autocomplete:name]</p>
<p><label for="bk-email">Email</label>[email* your-email id:bk-email autocomplete:email]</p>
<p><label for="bk-tour">Tour</label>[select* tour id:bk-tour first_as_label "Choose a tour" "Sunrise Harbor Paddle" "Sea Cave Explorer" "Night Glow Paddle" "Private group tour"]</p>
<div class="row2"><p><label for="bk-date">Preferred date</label>[date* tour-date id:bk-date]</p>
<p><label for="bk-size">Group size</label>[number* group-size id:bk-size min:1 max:16 "2"]</p></div>
<p><label for="bk-msg">Message <span class="hint">(optional)</span></label>[textarea your-message id:bk-msg x4]</p>
<p>[acceptance consent] I agree to be contacted by email about this request. [/acceptance]</p>
<p>[submit "Send booking request"]</p>
FORM;
	$id = wp_insert_post( [ 'post_type' => 'wpcf7_contact_form', 'post_status' => 'publish', 'post_title' => 'Booking request' ] );
	update_post_meta( $id, '_form', $body );
	update_post_meta( $id, '_mail', [ 'active' => true, 'subject' => 'Booking request: [tour]', 'sender' => 'Saltline <wordpress@saltlinekayak.example.com>', 'recipient' => 'hello@saltlinekayak.example.com', 'body' => "From: [your-name] <[your-email]>\nTour: [tour]\nDate: [tour-date]\nGroup: [group-size]\n\n[your-message]", 'additional_headers' => 'Reply-To: [your-email]', 'attachments' => '', 'use_html' => false, 'exclude_blank' => false ] );
	update_post_meta( $id, '_messages', [] );
	update_post_meta( $id, '_additional_settings', '' );
	update_post_meta( $id, '_locale', 'en_US' );
	update_post_meta( $id, '_hash', wp_hash( $id ) );
	return $id;
}
