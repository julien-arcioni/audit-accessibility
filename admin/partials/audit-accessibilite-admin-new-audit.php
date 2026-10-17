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
                                
                                <?php // TODO : désactivée ?>
                                <?php /* <label for="check_seo">
                                    <input type="checkbox" name="check_seo" id="check_seo" value="1" <?php checked(isset($options['seo_check']) && $options['seo_check']); ?> />
                                    <?php _e('Référencement (SEO)', 'audit-accessibilite'); ?>
                                </label><br> */ ?>
                                
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

    // Gestion de l'affichage du champ email
    $('#email_report').on('change', function() {
        if ($(this).is(':checked')) {
            $('#email_field').show();
        } else {
            $('#email_field').hide();
        }
    });

    // Lancement de l'audit
    $('#start-audit').on('click', function() {
        var button = $(this);
        var form = $('#audit-accessibilite-options');
        var loadingDiv = $('.audit-accessibilite-loading');
        var errorDiv = $('#audit-accessibilite-error');
        
        // Validation
        var hasCheck = false;
        form.find('input[type="checkbox"][name^="check_"]').each(function() {
            if ($(this).is(':checked')) {
                hasCheck = true;
                return false;
            }
        });
        
        if (!hasCheck) {
            alert('Veuillez sélectionner au moins un critère à vérifier.');
            return;
        }
        
        // Vérification de l'URL si page spécifique
        if ($('#audit_target').val() === 'page') {
            var url = $('#specific_url').val();
            if (!url || !isValidUrl(url)) {
                alert('Veuillez entrer une URL valide.');
                $('#specific_url').focus();
                return;
            }
        }
        
        // Afficher le loading
        button.prop('disabled', true);
        loadingDiv.show();
        errorDiv.hide();
        
        // Préparer les données
        var formData = {
            action: 'audit_accessibilite_run_audit',
            nonce: $('#audit_accessibilite_nonce').val(),
            audit_target: $('#audit_target').val(),
            specific_url: $('#specific_url').val(),
            check_rgaa: $('#check_rgaa').is(':checked') ? 1 : 0,
            // check_seo: $('#check_seo').is(':checked') ? 1 : 0, // Temporairement désactivé
            check_performance: $('#check_performance').is(':checked') ? 1 : 0,
            check_security: $('#check_security').is(':checked') ? 1 : 0,
            save_report: $('#save_report').is(':checked') ? 1 : 0,
            email_report: $('#email_report').is(':checked') ? 1 : 0,
            email_address: $('#email_address').val()
        };
        
        console.log('Données envoyées:', formData);
        
        // Envoyer la requête AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            timeout: 300000, // 5 minutes
            success: function(response) {
                console.log('Réponse reçue:', response);
                
                if (response.success) {
                    // Rediriger vers la page des rapports ou afficher le résultat
                    if (response.data.redirect_url) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        alert('Audit terminé avec succès !');
                        location.reload();
                    }
                } else {
                    errorDiv.find('p').text(response.data.message || 'Erreur inconnue');
                    errorDiv.show();
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', status, error);
                var message = 'Erreur de communication avec le serveur.';
                
                if (status === 'timeout') {
                    message = 'L\'audit a pris trop de temps. Il se peut qu\'il soit encore en cours d\'exécution en arrière-plan.';
                }
                
                errorDiv.find('p').text(message);
                errorDiv.show();
            },
            complete: function() {
                button.prop('disabled', false);
                loadingDiv.hide();
            }
        });
    });
    
    // Fonction utilitaire pour valider les URLs
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
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
    border-left: 4px solid var(--wp-admin-theme-color, #2271b1);
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
</style> 