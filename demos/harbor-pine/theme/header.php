<?php
/**
 * Harbor & Pine header: top bar, header with nav, accessible mobile menu. Opens <main>.
 */
$phone_href = 'tel:+15550100100';
$mark = '<svg viewBox="0 0 30 30" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3l6 9h-3.5l4.5 6H8l4.5-6H9z"/><path d="M15 18v5"/><path d="M3 26c2.5-2 5-2 7.5 0s5 2 7.5 0 5-2 7.5 0"/></svg>';
$nav = [ 'Personal injury' => '/personal-injury/', 'Family law' => '/family-law/', 'Our attorneys' => '/our-attorneys/', 'Results' => '/results/', 'Contact' => '/contact/' ];
$req = trailingslashit( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ) );
$eval = is_front_page() ? '#intake' : '/contact/#intake';
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content">Skip to content</a>
<div class="topbar"><div class="wrap"><span>Free case evaluations. No fee unless we win on injury cases.</span><span><a href="<?php echo esc_attr( $phone_href ); ?>">(555) 010-0100</a> · Evening and weekend calls</span></div></div>
<header class="header">
	<div class="wrap nav">
		<a class="brand" href="/"><?php echo $mark; // phpcs:ignore ?><span>Harbor &amp; Pine</span></a>
		<nav aria-label="Primary" class="nav-links"><ul>
			<?php foreach ( $nav as $label => $href ) : ?>
				<li><a href="<?php echo esc_attr( $href ); ?>"<?php echo $req === $href ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a></li>
			<?php endforeach; ?>
		</ul></nav>
		<div class="nav-cta">
			<a class="btn btn-secondary btn-phone" href="<?php echo esc_attr( $phone_href ); ?>">(555) 010-0100</a>
			<a class="btn btn-primary btn-eval" href="<?php echo esc_attr( $eval ); ?>">Free case evaluation</a>
			<a class="iconbtn" href="<?php echo esc_attr( $phone_href ); ?>" aria-label="Call (555) 010-0100"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg></a>
			<button class="iconbtn menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu"><span class="screen-reader-text">Menu</span><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path class="i-open" d="M4 7h16M4 12h16M4 17h16"/><path class="i-close" d="M6 6l12 12M18 6L6 18"/></svg></button>
		</div>
	</div>
	<nav id="mobile-menu" class="mobile-menu" aria-label="Mobile" hidden>
		<ul>
			<?php foreach ( $nav as $label => $href ) : ?>
				<li><a href="<?php echo esc_attr( $href ); ?>"<?php echo $req === $href ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<div class="mobile-menu-cta"><a class="btn btn-secondary" href="<?php echo esc_attr( $phone_href ); ?>">Call (555) 010-0100</a><a class="btn btn-primary" href="<?php echo esc_attr( $eval ); ?>">Free case evaluation</a></div>
	</nav>
</header>
<main id="content">
