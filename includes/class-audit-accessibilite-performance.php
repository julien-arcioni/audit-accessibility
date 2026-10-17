<?php

/**
 * Classe pour l'audit de performance
 *
 * @since      1.0.0
 */
class Audit_Accessibilite_Performance {

    /**
     * Exécute un audit de performance sur une URL donnée
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

        // Charger le contenu de la page
        $html = $this->get_page_content($url);
        
        if (!$html) {
            return array();
        }

        // Créer un DOM à partir du HTML
        $dom = new DOMDocument();
        $dom->validateOnParse = true;
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . htmlspecialchars($html, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</body></html>';
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);

        // Initialiser les résultats
        $issues = array();
        $tests_passed = 0;
        $tests_total = 0;

        // Tests de performance basiques
        $this->check_image_size($dom, $xpath, $issues, $tests_passed, $tests_total);
        $this->check_external_resources($dom, $xpath, $issues, $tests_passed, $tests_total);
        $this->check_css_js_minification($url, $issues, $tests_passed, $tests_total);
        
        // Calculer le score
        $score = ($tests_total > 0) ? max(0, round(($tests_passed / $tests_total) * 100)) : 0;
        
        // Préparer le résultat pour correspondre au format attendu par l'interface
        $formatted_issues = array();
        foreach ($issues as $issue) {
            $formatted_issues[] = array(
                'id' => isset($issue['element_id']) ? $issue['element_id'] : uniqid('perf_'),
                'description' => isset($issue['description']) ? $issue['description'] : (isset($issue['message']) ? $issue['message'] : ''),
                'location' => isset($issue['element']) ? $issue['element'] : $url,
                'priority' => isset($issue['priority']) ? $issue['priority'] : 'medium',
                'status' => 'open',
                'category' => 'performance',
                'criterion' => isset($issue['element_type']) ? $issue['element_type'] : 'performance',
                'solution' => isset($issue['recommendation']) ? $issue['recommendation'] : (isset($issue['solution']) ? $issue['solution'] : __('Optimiser les performances selon les recommandations.', 'audit-accessibilite'))
            );
        }
        
        return $formatted_issues;
    }

    /**
     * Récupère le contenu d'une page web
     *
     * @since    1.0.0
     * @param    string    $url    L'URL de la page
     * @return   string            Le contenu HTML de la page
     */
    private function get_page_content($url) {
        $response = audit_accessibilite_remote_get($url, array(
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $html = wp_remote_retrieve_body($response);
        
        if (empty($html)) {
            return false;
        }
        
        return $html;
    }

    /**
     * Vérifie la taille des images
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_image_size($dom, $xpath, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        $images = $xpath->query('//img');
        $large_images = 0;
        $total_images = $images->length;
        
        if ($total_images === 0) {
            $tests_passed++;
            return;
        }
        
        foreach ($images as $image) {
            $src = $image->getAttribute('src');
            
            // Si l'image est une URL complète
            if (filter_var($src, FILTER_VALIDATE_URL)) {
                $headers = get_headers($src, 1);
                
                if (isset($headers['Content-Length'])) {
                    $size = intval($headers['Content-Length']);
                    if ($size > 200000) { // 200KB
                        $large_images++;
                    }
                }
            }
        }
        
        if ($large_images > 0) {
            $issues[] = array(
                'element_id' => 'large_images',
                'element_type' => 'img',
                'description' => sprintf(__('%d image(s) de grande taille détectée(s).', 'audit-accessibilite'), $large_images),
                'priority' => 'medium',
                'recommendation' => __('Optimiser les images trop volumineuses (> 200KB) pour améliorer le temps de chargement.', 'audit-accessibilite')
            );
        } else {
            $tests_passed++;
        }
    }

    /**
     * Vérifie les ressources externes
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_external_resources($dom, $xpath, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        $site_host = parse_url(home_url(), PHP_URL_HOST);
        $external_js = $xpath->query('//script[@src]');
        $external_css = $xpath->query('//link[@rel="stylesheet"]');
        
        $external_count = 0;
        
        foreach ($external_js as $script) {
            $src = $script->getAttribute('src');
            $script_host = parse_url($src, PHP_URL_HOST);
            
            if ($script_host && $script_host !== $site_host) {
                $external_count++;
            }
        }
        
        foreach ($external_css as $stylesheet) {
            $href = $stylesheet->getAttribute('href');
            $css_host = parse_url($href, PHP_URL_HOST);
            
            if ($css_host && $css_host !== $site_host) {
                $external_count++;
            }
        }
        
        if ($external_count > 5) {
            $issues[] = array(
                'element_id' => 'external_resources',
                'element_type' => 'script/css',
                'description' => sprintf(__('Trop de ressources externes (%d) détectées.', 'audit-accessibilite'), $external_count),
                'priority' => 'medium',
                'recommendation' => __('Réduire le nombre de ressources externes pour améliorer la performance.', 'audit-accessibilite')
            );
        } else {
            $tests_passed++;
        }
    }

    /**
     * Vérifie si les fichiers CSS et JS sont minifiés
     *
     * @since    1.0.0
     * @param    string    $url           L'URL de la page
     * @param    array     &$issues       Référence au tableau des problèmes
     * @param    int       &$tests_passed Référence au compteur de tests réussis
     * @param    int       &$tests_total  Référence au compteur de tests total
     */
    private function check_css_js_minification($url, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        // Vérification simplifiée pour la version initiale
        // TODO: Ajouter une vérification plus complète
        
        $issues[] = array(
            'element_id' => 'minification',
            'element_type' => 'css/js',
            'description' => __('Vérification de minification recommandée.', 'audit-accessibilite'),
            'priority' => 'low',
            'recommendation' => __('S\'assurer que les fichiers CSS et JavaScript sont minifiés pour optimiser le temps de chargement.', 'audit-accessibilite')
        );
    }

    /**
     * Exécute un audit de performance complet sur toutes les pages accessibles
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
            error_log('Performance - URLs à analyser: ' . print_r($urls, true));
            
            // Analyser chaque URL
            foreach ($urls as $url) {
                $page_issues = $this->run_audit($url, $config);
                
                // Log pour debugging
                //error_log('Performance - Page analysée: ' . $url . ' - Problèmes trouvés: ' . count($page_issues));
                
                // Ajouter les problèmes de cette page au tableau global
                $all_issues = array_merge($all_issues, $page_issues);
            }
            
            // Log pour debugging
            error_log('Performance - Total des problèmes trouvés: ' . count($all_issues));
            
            return $all_issues;
        } catch (Exception $e) {
            error_log('Performance - Erreur lors de l\'audit complet: ' . $e->getMessage());
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
        
        // Récupérer les exclusions depuis les paramètres
        $options = get_option('audit_accessibilite_options', array());
        $exclusions = isset($options['custom_exclusions']) ? $options['custom_exclusions'] : '';
        $exclusion_patterns = $this->parse_exclusion_patterns($exclusions);
        
        // Récupérer le domaine du site
        $site_domain = parse_url($start_url, PHP_URL_HOST);
        error_log('Performance - Domaine du site : ' . $site_domain);
        
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
                if (!in_array($post_url, $to_visit) && !$this->is_url_excluded($post_url, $exclusion_patterns)) {
                    $to_visit[] = $post_url;
                    error_log('Performance - Ajout URL de post : ' . $post_url);
                }
            }
        }
        
        // Ajouter les URLs des pages
        $pages = get_pages();
        foreach ($pages as $page) {
            $page_url = get_page_link($page->ID);
            if (!in_array($page_url, $to_visit) && !$this->is_url_excluded($page_url, $exclusion_patterns)) {
                $to_visit[] = $page_url;
                error_log('Performance - Ajout URL de page : ' . $page_url);
            }
        }
        
        while (!empty($to_visit)) {
            $current_url = array_shift($to_visit);
            error_log('Performance - Analyse de l\'URL : ' . $current_url);
            
            // Si l'URL a déjà été visitée, passer à la suivante
            if (in_array($current_url, $visited)) {
                error_log('Performance - URL déjà visitée : ' . $current_url);
                continue;
            }
            
            // Vérifier si l'URL est exclue
            if ($this->is_url_excluded($current_url, $exclusion_patterns)) {
                error_log('Performance - URL exclue : ' . $current_url);
                continue;
            }
            
            // Vérifier que l'URL appartient au même domaine
            $url_domain = parse_url($current_url, PHP_URL_HOST);
            if ($url_domain !== $site_domain) {
                error_log('Performance - URL ignorée (domaine différent) : ' . $current_url);
                continue;
            }
            
            // Récupérer le contenu de la page
            $html = $this->get_page_content($current_url);
            if (!$html) {
                error_log('Performance - Impossible de charger le contenu : ' . $current_url);
                continue;
            }
            
            // Ajouter l'URL à la liste des URLs à analyser
            $urls[] = $current_url;
            $visited[] = $current_url;
            error_log('Performance - URL ajoutée aux résultats : ' . $current_url);
            
            try {
                $dom = new DOMDocument();
                $dom->validateOnParse = true;
                $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . htmlspecialchars($html, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</body></html>';
                @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
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
                        if (!in_array($href, $visited) && !in_array($href, $to_visit) && !$this->is_url_excluded($href, $exclusion_patterns)) {
                            $to_visit[] = $href;
                            error_log('Performance - Nouvelle URL trouvée : ' . $href);
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('Performance - Erreur lors de l\'extraction des liens : ' . $e->getMessage());
            }
        }
        
        error_log('Performance - Total des URLs trouvées : ' . count($urls));
        return $urls;
    }
    
    /**
     * Parse les patterns d'exclusion depuis la configuration
     *
     * @since    1.0.0
     * @param    string    $exclusions    Les exclusions en format texte
     * @return   array                    Les patterns d'exclusion
     */
    private function parse_exclusion_patterns($exclusions) {
        if (empty($exclusions)) {
            return array();
        }
        
        $patterns = array();
        $lines = explode("\n", $exclusions);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $patterns[] = $line;
            }
        }
        
        return $patterns;
    }
    
    /**
     * Vérifie si une URL doit être exclue selon les patterns
     *
     * @since    1.0.0
     * @param    string    $url         L'URL à vérifier
     * @param    array     $patterns    Les patterns d'exclusion
     * @return   boolean                True si l'URL doit être exclue
     */
    private function is_url_excluded($url, $patterns) {
        if (empty($patterns)) {
            return false;
        }
        
        foreach ($patterns as $pattern) {
            // Vérifier correspondance exacte
            if (strpos($url, $pattern) !== false) {
                return true;
            }
            
            // Vérifier avec regex si le pattern commence par /
            if (strpos($pattern, '/') === 0 && preg_match($pattern, $url)) {
                return true;
            }
        }
        
        return false;
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
} 