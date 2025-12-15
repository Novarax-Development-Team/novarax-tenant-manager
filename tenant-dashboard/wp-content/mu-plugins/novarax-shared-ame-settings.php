<?php
/**
 * Plugin Name: NovaRax Shared AME Settings
 * Description: Syncs Admin Menu Editor Pro settings from master dashboard (Nova-tenantdash)
 * Version: 1.0.0
 * Author: NovaRax Development Team
 */

if (!defined('ABSPATH')) {
    exit;
}

class NovaRax_Shared_AME_Settings {
    
    private static $instance = null;
    private $master_db = null;
    
    // Master database connection details
    const MASTER_DB_NAME = 'Nova_tenantdash';
    const MASTER_DB_USER = 'nova_g5ytr'; // Update with your master DB user
    const MASTER_DB_PASS = '9ln7bFseOR8mj$oNn'; // Update with your master DB password
    const MASTER_DB_HOST = 'localhost:3306';
    const MASTER_TABLE_PREFIX = 'RAX148_'; // Your master table prefix
    
    // Admin Menu Editor Pro option names to sync
    private $ame_options = [
       
'ws_menu_editor_pro',  // Main menu configuration
'ws_ame_table_columns',
'ws_abe_external_updates',
'ws_ame_admin_color_scheme_css',
'ws_ame_admin_colors',
'ws_ame_dashboard_styler',
'ws_ame_general_branding',
'ws_ame_role_editor',
'ws_ame_redirects',
'ws_ame_rui_first_change',
'ws_ame_dashboard_widgets',
'ame_pro_external_updates',
'ame_cpe_settings',
'ws_ame_tweak_settings',
'ws_menu_editor_pro',
'ws_abe_admin_bar_settings',
'ws_ame_login_page_settings',

// Add other AME options as needed
// Notes for clarity
// ws_menu_editor_pro → the primary Admin Menu Editor Pro settings (menus, modules, license, etc.)
// ws_ame_* → free + Pro extension features (colors, branding, dashboard widgets, redirects, role editor)
// ame_pro_external_updates → Pro plugin update metadata
// ame_cpe_settings → part of the “Custom Permissions Editor” module (role/capability customizations)
// ws_abe_external_updates → for “Admin Bar Editor” module if installed
// Not all AME Pro installs will have the same list — yours is complete for the modules you enabled.

    ];
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into WordPress initialization
        add_action('plugins_loaded', [$this, 'init'], 5);
        
        // Filter AME options to use master database values
        add_filter('pre_option_ws_menu_editor', [$this, 'get_master_option'], 10, 2);
        add_filter('pre_option_ws_ame_menu_color_scheme', [$this, 'get_master_option'], 10, 2);
        
        // Add filters for all AME options
        foreach ($this->ame_options as $option) {
            add_filter("pre_option_{$option}", [$this, 'get_master_option'], 10, 2);
        }
        
        // Prevent tenants from updating AME settings (read-only)
        add_filter('pre_update_option_ws_menu_editor', [$this, 'prevent_update'], 10, 3);
        foreach ($this->ame_options as $option) {
            add_filter("pre_update_option_{$option}", [$this, 'prevent_update'], 10, 3);
        }
    }
    
    public function init() {
        // Connect to master database
        $this->connect_master_db();
        
        // Log initialization
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('NovaRax Shared AME Settings initialized');
        }
    }
    
    /**
     * Connect to master database
     */
    private function connect_master_db() {
        if ($this->master_db !== null) {
            return;
        }
        
        try {
            $this->master_db = new wpdb(
                self::MASTER_DB_USER,
                self::MASTER_DB_PASS,
                self::MASTER_DB_NAME,
                self::MASTER_DB_HOST
            );
            
            $this->master_db->prefix = self::MASTER_TABLE_PREFIX;
            
            // Test connection
            $result = $this->master_db->get_var("SELECT 1");
            
            if ($result !== '1') {
                throw new Exception('Master database connection test failed');
            }
            
        } catch (Exception $e) {
            error_log('NovaRax AME Sync Error: ' . $e->getMessage());
            $this->master_db = null;
        }
    }
    
    /**
     * Get option from master database
     * 
     * @param mixed $pre_option The value to return instead of the option value
     * @param string $option Option name
     * @return mixed Option value from master database
     */
    public function get_master_option($pre_option, $option) {
        // If master DB connection failed, return default
        if ($this->master_db === null) {
            return $pre_option;
        }
        
        $table = $this->master_db->prefix . 'options';
        
        $value = $this->master_db->get_var(
            $this->master_db->prepare(
                "SELECT option_value FROM {$table} WHERE option_name = %s LIMIT 1",
                $option
            )
        );
        
        if ($value === null) {
            return $pre_option;
        }
        
        // Maybe unserialize
        $value = maybe_unserialize($value);
        
        // Cache the result for this request
        wp_cache_set($option, $value, 'options');
        
        return $value;
    }
    
    /**
     * Prevent tenants from updating AME settings
     * 
     * @param mixed $value The new option value
     * @param mixed $old_value The old option value
     * @param string $option Option name
     * @return mixed Return old value to prevent update
     */
    public function prevent_update($value, $old_value, $option) {
        // Show admin notice if tenant tries to update
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Admin Menu Editor:</strong> Settings are managed centrally and cannot be modified from tenant dashboards.</p>';
            echo '</div>';
        });
        
        // Return old value to prevent update
        return $old_value;
    }
    
    /**
     * Get current master database connection status
     * 
     * @return bool
     */
    public function is_connected() {
        return $this->master_db !== null;
    }
    
    /**
     * Force refresh AME options from master
     */
    public function refresh_ame_cache() {
        foreach ($this->ame_options as $option) {
            wp_cache_delete($option, 'options');
        }
    }
}

// Initialize
NovaRax_Shared_AME_Settings::get_instance();

// Admin page to test connection and refresh cache
add_action('admin_menu', function() {
    add_submenu_page(
        'options-general.php',
        'AME Sync Status',
        'AME Sync',
        'manage_options',
        'novarax-ame-sync',
        function() {
            $sync = NovaRax_Shared_AME_Settings::get_instance();
            
            if (isset($_POST['refresh_cache'])) {
                $sync->refresh_ame_cache();
                echo '<div class="notice notice-success"><p>Cache refreshed!</p></div>';
            }
            
            ?>
            <div class="wrap">
                <h1>Admin Menu Editor Sync Status</h1>
                <table class="form-table">
                    <tr>
                        <th>Master Database Connection</th>
                        <td>
                            <?php if ($sync->is_connected()): ?>
                                <span style="color: green;">✓ Connected</span>
                            <?php else: ?>
                                <span style="color: red;">✗ Not Connected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Master Database</th>
                        <td><?php echo NovaRax_Shared_AME_Settings::MASTER_DB_NAME; ?></td>
                    </tr>
                    <tr>
                        <th>Synced Options</th>
                        <td><?php echo count($sync->ame_options ?? []); ?> AME options</td>
                    </tr>
                </table>
                
                <form method="post">
                    <input type="hidden" name="refresh_cache" value="1">
                    <?php submit_button('Refresh AME Cache', 'secondary'); ?>
                </form>
                
                <hr>
                <h2>How It Works</h2>
                <p>This plugin automatically syncs Admin Menu Editor Pro settings from the master tenant dashboard database (<code>nova_tenantdash</code>).</p>
                <p>When you update menu settings in the master dashboard, changes will automatically appear on all tenant dashboards.</p>
                <p><strong>Note:</strong> Tenants cannot modify menu settings - they are read-only.</p>
            </div>
            <?php
        }
    );
});