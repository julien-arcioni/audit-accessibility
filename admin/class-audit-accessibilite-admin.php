<?php

/**
 * Fonctionnalités d'administration de l'extension
 *
 * @since      1.0.0
 */
class Audit_Accessibilite_Admin {

    /**
     * L'identifiant unique de l'extension
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    L'identifiant unique de l'extension
     */
    private $plugin_name;

    /**
     * La version actuelle de l'extension
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    La version actuelle de l'extension
     */
    private $version;

    /**
     * Instance de la classe RGAA pour les audits d'accessibilité
     *
     * @since    1.0.0
     * @access   private
     * @var      Audit_Accessibilite_RGAA    $rgaa    Instance de la classe RGAA
     */
    private $rgaa;

    /**
     * Instance de la classe SEO pour les audits SEO
     *
     * @since    1.0.0
     * @access   private
     * @var      Audit_Accessibilite_SEO    $seo    Instance de la classe SEO
     */
    private $seo;

    /**
     * Instance de la classe Performance pour les audits de performance
     *
     * @since    1.0.0
     * @access   private
     * @var      Audit_Accessibilite_Performance    $performance    Instance de la classe Performance
     */
    private $performance;

    /**
     * Instance de la classe Security pour les audits de sécurité
     *
     * @since    1.0.0
     * @access   private
     * @var      Audit_Accessibilite_Security    $security    Instance de la classe Security
     */
    private $security;

    /**
     * Instance de la classe Report pour la génération de rapports
     *
     * @since    1.0.0
     * @access   private
     * @var      Audit_Accessibilite_Report    $report    Instance de la classe Report
     */
    private $report;

    /**
     * Initialise la classe et définit ses propriétés
     *
     * @since    1.0.0
     * @param    string    $plugin_name       L'identifiant unique de l'extension
     * @param    string    $version           La version de l'extension
     * @param    Audit_Accessibilite_RGAA    $rgaa    Instance de la classe RGAA
     * @param    Audit_Accessibilite_SEO    $seo    Instance de la classe SEO
     * @param    Audit_Accessibilite_Performance    $performance    Instance de la classe Performance
     * @param    Audit_Accessibilite_Security    $security    Instance de la classe Security
     * @param    Audit_Accessibilite_Report    $report    Instance de la classe Report
     */
    public function __construct($plugin_name, $version, $rgaa, $seo, $performance, $security, $report) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->rgaa = $rgaa;
        $this->seo = $seo;
        $this->performance = $performance;
        $this->security = $security;
        $this->report = $report;
    }

    /**
     * Enregistre les styles pour l'administration
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            AUDIT_ACCESSIBILITE_PLUGIN_URL . 'assets/css/audit-accessibilite-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Enregistre les scripts pour l'administration
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            AUDIT_ACCESSIBILITE_PLUGIN_URL . 'assets/js/audit-accessibilite-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        wp_localize_script(
            $this->plugin_name,
            'audit_accessibilite_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('audit_accessibilite_nonce')
            )
        );
    }

    /**
     * Ajoute le menu d'administration du plugin
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Menu principal
        add_menu_page(
            __('Audit Accessibilité', 'audit-accessibilite'),
            __('Audit Accessibilité', 'audit-accessibilite'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_dashboard'),
            'dashicons-universal-access',
            30
        );

        // Sous-menus
        add_submenu_page(
            $this->plugin_name,
            __('Tableau de bord', 'audit-accessibilite'),
            __('Tableau de bord', 'audit-accessibilite'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_dashboard')
        );

        add_submenu_page(
            $this->plugin_name,
            __('Nouvel audit', 'audit-accessibilite'),
            __('Nouvel audit', 'audit-accessibilite'),
            'manage_options',
            $this->plugin_name . '-nouveau',
            array($this, 'display_plugin_admin_new_audit')
        );

        add_submenu_page(
            $this->plugin_name,
            __('Rapports', 'audit-accessibilite'),
            __('Rapports', 'audit-accessibilite'),
            'manage_options',
            $this->plugin_name . '-rapports',
            array($this, 'display_plugin_admin_reports')
        );

        // Ajouter la page de rapport individuel (masquée du menu)
        add_submenu_page(
            null, // Ne pas afficher dans le menu
            __('Rapport d\'audit', 'audit-accessibilite'),
            __('Rapport d\'audit', 'audit-accessibilite'),
            'manage_options',
            $this->plugin_name . '-rapport',
            array($this, 'display_plugin_admin_single_report')
        );

        add_submenu_page(
            $this->plugin_name,
            __('Paramètres', 'audit-accessibilite'),
            __('Paramètres', 'audit-accessibilite'),
            'manage_options',
            $this->plugin_name . '-parametres',
            array($this, 'display_plugin_admin_settings')
        );
    }

    /**
     * Affiche le tableau de bord principal
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_dashboard() {
        include_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'admin/partials/audit-accessibilite-admin-dashboard.php';
    }

    /**
     * Affiche la page de nouvel audit
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_new_audit() {
        include_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'admin/partials/audit-accessibilite-admin-new-audit.php';
    }

    /**
     * Affiche la page des rapports
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_reports() {
        include_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'admin/partials/audit-accessibilite-admin-reports.php';
    }

    /**
     * Affiche la page des paramètres
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_settings() {
        include_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'admin/partials/audit-accessibilite-admin-settings.php';
    }

    /**
     * Affiche un rapport d'audit individuel
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_single_report() {
        include_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'admin/partials/audit-accessibilite-admin-single-report.php';
    }

    /**
     * Exécute un audit planifié
     *
     * @since    1.0.0
     */
    public function run_scheduled_audit() {
        $options = get_option('audit_accessibilite_options');
        
        if (!isset($options['enable_scheduled_audits']) || !$options['enable_scheduled_audits']) {
            return;
        }
        
        $target_url = home_url();
        $audit_type = 'scheduled';
        
        // Exécution des audits selon les options
        $results = array();
        
        // Audit RGAA
        if (isset($options['rgaa_criteria']) && $options['rgaa_criteria']) {
            $results['rgaa'] = $this->rgaa->run_full_audit($target_url);
        }
        
        // Audit SEO
        if (isset($options['seo_check']) && $options['seo_check']) {
            $results['seo'] = $this->seo->run_audit($target_url);
        }
        
        // Audit Performance
        if (isset($options['performance_check']) && $options['performance_check']) {
            $results['performance'] = $this->performance->run_audit($target_url);
        }
        
        // Audit Sécurité
        if (isset($options['security_check']) && $options['security_check']) {
            $results['security'] = $this->security->run_audit($target_url);
        }
        
        // Sauvegarde des résultats
        $this->save_audit_results($results, $target_url, $audit_type);
        
        // Mise à jour de la date du dernier audit
        $options['last_audit_date'] = current_time('mysql');
        update_option('audit_accessibilite_options', $options);
        
        // Génération du rapport
        $report_url = $this->report->generate_report($results, $target_url);
        
        // Notification par email si configuré
        if (isset($options['email_notifications']) && $options['email_notifications']) {
            $this->send_audit_notification($results, $report_url);
        }
    }
    
    /**
     * Ajoute des intervalles personnalisés pour la planification des audits
     *
     * @since    1.0.0
     * @param    array    $schedules    Les intervalles existants
     * @return   array    Les intervalles mis à jour
     */
    public function add_cron_schedules($schedules) {
        $schedules['weekly'] = array(
            'interval' => 604800, // 7 jours en secondes
            'display'  => __('Une fois par semaine', 'audit-accessibilite')
        );
        
        $schedules['monthly'] = array(
            'interval' => 2592000, // 30 jours en secondes
            'display'  => __('Une fois par mois', 'audit-accessibilite')
        );
        
        return $schedules;
    }
    
    /**
     * Sauvegarde les résultats d'audit en base de données
     *
     * @since    1.0.0
     * @param    array     $results      Les résultats de l'audit
     * @param    string    $target_url   L'URL auditée
     * @param    string    $audit_type   Le type d'audit effectué
     */
    private function save_audit_results($results, $target_url, $audit_type) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'audit_accessibilite_results';
        
        // Enregistrement de l'audit
        $audit_id = $wpdb->insert(
            $wpdb->prefix . 'audit_accessibilite_audits',
            array(
                'audit_date' => current_time('mysql'),
                'target_url' => $target_url,
                'audit_type' => $audit_type,
                'summary' => json_encode($this->generate_audit_summary($results))
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        // Enregistrement de chaque problème trouvé
        foreach ($results as $category => $category_results) {
            if (isset($category_results['issues'])) {
                foreach ($category_results['issues'] as $issue) {
                    $wpdb->insert(
                        $table_name,
                        array(
                            'audit_id' => $audit_id,
                            'issue_type' => $category,
                            'issue_description' => $issue['description'],
                            'issue_location' => $issue['location'],
                            'issue_priority' => $issue['priority'],
                            'issue_status' => 'open',
                            'created_at' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
                    );
                }
            }
        }
        
        return $audit_id;
    }
    
    /**
     * Génère un résumé des résultats d'audit
     *
     * @since    1.0.0
     * @param    array    $results    Les résultats de l'audit
     * @return   array    Le résumé des résultats
     */
    private function generate_audit_summary($results) {
        $summary = array(
            'total_issues' => 0,
            'high_priority' => 0,
            'medium_priority' => 0,
            'low_priority' => 0,
            'categories' => array()
        );
        
        foreach ($results as $category => $category_results) {
            if (isset($category_results['issues'])) {
                $category_count = count($category_results['issues']);
                $summary['total_issues'] += $category_count;
                $summary['categories'][$category] = $category_count;
                
                // Comptage par priorité
                foreach ($category_results['issues'] as $issue) {
                    switch ($issue['priority']) {
                        case 'high':
                            $summary['high_priority']++;
                            break;
                        case 'medium':
                            $summary['medium_priority']++;
                            break;
                        case 'low':
                            $summary['low_priority']++;
                            break;
                    }
                }
            }
        }
        
        return $summary;
    }
    
    /**
     * Envoie une notification par email après un audit planifié
     *
     * @since    1.0.0
     * @param    array     $results      Les résultats de l'audit
     * @param    string    $report_url   L'URL du rapport généré
     */
    private function send_audit_notification($results, $report_url) {
        $options = get_option('audit_accessibilite_options');
        $email = isset($options['notification_email']) ? $options['notification_email'] : get_option('admin_email');
        
        $summary = $this->generate_audit_summary($results);
        
        $subject = sprintf(
            __('[%s] Rapport d\'audit d\'accessibilité - %s', 'audit-accessibilite'),
            get_bloginfo('name'),
            date_i18n(get_option('date_format'))
        );
        
        $message = sprintf(
            __('Un audit d\'accessibilité planifié a été effectué sur %s.', 'audit-accessibilite'),
            home_url()
        );
        
        $message .= "\n\n";
        $message .= sprintf(
            __('Résumé des problèmes détectés :%s- Total : %d%s- Priorité haute : %d%s- Priorité moyenne : %d%s- Priorité basse : %d', 'audit-accessibilite'),
            "\n",
            $summary['total_issues'],
            "\n",
            $summary['high_priority'],
            "\n",
            $summary['medium_priority'],
            "\n",
            $summary['low_priority']
        );
        
        $message .= "\n\n";
        $message .= sprintf(
            __('Vous pouvez consulter le rapport complet à l\'adresse suivante : %s', 'audit-accessibilite'),
            $report_url
        );
        
        $message .= "\n\n";
        $message .= __('Ce message a été envoyé automatiquement par l\'extension Audit Accessibilité.', 'audit-accessibilite');
        
        wp_mail($email, $subject, $message);
    }
} 