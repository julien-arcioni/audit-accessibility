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
        $default_config = array(
            'max_pages' => 10,
            'crawl_depth' => 2,
        );
        
        // Fusionner la configuration par défaut avec la configuration fournie
        $config = wp_parse_args($config, $default_config);

        // Charger le contenu de la page
        $html = $this->get_page_content($url);
        
        if (!$html) {
            return array();
        }

        // Créer un DOM à partir du HTML
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
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
        $score = ($tests_total > 0) ? round(($tests_passed / $tests_total) * 100) : 0;
        
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
        $response = wp_remote_get($url, array(
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
        
        // Cette vérification est simplifiée pour la version initiale
        // Une vérification plus complète impliquerait de télécharger les fichiers et d'analyser leur contenu
        
        // Pour cette version, nous nous contentons d'un rappel de bonnes pratiques
        $issues[] = array(
            'element_id' => 'minification',
            'element_type' => 'css/js',
            'description' => __('Vérification de minification recommandée.', 'audit-accessibilite'),
            'priority' => 'low',
            'recommendation' => __('S\'assurer que les fichiers CSS et JavaScript sont minifiés pour optimiser le temps de chargement.', 'audit-accessibilite')
        );
    }
} 