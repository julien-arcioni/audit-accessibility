<?php

/**
 * Classe pour la génération de rapports d'audit
 *
 * @since      1.0.0
 */
class Audit_Accessibilite_Report {

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
     * Génère un rapport à partir des résultats de l'audit
     *
     * @since    1.0.0
     * @param    array     $results    Les résultats de l'audit
     * @param    string    $url        L'URL auditée
     * @return   string                URL du rapport généré
     */
    public function generate_report($results, $url) {
        // Créer le dossier des rapports s'il n'existe pas
        $upload_dir = wp_upload_dir();
        $reports_dir = $upload_dir['basedir'] . '/audit-accessibilite-reports';
        
        if (!file_exists($reports_dir)) {
            wp_mkdir_p($reports_dir);
            
            // Créer un fichier index.php pour protection
            file_put_contents($reports_dir . '/index.php', '<?php // Silence is golden');
        }
        
        // Générer le nom du fichier
        $date = date('Y-m-d-H-i-s');
        $report_name = 'rapport-accessibilite-' . sanitize_title(parse_url($url, PHP_URL_HOST)) . '-' . $date;
        $report_file = $reports_dir . '/' . $report_name . '.html';
        
        // Générer le contenu du rapport
        $report_content = $this->generate_html_report($results, $url);
        
        // Sauvegarder le rapport
        file_put_contents($report_file, $report_content);
        
        // Retourner l'URL du rapport
        return $upload_dir['baseurl'] . '/audit-accessibilite-reports/' . $report_name . '.html';
    }

    /**
     * Génère un rapport HTML
     *
     * @since    1.0.0
     * @param    array     $results    Les résultats de l'audit
     * @param    string    $url        L'URL auditée
     * @return   string                Le contenu HTML du rapport
     */
    private function generate_html_report($results, $url) {
        // Scores et statistiques
        $total_issues = 0;
        $total_score = 0;
        $scores_count = 0;
        
        // Compiler les données de tous les types d'audits (RGAA, SEO, Performance, Sécurité)
        $all_issues = array();
        
        foreach ($results as $audit_type => $audit_results) {
            if (!empty($audit_results['issues'])) {
                $total_issues += count($audit_results['issues']);
                $all_issues[$audit_type] = $audit_results['issues'];
            }
            
            if (isset($audit_results['score'])) {
                $total_score += $audit_results['score'];
                $scores_count++;
            }
        }
        
        $average_score = ($scores_count > 0) ? round($total_score / $scores_count) : 0;
        
        // Début du document HTML
        $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport d\'audit d\'accessibilité - ' . esc_html(parse_url($url, PHP_URL_HOST)) . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        header {
            background-color: #1e88e5;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        h1, h2, h3, h4 {
            color: #1e88e5;
        }
        .score-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .score-box {
            text-align: center;
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            width: 200px;
            margin: 10px;
        }
        .score-value {
            font-size: 48px;
            font-weight: bold;
            margin: 0;
            color: #333;
        }
        .score-label {
            font-size: 16px;
            color: #666;
        }
        .issues-container {
            margin-top: 30px;
        }
        .issue {
            background-color: #f9f9f9;
            border-left: 5px solid #ffc107;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 0 5px 5px 0;
        }
        .issue.high {
            border-left-color: #f44336;
        }
        .issue.medium {
            border-left-color: #ff9800;
        }
        .issue.low {
            border-left-color: #4caf50;
        }
        .issue-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .issue-title {
            font-weight: bold;
            font-size: 18px;
        }
        .issue-priority {
            font-size: 14px;
            padding: 3px 8px;
            border-radius: 3px;
            color: white;
        }
        .priority-high {
            background-color: #f44336;
        }
        .priority-medium {
            background-color: #ff9800;
        }
        .priority-low {
            background-color: #4caf50;
        }
        .issue-description {
            margin-bottom: 10px;
        }
        .issue-recommendation {
            background-color: #e3f2fd;
            padding: 10px;
            border-radius: 5px;
        }
        .no-issues {
            background-color: #e8f5e9;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
        }
        footer {
            margin-top: 50px;
            text-align: center;
            color: #666;
            font-size: 14px;
            padding: 20px;
            border-top: 1px solid #ddd;
        }
        @media print {
            body {
                background-color: white;
            }
            .container {
                box-shadow: none;
                max-width: none;
            }
            .issue {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Rapport d\'audit d\'accessibilité</h1>
            <p>URL auditée : ' . esc_html($url) . '</p>
            <p>Date de l\'audit : ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format')) . '</p>
        </header>
        
        <section>
            <h2>Résumé de l\'audit</h2>
            
            <div class="score-container">
                <div class="score-box">
                    <p class="score-value">' . $average_score . '%</p>
                    <p class="score-label">Score global</p>
                </div>';
        
        // Ajouter les scores par type d'audit
        foreach ($results as $audit_type => $audit_results) {
            if (isset($audit_results['score'])) {
                $audit_type_name = '';
                switch ($audit_type) {
                    case 'rgaa':
                        $audit_type_name = __('Accessibilité RGAA', 'audit-accessibilite');
                        break;
                    case 'seo':
                        $audit_type_name = __('SEO', 'audit-accessibilite');
                        break;
                    case 'performance':
                        $audit_type_name = __('Performance', 'audit-accessibilite');
                        break;
                    case 'security':
                        $audit_type_name = __('Sécurité', 'audit-accessibilite');
                        break;
                }
                
                $html .= '
                <div class="score-box">
                    <p class="score-value">' . esc_html($audit_results['score']) . '%</p>
                    <p class="score-label">' . esc_html($audit_type_name) . '</p>
                </div>';
            }
        }
        
        $html .= '
                <div class="score-box">
                    <p class="score-value">' . $total_issues . '</p>
                    <p class="score-label">' . __('Problèmes détectés', 'audit-accessibilite') . '</p>
                </div>
            </div>
        </section>';
        
        // Afficher les problèmes par type d'audit
        foreach ($all_issues as $audit_type => $issues) {
            $audit_type_name = '';
            switch ($audit_type) {
                case 'rgaa':
                    $audit_type_name = __('Audit d\'accessibilité RGAA 4.1', 'audit-accessibilite');
                    break;
                case 'seo':
                    $audit_type_name = __('Audit SEO', 'audit-accessibilite');
                    break;
                case 'performance':
                    $audit_type_name = __('Audit de performance', 'audit-accessibilite');
                    break;
                case 'security':
                    $audit_type_name = __('Audit de sécurité', 'audit-accessibilite');
                    break;
            }
            
            $html .= '
            <section class="issues-container">
                <h2>' . esc_html($audit_type_name) . '</h2>';
            
            if (empty($issues)) {
                $html .= '
                <div class="no-issues">
                    <p>' . __('Aucun problème détecté', 'audit-accessibilite') . '</p>
                </div>';
            } else {
                // Si RGAA, grouper par thématique
                if ($audit_type === 'rgaa') {
                    // Regrouper les problèmes par thématique
                    $issues_by_theme = array();
                    foreach ($issues as $issue) {
                        if (isset($issue['theme']) && isset($this->rgaa_themes[$issue['theme']])) {
                            $theme_id = $issue['theme'];
                            if (!isset($issues_by_theme[$theme_id])) {
                                $issues_by_theme[$theme_id] = array();
                            }
                            $issues_by_theme[$theme_id][] = $issue;
                        } else {
                            // Pour les problèmes sans thème spécifié
                            if (!isset($issues_by_theme[0])) {
                                $issues_by_theme[0] = array();
                            }
                            $issues_by_theme[0][] = $issue;
                        }
                    }
                    
                    // Afficher les problèmes par thématique
                    ksort($issues_by_theme);
                    foreach ($issues_by_theme as $theme_id => $theme_issues) {
                        $theme_name = isset($this->rgaa_themes[$theme_id]) ? $this->rgaa_themes[$theme_id] : __('Général', 'audit-accessibilite');
                        
                        $html .= '
                        <h3>' . esc_html($theme_name) . '</h3>';
                        
                        foreach ($theme_issues as $issue) {
                            $priority_class = isset($issue['priority']) ? 'priority-' . $issue['priority'] : 'priority-medium';
                            $issue_class = isset($issue['priority']) ? $issue['priority'] : 'medium';
                            
                            $html .= '
                            <div class="issue ' . esc_attr($issue_class) . '">
                                <div class="issue-header">
                                    <div class="issue-title">' . esc_html($issue['description']) . '</div>
                                    <div class="issue-priority ' . esc_attr($priority_class) . '">' . $this->get_priority_label($issue['priority']) . '</div>
                                </div>
                                <div class="issue-description">
                                    <p><strong>' . __('Élément :', 'audit-accessibilite') . '</strong> ' . esc_html($issue['element_type']) . '</p>
                                    ' . (isset($issue['criterion']) ? '<p><strong>' . __('Critère RGAA :', 'audit-accessibilite') . '</strong> ' . esc_html($issue['criterion']) . '</p>' : '') . '
                                </div>
                                <div class="issue-recommendation">
                                    <p><strong>' . __('Recommandation :', 'audit-accessibilite') . '</strong> ' . esc_html($issue['recommendation']) . '</p>
                                </div>
                            </div>';
                        }
                    }
                } else {
                    // Pour les autres types d'audit, afficher simplement la liste
                    foreach ($issues as $issue) {
                        $priority_class = isset($issue['priority']) ? 'priority-' . $issue['priority'] : 'priority-medium';
                        $issue_class = isset($issue['priority']) ? $issue['priority'] : 'medium';
                        
                        $html .= '
                        <div class="issue ' . esc_attr($issue_class) . '">
                            <div class="issue-header">
                                <div class="issue-title">' . esc_html($issue['description']) . '</div>
                                <div class="issue-priority ' . esc_attr($priority_class) . '">' . $this->get_priority_label($issue['priority']) . '</div>
                            </div>
                            <div class="issue-description">
                                <p><strong>' . __('Élément :', 'audit-accessibilite') . '</strong> ' . esc_html($issue['element_type']) . '</p>
                            </div>
                            <div class="issue-recommendation">
                                <p><strong>' . __('Recommandation :', 'audit-accessibilite') . '</strong> ' . esc_html($issue['recommendation']) . '</p>
                            </div>
                        </div>';
                    }
                }
            }
            
            $html .= '
            </section>';
        }
        
        // Pied de page
        $html .= '
        <footer>
            <p>' . __('Rapport généré par l\'extension Audit Accessibilité pour WordPress', 'audit-accessibilite') . '</p>
            <p>' . __('Version', 'audit-accessibilite') . ' ' . AUDIT_ACCESSIBILITE_VERSION . '</p>
        </footer>
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Exporte un rapport dans le format spécifié
     *
     * @since    1.0.0
     * @param    array     $report_data    Les données du rapport
     * @param    string    $format         Le format d'export (pdf, csv, html)
     * @return   string                    L'URL du fichier exporté
     */
    public function export_report($report_data, $format = 'pdf') {
        $upload_dir = wp_upload_dir();
        $reports_dir = $upload_dir['basedir'] . '/audit-accessibilite-reports';
        
        if (!file_exists($reports_dir)) {
            wp_mkdir_p($reports_dir);
        }
        
        $date = date('Y-m-d-H-i-s');
        $filename = 'export-accessibilite-' . $date;
        
        switch ($format) {
            case 'pdf':
                // Pour cette version simple, nous retournons simplement le HTML généré
                // Une implémentation complète nécessiterait une bibliothèque PDF
                $results = array(
                    'rgaa' => array(
                        'issues' => array(maybe_unserialize($report_data['audit_results'])),
                        'score' => $report_data['score']
                    )
                );
                
                $html = $this->generate_html_report($results, $report_data['page_url']);
                $file_path = $reports_dir . '/' . $filename . '.html';
                file_put_contents($file_path, $html);
                
                return $upload_dir['baseurl'] . '/audit-accessibilite-reports/' . $filename . '.html';
                
            case 'csv':
                // Export CSV basique
                $file_path = $reports_dir . '/' . $filename . '.csv';
                $fp = fopen($file_path, 'w');
                
                // En-têtes
                fputcsv($fp, array(
                    __('Date', 'audit-accessibilite'),
                    __('URL', 'audit-accessibilite'),
                    __('Catégorie', 'audit-accessibilite'),
                    __('Description', 'audit-accessibilite'),
                    __('Priorité', 'audit-accessibilite'),
                    __('Recommandation', 'audit-accessibilite')
                ));
                
                // Données
                $issue = maybe_unserialize($report_data['audit_results']);
                fputcsv($fp, array(
                    $report_data['audit_date'],
                    $report_data['page_url'],
                    $report_data['category'],
                    $issue['description'],
                    $issue['priority'],
                    $issue['recommendation']
                ));
                
                fclose($fp);
                
                return $upload_dir['baseurl'] . '/audit-accessibilite-reports/' . $filename . '.csv';
                
            case 'html':
            default:
                $results = array(
                    'rgaa' => array(
                        'issues' => array(maybe_unserialize($report_data['audit_results'])),
                        'score' => $report_data['score']
                    )
                );
                
                $html = $this->generate_html_report($results, $report_data['page_url']);
                $file_path = $reports_dir . '/' . $filename . '.html';
                file_put_contents($file_path, $html);
                
                return $upload_dir['baseurl'] . '/audit-accessibilite-reports/' . $filename . '.html';
        }
    }

    /**
     * Obtient le libellé de priorité
     *
     * @since    1.0.0
     * @param    string    $priority    La priorité (high, medium, low)
     * @return   string                 Le libellé de priorité
     */
    private function get_priority_label($priority) {
        switch ($priority) {
            case 'high':
                return __('Élevée', 'audit-accessibilite');
            case 'medium':
                return __('Moyenne', 'audit-accessibilite');
            case 'low':
                return __('Faible', 'audit-accessibilite');
            default:
                return __('Moyenne', 'audit-accessibilite');
        }
    }

    /**
     * Envoie un rapport d'audit par e-mail
     *
     * @since    1.0.0
     * @param    string    $email      L'adresse e-mail du destinataire
     * @param    array     $results    Les résultats de l'audit
     * @return   bool                  True si l'e-mail a été envoyé, false sinon
     */
    public function send_email_report($email, $results) {
        // Vérifier l'adresse e-mail
        if (!is_email($email)) {
            return false;
        }

        // Récupérer l'URL cible de l'audit
        $target_url = isset($results['target_url']) ? $results['target_url'] : home_url();
        
        // Récupérer la date de l'audit
        $audit_date = isset($results['audit_date']) ? $results['audit_date'] : current_time('mysql');
        $formatted_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($audit_date));
        
        // Générer le sujet de l'e-mail
        $site_name = get_bloginfo('name');
        $subject = sprintf(
            __('[%s] Rapport d\'audit d\'accessibilité - %s', 'audit-accessibilite'),
            $site_name,
            $formatted_date
        );
        
        // Générer l'en-tête de l'e-mail
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $site_name . ' <' . get_option('admin_email') . '>',
        );
        
        // Générer le contenu de l'e-mail
        $content = '<html><body>';
        $content .= '<h1>' . __('Rapport d\'audit d\'accessibilité', 'audit-accessibilite') . '</h1>';
        $content .= '<p>' . sprintf(
            __('Ce rapport a été généré automatiquement par l\'extension Audit Accessibilité pour le site %s.', 'audit-accessibilite'),
            '<strong>' . $site_name . '</strong>'
        ) . '</p>';
        
        $content .= '<p><strong>' . __('URL auditée :', 'audit-accessibilite') . '</strong> ' . esc_html($target_url) . '</p>';
        $content .= '<p><strong>' . __('Date de l\'audit :', 'audit-accessibilite') . '</strong> ' . esc_html($formatted_date) . '</p>';
        
        // Statistiques globales
        $content .= '<h2>' . __('Résumé de l\'audit', 'audit-accessibilite') . '</h2>';
        $content .= '<table style="border-collapse: collapse; width: 100%; margin-bottom: 20px;">';
        $content .= '<tr style="background-color: #f2f2f2;">';
        $content .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">' . __('Critère', 'audit-accessibilite') . '</th>';
        $content .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">' . __('Valeur', 'audit-accessibilite') . '</th>';
        $content .= '</tr>';
        
        $content .= '<tr>';
        $content .= '<td style="border: 1px solid #ddd; padding: 8px;">' . __('Score global', 'audit-accessibilite') . '</td>';
        $content .= '<td style="border: 1px solid #ddd; padding: 8px;">' . (isset($results['score']) ? $results['score'] : 0) . '%</td>';
        $content .= '</tr>';
        
        $content .= '<tr>';
        $content .= '<td style="border: 1px solid #ddd; padding: 8px;">' . __('Problèmes détectés', 'audit-accessibilite') . '</td>';
        $content .= '<td style="border: 1px solid #ddd; padding: 8px;">' . (isset($results['total_issues']) ? $results['total_issues'] : 0) . '</td>';
        $content .= '</tr>';
        
        $content .= '<tr>';
        $content .= '<td style="border: 1px solid #ddd; padding: 8px;">' . __('Problèmes critiques', 'audit-accessibilite') . '</td>';
        $content .= '<td style="border: 1px solid #ddd; padding: 8px;">' . (isset($results['high_priority']) ? $results['high_priority'] : 0) . '</td>';
        $content .= '</tr>';
        
        $content .= '</table>';
        
        // Inclure un lien pour voir le rapport complet
        $admin_url = admin_url('admin.php?page=audit-accessibilite-rapports');
        $content .= '<p><a href="' . esc_url($admin_url) . '" style="display: inline-block; padding: 10px 15px; background-color: #0073aa; color: white; text-decoration: none; border-radius: 3px;">' . __('Voir le rapport complet', 'audit-accessibilite') . '</a></p>';
        
        $content .= '<hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">';
        $content .= '<p style="font-size: 12px; color: #666;">' . __('Ce message a été envoyé automatiquement par l\'extension Audit Accessibilité.', 'audit-accessibilite') . '</p>';
        $content .= '</body></html>';
        
        // Envoyer l'e-mail
        $sent = wp_mail($email, $subject, $content, $headers);
        
        return $sent;
    }
}
