<?php
/**
 * Importer safety tests. Loaded only by scripts/test-importer.sh (copied into mu-plugins for one request, then removed).
 * Prints PASS/FAIL lines; the shell script fails if any FAIL appears.
 */
if ( ! isset( $_GET['fw_importer_tests'] ) ) return;
add_action( 'wp_loaded', function () {
	header( 'content-type: text/plain' );
	$expect_fail = [
		'https://evil.example.com/x.csv'                         => 'disallowed host',
		'http://docs.google.com/spreadsheets/d/e/x/pub?output=csv' => 'plain http',
		'https://docs.google.com.evil.example/x.csv'             => 'look-alike host',
		'https://169.254.169.254/latest/meta-data'               => 'metadata IP',
		'file:///etc/passwd'                                     => 'file scheme',
		'/etc/passwd'                                            => 'path outside data dir',
		WP_CONTENT_DIR . '/demo-data/../../wp-config.php'        => 'traversal out of data dir',
		WP_CONTENT_DIR . '/demo-data/missing.csv'                => 'missing file',
	];
	foreach ( $expect_fail as $src => $why ) {
		$r = fw_import_csv( $src );
		// Must be rejected by the allow-list itself (not merely fail later on the network or parse step).
		$want = preg_match( '#^[a-z]+://#', $src ) ? 'are allowed' : 'must be a CSV file inside';
		$ok = 1 === $r['failed'] && 0 === $r['created'] + $r['updated'] && str_contains( $r['errors'][0] ?? '', $want );
		echo ( $ok ? 'PASS ' : 'FAIL ' ) . "rejects $why: " . ( $r['errors'][0] ?? '' ) . "\n";
	}
	$r = fw_import_csv( WP_CONTENT_DIR . '/demo-data/makers-sample.csv' );
	echo ( 0 === $r['failed'] && 0 === $r['created'] && $r['updated'] > 0 ? 'PASS ' : 'FAIL ' ) . 'sample re-import is idempotent: ' . wp_json_encode( $r ) . "\n";
	exit;
} );
