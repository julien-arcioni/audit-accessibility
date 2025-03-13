<?php
/**
 * Affiche la page des rapports d'audit
 *
 * @since      1.0.0
 */

// Empêcher l'accès direct
if (!defined('WPINC')) {
    die;
}

// Récupérer les audits depuis la base de données
global $wpdb;
$audits_table = $wpdb->prefix . 'audit_accessibilite_audits';

// Paramètres de pagination
$current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 10;
$offset = ($current_page - 1) * $per_page;

// Vérifier si la table existe
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$audits_table'") === $audits_table;

if ($table_exists) {
    // Récupérer les audits avec pagination
    $audits = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $audits_table ORDER BY audit_date DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );

    // Compter le nombre total d'audits
    $total_audits = $wpdb->get_var("SELECT COUNT(*) FROM $audits_table");
    $total_pages = ceil($total_audits / $per_page);
} else {
    // Si la table n'existe pas encore
    $audits = array();
    $total_audits = 0;
    $total_pages = 0;
}
?>

<div class="wrap audit-accessibilite-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="audit-accessibilite-box">
        <h2><?php _e('Historique des audits', 'audit-accessibilite'); ?></h2>
        
        <?php if (empty($audits)) : ?>
            <div class="notice notice-warning inline">
                <p><?php _e('Aucun audit n\'a encore été effectué. Lancez votre premier audit pour générer des rapports !', 'audit-accessibilite'); ?></p>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=audit-accessibilite-nouveau')); ?>" class="button button-primary">
                        <?php _e('Lancer un nouvel audit', 'audit-accessibilite'); ?>
                    </a>
                </p>
            </div>
        <?php else : ?>
            <div class="audit-accessibilite-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
                    
                    <div class="audit-accessibilite-filter">
                        <label for="filter_date"><?php _e('Filtrer par date :', 'audit-accessibilite'); ?></label>
                        <select name="filter_date" id="filter_date">
                            <option value=""><?php _e('Toutes les dates', 'audit-accessibilite'); ?></option>
                            <option value="today" <?php selected(isset($_GET['filter_date']) && $_GET['filter_date'] === 'today'); ?>><?php _e('Aujourd\'hui', 'audit-accessibilite'); ?></option>
                            <option value="week" <?php selected(isset($_GET['filter_date']) && $_GET['filter_date'] === 'week'); ?>><?php _e('Cette semaine', 'audit-accessibilite'); ?></option>
                            <option value="month" <?php selected(isset($_GET['filter_date']) && $_GET['filter_date'] === 'month'); ?>><?php _e('Ce mois-ci', 'audit-accessibilite'); ?></option>
                        </select>
                    </div>
                    
                    <div class="audit-accessibilite-filter">
                        <label for="filter_type"><?php _e('Type d\'audit :', 'audit-accessibilite'); ?></label>
                        <select name="filter_type" id="filter_type">
                            <option value=""><?php _e('Tous les types', 'audit-accessibilite'); ?></option>
                            <option value="manual" <?php selected(isset($_GET['filter_type']) && $_GET['filter_type'] === 'manual'); ?>><?php _e('Manuel', 'audit-accessibilite'); ?></option>
                            <option value="scheduled" <?php selected(isset($_GET['filter_type']) && $_GET['filter_type'] === 'scheduled'); ?>><?php _e('Planifié', 'audit-accessibilite'); ?></option>
                        </select>
                    </div>
                    
                    <button type="submit" class="button"><?php _e('Filtrer', 'audit-accessibilite'); ?></button>
                </form>
            </div>
            
            <table class="widefat audit-accessibilite-reports-table">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'audit-accessibilite'); ?></th>
                        <th><?php _e('Date', 'audit-accessibilite'); ?></th>
                        <th><?php _e('URL cible', 'audit-accessibilite'); ?></th>
                        <th><?php _e('Type', 'audit-accessibilite'); ?></th>
                        <th><?php _e('Problèmes', 'audit-accessibilite'); ?></th>
                        <th><?php _e('Score', 'audit-accessibilite'); ?></th>
                        <th><?php _e('Actions', 'audit-accessibilite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($audits as $audit) : 
                        $summary = json_decode($audit->summary, true);
                        $score = 0;
                        if (isset($summary['tests_total']) && $summary['tests_total'] > 0) {
                            $score = round(($summary['tests_passed'] / $summary['tests_total']) * 100);
                        }
                        
                        $type_label = '';
                        switch ($audit->audit_type) {
                            case 'manual':
                                $type_label = __('Manuel', 'audit-accessibilite');
                                break;
                            case 'scheduled':
                                $type_label = __('Planifié', 'audit-accessibilite');
                                break;
                            default:
                                $type_label = $audit->audit_type;
                        }
                    ?>
                        <tr>
                            <td><?php echo esc_html($audit->id); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($audit->audit_date))); ?></td>
                            <td><?php echo esc_html($audit->target_url); ?></td>
                            <td><?php echo esc_html($type_label); ?></td>
                            <td>
                                <?php if (isset($summary['total_issues'])) : ?>
                                    <strong><?php echo esc_html($summary['total_issues']); ?></strong>
                                    <?php if (isset($summary['high_priority']) && $summary['high_priority'] > 0) : ?>
                                        <span class="audit-accessibilite-priority audit-accessibilite-priority-high"><?php echo esc_html($summary['high_priority']); ?></span>
                                    <?php endif; ?>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($score > 0) : ?>
                                    <div class="audit-accessibilite-score-bar">
                                        <div class="audit-accessibilite-score-fill" style="width: <?php echo esc_attr($score); ?>%;">
                                            <?php echo esc_html($score); ?>%
                                        </div>
                                    </div>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=audit-accessibilite-rapport&id=' . $audit->id)); ?>" class="button button-small">
                                    <?php _e('Voir', 'audit-accessibilite'); ?>
                                </a>
                                <button type="button" class="button button-small audit-accessibilite-export-pdf" data-id="<?php echo esc_attr($audit->id); ?>">
                                    <?php _e('PDF', 'audit-accessibilite'); ?>
                                </button>
                                <button type="button" class="button button-small audit-accessibilite-export-csv" data-id="<?php echo esc_attr($audit->id); ?>">
                                    <?php _e('CSV', 'audit-accessibilite'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1) : ?>
                <div class="audit-accessibilite-pagination">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; Précédent', 'audit-accessibilite'),
                        'next_text' => __('Suivant &raquo;', 'audit-accessibilite'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="audit-accessibilite-box">
        <h2><?php _e('Statistiques globales', 'audit-accessibilite'); ?></h2>
        
        <?php if (empty($audits)) : ?>
            <p><?php _e('Aucune statistique disponible pour le moment.', 'audit-accessibilite'); ?></p>
        <?php else : 
            // Calculer les statistiques globales
            $total_issues = 0;
            $high_priority_issues = 0;
            $avg_score = 0;
            $scores_sum = 0;
            $audits_with_score = 0;
            
            foreach ($audits as $audit) {
                $summary = json_decode($audit->summary, true);
                if (isset($summary['total_issues'])) {
                    $total_issues += $summary['total_issues'];
                }
                if (isset($summary['high_priority'])) {
                    $high_priority_issues += $summary['high_priority'];
                }
                if (isset($summary['tests_passed']) && isset($summary['tests_total']) && $summary['tests_total'] > 0) {
                    $scores_sum += ($summary['tests_passed'] / $summary['tests_total']) * 100;
                    $audits_with_score++;
                }
            }
            
            $avg_score = $audits_with_score > 0 ? round($scores_sum / $audits_with_score) : 0;
        ?>
            <div class="audit-accessibilite-stats">
                <div class="audit-accessibilite-stat-box">
                    <div class="audit-accessibilite-stat-value"><?php echo esc_html($total_audits); ?></div>
                    <div class="audit-accessibilite-stat-label"><?php _e('Audits réalisés', 'audit-accessibilite'); ?></div>
                </div>
                
                <div class="audit-accessibilite-stat-box">
                    <div class="audit-accessibilite-stat-value"><?php echo esc_html($total_issues); ?></div>
                    <div class="audit-accessibilite-stat-label"><?php _e('Problèmes détectés', 'audit-accessibilite'); ?></div>
                </div>
                
                <div class="audit-accessibilite-stat-box">
                    <div class="audit-accessibilite-stat-value"><?php echo esc_html($high_priority_issues); ?></div>
                    <div class="audit-accessibilite-stat-label"><?php _e('Priorité élevée', 'audit-accessibilite'); ?></div>
                </div>
                
                <div class="audit-accessibilite-stat-box">
                    <div class="audit-accessibilite-stat-value"><?php echo esc_html($avg_score); ?>%</div>
                    <div class="audit-accessibilite-stat-label"><?php _e('Score moyen', 'audit-accessibilite'); ?></div>
                </div>
            </div>
            
            <p class="description"><?php _e('Ces statistiques sont calculées à partir de tous les audits réalisés.', 'audit-accessibilite'); ?></p>
        <?php endif; ?>
    </div>
</div>

<style>
.audit-accessibilite-filters {
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: center;
    background: #f8f9fa;
    padding: 15px;
    border-radius: 3px;
}

.audit-accessibilite-filter {
    display: flex;
    align-items: center;
    gap: 8px;
}

.audit-accessibilite-reports-table {
    margin-top: 15px;
}

.audit-accessibilite-score-bar {
    background-color: #f0f0f1;
    border-radius: 3px;
    height: 20px;
    width: 100%;
    max-width: 150px;
    overflow: hidden;
}

.audit-accessibilite-score-fill {
    background-color: #2271b1;
    color: #fff;
    height: 100%;
    text-align: center;
    font-size: 12px;
    line-height: 20px;
    min-width: 20px;
}

/* Si le score est inférieur à 50%, on change la couleur */
.audit-accessibilite-score-fill[style*="width: 50"] {
    background-color: #ffc107;
    color: #000;
}

.audit-accessibilite-score-fill[style*="width: 4"],
.audit-accessibilite-score-fill[style*="width: 3"],
.audit-accessibilite-score-fill[style*="width: 2"],
.audit-accessibilite-score-fill[style*="width: 1"] {
    background-color: #dc3545;
}

.audit-accessibilite-priority {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 5px;
}

.audit-accessibilite-priority-high {
    background-color: #dc3545;
    color: #fff;
}
</style> 