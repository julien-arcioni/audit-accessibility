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
     * Tests réussis et tests effectués, cumulés sur toutes les pages auditées
     *
     * @since    1.1.0
     * @access   private
     * @var      array    $tests_counts
     */
    private $tests_counts = array('passed' => 0, 'total' => 0);

    /**
     * Exécute un audit d'accessibilité RGAA sur une URL donnée
     *
     * @since    1.0.0
     * @param    string    $url              L'URL à auditer
     * @return   array                       Les résultats de l'audit
     */
    public function run_audit($url) {
        $html = $this->get_page_content($url);
        
        if (!$html) {
            error_log('RGAA - Impossible de charger le contenu de la page: ' . $url);
            return array();
        }

        return $this->analyser_html($html, $url);
    }

    /**
     * Analyse un code HTML et renvoie les problèmes RGAA détectés
     *
     * @since    1.1.0
     * @param    string    $html    Le code HTML de la page
     * @param    string    $url     L'URL de la page
     * @return   array              Les problèmes, avec les clés rule (identifiant de règle) et path (chemin de l'élément, null pour une règle de page)
     */
    public function analyser_html($html, $url = '') {
        try {
            $dom = new DOMDocument();
            $dom->validateOnParse = true;
            @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($dom);

            $issues = array();
            $tests_passed = 0;
            $tests_total = 0;

            // Vérification des images
            $this->check_images_alt($dom, $xpath, $issues, $tests_passed, $tests_total, $url);
            
            // Vérification des contrastes
            //$this->check_contrast($dom, $xpath, $issues, $tests_passed, $tests_total, $url);
            
            // Vérification des liens
            $this->check_links_text($dom, $xpath, $issues, $tests_passed, $tests_total, $url);
            
            // Vérification du titre du document
            $this->check_document_title($dom, $xpath, $issues, $tests_passed, $tests_total, $url);
            
            // Vérification de la langue
            $this->check_page_language($dom, $xpath, $issues, $tests_passed, $tests_total, $url);
            
            // Vérification de la structure des titres
            $this->check_headings_structure($dom, $xpath, $issues, $tests_passed, $tests_total, $url);
            
            // Vérification des formulaires
            $this->check_forms($dom, $xpath, $issues, $tests_passed, $tests_total, $url);
            
            // Vérification des tableaux
            $this->check_tables($dom, $xpath, $issues, $tests_passed, $tests_total, $url);
            
            $this->tests_counts['passed'] += $tests_passed;
            $this->tests_counts['total'] += $tests_total;

            $formatted_issues = array();
            foreach ($issues as $issue) {
                $issue_id = isset($issue['id']) ? $issue['id'] : (isset($issue['element_id']) ? $issue['element_id'] : uniqid('rgaa_'));
                $issue_description = isset($issue['description']) ? $issue['description'] : '';
                $issue_location = isset($issue['element']) ? $issue['element'] : $url;
                $issue_priority = isset($issue['priority']) ? $issue['priority'] : 'medium';
                $issue_category = isset($issue['category']) ? $issue['category'] : 'rgaa';
                $issue_criterion = isset($issue['criterion']) ? $issue['criterion'] : '';
                $issue_solution = isset($issue['solution']) ? $issue['solution'] : (isset($issue['recommendation']) ? $issue['recommendation'] : '');
                
                // S'assurer que l'URL de la page est toujours incluse dans le résultat
                $formatted_issues[] = array(
                    'id' => $issue_id . '_' . md5($url), // Rendre l'ID unique par URL
                    'description' => $issue_description,
                    'location' => $issue_location,
                    'page_url' => $url, // Ajouter l'URL de la page
                    'priority' => $issue_priority,
                    'status' => 'open',
                    'category' => $issue_category,
                    'criterion' => $issue_criterion,
                    'solution' => $issue_solution,
                    'rule' => isset($issue['rule']) ? $issue['rule'] : '',
                    'path' => isset($issue['path']) ? $issue['path'] : null
                );
            }
            
            return $formatted_issues;
        } catch (Exception $e) {
            error_log('RGAA - Erreur lors de l\'audit: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Texte de recommandation d'une règle, tiré du catalogue
     *
     * @since    1.1.0
     * @param    string    $regle    Identifiant de la règle
     * @return   string
     */
    private function recommandation($regle) {
        $fiche = Audit_Accessibilite_Catalogue::regle($regle);
        if (!$fiche) {
            return '';
        }
        return implode(' ', $fiche['corriger']) . ' ' . $fiche['reflexe'];
    }

    /**
     * Renvoie les tests réussis et effectués, cumulés sur les pages auditées
     *
     * @since    1.1.0
     * @return   array     Tableau avec les clés passed et total
     */
    public function get_tests_counts() {
        return $this->tests_counts;
    }

    /**
     * Ancien calcul du score, conservé pour compatibilité
     *
     * @since    1.0.0
     * @return   int       Le nombre de critères RGAA testés
     */
    public function get_total_criteria_count() {
        try {
            // Nombre total de critères dans le RGAA 4.1
            // Nombre de critères testés pour l'instant
            return 5;
        } catch (Exception $e) {
            error_log('RGAA - Erreur dans get_total_criteria_count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Exécute un audit d'accessibilité RGAA complet sur toutes les pages accessibles
     *
     * @since    1.0.0
     * @return   array                Les résultats de l'audit
     */
    public function run_full_audit() {
        error_log('RGAA - Début de l\'audit complet du site');
        $home_url = home_url();
        $all_issues = array();
        
        // Récupérer toutes les URLs à analyser
        $urls = $this->get_site_urls($home_url);
        
        foreach ($urls as $url) {
            $page_issues = $this->run_audit($url);
            $all_issues = array_merge($all_issues, $page_issues);
        }
        
        return $all_issues;
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
        error_log('RGAA - Domaine du site : ' . $site_domain);
        
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
                    error_log('RGAA - Ajout URL de post : ' . $post_url);
                }
            }
        }
        
        // Ajouter les URLs des pages
        $pages = get_pages();
        foreach ($pages as $page) {
            $page_url = get_page_link($page->ID);
            if (!in_array($page_url, $to_visit) && !$this->is_url_excluded($page_url, $exclusion_patterns)) {
                $to_visit[] = $page_url;
                error_log('RGAA - Ajout URL de page : ' . $page_url);
            }
        }
        
        while (!empty($to_visit)) {
            $current_url = $this->normalize_crawl_url(array_shift($to_visit), $start_url);
            if ($current_url === false) {
                continue;
            }
            error_log('RGAA - Analyse de l\'URL : ' . $current_url);
            
            // Si l'URL a déjà été visitée, passer à la suivante
            if (in_array($current_url, $visited)) {
                error_log('RGAA - URL déjà visitée : ' . $current_url);
                continue;
            }
            
            // Vérifier si l'URL est exclue
            if ($this->is_url_excluded($current_url, $exclusion_patterns)) {
                error_log('RGAA - URL exclue : ' . $current_url);
                continue;
            }
            
            // Vérifier que l'URL appartient au même domaine
            $url_domain = parse_url($current_url, PHP_URL_HOST);
            if ($url_domain !== $site_domain) {
                error_log('RGAA - URL ignorée (domaine différent) : ' . $current_url);
                continue;
            }
            
            // Récupérer le contenu de la page
            $html = $this->get_page_content($current_url);
            if (!$html) {
                error_log('RGAA - Impossible de charger le contenu : ' . $current_url);
                continue;
            }
            
            // Ajouter l'URL à la liste des URLs à analyser
            $urls[] = $current_url;
            $visited[] = $current_url;
            error_log('RGAA - URL ajoutée aux résultats : ' . $current_url);
            
            // Extraire tous les liens de la page
            try {
                $dom = new DOMDocument();
                $dom->validateOnParse = true;
                @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
                $xpath = new DOMXPath($dom);
                
                // Récupérer tous les liens
                $links = $xpath->query('//a[@href]');
                foreach ($links as $link) {
                    // URL absolue, sans ancre, ni lien d'action, ni fichier (voir normalize_crawl_url)
                    $href = $this->normalize_crawl_url($link->getAttribute('href'), $current_url);

                    // Vérifier que l'URL est valide et appartient au même domaine
                    if ($href !== false && $this->is_valid_url($href) && parse_url($href, PHP_URL_HOST) === $site_domain) {
                        if (!in_array($href, $visited) && !in_array($href, $to_visit) && !$this->is_url_excluded($href, $exclusion_patterns)) {
                            $to_visit[] = $href;
                            error_log('RGAA - Nouvelle URL trouvée : ' . $href);
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('RGAA - Erreur lors de l\'extraction des liens : ' . $e->getMessage());
            }
        }
        
        error_log('RGAA - Total des URLs trouvées : ' . count($urls));
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
        $base_parts = parse_url($base);
        $origine = $base_parts['scheme'] . '://' . $base_parts['host'] . (isset($base_parts['port']) ? ':' . $base_parts['port'] : '');

        if (strpos($relative, '//') === 0) {
            return $base_parts['scheme'] . ':' . $relative;
        }

        if (strpos($relative, '/') === 0) {
            return $origine . $relative;
        }

        if (strpos($relative, '?') === 0) {
            $chemin = isset($base_parts['path']) ? $base_parts['path'] : '/';
            return $origine . $chemin . $relative;
        }

        // Chemin relatif : il se résout depuis le dossier de la page (tout ce qui précède le dernier « / »)
        $chemin = isset($base_parts['path']) ? $base_parts['path'] : '/';
        $dossier = substr($chemin, 0, strrpos($chemin, '/') + 1);
        return $origine . $dossier . $relative;
    }

    /**
     * Prépare une URL trouvée dans une page pour l'exploration : URL absolue, sans ancre.
     * Écarte les liens qui ne mènent pas à une page HTML (mailto:, tel:, fichiers)
     * et les liens d'action (ajout au panier, déconnexion…) qui modifieraient le site.
     *
     * @since    1.1.0
     * @param    string    $href    L'URL telle qu'écrite dans la page
     * @param    string    $base    L'URL de la page qui contient le lien
     * @return   string|false       L'URL à explorer, ou false si elle doit être ignorée
     */
    private function normalize_crawl_url($href, $base) {
        $href = trim($href);

        // Ancre seule, ou protocole qui n'est pas une page web
        if ($href === '' || $href[0] === '#' || preg_match('/^(mailto|tel|sms|javascript|data|ftp):/i', $href)) {
            return false;
        }

        if (!preg_match('#^https?://#i', $href)) {
            $href = $this->make_absolute_url($href, $base);
        }

        // L'ancre ne change pas la page
        $href = preg_replace('/#.*$/', '', $href);

        $parts = parse_url($href);
        if (!$parts || empty($parts['host'])) {
            return false;
        }
        $chemin = isset($parts['path']) && $parts['path'] !== '' ? $parts['path'] : '/';

        // Fichiers (images, documents, flux) et espaces techniques de WordPress
        if (preg_match('/\.(jpe?g|png|gif|webp|avif|svg|ico|pdf|zip|docx?|xlsx?|mp4|mp3|webm|css|js|json|xml|txt)$/i', $chemin)
            || preg_match('#/(wp-admin|wp-login\.php|wp-json|xmlrpc\.php|feed)(/|$)#', $chemin)) {
            return false;
        }

        // Liens d'action : les suivre ajouterait au panier, viderait le panier ou déconnecterait l'utilisateur
        if (isset($parts['query']) && preg_match('/(^|&)(add-to-cart|remove_item|undo_item|removed_item|_wpnonce|action|replytocom|customer-logout)=/', $parts['query'])) {
            return false;
        }

        // Une page du site n'est auditée qu'une fois, même liée en http et en https
        $scheme = $parts['host'] === parse_url(home_url(), PHP_URL_HOST) ? parse_url(home_url(), PHP_URL_SCHEME) : $parts['scheme'];

        return $scheme . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . $chemin
            . (isset($parts['query']) ? '?' . $parts['query'] : '');
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
        $response = audit_accessibilite_remote_get($url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        // Seules les pages HTML s'auditent (une image ou un PDF n'a ni titre ni langue)
        $type = wp_remote_retrieve_header($response, 'content-type');
        if ($type && stripos($type, 'text/html') === false) {
            return false;
        }

        $html = wp_remote_retrieve_body($response);

        if (empty($html)) {
            return false;
        }

        return $html;
    }

    /**
     * Indique si un attribut est présent, y compris quand l'API Interactivity de WordPress
     * le remplit dans le navigateur (data-wp-bind--alt, data-wp-bind--aria-label…)
     *
     * @since    1.1.0
     * @param    DOMElement    $element    L'élément
     * @param    string        $attribut   Le nom de l'attribut
     * @return   boolean
     */
    private function has_attribute_or_binding($element, $attribut) {
        return $element->hasAttribute($attribut) || $element->hasAttribute('data-wp-bind--' . $attribut);
    }

    /**
     * Indique si un élément fait partie d'un gabarit inerte (balise template),
     * dont le contenu n'est pas affiché tel quel
     *
     * @since    1.1.0
     * @param    DOMXPath      $xpath      L'objet XPath pour les requêtes
     * @param    DOMElement    $element    L'élément
     * @return   boolean
     */
    private function is_in_template($xpath, $element) {
        return $xpath->query('ancestor::template', $element)->length > 0;
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
    private function check_images_alt($dom, $xpath, &$issues, &$tests_passed, &$tests_total, $page_url = '') {
        $images = $xpath->query('//img');
        
        foreach ($images as $image) {
            if ($this->is_in_template($xpath, $image)) {
                continue;
            }
            $tests_total++;
            
            $alt = trim($image->getAttribute('alt'));
            $src = $image->getAttribute('src');
            $fichier = basename(parse_url($src, PHP_URL_PATH));
            $element_id = !empty($image->getAttribute('id')) ? $image->getAttribute('id') : md5($src);

            // Image ignorée des technologies d'assistance : rien à vérifier
            $decorative = $image->getAttribute('aria-hidden') === 'true'
                || in_array($image->getAttribute('role'), array('presentation', 'none'), true);

            if (!$decorative && !$this->has_attribute_or_binding($image, 'alt') && !$this->has_attribute_or_binding($image, 'aria-label') && !$image->hasAttribute('aria-labelledby')) {
                // alt absent (un alt vide est correct pour une image décorative)
                $issues[] = array(
                    'theme' => 1,
                    'criterion' => 1.1,
                    'element_id' => $element_id,
                    'element_type' => 'img',
                    'description' => sprintf(__('L\'image « %s » n\'a pas d\'alternative textuelle (attribut alt absent).', 'audit-accessibilite'), $fichier),
                    'priority' => 'high',
                    'recommendation' => $this->recommandation('image-alt-absent'),
                    'rule' => 'image-alt-absent',
                    'path' => $image->getNodePath(),
                    'page_url' => $page_url // Ajouter l'URL de la page
                );
            } else if (!$decorative && $alt !== '' && (strcasecmp($alt, $fichier) === 0 || preg_match('/\.(jpe?g|png|gif|webp|avif|svg)$/i', $alt))) {
                // alt rempli avec le nom du fichier : présent mais non pertinent
                $issues[] = array(
                    'theme' => 1,
                    'criterion' => 1.3,
                    'element_id' => $element_id,
                    'element_type' => 'img',
                    'description' => sprintf(__('L\'alternative de l\'image « %s » reprend le nom du fichier.', 'audit-accessibilite'), $fichier),
                    'priority' => 'medium',
                    'recommendation' => $this->recommandation('image-alt-fichier'),
                    'rule' => 'image-alt-fichier',
                    'path' => $image->getNodePath(),
                    'page_url' => $page_url
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
    private function check_contrast($dom, $xpath, &$issues, &$tests_passed, &$tests_total, $page_url = '') {
        $tests_total++;
        
        // Pour cette version, simple avertissement
        // TODO: Ajouter une vérification plus poussée
        $issues[] = array(
            'theme' => 3,
            'criterion' => 3.1,
            'element_id' => 'contrast',
            'element_type' => 'general',
            'description' => __('Vérification manuelle requise : contraste des couleurs', 'audit-accessibilite'),
            'priority' => 'medium',
            'recommendation' => __('Vérifier que le contraste entre la couleur du texte et celle de son arrière-plan est suffisamment élevé (4.5:1 pour le texte normal, 3:1 pour le texte de grande taille).', 'audit-accessibilite'),
            'page_url' => $page_url // Ajouter l'URL de la page
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
    private function check_links_text($dom, $xpath, &$issues, &$tests_passed, &$tests_total, $page_url = '') {
        $links = $xpath->query('//a[@href]');

        // Intitulés qui ne disent rien de la destination une fois sortis de leur contexte
        $generiques = array('ici', 'cliquez', 'cliquez ici', 'en savoir plus', 'lire la suite', 'lire plus', 'voir plus', 'voir', 'plus', 'suite', 'lien', 'click', 'click here', 'here', 'read more', 'more');

        foreach ($links as $link) {
            // Lien masqué aux technologies d'assistance : non concerné
            if ($link->getAttribute('aria-hidden') === 'true' || $this->is_in_template($xpath, $link)) {
                continue;
            }
            $tests_total++;

            $href = $link->getAttribute('href');
            $text = $this->get_accessible_name($xpath, $link);
            $element_id = !empty($link->getAttribute('id')) ? $link->getAttribute('id') : md5($href . $text);
            $normalise = mb_strtolower(trim($text, " \t\n\r\0\x0B.…›»→"));

            if ($text === '' && !$link->hasAttribute('data-wp-bind--aria-label')) {
                $issues[] = array(
                    'theme' => 6,
                    'criterion' => 6.2,
                    'element_id' => $element_id,
                    'element_type' => 'a',
                    'description' => sprintf(__('Le lien vers « %s » n\'a pas d\'intitulé.', 'audit-accessibilite'), $href),
                    'priority' => 'high',
                    'recommendation' => $this->recommandation('lien-sans-intitule'),
                    'rule' => 'lien-sans-intitule',
                    'path' => $link->getNodePath(),
                    'page_url' => $page_url
                );
            } else if (in_array($normalise, $generiques, true)) {
                $issues[] = array(
                    'theme' => 6,
                    'criterion' => 6.1,
                    'element_id' => $element_id,
                    'element_type' => 'a',
                    'description' => sprintf(__('Le lien « %1$s » (vers %2$s) n\'est pas explicite hors de son contexte.', 'audit-accessibilite'), $text, $href),
                    'priority' => 'medium',
                    'recommendation' => $this->recommandation('lien-generique'),
                    'rule' => 'lien-generique',
                    'path' => $link->getNodePath(),
                    'page_url' => $page_url // Ajouter l'URL de la page
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
    private function check_document_title($dom, $xpath, &$issues, &$tests_passed, &$tests_total, $page_url = '') {
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
                'recommendation' => $this->recommandation('titre-document-absent'),
                    'rule' => 'titre-document-absent',
                    'path' => null,
                'page_url' => $page_url // Ajouter l'URL de la page
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
                    'recommendation' => $this->recommandation('titre-document-court'),
                    'rule' => 'titre-document-court',
                    'path' => null,
                    'page_url' => $page_url // Ajouter l'URL de la page
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
    private function check_page_language($dom, $xpath, &$issues, &$tests_passed, &$tests_total, $page_url = '') {
        $tests_total++;
        
        $html = $dom->getElementsByTagName('html')->item(0);
        
        if (!$html) {
            $issues[] = array(
                'theme' => 8,
                'criterion' => 8.3,
                'element_id' => 'html_tag',
                'element_type' => 'html',
                'description' => __('La balise HTML est manquante.', 'audit-accessibilite'),
                'priority' => 'high',
                'recommendation' => $this->recommandation('html-absent'),
                    'rule' => 'html-absent',
                    'path' => null,
                'page_url' => $page_url
            );
            return;
        }
        
        if (!$html->hasAttribute('lang')) {
            $issues[] = array(
                'theme' => 8,
                'criterion' => 8.3,
                'element_id' => 'html_lang',
                'element_type' => 'html',
                'description' => __('La langue de la page n\'est pas définie.', 'audit-accessibilite'),
                'priority' => 'high',
                'recommendation' => $this->recommandation('langue-absente'),
                    'rule' => 'langue-absente',
                    'path' => null,
                'page_url' => $page_url
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
                    'recommendation' => $this->recommandation('langue-vide'),
                    'rule' => 'langue-vide',
                    'path' => null,
                    'page_url' => $page_url
                );
            } else if (!preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})*$/', $lang)) {
                // « fr » comme « fr-FR » sont valides (RGAA 8.4) : seul un code mal formé est signalé
                $issues[] = array(
                    'theme' => 8,
                    'criterion' => 8.4,
                    'element_id' => 'html_lang',
                    'element_type' => 'html',
                    'description' => sprintf(__('Le code de langue « %s » n\'est pas valide.', 'audit-accessibilite'), $lang),
                    'priority' => 'medium',
                    'recommendation' => $this->recommandation('langue-invalide'),
                    'rule' => 'langue-invalide',
                    'path' => null,
                    'page_url' => $page_url
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
    private function check_headings_structure($dom, $xpath, &$issues, &$tests_passed, &$tests_total, $page_url = '') {
        $tests_total++;
        $issues_before = count($issues);

        // Titres dans l'ordre du document (une requête par niveau les classerait par niveau)
        $headings = array();
        foreach ($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $h) {
            $level = (int) substr($h->nodeName, 1);
            $headings[] = array(
                'level' => $level,
                'text' => trim($h->textContent),
                'node' => $h,
                'id' => !empty($h->getAttribute('id')) ? $h->getAttribute('id') : md5($level . $h->textContent)
            );
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
                'recommendation' => $this->recommandation('titres-absents'),
                    'rule' => 'titres-absents',
                    'path' => null,
                'page_url' => $page_url // Ajouter l'URL de la page
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
                'recommendation' => $this->recommandation('h1-absent'),
                    'rule' => 'h1-absent',
                    'path' => null,
                'page_url' => $page_url // Ajouter l'URL de la page
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
                    'description' => sprintf(__('Saut dans la hiérarchie des titres : passage de h%1$d à h%2$d (« %3$s »).', 'audit-accessibilite'), $previous_level, $heading['level'], wp_trim_words($heading['text'], 8)),
                    'priority' => 'medium',
                    'recommendation' => $this->recommandation('titres-saut'),
                    'rule' => 'titres-saut',
                    'path' => $heading['node']->getNodePath(),
                    'page_url' => $page_url // Ajouter l'URL de la page
                );
            }
            $previous_level = $heading['level'];
        }
        
        // Si aucun problème trouvé sur cette page, test réussi
        if (count($issues) === $issues_before) {
            $tests_passed++;
        }
    }

    /**
     * Vérifie que chaque champ de formulaire a une étiquette (RGAA 11.1)
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_forms($dom, $xpath, &$issues, &$tests_passed, &$tests_total, $page_url = '') {
        // Champs de saisie, hors champs cachés et boutons (qui ont leur propre intitulé)
        $fields = $xpath->query('//input[not(@type="hidden" or @type="submit" or @type="button" or @type="reset" or @type="image")]|//select|//textarea');

        foreach ($fields as $field) {
            if ($field->getAttribute('aria-hidden') === 'true' || $field->getAttribute('tabindex') === '-1' || $this->is_in_template($xpath, $field)) {
                continue;
            }
            $tests_total++;

            $nom = $field->getAttribute('name') ?: $field->getAttribute('id') ?: $field->nodeName;
            $element_id = !empty($field->getAttribute('id')) ? $field->getAttribute('id') : md5($nom);

            if ($this->get_field_label($xpath, $field) === '' && !$field->hasAttribute('data-wp-bind--aria-label')) {
                $placeholder = $field->getAttribute('placeholder');
                $issues[] = array(
                    'theme' => 11,
                    'criterion' => 11.1,
                    'element_id' => $element_id,
                    'element_type' => $field->nodeName,
                    'description' => $placeholder !== ''
                        ? sprintf(__('Le champ « %1$s » n\'a pas d\'étiquette : seul son texte indicatif (« %2$s ») le décrit.', 'audit-accessibilite'), $nom, $placeholder)
                        : sprintf(__('Le champ « %s » n\'a pas d\'étiquette.', 'audit-accessibilite'), $nom),
                    'priority' => 'high',
                    'recommendation' => $this->recommandation('champ-sans-etiquette'),
                    'rule' => 'champ-sans-etiquette',
                    'path' => $field->getNodePath(),
                    'page_url' => $page_url // Ajouter l'URL de la page
                );
            } else {
                $tests_passed++;
            }
        }
    }

    /**
     * Calcule le nom accessible d'un élément : aria-labelledby, aria-label,
     * puis texte et alternatives des images qu'il contient
     *
     * @since    1.1.0
     * @param    DOMXPath      $xpath    L'objet XPath pour les requêtes
     * @param    DOMElement    $element  L'élément à nommer
     * @return   string                  Le nom accessible, chaîne vide s'il n'y en a pas
     */
    private function get_accessible_name($xpath, $element) {
        if ($element->hasAttribute('aria-labelledby')) {
            $textes = array();
            foreach (preg_split('/\s+/', trim($element->getAttribute('aria-labelledby'))) as $id) {
                $cible = $xpath->query('//*[@id="' . $id . '"]')->item(0);
                if ($cible) {
                    $textes[] = trim($cible->textContent);
                }
            }
            $nom = trim(implode(' ', $textes));
            if ($nom !== '') {
                return $nom;
            }
        }

        if (trim($element->getAttribute('aria-label')) !== '') {
            return trim($element->getAttribute('aria-label'));
        }

        $texte = trim(preg_replace('/\s+/u', ' ', $element->textContent));
        foreach ($xpath->query('.//img[@alt]', $element) as $image) {
            $texte .= ' ' . trim($image->getAttribute('alt'));
        }

        return trim($texte);
    }

    /**
     * Retrouve l'étiquette d'un champ de formulaire : aria-labelledby, aria-label,
     * label associé par for, label parent, puis attribut title
     *
     * @since    1.1.0
     * @param    DOMXPath      $xpath    L'objet XPath pour les requêtes
     * @param    DOMElement    $field    Le champ
     * @return   string                  L'étiquette, chaîne vide s'il n'y en a pas
     */
    private function get_field_label($xpath, $field) {
        if ($field->hasAttribute('aria-labelledby') || $field->hasAttribute('aria-label')) {
            $nom = $this->get_accessible_name($xpath, $field);
            // Pour un champ, le texte contenu (options d'une liste) n'est pas une étiquette
            if ($nom !== '' && $nom !== trim(preg_replace('/\s+/u', ' ', $field->textContent))) {
                return $nom;
            }
        }

        $id = $field->getAttribute('id');
        if ($id !== '') {
            $label = $xpath->query('//label[@for="' . $id . '"]')->item(0);
            if ($label && trim($label->textContent) !== '') {
                return trim($label->textContent);
            }
        }

        $parent = $field->parentNode;
        while ($parent && $parent instanceof DOMElement) {
            if ($parent->nodeName === 'label' && trim($parent->textContent) !== '') {
                return trim($parent->textContent);
            }
            $parent = $parent->parentNode;
        }

        return trim($field->getAttribute('title'));
    }

    /**
     * Vérifie que les tableaux de données déclarent leurs en-têtes (RGAA 5.6)
     *
     * @since    1.0.0
     * @param    DOMDocument    $dom           L'objet DOM de la page
     * @param    DOMXPath       $xpath         L'objet XPath pour les requêtes
     * @param    array          &$issues       Référence au tableau des problèmes
     * @param    int            &$tests_passed Référence au compteur de tests réussis
     * @param    int            &$tests_total  Référence au compteur de tests total
     */
    private function check_tables($dom, $xpath, &$issues, &$tests_passed, &$tests_total, $page_url = '') {
        // Tableaux de données : un tableau de mise en forme se déclare avec role="presentation"
        $tables = $xpath->query('//table[not(@role="presentation" or @role="none")]');

        foreach ($tables as $table) {
            $tests_total++;

            $element_id = !empty($table->getAttribute('id')) ? $table->getAttribute('id') : md5($table->C14N());
            $en_tetes = $xpath->query('.//th|.//*[@role="columnheader" or @role="rowheader"]', $table)->length;

            if ($en_tetes === 0) {
                $legende = $xpath->query('.//caption', $table)->item(0);
                $issues[] = array(
                    'theme' => 5,
                    'criterion' => 5.6,
                    'element_id' => $element_id,
                    'element_type' => 'table',
                    'description' => $legende
                        ? sprintf(__('Le tableau « %s » n\'a aucun en-tête de colonne ou de ligne (balise th).', 'audit-accessibilite'), trim($legende->textContent))
                        : __('Ce tableau de données n\'a aucun en-tête de colonne ou de ligne (balise th).', 'audit-accessibilite'),
                    'priority' => 'medium',
                    'recommendation' => $this->recommandation('tableau-sans-en-tetes'),
                    'rule' => 'tableau-sans-en-tetes',
                    'path' => $table->getNodePath(),
                    'page_url' => $page_url // Ajouter l'URL de la page
                );
            } else {
                $tests_passed++;
            }
        }
    }
}
