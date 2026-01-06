<?php
/**
 * Advanced Settings Tab
 * Location: /wp-content/mu-plugins/novarax-tenant-manager/admin/views/settings-tabs/advanced.php
 */

if (!defined('ABSPATH')) exit;
?>

<div class="novarax-warning-box">
    <p>
        <span class="dashicons dashicons-warning"></span>
        <strong><?php _e('Warning:', 'novarax-tenant-manager'); ?></strong> 
        <?php _e('These are advanced settings. Only change these if you know what you\'re doing. Incorrect settings can break your tenant system.', 'novarax-tenant-manager'); ?>
    </p>
</div>

<table class="form-table" role="presentation">
    
    <!-- Debug Mode -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_debug_mode">
                <?php _e('Debug Mode', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <label>
                <input type="checkbox" 
                       id="novarax_tm_debug_mode" 
                       name="novarax_tm_debug_mode" 
                       value="1" 
                       <?php checked(get_option('novarax_tm_debug_mode', '0'), '1'); ?>>
                <?php _e('Enable verbose debugging', 'novarax-tenant-manager'); ?>
            </label>
            <p class="description">
                <?php _e('When enabled, detailed logs will be written for all operations. Disable in production for performance.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Log Level -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_log_level">
                <?php _e('Log Level', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <select id="novarax_tm_log_level" 
                    name="novarax_tm_log_level" 
                    class="regular-text">
                <option value="error" <?php selected(get_option('novarax_tm_log_level', 'error'), 'error'); ?>>
                    <?php _e('Error - Only errors', 'novarax-tenant-manager'); ?>
                </option>
                <option value="warning" <?php selected(get_option('novarax_tm_log_level', 'error'), 'warning'); ?>>
                    <?php _e('Warning - Errors and warnings', 'novarax-tenant-manager'); ?>
                </option>
                <option value="info" <?php selected(get_option('novarax_tm_log_level', 'error'), 'info'); ?>>
                    <?php _e('Info - All informational messages', 'novarax-tenant-manager'); ?>
                </option>
                <option value="debug" <?php selected(get_option('novarax_tm_log_level', 'error'), 'debug'); ?>>
                    <?php _e('Debug - Everything (very verbose)', 'novarax-tenant-manager'); ?>
                </option>
            </select>
            <p class="description">
                <?php _e('Minimum level of messages to log. Lower levels include all higher levels.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- API Enabled -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_api_enabled">
                <?php _e('REST API', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <label>
                <input type="checkbox" 
                       id="novarax_tm_api_enabled" 
                       name="novarax_tm_api_enabled" 
                       value="1" 
                       <?php checked(get_option('novarax_tm_api_enabled', '1'), '1'); ?>>
                <?php _e('Enable REST API endpoints', 'novarax-tenant-manager'); ?>
            </label>
            <p class="description">
                <?php _e('When enabled, NovaRax REST API endpoints will be available at /wp-json/novarax/v1/', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- API Rate Limit -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_api_rate_limit">
                <?php _e('API Rate Limit', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="number" 
                   id="novarax_tm_api_rate_limit" 
                   name="novarax_tm_api_rate_limit" 
                   value="<?php echo esc_attr(get_option('novarax_tm_api_rate_limit', '1000')); ?>" 
                   class="regular-text" 
                   min="10"
                   max="10000"> 
            <?php _e('requests per hour', 'novarax-tenant-manager'); ?>
            <p class="description">
                <?php _e('Maximum number of API requests allowed per hour per API key.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
</table>

<!-- System Information -->
<h3 style="margin-top: 40px;"><?php _e('System Information', 'novarax-tenant-manager'); ?></h3>
<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php _e('Plugin Version:', 'novarax-tenant-manager'); ?></th>
        <td><strong><?php echo defined('NOVARAX_TM_VERSION') ? NOVARAX_TM_VERSION : '1.0.0'; ?></strong></td>
    </tr>
    <tr>
        <th scope="row"><?php _e('WordPress Version:', 'novarax-tenant-manager'); ?></th>
        <td><strong><?php echo get_bloginfo('version'); ?></strong></td>
    </tr>
    <tr>
        <th scope="row"><?php _e('PHP Version:', 'novarax-tenant-manager'); ?></th>
        <td><strong><?php echo PHP_VERSION; ?></strong></td>
    </tr>
    <tr>
        <th scope="row"><?php _e('MySQL Version:', 'novarax-tenant-manager'); ?></th>
        <td><strong><?php echo $GLOBALS['wpdb']->db_version(); ?></strong></td>
    </tr>
    <tr>
        <th scope="row"><?php _e('Server Software:', 'novarax-tenant-manager'); ?></th>
        <td><strong><?php echo $_SERVER['SERVER_SOFTWARE']; ?></strong></td>
    </tr>
    <tr>
        <th scope="row"><?php _e('Max Execution Time:', 'novarax-tenant-manager'); ?></th>
        <td><strong><?php echo ini_get('max_execution_time'); ?>s</strong></td>
    </tr>
    <tr>
        <th scope="row"><?php _e('Memory Limit:', 'novarax-tenant-manager'); ?></th>
        <td><strong><?php echo ini_get('memory_limit'); ?></strong></td>
    </tr>
</table>

<!-- Dangerous Zone -->
<h3 style="margin-top: 40px; color: #dc3232;">
    <?php _e('Danger Zone', 'novarax-tenant-manager'); ?>
</h3>
<div class="novarax-warning-box">
    <p>
        <span class="dashicons dashicons-warning"></span>
        <strong><?php _e('These actions are irreversible!', 'novarax-tenant-manager'); ?></strong>
    </p>
</div>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row">
            <?php _e('Clear All Logs', 'novarax-tenant-manager'); ?>
        </th>
        <td>
            <button type="button" 
                    class="button button-secondary" 
                    id="clear-logs-btn"
                    style="color: #dc3232;">
                <?php _e('Clear All Logs', 'novarax-tenant-manager'); ?>
            </button>
            <p class="description">
                <?php _e('Permanently delete all audit logs and system logs.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <tr>
        <th scope="row">
            <?php _e('Reset Plugin Settings', 'novarax-tenant-manager'); ?>
        </th>
        <td>
            <button type="button" 
                    class="button button-secondary" 
                    id="reset-settings-btn"
                    style="color: #dc3232;">
                <?php _e('Reset to Defaults', 'novarax-tenant-manager'); ?>
            </button>
            <p class="description">
                <?php _e('Reset all NovaRax settings to default values. Tenant data will not be affected.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
</table>

<script>
jQuery(document).ready(function($) {
    
    // Clear logs confirmation
    $('#clear-logs-btn').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to delete all logs? This cannot be undone!', 'novarax-tenant-manager'); ?>')) {
            return;
        }
        
        $(this).prop('disabled', true).text('<?php _e('Clearing...', 'novarax-tenant-manager'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'novarax_clear_logs',
                nonce: '<?php echo wp_create_nonce('novarax_clear_logs'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('All logs have been cleared successfully.', 'novarax-tenant-manager'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Failed to clear logs. Please try again.', 'novarax-tenant-manager'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('An error occurred. Please try again.', 'novarax-tenant-manager'); ?>');
            },
            complete: function() {
                $('#clear-logs-btn').prop('disabled', false).text('<?php _e('Clear All Logs', 'novarax-tenant-manager'); ?>');
            }
        });
    });
    
    // Reset settings confirmation
    $('#reset-settings-btn').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to reset all settings to defaults? This cannot be undone!', 'novarax-tenant-manager'); ?>')) {
            return;
        }
        
        $(this).prop('disabled', true).text('<?php _e('Resetting...', 'novarax-tenant-manager'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'novarax_reset_settings',
                nonce: '<?php echo wp_create_nonce('novarax_reset_settings'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Settings have been reset to defaults.', 'novarax-tenant-manager'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Failed to reset settings. Please try again.', 'novarax-tenant-manager'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('An error occurred. Please try again.', 'novarax-tenant-manager'); ?>');
            },
            complete: function() {
                $('#reset-settings-btn').prop('disabled', false).text('<?php _e('Reset to Defaults', 'novarax-tenant-manager'); ?>');
            }
        });
    });
    
});
</script>