<?php

/**
 * Classe pour l'audit de sécurité
 *
 * @since      1.0.0
 */
class Audit_Accessibilite_Security {

    /**
     * Exécute un audit de sécurité sur une URL donnée
     *
     * @since    1.0.0
     * @param    string    $url              L'URL à auditer
     * @param    array     $config           Configuration de l'audit (optionnel)
     * @return   array                       Les résultats de l'audit
     */
    public function run_audit($url, $config = array()) {
        // Configuration par défaut
        $default_config = array(
            'max_pages' => 10,
            'crawl_depth' => 2,
        );
        
        // Fusionner la configuration par défaut avec la configuration fournie
        $config = wp_parse_args($config, $default_config);

        // Initialiser les résultats
        $issues = array();
        $tests_passed = 0;
        $tests_total = 0;

        // Tests de sécurité basiques
        $this->check_https($url, $issues, $tests_passed, $tests_total);
        $this->check_security_headers($url, $issues, $tests_passed, $tests_total);
        $this->check_wordpress_version($issues, $tests_passed, $tests_total);
        
        // Calculer le score
        $score = ($tests_total > 0) ? round(($tests_passed / $tests_total) * 100) : 0;
        
        // Préparer le résultat pour correspondre au format attendu par l'interface
        $formatted_issues = array();
        foreach ($issues as $issue) {
            $formatted_issues[] = array(
                'id' => isset($issue['element_id']) ? $issue['element_id'] : uniqid('sec_'),
                'description' => isset($issue['description']) ? $issue['description'] : '',
                'location' => isset($issue['element']) ? $issue['element'] : $url,
                'priority' => isset($issue['priority']) ? $issue['priority'] : 'high',
                'status' => 'open',
                'category' => 'security',
                'criterion' => isset($issue['element_type']) ? $issue['element_type'] : 'security',
                'solution' => isset($issue['recommendation']) ? $issue['recommendation'] : __('Résoudre ce problème de sécurité selon les recommandations.', 'audit-accessibilite')
            );
        }
        
        return $formatted_issues;
    }

    /**
     * Vérifie si le site utilise HTTPS
     *
     * @since    1.0.0
     * @param    string    $url           L'URL de la page
     * @param    array     &$issues       Référence au tableau des problèmes
     * @param    int       &$tests_passed Référence au compteur de tests réussis
     * @param    int       &$tests_total  Référence au compteur de tests total
     */
    private function check_https($url, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        $scheme = parse_url($url, PHP_URL_SCHEME);
        
        if ($scheme !== 'https') {
            $issues[] = array(
                'element_id' => 'https',
                'element_type' => 'security',
                'description' => __('Le site n\'utilise pas HTTPS.', 'audit-accessibilite'),
                'priority' => 'high',
                'recommendation' => __('Mettre en place HTTPS pour sécuriser les communications entre le serveur et les utilisateurs.', 'audit-accessibilite')
            );
        } else {
            $tests_passed++;
        }
    }

    /**
     * Vérifie les en-têtes de sécurité HTTP
     *
     * @since    1.0.0
     * @param    string    $url           L'URL de la page
     * @param    array     &$issues       Référence au tableau des problèmes
     * @param    int       &$tests_passed Référence au compteur de tests réussis
     * @param    int       &$tests_total  Référence au compteur de tests total
     */
    private function check_security_headers($url, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        $response = wp_remote_get($url, array(
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            $issues[] = array(
                'element_id' => 'headers_error',
                'element_type' => 'security',
                'description' => __('Impossible de vérifier les en-têtes de sécurité.', 'audit-accessibilite'),
                'priority' => 'medium',
                'recommendation' => __('Vérifier l\'accessibilité du site et réessayer.', 'audit-accessibilite')
            );
            return;
        }
        
        $headers = wp_remote_retrieve_headers($response);
        $missing_headers = array();
        
        $security_headers = array(
            'x-content-type-options' => 'nosniff',
            'x-frame-options' => 'SAMEORIGIN',
            'strict-transport-security' => null,
            'content-security-policy' => null,
            'referrer-policy' => null,
            'permissions-policy' => null
        );
        
        foreach ($security_headers as $header => $expected_value) {
            if (!isset($headers[$header])) {
                $missing_headers[] = $header;
            } elseif ($expected_value !== null && $headers[$header] !== $expected_value) {
                $missing_headers[] = $header . ' (valeur incorrecte)';
            }
        }
        
        if (!empty($missing_headers)) {
            $issues[] = array(
                'element_id' => 'security_headers',
                'element_type' => 'security',
                'description' => sprintf(__('En-têtes de sécurité manquants : %s', 'audit-accessibilite'), implode(', ', $missing_headers)),
                'priority' => 'medium',
                'recommendation' => __('Ajouter les en-têtes de sécurité manquants pour renforcer la sécurité du site.', 'audit-accessibilite')
            );
        } else {
            $tests_passed++;
        }
    }

    /**
     * Vérifie la version de WordPress
     *
     * @since    1.0.0
     * @param    array     &$issues       Référence au tableau des problèmes
     * @param    int       &$tests_passed Référence au compteur de tests réussis
     * @param    int       &$tests_total  Référence au compteur de tests total
     */
    private function check_wordpress_version(&$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        global $wp_version;
        
        // Récupérer la dernière version stable de WordPress
        // Cette méthode est simplifiée pour la version initiale
        $latest_wp_version = '6.0'; // À remplacer par une méthode de récupération dynamique dans une version future
        
        if (version_compare($wp_version, $latest_wp_version, '<')) {
            $issues[] = array(
                'element_id' => 'wp_version',
                'element_type' => 'security',
                'description' => sprintf(__('Version de WordPress obsolète (%s). La dernière version est %s.', 'audit-accessibilite'), $wp_version, $latest_wp_version),
                'priority' => 'high',
                'recommendation' => __('Mettre à jour WordPress vers la dernière version pour bénéficier des dernières corrections de sécurité.', 'audit-accessibilite')
            );
        } else {
            $tests_passed++;
        }
    }
} 