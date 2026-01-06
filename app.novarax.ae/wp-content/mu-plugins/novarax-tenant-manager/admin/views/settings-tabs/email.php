<?php
/**
 * Email Settings Tab
 * Location: /wp-content/mu-plugins/novarax-tenant-manager/admin/views/settings-tabs/email.php
 */

if (!defined('ABSPATH')) exit;
?>

<table class="form-table" role="presentation">
    
    <!-- From Email -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_from_email">
                <?php _e('From Email Address', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="email" 
                   id="novarax_tm_from_email" 
                   name="novarax_tm_from_email" 
                   value="<?php echo esc_attr(get_option('novarax_tm_from_email', get_option('admin_email'))); ?>" 
                   class="regular-text">
            <p class="description">
                <?php _e('Email address used in the "From" field for all NovaRax emails.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- From Name -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_from_name">
                <?php _e('From Name', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="text" 
                   id="novarax_tm_from_name" 
                   name="novarax_tm_from_name" 
                   value="<?php echo esc_attr(get_option('novarax_tm_from_name', get_bloginfo('name'))); ?>" 
                   class="regular-text">
            <p class="description">
                <?php _e('Name displayed in the "From" field for all NovaRax emails.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Email Logo -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_email_logo">
                <?php _e('Email Logo URL', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="url" 
                   id="novarax_tm_email_logo" 
                   name="novarax_tm_email_logo" 
                   value="<?php echo esc_attr(get_option('novarax_tm_email_logo', '')); ?>" 
                   class="regular-text">
            <button type="button" class="button" id="upload_email_logo_button">
                <?php _e('Upload Logo', 'novarax-tenant-manager'); ?>
            </button>
            <p class="description">
                <?php _e('Logo displayed at the top of email templates. Recommended size: 200x60px', 'novarax-tenant-manager'); ?>
            </p>
            <?php if (get_option('novarax_tm_email_logo')) : ?>
                <div style="margin-top: 10px;">
                    <img src="<?php echo esc_url(get_option('novarax_tm_email_logo')); ?>" 
                         style="max-width: 200px; height: auto; border: 1px solid #ddd; padding: 5px;">
                </div>
            <?php endif; ?>
        </td>
    </tr>
    
    <!-- Email Primary Color -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_email_primary_color">
                <?php _e('Primary Brand Color', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="color" 
                   id="novarax_tm_email_primary_color" 
                   name="novarax_tm_email_primary_color" 
                   value="<?php echo esc_attr(get_option('novarax_tm_email_primary_color', '#3ECF8E')); ?>">
            <p class="description">
                <?php _e('Primary color used in email templates for buttons and accents.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
</table>

<script>
jQuery(document).ready(function($) {
    // Media uploader for email logo
    var mediaUploader;
    
    $('#upload_email_logo_button').on('click', function(e) {
        e.preventDefault();
        
        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Create the media frame
        mediaUploader = wp.media({
            title: '<?php _e('Choose Email Logo', 'novarax-tenant-manager'); ?>',
            button: {
                text: '<?php _e('Use this logo', 'novarax-tenant-manager'); ?>'
            },
            multiple: false
        });
        
        // When an image is selected, run a callback
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#novarax_tm_email_logo').val(attachment.url);
        });
        
        // Open the uploader dialog
        mediaUploader.open();
    });
});
</script>