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
                    if (response.data && response.data.audit_id) {
                        var baseUrl = '<?php echo admin_url('admin.php?page=audit-accessibilite-rapport'); ?>';
                        window.location.href = baseUrl + '&id=' + response.data.audit_id;
                    } else {
                        window.location.href = '<?php echo esc_url(admin_url('admin.php?page=audit-accessibilite-rapports')); ?>';
                    }
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
</style> 