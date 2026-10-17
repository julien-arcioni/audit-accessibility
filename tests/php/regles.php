<?php
/**
 * Règles RGAA : chaque page de tests/fixtures déclare ses défauts avec data-attendu
 * (sur l'élément) et data-attendu-page (sur <html>). On compare, dans les deux sens,
 * avec ce que renvoie Audit_Accessibilite_RGAA::analyser_html().
 */
require_once __DIR__ . '/lib.php';

aa_test( 'méthode analyser_html disponible', method_exists( 'Audit_Accessibilite_RGAA', 'analyser_html' ) );

$rgaa      = new Audit_Accessibilite_RGAA();
$resultats = array();
$fichiers  = glob( __DIR__ . '/../fixtures/*.html' );
sort( $fichiers );

foreach ( $fichiers as $fichier ) {
	$nom  = basename( $fichier, '.html' );
	$html = file_get_contents( $fichier );

	// Attendu : chemins calculés sur le DOM chargé comme le fait analyser_html
	$dom = new DOMDocument();
	@$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING );
	$xpath   = new DOMXPath( $dom );
	$attendu = array();
	foreach ( $xpath->query( '//*[@data-attendu]' ) as $el ) {
		foreach ( preg_split( '/\s+/', trim( $el->getAttribute( 'data-attendu' ) ) ) as $id ) {
			$attendu[] = $id . '|' . $el->getNodePath();
		}
	}
	$html_el = $dom->getElementsByTagName( 'html' )->item( 0 );
	if ( $html_el && $html_el->hasAttribute( 'data-attendu-page' ) ) {
		foreach ( preg_split( '/\s+/', trim( $html_el->getAttribute( 'data-attendu-page' ) ) ) as $id ) {
			$attendu[] = $id . '|';
		}
	}

	$problemes = $rgaa->analyser_html( $html, 'http://test/' . $nom );
	$obtenu    = array();
	foreach ( $problemes as $p ) {
		$obtenu[] = $p['rule'] . '|' . ( null === $p['path'] ? '' : $p['path'] );
		aa_test( "$nom : {$p['rule']} a une recommandation", '' !== trim( $p['solution'] ) );
	}
	sort( $attendu );
	sort( $obtenu );

	foreach ( $attendu as $a ) {
		list( $regle, $chemin ) = explode( '|', $a, 2 );
		aa_test( "$nom : $regle sur " . ( '' === $chemin ? '(page)' : $chemin ), in_array( $a, $obtenu, true ), 'manquant' );
	}
	foreach ( array_diff( $obtenu, $attendu ) as $o ) {
		aa_test( "$nom : rien en trop ($o)", false, 'non attendu' );
	}
	aa_test( "$nom : autant de problèmes qu'attendu", count( $attendu ) === count( $obtenu ), count( $obtenu ) . ' / ' . count( $attendu ) );

	$resultats[ $nom ] = array_map(
		function ( $o ) {
			list( $regle, $chemin ) = explode( '|', $o, 2 );
			return array( 'regle' => $regle, 'chemin' => '' === $chemin ? null : $chemin );
		},
		$obtenu
	);
}

@mkdir( __DIR__ . '/../.resultats', 0777, true );
file_put_contents( __DIR__ . '/../.resultats/php.json', wp_json_encode( $resultats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );

aa_test_fin();
