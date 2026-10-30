<?php
/**
 * Plugin Name: Saltline online booking (Events Manager)
 * Description: Tours are Events Manager events (one per departure, 8 spaces). /book/ renders a six-step booking flow over real
 *              availability; on live WordPress "Hold my seats" creates a real Events Manager booking (pay at the boathouse).
 *              The static copy runs the same flow over the exported availability snapshot and nothing leaves the page.
 */

function sl_book_data() {
	static $d = null;
	if ( null === $d ) $d = json_decode( file_get_contents( WP_CONTENT_DIR . '/demo-data/book-data.json' ), true );
	return $d;
}

/* ---------------------------------------------------------------- seed */
function sl_booking_seed() {
	if ( ! defined( 'EM_DIR' ) ) return;
	wp_set_current_user( 1 );
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	include_once EM_DIR . '/em-install.php';
	if ( function_exists( 'em_install' ) ) em_install();
	foreach ( [ 'dbem_bookings_approval' => 0, 'dbem_bookings_double' => 1, 'dbem_bookings_anonymous' => 1, 'dbem_bookings_registration_disable' => 1, 'dbem_rsvp_enabled' => 1, 'dbem_css' => 0, 'dbem_timezone_default' => 'America/Los_Angeles' ] as $k => $v ) update_option( $k, $v );
	update_option( 'dbem_bookings_email_confirmed_subject', 'You are booked: #_EVENTNAME' );
	$d = sl_book_data();
	$cats = [];
	foreach ( $d['tours'] as $t ) {
		$term = term_exists( $t['slug'], 'event-categories' ) ?: wp_insert_term( $t['name'], 'event-categories', [ 'slug' => $t['slug'] ] );
		$cats[ $t['key'] ] = is_array( $term ) ? (int) $term['term_id'] : 0;
	}
	$loc = new EM_Location();
	$loc->location_name = $d['location']; $loc->location_address = 'Marrow Cove public launch'; $loc->location_town = 'Marrow Cove'; $loc->location_country = 'US'; $loc->post_content = 'Meeting point for every Saltline tour.';
	$loc->save();
	$tours = array_column( $d['tours'], null, 'key' );
	$map = [];
	foreach ( $d['departures'] as $dep ) {
		$t = $tours[ $dep['tour'] ];
		$e = new EM_Event();
		$e->event_name = $t['name'] . ', ' . date( 'M j', strtotime( $dep['date'] ) ) . ', ' . date( 'g:i a', strtotime( $dep['time'] ) );
		$e->event_start_date = $dep['date']; $e->event_end_date = $dep['date'];
		$e->event_start_time = $dep['time'] . ':00'; $e->event_end_time = $dep['end'] . ':00';
		$e->event_timezone = 'America/Los_Angeles'; $e->event_rsvp = 1; $e->event_spaces = $d['capacity'];
		$e->location_id = $loc->location_id; $e->post_content = $dep['note'] . '. Meet at the boathouse 15 minutes before.'; $e->force_status = 'publish';
		if ( ! $e->save() ) continue;
		if ( $cats[ $dep['tour'] ] ) wp_set_object_terms( $e->post_id, [ $cats[ $dep['tour'] ] ], 'event-categories' );
		$ticket = new EM_Ticket( [ 'event_id' => $e->event_id, 'ticket_name' => 'Paddler', 'ticket_description' => 'Adults 13+ and kids 8 to 12, same price', 'ticket_price' => $t['price'], 'ticket_spaces' => $d['capacity'], 'ticket_min' => 1, 'ticket_max' => $d['capacity'] ] );
		$ticket->save();
		$map[] = [ 'tour' => $dep['tour'], 'date' => $dep['date'], 'time' => $dep['time'], 'note' => $dep['note'], 'event' => (int) $e->event_id, 'ticket' => (int) $ticket->ticket_id ];
		// Sample bookings so the calendar shows realistic seats left (real Events Manager bookings, guest "Sample booking").
		if ( $dep['booked'] > 0 ) sl_em_book( (int) $e->event_id, (int) $ticket->ticket_id, (int) $dep['booked'], 'Sample booking', 'sample@example.com', '(555) 010-0150', false );
	}
	update_option( 'sl_departures', $map );
	wp_set_current_user( 0 );
}

/** Create a real Events Manager booking through its own request handling (the same path as its booking form). */
function sl_em_book( $event_id, $ticket_id, $spaces, $name, $email, $phone, $notify = true, $notes = '' ) {
	$saved = $_REQUEST; $savedp = $_POST;
	$_REQUEST = $_POST = [ 'event_id' => $event_id, 'em_tickets' => [ $ticket_id => [ 'spaces' => $spaces ] ], 'user_name' => $name, 'user_email' => $email, 'dbem_phone' => $phone, 'booking_comment' => $notes, 'data_privacy_consent' => 1 ];
	$event = em_get_event( $event_id );
	$b = new EM_Booking(); $b->event_id = $event_id;
	if ( ! $notify ) add_filter( 'em_booking_email', '__return_false' );
	$ok = $b->get_post() && $b->validate() && $event->get_bookings()->add( $b );
	if ( ! $notify ) remove_filter( 'em_booking_email', '__return_false' );
	$_REQUEST = $saved; $_POST = $savedp;
	return $ok ? $b : ( $b->errors ?: [ 'Booking could not be created.' ] );
}

/** Availability for the flow: every departure with live seats left from Events Manager. */
function sl_availability() {
	$d = sl_book_data();
	$deps = [];
	foreach ( (array) get_option( 'sl_departures', [] ) as $m ) {
		$e = em_get_event( $m['event'] );
		if ( ! $e || ! $e->event_id ) continue;
		$deps[] = $m + [ 'left' => max( 0, (int) $e->get_bookings()->get_available_spaces() ) ];
	}
	return [ 'month' => $d['month'], 'today' => $d['sampleToday'], 'capacity' => $d['capacity'], 'location' => $d['location'],
		'tours' => array_map( fn( $t ) => $t + [ 'thumb' => sl_tour_thumb( $t['img'] ) ], $d['tours'] ), 'departures' => $deps ];
}
function sl_tour_thumb( $key ) {
	$att = get_posts( [ 'post_type' => 'attachment', 'name' => sanitize_title( $key . '-900.webp' ), 'numberposts' => 1 ] );
	return $att ? wp_make_link_relative( wp_get_attachment_image_url( $att[0]->ID, 'medium' ) ) : '';
}

/* ---------------------------------------------------------------- live submit */
add_action( 'template_redirect', function () {
	if ( empty( $_POST['sl_book_nonce'] ) || ! is_page( 'book' ) ) return;
	if ( ! wp_verify_nonce( sanitize_key( $_POST['sl_book_nonce'] ), 'sl_book' ) ) return;
	$in = wp_unslash( $_POST );
	$event = absint( $in['sl_event'] ?? 0 ); $adults = absint( $in['sl_adults'] ?? 0 ); $kids = absint( $in['sl_kids'] ?? 0 );
	$dep = current( array_filter( (array) get_option( 'sl_departures', [] ), fn( $m ) => (int) $m['event'] === $event ) );
	$err = '';
	if ( ! $dep ) $err = 'Pick a departure.';
	elseif ( $adults < 1 || $kids > $adults ) $err = 'Every booking needs an adult, with one adult per kid.';
	elseif ( empty( $in['sl_consent'] ) ) $err = 'Please confirm the swim and age policy.';
	elseif ( ! is_email( $in['sl_email'] ?? '' ) || '' === trim( $in['sl_name'] ?? '' ) ) $err = 'Please enter your name and a valid email.';
	if ( ! $err ) {
		$notes = trim( sanitize_textarea_field( ( $in['sl_notes'] ?? '' ) ) . ' Experience: ' . sanitize_text_field( $in['sl_exp'] ?? '' ) . '. Adults ' . $adults . ', kids ' . $kids . '. Pay at the boathouse.' );
		$b = sl_em_book( $event, (int) $dep['ticket'], $adults + $kids, sanitize_text_field( $in['sl_name'] ), sanitize_email( $in['sl_email'] ), sanitize_text_field( $in['sl_phone'] ?? '' ), true, $notes );
		if ( $b instanceof EM_Booking ) {
			update_metadata( 'em_booking', $b->booking_id, 'sl_party', [ 'adults' => $adults, 'kids' => $kids ] );
			set_transient( 'sl_done_' . $b->booking_id, [ 'adults' => $adults, 'kids' => $kids, 'name' => sanitize_text_field( $in['sl_name'] ), 'email' => sanitize_email( $in['sl_email'] ) ], DAY_IN_SECONDS );
			wp_safe_redirect( add_query_arg( [ 'booked' => $b->booking_id, 'k' => wp_hash( 'sl' . $b->booking_id ) ], home_url( '/book/' ) ) . '#done' ); exit;
		}
		$err = wp_strip_all_tags( implode( ' ', (array) $b ) );
	}
	$GLOBALS['sl_book_error'] = $err;
} );

/* ---------------------------------------------------------------- render */
function sl_icon( $n, $s = 20 ) {
	$p = [ 'check' => '<path d="m5 12 5 5L20 7"/>', 'circle' => '<circle cx="12" cy="12" r="8"/>', 'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/>', 'prev' => '<path d="m15 6-6 6 6 6"/>', 'next' => '<path d="m9 6 6 6-6 6"/>' ];
	return '<svg width="' . $s . '" height="' . $s . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p[ $n ] . '</svg>';
}

add_shortcode( 'sl_booking', function () {
	$a = sl_availability();
	$tours = array_column( $a['tours'], null, 'key' );
	$pre = isset( $_GET['tour'] ) ? sanitize_title( wp_unslash( $_GET['tour'] ) ) : ''; // phpcs:ignore
	$done = null;
	if ( ! empty( $_GET['booked'] ) && ! empty( $_GET['k'] ) && hash_equals( wp_hash( 'sl' . absint( $_GET['booked'] ) ), (string) $_GET['k'] ) ) { // phpcs:ignore
		$bk = em_get_booking( absint( $_GET['booked'] ) ); // phpcs:ignore
		if ( $bk && $bk->booking_id ) {
			$ev = $bk->get_event(); $party = get_transient( 'sl_done_' . $bk->booking_id ) ?: [ 'adults' => $bk->get_spaces(), 'kids' => 0 ];
			$dep = current( array_filter( (array) get_option( 'sl_departures', [] ), fn( $m ) => (int) $m['event'] === (int) $ev->event_id ) );
			$done = [ 'n' => 'SL-' . substr( $dep['date'], 0, 4 ) . '-' . str_pad( $bk->booking_id, 4, '0', STR_PAD_LEFT ), 'name' => $party['name'] ?? $bk->get_person()->get_name(), 'email' => $party['email'] ?? $bk->get_person()->user_email, 'tour' => $dep['tour'], 'date' => $dep['date'], 'time' => $dep['time'], 'adults' => (int) $party['adults'], 'kids' => (int) $party['kids'] ];
		}
	}
	$steps = [ 'tour' => 'Tour', 'date' => 'Date', 'time' => 'Time', 'party' => 'Party', 'details' => 'Details', 'confirm' => 'Confirm' ];
	ob_start(); ?>
	<div class="booking wrap" data-booking data-avail="<?php echo esc_attr( wp_json_encode( $a ) ); ?>" data-pre="<?php echo esc_attr( $pre ); ?>" data-done="<?php echo esc_attr( $done ? wp_json_encode( $done ) : '' ); ?>">
		<div class="page-head">
			<p class="eyebrow">Online booking</p>
			<h1>Book your morning on the water.</h1>
			<p class="lead">Pick a tour and a date, see the seats left on each departure, and hold your spots in about two minutes. You pay at the boathouse when you check in.</p>
		</div>
		<div class="nojs-book">
			<p class="note"><?php echo sl_icon( 'info', 18 ); // phpcs:ignore ?><span>Online booking needs JavaScript. Below are the departures for <?php echo esc_html( date( 'F Y', strtotime( $a['month'] . '-01' ) ) ); ?> with seats left (sample data); to book without JavaScript, <a href="/contact/">send us a request</a>.</span></p>
			<table class="dep-table"><caption class="screen-reader-text">Departures and seats left</caption><thead><tr><th scope="col">Date</th><th scope="col">Time</th><th scope="col">Tour</th><th scope="col">Seats left</th></tr></thead><tbody>
			<?php foreach ( array_slice( $a['departures'], 0, 24 ) as $dep ) echo '<tr><td>' . esc_html( date( 'D, M j', strtotime( $dep['date'] ) ) ) . '</td><td>' . esc_html( date( 'g:i a', strtotime( $dep['time'] ) ) ) . '</td><td>' . esc_html( $tours[ $dep['tour'] ]['name'] ) . '</td><td>' . ( $dep['left'] ? (int) $dep['left'] : 'Sold out' ) . '</td></tr>'; ?>
			</tbody></table>
		</div>
		<div class="layout js-book">
			<div>
				<ol class="stepper" aria-label="Booking steps"><?php $i = 1; foreach ( $steps as $k => $l ) echo '<li data-step="' . $k . '">' . $i++ . '. ' . $l . '</li>'; ?></ol>
				<form class="panel book-form" method="post" action="/book/">
					<?php wp_nonce_field( 'sl_book', 'sl_book_nonce' ); ?>
					<input type="hidden" name="sl_event" data-f-event><input type="hidden" name="sl_adults" data-f-adults><input type="hidden" name="sl_kids" data-f-kids>
					<?php if ( ! empty( $GLOBALS['sl_book_error'] ) ) echo '<p class="note err" role="alert">' . esc_html( $GLOBALS['sl_book_error'] ) . '</p>'; ?>
					<section class="step" data-s="tour" aria-labelledby="h-tour">
						<p class="eyebrow">Step 1 of 6</p><h2 id="h-tour" tabindex="-1">Which tour?</h2>
						<div class="tours" role="list">
							<?php foreach ( $a['tours'] as $t ) : ?>
								<button type="button" class="tour" role="listitem" data-tour="<?php echo esc_attr( $t['key'] ); ?>"><?php if ( $t['thumb'] ) echo '<img src="' . esc_url( $t['thumb'] ) . '" alt="" width="88" height="66" loading="lazy">'; ?><span><b><?php echo esc_html( $t['name'] ); ?></b><span class="meta"><span><?php echo esc_html( $t['dur'] ); ?></span><span><?php echo esc_html( $t['level'] ); ?></span><span><?php echo esc_html( $t['season'] ); ?></span><span><strong>$<?php echo (int) $t['price']; ?></strong> per person</span></span></span></button>
							<?php endforeach; ?>
						</div>
						<p class="small mute" style="margin-top:16px">Groups of 9 or more, or a private tour: <a href="/contact/">write to us</a> and we will set up a departure just for you.</p>
					</section>
					<section class="step" data-s="date" aria-labelledby="h-date">
						<p class="eyebrow">Step 2 of 6</p><h2 id="h-date" tabindex="-1">Pick a date for the <span data-tourname></span></h2>
						<div class="cal-head"><button class="iconbtn" type="button" data-month="-1"><span class="screen-reader-text">Previous month</span><?php echo sl_icon( 'prev', 22 ); // phpcs:ignore ?></button><h3 aria-live="polite" data-monthname></h3><button class="iconbtn" type="button" data-month="1"><span class="screen-reader-text">Next month</span><?php echo sl_icon( 'next', 22 ); // phpcs:ignore ?></button></div>
						<p class="small mute" data-month-note hidden>Only July 2027 has sample departures in this demo.</p>
						<table class="cal" aria-labelledby="h-date"><thead><tr><?php foreach ( [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' ] as $wd ) echo '<th scope="col"><abbr title="' . $wd . '">' . substr( $wd, 0, 3 ) . '</abbr></th>'; ?></tr></thead><tbody data-cal></tbody></table>
						<div class="legend"><span><i class="lg-sea"></i>Seats available</span><span><i class="lg-few"></i>3 or fewer left</span><span><i class="lg-none"></i>Sold out or no departure</span></div>
						<div class="step-foot"><button class="btn btn-secondary" type="button" data-back>Back</button><button class="btn btn-primary" type="button" data-next aria-disabled="true">Pick a date to continue</button></div>
					</section>
					<section class="step" data-s="time" aria-labelledby="h-time">
						<p class="eyebrow">Step 3 of 6</p><h2 id="h-time" tabindex="-1">Departures on <span data-datename></span></h2>
						<p class="small mute" style="margin-top:6px">Meet at the boathouse 15 minutes before. Each departure takes up to 8 paddlers.</p>
						<div class="slots" data-slots role="list"></div>
						<div class="note" style="margin-top:16px"><?php echo sl_icon( 'info', 18 ); // phpcs:ignore ?><span>Times shift with the tide through the season. The time you book is the time on your confirmation; we email you if the weather changes it.</span></div>
						<div class="step-foot"><button class="btn btn-secondary" type="button" data-back>Back</button><button class="btn btn-primary" type="button" data-next aria-disabled="true">Pick a departure</button></div>
					</section>
					<section class="step" data-s="party" aria-labelledby="h-party">
						<p class="eyebrow">Step 4 of 6</p><h2 id="h-party" tabindex="-1">How many paddlers?</h2>
						<div class="party">
							<div class="counter"><div><b>Adults</b><span class="s">13 and up, <span data-price></span> each</span></div><div class="qty" role="group" aria-label="Adults"><button type="button" data-ad="-1" aria-label="Fewer adults">&minus;</button><output data-adults aria-live="polite">2</output><button type="button" data-ad="1" aria-label="More adults">+</button></div></div>
							<div class="counter"><div><b>Kids</b><span class="s">8 to 12, same price, paired with an adult in a double kayak</span></div><div class="qty" role="group" aria-label="Kids"><button type="button" data-kd="-1" aria-label="Fewer kids">&minus;</button><output data-kids aria-live="polite">0</output><button type="button" data-kd="1" aria-label="More kids">+</button></div></div>
							<div><div class="cap-row"><span data-captext></span><span class="mute">8 per departure</span></div><div class="capbar" aria-hidden="true"><i data-capbar></i></div></div>
							<div class="note" data-capnote hidden><?php echo sl_icon( 'info', 18 ); // phpcs:ignore ?><span>That is every remaining seat on this departure. For a bigger group, <a href="/contact/">write to us</a> and we will add a private departure.</span></div>
						</div>
						<div class="step-foot"><button class="btn btn-secondary" type="button" data-back>Back</button><button class="btn btn-primary" type="button" data-next>Continue</button></div>
					</section>
					<section class="step" data-s="details" aria-labelledby="h-details">
						<p class="eyebrow">Step 5 of 6</p><h2 id="h-details" tabindex="-1">Who is coming?</h2>
						<div class="fields-2" style="margin-top:16px">
							<div class="field"><label for="f-name">Full name</label><input type="text" id="f-name" name="sl_name" autocomplete="name" required></div>
							<div class="field"><label for="f-phone">Mobile phone</label><input type="tel" id="f-phone" name="sl_phone" autocomplete="tel" required></div>
						</div>
						<div class="field"><label for="f-email">Email</label><input type="email" id="f-email" name="sl_email" autocomplete="email" required aria-describedby="f-email-hint"><p class="small mute" id="f-email-hint" style="margin-top:6px">Your confirmation and any weather updates go here.</p></div>
						<div class="field"><label for="f-exp">Kayaking experience</label><select id="f-exp" name="sl_exp"><option>First time for some of us</option><option>We have all paddled before</option><option>Experienced, we paddle regularly</option></select></div>
						<div class="field"><label for="f-notes">Anything we should know? <span class="mute" style="font-weight:400">(optional)</span></label><textarea id="f-notes" name="sl_notes" placeholder="Allergies, mobility, a birthday on board"></textarea></div>
						<div class="field"><label class="check"><input type="checkbox" name="sl_consent" value="1" required><span>Everyone in my group can swim and is 8 or older. I have read the <a href="/faq/#weather-and-cancellations">safety and cancellation policy</a>.</span></label></div>
						<div class="step-foot"><button class="btn btn-secondary" type="button" data-back>Back</button><button class="btn btn-primary" type="button" data-next>Review booking</button></div>
					</section>
					<section class="step" data-s="confirm" aria-labelledby="h-confirm">
							<p class="eyebrow">Step 6 of 6</p><h2 id="h-confirm" tabindex="-1">Check the details and hold your seats.</h2>
							<div class="review" data-review></div>
							<div class="pay"><p class="pay-card"><b>Pay at the boathouse</b><span class="small mute">Card or cash when you check in. Your seats are held now. No deposit.</span></p></div>
							<p class="small mute" style="margin-top:16px">Free changes up to 24 hours before. If we cancel for weather you get a full refund or a new date.</p>
							<div class="step-foot"><button class="btn btn-secondary" type="button" data-back>Back</button><button class="btn btn-primary" type="submit" data-hold>Hold my seats</button></div>
					</section>
					<section class="step" data-s="done" aria-labelledby="h-done">
						<div class="stamp" aria-hidden="true"><?php echo sl_icon( 'check', 36 ); // phpcs:ignore ?></div>
						<p class="eyebrow">Booked</p><h2 id="h-done" tabindex="-1" data-done-title>See you on the water.</h2>
						<p style="margin-top:8px" data-done-lead></p>
						<div class="done-actions"><button class="btn btn-secondary" type="button" data-again>Book another tour</button></div>
						<div class="email" aria-label="Confirmation email preview" data-email></div>
					</section>
				</form>
			</div>
			<aside class="aside" aria-label="Your booking so far">
				<h2>Your booking</h2>
				<ul class="sum" data-sum></ul>
				<div class="total"><span>Total, pay at check-in</span><b data-total>$0</b></div>
				<p class="policy">No deposit. Free changes up to 24 hours before. Full refund if we cancel for weather. Kids 8 and up, everyone must be able to swim.</p>
				<p class="policy">Questions? <a href="mailto:hello@saltlinekayak.example.com">hello@saltlinekayak.example.com</a> or (555) 010-0100.</p>
				<p class="policy static-only">Availability shown is sample data.</p>
			</aside>
		</div>
	</div>
	<?php return ob_get_clean();
} );

// Events Manager front-end assets are not used: /book/ renders its own markup.
add_action( 'wp_enqueue_scripts', function () {
	foreach ( [ 'events-manager', 'events-manager-pro', 'em-flatpickr', 'events-manager-frontend', 'chart-js', 'moment', 'em-select2', 'events-manager-admin' ] as $h ) { wp_dequeue_style( $h ); wp_dequeue_script( $h ); }
	if ( is_page( 'book' ) ) wp_enqueue_script( 'sl-book', get_stylesheet_directory_uri() . '/assets/js/book.js', [], '1.0.0', [ 'strategy' => 'defer', 'in_footer' => true ] );
}, 100 );
add_action( 'wp_head', fn() => print( "<script>document.documentElement.classList.add('js-on')</script>\n" ), 0 );

// Fallback: if the blueprint's seed ran before Events Manager was fully loaded, seed departures on the first request.
add_action( 'wp_loaded', function () {
	if ( get_option( 'demo_seeded' ) && ! get_option( 'sl_departures' ) && ! get_transient( 'sl_seeding' ) && defined( 'EM_DIR' ) ) {
		set_transient( 'sl_seeding', 1, 300 );
		sl_booking_seed();
	}
} );
