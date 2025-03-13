<?php
/**
 * Classe responsable de la gestion des requêtes AJAX
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Audit_Accessibilite
 * @subpackage Audit_Accessibilite/includes
 */

/**
 * Classe responsable de la gestion des requêtes AJAX
 *
 * Cette classe gère toutes les requêtes AJAX utilisées par l'extension
 * pour les interactions asynchrones avec le serveur.
 *
 * @package    Audit_Accessibilite
 * @subpackage Audit_Accessibilite/includes
 * @author     Votre Nom <email@example.com>
 */
class Audit_Accessibilite_Ajax {

    /**
     * Le loader qui est responsable de maintenir et d'enregistrer tous les hooks de l'extension.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_Loader    $loader    Maintient et enregistre tous les hooks pour l'extension.
     */
    protected $loader;

    /**
     * Le nom unique de cette extension.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    Le nom de l'extension.
     */
    protected $plugin_name;

    /**
     * La version actuelle de l'extension.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    La version actuelle de l'extension.
     */
    protected $version;

    /**
     * Instance de la classe RGAA pour les audits d'accessibilité.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_RGAA    $rgaa    Instance de la classe RGAA.
     */
    protected $rgaa;

    /**
     * Instance de la classe SEO pour les audits SEO.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_SEO    $seo    Instance de la classe SEO.
     */
    protected $seo;

    /**
     * Instance de la classe Performance pour les audits de performance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_Performance    $performance    Instance de la classe Performance.
     */
    protected $performance;

    /**
     * Instance de la classe Security pour les audits de sécurité.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_Security    $security    Instance de la classe Security.
     */
    protected $security;

    /**
     * Instance de la classe Report pour la génération de rapports.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Audit_Accessibilite_Report    $report    Instance de la classe Report.
     */
    protected $report;

    /**
     * Initialise la classe et définit ses propriétés.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       Le nom de l'extension.
     * @param    string    $version           La version de l'extension.
     * @param    Audit_Accessibilite_Loader    $loader    Le loader de l'extension.
     * @param    Audit_Accessibilite_RGAA    $rgaa    Instance de la classe RGAA.
     * @param    Audit_Accessibilite_SEO    $seo    Instance de la classe SEO.
     * @param    Audit_Accessibilite_Performance    $performance    Instance de la classe Performance.
     * @param    Audit_Accessibilite_Security    $security    Instance de la classe Security.
     * @param    Audit_Accessibilite_Report    $report    Instance de la classe Report.
     */
    public function __construct( $plugin_name, $version, $loader, $rgaa, $seo, $performance, $security, $report ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->loader = $loader;
        $this->rgaa = $rgaa;
        $this->seo = $seo;
        $this->performance = $performance;
        $this->security = $security;
        $this->report = $report;

        $this->define_ajax_hooks();
    }

    /**
     * Enregistre tous les hooks liés aux requêtes AJAX.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_ajax_hooks() {
        // Actions pour les utilisateurs connectés
        $this->loader->add_action( 'wp_ajax_start_accessibility_audit', $this, 'start_accessibility_audit' );
        $this->loader->add_action( 'wp_ajax_run_accessibility_audit', $this, 'run_accessibility_audit' );
        $this->loader->add_action( 'wp_ajax_export_accessibility_audit_pdf', $this, 'export_accessibility_audit_pdf' );
        $this->loader->add_action( 'wp_ajax_export_accessibility_audit_csv', $this, 'export_accessibility_audit_csv' );
        $this->loader->add_action( 'wp_ajax_get_accessibility_audit_page', $this, 'get_accessibility_audit_page' );
        $this->loader->add_action( 'wp_ajax_fix_accessibility_issue', $this, 'fix_accessibility_issue' );
        $this->loader->add_action( 'wp_ajax_ignore_accessibility_issue', $this, 'ignore_accessibility_issue' );
        $this->loader->add_action( 'wp_ajax_get_audit_statistics', $this, 'get_audit_statistics' );
    }

    /**
     * Vérifie le nonce pour la sécurité.
     *
     * @since    1.0.0
     * @access   private
     * @return   bool    True si le nonce est valide, false sinon.
     */
    private function verify_nonce() {
        // Pour le débogage
        error_log('Vérification du nonce: ' . (isset($_POST['nonce']) ? $_POST['nonce'] : 'non défini'));
        
        if ( ! isset( $_POST['nonce'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Erreur de sécurité: nonce manquant. Veuillez rafraîchir la page et réessayer.', 'audit-accessibilite' ) ) );
            return false;
        }
        
        // Vérifier le nonce avec la bonne action
        $is_valid = wp_verify_nonce( $_POST['nonce'], 'audit_accessibilite_new_audit' );
        
        if ( ! $is_valid ) {
            wp_send_json_error( array( 'message' => __( 'Erreur de sécurité: nonce invalide. Veuillez rafraîchir la page et réessayer.', 'audit-accessibilite' ) ) );
            return false;
        }
        
        return true;
    }

    /**
     * Vérifie les capacités de l'utilisateur.
     *
     * @since    1.0.0
     * @access   private
     * @return   bool    True si l'utilisateur a les capacités requises, false sinon.
     */
    private function check_capabilities() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Vous n\'avez pas les droits nécessaires pour effectuer cette action.', 'audit-accessibilite' ) ) );
            return false;
        }
        return true;
    }

    /**
     * Lance un audit d'accessibilité complet.
     *
     * @since    1.0.0
     */
    public function start_accessibility_audit() {
        // Vérifier le nonce et les capacités
        if ( ! $this->verify_nonce() || ! $this->check_capabilities() ) {
            return;
        }

        // Lancer l'audit d'accessibilité
        $results = $this->rgaa->run_full_audit();

        // Sauvegarder les résultats dans la base de données
        update_option( 'audit_accessibilite_last_audit', array(
            'date' => current_time( 'mysql' ),
            'results' => $results,
        ) );

        // Préparer les données pour la réponse
        $response_data = $this->prepare_audit_data( $results );

        // Envoyer la réponse
        wp_send_json_success( $response_data );
    }

    /**
     * Lance un audit d'accessibilité à partir du formulaire d'audit.
     *
     * @since    1.0.0
     */
    public function run_accessibility_audit() {
        // Activer la journalisation des erreurs pour le débogage
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        
        try {
            // Vérifier le nonce et les capacités
            if ( ! $this->verify_nonce() || ! $this->check_capabilities() ) {
                return;
            }
            
            // S'assurer que form_data est défini
            if ( empty( $_POST['form_data'] ) ) {
                wp_send_json_error( array( 'message' => __( 'Données du formulaire manquantes.', 'audit-accessibilite' ) ) );
                return;
            }

            // Récupérer les données du formulaire
            $form_data = array();
            parse_str( $_POST['form_data'], $form_data );
            
            // Vérifier les paramètres obligatoires
            if ( empty( $form_data['audit_target'] ) ) {
                wp_send_json_error( array( 'message' => __( 'Veuillez spécifier une cible d\'audit.', 'audit-accessibilite' ) ) );
                return;
            }

            // Initialiser le tableau des résultats
            $all_results = array();
            $target_url = '';
            
            // Déterminer l'URL cible
            if ( $form_data['audit_target'] === 'site' ) {
                $target_url = home_url();
            } elseif ( $form_data['audit_target'] === 'page' && !empty( $form_data['specific_url'] ) ) {
                $target_url = esc_url_raw( $form_data['specific_url'] );
            } else {
                // Si la cible est "page" mais l'URL est vide, utiliser l'URL du site
                if ( $form_data['audit_target'] === 'page' ) {
                    $target_url = home_url();
                } else {
                    wp_send_json_error( array( 'message' => __( 'URL cible invalide.', 'audit-accessibilite' ) ) );
                    return;
                }
            }

            // Configurer les paramètres d'audit
            $audit_config = array(
                'max_pages' => isset( $form_data['max_pages'] ) ? intval( $form_data['max_pages'] ) : 10,
                'crawl_depth' => isset( $form_data['crawl_depth'] ) ? intval( $form_data['crawl_depth'] ) : 2,
                'save_report' => isset( $form_data['save_report'] ) && $form_data['save_report'] == 1,
                'email_report' => isset( $form_data['email_report'] ) && $form_data['email_report'] == 1,
                'email_address' => isset( $form_data['email_address'] ) ? sanitize_email( $form_data['email_address'] ) : '',
            );

            // Vérifier au moins un critère est sélectionné
            $has_criteria = false;
            $criteria_types = array('check_rgaa', 'check_seo', 'check_performance', 'check_security');
            
            foreach ($criteria_types as $type) {
                if (isset($form_data[$type]) && $form_data[$type] == 1) {
                    $has_criteria = true;
                    break;
                }
            }
            
            if (!$has_criteria) {
                wp_send_json_error(array(
                    'message' => __('Veuillez sélectionner au moins un type d\'audit à exécuter.', 'audit-accessibilite')
                ));
                return;
            }

            // Exécuter les audits sélectionnés
            if ( isset( $form_data['check_rgaa'] ) && $form_data['check_rgaa'] == 1 ) {
                try {
                    // Si l'audit concerne le site entier, utiliser run_full_audit
                    if ($form_data['audit_target'] === 'site') {
                        $rgaa_results = $this->rgaa->run_full_audit($audit_config);
                    } else {
                        $rgaa_results = $this->rgaa->run_audit($target_url, $audit_config);
                    }
                    $all_results['rgaa_issues'] = $rgaa_results;
                } catch (Exception $e) {
                    error_log('Erreur lors de l\'audit RGAA: ' . $e->getMessage());
                    $all_results['rgaa_issues'] = array();
                }
            }

            if ( isset( $form_data['check_seo'] ) && $form_data['check_seo'] == 1 ) {
                try {
                    // Si l'audit concerne le site entier, utiliser run_full_audit pour SEO également
                    if ($form_data['audit_target'] === 'site') {
                        $seo_results = $this->seo->run_full_audit($audit_config);
                    } else {
                        $seo_results = $this->seo->run_audit($target_url, $audit_config);
                    }
                    $all_results['seo_issues'] = $seo_results;
                } catch (Exception $e) {
                    error_log('Erreur lors de l\'audit SEO: ' . $e->getMessage());
                    $all_results['seo_issues'] = array();
                }
            }

            if ( isset( $form_data['check_performance'] ) && $form_data['check_performance'] == 1 ) {
                try {
                    $performance_results = $this->performance->run_audit( $target_url, $audit_config );
                    $all_results['performance_issues'] = $performance_results;
                } catch (Exception $e) {
                    error_log('Erreur lors de l\'audit Performance: ' . $e->getMessage());
                    $all_results['performance_issues'] = array();
                }
            }

            if ( isset( $form_data['check_security'] ) && $form_data['check_security'] == 1 ) {
                try {
                    $security_results = $this->security->run_audit( $target_url, $audit_config );
                    $all_results['security_issues'] = $security_results;
                } catch (Exception $e) {
                    error_log('Erreur lors de l\'audit Sécurité: ' . $e->getMessage());
                    $all_results['security_issues'] = array();
                }
            }

            // Si aucun audit n'a renvoyé de résultats
            if ( empty( $all_results ) ) {
                wp_send_json_error( array( 'message' => __( 'Aucun résultat d\'audit n\'a pu être généré.', 'audit-accessibilite' ) ) );
                return;
            }

            // Calculer des statistiques globales
            $total_issues = 0;
            $open_issues = 0;
            $high_priority = 0;
            
            foreach ( $all_results as $type => $issues ) {
                if (!is_array($issues)) {
                    continue; // Ignorer les résultats non valides
                }
                
                foreach ( $issues as $issue ) {
                    $total_issues++;
                    if ( isset($issue['status']) && $issue['status'] === 'open' ) {
                        $open_issues++;
                    }
                    if ( isset($issue['priority']) && $issue['priority'] === 'high' ) {
                        $high_priority++;
                    }
                }
            }

            // Calculer un score global (pourcentage de conformité)
            $score = 0;
            $tests_total = 0;
            $tests_passed = 0;
            
            if ( isset( $all_results['rgaa_issues'] ) ) {
                try {
                    $total_rgaa_criteria = $this->rgaa->get_total_criteria_count();
                    $failed_criteria = is_array($all_results['rgaa_issues']) ? count( $all_results['rgaa_issues'] ) : 0;
                    $passed_criteria = $total_rgaa_criteria - $failed_criteria;
                    
                    $tests_total += $total_rgaa_criteria;
                    $tests_passed += $passed_criteria;
                } catch (Exception $e) {
                    error_log('Erreur lors du calcul du score RGAA: ' . $e->getMessage());
                }
            }
            
            if ( $tests_total > 0 ) {
                $score = round( ( $tests_passed / $tests_total ) * 100 );
            }

            // Préparer les données pour la réponse
            $response_data = array_merge( $all_results, array(
                'total_issues' => $total_issues,
                'open_issues' => $open_issues,
                'high_priority' => $high_priority,
                'score' => $score,
                'tests_total' => $tests_total,
                'tests_passed' => $tests_passed,
                'target_url' => $target_url,
                'audit_date' => current_time( 'mysql' ),
            ));

            // Sauvegarder l'audit dans la base de données si demandé
            if ( $audit_config['save_report'] ) {
                try {
                    $audit_id = $this->save_audit_results( $target_url, $response_data );
                    $response_data['audit_id'] = $audit_id;
                } catch (Exception $e) {
                    error_log('Erreur lors de la sauvegarde des résultats: ' . $e->getMessage());
                }
            }

            // Envoyer le rapport par email si demandé
            if ( $audit_config['email_report'] && !empty( $audit_config['email_address'] ) ) {
                try {
                    $this->report->send_email_report( $audit_config['email_address'], $response_data );
                } catch (Exception $e) {
                    error_log('Erreur lors de l\'envoi du rapport par email: ' . $e->getMessage());
                }
            }

            // Envoyer la réponse
            wp_send_json_success( $response_data );
            
        } catch (Exception $e) {
            error_log('Exception non gérée dans run_accessibility_audit: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Une erreur inattendue s\'est produite lors de l\'audit.', 'audit-accessibilite') . ' ' . $e->getMessage()
            ));
        }
    }

    /**
     * Sauvegarde les résultats d'un audit dans la base de données.
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $target_url    L'URL cible de l'audit.
     * @param    array     $results       Les résultats de l'audit.
     * @return   int       L'ID de l'enregistrement d'audit.
     */
    private function save_audit_results( $target_url, $results ) {
        global $wpdb;
        
        // Nom de la table pour les audits
        $table_name = $wpdb->prefix . 'audit_accessibilite_audits';
        
        // Créer la table si elle n'existe pas
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                audit_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                target_url varchar(255) NOT NULL,
                audit_type varchar(50) NOT NULL,
                summary longtext NOT NULL,
                results longtext NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
        
        // Préparer un résumé des résultats
        $summary = array(
            'total_issues' => $results['total_issues'],
            'open_issues' => $results['open_issues'],
            'high_priority' => $results['high_priority'],
            'score' => $results['score'],
            'tests_total' => isset( $results['tests_total'] ) ? $results['tests_total'] : 0,
            'tests_passed' => isset( $results['tests_passed'] ) ? $results['tests_passed'] : 0,
        );
        
        // Insérer l'enregistrement d'audit
        $wpdb->insert(
            $table_name,
            array(
                'audit_date' => current_time( 'mysql' ),
                'target_url' => $target_url,
                'audit_type' => 'manual',
                'summary' => json_encode( $summary ),
                'results' => json_encode( $results ),
            )
        );
        
        // Mettre à jour l'option de date du dernier audit
        $options = get_option( 'audit_accessibilite_options', array() );
        $options['last_audit_date'] = current_time( 'mysql' );
        update_option( 'audit_accessibilite_options', $options );
        
        return $wpdb->insert_id;
    }

    /**
     * Prépare les données d'audit pour l'affichage.
     *
     * @since    1.0.0
     * @access   private
     * @param    array    $results    Les résultats de l'audit.
     * @return   array    Les données préparées pour l'affichage.
     */
    private function prepare_audit_data( $results ) {
        // Calculer les statistiques
        $total_issues = count( $results );
        $open_issues = 0;
        $high_priority = 0;
        
        foreach ( $results as $issue ) {
            if ( $issue['status'] === 'open' ) {
                $open_issues++;
            }
            if ( $issue['priority'] === 'high' ) {
                $high_priority++;
            }
        }

        // Préparer les données pour les graphiques
        $chart_data = array(
            'by_priority' => array(
                'high' => $high_priority,
                'medium' => count( array_filter( $results, function( $issue ) {
                    return $issue['priority'] === 'medium';
                } ) ),
                'low' => count( array_filter( $results, function( $issue ) {
                    return $issue['priority'] === 'low';
                } ) ),
            ),
            'by_category' => array(
                'images' => count( array_filter( $results, function( $issue ) {
                    return strpos( $issue['category'], 'image' ) !== false;
                } ) ),
                'navigation' => count( array_filter( $results, function( $issue ) {
                    return strpos( $issue['category'], 'navigation' ) !== false;
                } ) ),
                'forms' => count( array_filter( $results, function( $issue ) {
                    return strpos( $issue['category'], 'form' ) !== false;
                } ) ),
                'structure' => count( array_filter( $results, function( $issue ) {
                    return strpos( $issue['category'], 'structure' ) !== false;
                } ) ),
                'other' => count( array_filter( $results, function( $issue ) {
                    return strpos( $issue['category'], 'image' ) === false &&
                           strpos( $issue['category'], 'navigation' ) === false &&
                           strpos( $issue['category'], 'form' ) === false &&
                           strpos( $issue['category'], 'structure' ) === false;
                } ) ),
            ),
        );

        // Retourner les données préparées
        return array(
            'total_issues' => $total_issues,
            'open_issues' => $open_issues,
            'high_priority' => $high_priority,
            'issues' => array_slice( $results, 0, 10 ), // Limiter à 10 résultats pour la première page
            'chart_data' => $chart_data,
        );
    }

    /**
     * Exporte les résultats d'audit au format PDF.
     *
     * @since    1.0.0
     */
    public function export_accessibility_audit_pdf() {
        // Vérifier le nonce et les capacités
        if ( ! $this->verify_nonce() || ! $this->check_capabilities() ) {
            return;
        }

        // Récupérer les résultats du dernier audit
        $last_audit = get_option( 'audit_accessibilite_last_audit', array() );
        
        if ( empty( $last_audit ) || ! isset( $last_audit['results'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Aucun audit disponible. Veuillez d\'abord lancer un audit.', 'audit-accessibilite' ) ) );
            return;
        }

        // Générer le rapport PDF
        $pdf_path = $this->report->generate_pdf_report( $last_audit['results'] );
        
        if ( ! $pdf_path ) {
            wp_send_json_error( array( 'message' => __( 'Erreur lors de la génération du rapport PDF.', 'audit-accessibilite' ) ) );
            return;
        }

        // Envoyer l'URL de téléchargement
        wp_send_json_success( array(
            'download_url' => $pdf_path,
        ) );
    }

    /**
     * Exporte les résultats d'audit au format CSV.
     *
     * @since    1.0.0
     */
    public function export_accessibility_audit_csv() {
        // Vérifier le nonce et les capacités
        if ( ! $this->verify_nonce() || ! $this->check_capabilities() ) {
            return;
        }

        // Récupérer les résultats du dernier audit
        $last_audit = get_option( 'audit_accessibilite_last_audit', array() );
        
        if ( empty( $last_audit ) || ! isset( $last_audit['results'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Aucun audit disponible. Veuillez d\'abord lancer un audit.', 'audit-accessibilite' ) ) );
            return;
        }

        // Générer le rapport CSV
        $csv_path = $this->report->generate_csv_report( $last_audit['results'] );
        
        if ( ! $csv_path ) {
            wp_send_json_error( array( 'message' => __( 'Erreur lors de la génération du rapport CSV.', 'audit-accessibilite' ) ) );
            return;
        }

        // Envoyer l'URL de téléchargement
        wp_send_json_success( array(
            'download_url' => $csv_path,
        ) );
    }

    /**
     * Récupère une page spécifique des résultats d'audit.
     *
     * @since    1.0.0
     */
    public function get_accessibility_audit_page() {
        // Vérifier le nonce et les capacités
        if ( ! $this->verify_nonce() || ! $this->check_capabilities() ) {
            return;
        }

        // Récupérer le numéro de page
        $page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        $per_page = 10;
        $offset = ( $page - 1 ) * $per_page;

        // Récupérer les résultats du dernier audit
        $last_audit = get_option( 'audit_accessibilite_last_audit', array() );
        
        if ( empty( $last_audit ) || ! isset( $last_audit['results'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Aucun audit disponible. Veuillez d\'abord lancer un audit.', 'audit-accessibilite' ) ) );
            return;
        }

        $results = $last_audit['results'];
        $total_issues = count( $results );
        $total_pages = ceil( $total_issues / $per_page );

        // Vérifier que la page demandée existe
        if ( $page > $total_pages ) {
            wp_send_json_error( array( 'message' => __( 'Page non valide.', 'audit-accessibilite' ) ) );
            return;
        }

        // Extraire les résultats pour la page demandée
        $page_results = array_slice( $results, $offset, $per_page );

        // Préparer les données pour la réponse
        $response_data = array(
            'issues' => $page_results,
            'pagination' => array(
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_issues' => $total_issues,
            ),
        );

        // Envoyer la réponse
        wp_send_json_success( $response_data );
    }

    /**
     * Marque un problème d'accessibilité comme corrigé.
     *
     * @since    1.0.0
     */
    public function fix_accessibility_issue() {
        // Vérifier le nonce et les capacités
        if ( ! $this->verify_nonce() || ! $this->check_capabilities() ) {
            return;
        }

        // Récupérer l'ID du problème
        $issue_id = isset( $_POST['issue_id'] ) ? intval( $_POST['issue_id'] ) : 0;

        if ( $issue_id === 0 ) {
            wp_send_json_error( array( 'message' => __( 'ID de problème non valide.', 'audit-accessibilite' ) ) );
            return;
        }

        // Récupérer les résultats du dernier audit
        $last_audit = get_option( 'audit_accessibilite_last_audit', array() );
        
        if ( empty( $last_audit ) || ! isset( $last_audit['results'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Aucun audit disponible.', 'audit-accessibilite' ) ) );
            return;
        }

        $results = $last_audit['results'];
        $updated = false;

        // Mettre à jour le statut du problème
        foreach ( $results as $key => $issue ) {
            if ( $issue['id'] === $issue_id ) {
                $results[$key]['status'] = 'fixed';
                $results[$key]['fixed_date'] = current_time( 'mysql' );
                $updated = true;
                break;
            }
        }

        if ( ! $updated ) {
            wp_send_json_error( array( 'message' => __( 'Problème non trouvé.', 'audit-accessibilite' ) ) );
            return;
        }

        // Sauvegarder les résultats mis à jour
        $last_audit['results'] = $results;
        update_option( 'audit_accessibilite_last_audit', $last_audit );

        // Préparer les données pour la réponse
        $response_data = $this->prepare_audit_data( $results );

        // Envoyer la réponse
        wp_send_json_success( $response_data );
    }

    /**
     * Marque un problème d'accessibilité comme ignoré.
     *
     * @since    1.0.0
     */
    public function ignore_accessibility_issue() {
        // Vérifier le nonce et les capacités
        if ( ! $this->verify_nonce() || ! $this->check_capabilities() ) {
            return;
        }

        // Récupérer l'ID du problème
        $issue_id = isset( $_POST['issue_id'] ) ? intval( $_POST['issue_id'] ) : 0;

        if ( $issue_id === 0 ) {
            wp_send_json_error( array( 'message' => __( 'ID de problème non valide.', 'audit-accessibilite' ) ) );
            return;
        }

        // Récupérer les résultats du dernier audit
        $last_audit = get_option( 'audit_accessibilite_last_audit', array() );
        
        if ( empty( $last_audit ) || ! isset( $last_audit['results'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Aucun audit disponible.', 'audit-accessibilite' ) ) );
            return;
        }

        $results = $last_audit['results'];
        $updated = false;

        // Mettre à jour le statut du problème
        foreach ( $results as $key => $issue ) {
            if ( $issue['id'] === $issue_id ) {
                $results[$key]['status'] = 'ignored';
                $results[$key]['ignored_date'] = current_time( 'mysql' );
                $updated = true;
                break;
            }
        }

        if ( ! $updated ) {
            wp_send_json_error( array( 'message' => __( 'Problème non trouvé.', 'audit-accessibilite' ) ) );
            return;
        }

        // Sauvegarder les résultats mis à jour
        $last_audit['results'] = $results;
        update_option( 'audit_accessibilite_last_audit', $last_audit );

        // Préparer les données pour la réponse
        $response_data = $this->prepare_audit_data( $results );

        // Envoyer la réponse
        wp_send_json_success( $response_data );
    }

    /**
     * Récupère les statistiques d'audit pour le tableau de bord.
     *
     * @since    1.0.0
     */
    public function get_audit_statistics() {
        // Vérifier le nonce et les capacités
        if ( ! $this->verify_nonce() || ! $this->check_capabilities() ) {
            return;
        }

        // Récupérer les résultats du dernier audit
        $last_audit = get_option( 'audit_accessibilite_last_audit', array() );
        
        if ( empty( $last_audit ) || ! isset( $last_audit['results'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Aucun audit disponible. Veuillez d\'abord lancer un audit.', 'audit-accessibilite' ) ) );
            return;
        }

        // Préparer les données pour la réponse
        $response_data = $this->prepare_audit_data( $last_audit['results'] );

        // Ajouter la date du dernier audit
        $response_data['last_audit_date'] = $last_audit['date'];

        // Envoyer la réponse
        wp_send_json_success( $response_data );
    }
} 