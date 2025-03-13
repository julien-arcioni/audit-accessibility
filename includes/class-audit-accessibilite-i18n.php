<?php

/**
 * Définit la fonctionnalité d'internationalisation
 *
 * @since      1.0.0
 */
class Audit_Accessibilite_i18n {

    /**
     * Charge le domaine de texte de l'extension pour la traduction
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'audit-accessibilite',
            false,
            dirname(AUDIT_ACCESSIBILITE_PLUGIN_BASENAME) . '/languages/'
        );
    }
} 