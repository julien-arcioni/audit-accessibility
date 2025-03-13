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
$table_name = $wpdb->prefix . 'audit_accessibilite_results';
$total_issues = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$open_issues = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'open'");
$high_priority = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE priority = 'high' AND status = 'open'");

$by_category = $wpdb->get_results("
    SELECT category, COUNT(*) as count
    FROM $table_name
    WHERE status = 'open'
    GROUP BY category
    ORDER BY count DESC
");

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
            <div class="audit-accessibilite-categories">
                <h2><?php _e('Problèmes par catégorie', 'audit-accessibilite'); ?></h2>
                
                <div class="audit-accessibilite-categories-chart">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php _e('Catégorie', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Problèmes ouverts', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Actions', 'audit-accessibilite'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($by_category as $category) : ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $category_name = '';
                                        switch ($category->category) {
                                            case 'rgaa':
                                                $category_name = __('Accessibilité RGAA', 'audit-accessibilite');
                                                break;
                                            case 'seo':
                                                $category_name = __('SEO', 'audit-accessibilite');
                                                break;
                                            case 'performance':
                                                $category_name = __('Performance', 'audit-accessibilite');
                                                break;
                                            case 'security':
                                                $category_name = __('Sécurité', 'audit-accessibilite');
                                                break;
                                            default:
                                                $category_name = $category->category;
                                        }
                                        echo esc_html($category_name); 
                                        ?>
                                    </td>
                                    <td><?php echo esc_html($category->count); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=audit-accessibilite-rapports&filter_category=' . urlencode($category->category))); ?>" class="button button-small">
                                            <?php _e('Voir', 'audit-accessibilite'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
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
.audit-accessibilite-categories,
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

.audit-accessibilite-categories-chart {
    margin-top: 15px;
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