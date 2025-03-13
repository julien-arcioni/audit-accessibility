<?php
/**
 * Affiche un rapport d'audit individuel
 *
 * @since      1.0.0
 */

// Empêcher l'accès direct
if (!defined('WPINC')) {
    die;
}

// Vérifier que l'ID de l'audit est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    wp_die(__('Rapport non trouvé. Veuillez retourner à la liste des rapports.', 'audit-accessibilite'));
}

$audit_id = intval($_GET['id']);

// Récupérer les données de l'audit
global $wpdb;
$audits_table = $wpdb->prefix . 'audit_accessibilite_audits';

// Vérifier si la table existe
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$audits_table'") === $audits_table;

if (!$table_exists) {
    wp_die(__('Aucun audit n\'a encore été effectué. Veuillez lancer un audit.', 'audit-accessibilite'));
}

// Récupérer l'audit spécifique
$audit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $audits_table WHERE id = %d", $audit_id));

if (!$audit) {
    wp_die(__('Le rapport demandé n\'existe pas.', 'audit-accessibilite'));
}

// Décoder les données JSON
$summary = json_decode($audit->summary, true);
$results = json_decode($audit->results, true);

// Calcul du score
$score = 0;
if (isset($summary['tests_total']) && $summary['tests_total'] > 0) {
    $score = round(($summary['tests_passed'] / $summary['tests_total']) * 100);
}

// Type d'audit
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

<div class="wrap audit-accessibilite-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="audit-accessibilite-back-link">
        <a href="<?php echo esc_url(admin_url('admin.php?page=audit-accessibilite-rapports')); ?>" class="button">
            &larr; <?php _e('Retour aux rapports', 'audit-accessibilite'); ?>
        </a>
    </div>
    
    <div class="audit-accessibilite-box">
        <h2><?php _e('Informations sur l\'audit', 'audit-accessibilite'); ?></h2>
        
        <div class="audit-accessibilite-report-header">
            <div class="audit-accessibilite-report-meta">
                <p><strong><?php _e('Date :', 'audit-accessibilite'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($audit->audit_date))); ?></p>
                <p><strong><?php _e('URL cible :', 'audit-accessibilite'); ?></strong> <a href="<?php echo esc_url($audit->target_url); ?>" target="_blank"><?php echo esc_html($audit->target_url); ?></a></p>
                <p><strong><?php _e('Type d\'audit :', 'audit-accessibilite'); ?></strong> <?php echo esc_html($type_label); ?></p>
            </div>
            
            <div class="audit-accessibilite-report-actions">
                <button type="button" class="button audit-accessibilite-export-pdf" data-id="<?php echo esc_attr($audit_id); ?>">
                    <?php _e('Exporter en PDF', 'audit-accessibilite'); ?>
                </button>
                <button type="button" class="button audit-accessibilite-export-csv" data-id="<?php echo esc_attr($audit_id); ?>">
                    <?php _e('Exporter en CSV', 'audit-accessibilite'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <div class="audit-accessibilite-box">
        <h2><?php _e('Résumé de l\'audit', 'audit-accessibilite'); ?></h2>
        
        <div class="audit-accessibilite-stats">
            <div class="audit-accessibilite-stat-box">
                <div class="audit-accessibilite-stat-value"><?php echo isset($summary['total_issues']) ? esc_html($summary['total_issues']) : 0; ?></div>
                <div class="audit-accessibilite-stat-label"><?php _e('Problèmes détectés', 'audit-accessibilite'); ?></div>
            </div>
            
            <div class="audit-accessibilite-stat-box">
                <div class="audit-accessibilite-stat-value"><?php echo isset($summary['open_issues']) ? esc_html($summary['open_issues']) : 0; ?></div>
                <div class="audit-accessibilite-stat-label"><?php _e('Problèmes ouverts', 'audit-accessibilite'); ?></div>
            </div>
            
            <div class="audit-accessibilite-stat-box">
                <div class="audit-accessibilite-stat-value"><?php echo isset($summary['high_priority']) ? esc_html($summary['high_priority']) : 0; ?></div>
                <div class="audit-accessibilite-stat-label"><?php _e('Priorité élevée', 'audit-accessibilite'); ?></div>
            </div>
            
            <div class="audit-accessibilite-stat-box">
                <div class="audit-accessibilite-stat-value"><?php echo esc_html($score); ?>%</div>
                <div class="audit-accessibilite-stat-label"><?php _e('Score', 'audit-accessibilite'); ?></div>
            </div>
        </div>
    </div>
    
    <div class="audit-accessibilite-box">
        <h2><?php _e('Détails des problèmes', 'audit-accessibilite'); ?></h2>
        
        <div class="audit-accessibilite-tabs">
            <div class="audit-accessibilite-tab-buttons">
                <?php if (isset($results['rgaa_issues']) && !empty($results['rgaa_issues'])) : ?>
                <button type="button" class="audit-accessibilite-tab-button active" data-tab="audit-rgaa-tab">
                    <?php _e('Accessibilité (RGAA)', 'audit-accessibilite'); ?>
                </button>
                <?php endif; ?>
                
                <?php if (isset($results['seo_issues']) && !empty($results['seo_issues'])) : ?>
                <button type="button" class="audit-accessibilite-tab-button" data-tab="audit-seo-tab">
                    <?php _e('SEO', 'audit-accessibilite'); ?>
                </button>
                <?php endif; ?>
                
                <?php if (isset($results['performance_issues']) && !empty($results['performance_issues'])) : ?>
                <button type="button" class="audit-accessibilite-tab-button" data-tab="audit-performance-tab">
                    <?php _e('Performance', 'audit-accessibilite'); ?>
                </button>
                <?php endif; ?>
                
                <?php if (isset($results['security_issues']) && !empty($results['security_issues'])) : ?>
                <button type="button" class="audit-accessibilite-tab-button" data-tab="audit-security-tab">
                    <?php _e('Sécurité', 'audit-accessibilite'); ?>
                </button>
                <?php endif; ?>
            </div>
            
            <div class="audit-accessibilite-tab-content-wrapper">
                <?php if (isset($results['rgaa_issues']) && !empty($results['rgaa_issues'])) : ?>
                <div id="audit-rgaa-tab" class="audit-accessibilite-tab-content active">
                    <h3><?php _e('Problèmes d\'accessibilité', 'audit-accessibilite'); ?></h3>
                    
                    <table class="widefat audit-accessibilite-issues-table">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Description', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Localisation', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Priorité', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Statut', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Solution', 'audit-accessibilite'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['rgaa_issues'] as $issue) : ?>
                            <tr>
                                <td><?php echo esc_html($issue['id']); ?></td>
                                <td><?php echo esc_html($issue['description']); ?></td>
                                <td>
                                    <?php 
                                    $location = esc_html($issue['location']);
                                    if (filter_var($location, FILTER_VALIDATE_URL)) {
                                        echo '<a href="' . esc_url($location) . '" target="_blank" rel="noopener noreferrer">' . $location . '</a>';
                                    } else {
                                        echo $location;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="audit-accessibilite-priority audit-accessibilite-priority-<?php echo esc_attr($issue['priority']); ?>">
                                        <?php echo esc_html($issue['priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($issue['status']); ?></td>
                                <td><?php echo esc_html($issue['solution']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if (isset($results['seo_issues']) && !empty($results['seo_issues'])) : ?>
                <div id="audit-seo-tab" class="audit-accessibilite-tab-content <?php echo (!isset($results['rgaa_issues']) || empty($results['rgaa_issues'])) ? 'active' : ''; ?>">
                    <h3><?php _e('Problèmes de référencement (SEO)', 'audit-accessibilite'); ?></h3>
                    
                    <table class="widefat audit-accessibilite-issues-table">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Description', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Localisation', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Priorité', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Statut', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Solution', 'audit-accessibilite'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['seo_issues'] as $issue) : ?>
                            <tr>
                                <td><?php echo esc_html($issue['id']); ?></td>
                                <td><?php echo esc_html($issue['description']); ?></td>
                                <td>
                                    <?php 
                                    $location = esc_html($issue['location']);
                                    if (filter_var($location, FILTER_VALIDATE_URL)) {
                                        echo '<a href="' . esc_url($location) . '" target="_blank" rel="noopener noreferrer">' . $location . '</a>';
                                    } else {
                                        echo $location;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="audit-accessibilite-priority audit-accessibilite-priority-<?php echo esc_attr($issue['priority']); ?>">
                                        <?php echo esc_html($issue['priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($issue['status']); ?></td>
                                <td><?php echo esc_html($issue['solution']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if (isset($results['performance_issues']) && !empty($results['performance_issues'])) : ?>
                <div id="audit-performance-tab" class="audit-accessibilite-tab-content">
                    <h3><?php _e('Problèmes de performance', 'audit-accessibilite'); ?></h3>
                    
                    <table class="widefat audit-accessibilite-issues-table">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Description', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Localisation', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Priorité', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Statut', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Solution', 'audit-accessibilite'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (isset($results['performance_issues']) && isset($results['performance_issues']['results']) && is_array($results['performance_issues']['results'])) :
                                foreach ($results['performance_issues']['results'] as $issue) : 
                                    if (is_array($issue)) :
                            ?>
                            <tr>
                                <td><?php echo isset($issue['id']) ? esc_html($issue['id']) : 'N/A'; ?></td>
                                <td><?php echo isset($issue['description']) ? esc_html($issue['description']) : 'N/A'; ?></td>
                                <td>
                                    <?php 
                                    if (isset($issue['location'])) {
                                        $location = esc_html($issue['location']);
                                        if (filter_var($location, FILTER_VALIDATE_URL)) {
                                            echo '<a href="' . esc_url($location) . '" target="_blank" rel="noopener noreferrer">' . $location . '</a>';
                                        } else {
                                            echo $location;
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if (isset($issue['priority'])) {
                                        echo '<span class="audit-accessibilite-priority audit-accessibilite-priority-' . esc_attr($issue['priority']) . '">';
                                        echo esc_html($issue['priority']); 
                                        echo '</span>';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td><?php echo isset($issue['status']) ? esc_html($issue['status']) : 'N/A'; ?></td>
                                <td><?php echo isset($issue['solution']) ? esc_html($issue['solution']) : 'N/A'; ?></td>
                            </tr>
                            <?php 
                                    else:
                                        echo '<tr><td colspan="6">Format de données invalide</td></tr>';
                                    endif;
                                endforeach;
                            else :
                            ?>
                            <tr>
                                <td colspan="6"><?php _e('Aucun problème de performance détecté.', 'audit-accessibilite'); ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if (isset($results['security_issues']) && !empty($results['security_issues'])) : ?>
                <div id="audit-security-tab" class="audit-accessibilite-tab-content">
                    <h3><?php _e('Problèmes de sécurité', 'audit-accessibilite'); ?></h3>
                    
                    <table class="widefat audit-accessibilite-issues-table">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Description', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Localisation', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Priorité', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Statut', 'audit-accessibilite'); ?></th>
                                <th><?php _e('Solution', 'audit-accessibilite'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['security_issues'] as $issue) : ?>
                            <tr>
                                <td><?php echo esc_html($issue['id']); ?></td>
                                <td><?php echo esc_html($issue['description']); ?></td>
                                <td>
                                    <?php 
                                    $location = esc_html($issue['location']);
                                    if (filter_var($location, FILTER_VALIDATE_URL)) {
                                        echo '<a href="' . esc_url($location) . '" target="_blank" rel="noopener noreferrer">' . $location . '</a>';
                                    } else {
                                        echo $location;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="audit-accessibilite-priority audit-accessibilite-priority-<?php echo esc_attr($issue['priority']); ?>">
                                        <?php echo esc_html($issue['priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($issue['status']); ?></td>
                                <td><?php echo esc_html($issue['solution']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if (!isset($results['rgaa_issues']) && !isset($results['seo_issues']) && !isset($results['performance_issues']) && !isset($results['security_issues'])) : ?>
                <div class="notice notice-warning">
                    <p><?php _e('Aucun problème n\'a été détecté dans cet audit.', 'audit-accessibilite'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
(function($) {
    // Gestion des onglets
    $('.audit-accessibilite-tab-button').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Activer l'onglet sélectionné
        $('.audit-accessibilite-tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Afficher le contenu correspondant
        $('.audit-accessibilite-tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Gestion de l'export PDF
    $('.audit-accessibilite-export-pdf').on('click', function() {
        var auditId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_accessibility_audit_pdf',
                nonce: audit_accessibilite_ajax.nonce,
                audit_id: auditId
            },
            success: function(response) {
                if (response.success && response.data.download_url) {
                    window.location.href = response.data.download_url;
                } else {
                    alert(response.data.message || 'Une erreur est survenue lors de l\'export PDF.');
                }
            },
            error: function() {
                alert('Une erreur est survenue lors de la connexion au serveur.');
            }
        });
    });
    
    // Gestion de l'export CSV
    $('.audit-accessibilite-export-csv').on('click', function() {
        var auditId = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'export_accessibility_audit_csv',
                nonce: audit_accessibilite_ajax.nonce,
                audit_id: auditId
            },
            success: function(response) {
                if (response.success && response.data.download_url) {
                    window.location.href = response.data.download_url;
                } else {
                    alert(response.data.message || 'Une erreur est survenue lors de l\'export CSV.');
                }
            },
            error: function() {
                alert('Une erreur est survenue lors de la connexion au serveur.');
            }
        });
    });
})(jQuery);
</script>

<style>
.audit-accessibilite-back-link {
    margin-bottom: 20px;
}

.audit-accessibilite-report-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.audit-accessibilite-tab-buttons {
    margin-bottom: 15px;
    border-bottom: 1px solid #ccd0d4;
    padding-bottom: 0;
}

.audit-accessibilite-tab-button {
    padding: 10px 15px;
    border: 1px solid #ccd0d4;
    border-bottom: none;
    background: #f8f9fa;
    cursor: pointer;
    margin-right: 5px;
    border-radius: 3px 3px 0 0;
}

.audit-accessibilite-tab-button.active {
    background: #ffffff;
    border-bottom: 1px solid #ffffff;
    margin-bottom: -1px;
}

.audit-accessibilite-tab-content {
    display: none;
    padding: 20px;
    background: #ffffff;
    border: 1px solid #ccd0d4;
    border-top: none;
}

.audit-accessibilite-tab-content.active {
    display: block;
}

.audit-accessibilite-priority {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 0.9em;
}

.audit-accessibilite-priority-high {
    background-color: #f44336;
    color: #ffffff;
}

.audit-accessibilite-priority-medium {
    background-color: #ff9800;
    color: #ffffff;
}

.audit-accessibilite-priority-low {
    background-color: #4caf50;
    color: #ffffff;
}
</style> 