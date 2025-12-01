<?php
/**
 * Fix Tenant WordPress Setup Issues
 * 
 * This adds the missing configuration to prevent database upgrade prompts
 * and admin email verification for tenants.
 * 
 * Add this to: /var/www/vhosts/novarax.ae/tenant-dashboard/wp-content/mu-plugins/novarax-tenant-fixes.php
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Disable database upgrade prompt for tenants
 * 
 * This runs after WordPress loads and sets the database version
 * to match the current WordPress version, preventing upgrade prompts.
 */
add_action('init', function() {
    global $wp_db_version;
    
    // Get current database version
    $current_db_version = get_option('db_version');
    
    // If database version doesn't match, update it silently
    if ($current_db_version != $wp_db_version) {
        // Update database version to current WordPress version
        update_option('db_version', $wp_db_version);
    }
}, 1);

/**
 * Disable admin email verification prompt
 * 
 * WordPress shows this annoying prompt to verify admin email.
 * For tenants, we don't need this as the email was already verified during signup.
 */
add_filter('admin_email_check_interval', '__return_false');

/**
 * Remove admin email verification notice completely
 */
add_action('admin_init', function() {
    // Remove the admin email verification meta
    remove_action('admin_notices', 'new_admin_email_notice');
    
    // Set the last admin email check to now
    update_option('admin_email_lifespan', time());
});

/**
 * Disable WordPress auto-updates for tenants
 * 
 * Tenants shouldn't update WordPress core - that's managed centrally.
 */
add_filter('automatic_updater_disabled', '__return_true');
add_filter('auto_update_core', '__return_false');
add_filter('auto_update_plugin', '__return_false');
add_filter('auto_update_theme', '__return_false');

/**
 * Remove the "WordPress updated successfully" notice
 */
add_action('admin_init', function() {
    remove_action('admin_notices', 'update_nag', 3);
    remove_action('network_admin_notices', 'update_nag', 3);
});

/**
 * Hide core update notices from tenants
 */
add_action('admin_menu', function() {
    remove_action('admin_notices', 'update_nag', 3);
});

/**
 * Prevent tenants from seeing WordPress update notices
 */
add_filter('pre_site_transient_update_core', function($transient) {
    // Return empty transient to hide updates
    if (!is_super_admin()) {
        return null;
    }
    return $transient;
});

/**
 * Custom admin footer text for tenants
 */
add_filter('admin_footer_text', function($text) {
    return 'Powered by <a href="https://app.novarax.ae">NovaRax</a>';
});

/**
 * Remove WordPress version from footer for tenants
 */
add_filter('update_footer', '__return_empty_string', 11);

/**
 * Redirect to dashboard after login (skip email verification)
 */
add_filter('login_redirect', function($redirect_to, $request, $user) {
    // If no redirect specified, go to dashboard
    if (empty($redirect_to) || $redirect_to == 'wp-admin/' || $redirect_to == admin_url()) {
        return admin_url('index.php');
    }
    return $redirect_to;
}, 10, 3);

/**
 * Auto-confirm admin email on first login
 */
add_action('wp_login', function($user_login, $user) {
    // Update admin email lifespan to skip verification
    update_option('admin_email_lifespan', time() + (6 * MONTH_IN_SECONDS));
}, 10, 2);