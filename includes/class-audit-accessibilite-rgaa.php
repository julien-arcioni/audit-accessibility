<?php

/**
 * Classe pour l'audit d'accessibilité selon le RGAA 4.1
 *
 * @since      1.0.0
 */
class Audit_Accessibilite_RGAA {

    /**
     * Thématiques du RGAA 4.1
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $rgaa_themes    Les thématiques du RGAA 4.1
     */
    private $rgaa_themes = array(
        1 => 'Images',
        2 => 'Cadres',
        3 => 'Couleurs',
        4 => 'Multimédia',
        5 => 'Tableaux',
        6 => 'Liens',
        7 => 'Scripts',
        8 => 'Éléments obligatoires',
        9 => 'Structuration de l\'information',
        10 => 'Présentation de l\'information',
        11 => 'Formulaires',
        12 => 'Navigation',
        13 => 'Consultation'
    );

    /**
     * Exécute un audit d'accessibilité RGAA sur une URL donnée
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
            // Retourner un tableau vide en cas d'erreur
            error_log('RGAA - Impossible de charger le contenu de la page: ' . $url);
            return array();
        }

        try {
            // Créer un DOM à partir du HTML
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);

            // Initialiser les résultats
            $issues = array();
            $tests_passed = 0;
            $tests_total = 0;

            // Pour cette version initiale, nous testons seulement quelques critères essentiels
            // Thématique 1 : Images
            $this->check_images_alt($dom, $xpath, $issues, $tests_passed, $tests_total);
            
            // Thématique 3 : Couleurs
            $this->check_contrast($dom, $xpath, $issues, $tests_passed, $tests_total);
            
            // Thématique 6 : Liens
            $this->check_links_text($dom, $xpath, $issues, $tests_passed, $tests_total);
            
            // Thématique 8 : Éléments obligatoires
            $this->check_document_title($dom, $xpath, $issues, $tests_passed, $tests_total);
            $this->check_page_language($dom, $xpath, $issues, $tests_passed, $tests_total);
            
            // Thématique 9 : Structuration de l'information
            $this->check_headings_structure($dom, $xpath, $issues, $tests_passed, $tests_total);
            
            // Calculer le score
            $score = ($tests_total > 0) ? round(($tests_passed / $tests_total) * 100) : 0;
            
            // Préparer le résultat pour correspondre au format attendu par l'interface
            $formatted_issues = array();
            foreach ($issues as $issue) {
                // Assurer que toutes les clés nécessaires existent
                $issue_id = isset($issue['id']) ? $issue['id'] : (isset($issue['element_id']) ? $issue['element_id'] : uniqid('rgaa_'));
                $issue_description = isset($issue['description']) ? $issue['description'] : '';
                $issue_location = isset($issue['element']) ? $issue['element'] : $url;
                $issue_priority = isset($issue['priority']) ? $issue['priority'] : 'medium';
                $issue_category = isset($issue['category']) ? $issue['category'] : 'rgaa';
                $issue_criterion = isset($issue['criterion']) ? $issue['criterion'] : '';
                $issue_solution = isset($issue['solution']) ? $issue['solution'] : (isset($issue['recommendation']) ? $issue['recommendation'] : '');
                
                $formatted_issues[] = array(
                    'id' => $issue_id,
                    'description' => $issue_description,
                    'location' => $issue_location,
                    'priority' => $issue_priority,
                    'status' => 'open',
                    'category' => $issue_category,
                    'criterion' => $issue_criterion,
                    'solution' => $issue_solution
                );
            }
            
            return $formatted_issues;
        } catch (Exception $e) {
            error_log('RGAA - Erreur lors de l\'audit: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Récupère le nombre total de critères RGAA testés
     *
     * @since    1.0.0
     * @return   int       Le nombre total de critères RGAA
     */
    public function get_total_criteria_count() {
        try {
            // Nombre total de critères dans le RGAA 4.1
            // Dans notre implémentation actuelle, nous ne testons que 6 critères
            // Ce nombre devrait correspondre au nombre total de critères testés
            // et être mis à jour à mesure que nous implémentons plus de tests
            return 6;
        } catch (Exception $e) {
            error_log('RGAA - Erreur dans get_total_criteria_count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Exécute un audit d'accessibilité RGAA complet sur toutes les pages accessibles
     *
     * @since    1.0.0
     * @param    array     $config    Configuration de l'audit (optionnel)
     * @return   array                Les résultats de l'audit
     */
    public function run_full_audit($config = array()) {
        // Configuration par défaut
        $default_config = array(
            'max_pages' => 10,
            'crawl_depth' => 2,
        );
        
        // Fusionner la configuration par défaut avec la configuration fournie
        $config = wp_parse_args($config, $default_config);
        
        // URL du site
        $home_url = home_url();
        
        // Initialiser les résultats
        $all_issues = array();
        
        // Récupérer la liste des URLs à analyser
        $urls = $this->get_site_urls($home_url, $config['crawl_depth'], $config['max_pages']);
        
        // Analyser chaque URL
        foreach ($urls as $url) {
            $page_issues = $this->run_audit($url, $config);
            $all_issues = array_merge($all_issues, $page_issues);
        }
        
        return $all_issues;
    }
    
    /**
     * Récupère la liste des URLs à analyser
     *
     * @since    1.0.0
     * @param    string    $start_url    L'URL de départ
     * @param    int       $depth        La profondeur de crawl
     * @param    int       $max_pages    Le nombre maximum de pages à analyser
     * @return   array                   La liste des URLs
     */
    private function get_site_urls($start_url, $depth, $max_pages) {
        // Pour cette version simple, on retourne juste l'URL de départ
        // Dans une version plus avancée, on pourrait implémenter un crawler
        return array($start_url);
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
     * Vérifie si les images ont des alternatives textuelles (RGAA 1.1)
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
        
        foreach ($images as $image) {
            $tests_total++;
            
            $alt = $image->getAttribute('alt');
            $src = $image->getAttribute('src');
            $element_id = !empty($image->getAttribute('id')) ? $image->getAttribute('id') : md5($src);
            
            if (empty($alt) && !$image->hasAttribute('role') && $image->getAttribute('role') !== 'presentation') {
                $issues[] = array(
                    'theme' => 1,
                    'criterion' => 1.1,
                    'element_id' => $element_id,
                    'element_type' => 'img',
                    'description' => sprintf(__('L\'image %s n\'a pas d\'attribut alt.', 'audit-accessibilite'), $src),
                    'priority' => 'high',
                    'recommendation' => __('Ajouter un attribut alt à cette image pour décrire son contenu ou utiliser role="presentation" si elle est purement décorative.', 'audit-accessibilite')
                );
            } else {
                $tests_passed++;
            }
        }
    }

    /**
     * Vérifie le contraste des couleurs (RGAA 3.1 à 3.3)
     * Note: Cette méthode nécessiterait une analyse plus poussée des CSS pour être précise
     * Pour cette version, nous faisons une vérification basique
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_contrast($dom, $xpath, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        // Pour cette version, nous ajoutons simplement un avertissement général
        // car l'analyse précise des contrastes nécessite des outils spécifiques
        $issues[] = array(
            'theme' => 3,
            'criterion' => 3.1,
            'element_id' => 'contrast',
            'element_type' => 'general',
            'description' => __('Vérification manuelle requise : contraste des couleurs', 'audit-accessibilite'),
            'priority' => 'medium',
            'recommendation' => __('Vérifier que le contraste entre la couleur du texte et celle de son arrière-plan est suffisamment élevé (4.5:1 pour le texte normal, 3:1 pour le texte de grande taille).', 'audit-accessibilite')
        );
    }

    /**
     * Vérifie que les liens ont un intitulé explicite (RGAA 6.1)
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_links_text($dom, $xpath, &$issues, &$tests_passed, &$tests_total) {
        $links = $xpath->query('//a');
        
        foreach ($links as $link) {
            $tests_total++;
            
            $href = $link->getAttribute('href');
            $text = trim($link->textContent);
            $element_id = !empty($link->getAttribute('id')) ? $link->getAttribute('id') : md5($href . $text);
            
            // Vérifier les liens sans texte ou avec texte trop court/non explicite
            if (empty($text) || strlen($text) < 3 || in_array(strtolower($text), array('ici', 'cliquez', 'click', 'here', 'lien'))) {
                $issues[] = array(
                    'theme' => 6,
                    'criterion' => 6.1,
                    'element_id' => $element_id,
                    'element_type' => 'a',
                    'description' => sprintf(__('Le lien "%s" n\'a pas d\'intitulé explicite.', 'audit-accessibilite'), !empty($text) ? $text : $href),
                    'priority' => 'medium',
                    'recommendation' => __('Fournir un intitulé de lien explicite qui décrit sa fonction ou sa destination.', 'audit-accessibilite')
                );
            } else {
                $tests_passed++;
            }
        }
    }

    /**
     * Vérifie la présence d'un titre de document pertinent (RGAA 8.5)
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_document_title($dom, $xpath, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        $titles = $xpath->query('//title');
        
        if ($titles->length === 0) {
            $issues[] = array(
                'theme' => 8,
                'criterion' => 8.5,
                'element_id' => 'document_title',
                'element_type' => 'title',
                'description' => __('Le document ne possède pas de balise title.', 'audit-accessibilite'),
                'priority' => 'high',
                'recommendation' => __('Ajouter une balise title pertinente décrivant le contenu de la page.', 'audit-accessibilite')
            );
        } else {
            $title = trim($titles->item(0)->textContent);
            
            if (empty($title) || strlen($title) < 5) {
                $issues[] = array(
                    'theme' => 8,
                    'criterion' => 8.5,
                    'element_id' => 'document_title',
                    'element_type' => 'title',
                    'description' => __('Le titre du document est vide ou trop court.', 'audit-accessibilite'),
                    'priority' => 'high',
                    'recommendation' => __('Fournir un titre de document pertinent et suffisamment descriptif.', 'audit-accessibilite')
                );
            } else {
                $tests_passed++;
            }
        }
    }

    /**
     * Vérifie la déclaration de la langue de la page (RGAA 8.3)
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_page_language($dom, $xpath, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        $html = $dom->getElementsByTagName('html')->item(0);
        
        if (!$html->hasAttribute('lang')) {
            $issues[] = array(
                'theme' => 8,
                'criterion' => 8.3,
                'element_id' => 'html_lang',
                'element_type' => 'html',
                'description' => __('La langue de la page n\'est pas définie.', 'audit-accessibilite'),
                'priority' => 'high',
                'recommendation' => __('Ajouter l\'attribut lang à la balise html, par exemple : <html lang="fr">.', 'audit-accessibilite')
            );
        } else {
            $lang = $html->getAttribute('lang');
            
            if (empty($lang)) {
                $issues[] = array(
                    'theme' => 8,
                    'criterion' => 8.3,
                    'element_id' => 'html_lang',
                    'element_type' => 'html',
                    'description' => __('L\'attribut lang de la page est présent mais vide.', 'audit-accessibilite'),
                    'priority' => 'high',
                    'recommendation' => __('Définir correctement la valeur de l\'attribut lang (par exemple "fr" pour le français).', 'audit-accessibilite')
                );
            } else {
                $tests_passed++;
            }
        }
    }

    /**
     * Vérifie la structure des titres (RGAA 9.1)
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_headings_structure($dom, $xpath, &$issues, &$tests_passed, &$tests_total) {
        $tests_total++;
        
        $headings = array();
        for ($i = 1; $i <= 6; $i++) {
            $h_elements = $xpath->query('//h' . $i);
            foreach ($h_elements as $h) {
                $headings[] = array(
                    'level' => $i,
                    'text' => trim($h->textContent),
                    'id' => !empty($h->getAttribute('id')) ? $h->getAttribute('id') : md5($i . $h->textContent)
                );
            }
        }
        
        // Vérifier si des titres sont présents
        if (empty($headings)) {
            $issues[] = array(
                'theme' => 9,
                'criterion' => 9.1,
                'element_id' => 'headings_structure',
                'element_type' => 'general',
                'description' => __('Aucun titre (h1-h6) n\'a été trouvé dans la page.', 'audit-accessibilite'),
                'priority' => 'medium',
                'recommendation' => __('Structurer le contenu en utilisant des titres de niveaux appropriés (h1-h6).', 'audit-accessibilite')
            );
            return;
        }
        
        // Vérifier si un h1 est présent
        $has_h1 = false;
        foreach ($headings as $heading) {
            if ($heading['level'] === 1) {
                $has_h1 = true;
                break;
            }
        }
        
        if (!$has_h1) {
            $issues[] = array(
                'theme' => 9,
                'criterion' => 9.1,
                'element_id' => 'headings_h1',
                'element_type' => 'h1',
                'description' => __('Aucun titre de niveau 1 (h1) n\'a été trouvé dans la page.', 'audit-accessibilite'),
                'priority' => 'medium',
                'recommendation' => __('Ajouter un titre h1 principal pour décrire le contenu de la page.', 'audit-accessibilite')
            );
        }
        
        // Vérifier la hiérarchie des titres
        $previous_level = 0;
        foreach ($headings as $heading) {
            if ($heading['level'] > $previous_level + 1 && $previous_level > 0) {
                $issues[] = array(
                    'theme' => 9,
                    'criterion' => 9.1,
                    'element_id' => $heading['id'],
                    'element_type' => 'h' . $heading['level'],
                    'description' => sprintf(__('Saut dans la hiérarchie des titres : passage de h%d à h%d.', 'audit-accessibilite'), $previous_level, $heading['level']),
                    'priority' => 'medium',
                    'recommendation' => __('Corriger la hiérarchie des titres pour éviter les sauts de niveaux.', 'audit-accessibilite')
                );
            }
            $previous_level = $heading['level'];
        }
        
        // Si aucun problème trouvé, test réussi
        if (count($issues) === $tests_total - 1) {
            $tests_passed++;
        }
    }
}
