<?php
/**
 * Plugin Name: AME Branding Addon (Loader)
 * Plugin URI: https://novarax.ae
 * Description: Loads the Novarax AME Branding Addon MU-plugin from subdirectory
 * Version: 1.0.0
 * Author: Novarax Development Team
 * Author URI: https://novarax.ae
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if the main plugin file exists
$plugin_file = __DIR__ . '/ame-branding-add-on/ame-branding-add-on.php';

if (file_exists($plugin_file)) {
    require_once $plugin_file;
} else {
    // Log error if plugin file not found
    if (function_exists('error_log')) {
        error_log('Novarax AME Branding Addon : Main plugin file not found at ' . $plugin_file);
    }
    
    // Show admin notice
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><strong>Novarax AME Branding Addon Error:</strong> Main plugin file not found. Please check the installation.</p>
        </div>
        <?php
    });
}
