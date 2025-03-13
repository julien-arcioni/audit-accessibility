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
        $default_config = array();
        
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
        $latest_wp_version = '6.0';

        // TODO: Vérifier si la version de WordPress est la dernière
        
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

    /**
     * Exécute un audit de sécurité complet sur toutes les pages accessibles
     *
     * @since    1.0.0
     * @param    array     $config    Configuration de l'audit (optionnel)
     * @return   array                Les résultats de l'audit
     */
    public function run_full_audit($config = array()) {
        try {
            $default_config = array();
            
            // Fusionner la configuration par défaut avec la configuration fournie
            $config = wp_parse_args($config, $default_config);
            
            $home_url = home_url();
            
            $all_issues = array();
            
            // Récupérer les URLs à analyser
            $urls = $this->get_site_urls($home_url);
            
            // Log pour debugging
            error_log('Security - URLs à analyser: ' . print_r($urls, true));
            
            // Analyser chaque URL
            foreach ($urls as $url) {
                $page_issues = $this->run_audit($url, $config);
                
                // Log pour debugging
                error_log('Security - Page analysée: ' . $url . ' - Problèmes trouvés: ' . count($page_issues));
                
                // Ajouter les problèmes de cette page au tableau global
                $all_issues = array_merge($all_issues, $page_issues);
            }
            
            // Log pour debugging
            error_log('Security - Total des problèmes trouvés: ' . count($all_issues));
            
            return $all_issues;
        } catch (Exception $e) {
            error_log('Security - Erreur lors de l\'audit complet: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Récupère la liste des URLs à analyser
     *
     * @since    1.0.0
     * @param    string    $start_url    L'URL de départ
     * @return   array                   La liste des URLs
     */
    private function get_site_urls($start_url) {
        $urls = array();
        $visited = array();
        $to_visit = array($start_url);
        
        // Récupérer le domaine du site
        $site_domain = parse_url($start_url, PHP_URL_HOST);
        error_log('Security - Domaine du site : ' . $site_domain);
        
        // Ajouter les URLs des types de contenus personnalisés (CPT)
        $post_types = get_post_types(array('public' => true), 'objects');
        foreach ($post_types as $post_type) {
            $posts = get_posts(array(
                'post_type' => $post_type->name,
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
            
            foreach ($posts as $post) {
                $post_url = get_permalink($post->ID);
                if (!in_array($post_url, $to_visit)) {
                    $to_visit[] = $post_url;
                    error_log('Security - Ajout URL de post : ' . $post_url);
                }
            }
        }
        
        // Ajouter les URLs des pages
        $pages = get_pages();
        foreach ($pages as $page) {
            $page_url = get_page_link($page->ID);
            if (!in_array($page_url, $to_visit)) {
                $to_visit[] = $page_url;
                error_log('Security - Ajout URL de page : ' . $page_url);
            }
        }
        
        while (!empty($to_visit)) {
            $current_url = array_shift($to_visit);
            error_log('Security - Analyse de l\'URL : ' . $current_url);
            
            // Si l'URL a déjà été visitée, passer à la suivante
            if (in_array($current_url, $visited)) {
                error_log('Security - URL déjà visitée : ' . $current_url);
                continue;
            }
            
            // Vérifier que l'URL appartient au même domaine
            $url_domain = parse_url($current_url, PHP_URL_HOST);
            if ($url_domain !== $site_domain) {
                error_log('Security - URL ignorée (domaine différent) : ' . $current_url);
                continue;
            }
            
            // Récupérer le contenu de la page
            $html = $this->get_page_content($current_url);
            if (!$html) {
                error_log('Security - Impossible de charger le contenu : ' . $current_url);
                continue;
            }
            
            // Ajouter l'URL à la liste des URLs à analyser
            $urls[] = $current_url;
            $visited[] = $current_url;
            error_log('Security - URL ajoutée aux résultats : ' . $current_url);
            
            try {
                $dom = new DOMDocument();
                @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
                $xpath = new DOMXPath($dom);
                
                // Récupérer tous les liens
                $links = $xpath->query('//a[@href]');
                foreach ($links as $link) {
                    $href = $link->getAttribute('href');
                    
                    // Convertir les URLs relatives en URLs absolues
                    if (strpos($href, 'http') !== 0) {
                        $href = $this->make_absolute_url($href, $current_url);
                    }
                    
                    // Vérifier que l'URL est valide et appartient au même domaine
                    if ($this->is_valid_url($href) && parse_url($href, PHP_URL_HOST) === $site_domain) {
                        if (!in_array($href, $visited) && !in_array($href, $to_visit)) {
                            $to_visit[] = $href;
                            error_log('Security - Nouvelle URL trouvée : ' . $href);
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('Security - Erreur lors de l\'extraction des liens : ' . $e->getMessage());
            }
        }
        
        error_log('Security - Total des URLs trouvées : ' . count($urls));
        return $urls;
    }

    /**
     * Convertit une URL relative en URL absolue
     *
     * @since    1.0.0
     * @param    string    $relative    L'URL relative
     * @param    string    $base        L'URL de base
     * @return   string                 L'URL absolue
     */
    private function make_absolute_url($relative, $base) {
        if (strpos($relative, '//') === 0) {
            return 'https:' . $relative;
        }
        
        if (strpos($relative, '/') === 0) {
            $base_parts = parse_url($base);
            return $base_parts['scheme'] . '://' . $base_parts['host'] . $relative;
        }
        
        $base_parts = parse_url($base);
        $base_path = isset($base_parts['path']) ? dirname($base_parts['path']) : '/';
        return $base_parts['scheme'] . '://' . $base_parts['host'] . $base_path . '/' . $relative;
    }
    
    /**
     * Vérifie si une URL est valide
     *
     * @since    1.0.0
     * @param    string    $url    L'URL à vérifier
     * @return   boolean          True si l'URL est valide
     */
    private function is_valid_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Récupère le contenu d'une page web
     *
     * @since    1.0.0
     * @param    string    $url    L'URL de la page
     * @return   string            Le contenu HTML de la page
     */
    private function get_page_content($url) {
        $response = wp_remote_get($url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $html = wp_remote_retrieve_body($response);
        
        if (empty($html)) {
            return false;
        }
        
        return $html;
    }
} 