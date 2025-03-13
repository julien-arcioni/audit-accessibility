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
        // Supprimer les tâches planifiées
        $timestamp = wp_next_scheduled('audit_accessibilite_cron_hook');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'audit_accessibilite_cron_hook');
        }
        
        // Nous conservons les données en base pour une éventuelle réactivation
        // Pour les supprimer, il faudra désinstaller complètement l'extension
    }
} 