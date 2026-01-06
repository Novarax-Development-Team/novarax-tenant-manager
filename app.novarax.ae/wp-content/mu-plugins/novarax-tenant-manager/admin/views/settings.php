<?php
/**
 * NovaRax Settings Page - Enhanced with Marketplace and Webhooks
 * Location: /wp-content/mu-plugins/novarax-tenant-manager/admin/views/settings.php
 */

if (!defined('ABSPATH')) exit;

$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Apply filters to allow other plugins to add tabs
$tabs = apply_filters('novarax_settings_tabs', array(
    'general' => __('General', 'novarax-tenant-manager'),
    'marketplace' => __('Marketplace', 'novarax-tenant-manager'),
    'webhooks' => __('Webhooks', 'novarax-tenant-manager'),
    'email' => __('Email', 'novarax-tenant-manager'),
    'advanced' => __('Advanced', 'novarax-tenant-manager'),
));
?>

<div class="wrap novarax-settings-wrap">
    <h1><?php _e('NovaRax Settings', 'novarax-tenant-manager'); ?></h1>
    
    <!-- Tab Navigation -->
    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_key => $tab_label) : ?>
            <a href="?page=novarax-tenants-settings&tab=<?php echo esc_attr($tab_key); ?>" 
               class="nav-tab <?php echo $current_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_label); ?>
            </a>
        <?php endforeach; ?>
    </h2>
    
    <!-- Settings Form -->
    <form method="post" action="">
        <?php wp_nonce_field('novarax_tm_action', 'novarax_tm_nonce'); ?>
        <input type="hidden" name="novarax_tm_action" value="update_settings">
        <input type="hidden" name="novarax_tm_settings_tab" value="<?php echo esc_attr($current_tab); ?>">
        
        <?php
        // Render tab content
        switch ($current_tab) {
            case 'general':
                include NOVARAX_TM_PLUGIN_DIR . 'admin/views/settings-tabs/general.php';
                break;
            case 'marketplace':
                include NOVARAX_TM_PLUGIN_DIR . 'admin/views/settings-tabs/marketplace.php';
                break;
            case 'webhooks':
                include NOVARAX_TM_PLUGIN_DIR . 'admin/views/settings-tabs/webhooks.php';
                break;
            case 'email':
                include NOVARAX_TM_PLUGIN_DIR . 'admin/views/settings-tabs/email.php';
                break;
            case 'advanced':
                include NOVARAX_TM_PLUGIN_DIR . 'admin/views/settings-tabs/advanced.php';
                break;
            default:
                // Allow other plugins to render their tabs
                do_action('novarax_settings_tab_' . $current_tab);
                break;
        }
        ?>
        
        <?php submit_button(__('Save Settings', 'novarax-tenant-manager')); ?>
    </form>
</div>

<style>
/* Enhanced Settings Styles */
.novarax-settings-wrap .form-table {
    max-width: 900px;
}

.novarax-settings-wrap .form-table th {
    width: 280px;
    padding-left: 0;
}

.novarax-settings-wrap .description {
    color: #646970;
    font-size: 13px;
    margin-top: 5px;
}

/* Webhook Status Indicators */
.webhook-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.webhook-status.enabled {
    background: #d5f4e6;
    color: #0f5132;
}

.webhook-status.disabled {
    background: #f0f0f1;
    color: #646970;
}

.webhook-status .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Test Webhook Button */
.test-webhook-btn {
    margin-left: 10px;
    vertical-align: middle;
}

/* Webhook Results */
.webhook-test-result {
    margin-top: 10px;
    padding: 12px;
    border-radius: 4px;
    display: none;
}

.webhook-test-result.success {
    background: #d5f4e6;
    border-left: 3px solid #46b450;
}

.webhook-test-result.error {
    background: #fef0f0;
    border-left: 3px solid #dc3232;
}

/* Info Boxes */
.novarax-info-box {
    background: #f0f6fc;
    border-left: 4px solid #2271b1;
    padding: 12px 16px;
    margin: 20px 0;
    border-radius: 4px;
}

.novarax-info-box p {
    margin: 0;
}

.novarax-info-box .dashicons {
    color: #2271b1;
    margin-right: 8px;
}

/* Warning Boxes */
.novarax-warning-box {
    background: #fcf9e8;
    border-left: 4px solid #f0b849;
    padding: 12px 16px;
    margin: 20px 0;
    border-radius: 4px;
}

.novarax-warning-box .dashicons {
    color: #f0b849;
    margin-right: 8px;
}

/* Code Blocks */
.novarax-code-block {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 16px;
    border-radius: 6px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    overflow-x: auto;
    margin: 10px 0;
}

.novarax-code-block code {
    color: #d4d4d4;
}
</style>