<?php

/**
 * Classe lancée lors de la désactivation du plugin
 *
 * @since      1.0.0
 */
class Audit_Accessibilite_Deactivator {

    /**
     * Désactivation de l'extension
     *
     * Méthode exécutée lors de la désactivation de l'extension
     * - Nettoyage des tâches planifiées
     * - Conservation des données pour une réactivation ultérieure
     * 
     * @since    1.0.0
     */
    public static function deactivate() {
        $timestamp = wp_next_scheduled('audit_accessibilite_cron_hook');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'audit_accessibilite_cron_hook');
        }
    }
} 