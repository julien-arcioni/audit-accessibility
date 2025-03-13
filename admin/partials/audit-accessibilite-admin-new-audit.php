<?php
/**
 * Affiche le formulaire pour lancer un nouvel audit
 *
 * @since      1.0.0
 */

// Empêcher l'accès direct
if (!defined('WPINC')) {
    die;
}

// Récupérer les options
$options = get_option('audit_accessibilite_options', array());

$default_options = array(
    'rgaa_criteria' => true,
    'seo_check' => false,
    'performance_check' => false,
    'security_check' => false,
    'max_pages_to_audit' => 10,
    'audit_depth' => 2,
    'email_notifications' => false,
    'notification_email' => get_option('admin_email'),
);
$options = wp_parse_args($options, $default_options);
?>

<div class="wrap audit-accessibilite-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="audit-accessibilite-box audit-accessibilite-new-audit">
        <h2><?php _e('Configurer un nouvel audit', 'audit-accessibilite'); ?></h2>
        
        <div id="audit-accessibilite-form" class="audit-accessibilite-form">
            <div class="audit-accessibilite-loading" style="display: none;">
                <div class="spinner is-active"></div>
                <p><?php _e('Audit en cours... Cette opération peut prendre plusieurs minutes selon la taille du site.', 'audit-accessibilite'); ?></p>
            </div>
            
            <div id="audit-accessibilite-error" class="notice notice-error" style="display: none;">
                <p></p>
            </div>
            
            <form id="audit-accessibilite-options">
                <?php wp_nonce_field('audit_accessibilite_new_audit', 'audit_accessibilite_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="audit_target"><?php _e('Cible de l\'audit', 'audit-accessibilite'); ?></label>
                        </th>
                        <td>
                            <select name="audit_target" id="audit_target">
                                <option value="site"><?php _e('Site entier', 'audit-accessibilite'); ?></option>
                                <option value="page"><?php _e('Page spécifique', 'audit-accessibilite'); ?></option>
                            </select>
                            <p class="description"><?php _e('L\'audit du site entier analysera toutes les pages accessibles.', 'audit-accessibilite'); ?></p>
                        </td>
                    </tr>
                    
                    <tr id="specific_url_row" style="display: none;">
                        <th scope="row">
                            <label for="specific_url"><?php _e('URL à analyser', 'audit-accessibilite'); ?></label>
                        </th>
                        <td>
                            <input type="url" name="specific_url" id="specific_url" class="regular-text" value="<?php echo esc_url(home_url()); ?>" />
                            <p class="description"><?php _e('Entrez l\'URL complète de la page à auditer.', 'audit-accessibilite'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Critères à vérifier', 'audit-accessibilite'); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><?php _e('Critères à vérifier', 'audit-accessibilite'); ?></legend>
                                
                                <label for="check_rgaa">
                                    <input type="checkbox" name="check_rgaa" id="check_rgaa" value="1" <?php checked(isset($options['rgaa_criteria']) && $options['rgaa_criteria']); ?> />
                                    <?php _e('Accessibilité (RGAA 4.1)', 'audit-accessibilite'); ?>
                                </label><br>
                                
                                <label for="check_seo">
                                    <input type="checkbox" name="check_seo" id="check_seo" value="1" <?php checked(isset($options['seo_check']) && $options['seo_check']); ?> />
                                    <?php _e('Référencement (SEO)', 'audit-accessibilite'); ?>
                                </label><br>
                                
                                <label for="check_performance">
                                    <input type="checkbox" name="check_performance" id="check_performance" value="1" <?php checked(isset($options['performance_check']) && $options['performance_check']); ?> />
                                    <?php _e('Performance', 'audit-accessibilite'); ?>
                                </label><br>
                                
                                <label for="check_security">
                                    <input type="checkbox" name="check_security" id="check_security" value="1" <?php checked(isset($options['security_check']) && $options['security_check']); ?> />
                                    <?php _e('Sécurité', 'audit-accessibilite'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Options avancées', 'audit-accessibilite'); ?></th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text"><?php _e('Options avancées', 'audit-accessibilite'); ?></legend>
                                
                                <label for="crawl_depth">
                                    <?php _e('Profondeur d\'analyse :', 'audit-accessibilite'); ?>
                                    <select name="crawl_depth" id="crawl_depth">
                                        <option value="1" <?php selected($options['audit_depth'], 1); ?>><?php _e('1 niveau (page d\'accueil uniquement)', 'audit-accessibilite'); ?></option>
                                        <option value="2" <?php selected($options['audit_depth'], 2); ?>><?php _e('2 niveaux (page d\'accueil + liens directs)', 'audit-accessibilite'); ?></option>
                                        <option value="3" <?php selected($options['audit_depth'], 3); ?>><?php _e('3 niveaux (analyse approfondie)', 'audit-accessibilite'); ?></option>
                                    </select>
                                </label><br>
                                
                                <label for="max_pages">
                                    <?php _e('Nombre maximum de pages à analyser :', 'audit-accessibilite'); ?>
                                    <input type="number" name="max_pages" id="max_pages" value="<?php echo intval($options['max_pages_to_audit']); ?>" min="1" max="100" class="small-text" />
                                </label><br>
                                
                                <label for="save_report">
                                    <input type="checkbox" name="save_report" id="save_report" value="1" checked />
                                    <?php _e('Sauvegarder le rapport complet', 'audit-accessibilite'); ?>
                                </label><br>
                                
                                <label for="email_report">
                                    <input type="checkbox" name="email_report" id="email_report" value="1" <?php checked(isset($options['email_notifications']) && $options['email_notifications']); ?> />
                                    <?php _e('Envoyer le rapport par email', 'audit-accessibilite'); ?>
                                </label>
                                
                                <div id="email_field" style="display: <?php echo (isset($options['email_notifications']) && $options['email_notifications']) ? 'block' : 'none'; ?>; margin-top: 10px;">
                                    <input type="email" name="email_address" id="email_address" class="regular-text" 
                                        value="<?php echo esc_attr(isset($options['notification_email']) ? $options['notification_email'] : get_option('admin_email')); ?>" />
                                </div>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <div class="audit-accessibilite-submit">
                    <button type="button" id="start-audit" class="button button-primary audit-accessibilite-start-audit">
                        <?php _e('Lancer l\'audit', 'audit-accessibilite'); ?>
                    </button>
                    <span class="spinner"></span>
                </div>
            </form>
        </div>
    </div>
    
    <div id="audit-accessibilite-results" class="audit-accessibilite-box audit-accessibilite-results" style="display: none;">
        <h2><?php _e('Résultats de l\'audit', 'audit-accessibilite'); ?></h2>
        
        <div class="audit-accessibilite-stats">
            <div class="audit-accessibilite-stat-box">
                <div class="audit-accessibilite-stat-value audit-accessibilite-total-issues">0</div>
                <div class="audit-accessibilite-stat-label"><?php _e('Problèmes détectés', 'audit-accessibilite'); ?></div>
            </div>
            
            <div class="audit-accessibilite-stat-box">
                <div class="audit-accessibilite-stat-value audit-accessibilite-open-issues">0</div>
                <div class="audit-accessibilite-stat-label"><?php _e('Problèmes ouverts', 'audit-accessibilite'); ?></div>
            </div>
            
            <div class="audit-accessibilite-stat-box">
                <div class="audit-accessibilite-stat-value audit-accessibilite-high-priority">0</div>
                <div class="audit-accessibilite-stat-label"><?php _e('Priorité élevée', 'audit-accessibilite'); ?></div>
            </div>
            
            <div class="audit-accessibilite-stat-box">
                <div class="audit-accessibilite-stat-value audit-accessibilite-score">0%</div>
                <div class="audit-accessibilite-stat-label"><?php _e('Score global', 'audit-accessibilite'); ?></div>
            </div>
        </div>
        
        <div class="audit-accessibilite-tabs">
            <div class="audit-accessibilite-tab-buttons">
                <button type="button" class="audit-accessibilite-tab-button active" data-tab="audit-rgaa-tab">
                    <?php _e('Accessibilité (RGAA)', 'audit-accessibilite'); ?>
                </button>
                <button type="button" class="audit-accessibilite-tab-button" data-tab="audit-seo-tab">
                    <?php _e('SEO', 'audit-accessibilite'); ?>
                </button>
                <button type="button" class="audit-accessibilite-tab-button" data-tab="audit-performance-tab">
                    <?php _e('Performance', 'audit-accessibilite'); ?>
                </button>
                <button type="button" class="audit-accessibilite-tab-button" data-tab="audit-security-tab">
                    <?php _e('Sécurité', 'audit-accessibilite'); ?>
                </button>
            </div>
            
            <div class="audit-accessibilite-tab-content active" id="audit-rgaa-tab">
                <div class="audit-accessibilite-filters">
                    <div class="audit-accessibilite-filter">
                        <label for="rgaa-filter"><?php _e('Filtrer les résultats :', 'audit-accessibilite'); ?></label>
                        <input type="text" id="rgaa-filter" class="audit-accessibilite-filter-input" placeholder="<?php esc_attr_e('Rechercher...', 'audit-accessibilite'); ?>">
                    </div>
                    <div class="audit-accessibilite-filter">
                        <select id="rgaa-priority-filter">
                            <option value=""><?php _e('Toutes les priorités', 'audit-accessibilite'); ?></option>
                            <option value="high"><?php _e('Priorité haute', 'audit-accessibilite'); ?></option>
                            <option value="medium"><?php _e('Priorité moyenne', 'audit-accessibilite'); ?></option>
                            <option value="low"><?php _e('Priorité basse', 'audit-accessibilite'); ?></option>
                        </select>
                    </div>
                </div>
                
                <table class="widefat audit-accessibilite-results-table">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'audit-accessibilite'); ?></th>
                            <th><?php _e('Problème', 'audit-accessibilite'); ?></th>
                            <th><?php _e('Emplacement', 'audit-accessibilite'); ?></th>
                            <th><?php _e('Priorité', 'audit-accessibilite'); ?></th>
                            <th><?php _e('Statut', 'audit-accessibilite'); ?></th>
                            <th><?php _e('Actions', 'audit-accessibilite'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6"><?php _e('Aucun résultat disponible', 'audit-accessibilite'); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="audit-accessibilite-pagination">
                    <!-- La pagination sera ajoutée dynamiquement en JavaScript -->
                </div>
            </div>
            
            <div class="audit-accessibilite-tab-content" id="audit-seo-tab">
                <p><?php _e('Les résultats SEO seront affichés ici.', 'audit-accessibilite'); ?></p>
                <!-- Contenu similaire au tableau RGAA -->
            </div>
            
            <div class="audit-accessibilite-tab-content" id="audit-performance-tab">
                <p><?php _e('Les résultats de performance seront affichés ici.', 'audit-accessibilite'); ?></p>
                <!-- Contenu similaire au tableau RGAA -->
            </div>
            
            <div class="audit-accessibilite-tab-content" id="audit-security-tab">
                <p><?php _e('Les résultats de sécurité seront affichés ici.', 'audit-accessibilite'); ?></p>
                <!-- Contenu similaire au tableau RGAA -->
            </div>
        </div>
        
        <div class="audit-accessibilite-export">
            <h3><?php _e('Exporter les résultats', 'audit-accessibilite'); ?></h3>
            <button type="button" class="button audit-accessibilite-export-pdf">
                <?php _e('Exporter en PDF', 'audit-accessibilite'); ?>
            </button>
            <button type="button" class="button audit-accessibilite-export-csv">
                <?php _e('Exporter en CSV', 'audit-accessibilite'); ?>
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=audit-accessibilite-rapports')); ?>" class="button">
                <?php _e('Voir tous les rapports', 'audit-accessibilite'); ?>
            </a>
        </div>
    </div>
</div>

<script>
(function($) {
    console.log('Script d\'audit d\'accessibilité chargé');

    if ($('#audit_target').length === 0) {
        console.error('Erreur: Le sélecteur #audit_target n\'existe pas');
    }

    if ($('#specific_url_row').length === 0) {
        console.error('Erreur: Le sélecteur #specific_url_row n\'existe pas');
    }

    // Gestion de l'affichage du champ URL spécifique
    $('#audit_target').on('change', function() {
        console.log('Changement de cible d\'audit: ' + $(this).val());
        if ($(this).val() === 'page') {
            $('#specific_url_row').show();
        } else {
            $('#specific_url_row').hide();
        }
    });
    
    if ($('#audit_target').val() === 'page') {
        $('#specific_url_row').show();
    }
    
    setTimeout(function() {
        if ($('#audit_target').val() === 'page') {
            $('#specific_url_row').show();
        }
    }, 500);
    
    // Gestion de l'affichage du champ email
    $('#email_report').on('change', function() {
        if ($(this).is(':checked')) {
            $('#email_field').show();
        } else {
            $('#email_field').hide();
        }
    });
    
    // Vérifier l'état initial des cases à cocher
    if ($('#email_report').is(':checked')) {
        $('#email_field').show();
    }
    
    // Gérer les onglets de résultats
    $('.audit-accessibilite-tab-button').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Activer l'onglet sélectionné
        $('.audit-accessibilite-tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Afficher le contenu correspondant
        $('.audit-accessibilite-tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });
    
    // Lancer l'audit
    $('#start-audit').on('click', function() {
        console.log('Démarrage de l\'audit');
        
        // Masquer les erreurs précédentes
        $('#audit-accessibilite-error').hide();
        
        // Afficher la barre de chargement
        $('.audit-accessibilite-form .spinner').addClass('is-active');
        $('.audit-accessibilite-loading').show();
        
        // Désactiver le bouton pendant l'exécution
        $(this).prop('disabled', true);
        
        // Récupérer les données du formulaire
        var formData = $('#audit-accessibilite-options').serialize();
        console.log('Données du formulaire: ', formData);
        
        // Requête AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'run_accessibility_audit',
                nonce: $('#audit_accessibilite_nonce').val(),
                form_data: formData
            },
            success: function(response) {
                console.log('Réponse reçue: ', response);
                
                $('.audit-accessibilite-form .spinner').removeClass('is-active');
                $('.audit-accessibilite-loading').hide();
                
                $('#start-audit').prop('disabled', false);
                
                if (response.success) {
                    displayAuditResults(response.data);
                } else {
                    $('#audit-accessibilite-error').show().find('p').text(
                        response.data && response.data.message 
                            ? response.data.message 
                            : '<?php _e('Une erreur inconnue est produite lors de laudit.', 'audit-accessibilite'); ?>'
                    );
                    console.error('Erreur dans la réponse: ', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX: ', {xhr: xhr, status: status, error: error});
                
                // Masquer le chargement
                $('.audit-accessibilite-form .spinner').removeClass('is-active');
                $('.audit-accessibilite-loading').hide();
                
                // Réactiver le bouton
                $('#start-audit').prop('disabled', false);
                
                // Afficher l'erreur
                $('#audit-accessibilite-error').show().find('p').text(
                    '<?php _e('Une erreur est apparue pendant cette audit. Veuillez réessayer.', 'audit-accessibilite'); ?>'
                );
            }
        });
    });
    
    // Fonction pour afficher les résultats
    function displayAuditResults(data) {
        console.log('Affichage des résultats: ', data);
        
        $('#audit-accessibilite-form').hide();
        $('#audit-accessibilite-results').show();
        
        // Remplir les statistiques
        $('.audit-accessibilite-total-issues').text(data.total_issues || 0);
        $('.audit-accessibilite-open-issues').text(data.open_issues || 0);
        $('.audit-accessibilite-high-priority').text(data.high_priority || 0);
        $('.audit-accessibilite-score').text((data.score || 0) + '%');
        
        // Remplir les tableaux de résultats pour chaque type d'audit
        if (data.rgaa_issues && data.rgaa_issues.length > 0) {
            fillResultsTable('rgaa', data.rgaa_issues);
        }
        
        if (data.seo_issues && data.seo_issues.length > 0) {
            fillResultsTable('seo', data.seo_issues);
        }
        
        if (data.performance_issues && data.performance_issues.length > 0) {
            fillResultsTable('performance', data.performance_issues);
        }
        
        if (data.security_issues && data.security_issues.length > 0) {
            fillResultsTable('security', data.security_issues);
        }
    }
    
    // Fonction pour remplir un tableau de résultats
    function fillResultsTable(type, issues) {
        var tableId = '#audit-' + type + '-tab table tbody';
        var tableContent = '';
        
        $.each(issues, function(index, issue) {
            var priorityClass = 'audit-accessibilite-priority-' + issue.priority;
            
            tableContent += '<tr>';
            tableContent += '<td>' + issue.id + '</td>';
            tableContent += '<td>' + issue.description + '</td>';
            tableContent += '<td>' + issue.location + '</td>';
            tableContent += '<td><span class="audit-accessibilite-priority ' + priorityClass + '">' + issue.priority + '</span></td>';
            tableContent += '<td>' + issue.status + '</td>';
            tableContent += '<td>';
            tableContent += '<button type="button" class="button button-small" data-issue-id="' + issue.id + '">';
            tableContent += '<?php _e('Détails', 'audit-accessibilite'); ?>';
            tableContent += '</button>';
            tableContent += '</td>';
            tableContent += '</tr>';
        });
        
        $(tableId).html(tableContent);
    }
})(jQuery);
</script>

<style>
.audit-accessibilite-new-audit .spinner {
    float: none;
    margin-top: 0;
    margin-left: 10px;
    vertical-align: middle;
}

.audit-accessibilite-loading {
    padding: 20px;
    background: #f8f9fa;
    border-left: 4px solid #2271b1;
    margin-bottom: 20px;
}

.audit-accessibilite-loading .spinner {
    float: none;
    display: inline-block;
    margin: 0 10px 0 0;
    vertical-align: middle;
}

.audit-accessibilite-submit {
    margin-top: 20px;
}

.audit-accessibilite-tabs {
    margin-top: 20px;
}

.audit-accessibilite-tab-buttons {
    margin-bottom: 15px;
    border-bottom: 1px solid #ccd0d4;
    padding-bottom: 0;
}

.audit-accessibilite-tab-button {
    padding: 10px 15px;
    background: #f8f9fa;
    border: 1px solid #ccd0d4;
    border-bottom: none;
    cursor: pointer;
    margin-right: 5px;
    margin-bottom: -1px;
}

.audit-accessibilite-tab-button.active {
    background: #fff;
    border-bottom: 1px solid #fff;
}

.audit-accessibilite-tab-content {
    display: none;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-top: none;
}

.audit-accessibilite-tab-content.active {
    display: block;
}

.audit-accessibilite-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    align-items: center;
}

.audit-accessibilite-filter {
    display: flex;
    align-items: center;
    gap: 5px;
}

.audit-accessibilite-export {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ccd0d4;
}

.audit-accessibilite-export .button {
    margin-right: 10px;
}

/* Priorités dans le tableau des résultats */
.audit-accessibilite-priority {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.audit-accessibilite-priority-high {
    background-color: #dc3545;
    color: #fff;
}

.audit-accessibilite-priority-medium {
    background-color: #ffc107;
    color: #000;
}

.audit-accessibilite-priority-low {
    background-color: #28a745;
    color: #fff;
}
</style> 