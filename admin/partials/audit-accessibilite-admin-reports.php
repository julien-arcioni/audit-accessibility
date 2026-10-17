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

// Traiter la suppression d'un audit si demandé
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $audit_id = intval($_GET['id']);
    
    // Vérifier le nonce pour la sécurité
    if (isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_audit_' . $audit_id)) {
        // Supprimer l'audit
        $wpdb->delete($audits_table, array('id' => $audit_id), array('%d'));
        
        // Rediriger vers la page des rapports sans les paramètres de suppression
        echo '<script>window.location.href = "' . admin_url('admin.php?page=audit-accessibilite-rapports&deleted=1') . '";</script>';
        exit;
    }
}

// Afficher un message de succès après la suppression
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Le rapport d\'audit a été supprimé avec succès.', 'audit-accessibilite') . '</p></div>';
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
                                <?php // Bouton CSV temporairement désactivé ?>
                                <?php /* <button type="button" class="button button-small audit-accessibilite-export-csv" data-id="<?php echo esc_attr($audit->id); ?>">
                                    <?php _e('CSV', 'audit-accessibilite'); ?>
                                </button> */ ?>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=audit-accessibilite-rapports&action=delete&id=' . $audit->id), 'delete_audit_' . $audit->id)); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir supprimer ce rapport ? Cette action est irréversible.', 'audit-accessibilite')); ?>');">
                                    <?php _e('Supprimer', 'audit-accessibilite'); ?>
                                </a>
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
                        'current' => $current_page,
                        'type' => 'list' // Ça va générer une liste HTML avec des <li>
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
    background-color: var(--wp-admin-theme-color, #2271b1);
    color: #fff;
    height: 100%;
    text-align: center;
    font-size: 12px;
    line-height: 20px;
    min-width: 20px;
}

/* Si le score est inférieur à 50%, on change la couleur */
.audit-accessibilite-score-fill[style*="width: 50"] {
    background-color: #dba617;
    color: #000;
}

.audit-accessibilite-score-fill[style*="width: 100"] {
    background-color: #00a32a;
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
    background-color: #d63638;
    color: #fff;
}

/* Style pour la pagination horizontale - version améliorée */
.audit-accessibilite-pagination {
    margin: 20px 0;
    text-align: center;
}

.audit-accessibilite-pagination .page-numbers {
    display: inline-flex;
    flex-wrap: nowrap;
    list-style: none;
    padding: 0;
    margin: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 4px;
    overflow: hidden;
    background: #fff;
}

.audit-accessibilite-pagination .page-numbers li {
    margin: 0;
    display: inline-block;
}

.audit-accessibilite-pagination a,
.audit-accessibilite-pagination span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 10px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    border: none;
    background-color: #fff;
    color: #333;
    transition: all 0.2s ease;
}

.audit-accessibilite-pagination a.prev,
.audit-accessibilite-pagination a.next {
    padding: 0 15px;
}

.audit-accessibilite-pagination span.current {
    background-color: var(--wp-admin-theme-color, #2271b1);
    color: #fff;
}

.audit-accessibilite-pagination a:hover {
    background-color: #f0f0f1;
    color: var(--wp-admin-theme-color, #2271b1);
}

.audit-accessibilite-pagination .page-numbers li:not(:last-child) {
    border-right: 1px solid #f0f0f1;
}
</style>

<script type="text/javascript">
// Désactiver la gestion d'événement JavaScript pour les liens de pagination
jQuery(document).ready(function($) {
    $('.audit-accessibilite-pagination a').off('click');
});
</script> 