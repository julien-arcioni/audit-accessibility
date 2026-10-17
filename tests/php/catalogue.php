<?php
require_once __DIR__ . '/lib.php';

aa_test( 'classe Audit_Accessibilite_Catalogue chargée', class_exists( 'Audit_Accessibilite_Catalogue' ) );

$regles = Audit_Accessibilite_Catalogue::regles();
aa_test( '15 règles', count( $regles ) === 15 );

$attendu = array(
	'image-alt-absent' => array( '1.1', 'high', 'directe-ou-guidee' ),
	'image-alt-fichier' => array( '1.3', 'medium', 'directe-ou-guidee' ),
	'lien-sans-intitule' => array( '6.2', 'high', 'guidee' ),
	'lien-generique' => array( '6.1', 'medium', 'directe-ou-guidee' ),
	'titre-document-absent' => array( '8.5', 'high', 'guidee' ),
	'titre-document-court' => array( '8.5', 'high', 'guidee' ),
	'html-absent' => array( '8.3', 'high', 'guidee' ),
	'langue-absente' => array( '8.3', 'high', 'guidee' ),
	'langue-vide' => array( '8.3', 'high', 'guidee' ),
	'langue-invalide' => array( '8.4', 'medium', 'guidee' ),
	'titres-absents' => array( '9.1', 'medium', 'guidee' ),
	'h1-absent' => array( '9.1', 'medium', 'guidee' ),
	'titres-saut' => array( '9.1', 'medium', 'guidee' ),
	'champ-sans-etiquette' => array( '11.1', 'high', 'guidee' ),
	'tableau-sans-en-tetes' => array( '5.6', 'medium', 'guidee' ),
);

foreach ( $attendu as $id => $v ) {
	$f = Audit_Accessibilite_Catalogue::regle( $id );
	aa_test( "$id : présente", is_array( $f ) );
	if ( ! is_array( $f ) ) {
		continue;
	}
	foreach ( array( 'critere', 'priorite', 'titre', 'explication', 'qui_gene', 'corriger', 'reflexe', 'correction' ) as $champ ) {
		aa_test( "$id : champ $champ non vide", ! empty( $f[ $champ ] ) );
	}
	aa_test( "$id : au moins 2 étapes", is_array( $f['corriger'] ?? null ) && count( $f['corriger'] ) >= 2 );
	aa_test( "$id : priorité valide", in_array( $f['priorite'] ?? '', array( 'high', 'medium', 'low' ), true ) );
	aa_test( "$id : critère {$v[0]}", ( $f['critere'] ?? '' ) === $v[0] );
	aa_test( "$id : priorité {$v[1]}", ( $f['priorite'] ?? '' ) === $v[1] );
	aa_test( "$id : correction {$v[2]}", ( $f['correction'] ?? '' ) === $v[2] );
}

aa_test( 'règle inconnue : null', Audit_Accessibilite_Catalogue::regle( 'nimporte-quoi' ) === null );

$limites = Audit_Accessibilite_Catalogue::limites();
aa_test( '8 limites', count( $limites ) === 8 );
foreach ( $limites as $i => $l ) {
	aa_test( "limite $i : titre et geste", ! empty( $l['titre'] ) && ! empty( $l['geste'] ) );
}

$brut = file_get_contents( Audit_Accessibilite_Catalogue::chemin_fichier() );
aa_test( 'fichier lisible', is_string( $brut ) && '' !== $brut );
aa_test( 'jamais « conforme »', false === stripos( (string) $brut, 'conforme' ) );
$data = json_decode( (string) $brut, true );
aa_test( 'avertissement exact', ( $data['avertissement'] ?? '' ) === "L'analyse automatique repère certaines erreurs fréquentes. Elle ne remplace pas un audit complet." );

aa_test_fin();
