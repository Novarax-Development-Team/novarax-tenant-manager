<?php
/**
 * General Settings Tab
 * Location: /wp-content/mu-plugins/novarax-tenant-manager/admin/views/settings-tabs/general.php
 */

if (!defined('ABSPATH')) exit;
?>

<table class="form-table" role="presentation">
    
    <!-- Subdomain Suffix -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_subdomain_suffix">
                <?php _e('Subdomain Suffix', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="text" 
                   id="novarax_tm_subdomain_suffix" 
                   name="novarax_tm_subdomain_suffix" 
                   value="<?php echo esc_attr(get_option('novarax_tm_subdomain_suffix', '.app.novarax.ae')); ?>" 
                   class="regular-text">
            <p class="description">
                <?php _e('The subdomain suffix for all tenant subdomains. Example: .app.novarax.ae', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Tenant Database Prefix -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_tenant_db_prefix">
                <?php _e('Tenant Database Prefix', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="text" 
                   id="novarax_tm_tenant_db_prefix" 
                   name="novarax_tm_tenant_db_prefix" 
                   value="<?php echo esc_attr(get_option('novarax_tm_tenant_db_prefix', 'novarax_tenant_')); ?>" 
                   class="regular-text">
            <p class="description">
                <?php _e('Prefix for tenant database names. Example: novarax_tenant_', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Tenant Codebase Path -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_tenant_codebase_path">
                <?php _e('Tenant Codebase Path', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="text" 
                   id="novarax_tm_tenant_codebase_path" 
                   name="novarax_tm_tenant_codebase_path" 
                   value="<?php echo esc_attr(get_option('novarax_tm_tenant_codebase_path', '/var/www/vhosts/novarax.ae/tenant-dashboard')); ?>" 
                   class="regular-text code">
            <p class="description">
                <?php _e('Server path to the tenant WordPress codebase. All tenants share this codebase.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Default Storage Limit -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_tenant_storage_limit">
                <?php _e('Default Storage Limit', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="number" 
                   id="novarax_tm_tenant_storage_limit" 
                   name="novarax_tm_tenant_storage_limit" 
                   value="<?php echo esc_attr(get_option('novarax_tm_tenant_storage_limit', 5368709120)); ?>" 
                   class="regular-text" 
                   min="1073741824"
                   step="1073741824"> 
            <?php _e('bytes', 'novarax-tenant-manager'); ?>
            <p class="description">
                <?php _e('Default storage limit for new tenants in bytes. 5368709120 = 5GB', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Default User Limit -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_tenant_user_limit">
                <?php _e('Default User Limit', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="number" 
                   id="novarax_tm_tenant_user_limit" 
                   name="novarax_tm_tenant_user_limit" 
                   value="<?php echo esc_attr(get_option('novarax_tm_tenant_user_limit', 10)); ?>" 
                   class="small-text" 
                   min="1"
                   max="1000">
            <p class="description">
                <?php _e('Maximum number of users allowed per tenant by default.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Auto-Provision -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_auto_provision">
                <?php _e('Auto-Provision Tenants', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <label>
                <input type="checkbox" 
                       id="novarax_tm_auto_provision" 
                       name="novarax_tm_auto_provision" 
                       value="1" 
                       <?php checked(get_option('novarax_tm_auto_provision', '1'), '1'); ?>>
                <?php _e('Automatically provision tenants after creation', 'novarax-tenant-manager'); ?>
            </label>
            <p class="description">
                <?php _e('When enabled, tenant databases and WordPress installations are created automatically via cron queue.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Grace Period -->
    <tr>
        <th scope="row">
            <label for="novarax_tm_grace_period_days">
                <?php _e('Grace Period', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="number" 
                   id="novarax_tm_grace_period_days" 
                   name="novarax_tm_grace_period_days" 
                   value="<?php echo esc_attr(get_option('novarax_tm_grace_period_days', 7)); ?>" 
                   class="small-text" 
                   min="0"
                   max="90"> 
            <?php _e('days', 'novarax-tenant-manager'); ?>
            <p class="description">
                <?php _e('Number of days to keep expired tenants active before suspension. Set to 0 for immediate suspension.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
</table>