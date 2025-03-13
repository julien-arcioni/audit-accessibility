<?php

/**
 * Classe pour l'audit SEO
 *
 * @since      1.0.0
 */
class Audit_Accessibilite_SEO {

    /**
     * Exécute un audit SEO sur une URL donnée
     *
     * @since    1.0.0
     * @param    string    $url              L'URL à auditer
     * @param    array     $config           Configuration de l'audit (optionnel)
     * @return   array                       Les résultats de l'audit
     */
    public function run_audit($url, $config = array()) {
        try {
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
                error_log('SEO - Impossible de charger le contenu de la page: ' . $url);
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

            // Tests SEO de base
            $this->check_meta_title($dom, $xpath, $issues, $tests_passed, $tests_total);
            $this->check_meta_description($dom, $xpath, $issues, $tests_passed, $tests_total);
            $this->check_headings($dom, $xpath, $issues, $tests_passed, $tests_total);
            $this->check_images_alt($dom, $xpath, $issues, $tests_passed, $tests_total);
            $this->check_canonical($dom, $xpath, $issues, $tests_passed, $tests_total);
            
            // Calculer le score
            $score = ($tests_total > 0) ? round(($tests_passed / $tests_total) * 100) : 0;
            
            // Préparer le résultat pour correspondre au format attendu par l'interface
            $formatted_issues = array();
            foreach ($issues as $issue) {
                $formatted_issues[] = array(
                    'id' => isset($issue['element_id']) ? $issue['element_id'] : uniqid('seo_'),
                    'description' => isset($issue['message']) ? $issue['message'] : (isset($issue['description']) ? $issue['description'] : ''),
                    'location' => isset($issue['element']) ? $issue['element'] : $url,
                    'priority' => isset($issue['priority']) ? $issue['priority'] : 'medium',
                    'status' => 'open',
                    'category' => 'seo',
                    'criterion' => isset($issue['element_type']) ? $issue['element_type'] : 'unknown',
                    'solution' => isset($issue['solution']) ? $issue['solution'] : (isset($issue['recommendation']) ? $issue['recommendation'] : __('Vérifier et corriger le problème SEO identifié.', 'audit-accessibilite'))
                );
            }
            
            return $formatted_issues;
        } catch (Exception $e) {
            error_log('SEO - Erreur lors de l\'audit: ' . $e->getMessage());
            return array();
        }
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

    /**
     * Vérifie la balise titre pour le SEO
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_meta_title($dom, $xpath, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        $titles = $xpath->query('//title');
        
        if ($titles->length === 0) {
            $issues[] = array(
                'element_id' => 'meta_title',
                'element_type' => 'title',
                'description' => __('La page ne contient pas de balise title.', 'audit-accessibilite'),
                'priority' => 'high',
                'recommendation' => __('Ajouter une balise title optimisée pour le SEO, entre 50 et 60 caractères.', 'audit-accessibilite')
            );
        } else {
            $title = trim($titles->item(0)->textContent);
            $title_length = mb_strlen($title);
            
            if (empty($title)) {
                $issues[] = array(
                    'element_id' => 'meta_title',
                    'element_type' => 'title',
                    'description' => __('La balise title est vide.', 'audit-accessibilite'),
                    'priority' => 'high',
                    'recommendation' => __('Ajouter un contenu pertinent à la balise title.', 'audit-accessibilite')
                );
            } elseif ($title_length < 30) {
                $issues[] = array(
                    'element_id' => 'meta_title',
                    'element_type' => 'title',
                    'description' => sprintf(__('La balise title est trop courte (%d caractères).', 'audit-accessibilite'), $title_length),
                    'priority' => 'medium',
                    'recommendation' => __('Allonger la balise title pour qu\'elle contienne entre 50 et 60 caractères.', 'audit-accessibilite')
                );
            } elseif ($title_length > 60) {
                $issues[] = array(
                    'element_id' => 'meta_title',
                    'element_type' => 'title',
                    'description' => sprintf(__('La balise title est trop longue (%d caractères).', 'audit-accessibilite'), $title_length),
                    'priority' => 'medium',
                    'recommendation' => __('Raccourcir la balise title pour qu\'elle contienne entre 50 et 60 caractères.', 'audit-accessibilite')
                );
            } else {
                $tests_passed++;
            }
        }
    }

    /**
     * Vérifie la balise meta description pour le SEO
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_meta_description($dom, $xpath, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        $descriptions = $xpath->query('//meta[@name="description"]');
        
        if ($descriptions->length === 0) {
            $issues[] = array(
                'element_id' => 'meta_description',
                'element_type' => 'meta',
                'description' => __('La page ne contient pas de balise meta description.', 'audit-accessibilite'),
                'priority' => 'high',
                'recommendation' => __('Ajouter une balise meta description optimisée pour le SEO, entre 120 et 158 caractères.', 'audit-accessibilite')
            );
        } else {
            $description = $descriptions->item(0)->getAttribute('content');
            $description_length = mb_strlen($description);
            
            if (empty($description)) {
                $issues[] = array(
                    'element_id' => 'meta_description',
                    'element_type' => 'meta',
                    'description' => __('La balise meta description est vide.', 'audit-accessibilite'),
                    'priority' => 'high',
                    'recommendation' => __('Ajouter un contenu pertinent à la balise meta description.', 'audit-accessibilite')
                );
            } elseif ($description_length < 120) {
                $issues[] = array(
                    'element_id' => 'meta_description',
                    'element_type' => 'meta',
                    'description' => sprintf(__('La balise meta description est trop courte (%d caractères).', 'audit-accessibilite'), $description_length),
                    'priority' => 'medium',
                    'recommendation' => __('Allonger la balise meta description pour qu\'elle contienne entre 120 et 158 caractères.', 'audit-accessibilite')
                );
            } elseif ($description_length > 158) {
                $issues[] = array(
                    'element_id' => 'meta_description',
                    'element_type' => 'meta',
                    'description' => sprintf(__('La balise meta description est trop longue (%d caractères).', 'audit-accessibilite'), $description_length),
                    'priority' => 'medium',
                    'recommendation' => __('Raccourcir la balise meta description pour qu\'elle contienne entre 120 et 158 caractères.', 'audit-accessibilite')
                );
            } else {
                $tests_passed++;
            }
        }
    }

    /**
     * Vérifie la structure des titres pour le SEO
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_headings($dom, $xpath, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        $h1s = $xpath->query('//h1');
        
        if ($h1s->length === 0) {
            $issues[] = array(
                'element_id' => 'h1_missing',
                'element_type' => 'h1',
                'description' => __('La page ne contient pas de titre h1.', 'audit-accessibilite'),
                'priority' => 'high',
                'recommendation' => __('Ajouter un titre h1 contenant le mot-clé principal.', 'audit-accessibilite')
            );
        } elseif ($h1s->length > 1) {
            $issues[] = array(
                'element_id' => 'h1_multiple',
                'element_type' => 'h1',
                'description' => sprintf(__('La page contient %d titres h1.', 'audit-accessibilite'), $h1s->length),
                'priority' => 'medium',
                'recommendation' => __('Il est recommandé de n\'avoir qu\'un seul titre h1 par page.', 'audit-accessibilite')
            );
        } else {
            $h1_content = trim($h1s->item(0)->textContent);
            
            if (empty($h1_content) || mb_strlen($h1_content) < 5) {
                $issues[] = array(
                    'element_id' => 'h1_content',
                    'element_type' => 'h1',
                    'description' => __('Le contenu du titre h1 est trop court ou vide.', 'audit-accessibilite'),
                    'priority' => 'medium',
                    'recommendation' => __('Améliorer le contenu du titre h1 en incluant le mot-clé principal.', 'audit-accessibilite')
                );
            } else {
                $tests_passed++;
            }
        }
    }

    /**
     * Vérifie les attributs alt des images pour le SEO
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_images_alt($dom, $xpath, &$issues, &$tests_passed, &$tests_total) {
        $images = $xpath->query('//img');
        $total_images = $images->length;
        $images_with_good_alt = 0;
        
        if ($total_images > 0) {
            $tests_total++;
            
            foreach ($images as $image) {
                $alt = $image->getAttribute('alt');
                $src = $image->getAttribute('src');
                
                if (!empty($alt) && !preg_match('/^(image|img|picture|photo|\s*|\d+)$/i', $alt)) {
                    $images_with_good_alt++;
                }
            }
            
            $alt_ratio = round(($images_with_good_alt / $total_images) * 100);
            
            if ($alt_ratio < 80) {
                $issues[] = array(
                    'element_id' => 'images_alt_seo',
                    'element_type' => 'img',
                    'description' => sprintf(__('Seulement %d%% des images ont un attribut alt descriptif pour le SEO.', 'audit-accessibilite'), $alt_ratio),
                    'priority' => 'medium',
                    'recommendation' => __('Optimiser les attributs alt des images en incluant des mots-clés pertinents tout en restant descriptif.', 'audit-accessibilite')
                );
            } else {
                $tests_passed++;
            }
        }
    }

    /**
     * Vérifie la présence d'une balise canonique
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_canonical($dom, $xpath, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        $canonicals = $xpath->query('//link[@rel="canonical"]');
        
        if ($canonicals->length === 0) {
            $issues[] = array(
                'element_id' => 'canonical',
                'element_type' => 'link',
                'description' => __('La page ne contient pas de balise canonique.', 'audit-accessibilite'),
                'priority' => 'medium',
                'recommendation' => __('Ajouter une balise link rel="canonical" pour indiquer l\'URL préférée de la page.', 'audit-accessibilite')
            );
        } else {
            $canonical_href = $canonicals->item(0)->getAttribute('href');
            
            if (empty($canonical_href)) {
                $issues[] = array(
                    'element_id' => 'canonical_empty',
                    'element_type' => 'link',
                    'description' => __('La balise canonique est présente mais son attribut href est vide.', 'audit-accessibilite'),
                    'priority' => 'medium',
                    'recommendation' => __('Définir correctement l\'attribut href de la balise canonique.', 'audit-accessibilite')
                );
            } else {
                $tests_passed++;
            }
        }
    }
}
