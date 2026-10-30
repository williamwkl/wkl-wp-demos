<?php
/** Harbor & Pine footer. Closes <main>. */
$mark = '<svg viewBox="0 0 30 30" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3l6 9h-3.5l4.5 6H8l4.5-6H9z"/><path d="M15 18v5"/><path d="M3 26c2.5-2 5-2 7.5 0s5 2 7.5 0 5-2 7.5 0"/></svg>';
$eval = is_front_page() ? '#intake' : '/contact/#intake';
?>
</main>
<footer class="footer">
	<div class="wrap">
		<div class="foot-grid">
			<div>
				<a class="brand" href="/"><?php echo $mark; // phpcs:ignore ?><span>Harbor &amp; Pine</span></a>
				<p class="foot-addr">Harborview, by appointment<br>Monday to Friday, 8 am to 6 pm<br><a href="mailto:intake@harborpinelaw.example.com">intake@harborpinelaw.example.com</a><br><a href="tel:+15550100100">(555) 010-0100</a></p>
			</div>
			<nav aria-label="Practice"><p class="foot-h">Practice</p><ul><li><a href="/personal-injury/">Personal injury</a></li><li><a href="/family-law/">Family law</a></li><li><a href="/results/">Results</a></li></ul></nav>
			<nav aria-label="Firm"><p class="foot-h">Firm</p><ul><li><a href="/our-attorneys/">Our attorneys</a></li><li><a href="/personal-injury/#how-fees-work">How fees work</a></li><li><a href="/contact/">Contact</a></li></ul></nav>
			<nav aria-label="Legal"><p class="foot-h">Legal</p><ul><li><a href="/legal/#privacy">Privacy</a></li><li><a href="/legal/#disclaimer">Disclaimer</a></li><li><a href="/legal/#accessibility">Accessibility</a></li></ul></nav>
		</div>
		<div class="foot-bottom"><span>Attorney advertising. Prior results do not guarantee a similar outcome.</span><span>Sample site built for portfolio purposes. Fictional firm.</span></div>
	</div>
</footer>
<div class="sticky-bar" aria-label="Quick actions" role="region">
	<a class="btn btn-secondary" href="tel:+15550100100">Call</a>
	<a class="btn btn-primary" href="<?php echo esc_attr( $eval ); ?>">Free evaluation</a>
</div>
<?php wp_footer(); ?>
</body>
</html>
