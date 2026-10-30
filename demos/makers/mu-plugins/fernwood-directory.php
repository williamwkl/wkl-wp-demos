<?php
/**
 * Plugin Name: Fernwood Makers directory
 * Description: `maker` post type, `craft` and `town` taxonomies, listing meta, a CSV importer (WP-CLI + PHP), a generated JSON index,
 *              and the server-rendered directory (rail + grid) that a small vanilla script filters in the browser.
 * Version: 1.0.0
 */

const FW_PRICE_BANDS = [ 1 => [ '$', 'Under $25' ], 2 => [ '$$', '$25 to $75' ], 3 => [ '$$$', '$75 to $200' ], 4 => [ '$$$$', '$200 and up' ] ];

/* ---------------------------------------------------------------- Data model */
add_action( 'init', function () {
	register_post_type( 'maker', [
		'labels'       => [ 'name' => 'Makers', 'singular_name' => 'Maker', 'add_new_item' => 'Add maker', 'edit_item' => 'Edit maker', 'all_items' => 'All makers' ],
		'public'       => true,
		'has_archive'  => 'makers',
		'rewrite'      => [ 'slug' => 'makers', 'with_front' => false ],
		'menu_icon'    => 'dashicons-store',
		'show_in_rest' => true,
		'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
	] );
	register_taxonomy( 'craft', 'maker', [ 'labels' => [ 'name' => 'Crafts', 'singular_name' => 'Craft' ], 'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true, 'rewrite' => [ 'slug' => 'craft', 'with_front' => false ] ] );
	register_taxonomy( 'town', 'maker', [ 'labels' => [ 'name' => 'Towns', 'singular_name' => 'Town' ], 'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true, 'rewrite' => [ 'slug' => 'town', 'with_front' => false ] ] );
	$meta = [ 'maker_name' => 'string', 'price_band' => 'integer', 'open_studio' => 'boolean', 'website' => 'string', 'materials' => 'string', 'lead_time' => 'string', 'where_to_buy' => 'string' ];
	foreach ( $meta as $key => $type ) {
		register_post_meta( 'maker', $key, [ 'type' => $type, 'single' => true, 'show_in_rest' => true, 'auth_callback' => fn() => current_user_can( 'edit_posts' ) ] );
	}
} );

// Simple meta box so editors can change listing fields without a fields plugin.
add_action( 'add_meta_boxes_maker', function () {
	add_meta_box( 'fw-details', 'Listing details', function ( $post ) {
		wp_nonce_field( 'fw_details', 'fw_details_nonce' );
		$f = fn( $k ) => esc_attr( get_post_meta( $post->ID, $k, true ) );
		echo '<p><label>Maker name<br><input class="widefat" name="fw[maker_name]" value="' . $f( 'maker_name' ) . '"></label></p>';
		echo '<p><label>Price band<br><select name="fw[price_band]">';
		foreach ( FW_PRICE_BANDS as $n => $b ) printf( '<option value="%d"%s>%s (%s)</option>', $n, selected( (int) $f( 'price_band' ), $n, false ), $b[0], esc_html( $b[1] ) );
		echo '</select></label></p>';
		echo '<p><label><input type="checkbox" name="fw[open_studio]" value="1"' . checked( (bool) get_post_meta( $post->ID, 'open_studio', true ), true, false ) . '> Open studio visits</label></p>';
		foreach ( [ 'materials' => 'Materials', 'lead_time' => 'Lead time', 'where_to_buy' => 'Where to buy', 'website' => 'Website' ] as $k => $l ) {
			echo '<p><label>' . esc_html( $l ) . '<br><input class="widefat" name="fw[' . $k . ']" value="' . $f( $k ) . '"></label></p>';
		}
	}, 'maker', 'side' );
} );
add_action( 'save_post_maker', function ( $id ) {
	if ( ! isset( $_POST['fw_details_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['fw_details_nonce'] ), 'fw_details' ) || ! current_user_can( 'edit_post', $id ) ) return;
	$in = wp_unslash( $_POST['fw'] ?? [] );
	update_post_meta( $id, 'maker_name', sanitize_text_field( $in['maker_name'] ?? '' ) );
	update_post_meta( $id, 'price_band', max( 1, min( 4, (int) ( $in['price_band'] ?? 1 ) ) ) );
	update_post_meta( $id, 'open_studio', ! empty( $in['open_studio'] ) );
	foreach ( [ 'materials', 'lead_time', 'where_to_buy' ] as $k ) update_post_meta( $id, $k, sanitize_text_field( $in[ $k ] ?? '' ) );
	update_post_meta( $id, 'website', esc_url_raw( $in['website'] ?? '' ) );
} );
// Keep the static JSON index in sync with any edit.
add_action( 'save_post_maker', fn() => fw_write_index(), 20 );
add_action( 'deleted_post', fn( $id, $p ) => $p && 'maker' === $p->post_type && fw_write_index(), 10, 2 );

/* ---------------------------------------------------------------- CSV import (Google Sheets path) */
/**
 * Import makers from a CSV file path or URL (for example a Google Sheet published as CSV:
 * File > Share > Publish to web > Comma-separated values). Matching is by business_name, so re-running updates rows in place.
 * Columns: business_name, maker_name, craft, town, price_band, open_studio, description, materials, lead_time, where_to_buy, website, image
 *
 * @return array{created:int,updated:int,skipped:int,errors:string[]}
 */
const FW_IMPORT_MAX_BYTES = 2 * 1024 * 1024; // 2 MB is far more than any directory sheet needs

/** Only Google Sheets CSV exports over https are fetched: docs.google.com, and its *.googleusercontent.com export redirect. */
function fw_import_host_ok( $url ) {
	$u = wp_parse_url( $url );
	if ( ! $u || 'https' !== ( $u['scheme'] ?? '' ) || ! empty( $u['user'] ) || ! empty( $u['pass'] ) || ( isset( $u['port'] ) && 443 !== (int) $u['port'] ) ) return false;
	$host = strtolower( $u['host'] ?? '' );
	return 'docs.google.com' === $host || (bool) preg_match( '/^[a-z0-9-]+\.googleusercontent\.com$/', $host );
}

/** Directory local imports may read from (filterable). Paths are resolved with realpath and must stay inside it. */
function fw_import_dir() {
	return apply_filters( 'fw_import_dir', WP_CONTENT_DIR . '/demo-data' );
}

/** @return string|WP_Error CSV text */
function fw_import_read( $source ) {
	if ( preg_match( '#^[a-z][a-z0-9+.-]*://#i', $source ) ) {
		if ( ! fw_import_host_ok( $source ) ) return new WP_Error( 'fw_host', 'Only https Google Sheets CSV links (docs.google.com) are allowed.' );
		$url = $source;
		// Follow redirects ourselves (max 3) so every hop is re-validated against the allow-list.
		for ( $hop = 0; $hop <= 3; $hop++ ) {
			$res = wp_safe_remote_get( $url, [ 'timeout' => 20, 'redirection' => 0, 'limit_response_size' => FW_IMPORT_MAX_BYTES + 1 ] );
			if ( is_wp_error( $res ) ) return new WP_Error( 'fw_http', 'Could not download the sheet: ' . $res->get_error_message() );
			$code = (int) wp_remote_retrieve_response_code( $res );
			if ( $code >= 300 && $code < 400 ) {
				$next = wp_remote_retrieve_header( $res, 'location' );
				$next = $next ? WP_Http::make_absolute_url( $next, $url ) : '';
				if ( ! $next || ! fw_import_host_ok( $next ) ) return new WP_Error( 'fw_redirect', 'The sheet link redirected to a host that is not allowed.' );
				$url = $next;
				continue;
			}
			if ( 200 !== $code ) return new WP_Error( 'fw_http', 'Could not download the sheet: HTTP ' . $code );
			$body = wp_remote_retrieve_body( $res );
			if ( strlen( $body ) > FW_IMPORT_MAX_BYTES ) return new WP_Error( 'fw_size', 'The sheet is larger than 2 MB.' );
			return $body;
		}
		return new WP_Error( 'fw_redirect', 'Too many redirects.' );
	}
	$dir  = realpath( fw_import_dir() );
	$path = realpath( $source );
	if ( ! $dir || ! $path || ! str_starts_with( $path, trailingslashit( $dir ) ) || ! is_file( $path ) ) {
		return new WP_Error( 'fw_path', 'Local imports must be a CSV file inside ' . fw_import_dir() . '.' );
	}
	if ( filesize( $path ) > FW_IMPORT_MAX_BYTES ) return new WP_Error( 'fw_size', 'The file is larger than 2 MB.' );
	return file_get_contents( $path );
}

function fw_import_csv( $source, $image_resolver = null ) {
	$report = [ 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => [] ];
	$text = fw_import_read( $source );
	if ( is_wp_error( $text ) ) { $report['failed']++; $report['errors'][] = $text->get_error_message(); return $report; }
	$text = preg_replace( '/^\xEF\xBB\xBF/', '', $text ); // Excel / Sheets BOM
	$fh = fopen( 'php://temp', 'r+' ); fwrite( $fh, $text ); rewind( $fh );
	$head = array_map( fn( $h ) => strtolower( trim( $h ) ), (array) fgetcsv( $fh, 0, ',', '"', '' ) );
	foreach ( [ 'business_name', 'craft', 'town' ] as $req ) {
		if ( ! in_array( $req, $head, true ) ) { $report["failed"]++; $report["errors"][] = "Missing required column: $req"; return $report; }
	}
	$line = 1;
	while ( ( $cols = fgetcsv( $fh, 0, ',', '"', '' ) ) !== false ) {
		$line++;
		if ( count( array_filter( $cols, 'strlen' ) ) === 0 ) continue;
		$r = array_combine( $head, array_pad( array_slice( $cols, 0, count( $head ) ), count( $head ), '' ) );
		$name = sanitize_text_field( $r['business_name'] );
		if ( '' === $name ) { $report['skipped']++; $report['errors'][] = "Row $line: empty business_name"; continue; }
		$existing = get_posts( [ 'post_type' => 'maker', 'title' => $name, 'numberposts' => 1, 'post_status' => 'any' ] );
		$existing = $existing ? $existing[0] : null;
		$postarr = [ 'post_type' => 'maker', 'post_status' => 'publish', 'post_title' => $name, 'post_name' => sanitize_title( $name ),
			'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $r['description'] ?? '' ) . '</p><!-- /wp:paragraph -->', 'post_excerpt' => sanitize_text_field( $r['description'] ?? '' ) ];
		$id = $existing ? wp_update_post( array_merge( $postarr, [ 'ID' => $existing->ID ] ), true ) : wp_insert_post( $postarr, true );
		if ( is_wp_error( $id ) || ! $id ) { $report['failed']++; $report['errors'][] = "Row $line: " . ( is_wp_error( $id ) ? $id->get_error_message() : 'could not save' ); continue; }
		$report[ $existing ? 'updated' : 'created' ]++;
		wp_set_object_terms( $id, sanitize_text_field( $r['craft'] ), 'craft' );
		wp_set_object_terms( $id, sanitize_text_field( $r['town'] ), 'town' );
		update_post_meta( $id, 'maker_name', sanitize_text_field( $r['maker_name'] ?? '' ) );
		update_post_meta( $id, 'price_band', max( 1, min( 4, (int) ( $r['price_band'] ?? 1 ) ) ) );
		update_post_meta( $id, 'open_studio', in_array( strtolower( trim( $r['open_studio'] ?? '' ) ), [ 'yes', 'y', 'true', '1' ], true ) );
		foreach ( [ 'materials', 'lead_time', 'where_to_buy' ] as $k ) update_post_meta( $id, $k, sanitize_text_field( $r[ $k ] ?? '' ) );
		update_post_meta( $id, 'website', esc_url_raw( $r['website'] ?? '' ) );
		if ( $image_resolver && ! empty( $r['image'] ) ) {
			$att = $image_resolver( $r['image'], $id );
			if ( $att ) set_post_thumbnail( $id, $att );
		}
	}
	fclose( $fh );
	fw_write_index();
	return $report;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * wp fernwood import <csv-path-or-url>
	 */
	WP_CLI::add_command( 'fernwood import', function ( $args ) {
		$r = fw_import_csv( $args[0] );
		foreach ( $r['errors'] as $e ) WP_CLI::warning( $e );
		$msg = sprintf( 'Created %d, updated %d, skipped %d, failed %d.', $r['created'], $r['updated'], $r['skipped'], $r['failed'] );
		if ( $r['failed'] ) WP_CLI::error( $msg );
		WP_CLI::success( $msg );
	} );
}

/* ---------------------------------------------------------------- Read helpers + JSON index */
function fw_listing( $p ) {
	$craft = get_the_terms( $p, 'craft' ); $craft = $craft && ! is_wp_error( $craft ) ? $craft[0] : null;
	$town  = get_the_terms( $p, 'town' );  $town  = $town && ! is_wp_error( $town ) ? $town[0] : null;
	return [
		'id' => $p->ID, 'title' => get_the_title( $p ), 'url' => wp_make_link_relative( get_permalink( $p ) ),
		'maker' => get_post_meta( $p->ID, 'maker_name', true ), 'price' => (int) get_post_meta( $p->ID, 'price_band', true ) ?: 1,
		'open' => (bool) get_post_meta( $p->ID, 'open_studio', true ),
		'craft' => $craft ? $craft->slug : '', 'craftName' => $craft ? $craft->name : '',
		'town' => $town ? $town->slug : '', 'townName' => $town ? $town->name : '',
		'text' => wp_strip_all_tags( implode( ' ', [ get_the_title( $p ), get_post_meta( $p->ID, 'maker_name', true ), $craft ? $craft->name : '', $town ? $town->name : '', $p->post_excerpt, get_post_meta( $p->ID, 'materials', true ) ] ) ),
		'date' => get_post_time( 'U', true, $p ),
	];
}
function fw_all() {
	static $cache = null;
	if ( null === $cache ) {
		$cache = array_map( 'fw_listing', get_posts( [ 'post_type' => 'maker', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'post_status' => 'publish' ] ) );
	}
	return $cache;
}
function fw_index_path() { $u = wp_upload_dir(); return [ trailingslashit( $u['basedir'] ) . 'fernwood/makers.json', trailingslashit( $u['baseurl'] ) . 'fernwood/makers.json' ]; }
function fw_write_index() {
	[ $file ] = fw_index_path();
	wp_mkdir_p( dirname( $file ) );
	$rows = array_map( fn( $l ) => array_intersect_key( $l, array_flip( [ 'id', 'title', 'url', 'maker', 'price', 'open', 'craft', 'town', 'text', 'date' ] ) ),
		array_map( 'fw_listing', get_posts( [ 'post_type' => 'maker', 'numberposts' => -1, 'post_status' => 'publish' ] ) ) );
	file_put_contents( $file, wp_json_encode( [ 'generated' => gmdate( 'c' ), 'count' => count( $rows ), 'makers' => $rows ], JSON_UNESCAPED_SLASHES ) );
}
function fw_terms( $tax ) {
	return get_terms( [ 'taxonomy' => $tax, 'hide_empty' => true, 'orderby' => 'name' ] );
}

/* ---------------------------------------------------------------- Rendering */
function fw_icon( $name ) {
	$p = [
		'search' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
		'arrow'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'check'  => '<path d="M5 12l4 4L19 6"/>',
		'filter' => '<path d="M4 6h16M7 12h10M10 18h4"/>',
		'pin'    => '<path d="M12 22s7-6.2 7-12a7 7 0 10-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/>',
	];
	return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $name ] . '</svg>';
}
function fw_price_html( $n ) {
	return '<span class="price" aria-label="Price band ' . (int) $n . ' of 4, ' . esc_attr( FW_PRICE_BANDS[ $n ][1] ) . '">' . str_repeat( '$', $n ) . '<span class="off" aria-hidden="true">' . str_repeat( '$', 4 - $n ) . '</span></span>';
}
function fw_card( $l, $heading = 'h3', $eager = 0 ) {
	// Cards above the fold on directory pages load eagerly; the first one is the LCP candidate.
	$img = get_the_post_thumbnail( $l['id'], 'medium_large', [ 'alt' => '', 'loading' => $eager ? 'eager' : 'lazy', 'fetchpriority' => 1 === $eager ? 'high' : 'auto', 'sizes' => '(max-width: 599px) calc(100vw - 32px), (max-width: 959px) 45vw, 300px' ] );
	$open = $l['open'] ? '<span class="tag">' . str_replace( 'width="20" height="20"', 'width="10" height="10"', fw_icon( 'check' ) ) . 'Open studio</span>' : '<span></span>';
	return sprintf(
		'<article class="card" data-id="%1$d" data-craft="%2$s" data-town="%3$s" data-price="%4$d" data-open="%5$d"><div class="card-img">%6$s</div><div class="body"><p class="meta"><span class="dot" aria-hidden="true"></span>%7$s · %8$s</p><%12$s class="card-title"><a href="%9$s">%10$s</a></%12$s><p class="by">%11$s</p><div class="foot">%13$s%14$s</div></div></article>',
		$l['id'], esc_attr( $l['craft'] ), esc_attr( $l['town'] ), $l['price'], $l['open'] ? 1 : 0, $img,
		esc_html( $l['craftName'] ), esc_html( $l['townName'] ), esc_url( $l['url'] ), esc_html( $l['title'] ), esc_html( $l['maker'] ), $heading, fw_price_html( $l['price'] ), $open
	);
}

/** Read the active filter from the query string (no-JS fallback and shareable URLs) plus any preset (taxonomy pages). */
function fw_current_filter( $preset = [] ) {
	$g = fn( $k ) => isset( $_GET[ $k ] ) ? array_filter( array_map( 'sanitize_title', explode( ',', wp_unslash( (string) $_GET[ $k ] ) ) ) ) : []; // phpcs:ignore
	return [
		'q'        => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '', // phpcs:ignore
		'category' => $preset['category'] ?? $g( 'category' ),
		'town'     => $preset['town'] ?? $g( 'town' ),
		'price'    => array_map( 'intval', $g( 'price' ) ),
		'open'     => ! empty( $_GET['open'] ), // phpcs:ignore
	];
}
function fw_matches( $l, $f ) {
	if ( $f['category'] && ! in_array( $l['craft'], $f['category'], true ) ) return false;
	if ( $f['town'] && ! in_array( $l['town'], $f['town'], true ) ) return false;
	if ( $f['price'] && ! in_array( $l['price'], $f['price'], true ) ) return false;
	if ( $f['open'] && ! $l['open'] ) return false;
	if ( $f['q'] ) {
		foreach ( preg_split( '/\s+/', strtolower( $f['q'] ) ) as $t ) {
			if ( $t && false === strpos( strtolower( $l['text'] ), $t ) ) return false;
		}
	}
	return true;
}

/**
 * [fernwood_directory per_page="12" heading="Browse the directory." search="1" more="0"]
 * Server renders every listing (crawlable, works without JS). With JS, assets/js/directory.js filters, searches,
 * sorts and paginates in the browser using the generated JSON index and keeps the URL in sync.
 */
add_shortcode( 'fernwood_directory', function ( $atts ) {
	$a = shortcode_atts( [ 'per_page' => 12, 'heading' => 'Browse the directory.', 'search' => '1', 'more' => '0', 'craft' => '', 'town' => '', 'eager' => '0' ], $atts );
	$preset = [];
	if ( is_tax( 'craft' ) || $a['craft'] ) $preset['category'] = [ $a['craft'] ?: get_queried_object()->slug ];
	if ( is_tax( 'town' ) || $a['town'] ) $preset['town'] = [ $a['town'] ?: get_queried_object()->slug ];
	$f = fw_current_filter( $preset );
	$all = fw_all();
	$total = count( $all );
	$shown = array_values( array_filter( $all, fn( $l ) => fw_matches( $l, $f ) ) );
	$crafts = fw_terms( 'craft' ); $towns = fw_terms( 'town' );
	$count_by = function ( $key ) use ( $all ) { $c = []; foreach ( $all as $l ) $c[ $l[ $key ] ] = ( $c[ $l[ $key ] ] ?? 0 ) + 1; return $c; };
	$cc = $count_by( 'craft' ); $tc = $count_by( 'town' ); $pc = $count_by( 'price' );
	[ , $json ] = fw_index_path();
	$uid = 'dir-' . wp_rand( 100, 999 );
	$archive = wp_make_link_relative( get_post_type_archive_link( 'maker' ) );
	$locked = array_keys( $preset );

	$group = function ( $name, $legend, $items, $selected, $counts ) use ( $locked ) {
		$h = '<fieldset class="grp"><legend>' . esc_html( $legend ) . '</legend>';
		if ( in_array( $name, $locked, true ) ) {
			foreach ( $selected as $val ) {
				$h .= sprintf( '<label class="locked"><input type="checkbox" name="%3$s" value="%4$s" checked aria-disabled="true" aria-describedby="lock-%4$s"> <span>%1$s</span><span class="n">%2$d</span></label><p class="lock-note" id="lock-%4$s"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>Set by this page</p>', esc_html( $items[ $val ] ?? $val ), (int) ( $counts[ $val ] ?? 0 ), $name, esc_attr( $val ) );
			}
			$base = wp_make_link_relative( get_post_type_archive_link( 'maker' ) );
			return $h . '<a class="change" href="' . esc_url( $base ) . '">All ' . ( 'town' === $name ? 'towns' : 'categories' ) . '</a></fieldset>';
		}
		foreach ( $items as $val => $label ) {
			$h .= sprintf( '<label><input type="checkbox" name="%1$s" value="%2$s"%3$s> <span>%4$s</span><span class="n">%5$d</span></label>',
				$name, esc_attr( $val ), in_array( $val, $selected, false ) ? ' checked' : '', esc_html( $label ), (int) ( $counts[ $val ] ?? 0 ) );
		}
		return $h . '</fieldset>';
	};
	$craft_items = []; foreach ( $crafts as $t ) $craft_items[ $t->slug ] = $t->name;
	$town_items = []; foreach ( $towns as $t ) $town_items[ $t->slug ] = $t->name;
	$price_items = []; foreach ( FW_PRICE_BANDS as $n => $b ) $price_items[ $n ] = $b[0] . ' ' . $b[1];

	ob_start(); ?>
	<div class="directory" id="<?php echo esc_attr( $uid ); ?>" data-index="<?php echo esc_url( $json ); ?>" data-per-page="<?php echo (int) $a['per_page']; ?>" data-total="<?php echo (int) $total; ?>" data-sync-url="<?php echo $a['more'] ? '0' : '1'; ?>" data-lock-category="<?php echo esc_attr( implode( ',', $preset['category'] ?? [] ) ); ?>" data-lock-town="<?php echo esc_attr( implode( ',', $preset['town'] ?? [] ) ); ?>">
		<?php if ( '1' === $a['search'] ) : ?>
		<form class="search dir-search" role="search" action="<?php echo esc_url( $archive ); ?>" method="get">
			<?php echo fw_icon( 'search' ); // phpcs:ignore ?>
			<label class="screen-reader-text" for="<?php echo esc_attr( $uid ); ?>-q">Search makers</label>
			<input id="<?php echo esc_attr( $uid ); ?>-q" type="search" name="q" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="Mugs, Coldwater, under $50" autocomplete="off">
			<button class="btn btn-dark" type="submit" aria-label="Search"><span aria-hidden="true">Search</span><?php echo fw_icon( 'arrow' ); // phpcs:ignore ?></button>
		</form>
		<?php endif; ?>
		<div class="browse">
			<form class="filters" id="<?php echo esc_attr( $uid ); ?>-filters" action="<?php echo esc_url( $archive ); ?>" method="get" aria-label="Filter makers">
				<div class="sheet-head"><h2 class="sheet-title" id="<?php echo esc_attr( $uid ); ?>-sheet-title">Filters</h2><button type="button" class="iconbtn sheet-close" aria-label="Close filters"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg></button></div>
				<?php
				echo $group( 'category', 'Category', $craft_items, $f['category'], $cc ); // phpcs:ignore
				echo $group( 'town', 'Town', $town_items, $f['town'], $tc ); // phpcs:ignore
				echo $group( 'price', 'Price range', $price_items, $f['price'], $pc ); // phpcs:ignore
				?>
				<fieldset class="grp"><legend>Visits</legend><label class="switch"><input type="checkbox" name="open" value="1"<?php checked( $f['open'] ); ?>> <span>Open studio visits</span></label></fieldset>
				<div class="filter-actions">
					<a class="clear" href="<?php echo esc_url( is_tax() ? wp_make_link_relative( get_term_link( get_queried_object() ) ) : $archive ); ?>">Clear filters</a>
					<button class="btn btn-secondary nojs-apply" type="submit">Apply filters</button>
					<button class="btn btn-primary sheet-done" type="button">Show <span class="js-count"><?php echo count( $shown ); ?></span> makers</button>
				</div>
			</form>
			<div class="results">
				<div class="nojs-browse"><p>Search and filters need JavaScript. Every listing is shown below, or browse a smaller list by
					<?php echo implode( ', ', array_map( fn( $t ) => '<a href="' . esc_url( wp_make_link_relative( get_term_link( $t ) ) ) . '">' . esc_html( $t->name ) . '</a>', $crafts ) ); ?>,
					or by town: <?php echo implode( ', ', array_map( fn( $t ) => '<a href="' . esc_url( wp_make_link_relative( get_term_link( $t ) ) ) . '">' . esc_html( $t->name ) . '</a>', $towns ) ); ?>.</p></div>
				<div class="mob-filters" aria-label="Quick filters">
					<button type="button" class="chip filters-open" aria-expanded="false" aria-controls="<?php echo esc_attr( $uid ); ?>-filters"><?php echo fw_icon( 'filter' ); // phpcs:ignore ?> Filters</button>
				</div>
				<div class="active-chips" aria-label="Active filters"></div>
				<h2 class="screen-reader-text">Results</h2>
				<div class="res-head">
					<p class="count" role="status" aria-live="polite"><strong><?php echo count( $shown ); ?></strong> <?php echo 1 === count( $shown ) ? 'maker' : 'makers'; ?></p>
					<label class="sort"><span>Sort</span> <select name="sort" form="<?php echo esc_attr( $uid ); ?>-filters"><option value="recommended">Recommended</option><option value="newest">Newest</option><option value="az">A to Z</option></select></label>
				</div>
				<div class="grid">
					<?php
					// Non-matching cards are hidden; matching cards beyond the first page get .pg-hide, which only applies
					// when JS is on (html.js-on), so the first paint already matches what the script will show (no layout shift).
					$pos = 0;
					foreach ( $all as $l ) {
						$match = in_array( $l, $shown, true );
						$attr  = $match ? ( $pos++ >= (int) $a['per_page'] ? ' class="card pg-hide"' : ' class="card"' ) : ' class="card" hidden';
						$eager = ( $match && $pos <= (int) $a['eager'] ) ? $pos : 0;
						echo str_replace( '<article class="card"', '<article' . $attr, fw_card( $l, 'h3', $eager ) ); // phpcs:ignore
					}
					?>
				</div>
				<div class="empty" hidden><p><strong>No makers match those filters yet.</strong> Try removing a town or widening the price range.</p><a class="btn btn-secondary" href="<?php echo esc_url( $archive ); ?>">Clear filters</a></div>
				<?php if ( $a['more'] ) : ?>
					<p class="see-all"><a class="btn btn-secondary" href="<?php echo esc_url( $archive ); ?>">See all <?php echo (int) $total; ?> makers</a></p>
				<?php else : ?>
					<nav class="pager" aria-label="Pagination" hidden></nav>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
} );

add_shortcode( 'fernwood_chips', function () {
	$h = '<ul class="chips" aria-label="Browse by category">';
	foreach ( fw_terms( 'craft' ) as $t ) {
		$h .= '<li><a class="chip" href="' . esc_url( wp_make_link_relative( get_term_link( $t ) ) ) . '">' . esc_html( $t->name ) . ' <span class="n">' . (int) $t->count . '</span></a></li>';
	}
	return $h . '<li><a class="chip" href="' . esc_url( wp_make_link_relative( home_url( '/categories/' ) ) ) . '">All categories</a></li></ul>';
} );
add_shortcode( 'fernwood_towns', function () {
	$h = '<ul class="towns">';
	foreach ( fw_terms( 'town' ) as $t ) {
		$h .= '<li><a href="' . esc_url( wp_make_link_relative( get_term_link( $t ) ) ) . '">' . esc_html( $t->name ) . '<span class="n">' . (int) $t->count . '</span></a></li>';
	}
	return $h . '</ul>';
} );
add_shortcode( 'fernwood_terms', function ( $atts ) {
	$tax = in_array( $atts['tax'] ?? '', [ 'craft', 'town' ], true ) ? $atts['tax'] : 'craft';
	// Photo tiles (Categories and Towns pages): each tile shows a listing photo from that term, never repeating one on the page.
	$h = '<ul class="cat-tiles">';
	$used = [];
	$i = 0;
	foreach ( fw_terms( $tax ) as $t ) {
		$img = '';
		foreach ( get_posts( [ 'post_type' => 'maker', 'numberposts' => -1, 'orderby' => 'menu_order', 'order' => 'ASC', 'tax_query' => [ [ 'taxonomy' => $tax, 'terms' => $t->term_id ] ] ] ) as $mp ) {
			$tid = get_post_thumbnail_id( $mp );
			if ( $tid && ! in_array( $tid, $used, true ) ) {
				$used[] = $tid;
				$img = wp_get_attachment_image( $tid, 'medium_large', false, [ 'alt' => '', 'loading' => $i < 3 ? 'eager' : 'lazy', 'fetchpriority' => 0 === $i ? 'high' : 'auto', 'sizes' => '(max-width: 599px) 50vw, 380px' ] );
				break;
			}
		}
		$h .= '<li><a class="cat-tile" href="' . esc_url( wp_make_link_relative( get_term_link( $t ) ) ) . '"><span class="cat-img">' . $img . '</span><span class="cat-name">' . esc_html( $t->name ) . '</span><span class="cat-n">' . (int) $t->count . ' ' . ( 1 === (int) $t->count ? 'maker' : 'makers' ) . '</span></a></li>';
		$i++;
	}
	return $h . '</ul>';
	$h = '<ul class="towns term-index">';
	foreach ( fw_terms( $tax ) as $t ) $h .= '<li><a href="' . esc_url( wp_make_link_relative( get_term_link( $t ) ) ) . '">' . esc_html( $t->name ) . '<span class="n">' . (int) $t->count . '</span></a></li>';
	return $h . '</ul>';
} );
add_shortcode( 'fernwood_count', fn( $a ) => (string) ( 'towns' === ( $a['of'] ?? '' ) ? count( fw_terms( 'town' ) ) : count( fw_all() ) ) );
add_shortcode( 'fernwood_term_header', function () {
	$t = get_queried_object();
	if ( ! $t instanceof WP_Term ) return '';
	$label = 'craft' === $t->taxonomy ? 'Category' : 'Town';
	$lead = 'craft' === $t->taxonomy ? sprintf( '%d %s makers across Fernwood County.', $t->count, strtolower( $t->name ) ) : sprintf( '%d makers in and around %s.', $t->count, $t->name );
	return '<p class="eyebrow">' . esc_html( $label ) . '</p><h1>' . esc_html( $t->name ) . '</h1><p class="lead">' . esc_html( $lead ) . '</p>';
} );

/** Single maker page body. */
add_shortcode( 'fernwood_single', function () {
	$p = get_post();
	if ( ! $p || 'maker' !== $p->post_type ) return '';
	$l = fw_listing( $p );
	$thumb = get_post_thumbnail_id( $p );
	$alt = esc_attr( $l['craftName'] . ' work by ' . $l['title'] );
	$main = $thumb ? wp_get_attachment_image( $thumb, 'large', false, [ 'alt' => $alt, 'fetchpriority' => 'high', 'loading' => 'eager', 'sizes' => '(max-width: 767px) calc(100vw - 32px), 680px' ] ) : '';
	$others = array_values( array_filter( fw_all(), fn( $x ) => $x['craft'] === $l['craft'] && $x['id'] !== $l['id'] ) );
	$more = array_slice( $others, 0, 3 );
	// Gallery: the listing photo plus two different photos, never one already shown in the "More in" row.
	$pool = array_map( fn( $x ) => get_post_thumbnail_id( $x['id'] ), array_slice( $others, 3 ) );
	$pool = array_merge( $pool, (array) ( get_option( 'fw_gallery_extras' )[ $l['craft'] ] ?? [] ) );
	$override = (array) ( get_option( 'fw_gallery_override' )[ $p->post_name ] ?? [] );
	if ( $override ) $pool = $override; // hand-picked shots for listings where category photos would not fit
	$pool = array_values( array_unique( array_filter( $pool, fn( $id ) => $id && $id !== $thumb ) ) );
	$thumbs = '';
	foreach ( array_slice( $pool, 0, 2 ) as $i => $tid ) {
		$thumbs .= $tid ? '<div class="thumb">' . wp_get_attachment_image( $tid, 'medium', false, [ 'alt' => '', 'loading' => 'lazy', 'sizes' => '(max-width: 767px) 30vw, 220px' ] ) . '</div>' : '';
	}
	$open = $l['open'] ? '<span class="tag">Open studio</span>' : '';
	ob_start(); ?>
	<nav class="crumbs" aria-label="Breadcrumb"><a href="<?php echo esc_url( wp_make_link_relative( get_post_type_archive_link( 'maker' ) ) ); ?>">Directory</a> <span aria-hidden="true">/</span> <a href="<?php echo esc_url( wp_make_link_relative( get_term_link( $l['craft'], 'craft' ) ) ); ?>"><?php echo esc_html( $l['craftName'] ); ?></a></nav>
	<div class="single-grid">
		<div class="gallery"><div class="main"><?php echo $main; // phpcs:ignore ?></div><div class="thumbs"><?php echo $thumbs; // phpcs:ignore ?></div></div>
		<div class="single-info">
			<p class="meta"><a class="chip" href="<?php echo esc_url( wp_make_link_relative( get_term_link( $l['craft'], 'craft' ) ) ); ?>"><?php echo esc_html( $l['craftName'] ); ?></a> <a class="chip" href="<?php echo esc_url( wp_make_link_relative( get_term_link( $l['town'], 'town' ) ) ); ?>"><?php echo esc_html( $l['townName'] ); ?></a> <?php echo $open; // phpcs:ignore ?></p>
			<h1><?php echo esc_html( $l['title'] ); ?></h1>
			<p class="by-line">By <?php echo esc_html( $l['maker'] ); ?> · <?php echo fw_price_html( $l['price'] ); // phpcs:ignore ?> <span class="mute"><?php echo esc_html( FW_PRICE_BANDS[ $l['price'] ][1] ); ?></span></p>
			<div class="prose"><?php echo apply_filters( 'the_content', $p->post_content ); // phpcs:ignore ?></div>
			<dl class="details">
				<dt>Materials</dt><dd><?php echo esc_html( get_post_meta( $p->ID, 'materials', true ) ); ?></dd>
				<dt>Lead time</dt><dd><?php echo esc_html( get_post_meta( $p->ID, 'lead_time', true ) ); ?></dd>
				<dt>Where to buy</dt><dd><?php echo esc_html( get_post_meta( $p->ID, 'where_to_buy', true ) ); ?></dd>
				<dt>Website</dt><dd class="site"><?php echo str_replace( '.', '<wbr>.', esc_html( preg_replace( '#^https?://#', '', get_post_meta( $p->ID, 'website', true ) ) ) ); // break only before dots ?></dd>
			</dl>
			<form class="contact-form demo-form" action="/demo-form/" method="get" aria-labelledby="contact-h">
				<h2 id="contact-h">Message <?php echo esc_html( $l['maker'] ? strtok( $l['maker'], ' ' ) : 'the maker' ); ?></h2>
				<p class="demo-note">Demo form: checks your entries in the browser and sends nothing.</p>
				<p><label for="c-name">Your name</label><input id="c-name" name="name" required autocomplete="name"></p>
				<p><label for="c-email">Email</label><input id="c-email" name="email" type="email" required autocomplete="email"></p>
				<p><label for="c-msg">Message</label><textarea id="c-msg" name="message" rows="4" required></textarea></p>
				<button class="btn btn-primary" type="submit">Send message</button>
			</form>
		</div>
	</div>
	<?php if ( $more ) : ?>
	<section class="more" aria-labelledby="more-h"><h2 id="more-h">More in <?php echo esc_html( $l['craftName'] ); ?></h2><div class="grid"><?php foreach ( $more as $m ) echo fw_card( $m ); // phpcs:ignore ?></div></section>
	<?php endif;
	return ob_get_clean();
} );

// The directory is server-filtered from query params on live WordPress (no-JS fallback); keep archive queries cheap.
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() && $q->is_main_query() && ( $q->is_post_type_archive( 'maker' ) || $q->is_tax( [ 'craft', 'town' ] ) ) ) {
		$q->set( 'posts_per_page', -1 ); // one page: the directory shortcode renders and paginates the listings itself (no /page/N/ duplicates)
		$q->set( 'no_found_rows', true );
	}
} );

// Flag JS support before first paint so the directory can render its paginated state without a layout shift.
add_action( 'wp_head', fn() => print( "<script>document.documentElement.classList.add('js-on')</script>\n" ), 0 );

// Photo credits page: the full list comes from the same CSV that documents every image.
add_filter( 'the_content', function ( $c ) {
	if ( ! is_page( 'credits' ) ) return $c;
	$c = str_replace( 'The full list with source file names is in the project repository.', 'The full list is below.', $c );
	$file = WP_CONTENT_DIR . '/demo-data/image-credits.csv';
	if ( ! is_readable( $file ) ) return $c;
	$fh = fopen( $file, 'r' ); fgetcsv( $fh, 0, ',', '"', '' );
	$rows = '';
	while ( ( $r = fgetcsv( $fh, 0, ',', '"', '' ) ) !== false ) {
		$rows .= '<tr><td>' . esc_html( preg_replace( '/\.(jpe?g|png|webp)/i', '', $r[1] ) ) . '</td><td>' . esc_html( $r[2] ) . '</td></tr>';
	}
	fclose( $fh );
	return $c . '<section class="section no-top"><div class="wrap"><table class="credits-table"><caption class="screen-reader-text">Photo sources</caption><thead><tr><th scope="col">Photo (Wikimedia Commons file)</th><th scope="col">License</th></tr></thead><tbody>' . $rows . '</tbody></table></div></section>';
}, 20 );
