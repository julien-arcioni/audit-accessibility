<?php
/**
 * Mini-bibliothèque de tests PHP (exécutée par `wp eval-file`).
 */
$GLOBALS['aa_test_total']  = 0;
$GLOBALS['aa_test_echecs'] = 0;

function aa_test( string $nom, bool $ok, string $detail = '' ): void {
	$GLOBALS['aa_test_total']++;
	if ( $ok ) {
		echo "OK     $nom\n";
		return;
	}
	$GLOBALS['aa_test_echecs']++;
	echo "ÉCHEC  $nom" . ( '' !== $detail ? " ($detail)" : '' ) . "\n";
}

function aa_test_fin(): void {
	$t = $GLOBALS['aa_test_total'];
	$e = $GLOBALS['aa_test_echecs'];
	echo "\n$t test(s), $e échec(s)\n";
	if ( $e > 0 ) {
		exit( 1 );
	}
}
