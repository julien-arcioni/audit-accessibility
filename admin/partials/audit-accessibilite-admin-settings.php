<?php
/**
 * Affiche la page des paramètres de l'extension
 *
 * @since      1.0.0
 */

// Empêcher l'accès direct
if (!defined('WPINC')) {
    die;
}

// Récupérer les options
$options = get_option('audit_accessibilite_options', array());

// Valeurs par défaut
$default_options = array(
    'rgaa_criteria' => true,
    'seo_check' => false,
    'performance_check' => false,
    'security_check' => false,
    'enable_scheduled_audits' => false,
    'scheduled_frequency' => 'weekly',
    'email_notifications' => false,
    'notification_email' => get_option('admin_email'),
    'save_html_snapshots' => false,
    'custom_exclusions' => '',
    'last_audit_date' => '',
);

// Fusionner avec les valeurs par défaut
$options = wp_parse_args($options, $default_options);

// Traitement du formulaire
$updated = false;
$reset_data = false;

if (isset($_POST['audit_accessibilite_settings_submit']) && check_admin_referer('audit_accessibilite_settings')) {
    // Récupérer et nettoyer les valeurs
    $options['rgaa_criteria'] = isset($_POST['rgaa_criteria']) ? 1 : 0;
    $options['seo_check'] = isset($_POST['seo_check']) ? 1 : 0;
    $options['performance_check'] = isset($_POST['performance_check']) ? 1 : 0;
    $options['security_check'] = isset($_POST['security_check']) ? 1 : 0;
    $options['enable_scheduled_audits'] = isset($_POST['enable_scheduled_audits']) ? 1 : 0;
    $options['scheduled_frequency'] = sanitize_text_field($_POST['scheduled_frequency']);
    $options['email_notifications'] = isset($_POST['email_notifications']) ? 1 : 0;
    $options['notification_email'] = sanitize_email($_POST['notification_email']);
    $options['save_html_snapshots'] = isset($_POST['save_html_snapshots']) ? 1 : 0;
    $options['custom_exclusions'] = sanitize_textarea_field($_POST['custom_exclusions']);
    
    // Sauvegarder les options
    update_option('audit_accessibilite_options', $options);
    
    // Planifier ou déplanifier les audits automatiques
    if ($options['enable_scheduled_audits']) {
        if (!wp_next_scheduled('audit_accessibilite_cron_hook')) {
            wp_schedule_event(time(), $options['scheduled_frequency'], 'audit_accessibilite_cron_hook');
        }
    } else {
        $timestamp = wp_next_scheduled('audit_accessibilite_cron_hook');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'audit_accessibilite_cron_hook');
        }
    }
    
    $updated = true;
}

// Traitement de la réinitialisation des données
if (isset($_POST['audit_accessibilite_reset_submit']) && check_admin_referer('audit_accessibilite_reset_data')) {
    global $wpdb;
    
    // Supprimer les options
    delete_option('audit_accessibilite_options');
    delete_option('audit_accessibilite_last_audit');
    
    // Réinitialiser les tables de base de données
    $tables = array(
        $wpdb->prefix . 'audit_accessibilite_audits',
        $wpdb->prefix . 'audit_accessibilite_issues',
        $wpdb->prefix . 'audit_accessibilite_snapshots'
    );
    
    foreach ($tables as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $wpdb->query("TRUNCATE TABLE $table");
        }
    }
    
    // Déplanifier les tâches cron
    $timestamp = wp_next_scheduled('audit_accessibilite_cron_hook');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'audit_accessibilite_cron_hook');
    }
    
    // Réinitialiser les options aux valeurs par défaut
    $options = $default_options;
    update_option('audit_accessibilite_options', $options);
    
    $reset_data = true;
}
?>

<div class="wrap audit-accessibilite-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if ($updated) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Paramètres mis à jour avec succès.', 'audit-accessibilite'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($reset_data) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Toutes les données de l\'extension ont été réinitialisées avec succès.', 'audit-accessibilite'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="audit-accessibilite-box">
        <h2><?php _e('Paramètres d\'audit', 'audit-accessibilite'); ?></h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('audit_accessibilite_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Critères à auditer', 'audit-accessibilite'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php _e('Critères à auditer', 'audit-accessibilite'); ?></legend>
                            
                            <label for="rgaa_criteria">
                                <input type="checkbox" name="rgaa_criteria" id="rgaa_criteria" value="1" <?php checked($options['rgaa_criteria']); ?> />
                                <?php _e('Accessibilité (RGAA 4.1)', 'audit-accessibilite'); ?>
                            </label><br>
                            
                            <label for="seo_check">
                                <input type="checkbox" name="seo_check" id="seo_check" value="1" <?php checked($options['seo_check']); ?> />
                                <?php _e('Référencement (SEO)', 'audit-accessibilite'); ?>
                            </label><br>
                            
                            <label for="performance_check">
                                <input type="checkbox" name="performance_check" id="performance_check" value="1" <?php checked($options['performance_check']); ?> />
                                <?php _e('Performance', 'audit-accessibilite'); ?>
                            </label><br>
                            
                            <label for="security_check">
                                <input type="checkbox" name="security_check" id="security_check" value="1" <?php checked($options['security_check']); ?> />
                                <?php _e('Sécurité', 'audit-accessibilite'); ?>
                            </label>
                            
                            <p class="description"><?php _e('Sélectionnez les types de critères à inclure dans les audits.', 'audit-accessibilite'); ?></p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Exclusions', 'audit-accessibilite'); ?></th>
                    <td>
                        <textarea name="custom_exclusions" id="custom_exclusions" rows="4" cols="50" class="large-text code"><?php echo esc_textarea($options['custom_exclusions']); ?></textarea>
                        <p class="description"><?php _e('URLs à exclure de l\'audit, une par ligne. Vous pouvez utiliser des expressions régulières.', 'audit-accessibilite'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Options avancées', 'audit-accessibilite'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php _e('Options avancées', 'audit-accessibilite'); ?></legend>
                            
                            <label for="save_html_snapshots">
                                <input type="checkbox" name="save_html_snapshots" id="save_html_snapshots" value="1" <?php checked($options['save_html_snapshots']); ?> />
                                <?php _e('Sauvegarder des instantanés HTML des pages auditées', 'audit-accessibilite'); ?>
                            </label>
                            <p class="description"><?php _e('Utile pour vérifier les problèmes même si le site a changé depuis l\'audit.', 'audit-accessibilite'); ?></p>
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <h2><?php _e('Audits planifiés', 'audit-accessibilite'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Planification', 'audit-accessibilite'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php _e('Planification', 'audit-accessibilite'); ?></legend>
                            
                            <label for="enable_scheduled_audits">
                                <input type="checkbox" name="enable_scheduled_audits" id="enable_scheduled_audits" value="1" <?php checked($options['enable_scheduled_audits']); ?> />
                                <?php _e('Activer les audits automatiques planifiés', 'audit-accessibilite'); ?>
                            </label>
                            
                            <div id="scheduled_options" style="margin-top: 10px; <?php echo $options['enable_scheduled_audits'] ? '' : 'display: none;'; ?>">
                                <label for="scheduled_frequency">
                                    <?php _e('Fréquence :', 'audit-accessibilite'); ?>
                                    <select name="scheduled_frequency" id="scheduled_frequency">
                                        <option value="daily" <?php selected($options['scheduled_frequency'], 'daily'); ?>><?php _e('Quotidien', 'audit-accessibilite'); ?></option>
                                        <option value="weekly" <?php selected($options['scheduled_frequency'], 'weekly'); ?>><?php _e('Hebdomadaire', 'audit-accessibilite'); ?></option>
                                        <option value="monthly" <?php selected($options['scheduled_frequency'], 'monthly'); ?>><?php _e('Mensuel', 'audit-accessibilite'); ?></option>
                                    </select>
                                </label>
                            </div>
                            
                            <p class="description">
                                <?php 
                                if ($options['last_audit_date']) {
                                    $date_format = get_option('date_format') . ' ' . get_option('time_format');
                                    $last_audit = date_i18n($date_format, strtotime($options['last_audit_date']));
                                    printf(__('Dernier audit automatique : %s', 'audit-accessibilite'), $last_audit);
                                } else {
                                    _e('Aucun audit automatique n\'a encore été effectué.', 'audit-accessibilite');
                                }
                                ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Notifications', 'audit-accessibilite'); ?></th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text"><?php _e('Notifications', 'audit-accessibilite'); ?></legend>
                            
                            <label for="email_notifications">
                                <input type="checkbox" name="email_notifications" id="email_notifications" value="1" <?php checked($options['email_notifications']); ?> />
                                <?php _e('Recevoir les rapports d\'audit par email', 'audit-accessibilite'); ?>
                            </label>
                            
                            <div id="email_options" style="margin-top: 10px; <?php echo $options['email_notifications'] ? '' : 'display: none;'; ?>">
                                <label for="notification_email">
                                    <?php _e('Adresse email :', 'audit-accessibilite'); ?>
                                    <input type="email" name="notification_email" id="notification_email" value="<?php echo esc_attr($options['notification_email']); ?>" class="regular-text" />
                                </label>
                            </div>
                        </fieldset>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="audit_accessibilite_settings_submit" class="button button-primary" value="<?php _e('Enregistrer les modifications', 'audit-accessibilite'); ?>" />
            </p>
        </form>
    </div>
    
    <div class="audit-accessibilite-box">
        <h2><?php _e('À propos du RGAA 4.1', 'audit-accessibilite'); ?></h2>
        
        <p><?php _e('Le <strong>Référentiel Général d\'Amélioration de l\'Accessibilité (RGAA)</strong> est le standard officiel en matière d\'accessibilité numérique en France.', 'audit-accessibilite'); ?></p>
        
        <p><?php _e('La version 4.1, publiée en 2021, comprend 13 thématiques et 106 critères permettant de vérifier la conformité d\'un site web aux normes internationales d\'accessibilité (WCAG 2.1).', 'audit-accessibilite'); ?></p>
        
        <p><?php _e('Pour en savoir plus, consultez la <a href="https://accessibilite.numerique.gouv.fr/" target="_blank">documentation officielle du RGAA</a>.', 'audit-accessibilite'); ?></p>
    </div>
    
    <div class="audit-accessibilite-box audit-accessibilite-danger-zone">
        <h2><?php _e('Réinitialisation des données', 'audit-accessibilite'); ?></h2>
        
        <p><strong><?php _e('Attention :', 'audit-accessibilite'); ?></strong> <?php _e('Cette action supprimera définitivement toutes les données d\'audit, les rapports et les paramètres de l\'extension.', 'audit-accessibilite'); ?></p>
        
        <form method="post" action="" id="reset-form">
            <?php wp_nonce_field('audit_accessibilite_reset_data'); ?>
            
            <p>
                <label>
                    <input type="checkbox" id="confirm-reset" name="confirm_reset" value="1" />
                    <?php _e('Je confirme vouloir supprimer toutes les données de l\'extension', 'audit-accessibilite'); ?>
                </label>
            </p>
            
            <p class="submit">
                <input type="submit" name="audit_accessibilite_reset_submit" id="reset-submit" class="button button-danger" value="<?php _e('Réinitialiser toutes les données', 'audit-accessibilite'); ?>" disabled />
            </p>
        </form>
    </div>
</div>

<script>
(function($) {
    // Gestion de l'affichage des options de planification
    $('#enable_scheduled_audits').on('change', function() {
        if ($(this).is(':checked')) {
            $('#scheduled_options').show();
        } else {
            $('#scheduled_options').hide();
        }
    });
    
    // Gestion de l'affichage des options de notification
    $('#email_notifications').on('change', function() {
        if ($(this).is(':checked')) {
            $('#email_options').show();
        } else {
            $('#email_options').hide();
        }
    });
    
    // Gestion du bouton de réinitialisation
    $('#confirm-reset').on('change', function() {
        if ($(this).is(':checked')) {
            $('#reset-submit').prop('disabled', false);
        } else {
            $('#reset-submit').prop('disabled', true);
        }
    });
    
    // Confirmation supplémentaire lors de la soumission du formulaire de réinitialisation
    $('#reset-form').on('submit', function(e) {
        if (!confirm('<?php _e("Êtes-vous vraiment sûr de vouloir réinitialiser toutes les données ? Cette action est irréversible.", "audit-accessibilite"); ?>')) {
            e.preventDefault();
            return false;
        }
        return true;
    });
})(jQuery);
</script> 