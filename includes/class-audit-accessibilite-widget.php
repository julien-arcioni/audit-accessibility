<?php
/**
 * Chargeur du widget pédagogique d'accessibilité (côté site public).
 *
 * @package    Audit_Accessibilite
 * @subpackage Audit_Accessibilite/includes
 */

class Audit_Accessibilite_Widget {

	const MODULE = 'audit-accessibilite-widget';

	protected $plugin_name;
	protected $version;
	protected $loader;

	public function __construct( $plugin_name, $version, $loader ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->loader      = $loader;

		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue' );
		$this->loader->add_filter( 'script_module_data_' . self::MODULE, $this, 'filtrer_donnees' );
	}

	/**
	 * Vrai si le widget peut s'afficher pour cet utilisateur sur cet objet.
	 */
	public static function peut_afficher( int $user_id, ?WP_Post $objet ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}
		$options = get_option( 'audit_accessibilite_options', array() );
		if ( is_array( $options ) && isset( $options['widget_frontend'] ) && ! (int) $options['widget_frontend'] ) {
			return false;
		}
		if ( $objet instanceof WP_Post ) {
			return user_can( $user_id, 'edit_post', $objet->ID );
		}
		return user_can( $user_id, 'edit_theme_options' );
	}

	/**
	 * Données transmises au module JavaScript.
	 */
	public static function donnees( ?WP_Post $objet ): array {
		$lien = null;
		if ( $objet instanceof WP_Post ) {
			$lien = get_edit_post_link( $objet->ID, 'raw' );
		}
		return array(
			'regles'          => Audit_Accessibilite_Catalogue::regles(),
			'limites'         => Audit_Accessibilite_Catalogue::limites(),
			'avertissement'   => Audit_Accessibilite_Catalogue::avertissement(),
			'objet'           => $objet instanceof WP_Post ? array(
				'id'           => $objet->ID,
				'type'         => $objet->post_type,
				'lienModifier' => $lien ? $lien : null,
			) : null,
			'lienEditeurSite' => self::lien_editeur_site(),
			'lienMediatheque' => current_user_can( 'upload_files' ) ? admin_url( 'upload.php' ) : null,
			'css'             => AUDIT_ACCESSIBILITE_PLUGIN_URL . 'assets/css/widget.css?ver=' . AUDIT_ACCESSIBILITE_VERSION,
		);
	}

	/**
	 * Lien vers l'outil qui modifie les gabarits du thème, selon le thème et les droits de l'utilisateur :
	 * l'éditeur du site (thème à blocs), l'outil de personnalisation (thème classique), sinon null.
	 *
	 * @return array{url: string, texte: string}|null
	 */
	public static function lien_editeur_site(): ?array {
		if ( wp_is_block_theme() ) {
			return current_user_can( 'edit_theme_options' )
				? array( 'url' => admin_url( 'site-editor.php' ), 'texte' => "Ouvrir l'éditeur du site" )
				: null;
		}
		return current_user_can( 'customize' )
			? array( 'url' => admin_url( 'customize.php' ), 'texte' => "Ouvrir l'outil de personnalisation" )
			: null;
	}

	/**
	 * Objet de la page courante (null hors contenu).
	 */
	protected static function objet_courant(): ?WP_Post {
		$objet = get_queried_object();
		return $objet instanceof WP_Post ? $objet : null;
	}

	protected static function contexte_exclu(): bool {
		return is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || wp_doing_ajax();
	}

	public function enqueue() {
		if ( self::contexte_exclu() || ! self::peut_afficher( get_current_user_id(), self::objet_courant() ) ) {
			return;
		}
		wp_register_script_module(
			self::MODULE,
			AUDIT_ACCESSIBILITE_PLUGIN_URL . 'assets/js/widget/index.js',
			array(),
			$this->version
		);
		wp_enqueue_script_module( self::MODULE );
	}

	public function filtrer_donnees( $data ) {
		return self::donnees( self::objet_courant() );
	}
}
