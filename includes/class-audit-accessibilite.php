<?php

/**
 * La classe principale du plugin
 *
 * Cette classe définit toutes les fonctionnalités et composants du plugin
 *
 * @since      1.0.0
 */
class Audit_Accessibilite {

    /**
     * Le chargeur qui est responsable de maintenir et d'enregistrer tous les hooks de l'extension
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_Loader    $loader    Maintient et enregistre tous les hooks pour l'extension
     */
    protected $loader;

    /**
     * Identifiant unique de l'extension
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    L'identifiant unique de l'extension
     */
    protected $plugin_name;

    /**
     * Version actuelle de l'extension
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    La version actuelle de l'extension
     */
    protected $version;

    /**
     * Instance de la classe RGAA pour les audits d'accessibilité
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_RGAA    $rgaa    Instance de la classe RGAA
     */
    protected $rgaa;

    /**
     * Instance de la classe SEO pour les audits SEO
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_SEO    $seo    Instance de la classe SEO
     */
    protected $seo;

    /**
     * Instance de la classe Performance pour les audits de performance
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_Performance    $performance    Instance de la classe Performance
     */
    protected $performance;

    /**
     * Instance de la classe Security pour les audits de sécurité
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_Security    $security    Instance de la classe Security
     */
    protected $security;

    /**
     * Instance de la classe Report pour la génération de rapports
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_Report    $report    Instance de la classe Report
     */
    protected $report;

    /**
     * Instance de la classe Assets pour la gestion des ressources CSS et JS
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_Assets    $assets    Instance de la classe Assets
     */
    protected $assets;

    /**
     * Instance de la classe Ajax pour la gestion des requêtes AJAX
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_Ajax    $ajax    Instance de la classe Ajax
     */
    protected $ajax;

    /**
     * Définit les fonctionnalités de base de l'extension
     *
     * - Définition des variables du plugin
     * - Chargement des dépendances
     * - Initialisation du chargeur
     * - Configuration des hooks admin et public
     * - Configuration des hooks internationaux
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = AUDIT_ACCESSIBILITE_VERSION;
        $this->plugin_name = 'audit-accessibilite';

        $this->load_dependencies();
        $this->set_locale();
        $this->initialize_components();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Charge les dépendances nécessaires à l'extension
     *
     * - Audit_Accessibilite_Loader coordonne les hooks de l'extension
     * - Audit_Accessibilite_i18n gère l'internationalisation
     * - Audit_Accessibilite_Admin gère les hooks de l'espace d'administration
     * - Audit_Accessibilite_Public gère les hooks de l'espace public
     * - Classes spécifiques aux fonctionnalités d'audit
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        // La classe qui orchestre le chargement des hooks
        require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'includes/class-audit-accessibilite-loader.php';
        
        // La classe qui gère l'internationalisation
        require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'includes/class-audit-accessibilite-i18n.php';
        
        // La classe qui gère l'administration
        require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'admin/class-audit-accessibilite-admin.php';
        
        // Classes d'audit spécifiques
        require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'includes/class-audit-accessibilite-rgaa.php';
        require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'includes/class-audit-accessibilite-seo.php';
        require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'includes/class-audit-accessibilite-performance.php';
        require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'includes/class-audit-accessibilite-security.php';
        require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'includes/class-audit-accessibilite-report.php';

        // Nouvelles classes
        require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'includes/class-audit-accessibilite-assets.php';
        require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'includes/class-audit-accessibilite-ajax.php';

        $this->loader = new Audit_Accessibilite_Loader();
    }

    /**
     * Initialise les composants principaux de l'extension
     *
     * @since    1.0.0
     * @access   private
     */
    private function initialize_components() {
        // Initialiser les classes d'audit
        $this->rgaa = new Audit_Accessibilite_RGAA($this->get_plugin_name(), $this->get_version());
        $this->seo = new Audit_Accessibilite_SEO($this->get_plugin_name(), $this->get_version());
        $this->performance = new Audit_Accessibilite_Performance($this->get_plugin_name(), $this->get_version());
        $this->security = new Audit_Accessibilite_Security($this->get_plugin_name(), $this->get_version());
        $this->report = new Audit_Accessibilite_Report($this->get_plugin_name(), $this->get_version());
        
        // Initialiser les nouvelles classes
        $this->assets = new Audit_Accessibilite_Assets($this->get_plugin_name(), $this->get_version(), $this->loader);
        $this->ajax = new Audit_Accessibilite_Ajax(
            $this->get_plugin_name(), 
            $this->get_version(), 
            $this->loader,
            $this->rgaa,
            $this->seo,
            $this->performance,
            $this->security,
            $this->report
        );
    }

    /**
     * Définit les paramètres de localisation de l'extension
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Audit_Accessibilite_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Enregistre tous les hooks liés à la partie admin
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Audit_Accessibilite_Admin(
            $this->get_plugin_name(), 
            $this->get_version(),
            $this->rgaa,
            $this->seo,
            $this->performance,
            $this->security,
            $this->report
        );
        
        // Menu admin
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Planification des audits automatiques
        $this->loader->add_action('audit_accessibilite_cron_hook', $plugin_admin, 'run_scheduled_audit');
        $this->loader->add_filter('cron_schedules', $plugin_admin, 'add_cron_schedules');
    }

    /**
     * Enregistre tous les hooks liés à la partie publique
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        // Aucun hook public pour le moment, l'extension fonctionne uniquement dans l'admin
    }

    /**
     * Exécute le chargeur pour exécuter tous les hooks
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Le nom de l'extension pour l'identifier de manière unique
     *
     * @since     1.0.0
     * @return    string    Le nom de l'extension.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * La référence à la classe qui orchestre les hooks
     *
     * @since     1.0.0
     * @return    Audit_Accessibilite_Loader    Le chargeur des hooks.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Récupère le numéro de version de l'extension
     *
     * @since     1.0.0
     * @return    string    Le numéro de version.
     */
    public function get_version() {
        return $this->version;
    }
} 