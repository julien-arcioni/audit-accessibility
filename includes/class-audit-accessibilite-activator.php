<?php

/**
 * Classe lancée lors de l'activation du plugin
 *
 * @since      1.0.0
 */
class Audit_Accessibilite_Activator {

    /**
     * Activation de l'extension
     *
     * Méthode exécutée lors de l'activation de l'extension
     * - Création des tables en base de données
     * - Initialisation des options par défaut
     * 
     * @since    1.0.0
     */
    public static function activate() {
        // Créer la table pour stocker les résultats des audits
        global $wpdb;
        $table_name = $wpdb->prefix . 'audit_accessibilite_results';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            audit_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            audit_type varchar(50) NOT NULL,
            audit_results longtext NOT NULL,
            score int(3) DEFAULT 0 NOT NULL,
            page_url varchar(255) NOT NULL,
            element_id varchar(255) DEFAULT '' NOT NULL,
            description text DEFAULT '' NOT NULL,
            status varchar(20) DEFAULT 'open' NOT NULL,
            category varchar(100) DEFAULT '' NOT NULL,
            priority varchar(20) DEFAULT 'medium' NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Initialiser les options par défaut
        $default_options = array(
            'rgaa_criteria' => true,
            'seo_check' => true,
            'performance_check' => true,
            'security_check' => true,
            'report_email' => get_option('admin_email'),
            'automated_audit_frequency' => 'weekly',
            'last_audit_date' => '',
            'notification_enabled' => true
        );

        update_option('audit_accessibilite_options', $default_options);
    }
} 