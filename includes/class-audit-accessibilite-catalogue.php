<?php
/**
 * Catalogue pédagogique des règles d'accessibilité.
 *
 * Lit assets/data/regles.json (une seule fois par requête) et expose les fiches,
 * la liste des limites de l'analyse automatique et l'avertissement.
 *
 * @package    Audit_Accessibilite
 * @subpackage Audit_Accessibilite/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Audit_Accessibilite_Catalogue {

	/**
	 * Contenu décodé du fichier (mis en cache).
	 *
	 * @var array|null
	 */
	private static $donnees = null;

	/**
	 * Chemin du fichier du catalogue.
	 */
	public static function chemin_fichier(): string {
		return AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'assets/data/regles.json';
	}

	/**
	 * Fiches des règles : identifiant => fiche.
	 */
	public static function regles(): array {
		$d = self::charger();
		return is_array( $d['regles'] ?? null ) ? $d['regles'] : array();
	}

	/**
	 * Fiche d'une règle, ou null si l'identifiant est inconnu.
	 */
	public static function regle( string $id ): ?array {
		$regles = self::regles();
		return isset( $regles[ $id ] ) && is_array( $regles[ $id ] ) ? $regles[ $id ] : null;
	}

	/**
	 * Ce que l'analyse automatique ne vérifie pas : liste de ['titre', 'geste'].
	 */
	public static function limites(): array {
		$d = self::charger();
		return is_array( $d['limites'] ?? null ) ? array_values( $d['limites'] ) : array();
	}

	/**
	 * Avertissement affiché sous le bouton d'analyse.
	 */
	public static function avertissement(): string {
		$d = self::charger();
		return is_string( $d['avertissement'] ?? null ) ? $d['avertissement'] : '';
	}

	/**
	 * Lit et décode le fichier une seule fois.
	 */
	private static function charger(): array {
		if ( null === self::$donnees ) {
			$brut          = is_readable( self::chemin_fichier() ) ? file_get_contents( self::chemin_fichier() ) : false;
			$decode        = false !== $brut ? json_decode( $brut, true ) : null;
			self::$donnees = is_array( $decode ) ? $decode : array();
		}
		return self::$donnees;
	}
}
