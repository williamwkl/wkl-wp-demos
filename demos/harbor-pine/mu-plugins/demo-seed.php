<?php
/**
 * Plugin Name: Harbor & Pine demo seed
 * Description: Builds Fluent Forms, media, Elementor kit globals and Elementor pages once. Called from the blueprint via runPHP.
 * The same seed serves both builds; hp_mode (naive|optimized) only changes images, icon delivery and Elementor performance settings.
 */

/* ---------------- Elementor element helpers ---------------- */
function hp_eid() { return substr( md5( uniqid( '', true ) . wp_rand() ), 0, 7 ); }
/** Container. $cls = CSS classes, $tag = html tag. Layout itself comes from the child stylesheet. */
function hp_con( $cls, $children, $tag = 'div', $extra = [] ) {
	return [ 'id' => hp_eid(), 'elType' => 'container', 'isInner' => false, 'elements' => $children,
		// Padding and gaps are left unset so the child stylesheet's section classes control spacing.
		'settings' => array_merge( [ 'content_width' => 'full', 'flex_direction' => 'column', 'css_classes' => $cls, 'html_tag' => $tag ], $extra ) ];
}
function hp_w( $type, $settings, $cls = '' ) {
	if ( $cls ) $settings['_css_classes'] = $cls;
	return [ 'id' => hp_eid(), 'elType' => 'widget', 'widgetType' => $type, 'settings' => $settings, 'elements' => [] ];
}
function hp_heading( $text, $tag = 'h2', $cls = '' ) { return hp_w( 'heading', [ 'title' => $text, 'header_size' => $tag ], $cls ); }
function hp_text( $html, $cls = '' ) { return hp_w( 'text-editor', [ 'editor' => $html ], $cls ); }
function hp_html( $html, $cls = '' ) { return hp_w( 'html', [ 'html' => $html ], $cls ); }
function hp_btn( $text, $url, $secondary = false ) { return hp_w( 'button', [ 'text' => $text, 'link' => [ 'url' => $url, 'is_external' => '', 'nofollow' => '' ], 'size' => 'sm' ], $secondary ? 'btn-secondary' : 'btn-primary' ); }
function hp_icons( $items, $icon, $cls ) {
	$list = [];
	foreach ( $items as $t ) {
		$i = is_array( $t ) ? $t[1] : $icon;
		$list[] = [ '_id' => hp_eid(), 'text' => is_array( $t ) ? $t[0] : $t, 'selected_icon' => $i ? [ 'value' => $i, 'library' => str_starts_with( $i, 'far' ) ? 'fa-regular' : 'fa-solid' ] : [ 'value' => '', 'library' => '' ] ];
	}
	return hp_w( 'icon-list', [ 'icon_list' => $list, 'view' => 'traditional' ], $cls );
}
function hp_form( $id ) { return hp_w( 'shortcode', [ 'shortcode' => '[fluentform id="' . $id . '"]' ] ); }
function hp_image( $att, $cls = 'svc-photo' ) {
	return hp_w( 'image', [ 'image' => [ 'url' => wp_get_attachment_url( $att ), 'id' => $att, 'source' => 'library' ], 'image_size' => hp_optimized() ? 'large' : 'full' ], $cls );
}

/* ---------------- Fluent Forms ---------------- */
function hp_ff_label( $label, $required ) {
	return [ 'container_class' => '', 'label' => $label, 'label_placement' => 'top', 'admin_field_label' => $label, 'help_message' => '',
		'validation_rules' => [ 'required' => [ 'value' => $required, 'message' => 'Please fill in ' . strtolower( $label ) . '.' ] ], 'conditional_logics' => [] ];
}
function hp_ff_input( $name, $label, $type = 'text', $required = true, $auto = '' ) {
	$el = 'email' === $type ? 'input_email' : 'input_text';
	$s = hp_ff_label( $label, $required );
	if ( 'email' === $type ) $s['validation_rules']['email'] = [ 'value' => true, 'message' => 'Please enter a valid email address.' ];
	return [ 'index' => 0, 'element' => $el, 'attributes' => [ 'type' => $type, 'name' => $name, 'value' => '', 'id' => '', 'class' => '', 'placeholder' => '', 'autocomplete' => $auto ],
		'settings' => $s, 'editor_options' => [ 'title' => $label, 'template' => 'inputText' ], 'uniqElKey' => 'el_' . $name ];
}
function hp_ff_select( $name, $label, $options, $required = true ) {
	$opts = [];
	foreach ( $options as $o ) $opts[] = [ 'label' => $o, 'value' => $o, 'calc_value' => '' ];
	$s = hp_ff_label( $label, $required ); $s['advanced_options'] = $opts; $s['placeholder'] = 'Choose one';
	return [ 'index' => 0, 'element' => 'select', 'attributes' => [ 'name' => $name, 'value' => '', 'id' => '', 'class' => '' ],
		'settings' => $s, 'editor_options' => [ 'title' => $label, 'template' => 'select' ], 'uniqElKey' => 'el_' . $name ];
}
function hp_ff_textarea( $name, $label, $rows = 4 ) {
	return [ 'index' => 0, 'element' => 'textarea', 'attributes' => [ 'name' => $name, 'value' => '', 'id' => '', 'class' => '', 'placeholder' => '', 'rows' => $rows, 'cols' => 2 ],
		'settings' => hp_ff_label( $label, false ), 'editor_options' => [ 'title' => $label, 'template' => 'inputTextarea' ], 'uniqElKey' => 'el_' . $name ];
}
function hp_ff_consent() {
	return [ 'index' => 0, 'element' => 'terms_and_condition', 'attributes' => [ 'type' => 'checkbox', 'name' => 'consent', 'value' => false, 'class' => '' ],
		'settings' => [ 'tnc_html' => 'I understand that submitting this form does not create an attorney-client relationship.', 'has_checkbox' => true, 'admin_field_label' => 'Consent', 'container_class' => '',
			'validation_rules' => [ 'required' => [ 'value' => true, 'message' => 'Please confirm you have read this.' ] ], 'conditional_logics' => [] ],
		'editor_options' => [ 'title' => 'Terms & Conditions', 'template' => 'termsCheckbox' ], 'uniqElKey' => 'el_consent' ];
}
function hp_ff_two( $a, $b ) {
	return [ 'index' => 0, 'element' => 'container', 'attributes' => [], 'settings' => [ 'container_class' => '', 'conditional_logics' => [] ],
		'columns' => [ [ 'fields' => [ $a ], 'width' => 50 ], [ 'fields' => [ $b ], 'width' => 50 ] ], 'editor_options' => [ 'title' => 'Two Column Container' ], 'uniqElKey' => 'el_two_' . $a['attributes']['name'] ];
}
function hp_ff_create( $title, $fields, $submit ) {
	global $wpdb;
	$json = [ 'fields' => $fields, 'submitButton' => [ 'uniqElKey' => 'el_submit', 'element' => 'button', 'attributes' => [ 'type' => 'submit', 'class' => '' ],
		'settings' => [ 'align' => 'left', 'button_style' => '', 'container_class' => '', 'help_message' => '', 'background_color' => '', 'button_size' => 'md', 'color' => '', 'button_ui' => [ 'type' => 'default', 'text' => $submit, 'img_url' => '' ] ],
		'editor_options' => [ 'title' => 'Submit Button' ] ] ];
	$wpdb->insert( $wpdb->prefix . 'fluentform_forms', [ 'title' => $title, 'status' => 'published', 'appearance_settings' => '', 'form_fields' => wp_json_encode( $json ), 'has_payment' => 0, 'type' => 'form', 'conditions' => '', 'created_by' => 1, 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ] );
	$id = $wpdb->insert_id;
	$settings = [ 'confirmation' => [ 'redirectTo' => 'samePage', 'messageToShow' => 'Thank you. Someone from our office will call you within one business day.', 'samePageFormBehavior' => 'hide_form' ],
		'layout' => [ 'labelPlacement' => 'top', 'helpMessagePlacement' => 'with_label', 'errorMessagePlacement' => 'inline', 'cssClassName' => '', 'asteriskPlacement' => 'asterisk-right' ],
		'restrictions' => [ 'limitNumberOfEntries' => [ 'enabled' => false ], 'scheduleForm' => [ 'enabled' => false ], 'requireLogin' => [ 'enabled' => false ], 'denyEmptySubmission' => [ 'enabled' => false ] ] ];
	$wpdb->insert( $wpdb->prefix . 'fluentform_form_meta', [ 'form_id' => $id, 'meta_key' => 'formSettings', 'value' => wp_json_encode( $settings ) ] );
	$wpdb->insert( $wpdb->prefix . 'fluentform_form_meta', [ 'form_id' => $id, 'meta_key' => 'notifications', 'value' => wp_json_encode( [ 'name' => 'Intake notification', 'sendTo' => [ 'type' => 'email', 'email' => 'intake@harborpinelaw.example.com' ], 'fromName' => 'Harbor & Pine website', 'subject' => 'New case evaluation request', 'message' => '{all_data}', 'enabled' => true ] ) ] );
	return $id;
}

/* ---------------- Media ---------------- */
function hp_media( $file, $alt, $caption ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$src  = get_stylesheet_directory() . '/assets/' . $file;
	$up   = wp_upload_dir();
	$dest = trailingslashit( $up['path'] ) . basename( $file );
	copy( $src, $dest );
	$mime = str_ends_with( $file, '.webp' ) ? 'image/webp' : 'image/jpeg';
	$id = wp_insert_attachment( [ 'post_mime_type' => $mime, 'post_title' => sanitize_title( basename( $file ) ), 'post_excerpt' => $caption, 'post_status' => 'inherit' ], $dest );
	if ( 'image/webp' === $mime ) {
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
	} else {
		// Naive build: no generated sizes, the full original is served (typical of an unoptimized upload).
		[ $w, $h ] = getimagesize( $dest );
		wp_update_attachment_metadata( $id, [ 'width' => $w, 'height' => $h, 'file' => _wp_relative_upload_path( $dest ), 'sizes' => [] ] );
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return $id;
}

/* ---------------- Page builders ---------------- */
function hp_page_hero( $eyebrow, $h1, $lead, $cta = true ) {
	$kids = [ hp_text( '<p class="eyebrow">' . $eyebrow . '</p>' ), hp_heading( $h1, 'h1' ), hp_text( '<p class="lead">' . $lead . '</p>' ) ];
	if ( $cta ) $kids[] = hp_con( 'actions', [ hp_btn( 'Start your free case evaluation', '/contact/#intake' ), hp_btn( 'Call (555) 010-0100', 'tel:+15550100100', true ) ] );
	return hp_con( 'page-hero', [ hp_con( 'wrap', $kids ) ], 'section' );
}
function hp_cta_band() {
	return hp_con( 'section dark', [ hp_con( 'wrap cta-inner', [
		hp_con( 'cta-copy', [ hp_heading( 'Tell us what happened. We will tell you where you stand.' ), hp_text( '<p>A free, confidential conversation with a lawyer. No obligation, no pressure, and a straight answer about whether you have a case.</p>' ) ] ),
		hp_con( 'actions', [ hp_btn( 'Start your free case evaluation', is_string( $GLOBALS['hp_eval'] ?? null ) ? $GLOBALS['hp_eval'] : '/contact/#intake' ), hp_btn( '(555) 010-0100', 'tel:+15550100100', true ) ] ),
	] ) ], 'section' );
}
function hp_faq( $items ) {
	$h = '<div class="faq-list">';
	foreach ( $items as $q ) $h .= '<details><summary>' . esc_html( $q[0] ) . '</summary><p>' . esc_html( $q[1] ) . '</p></details>';
	return hp_html( $h . '</div>' );
}
function hp_save_page( $slug, $title, $elements, $desc ) {
	$id = wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => '' ] );
	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $id, '_elementor_template_type', 'wp-page' );
	update_post_meta( $id, '_elementor_version', ELEMENTOR_VERSION );
	update_post_meta( $id, '_wp_page_template', 'elementor_header_footer' );
	update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );
	update_post_meta( $id, '_yoast_wpseo_metadesc', $desc );
	// Plain-text fallback in post_content (search, feeds, and the editor if Elementor is ever deactivated).
	$doc = \Elementor\Plugin::$instance->documents->get( $id );
	if ( $doc ) wp_update_post( [ 'ID' => $id, 'post_content' => $doc->get_content( true ) ? wp_strip_all_tags( $doc->get_content( true ) ) : '' ] );
	return $id;
}

function hp_service_page( $slug, $title, $eyebrow, $h1, $lead, $photo, $sections, $faqs, $mini, $full ) {
	$prose = [ hp_image( $photo ) ];
	foreach ( $sections as $s ) {
		$prose[] = hp_heading( $s[0], 'h2' );
		$prose[] = hp_text( $s[1] );
	}
	$prose[] = hp_heading( 'Common questions', 'h2' );
	$prose[] = hp_faq( $faqs );
	$side = hp_con( 'card sticky-side', [ hp_heading( 'Free case evaluation', 'h2' ), hp_text( '<p class="sub">Leave a number and a lawyer will call you within one business day.</p><p class="demo-note">Demo form: nothing is sent.</p>' ), hp_form( $mini ) ], 'aside' );
	return [
		hp_page_hero( $eyebrow, $h1, $lead ),
		hp_con( 'section', [ hp_con( 'wrap svc-grid', [ hp_con( 'prose', $prose ), $side ] ) ], 'section' ),
		hp_cta_band(),
	];
}

/* ---------------- Seed ---------------- */
function demo_seed() {
	if ( get_option( 'demo_seeded' ) ) return;
	kses_remove_filters();
	$opt = hp_optimized();

	// Elementor performance settings differ between the two builds.
	update_option( 'elementor_google_font', $opt ? '0' : '1' );
	update_option( 'elementor_font_display', 'swap' );
	update_option( 'elementor_css_print_method', $opt ? 'internal' : 'external' );
	update_option( 'elementor_experiment-e_font_icon_svg', $opt ? 'active' : 'inactive' );
	update_option( 'elementor_experiment-e_optimized_markup', $opt ? 'active' : 'inactive' );
	update_option( 'elementor_experiment-container', 'active' );
	// Background lazy-load needs Elementor's frontend JS; the static build runs without it, so keep it off.
	update_option( 'elementor_experiment-e_lazyload', 'inactive' );
	update_option( 'elementor_disable_color_schemes', 'yes' );
	update_option( 'elementor_disable_typography_schemes', 'yes' );
	update_option( 'elementor_load_fa4_shim', '' );
	update_option( 'elementor_onboarded', true );
	update_option( 'elementor_unfiltered_files_upload', '' );

	// Kit (Site Settings): global colours + fonts + container width, per DESIGN.md section 5.
	$kit = (int) get_option( 'elementor_active_kit' );
	if ( $kit ) {
		$c = fn( $id, $t, $hex ) => [ '_id' => $id, 'title' => $t, 'color' => $hex ];
		$f = fn( $id, $t, $fam, $w ) => [ '_id' => $id, 'title' => $t, 'typography_typography' => 'custom', 'typography_font_family' => $fam, 'typography_font_weight' => $w ];
		update_post_meta( $kit, '_elementor_page_settings', [
			'system_colors' => [ $c( 'primary', 'Primary (Ink)', '#1B2733' ), $c( 'secondary', 'Secondary (Pine)', '#2E4A3E' ), $c( 'text', 'Text (Ink)', '#1B2733' ), $c( 'accent', 'Accent (Brass)', '#8A5A24' ) ],
			'custom_colors' => [ $c( 'navy', 'Navy', '#24364A' ), $c( 'paper', 'Paper', '#F8F5EF' ), $c( 'paper2', 'Paper 2', '#EEE8DE' ), $c( 'line', 'Line', '#D9D2C5' ), $c( 'mute', 'Mute', '#5A6673' ), $c( 'white', 'Warm white', '#FFFDF9' ) ],
			'system_typography' => [ $f( 'primary', 'Headlines', 'Newsreader', '400' ), $f( 'secondary', 'Secondary', '', '400' ), $f( 'text', 'Body', '', '400' ), $f( 'accent', 'Accent', '', '600' ) ],
			'container_width' => [ 'unit' => 'px', 'size' => 1140 ],
			'space_between_widgets' => [ 'unit' => 'px', 'size' => 0, 'column' => '0', 'row' => '0' ],
			'viewport_mobile' => 767, 'viewport_tablet' => 1024,
			'body_background_background' => 'classic', 'body_background_color' => '#F8F5EF',
		] );
	}

	// Forms.
	$cases = [ 'Car or truck accident', 'Slip and fall', 'Workplace injury', 'Divorce or separation', 'Child custody and support', 'Something else' ];
	$intake = hp_ff_create( 'Free case evaluation', [
		hp_ff_two( hp_ff_input( 'full_name', 'Full name', 'text', true, 'name' ), hp_ff_input( 'phone', 'Phone', 'tel', true, 'tel' ) ),
		hp_ff_input( 'email', 'Email', 'email', true, 'email' ),
		hp_ff_select( 'matter', 'What do you need help with?', $cases ),
		hp_ff_textarea( 'details', 'Tell us briefly what happened (optional)', 3 ),
		hp_ff_consent(),
	], 'Request my free evaluation' );
	$full = hp_ff_create( 'Contact intake (full)', [
		hp_ff_two( hp_ff_input( 'full_name', 'Full name', 'text', true, 'name' ), hp_ff_input( 'phone', 'Phone', 'tel', true, 'tel' ) ),
		hp_ff_input( 'email', 'Email', 'email', true, 'email' ),
		hp_ff_select( 'matter', 'What do you need help with?', $cases ),
		hp_ff_select( 'best_time', 'Best time to reach you', [ 'Morning (8 am to 12 pm)', 'Afternoon (12 pm to 5 pm)', 'Evening (5 pm to 8 pm)', 'Any time' ] ),
		hp_ff_textarea( 'details', 'Tell us briefly what happened (optional)' ),
		hp_ff_consent(),
	], 'Request my free evaluation' );
	$mini = hp_ff_create( 'Call-back request (short)', [
		hp_ff_input( 'full_name', 'Full name', 'text', true, 'name' ),
		hp_ff_input( 'phone', 'Phone', 'tel', true, 'tel' ),
		hp_ff_consent(),
	], 'Request a call back' );

	// Media: optimized = sized WebP, naive = original full-size JPEG.
	$ext = $opt ? '-1200.webp' : '.jpg';
	$dir = $opt ? 'img/' : 'img-naive/';
	$pines = hp_media( $dir . 'pines' . $ext, 'Pine trees on a hillside with low fog rolling through', 'Photo: Dense mist over pine trees (Unsplash), CC0, Wikimedia Commons' );
	$ridge = hp_media( $dir . 'ridge' . $ext, 'A forested ridge under soft morning mist', 'Photo: Pine trees and fog (Unsplash), CC0, Wikimedia Commons' );

	$check = $opt ? 'fas fa-check' : 'fas fa-check';
	$GLOBALS['hp_eval'] = '#intake';

	/* ---------- Home ---------- */
	$home = [
		hp_con( 'hero', [ hp_con( 'wrap hero-grid', [
			hp_con( 'hero-copy', [
				hp_text( '<p class="eyebrow">Personal injury and family law in Harborview</p>' ),
				hp_heading( 'Steady counsel when life changes course.', 'h1', 'hero-h1' ),
				hp_text( '<p class="lead">When you are hurt, or your family is coming apart, you need someone who will tell you plainly where you stand. We have done that for Harborview families for 14 years.</p>' ),
				hp_con( 'actions hero-actions', [ hp_btn( 'Start your free case evaluation', '#intake' ), hp_btn( 'Call (555) 010-0100', 'tel:+15550100100', true ) ] ),
				hp_icons( [ 'No fee unless we win (injury)', 'Same-week appointments', 'Replies within one business day' ], $check, 'facts-list' ),
			] ),
			hp_con( 'card', [
				hp_heading( 'Free case evaluation', 'h2' ),
				hp_text( '<p class="sub">Takes about two minutes. Confidential.</p>' ),
				hp_form( $intake ),
				hp_text( '<p class="fine">We reply within one business day. Demo form: this sample site sends nothing.</p>' ),
			], 'div', [ '_element_id' => 'intake' ] ),
		] ) ], 'section' ),
		hp_con( 'trust', [ hp_con( 'wrap', [ hp_icons( [
			[ 'Free, confidential first conversation', 'fas fa-shield-alt' ],
			[ 'Same-week appointments, in person or by phone', 'far fa-clock' ],
			[ 'Fees explained in writing before you sign anything', 'far fa-file-alt' ],
			[ 'You talk to a lawyer, not a call center', 'fas fa-phone-alt' ],
		], '', 'trust-list' ) ] ) ], 'section' ),
		hp_con( 'section', [ hp_con( 'wrap', [
			hp_text( '<p class="eyebrow">Practice areas</p>' ),
			hp_heading( 'Two kinds of hard weeks. We handle both.', 'h2', 'sec-h' ),
			hp_con( 'areas', [
				hp_con( 'area-col', [ hp_heading( 'Personal injury', 'h3' ), hp_icons( [ 'Car and truck accidents', 'Slip and fall', 'Workplace injuries', 'Wrongful death' ], '', 'area-list' ), hp_text( '<p><a href="/personal-injury/">How we handle injury cases</a></p>' ) ] ),
				hp_con( 'area-col', [ hp_heading( 'Family law', 'h3' ), hp_icons( [ 'Divorce and separation', 'Child custody and parenting time', 'Child and spousal support', 'Modifications and enforcement' ], '', 'area-list' ), hp_text( '<p><a href="/family-law/">How we handle family cases</a></p>' ) ] ),
			] ),
		] ) ], 'section' ),
		hp_con( 'section no-top', [ hp_con( 'wrap expect', [
			hp_con( 'expect-copy', [
				hp_heading( 'What the first 30 days look like.' ),
				hp_text( '<p><strong>The first call.</strong> You tell us what happened. We tell you, honestly, whether you have a case worth pursuing and what it would involve. If we are not the right firm, we will say so and point you somewhere better.</p><p><strong>The plan in writing.</strong> Within a week you get a one-page summary: what we will do, what we need from you, and a realistic range of outcomes.</p><p><strong>No surprises on fees.</strong> Injury cases are contingency, so you pay nothing unless we recover money for you. Family matters are quoted up front, with a cap where we can offer one.</p>' ),
			] ),
			hp_con( 'expect-side', [ hp_html( '<blockquote class="pull"><p>“Most people call us on the worst week of their year. Our job is to make the next one calmer.”</p><footer>Elena Marsh, partner</footer></blockquote>' ) ] ),
		] ) ], 'section' ),
		hp_con( 'section no-top', [ hp_con( 'wrap', [
			hp_text( '<p class="eyebrow">Our attorneys</p>' ),
			hp_heading( 'The people you will actually talk to.', 'h2', 'sec-h' ),
			hp_con( 'people', [
				hp_html( '<article class="person"><div class="portrait-tile" role="img" aria-label="Monogram tile for Elena Marsh"><span aria-hidden="true">EM</span></div><div><h3 class="elementor-heading-title">Elena Marsh</h3><p class="role">Partner, personal injury. Admitted 2009</p><div class="bio"><p>Fourteen years of injury work, most of it against insurers who hoped the client would give up. Elena takes the calls herself.</p><p><a href="/our-attorneys/#elena-marsh">More about Elena</a></p></div></div></article>' ),
				hp_html( '<article class="person"><div class="portrait-tile" role="img" aria-label="Monogram tile for Daniel Okafor"><span aria-hidden="true">DO</span></div><div><h3 class="elementor-heading-title">Daniel Okafor</h3><p class="role">Partner, family law. Admitted 2011</p><div class="bio"><p>Divorce, custody and support, with a preference for settling well over fighting long. Trained mediator.</p><p><a href="/our-attorneys/#daniel-okafor">More about Daniel</a></p></div></div></article>' ),
			] ),
		] ) ], 'section' ),
		hp_con( 'section paper2', [ hp_con( 'wrap', [
			hp_text( '<p class="eyebrow">Results and reviews</p>' ),
			hp_heading( 'Outcomes, in their words.', 'h2', 'sec-h' ),
			hp_con( 'results', [
				hp_html( '<div><blockquote class="quote"><p>“Elena explained every letter the insurer sent before I had a chance to panic about it.”</p><footer>Former client, rear-end collision (sample review)</footer></blockquote><blockquote class="quote"><p>“Daniel kept the focus on our kids when neither of us could. We settled in four months.”</p><footer>Former client, custody matter (sample review)</footer></blockquote></div>' ),
				hp_html( '<div class="tiles-html"><div class="tile"><p class="amt">$410,000</p><p class="what">Settlement, rear-end collision with disputed liability (sample result)</p></div><div class="tile"><p class="amt">Full custody retained</p><p class="what">Contested relocation case (sample result)</p></div><div class="tile"><p class="amt">$125,000</p><p class="what">Settlement, retail slip and fall (sample result)</p></div></div>' ),
			] ),
			hp_text( '<p class="disclaimer">Prior results do not guarantee a similar outcome. Every case depends on its own facts. <a href="/results/">See more sample results</a></p>' ),
		] ) ], 'section' ),
		hp_cta_band(),
	];
	$GLOBALS['hp_eval'] = '/contact/#intake';

	/* ---------- Service pages ---------- */
	$pi = hp_service_page( 'personal-injury', 'Personal injury', 'Personal injury', 'Hurt in an accident? Start with a straight answer.',
		'We represent people injured in car and truck crashes, falls and workplace accidents across Harborview. You pay nothing unless we recover money for you.', $ridge,
		[
			[ 'What we handle', '<ul><li>Car, truck and motorcycle accidents</li><li>Slip and fall on unsafe property</li><li>Workplace injuries and third-party claims</li><li>Wrongful death claims for families</li></ul><p>Most cases settle. We prepare every one as if it will not, because insurers can tell the difference.</p>' ],
			[ 'How fees work', '<p id="how-fees-work">Injury cases are handled on contingency. That means no upfront cost and no hourly bills. Our fee is a percentage of what we recover, agreed in writing before we start, and if there is no recovery you owe us nothing for our time.</p>' ],
			[ 'What to bring to the first call', '<ul><li>The date and place of the accident</li><li>Any police or incident report number</li><li>Photos of the scene, vehicles or injuries</li><li>Names of doctors or clinics you have seen</li><li>Letters or emails from any insurance company</li></ul><p>Do not worry if you do not have all of it. We can request most records for you.</p>' ],
		],
		[
			[ 'How much is my case worth?', 'It depends on your injuries, costs, lost income and how clear the fault is. After the first call we give you a realistic range, in writing.' ],
			[ 'Should I talk to the other driver\'s insurer?', 'You can give basic facts, but avoid recorded statements or signing anything until you have spoken with a lawyer.' ],
			[ 'How long do I have to file?', 'Deadlines vary by claim type and can be short. Call us early so no deadline passes while you recover.' ],
			[ 'Will my case go to trial?', 'Most do not. We negotiate first and only go to court when an offer is not fair.' ],
			[ 'Who pays my medical bills now?', 'Often your own coverage pays first and is reimbursed later. We help sort out the order of payment.' ],
			[ 'What if I was partly at fault?', 'You may still recover. Shared fault usually reduces the amount rather than ending the claim.' ],
		], $mini, $full );
	$fam = hp_service_page( 'family-law', 'Family law', 'Family law', 'Family changes, handled with a steady hand.',
		'Divorce, custody and support, with a preference for settling well over fighting long. Clear quotes up front and a lawyer who returns your calls.', $pines,
		[
			[ 'What we handle', '<ul><li>Divorce and legal separation</li><li>Child custody and parenting time</li><li>Child and spousal support</li><li>Modifications and enforcement of existing orders</li><li>Mediation and negotiated agreements</li></ul>' ],
			[ 'How fees work', '<p>Family matters are quoted up front. For uncontested cases we offer a flat fee; for contested matters we explain the hourly rate, give a realistic estimate, and set a cap where we can. You get a written fee agreement before any work starts.</p>' ],
			[ 'What to bring to the first call', '<ul><li>Any current court orders or agreements</li><li>A rough list of income, assets and debts</li><li>Your children\'s current schedule</li><li>Questions you want answered first</li></ul>' ],
		],
		[
			[ 'Do we have to go to court?', 'Not always. Many families settle through negotiation or mediation, with a judge only approving the agreement.' ],
			[ 'How is custody decided?', 'Courts focus on the best interests of the child, including stability, each parent\'s involvement and the child\'s needs.' ],
			[ 'Can support be changed later?', 'Yes, when circumstances change significantly, such as a job loss or a change in parenting time.' ],
			[ 'How long does a divorce take?', 'Uncontested cases can finish in a few months. Contested cases take longer, and we give you a realistic timeline early.' ],
			[ 'Is mediation right for us?', 'It works best when both people can talk safely. We will tell you honestly whether it fits your situation.' ],
			[ 'Will my information stay private?', 'Yes. Conversations with our office are confidential from the first call.' ],
		], $mini, $full );

	/* ---------- Attorneys ---------- */
	$profile = function ( $anchor, $mono, $name, $role, $paras, $edu, $adm, $outside ) {
		return hp_con( 'profile', [
			hp_html( '<div class="portrait-tile" role="img" aria-label="Monogram tile for ' . $name . '"><span aria-hidden="true">' . $mono . '</span></div>' ),
			hp_con( 'profile-copy', [ hp_heading( $name, 'h2' ), hp_text( '<p class="role">' . $role . '</p>' . $paras . '<dl><dt>Education</dt><dd>' . $edu . '</dd><dt>Admissions</dt><dd>' . $adm . '</dd></dl><h3 class="out-h">Outside the office</h3><p>' . $outside . '</p>' ) ] ),
		], 'article', [ '_element_id' => $anchor ] );
	};
	$att = [
		hp_page_hero( 'Our attorneys', 'Two partners, one office, one phone number.', 'When you call Harbor & Pine, you reach one of the two lawyers who will actually handle your case.', false ),
		hp_con( 'section', [ hp_con( 'wrap', [
			$profile( 'elena-marsh', 'EM', 'Elena Marsh', 'Partner, personal injury. Admitted 2009 (sample profile)', '<p>Elena has spent fourteen years on injury cases, most of them against insurers who hoped the client would give up. She keeps a small caseload on purpose so she can take the calls herself.</p><p>She is known for explaining the process in plain language and for preparing every case as if it is going to trial.</p>', 'Law degree, Harborview School of Law (fictional)', 'State bar, 2009. Federal district court, 2011', 'Coaches a youth rowing crew and is slowly restoring a wooden sailboat.' ),
			$profile( 'daniel-okafor', 'DO', 'Daniel Okafor', 'Partner, family law. Admitted 2011 (sample profile)', '<p>Daniel handles divorce, custody and support with a preference for settling well over fighting long. He is a trained mediator and keeps children at the centre of every plan.</p><p>When a case does need a judge, he is calm, prepared and direct in court.</p>', 'Law degree, Coastal State University (fictional). Mediation certificate', 'State bar, 2011', 'Volunteers with a local legal aid clinic and bakes bread most weekends.' ),
		] ) ], 'section' ),
		hp_cta_band(),
	];

	/* ---------- Results ---------- */
	$tiles = [
		[ 'Personal injury', '$410,000', 'Settlement, rear-end collision with disputed liability' ],
		[ 'Personal injury', '$125,000', 'Settlement, retail slip and fall' ],
		[ 'Personal injury', '$260,000', 'Settlement, delivery truck intersection crash' ],
		[ 'Personal injury', '$95,000', 'Settlement, scaffold fall, third-party claim' ],
		[ 'Family law', 'Full custody retained', 'Contested relocation case' ],
		[ 'Family law', 'Agreement in 4 months', 'Divorce with shared business assets, mediated' ],
		[ 'Family law', 'Support modified', 'Payments adjusted after a job loss' ],
		[ 'Family law', 'Order enforced', 'Parenting schedule restored after missed visits' ],
	];
	$th = '<div class="result-grid-html">';
	foreach ( $tiles as $t ) $th .= '<div class="tile"><p class="tag">' . $t[0] . '</p><p class="amt">' . $t[1] . '</p><p class="what">' . $t[2] . ' (sample result)</p></div>';
	$res = [
		hp_page_hero( 'Results', 'Sample outcomes, plainly stated.', 'These are illustrative sample results for a demo site. Prior results do not guarantee a similar outcome.', false ),
		hp_con( 'section', [ hp_con( 'wrap', [ hp_text( '<p class="disclaimer first">Prior results do not guarantee a similar outcome. Every case depends on its own facts.</p>' ), hp_html( $th . '</div>' ), hp_text( '<p class="disclaimer">Attorney advertising. Sample results for portfolio purposes only.</p>' ) ] ) ], 'section' ),
		hp_cta_band(),
	];

	/* ---------- Contact ---------- */
	$contact = [
		hp_page_hero( 'Contact', 'Tell us what happened.', 'Fill in the form and a lawyer will call you within one business day. Prefer the phone? Call (555) 010-0100, evenings and weekends included.', false ),
		hp_con( 'section', [ hp_con( 'wrap contact-grid', [
			hp_con( 'card', [ hp_heading( 'Free case evaluation', 'h2' ), hp_text( '<p class="sub">Takes about two minutes. Confidential.</p><p class="demo-note">Demo form: this portfolio sample checks your entries in the browser and sends nothing.</p>' ), hp_form( $full ) ], 'div', [ '_element_id' => 'intake' ] ),
			hp_html( '<aside class="office"><h2>Our office</h2><p>Harborview, by appointment. We meet in person, by phone or by video.</p><h3>Hours</h3><p>Monday to Friday, 8 am to 6 pm. Evening and weekend calls by arrangement.</p><h3>Parking</h3><p>Free visitor parking behind the building, with a step-free entrance.</p><h3>Reach us</h3><p><a href="mailto:intake@harborpinelaw.example.com">intake@harborpinelaw.example.com</a><br><a href="tel:+15550100100">(555) 010-0100</a></p></aside>' ),
		] ) ], 'section' ),
	];

	/* ---------- Legal ---------- */
	$legal = [
		hp_page_hero( 'Legal', 'Notices and policies.', 'Plain-language notices for this sample site.', false ),
		hp_con( 'section', [ hp_con( 'wrap legal', [ hp_html( '<h2 id="disclaimer">Disclaimer</h2><p>This is a portfolio demo for a fictional law firm. Nothing on this site is legal advice, and contacting the firm does not create an attorney-client relationship. Attorney advertising. Prior results do not guarantee a similar outcome.</p><h2 id="privacy">Privacy</h2><p>The demo forms do not send or store anything. On a live site, this section would explain what the intake form collects, who can see it and how long it is kept.</p><h2 id="accessibility">Accessibility</h2><p>This site aims to meet WCAG 2.2 AA: readable contrast, keyboard access with visible focus, labelled form fields and a skip link. If something is hard to use, a live site would list a contact here.</p>' ) ] ) ], 'section' ),
	];

	$ids = [];
	$ids['home'] = hp_save_page( 'home', 'Home', $home, 'Harbor & Pine Law: personal injury and family law in Harborview. Free, confidential case evaluation and a straight answer about where you stand.' );
	$ids['pi'] = hp_save_page( 'personal-injury', 'Personal injury', $pi, 'Injured in a car, truck or workplace accident in Harborview? No fee unless we win. Free case evaluation with a personal injury lawyer.' );
	$ids['fam'] = hp_save_page( 'family-law', 'Family law', $fam, 'Divorce, custody and support in Harborview with clear quotes up front. Free first conversation with a family lawyer.' );
	hp_save_page( 'our-attorneys', 'Our attorneys', $att, 'Meet Elena Marsh (personal injury) and Daniel Okafor (family law), the two partners at Harbor & Pine Law.' );
	hp_save_page( 'results', 'Results', $res, 'Sample case results and outcomes from Harbor & Pine Law. Prior results do not guarantee a similar outcome.' );
	hp_save_page( 'contact', 'Contact', $contact, 'Request a free, confidential case evaluation from Harbor & Pine Law in Harborview. A lawyer calls back within one business day.' );
	hp_save_page( 'demo-form', 'Demo form', [ hp_page_hero( 'Demo form', 'This is a demo form.', 'This sample site has no server behind its forms, so nothing was sent. On a live site the firm would call you back within one business day.', false ) ], 'Demo form notice.' );
	hp_save_page( 'legal', 'Legal', $legal, 'Disclaimer, privacy and accessibility notices for the Harbor & Pine Law demo site.' );
	update_post_meta( $ids['home'], '_yoast_wpseo_title', 'Harbor & Pine Law | Personal injury and family lawyers in Harborview' );

	foreach ( [ 'hello-world', 'sample-page', 'privacy-policy' ] as $s ) {
		$p = get_page_by_path( $s, OBJECT, [ 'post', 'page' ] );
		if ( $p ) wp_delete_post( $p->ID, true );
	}
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids['home'] );
	update_option( 'wpseo_titles', array_merge( (array) get_option( 'wpseo_titles' ), [ 'company_or_person' => 'company', 'company_name' => 'Harbor & Pine Law', 'website_name' => 'Harbor & Pine Law', 'separator' => 'sc-pipe' ] ) );
	update_option( 'demo_seeded', 1 );
	\Elementor\Plugin::$instance->files_manager->clear_cache();
	flush_rewrite_rules();
}
