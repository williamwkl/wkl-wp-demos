<?php
/**
 * Plugin Name: Lantern Hill storefront
 * Description: Server-rendered storefront for the Lantern Hill demo (design/store/DESIGN.md). Live WordPress: real WooCommerce cart
 *              and checkout (offline "Test order" gateway). Static copy: assets/js/store.js keeps a simulated cart in sessionStorage.
 */

function lh_esc_json( $v ) { return esc_attr( wp_json_encode( $v ) ); }
function lh_money( $n ) { return '$' . ( floor( $n ) == $n ? number_format( $n, 0 ) : number_format( $n, 2 ) ); }
function lh_icon( $name, $size = 22 ) {
	$p = [
		'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'cart'   => '<path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6 5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/>',
		'menu'   => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'  => '<path d="M6 6l12 12M18 6 6 18"/>',
		'check'  => '<path d="m5 12 5 5L20 7"/>',
		'lock'   => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
		'info'   => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>',
	];
	return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $name ] . '</svg>';
}

/** Everything the templates need about one product, from the WooCommerce product plus the seeded design data. */
function lh_item( $product ) {
	if ( ! $product instanceof WC_Product ) $product = wc_get_product( $product );
	if ( ! $product ) return null;
	$d = json_decode( (string) get_post_meta( $product->get_id(), '_lh', true ), true ) ?: [];
	$vars = [];
	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			$vars[] = [ 'id' => $vid, 'size' => $v->get_attribute( 'size' ), 'price' => (float) $v->get_price() ];
		}
		usort( $vars, fn( $a, $b ) => $a['price'] <=> $b['price'] );
	}
	$cat = get_the_terms( $product->get_id(), 'product_cat' );
	return [
		'id' => $product->get_id(), 'name' => $product->get_name(), 'url' => wp_make_link_relative( get_permalink( $product->get_id() ) ),
		'price' => $vars ? $vars[0]['price'] : (float) $product->get_price(), 'vars' => $vars,
		'cat' => $cat && ! is_wp_error( $cat ) ? $cat[0]->slug : '', 'catName' => $cat && ! is_wp_error( $cat ) ? $cat[0]->name : '',
		'img' => $product->get_image_id(), 'd' => $d,
	];
}
function lh_items( $args = [] ) {
	$q = wc_get_products( array_merge( [ 'status' => 'publish', 'limit' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ], $args ) );
	return array_values( array_filter( array_map( 'lh_item', $q ) ) );
}
function lh_img( $id, $size, $sizes, $eager = false, $alt = '' ) {
	return $id ? wp_get_attachment_image( $id, $size, false, [ 'alt' => $alt, 'sizes' => $sizes, 'loading' => $eager ? 'eager' : 'lazy', 'fetchpriority' => $eager ? 'high' : 'auto', 'decoding' => 'async' ] ) : '';
}

/** The add-to-cart form. Live: a real WooCommerce add (POST). Static: store.js intercepts it into the simulated cart. */
function lh_add_form( $it, $label = 'Add', $class = 'add', $extra = '' ) {
	$v = $it['vars'][0] ?? null;
	$cart = [ 'id' => $it['id'], 'name' => $it['name'], 'price' => $it['price'], 'variant' => $v ? $v['size'] . ', whole bean' : ( $it['d']['bundleMeta'] ?? '' ), 'img' => $it['img'] ? wp_make_link_relative( wp_get_attachment_image_url( $it['img'], 'thumbnail' ) ) : '' ];
	$h = '<form class="add-form" method="post" action="' . esc_url( $it['url'] ) . '" data-item="' . lh_esc_json( $cart ) . '">';
	$h .= '<input type="hidden" name="add-to-cart" value="' . (int) $it['id'] . '"><input type="hidden" name="quantity" value="1">';
	if ( $v ) $h .= '<input type="hidden" name="variation_id" value="' . (int) $v['id'] . '"><input type="hidden" name="attribute_size" value="' . esc_attr( $v['size'] ) . '">';
	return $h . $extra . '<button class="' . esc_attr( $class ) . '" type="submit">' . esc_html( $label ) . '<span class="sr"> ' . esc_html( $it['name'] ) . ' to cart</span></button></form>';
}

function lh_card( $it, $eager = false, $short = false ) {
	$d = $it['d'];
	$tag = ! empty( $d['tag'] ) ? '<span class="tag' . ( ! empty( $d['tagStyle'] ) ? ' ' . esc_attr( $d['tagStyle'] ) : '' ) . '">' . esc_html( $d['tag'] ) . '</span>' : '';
	$unit = ! empty( $it['vars'] ) ? ' <span class="mute small">/ 12 oz</span>' : ( ! empty( $d['unit'] ) ? ' <span class="mute small">' . esc_html( $d['unit'] ) . '</span>' : '' );
	return '<article class="card" data-cat="' . esc_attr( $it['cat'] ) . '" data-price="' . esc_attr( $it['price'] ) . '" data-order="' . (int) ( $d['order'] ?? 0 ) . '"><a class="card-img" href="' . esc_url( $it['url'] ) . '" tabindex="-1" aria-hidden="true">' . lh_img( $it['img'], 'medium_large', '(max-width: 767px) 45vw, (max-width: 1023px) 30vw, 280px', $eager ) . '</a><div class="body">' . $tag
		. '<h3><a class="title" href="' . esc_url( $it['url'] ) . '">' . esc_html( $it['name'] ) . '</a></h3>' . ( $short ? '' : '<p class="meta">' . esc_html( $d['card'] ?? '' ) . '</p>' )
		. '<div class="row"><span class="price">' . lh_money( $it['price'] ) . $unit . '</span>' . lh_add_form( $it ) . '</div></div></article>';
}

/* ------------------------------------------------------------------ live cart behaviour */
add_filter( 'woocommerce_add_cart_item_data', function ( $data ) {
	if ( isset( $_POST['lh_grind'] ) && in_array( wp_unslash( $_POST['lh_grind'] ), LH_GRINDS, true ) ) $data['lh_grind'] = sanitize_text_field( wp_unslash( $_POST['lh_grind'] ) ); // phpcs:ignore
	return $data;
} );
add_filter( 'woocommerce_get_item_data', function ( $out, $item ) {
	if ( ! empty( $item['lh_grind'] ) ) $out[] = [ 'key' => 'Grind', 'value' => $item['lh_grind'] ];
	if ( ! empty( $item['lh_bundle'] ) ) $out[] = [ 'key' => 'Bundle', 'value' => '3-item bundle, 12 percent off' ];
	return $out;
}, 10, 2 );
add_action( 'woocommerce_checkout_create_order_line_item', function ( $line, $key, $values ) {
	if ( ! empty( $values['lh_grind'] ) ) $line->add_meta_data( 'Grind', $values['lh_grind'] );
	if ( ! empty( $values['lh_bundle'] ) ) $line->add_meta_data( 'Bundle', $values['lh_bundle'] );
}, 10, 3 );

// Bundle: POST from /bundle/ with exactly three eligible product ids (nonce-checked) adds three cart items sharing a bundle id.
add_action( 'template_redirect', function () {
	if ( empty( $_POST['lh_bundle_nonce'] ) || ! function_exists( 'WC' ) ) return;
	if ( ! wp_verify_nonce( sanitize_key( $_POST['lh_bundle_nonce'] ), 'lh_bundle' ) ) return;
	$ids = array_map( 'absint', (array) ( $_POST['lh_pick'] ?? [] ) ); // phpcs:ignore
	$eligible = wp_list_pluck( lh_items( [ 'tag' => [ 'bundle' ] ] ), 'id' );
	if ( 3 !== count( $ids ) || array_diff( $ids, $eligible ) ) { wc_add_notice( 'Pick exactly three items from the bundle list.', 'error' ); return; }
	$bundle = wp_generate_uuid4();
	foreach ( $ids as $id ) {
		$it = lh_item( $id );
		$v = $it['vars'][0] ?? null;
		WC()->cart->add_to_cart( $id, 1, $v ? $v['id'] : 0, $v ? [ 'attribute_size' => $v['size'] ] : [], [ 'lh_bundle' => $bundle ] );
	}
	wp_safe_redirect( wc_get_cart_url() ); exit;
} );
// 12 percent off every complete set of three bundle items, as a negative fee.
add_action( 'woocommerce_cart_calculate_fees', function ( $cart ) {
	$groups = [];
	foreach ( $cart->get_cart() as $item ) if ( ! empty( $item['lh_bundle'] ) ) $groups[ $item['lh_bundle'] ][] = (float) $item['line_subtotal'];
	$discount = 0;
	foreach ( $groups as $g ) if ( count( $g ) >= 3 ) $discount += array_sum( $g ) * LH_BUNDLE_RATE;
	if ( $discount > 0 ) $cart->add_fee( 'Bundle discount (12 percent)', -round( $discount, 2 ) );
} );

/* ------------------------------------------------------------------ shortcodes */
add_shortcode( 'lh_home', function () {
	$feat = array_slice( lh_items( [ 'featured' => true ] ), 0, 4 );
	$all = lh_items();
	ob_start(); ?>
	<section class="wrap hero">
		<div>
			<p class="eyebrow">Small-batch roaster, Ashgrove</p>
			<h1>Coffee roasted this week, <em>not this quarter.</em></h1>
			<p class="lead" style="margin-top:16px">Six coffees, one twelve-kilo drum, two roast days a week. Every bag leaves with the roast date stamped on the back so you know exactly how fresh it is.</p>
			<div class="actions"><a class="btn btn-primary" href="/shop/">Shop coffee</a><a class="btn btn-secondary" href="/bundle/">Build a 3-bag bundle</a></div>
			<ul class="hero-facts"><li><?php echo lh_icon( 'check', 16 ); ?>Roast date on every bag</li><li><?php echo lh_icon( 'check', 16 ); ?>Free shipping over $40</li><li><?php echo lh_icon( 'check', 16 ); ?>4.9 from 212 reviews (sample)</li></ul>
		</div>
		<div class="hero-photo"><?php echo lh_theme_img( 'hero-640.webp', 640, 800, 'Hands holding freshly roasted coffee beans', true ); // phpcs:ignore ?></div>
	</section>
	<div class="notes"><div class="wrap"><ul aria-label="This week's roast">
		<li><b>Thu, Oct 29</b><span>Last roast day</span></li><li><b>6 coffees</b><span>in rotation this month</span></li><li><b>12 kg</b><span>per batch, by hand</span></li><li><b>Mon and Thu</b><span>roast and ship days</span></li>
	</ul></div></div>
	<section class="wrap section">
		<div class="shop-head"><div><p class="eyebrow">This week's coffees</p><h2>Four bags worth starting with.</h2></div><a href="/shop/">See all <?php echo count( $all ); ?> products</a></div>
		<div class="grid products four"><?php foreach ( $feat as $it ) echo lh_card( $it ); // phpcs:ignore ?></div>
	</section>
	<section class="dark section"><div class="wrap band">
		<div>
			<p class="eyebrow peach">Build your own bundle</p>
			<h2>Pick any three, save 12 percent.</h2>
			<p class="mute" style="margin-top:12px">Mix bags, gear and mugs. The price updates as you pick, and the bundle ships as one box with a card that says what is inside and why.</p>
			<a class="btn btn-primary on-dark" href="/bundle/" style="margin-top:24px">Start a bundle</a>
		</div>
		<div class="stack" aria-hidden="true"><?php foreach ( [ 'morning-fog-bag', 'dripper', 'summit-bag' ] as $k ) { foreach ( $all as $it ) if ( ( $it['d']['img'] ?? '' ) === $k ) echo '<div class="stack-img">' . lh_img( $it['img'], 'medium', '160px' ) . '</div>'; } // phpcs:ignore ?></div>
	</div></section>
	<section class="wrap section split" id="roastery">
		<div class="split-photo"><?php echo lh_theme_img( 'roastery-900.webp', 900, 675, 'The roastery on Mill Street: brick walls, a long table and stools under hanging lamps' ); // phpcs:ignore ?></div>
		<div>
			<p class="eyebrow">Our roastery</p>
			<h2>Two people, one drum, a lot of cupping spoons.</h2>
			<p style="margin-top:12px">Mara and Tomas opened the roastery in a former bakery on Mill Street in 2019. They buy from four importers they have visited, roast in twelve-kilo batches, and cup every batch before it is bagged. If a roast misses, it becomes staff coffee, not your coffee.</p>
			<ul class="facts"><li><?php echo lh_icon( 'check', 18 ); ?>Green coffee bought on fixed contracts, prices published on each product page</li><li><?php echo lh_icon( 'check', 18 ); ?>Bags are paper with a recyclable liner</li><li><?php echo lh_icon( 'check', 18 ); ?>Cafe on Mill Street open Thursday to Sunday</li></ul>
		</div>
	</section>
	<section class="wrap section" style="padding-top:0">
		<p class="eyebrow">What customers say</p>
		<div class="quotes" style="margin-top:16px">
			<blockquote><p>&ldquo;The roast date was two days before it arrived. I did not know coffee could taste like that at home.&rdquo;</p><cite>Dana K., Ashgrove (sample review)</cite></blockquote>
			<blockquote><p>&ldquo;The bundle card told me which bag to open first. Small thing, but nobody else does it.&rdquo;</p><cite>Reuben O., Fallowfield (sample review)</cite></blockquote>
		</div>
	</section>
	<?php return ob_get_clean();
} );

function lh_theme_img( $file, $w, $h, $alt, $eager = false ) {
	$u = wp_make_link_relative( get_stylesheet_directory_uri() . '/assets/img/' . $file );
	return '<img src="' . esc_url( $u ) . '" width="' . $w . '" height="' . $h . '" alt="' . esc_attr( $alt ) . '" ' . ( $eager ? 'fetchpriority="high" loading="eager"' : 'loading="lazy"' ) . ' decoding="async">';
}

/** Shop and category archives. Chips are real links (no-JS) that store.js upgrades to in-page filters. */
add_shortcode( 'lh_shop', function () {
	$all = lh_items();
	$cur = is_product_category() ? get_queried_object()->slug : 'all';
	$cats = lh_data()['categories'];
	$counts = array_count_values( wp_list_pluck( $all, 'cat' ) );
	$shop = wp_make_link_relative( wc_get_page_permalink( 'shop' ) );
	ob_start(); ?>
	<section class="wrap section shop" data-shop>
		<div class="shop-head">
			<div><p class="eyebrow">Shop</p><h1 class="h2"><?php echo 'all' === $cur ? 'All products' : esc_html( get_queried_object()->name ); ?></h1></div>
			<div class="sort js-only"><label for="sort">Sort</label><select id="sort"><option value="featured">Featured</option><option value="low">Price, low to high</option><option value="high">Price, high to low</option></select></div>
		</div>
		<nav class="chips" aria-label="Filter by category">
			<a class="chip<?php echo 'all' === $cur ? ' on' : ''; ?>" href="<?php echo esc_url( $shop ); ?>" data-cat="all"<?php echo 'all' === $cur ? ' aria-current="page"' : ''; ?>>All (<?php echo count( $all ); ?>)</a>
			<?php foreach ( $cats as $c ) : $on = $cur === $c['slug']; ?>
				<a class="chip<?php echo $on ? ' on' : ''; ?>" href="<?php echo esc_url( wp_make_link_relative( get_term_link( $c['slug'], 'product_cat' ) ) ); ?>" data-cat="<?php echo esc_attr( $c['slug'] ); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c['chip'] ); ?> (<?php echo (int) ( $counts[ $c['slug'] ] ?? 0 ); ?>)</a>
			<?php endforeach; ?>
		</nav>
		<p class="small mute shop-count" aria-live="polite">Showing <?php echo 'all' === $cur ? count( $all ) : (int) ( $counts[ $cur ] ?? 0 ); ?> products</p>
		<h2 class="sr">Products</h2>
		<div class="grid products four shop-grid">
			<?php $i = 0; foreach ( $all as $k => $it ) { $it['d']['order'] = $k; $vis = 'all' === $cur || $it['cat'] === $cur; echo str_replace( '<article class="card"', '<article class="card"' . ( $vis ? '' : ' hidden' ), lh_card( $it, $vis && $i++ < 1 ) ); } // phpcs:ignore ?>
		</div>
	</section>
	<?php return ob_get_clean();
} );

/** Single product. */
add_shortcode( 'lh_product', function () {
	$it = lh_item( get_the_ID() );
	if ( ! $it ) return '';
	$d = $it['d'];
	$others = array_values( array_filter( lh_items( [ 'category' => [ 'brewing-gear' ] ] ), fn( $x ) => $x['id'] !== $it['id'] ) );
	$pair = 'coffee' === $it['cat'] ? array_slice( $others, 0, 2 ) : array_slice( array_values( array_filter( lh_items( [ 'category' => [ 'coffee' ] ] ), fn( $x ) => $x['id'] !== $it['id'] ) ), 0, 2 );
	$gallery = wc_get_product( $it['id'] )->get_gallery_image_ids(); // only this product's own images
	$cat_link = wp_make_link_relative( get_term_link( $it['cat'], 'product_cat' ) );
	ob_start(); ?>
	<section class="wrap section">
		<nav aria-label="Breadcrumb" class="crumbs small"><a href="/">Home</a> <span aria-hidden="true">/</span> <a href="<?php echo esc_url( $cat_link ); ?>"><?php echo esc_html( $it['catName'] ); ?></a> <span aria-hidden="true">/</span> <span aria-current="page"><?php echo esc_html( $it['name'] ); ?></span></nav>
		<div class="pdp">
			<div class="gallery">
				<div class="main"><?php echo lh_img( $it['img'], 'large', '(max-width: 767px) calc(100vw - 40px), 640px', true, $it['name'] . ', product photo' ); // phpcs:ignore ?></div>
				<?php if ( $gallery ) : ?><div class="thumbs" aria-label="More photos of <?php echo esc_attr( $it['name'] ); ?>"><?php foreach ( $gallery as $g ) echo '<figure class="thumb">' . lh_img( $g, 'medium', '(max-width: 767px) 45vw, 300px', false, $it['name'] . ', the roasted beans' ) . '</figure>'; // phpcs:ignore ?></div><?php endif; ?>
			</div>
			<div>
				<p class="eyebrow"><?php echo esc_html( $d['eyebrow'] ?? $it['catName'] ); ?></p>
				<h1 class="pdp-title"><?php echo esc_html( $it['name'] ); ?></h1>
				<?php if ( ! empty( $d['origin'] ) ) : ?><p class="mute" style="margin-top:6px"><?php echo esc_html( $d['origin'] ); ?></p><?php endif; ?>
				<?php if ( ! empty( $d['notes'] ) ) : ?><div class="notes-row" style="margin-top:14px"><?php foreach ( $d['notes'] as $n ) echo '<span class="tag">' . esc_html( $n ) . '</span>'; ?><span class="tag leaf">Roasted Thu, Oct 29</span></div><?php endif; ?>
				<p style="margin-top:16px"><?php echo esc_html( $d['desc'] ?? '' ); ?></p>
				<p class="price big-price" style="margin-top:16px" data-pdp-price aria-live="polite"><?php echo esc_html( '$' . number_format( $it['price'], 2 ) ); ?><?php echo ! empty( $d['unit'] ) ? ' <span class="mute small">' . esc_html( $d['unit'] ) . '</span>' : ''; ?></p>
				<form class="opts add-form pdp-form" method="post" action="<?php echo esc_url( $it['url'] ); ?>" data-item="<?php echo lh_esc_json( [ 'id' => $it['id'], 'name' => $it['name'], 'price' => $it['price'], 'img' => $it['img'] ? wp_make_link_relative( wp_get_attachment_image_url( $it['img'], 'thumbnail' ) ) : '', 'variant' => '' ] ); ?>">
					<input type="hidden" name="add-to-cart" value="<?php echo (int) $it['id']; ?>">
					<?php if ( $it['vars'] ) : ?>
						<input type="hidden" data-role="variation" name="variation_id" value="<?php echo (int) $it['vars'][0]['id']; ?>">
						<input type="hidden" data-role="attr-size" name="attribute_size" value="<?php echo esc_attr( $it['vars'][0]['size'] ); ?>">
						<fieldset><legend>Size</legend><div class="radio-row">
							<?php foreach ( $it['vars'] as $i => $v ) : ?><label><input type="radio" data-role="size" name="lh_size" value="<?php echo (int) $v['id']; ?>" data-size="<?php echo esc_attr( $v['size'] ); ?>" data-price="<?php echo esc_attr( $v['price'] ); ?>"<?php checked( 0, $i ); ?>><span><?php echo esc_html( $v['size'] . ', ' . lh_money( $v['price'] ) ); ?></span></label><?php endforeach; ?>
						</div></fieldset>
						<fieldset><legend>Grind</legend><div class="radio-row">
							<?php foreach ( LH_GRINDS as $i => $g ) : ?><label><input type="radio" data-role="grind" name="lh_grind" value="<?php echo esc_attr( $g ); ?>"<?php checked( 0, $i ); ?>><span><?php echo esc_html( $g ); ?></span></label><?php endforeach; ?>
						</div></fieldset>
					<?php endif; ?>
					<div class="buy">
						<div class="qty"><button type="button" class="js-only" data-step="-1" aria-label="Decrease quantity">&minus;</button><label class="sr" for="qty">Quantity</label><input id="qty" type="number" name="quantity" value="1" min="1" max="9" inputmode="numeric"><button type="button" class="js-only" data-step="1" aria-label="Increase quantity">+</button></div>
						<button class="btn btn-primary" type="submit">Add to cart</button>
					</div>
					<p class="small mute"><?php echo 'subscriptions' === $it['cat'] ? 'Demo product: recurring billing needs the paid Subscriptions extension, so nothing renews here.' : 'Ships Monday or Thursday, the day it is roasted. Free shipping over $40.'; ?></p>
				</form>
				<?php if ( 'coffee' === $it['cat'] ) : ?>
				<details class="acc"><summary>Brewing notes</summary><p>18 g to 300 g water at 94 C for pour-over. For French press, 30 g to 500 g, four minutes. If it tastes sharp, grind a little finer.</p></details>
				<details class="acc"><summary>Where it comes from</summary><p><?php echo esc_html( $d['origin'] ?? '' ); ?> Bought on a fixed contract from an importer we have visited (sample details).</p></details>
				<?php endif; ?>
				<details class="acc"><summary>Shipping and returns</summary><p>Orders over $40 ship free. If something arrives damaged, write to us within 14 days and we replace it, no return needed.</p></details>
			</div>
		</div>
		<?php if ( $pair ) : ?><div class="pair"><h2 class="h3">Goes well with</h2><div class="grid products two"><?php foreach ( $pair as $p ) echo lh_card( $p, false, true ); // phpcs:ignore ?></div></div><?php endif; ?>
	</section>
	<?php return ob_get_clean();
} );

/** Bundle builder. Live: POSTs three ids (nonce) to the handler above. Static: store.js adds a bundle line to the simulated cart. */
add_shortcode( 'lh_bundle', function () {
	$items = lh_items( [ 'tag' => [ 'bundle' ] ] );
	ob_start(); ?>
	<section class="wrap section">
		<p class="eyebrow">Build your own bundle</p>
		<h1 class="h2">Pick any three. Save 12 percent.</h1>
		<p class="lead" style="margin-top:8px">Bags, gear, mugs, in any mix. The price updates as you choose.</p>
		<form class="bundle" method="post" action="/bundle/" data-bundle data-rate="<?php echo esc_attr( LH_BUNDLE_RATE ); ?>">
			<?php wp_nonce_field( 'lh_bundle', 'lh_bundle_nonce' ); ?>
			<div>
				<div class="chips js-only" role="group" aria-label="Filter bundle items">
					<button class="chip on" type="button" aria-pressed="true" data-bcat="all">All</button>
					<button class="chip" type="button" aria-pressed="false" data-bcat="coffee">Coffee</button>
					<button class="chip" type="button" aria-pressed="false" data-bcat="brewing-gear">Gear</button>
					<button class="chip" type="button" aria-pressed="false" data-bcat="mugs-and-merch">Mugs and merch</button>
				</div>
				<fieldset class="pick"><legend class="sr">Bundle items, pick three</legend>
					<?php foreach ( $items as $i => $it ) : $meta = ! empty( $it['vars'] ) ? '12 oz' : ( $it['d']['bundleMeta'] ?? '' ); ?>
						<label class="pick-item" data-cat="<?php echo esc_attr( $it['cat'] ); ?>">
							<input type="checkbox" data-role="pick" name="lh_pick[]" value="<?php echo (int) $it['id']; ?>" data-price="<?php echo esc_attr( $it['price'] ); ?>" data-name="<?php echo esc_attr( $it['name'] ); ?>" data-img="<?php echo esc_attr( $it['img'] ? wp_make_link_relative( wp_get_attachment_image_url( $it['img'], 'thumbnail' ) ) : '' ); ?>">
							<span class="check" aria-hidden="true"><?php echo lh_icon( 'check', 16 ); // phpcs:ignore ?></span>
							<?php echo lh_img( $it['img'], 'medium', '(max-width: 767px) 45vw, 220px', $i < 3 ); // phpcs:ignore ?>
							<b><?php echo esc_html( $it['d']['bundleLabel'] ?? $it['name'] ); ?></b>
							<span class="meta"><span><?php echo esc_html( $meta ); ?></span><span class="price"><?php echo lh_money( $it['price'] ); ?></span></span>
						</label>
					<?php endforeach; ?>
				</fieldset>
			</div>
			<aside class="summary" aria-label="Your bundle">
				<h2 class="h4">Your bundle</h2>
				<p class="small mute" data-b-status aria-live="polite" style="margin-top:4px">0 of 3 picked</p>
				<div class="progress" aria-hidden="true"><i data-b-bar></i></div>
				<ol class="slots" data-b-slots><li class="slot">1. Pick a coffee, or anything</li><li class="slot">2. Pick another</li><li class="slot">3. And one more</li></ol>
				<div class="totals">
					<div><span>Items</span><span data-b-items>$0.00</span></div>
					<div><span>Bundle discount (12 percent)</span><span class="save" data-b-disc>$0.00</span></div>
					<div class="big"><span>Bundle price</span><span data-b-total>$0.00</span></div>
				</div>
				<p class="small mute" style="margin-top:10px">Ships free as one box with a printed card listing what is inside.</p>
				<button class="btn btn-primary btn-block" type="submit" data-b-cta style="margin-top:16px">Add bundle to cart</button>
				<p class="small mute nojs-note">Pick exactly three, then add the bundle.</p>
			</aside>
		</form>
	</section>
	<?php return ob_get_clean();
} );

/* ---- simulated pages for the static copy (they also render on live WordPress, with a pointer to the real cart) ---- */
function lh_live_pointer( $what ) {
	return '<p class="demo-note live-only">' . lh_icon( 'info', 18 ) . '<span>This page is the simulated ' . $what . ' used by the static copy of the site. On this live demo, use the <a href="' . esc_url( wp_make_link_relative( wc_get_cart_url() ) ) . '">real cart</a>.</span></p>';
}
add_shortcode( 'lh_sim_cart', function () {
	return '<section class="wrap section sim" data-sim-cart><ol class="steps"><li class="on" aria-current="step">Cart</li><li>Checkout</li><li>Confirmation</li></ol><h1 class="h2">Your cart</h1>' . lh_live_pointer( 'cart' )
		. '<noscript><p class="demo-note">The demo cart needs JavaScript. Nothing is stored or sent.</p></noscript>'
		. '<div class="cart-grid"><div><div data-cart-lines><p class="empty">Your cart is empty. <a href="/shop/">Browse the coffee</a></p></div><p class="small" data-cart-more><a class="inline-44" href="/shop/">Continue shopping</a></p></div>'
		. '<aside class="summary" aria-label="Order summary" data-cart-summary><h2 class="h4">Summary</h2><div class="totals" data-cart-totals style="margin-top:12px"></div>'
		. '<form class="coupon" data-coupon><label for="coupon">Coupon code</label><div class="coupon-row"><input type="text" id="coupon" autocomplete="off" placeholder="Code"><button class="btn btn-secondary" type="submit">Apply</button></div><p class="small" data-coupon-msg aria-live="polite"></p></form>'
		. '<a class="btn btn-primary btn-block" href="/demo-checkout/" data-cart-checkout>Check out</a>'
		. '<p class="lock" style="margin-top:12px">' . lh_icon( 'lock', 16 ) . 'Demo cart, held in your browser only. Checkout runs in test mode; nothing is charged.</p></aside></div></section>';
} );
add_shortcode( 'lh_sim_checkout', function () {
	$f = fn( $id, $label, $type = 'text', $ac = '', $extra = '' ) => '<div class="field"><label for="' . $id . '">' . $label . '</label><input type="' . $type . '" id="' . $id . '" required autocomplete="' . $ac . '"' . $extra . '></div>';
	return '<section class="wrap section sim" data-sim-checkout><ol class="steps"><li>Cart</li><li class="on" aria-current="step">Checkout</li><li>Confirmation</li></ol><h1 class="h2">Checkout</h1>' . lh_live_pointer( 'checkout' )
		. '<div class="cart-grid"><form method="post" action="/demo-form/" data-checkout-form novalidate>'
		. '<div class="demo-note">' . lh_icon( 'info', 18 ) . '<span><b>Demo checkout, test mode.</b> Nothing is charged, no order is placed and nothing you type leaves this page.</span></div>'
		. '<h2 class="h5">Contact</h2>' . $f( 'co-email', 'Email', 'email', 'email', ' placeholder="you@example.com"' )
		. '<h2 class="h5">Shipping address</h2><div class="fields-2">' . $f( 'co-first', 'First name', 'text', 'given-name' ) . $f( 'co-last', 'Last name', 'text', 'family-name' ) . '</div>'
		. $f( 'co-addr', 'Street address', 'text', 'street-address' ) . '<div class="fields-2">' . $f( 'co-city', 'City', 'text', 'address-level2' ) . $f( 'co-zip', 'ZIP', 'text', 'postal-code', ' inputmode="numeric"' ) . '</div>'
		. '<div class="field"><label for="co-ship">Shipping method</label><select id="co-ship" required><option value="std">Standard, 2 to 4 days, free over $40</option><option value="pickup">Local pickup at Mill Street, free</option><option value="express">Express, 1 to 2 days, $12</option></select></div>'
		. '<h2 class="h5">Payment</h2><div class="pay-option"><label class="radio-line"><input type="radio" checked aria-describedby="pay-note"> <span><b>Test order (no payment taken)</b><br><span class="small mute" id="pay-note">The demo uses an offline test gateway. There is no card field and no payment provider.</span></span></label></div>'
		. '<button class="btn btn-primary btn-block" type="submit" data-place-order style="margin-top:24px">Place test order</button></form>'
		. '<aside class="summary" aria-label="Order summary"><h2 class="h4">Order summary</h2><div data-cart-lines class="compact"></div><div class="totals" data-cart-totals style="margin-top:12px"></div></aside></div></section>';
} );
add_shortcode( 'lh_sim_confirm', function () {
	return '<section class="wrap section confirm sim" data-sim-confirm><ol class="steps"><li>Cart</li><li>Checkout</li><li class="on" aria-current="step">Confirmation</li></ol>'
		. '<div class="stamp" aria-hidden="true">' . lh_icon( 'check', 44 ) . '</div><p class="eyebrow">Test order placed</p><h1 class="h2" data-confirm-title>Thanks. Your coffee roasts Thursday.</h1>'
		. '<p class="lead" style="margin:12px auto 0" data-confirm-lead>This is the demo confirmation page. No order was placed and no email is sent.</p>'
		. '<div class="order" data-confirm-order hidden></div>'
		. '<div class="confirm-actions"><a class="btn btn-primary" href="/shop/">Keep shopping</a><a class="btn btn-secondary" href="/">Back to home</a></div></section>';
} );
add_shortcode( 'lh_demo_form', fn() => '<section class="wrap section prose"><h1 class="h2">This is a demo form.</h1><p>This sample store has no server behind its static copy, so nothing was sent and nothing was saved. The live demo in WordPress Playground runs the real WooCommerce cart and checkout with a test gateway.</p><p><a href="/shop/">Back to the shop</a></p></section>' );
add_shortcode( 'lh_credits', function () {
	$rows = '';
	$fh = fopen( WP_CONTENT_DIR . '/demo-data/image-credits.csv', 'r' ); fgetcsv( $fh, 0, ',', '"', '' );
	while ( ( $r = fgetcsv( $fh, 0, ',', '"', '' ) ) !== false ) $rows .= '<tr><td>' . esc_html( preg_replace( '/\.(jpe?g|png|webp)$/i', '', $r[1] ) ) . '</td><td>' . esc_html( $r[2] ) . '</td></tr>';
	fclose( $fh );
	return '<section class="wrap section prose"><p class="eyebrow">Credits</p><h1 class="h2">Photo credits</h1><p>All photos are CC0 or public domain images from Wikimedia Commons, cropped and re-encoded. Instrument Serif is licensed under the SIL Open Font License.</p><table class="credits-table"><thead><tr><th scope="col">Photo (Wikimedia Commons file)</th><th scope="col">License</th></tr></thead><tbody>' . $rows . '</tbody></table></section>';
} );

// Utility pages stay out of search results.
add_filter( 'wp_robots', function ( $r ) { if ( is_page( [ 'demo-form', 'credits', 'demo-cart', 'demo-checkout', 'demo-order' ] ) ) { $r['noindex'] = true; $r['follow'] = true; unset( $r['max-image-preview'] ); } return $r; } );
add_filter( 'wpseo_robots', fn( $v ) => is_page( [ 'demo-form', 'credits', 'demo-cart', 'demo-checkout', 'demo-order' ] ) ? 'noindex, follow' : $v );
