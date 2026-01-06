<?php
/**
 * Marketplace Settings Tab
 * Location: /wp-content/mu-plugins/novarax-tenant-manager/admin/views/settings-tabs/marketplace.php
 */

if (!defined('ABSPATH')) exit;
?>

<div class="novarax-info-box">
    <p>
        <span class="dashicons dashicons-info"></span>
        <strong><?php _e('About Marketplace Settings', 'novarax-tenant-manager'); ?></strong><br>
        <?php _e('Configure your user journey from registration to module selection. These settings control where users are redirected after signing up and how they interact with your marketplace.', 'novarax-tenant-manager'); ?>
    </p>
</div>

<table class="form-table" role="presentation">
    
    <!-- Post-Registration Redirect -->
    <tr>
        <th scope="row">
            <label for="novarax_post_registration_redirect">
                <?php _e('Post-Registration Redirect', 'novarax-tenant-manager'); ?>
                <span class="required" style="color: #dc3232;">*</span>
            </label>
        </th>
        <td>
            <input type="text" 
                   id="novarax_post_registration_redirect" 
                   name="novarax_post_registration_redirect" 
                   value="<?php echo esc_attr(get_option('novarax_post_registration_redirect', '/marketplace')); ?>" 
                   class="regular-text" 
                   placeholder="/marketplace"
                   required>
            <p class="description">
                <?php _e('URL path where users are redirected after creating their account (before checkout). Example: /marketplace, /choose-plan, /onboarding', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Marketplace Page URL -->
    <tr>
        <th scope="row">
            <label for="novarax_marketplace_page_url">
                <?php _e('Marketplace Page URL', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="url" 
                   id="novarax_marketplace_page_url" 
                   name="novarax_marketplace_page_url" 
                   value="<?php echo esc_attr(get_option('novarax_marketplace_page_url', home_url('/marketplace'))); ?>" 
                   class="regular-text" 
                   placeholder="https://app.novarax.ae/marketplace">
            <p class="description">
                <?php _e('Full URL to your Elementor-designed marketplace page. This is where users will browse and select modules/products.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Checkout Behavior Section -->
    <tr>
        <th scope="row" colspan="2" style="padding-top: 30px;">
            <h3 style="margin: 0;"><?php _e('Checkout & Provisioning', 'novarax-tenant-manager'); ?></h3>
        </th>
    </tr>
    
    <!-- Auto-Provision After Checkout -->
    <tr>
        <th scope="row">
            <label for="novarax_auto_provision_after_checkout">
                <?php _e('Auto-Provision After Checkout', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <label>
                <input type="checkbox" 
                       id="novarax_auto_provision_after_checkout" 
                       name="novarax_auto_provision_after_checkout" 
                       value="1" 
                       <?php checked(get_option('novarax_auto_provision_after_checkout', '1'), '1'); ?>>
                <?php _e('Automatically start provisioning after successful order', 'novarax-tenant-manager'); ?>
            </label>
            <p class="description">
                <?php _e('When enabled, tenant provisioning begins immediately after order completion. When disabled, you must manually trigger provisioning.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Allow Direct Checkout -->
    <tr>
        <th scope="row">
            <label for="novarax_allow_direct_checkout">
                <?php _e('Allow Direct Checkout', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <label>
                <input type="checkbox" 
                       id="novarax_allow_direct_checkout" 
                       name="novarax_allow_direct_checkout" 
                       value="1" 
                       <?php checked(get_option('novarax_allow_direct_checkout', '0'), '1'); ?>>
                <?php _e('Allow users to checkout without selecting any modules', 'novarax-tenant-manager'); ?>
            </label>
            <p class="description">
                <?php _e('If enabled, users can complete checkout with an empty cart. Useful for free trial or pay-later models.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Minimum Module Selection -->
    <tr>
        <th scope="row">
            <label for="novarax_minimum_module_selection">
                <?php _e('Minimum Module Selection', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="number" 
                   id="novarax_minimum_module_selection" 
                   name="novarax_minimum_module_selection" 
                   value="<?php echo esc_attr(get_option('novarax_minimum_module_selection', '0')); ?>" 
                   class="small-text" 
                   min="0"
                   max="10">
            <p class="description">
                <?php _e('Minimum number of modules users must select before checkout. Set to 0 for no minimum.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Thank You Page Section -->
    <tr>
        <th scope="row" colspan="2" style="padding-top: 30px;">
            <h3 style="margin: 0;"><?php _e('Thank You Page Settings', 'novarax-tenant-manager'); ?></h3>
        </th>
    </tr>
    
    <!-- Show Provisioning Status -->
    <tr>
        <th scope="row">
            <label for="novarax_show_provisioning_status">
                <?php _e('Show Provisioning Status', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <label>
                <input type="checkbox" 
                       id="novarax_show_provisioning_status" 
                       name="novarax_show_provisioning_status" 
                       value="1" 
                       <?php checked(get_option('novarax_show_provisioning_status', '1'), '1'); ?>>
                <?php _e('Display live provisioning progress on the Thank You page', 'novarax-tenant-manager'); ?>
            </label>
            <p class="description">
                <?php _e('When enabled, users see a real-time progress bar while their dashboard is being created. Uses AJAX polling.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Provisioning Status Refresh Interval -->
    <tr>
        <th scope="row">
            <label for="novarax_provisioning_refresh_interval">
                <?php _e('Status Refresh Interval', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <input type="number" 
                   id="novarax_provisioning_refresh_interval" 
                   name="novarax_provisioning_refresh_interval" 
                   value="<?php echo esc_attr(get_option('novarax_provisioning_refresh_interval', '3')); ?>" 
                   class="small-text" 
                   min="1"
                   max="30"> 
            <?php _e('seconds', 'novarax-tenant-manager'); ?>
            <p class="description">
                <?php _e('How often to check provisioning status (in seconds). Lower values = more real-time, but higher server load. Recommended: 3-5 seconds.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Custom Thank You Message -->
    <tr>
        <th scope="row">
            <label for="novarax_thankyou_custom_message">
                <?php _e('Custom Thank You Message', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <textarea id="novarax_thankyou_custom_message" 
                      name="novarax_thankyou_custom_message" 
                      rows="4" 
                      class="large-text"
                      placeholder="Thank you for choosing NovaRax! Your dashboard is being prepared..."><?php echo esc_textarea(get_option('novarax_thankyou_custom_message', '')); ?></textarea>
            <p class="description">
                <?php _e('Optional custom message to display on the Thank You page while provisioning is in progress. Leave empty for default message.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- User Experience Section -->
    <tr>
        <th scope="row" colspan="2" style="padding-top: 30px;">
            <h3 style="margin: 0;"><?php _e('User Experience', 'novarax-tenant-manager'); ?></h3>
        </th>
    </tr>
    
    <!-- Enable Onboarding Tour -->
    <tr>
        <th scope="row">
            <label for="novarax_enable_onboarding_tour">
                <?php _e('Enable Onboarding Tour', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <label>
                <input type="checkbox" 
                       id="novarax_enable_onboarding_tour" 
                       name="novarax_enable_onboarding_tour" 
                       value="1" 
                       <?php checked(get_option('novarax_enable_onboarding_tour', '1'), '1'); ?>>
                <?php _e('Show interactive onboarding tour on first login', 'novarax-tenant-manager'); ?>
            </label>
            <p class="description">
                <?php _e('Guides new users through their dashboard features on their first login.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
    <!-- Default Landing Page After Login -->
    <tr>
        <th scope="row">
            <label for="novarax_tenant_landing_page">
                <?php _e('Default Landing Page', 'novarax-tenant-manager'); ?>
            </label>
        </th>
        <td>
            <select id="novarax_tenant_landing_page" 
                    name="novarax_tenant_landing_page" 
                    class="regular-text">
                <option value="dashboard" <?php selected(get_option('novarax_tenant_landing_page', 'dashboard'), 'dashboard'); ?>>
                    <?php _e('Dashboard', 'novarax-tenant-manager'); ?>
                </option>
                <option value="my-apps" <?php selected(get_option('novarax_tenant_landing_page', 'dashboard'), 'my-apps'); ?>>
                    <?php _e('My Apps (Modules)', 'novarax-tenant-manager'); ?>
                </option>
                <option value="subscriptions" <?php selected(get_option('novarax_tenant_landing_page', 'dashboard'), 'subscriptions'); ?>>
                    <?php _e('Subscriptions', 'novarax-tenant-manager'); ?>
                </option>
                <option value="profile" <?php selected(get_option('novarax_tenant_landing_page', 'dashboard'), 'profile'); ?>>
                    <?php _e('Profile Settings', 'novarax-tenant-manager'); ?>
                </option>
            </select>
            <p class="description">
                <?php _e('Where users land after logging into their tenant dashboard.', 'novarax-tenant-manager'); ?>
            </p>
        </td>
    </tr>
    
</table>

<div class="novarax-info-box" style="margin-top: 30px;">
    <p>
        <span class="dashicons dashicons-lightbulb"></span>
        <strong><?php _e('Pro Tip:', 'novarax-tenant-manager'); ?></strong> 
        <?php _e('For the best user experience, design your marketplace page in Elementor with clear module descriptions, pricing, and feature lists. Use the [novarax_marketplace] shortcode or create a custom design.', 'novarax-tenant-manager'); ?>
    </p>
</div>