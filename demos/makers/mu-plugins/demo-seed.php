<?php
/**
 * Plugin Name: Fernwood Makers demo seed
 * Description: Imports the sample CSV through the same importer a client would use (fw_import_csv), adds media and pages. Run once from the blueprint.
 */

function fw_seed_credits() {
	$out = [];
	$file = WP_CONTENT_DIR . '/demo-data/image-credits.csv';
	if ( is_readable( $file ) && ( $fh = fopen( $file, 'r' ) ) ) {
		fgetcsv( $fh, 0, ',', '"', '' );
		while ( ( $r = fgetcsv( $fh, 0, ',', '"', '' ) ) !== false ) $out[ $r[0] ] = $r[1] . ', ' . $r[2];
		fclose( $fh );
	}
	return $out;
}
function fw_seed_media( $key ) {
	static $cache = [];
	if ( isset( $cache[ $key ] ) ) return $cache[ $key ];
	$credits = fw_seed_credits() + [
		'wheel' => 'Pottery wheel (Unsplash), CC0', 'mug' => 'Bucharest mug (Unsplash), CC0', 'wood' => 'Woodworking workshop (Unsplash), CC0',
		'yarn' => 'Ball of orange yarn (Unsplash), CC0', 'paper' => 'Stationery on a bench (Unsplash), CC0', 'paper2' => 'Stationery and flowers flatlay (Unsplash), CC0',
		'honey' => 'Honey and Clementines (Unsplash), CC0', 'bread' => 'Artisan Loaves of Bread (Unsplash), CC0', 'jewel' => 'Jeff White Custom Jewelry (Unsplash), CC0, cropped',
		'wheel-portrait' => 'Pottery wheel (Unsplash), CC0',
	];
	$alts = [ 'feature-portrait' => 'Clay-covered hands shaping a pot on a pottery wheel', 'wheel' => 'Clay-covered hands shaping a pot on a spinning wheel', 'wheel-portrait' => 'Hands shaping a stoneware pot on a pottery wheel', 'mug' => 'A striped ceramic mug on a table' ];
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$file = $key . '-800.webp';
	$src  = get_stylesheet_directory() . '/assets/img/' . $file;
	if ( ! is_readable( $src ) ) return $cache[ $key ] = 0;
	$up = wp_upload_dir();
	$dest = trailingslashit( $up['path'] ) . $file;
	copy( $src, $dest );
	$id = wp_insert_attachment( [ 'post_mime_type' => 'image/webp', 'post_title' => $key, 'post_excerpt' => 'Photo: ' . ( $credits[ $key ] ?? '' ) . ', Wikimedia Commons', 'post_status' => 'inherit' ], $dest );
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $alts[ $key ] ?? '' );
	return $cache[ $key ] = $id;
}

function fw_page( $slug, $title, $content, $desc ) {
	$id = wp_insert_post( wp_slash( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content ] ) );
	update_post_meta( $id, '_yoast_wpseo_metadesc', $desc );
	return $id;
}
function fw_page_hero( $eyebrow, $h1, $lead ) {
	return '<!-- wp:group {"tagName":"section","className":"page-hero","layout":{"type":"default"}} --><section class="wp-block-group page-hero"><!-- wp:group {"className":"wrap","layout":{"type":"default"}} --><div class="wp-block-group wrap">'
		. '<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">' . $eyebrow . '</p><!-- /wp:paragraph -->'
		. '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">' . $h1 . '</h1><!-- /wp:heading -->'
		. '<!-- wp:paragraph {"className":"lead"} --><p class="lead">' . $lead . '</p><!-- /wp:paragraph --></div><!-- /wp:group --></section><!-- /wp:group -->';
}
function fw_section( $inner, $class = 'section' ) {
	return '<!-- wp:group {"tagName":"section","className":"' . $class . '","layout":{"type":"default"}} --><section class="wp-block-group ' . $class . '"><!-- wp:group {"className":"wrap","layout":{"type":"default"}} --><div class="wp-block-group wrap">' . $inner . '</div><!-- /wp:group --></section><!-- /wp:group -->';
}

function demo_seed() {
	if ( get_option( 'demo_seeded' ) ) return;
	kses_remove_filters();

	// 1. Import listings from the sample CSV exactly as a client sheet would be imported.
	$report = fw_import_csv( WP_CONTENT_DIR . '/demo-data/makers-sample.csv', fn( $key ) => fw_seed_media( $key ) );
	update_option( 'fw_last_import', $report );
	// Spread publish dates so "Newest" sorting is meaningful.
	$i = 0;
	foreach ( get_posts( [ 'post_type' => 'maker', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC' ] ) as $p ) {
		wp_update_post( [ 'ID' => $p->ID, 'menu_order' => $i, 'post_date' => gmdate( 'Y-m-d H:i:s', strtotime( '2026-10-01' ) - ( ( $i * 37 ) % 90 ) * DAY_IN_SECONDS ) ] );
		$i++;
	}
	fw_write_index();
	$wheel = fw_seed_media( 'feature-portrait' );
	// Extra gallery photos for small categories, so a listing gallery never repeats a photo shown on the same page.
	update_option( 'fw_gallery_override', [ 'bramblewood-preserves' => [ fw_seed_media( 'food-50' ), fw_seed_media( 'food-52' ) ] ] );
	update_option( 'fw_gallery_extras', [ 'print-and-paper' => [ fw_seed_media( 'print-08' ), fw_seed_media( 'print-15' ) ] ] );
	$wheel_url = wp_get_attachment_url( $wheel );

	$underline = '<svg viewBox="0 0 200 12" preserveAspectRatio="none" aria-hidden="true"><path d="M2 8 C 50 2, 150 2, 198 7" stroke="currentColor" stroke-width="5" fill="none" stroke-linecap="round"/></svg>';
	$search = '<form class="search" role="search" action="/makers/" method="get"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg><label class="screen-reader-text" for="hero-q">Search makers</label><input id="hero-q" type="search" name="q" placeholder="Mugs, Coldwater, under $50" autocomplete="off"><button class="btn btn-dark" type="submit"><span>Search</span><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button></form>';

	/* ---------- Home ---------- */
	$home =
		'<!-- wp:group {"tagName":"section","className":"hero","layout":{"type":"default"}} --><section class="wp-block-group hero"><!-- wp:group {"className":"wrap","layout":{"type":"default"}} --><div class="wp-block-group wrap">'
		. '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Find the people who <span class="u">make' . $underline . '</span> things in Fernwood County.</h1><!-- /wp:heading -->'
		. '<!-- wp:paragraph {"className":"lead"} --><p class="lead">[fernwood_count] independent makers across [fernwood_count of="towns"] towns. Search by craft, town or price, then visit the studio or order direct.</p><!-- /wp:paragraph -->'
		. '<!-- wp:html -->' . $search . '<!-- /wp:html -->'
		. '<!-- wp:shortcode -->[fernwood_chips]<!-- /wp:shortcode -->'
		. '</div><!-- /wp:group --></section><!-- /wp:group -->'
		. fw_section( '<!-- wp:heading --><h2 class="wp-block-heading">Browse the directory.</h2><!-- /wp:heading --><!-- wp:shortcode -->[fernwood_directory per_page="6" search="0" more="1"]<!-- /wp:shortcode -->', 'section browse-sec' )
		. fw_section( '<!-- wp:group {"className":"feat","layout":{"type":"default"}} --><div class="wp-block-group feat"><!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group">'
			. '<!-- wp:paragraph {"className":"eyebrow"} --><p class="eyebrow">Maker of the month</p><!-- /wp:paragraph -->'
			. '<!-- wp:heading --><h2 class="wp-block-heading">“I throw about forty mugs a week. Maybe six make it to the shelf.”</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph {"className":"who"} --><p class="who">Theo Marchetti, Coldwater Clay</p><!-- /wp:paragraph -->'
			. '<!-- wp:html --><div class="actions"><a class="btn btn-primary" href="/makers/coldwater-clay/">Visit Coldwater Clay</a><a class="btn btn-secondary" href="/makers/coldwater-clay/#main">Read the interview</a></div><!-- /wp:html -->'
			. '</div><!-- /wp:group -->'
			. '<!-- wp:image {"id":' . $wheel . ',"sizeSlug":"large","linkDestination":"none","className":"feat-photo"} --><figure class="wp-block-image size-large feat-photo"><img src="' . esc_url( $wheel_url ) . '" alt="Hands shaping a stoneware pot on a pottery wheel" class="wp-image-' . $wheel . '"/></figure><!-- /wp:image -->'
			. '</div><!-- /wp:group -->', 'section dark' )
		. fw_section( '<!-- wp:heading --><h2 class="wp-block-heading">Nine towns, one directory.</h2><!-- /wp:heading --><!-- wp:shortcode -->[fernwood_towns]<!-- /wp:shortcode -->', 'section sand' )
		. fw_section( '<!-- wp:group {"className":"formakers","layout":{"type":"default"}} --><div class="wp-block-group formakers"><!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group">'
			. '<!-- wp:heading --><h2 class="wp-block-heading">Make things in Fernwood? Get listed for free.</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>Listings are free and take about ten minutes. We review every one by hand within a week, and we never take a commission on your sales.</p><!-- /wp:paragraph -->'
			. '<!-- wp:html --><div class="actions"><a class="btn btn-primary" href="/for-makers/">List your work</a><a href="/for-makers/#guidelines">Listing guidelines</a></div><!-- /wp:html -->'
			. '</div><!-- /wp:group -->'
			. '<!-- wp:html --><div class="preview" aria-label="Example listing card"><article class="card"><div class="card-img"><span>Your studio here</span></div><div class="body"><p class="meta"><span class="dot" aria-hidden="true"></span>Your craft · Your town</p><p class="card-title">Your studio name</p><p class="by">Your name</p><div class="foot"><span class="price">$$<span class="off">$$</span></span><span class="tag">Open studio</span></div></div></article></div><!-- /wp:html -->'
			. '</div><!-- /wp:group -->' );

	/* ---------- Index pages ---------- */
	$categories = fw_page_hero( 'Categories', 'Every craft in the county.', 'Pick a category to see every maker who works in it, then narrow by town or price.' ) . fw_section( '<!-- wp:shortcode -->[fernwood_terms tax="craft"]<!-- /wp:shortcode -->', 'section no-top' );
	$towns = fw_page_hero( 'Towns', 'Nine towns, one directory.', 'Find makers close to home, or plan a studio day out across the county.' ) . fw_section( '<!-- wp:shortcode -->[fernwood_terms tax="town"]<!-- /wp:shortcode -->', 'section no-top' );
	$events = fw_page_hero( 'Events', 'Open studios and markets.', 'Sample events for the demo. Dates and places are fictional.' ) . fw_section( '<!-- wp:html --><ul class="events">'
		. '<li><p class="date"><span>Nov</span>8</p><div><h2>Coldwater open kiln night</h2><p>Watch a kiln opening at the Harlow Clay Collective and meet four potters. Free, all ages.</p></div></li>'
		. '<li><p class="date"><span>Nov</span>15</p><div><h2>Alder Creek Saturday market</h2><p>Twenty makers under one roof at the Alder Creek grange hall, 9 am to 2 pm.</p></div></li>'
		. '<li><p class="date"><span>Dec</span>6</p><div><h2>County winter fair</h2><p>The big one: sixty stalls, hot cider and a kids\' workshop table at the Fallowfield fairground.</p></div></li>'
		. '</ul><!-- /wp:html -->', 'section no-top' );
	$crafts_opts = ''; foreach ( fw_terms( 'craft' ) as $t ) $crafts_opts .= '<option>' . esc_html( $t->name ) . '</option>';
	$towns_opts = ''; foreach ( fw_terms( 'town' ) as $t ) $towns_opts .= '<option>' . esc_html( $t->name ) . '</option>';
	$formakers = fw_page_hero( 'For makers', 'List your work for free.', 'The directory is free for makers and free for visitors. We check every listing by hand so it stays useful.' )
		. fw_section( '<!-- wp:group {"className":"prose","layout":{"type":"default"}} --><div class="wp-block-group prose">'
			. '<!-- wp:heading {"anchor":"guidelines"} --><h2 class="wp-block-heading" id="guidelines">Who can be listed</h2><!-- /wp:heading -->'
			. '<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>Your work is made in Fernwood County.</li><!-- /wp:list-item --><!-- wp:list-item --><li>It is made by the person or small team listed, not resold.</li><!-- /wp:list-item --><!-- wp:list-item --><li>You sell to the public, online, at markets or from a studio.</li><!-- /wp:list-item --></ul><!-- /wp:list -->'
			. '<!-- wp:heading --><h2 class="wp-block-heading">Submit a listing</h2><!-- /wp:heading -->'
			. '<!-- wp:html --><form class="demo-form" action="/demo-form/" method="get" data-success="Thanks. We review every listing by hand and will email you within a week."><p class="demo-note">Demo form: checks your entries in the browser and sends nothing.</p>'
			. '<p><label for="m-biz">Business name</label><input id="m-biz" name="business" required autocomplete="organization"></p>'
			. '<p><label for="m-name">Maker name</label><input id="m-name" name="maker" required autocomplete="name"></p>'
			. '<p><label for="m-email">Email</label><input id="m-email" name="email" type="email" required autocomplete="email"></p>'
			. '<p><label for="m-craft">Category</label><select id="m-craft" name="craft" required><option value="">Choose a category</option>' . $crafts_opts . '</select></p>'
			. '<p><label for="m-town">Town</label><select id="m-town" name="town" required><option value="">Choose a town</option>' . $towns_opts . '</select></p>'
			. '<p><label for="m-price">Typical price</label><select id="m-price" name="price" required><option value="">Choose a price band</option><option>$ Under $25</option><option>$$ $25 to $75</option><option>$$$ $75 to $200</option><option>$$$$ $200 and up</option></select></p>'
			. '<p><label for="m-desc">Describe your work <span class="hint">(two or three sentences)</span></label><textarea id="m-desc" name="description" rows="4" required></textarea></p>'
			. '<p><label for="m-web">Website or shop link <span class="hint">(optional)</span></label><input id="m-web" name="website" type="url" placeholder="https://" autocomplete="url"></p>'
			. '<fieldset class="uploads-field"><legend>Photos <span class="hint">(up to 4)</span></legend><label class="screen-reader-text" for="m-photos">Upload photos</label><input id="m-photos" name="photos" type="file" accept="image/*" multiple><div class="uploads" aria-hidden="true"><span>+</span><span>+</span><span>+</span><span>+</span></div></fieldset>'
			. '<p><label class="check"><input type="checkbox" name="consent" required> I confirm the work is made by me in Fernwood County and I am happy for it to be listed.</label></p>'
			. '<button class="btn btn-primary" type="submit">Submit my listing</button></form><!-- /wp:html -->'
			. '</div><!-- /wp:group -->', 'section no-top' );
	$about = fw_page_hero( 'About', 'A directory, kept by hand.', 'Fernwood Makers is a sample community directory built as a portfolio demo. Everything below explains how it works.' )
		. fw_section( '<!-- wp:group {"className":"prose","layout":{"type":"default"}} --><div class="wp-block-group prose">'
			. '<!-- wp:heading {"anchor":"import"} --><h2 class="wp-block-heading" id="import">How listings are updated</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>The library team keeps the master list in a shared spreadsheet. When it changes, they publish the sheet as CSV and run one import. Listings are matched by business name, so existing makers are updated in place and new rows become new listings. Photos, categories and towns come across with each row.</p><!-- /wp:paragraph -->'
			. '<!-- wp:paragraph --><p>Each listing is a WordPress "Maker" with a category, a town, a price band and an open-studio flag. The search and filters on this site run in your browser from a small index file, so they also work on a fast static copy of the site.</p><!-- /wp:paragraph -->'
			. '<!-- wp:heading {"anchor":"accessibility"} --><h2 class="wp-block-heading" id="accessibility">Accessibility</h2><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>The site aims for WCAG 2.2 AA: readable contrast, full keyboard access with visible focus, labelled form fields, a skip link, and filter results announced to screen readers. Without JavaScript every listing still shows and the filter form still submits.</p><!-- /wp:paragraph -->'
			. '</div><!-- /wp:group -->', 'section no-top' );

	$ids = [];
	$ids['home'] = fw_page( 'home', 'Home', $home, 'Find independent makers in Fernwood County: potters, woodworkers, weavers, bakers, printers and jewelers. Search by craft, town or price.' );
	fw_page( 'categories', 'Categories', $categories, 'Browse Fernwood makers by craft: ceramics, woodwork, textiles, jewelry, food and drink, print and paper.' );
	fw_page( 'towns', 'Towns', $towns, 'Browse Fernwood makers by town across the nine towns of Fernwood County.' );
	fw_page( 'events', 'Events', $events, 'Open studios, markets and fairs for Fernwood County makers (sample events).' );
	fw_page( 'for-makers', 'For makers', $formakers, 'List your handmade work in the Fernwood Makers directory for free. Criteria and submission form.' );
	fw_page( 'demo-form', 'Demo form', fw_page_hero( 'Demo form', 'This is a demo form.', 'This sample site has no server behind its forms, so nothing was sent. On a live site the directory team would reply by email within a week.' ), 'Demo form notice.' );
	fw_page( 'credits', 'Photo credits', fw_page_hero( 'Credits', 'Photo credits', 'All photos are CC0 or public domain images from Wikimedia Commons, cropped to 4:3. The full list with source file names is in the project repository. Bricolage Grotesque is licensed under the SIL Open Font License.' ), 'Photo credits for the Fernwood Makers sample site.' );
	fw_page( 'about', 'About', $about, 'How the Fernwood Makers directory works, including how listings are imported from a shared spreadsheet.' );
	update_post_meta( $ids['home'], '_yoast_wpseo_title', 'Fernwood Makers | Local makers directory for Fernwood County' );

	foreach ( [ 'hello-world', 'sample-page', 'privacy-policy' ] as $s ) {
		$p = get_page_by_path( $s, OBJECT, [ 'post', 'page' ] );
		if ( $p ) wp_delete_post( $p->ID, true );
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids['home'] );
	update_option( 'wpseo_titles', array_merge( (array) get_option( 'wpseo_titles' ), [ 'company_or_person' => 'company', 'company_name' => 'Fernwood Makers', 'website_name' => 'Fernwood Makers', 'separator' => 'sc-pipe',
		'title-ptarchive-maker' => 'Browse the makers directory %%sep%% %%sitename%%', 'metadesc-ptarchive-maker' => 'Search and filter independent makers in Fernwood County by craft, town, price and open studio visits.',
		'metadesc-maker' => '%%excerpt%% Listing in the Fernwood Makers directory.',
		'metadesc-tax-craft' => '%%term_title%% makers in Fernwood County. Browse handmade work by town and price.', 'metadesc-tax-town' => 'Makers in %%term_title%%, Fernwood County. Browse handmade work by craft and price.' ] ) );
	update_option( 'demo_seeded', 1 );
	flush_rewrite_rules();
}
