<?php
/**
 * Plugin Name: Audit Accessibilité
 * Plugin URI: https://arcioni.fr/audit-accessibilite
 * Description: Extension permettant de réaliser un audit d'accessibilité poussé des sites WordPress selon les normes RGAA 4.1
 * Version: 1.1.0
 * Requires at least: 6.7
 * Author: Julien Arcioni
 * Author URI: https://arcioni.fr
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: audit-accessibilite
 * Domain Path: /languages
 */

// Si ce fichier est appelé directement, on sort
if (!defined('WPINC')) {
    die;
}

// Définition des constantes
define('AUDIT_ACCESSIBILITE_VERSION', '1.1.0');
define('AUDIT_ACCESSIBILITE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AUDIT_ACCESSIBILITE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AUDIT_ACCESSIBILITE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Le code qui s'exécute pendant l'activation du plugin
 */
function activate_audit_accessibilite() {
    require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'includes/class-audit-accessibilite-activator.php';
    Audit_Accessibilite_Activator::activate();
}

/**
 * Le code qui s'exécute pendant la désactivation du plugin
 */
function deactivate_audit_accessibilite() {
    require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'includes/class-audit-accessibilite-deactivator.php';
    Audit_Accessibilite_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_audit_accessibilite');
register_deactivation_hook(__FILE__, 'deactivate_audit_accessibilite');

/**
 * Charge une page pour l'audit.
 * Quand la page appartient au site lui-même, la vérification du certificat suit
 * le filtre https_local_ssl_verify, comme les requêtes internes de WordPress :
 * un site local en HTTPS (certificat auto-signé) reste ainsi auditable.
 *
 * @since    1.1.0
 * @param    string    $url     L'URL à charger
 * @param    array     $args    Arguments de wp_remote_get()
 * @return   array|WP_Error     La réponse
 */
function audit_accessibilite_remote_get($url, $args = array()) {
    $defaults = array('timeout' => 30);
    if (parse_url($url, PHP_URL_HOST) === parse_url(home_url(), PHP_URL_HOST)) {
        $defaults['sslverify'] = apply_filters('https_local_ssl_verify', false);
    }
    $response = wp_remote_get($url, wp_parse_args($args, $defaults));
    if (is_wp_error($response)) {
        error_log('Audit Accessibilité - Échec du chargement de ' . $url . ' : ' . $response->get_error_message());
    }
    return $response;
}

/**
 * La classe principale du plugin
 */
require_once AUDIT_ACCESSIBILITE_PLUGIN_DIR . 'includes/class-audit-accessibilite.php';

/**
 * Démarre l'exécution du plugin
 */
function run_audit_accessibilite() {
    global $audit_accessibilite;
    $audit_accessibilite = new Audit_Accessibilite();
    $audit_accessibilite->run();
}
run_audit_accessibilite(); 