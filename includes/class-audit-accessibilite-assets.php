<?php
/**
 * Classe responsable de l'enregistrement et du chargement des ressources CSS et JavaScript
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Audit_Accessibilite
 * @subpackage Audit_Accessibilite/includes
 */

/**
 * Classe responsable de l'enregistrement et du chargement des ressources CSS et JavaScript
 *
 * Cette classe gère l'enregistrement et le chargement des styles et scripts
 * nécessaires au fonctionnement de l'extension.
 *
 * @package    Audit_Accessibilite
 * @subpackage Audit_Accessibilite/includes
 * @author     Votre Nom <email@example.com>
 */
class Audit_Accessibilite_Assets {

    /**
     * Le loader qui est responsable de maintenir et d'enregistrer tous les hooks de l'extension.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_Loader    $loader    Maintient et enregistre tous les hooks pour l'extension.
     */
    protected $loader;

    /**
     * Le nom unique de cette extension.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    Le nom de l'extension.
     */
    protected $plugin_name;

    /**
     * La version actuelle de l'extension.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    La version actuelle de l'extension.
     */
    protected $version;

    /**
     * Initialise la classe et définit ses propriétés.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       Le nom de l'extension.
     * @param    string    $version           La version de l'extension.
     * @param    Audit_Accessibilite_Loader    $loader    Le loader de l'extension.
     */
    public function __construct( $plugin_name, $version, $loader ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->loader = $loader;

        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Enregistre tous les hooks liés à la fonctionnalité admin de l'extension.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_scripts' );
    }

    /**
     * Enregistre tous les hooks liés à la fonctionnalité publique de l'extension.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_public_styles' );
        $this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_public_scripts' );
    }

    /**
     * Enregistre les styles pour l'interface d'administration.
     *
     * @since    1.0.0
     */
    public function enqueue_admin_styles() {
        // Enregistrer uniquement sur les pages de l'extension
        $screen = get_current_screen();
        if ( strpos( $screen->id, $this->plugin_name ) !== false ) {
            wp_enqueue_style(
                $this->plugin_name . '-admin',
                plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/audit-accessibilite-admin.css',
                array(),
                $this->version,
                'all'
            );
        }
    }

    /**
     * Enregistre les scripts pour l'interface d'administration.
     *
     * @since    1.0.0
     */
    public function enqueue_admin_scripts() {
        // Enregistrer uniquement sur les pages de l'extension
        $screen = get_current_screen();
        if ( strpos( $screen->id, $this->plugin_name ) !== false ) {
            wp_enqueue_script(
                $this->plugin_name . '-admin',
                plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/audit-accessibilite-admin.js',
                array( 'jquery' ),
                $this->version,
                false
            );

            // Passer des variables à JavaScript
            wp_localize_script(
                $this->plugin_name . '-admin',
                'audit_accessibilite_params',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'audit_accessibilite_nonce' ),
                    'plugin_url' => plugin_dir_url( dirname( __FILE__ ) ),
                    'i18n' => array(
                        'error_message' => __( 'Une erreur est survenue. Veuillez réessayer.', 'audit-accessibilite' ),
                        'success_message' => __( 'Opération réussie.', 'audit-accessibilite' ),
                        'confirm_delete' => __( 'Êtes-vous sûr de vouloir supprimer cet élément ?', 'audit-accessibilite' ),
                        'loading' => __( 'Chargement en cours...', 'audit-accessibilite' ),
                    ),
                )
            );
        }
    }

    /**
     * Enregistre les styles pour l'interface publique.
     *
     * @since    1.0.0
     */
    public function enqueue_public_styles() {
        // Pour l'instant, nous n'avons pas de styles publics
        // Mais nous pouvons les ajouter plus tard si nécessaire
    }

    /**
     * Enregistre les scripts pour l'interface publique.
     *
     * @since    1.0.0
     */
    public function enqueue_public_scripts() {
        // Pour l'instant, nous n'avons pas de scripts publics
        // Mais nous pouvons les ajouter plus tard si nécessaire
    }
} 