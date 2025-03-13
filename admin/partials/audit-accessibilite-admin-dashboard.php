<?php
/**
 * Affiche le tableau de bord de l'extension
 *
 * @since      1.0.0
 */

// Empêcher l'accès direct
if (!defined('WPINC')) {
    die;
}

// Récupérer les options et les statistiques
$options = get_option('audit_accessibilite_options');
$last_audit = $options['last_audit_date'] ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($options['last_audit_date'])) : __('Jamais', 'audit-accessibilite');

// Récupérer les statistiques d'audit
global $wpdb;
$audits_table = $wpdb->prefix . 'audit_accessibilite_audits';
$results_table = $wpdb->prefix . 'audit_accessibilite_results';

// Vérifier si les tables existent
$audits_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$audits_table'") === $audits_table;
$results_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$results_table'") === $results_table;

// Initialisation des variables
$total_issues = 0;
$open_issues = 0;
$high_priority = 0;
$by_category = array();

// Première méthode: Extraire les données du résumé des audits
if ($audits_table_exists) {
    // Récupérer l'audit le plus récent
    $latest_audit = $wpdb->get_row("SELECT id, summary FROM $audits_table ORDER BY audit_date DESC LIMIT 1");
    
    if ($latest_audit) {
        $summary = json_decode($latest_audit->summary, true);
        if (is_array($summary)) {
            // Récupérer les statistiques globales du dernier audit
            $total_issues = isset($summary['total_issues']) ? intval($summary['total_issues']) : 0;
            $open_issues = isset($summary['open_issues']) ? intval($summary['open_issues']) : $total_issues; // Considère tous les problèmes comme ouverts par défaut
            $high_priority = isset($summary['high_priority']) ? intval($summary['high_priority']) : 0;
            
            // Si les catégories sont définies dans le résumé
            if (isset($summary['categories']) && is_array($summary['categories'])) {
                foreach ($summary['categories'] as $category => $count) {
                    $cat_obj = new stdClass();
                    $cat_obj->category = $category;
                    $cat_obj->count = $count;
                    $by_category[] = $cat_obj;
                }
                
                // Trier les catégories par nombre de problèmes décroissant
                usort($by_category, function($a, $b) {
                    return $b->count - $a->count;
                });
            }
        }
    }
}

// Deuxième méthode: Récupérer les données de la table des résultats si elle existe
if ($results_table_exists && $total_issues === 0) {
    // Vérifier la structure de la table pour adapter les requêtes
    $columns = $wpdb->get_col("SHOW COLUMNS FROM $results_table");
    $has_issue_status = in_array('issue_status', $columns);
    $has_issue_type = in_array('issue_type', $columns);
    $has_issue_priority = in_array('issue_priority', $columns);
    
    // Adapter les requêtes selon la structure de la table
    $status_col = $has_issue_status ? 'issue_status' : 'status';
    $priority_col = $has_issue_priority ? 'issue_priority' : 'priority';
    $category_col = $has_issue_type ? 'issue_type' : 'category';
    
    // Compter le nombre total de problèmes
    $total_issues = $wpdb->get_var("SELECT COUNT(*) FROM $results_table");
    
    // Compter les problèmes ouverts
    $open_issues = $wpdb->get_var("SELECT COUNT(*) FROM $results_table WHERE $status_col = 'open'");
    
    // Compter les problèmes à haute priorité
    $high_priority = $wpdb->get_var("SELECT COUNT(*) FROM $results_table WHERE $priority_col = 'high' AND $status_col = 'open'");
    
    // Récupérer les problèmes par catégorie
    $by_category = $wpdb->get_results("
        SELECT $category_col as category, COUNT(*) as count
        FROM $results_table
        WHERE $status_col = 'open'
        GROUP BY $category_col
        ORDER BY count DESC
    ");
}

// Si aucun résultat n'a été trouvé par les deux méthodes, on utilise des données fictives pour le débogage
if (empty($by_category) && $total_issues > 0) {
    // Créer des catégories par défaut basées sur les standards RGAA
    $default_categories = array('rgaa' => 0, 'seo' => 0, 'performance' => 0, 'security' => 0);
    $open_count = max(1, $open_issues); // Éviter la division par zéro
    
    // Répartir les problèmes ouverts entre les catégories
    foreach ($default_categories as $cat => $count) {
        $cat_obj = new stdClass();
        $cat_obj->category = $cat;
        $cat_obj->count = round($open_issues / count($default_categories));
        $by_category[] = $cat_obj;
    }
}

?>

<div class="wrap audit-accessibilite-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="audit-accessibilite-dashboard">
        <div class="audit-accessibilite-overview">
            <h2><?php _e('Aperçu de l\'accessibilité', 'audit-accessibilite'); ?></h2>
            
            <div class="audit-accessibilite-stats">
                <div class="audit-accessibilite-stat-box">
                    <div class="audit-accessibilite-stat-value"><?php echo esc_html($total_issues); ?></div>
                    <div class="audit-accessibilite-stat-label"><?php _e('Problèmes détectés', 'audit-accessibilite'); ?></div>
                </div>
                
                <div class="audit-accessibilite-stat-box">
                    <div class="audit-accessibilite-stat-value"><?php echo esc_html($open_issues); ?></div>
                    <div class="audit-accessibilite-stat-label"><?php _e('Problèmes ouverts', 'audit-accessibilite'); ?></div>
                </div>
                
                <div class="audit-accessibilite-stat-box">
                    <div class="audit-accessibilite-stat-value"><?php echo esc_html($high_priority); ?></div>
                    <div class="audit-accessibilite-stat-label"><?php _e('Priorité élevée', 'audit-accessibilite'); ?></div>
                </div>
                
                <div class="audit-accessibilite-stat-box">
                    <div class="audit-accessibilite-stat-value"><?php echo esc_html($last_audit); ?></div>
                    <div class="audit-accessibilite-stat-label"><?php _e('Dernier audit', 'audit-accessibilite'); ?></div>
                </div>
            </div>
        </div>
        
        <div class="audit-accessibilite-actions">
            <h2><?php _e('Actions rapides', 'audit-accessibilite'); ?></h2>
            
            <div class="audit-accessibilite-buttons">
                <a href="<?php echo esc_url(admin_url('admin.php?page=audit-accessibilite-nouveau')); ?>" class="button button-primary">
                    <?php _e('Lancer un nouvel audit', 'audit-accessibilite'); ?>
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=audit-accessibilite-rapports')); ?>" class="button">
                    <?php _e('Voir tous les rapports', 'audit-accessibilite'); ?>
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=audit-accessibilite-parametres')); ?>" class="button">
                    <?php _e('Configurer l\'extension', 'audit-accessibilite'); ?>
                </a>
            </div>
        </div>
        
        <?php if ($total_issues > 0) : ?>
            <div class="audit-accessibilite-history">
                <h2><?php _e('Historique des audits', 'audit-accessibilite'); ?></h2>
                
                <?php
                // Récupérer l'historique des audits
                $audits = array();
                if ($audits_table_exists) {
                    $audits = $wpdb->get_results("SELECT * FROM $audits_table ORDER BY audit_date DESC LIMIT 5");
                }
                
                if (!empty($audits)) : 
                ?>
                <div class="audit-accessibilite-audits-list">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'audit-accessibilite'); ?></th>
                                <th><?php _e('URL', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Type', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Problèmes', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Actions', 'audit-accessibilite'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audits as $audit) : 
                                // Extraire les données du résumé
                                $audit_summary = json_decode($audit->summary, true);
                                $audit_problems = isset($audit_summary['total_issues']) ? $audit_summary['total_issues'] : 0;
                                
                                // Formater la date
                                $audit_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($audit->audit_date));
                                
                                // Déterminer le type d'audit
                                $audit_type_label = '';
                                switch ($audit->audit_type) {
                                    case 'manual':
                                        $audit_type_label = __('Manuel', 'audit-accessibilite');
                                        break;
                                    case 'scheduled':
                                        $audit_type_label = __('Planifié', 'audit-accessibilite');
                                        break;
                                    default:
                                        $audit_type_label = ucfirst($audit->audit_type);
                                }
                            ?>
                                <tr>
                                    <td><?php echo esc_html($audit_date); ?></td>
                                    <td><?php echo esc_html($audit->target_url); ?></td>
                                    <td><?php echo esc_html($audit_type_label); ?></td>
                                    <td><?php echo esc_html($audit_problems); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=audit-accessibilite-rapport&id=' . $audit->id)); ?>" class="button button-small">
                                            <?php _e('Voir le rapport', 'audit-accessibilite'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (count($audits) < $wpdb->get_var("SELECT COUNT(*) FROM $audits_table")) : ?>
                        <p class="audit-accessibilite-view-all">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=audit-accessibilite-rapports')); ?>" class="button">
                                <?php _e('Voir tous les rapports', 'audit-accessibilite'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
                <?php else : ?>
                <div class="notice notice-info inline">
                    <p><?php _e('Aucun historique d\'audit n\'est disponible.', 'audit-accessibilite'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div class="audit-accessibilite-notice">
                <div class="notice notice-info inline">
                    <p><?php _e('Aucun audit n\'a encore été effectué. Lancez votre premier audit pour obtenir des recommandations d\'accessibilité !', 'audit-accessibilite'); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="audit-accessibilite-info">
            <h2><?php _e('À propos du RGAA 4.1', 'audit-accessibilite'); ?></h2>
            
            <div class="audit-accessibilite-info-content">
                <p><?php _e('Le <strong>Référentiel Général d\'Amélioration de l\'Accessibilité (RGAA)</strong> est le standard officiel en matière d\'accessibilité numérique en France.', 'audit-accessibilite'); ?></p>
                
                <p><?php _e('La version 4.1, publiée en 2021, comprend 13 thématiques et 106 critères permettant de vérifier la conformité d\'un site web aux normes internationales d\'accessibilité (WCAG 2.1).', 'audit-accessibilite'); ?></p>
                
                <p><?php _e('Les thématiques du RGAA 4.1 :', 'audit-accessibilite'); ?></p>
                
                <ol>
                    <li><?php _e('Images', 'audit-accessibilite'); ?></li>
                    <li><?php _e('Cadres', 'audit-accessibilite'); ?></li>
                    <li><?php _e('Couleurs', 'audit-accessibilite'); ?></li>
                    <li><?php _e('Multimédia', 'audit-accessibilite'); ?></li>
                    <li><?php _e('Tableaux', 'audit-accessibilite'); ?></li>
                    <li><?php _e('Liens', 'audit-accessibilite'); ?></li>
                    <li><?php _e('Scripts', 'audit-accessibilite'); ?></li>
                    <li><?php _e('Éléments obligatoires', 'audit-accessibilite'); ?></li>
                    <li><?php _e('Structuration de l\'information', 'audit-accessibilite'); ?></li>
                    <li><?php _e('Présentation de l\'information', 'audit-accessibilite'); ?></li>
                    <li><?php _e('Formulaires', 'audit-accessibilite'); ?></li>
                    <li><?php _e('Navigation', 'audit-accessibilite'); ?></li>
                    <li><?php _e('Consultation', 'audit-accessibilite'); ?></li>
                </ol>
                
                <p><?php _e('Pour en savoir plus, consultez la <a href="https://accessibilite.numerique.gouv.fr/" target="_blank">documentation officielle du RGAA</a>.', 'audit-accessibilite'); ?></p>
            </div>
        </div>
    </div>
</div>

<style>
.audit-accessibilite-dashboard {
    margin-top: 20px;
}

.audit-accessibilite-overview,
.audit-accessibilite-actions,
.audit-accessibilite-history,
.audit-accessibilite-info,
.audit-accessibilite-notice {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-bottom: 20px;
    padding: 20px;
}

.audit-accessibilite-stats {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
}

.audit-accessibilite-stat-box {
    background: #f8f9fa;
    border: 1px solid #e2e4e7;
    border-left: 4px solid #2271b1;
    border-radius: 3px;
    box-sizing: border-box;
    margin: 10px;
    padding: 15px;
    text-align: center;
    width: calc(25% - 20px);
}

.audit-accessibilite-stat-value {
    color: #1e1e1e;
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 5px;
}

.audit-accessibilite-stat-label {
    color: #646970;
}

.audit-accessibilite-buttons {
    display: flex;
    gap: 10px;
}

.audit-accessibilite-audits-list {
    margin-top: 15px;
}

.audit-accessibilite-audits-list .widefat th {
    font-weight: 600;
}

.audit-accessibilite-view-all {
    margin-top: 15px;
    text-align: right;
}

.audit-accessibilite-info-content {
    line-height: 1.6;
}

@media screen and (max-width: 782px) {
    .audit-accessibilite-stat-box {
        width: calc(50% - 20px);
    }
    
    .audit-accessibilite-buttons {
        flex-direction: column;
    }
    
    .audit-accessibilite-buttons .button {
        margin-bottom: 10px;
        text-align: center;
    }
}

@media screen and (max-width: 480px) {
    .audit-accessibilite-stat-box {
        width: 100%;
    }
}
</style> 