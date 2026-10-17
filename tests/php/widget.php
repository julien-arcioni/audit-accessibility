<?php
require_once __DIR__ . '/lib.php';

$ancienne = get_option( 'audit_accessibilite_options', null );
$users    = array();
$post_id  = 0;
try {
	$suffixe = wp_generate_password( 6, false );
	foreach ( array( 'subscriber', 'editor', 'author' ) as $role ) {
		$users[ $role ] = wp_insert_user( array(
			'user_login' => "aa_test_{$role}_{$suffixe}",
			'user_pass'  => wp_generate_password(),
			'user_email' => "aa_test_{$role}_{$suffixe}@example.test",
			'role'       => $role,
		) );
	}
	$post_id = wp_insert_post( array(
		'post_title'  => 'Article de test widget',
		'post_status' => 'publish',
		'post_author' => $users['author'],
	) );
	$article = get_post( $post_id );
	$contact = get_page_by_path( 'contact' );
	if ( ! $contact ) {
		$ids     = get_posts( array( 'post_type' => 'page', 'numberposts' => 1, 'author__not_in' => array( $users['author'] ) ) );
		$contact = $ids ? $ids[0] : null;
	}

	aa_test( 'classe Audit_Accessibilite_Widget chargée', class_exists( 'Audit_Accessibilite_Widget' ) );
	aa_test( 'page Contact trouvée', $contact instanceof WP_Post );

	$opts = is_array( $ancienne ) ? $ancienne : array();
	unset( $opts['widget_frontend'] );
	update_option( 'audit_accessibilite_options', $opts );

	$admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0]->ID;
	aa_test( 'visiteur : refusé', ! Audit_Accessibilite_Widget::peut_afficher( 0, $contact ) );
	aa_test( 'abonné : refusé', ! Audit_Accessibilite_Widget::peut_afficher( $users['subscriber'], $contact ) );
	aa_test( 'éditeur : autorisé sur Contact', Audit_Accessibilite_Widget::peut_afficher( $users['editor'], $contact ) );
	aa_test( 'auteur : refusé sur Contact', ! Audit_Accessibilite_Widget::peut_afficher( $users['author'], $contact ) );
	aa_test( 'auteur : autorisé sur son article', Audit_Accessibilite_Widget::peut_afficher( $users['author'], $article ) );
	aa_test( 'admin sans objet : autorisé (réglage absent)', Audit_Accessibilite_Widget::peut_afficher( $admin, null ) );
	aa_test( 'éditeur sans objet : refusé (edit_theme_options)', ! Audit_Accessibilite_Widget::peut_afficher( $users['editor'], null ) );

	$opts['widget_frontend'] = 0;
	update_option( 'audit_accessibilite_options', $opts );
	aa_test( 'réglage à 0 : admin refusé', ! Audit_Accessibilite_Widget::peut_afficher( $admin, $contact ) );

	$opts['widget_frontend'] = 1;
	update_option( 'audit_accessibilite_options', $opts );
	aa_test( 'réglage à 1 : admin autorisé', Audit_Accessibilite_Widget::peut_afficher( $admin, $contact ) );

	wp_set_current_user( $admin );
	$d = Audit_Accessibilite_Widget::donnees( $article );
	aa_test( 'donnees : 15 règles', count( $d['regles'] ) === 15 );
	aa_test( 'donnees : pas de route REST tant qu\'elle n\'existe pas', ! array_key_exists( 'rest', $d ) );
	aa_test( 'donnees : objet', $d['objet']['id'] === $post_id && 'post' === $d['objet']['type'] && ! empty( $d['objet']['lienModifier'] ) );
	aa_test( 'donnees : sans objet', null === Audit_Accessibilite_Widget::donnees( null )['objet'] );
	aa_test( 'donnees : css versionné', false !== strpos( $d['css'], 'widget.css?ver=' . AUDIT_ACCESSIBILITE_VERSION ) );
	$bloc = wp_is_block_theme();
	aa_test( 'donnees : lien éditeur (admin)', is_array( $d['lienEditeurSite'] )
		&& false !== strpos( $d['lienEditeurSite']['url'], $bloc ? 'site-editor.php' : 'customize.php' )
		&& ( $bloc ? "Ouvrir l'éditeur du site" : "Ouvrir l'outil de personnalisation" ) === $d['lienEditeurSite']['texte'] );
	aa_test( 'donnees : lien médiathèque (admin)', is_string( $d['lienMediatheque'] ) && false !== strpos( $d['lienMediatheque'], 'upload.php' ) );

	wp_set_current_user( $users['author'] );
	$da = Audit_Accessibilite_Widget::donnees( $article );
	aa_test( 'donnees : auteur sans edit_theme_options, pas de lien éditeur', null === $da['lienEditeurSite'] );
	aa_test( 'donnees : auteur avec upload_files, lien médiathèque', is_string( $da['lienMediatheque'] ) );

	$contributeur = wp_insert_user( array(
		'user_login' => "aa_test_contributor_{$suffixe}",
		'user_pass'  => wp_generate_password(),
		'user_email' => "aa_test_contributor_{$suffixe}@example.test",
		'role'       => 'contributor',
	) );
	$users['contributor'] = $contributeur;
	wp_set_current_user( $contributeur );
	aa_test( 'donnees : contributeur sans upload_files, pas de lien médiathèque', null === Audit_Accessibilite_Widget::donnees( null )['lienMediatheque'] );
	wp_set_current_user( $admin );
	aa_test( 'donnees : limites et avertissement', isset( $d['limites'], $d['avertissement'] ) );
} finally {
	if ( $post_id ) {
		wp_delete_post( $post_id, true );
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $users as $id ) {
		wp_delete_user( $id );
	}
	if ( null === $ancienne ) {
		delete_option( 'audit_accessibilite_options' );
	} else {
		update_option( 'audit_accessibilite_options', $ancienne );
	}
}
aa_test_fin();
