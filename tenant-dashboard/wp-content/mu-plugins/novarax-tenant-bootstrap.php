<?php
/**
 * Plugin Name: NovaRax Tenant Bootstrap
 * Plugin URI: https://novarax.ae
 * Description: Bootstrap plugin for NovaRax tenant dashboards. Handles authentication, license checking, and dynamic module activation.
 * Version: 1.0.0
 * Author: NovaRax Development Team
 * Author URI: https://novarax.ae
 * License: Proprietary
 * Text Domain: novarax-tenant-bootstrap
 * 
 * This is a MU-Plugin - Must be placed in wp-content/mu-plugins/
 * 
 * @package NovaRax\TenantBootstrap
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('NOVARAX_TB_VERSION', '1.0.0');
define('NOVARAX_TB_FILE', __FILE__);
define('NOVARAX_TB_DIR', plugin_dir_path(__FILE__));
define('NOVARAX_MASTER_URL', 'https://app.novarax.ae');
define('NOVARAX_API_URL', NOVARAX_MASTER_URL . '/wp-json/novarax/v1');

/**
 * Main Tenant Bootstrap Class
 */
class NovaRax_Tenant_Bootstrap {
    
    /**
     * Tenant ID
     *
     * @var int
     */
    private $tenant_id = null;
    
    /**
     * Tenant data
     *
     * @var object
     */
    private $tenant_data = null;
    
    /**
     * Current subdomain
     *
     * @var string
     */
    private $subdomain = null;
    
    /**
     * Master API URL
     *
     * @var string
     */
    private $api_url = NOVARAX_API_URL;
    
    /**
     * Singleton instance
     *
     * @var NovaRax_Tenant_Bootstrap
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return NovaRax_Tenant_Bootstrap
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Detect subdomain and initialize
        $this->detect_subdomain();
        $this->load_tenant_config();
        
        // Setup hooks
        $this->setup_hooks();
    }
    
    /**
     * Detect current subdomain
     */
    private function detect_subdomain() {
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        
        // Extract subdomain from host
        // e.g., username.app.novarax.ae -> username
        $parts = explode('.', $host);
        
        if (count($parts) >= 3) {
            $this->subdomain = $parts[0];
        }
        
        // Also try to get tenant ID from options table
        $this->tenant_id = get_option('novarax_tenant_id');
    }
    
    /**
     * Load tenant configuration
     */
    private function load_tenant_config() {
        // Load tenant ID from database if not already set
        if (!$this->tenant_id) {
            $this->tenant_id = get_option('novarax_tenant_id');
        }
        
        // Load API key for communication with master
        $api_key = get_option('novarax_api_key');
        
        if ($api_key) {
            // Store for later use
            $this->api_key = $api_key;
        }
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Authentication hooks
        add_action('init', array($this, 'validate_session'), 1);
        add_action('wp_login', array($this, 'handle_login'), 10, 2);
        add_action('wp_logout', array($this, 'handle_logout'));
        
        // Plugin management hooks
        add_filter('option_active_plugins', array($this, 'filter_active_plugins'), 99);
        add_filter('site_option_active_sitewide_plugins', array($this, 'filter_active_plugins'), 99);
        
        // Admin notices for expired modules
        add_action('admin_notices', array($this, 'show_license_notices'));
        
        // License check cron
        add_action('novarax_check_licenses', array($this, 'check_all_licenses'));
        
        // Schedule cron if not scheduled
        if (!wp_next_scheduled('novarax_check_licenses')) {
            wp_schedule_event(time(), 'hourly', 'novarax_check_licenses');
        }
        
        // AJAX handlers for license checking
        add_action('wp_ajax_novarax_check_module_license', array($this, 'ajax_check_license'));
        
        // Customize admin bar
        add_action('admin_bar_menu', array($this, 'customize_admin_bar'), 100);
        
        // Redirect to master for subscriptions
        add_action('admin_menu', array($this, 'add_subscription_menu'));
    }
    
    /**
     * Validate user session
     */
    public function validate_session() {
        // Skip validation for login page
        if ($this->is_login_page()) {
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return;
        }
        
        // Get authentication token from cookie
        $token = $this->get_auth_token();
        
        if (!$token) {
            // No token, user must login
            return;
        }
        
        // Check if token is still valid (cached check every 5 minutes)
        $cache_key = 'novarax_session_valid_' . get_current_user_id();
        $is_valid = get_transient($cache_key);
        
        if ($is_valid === false) {
            // Validate with master API
            $result = $this->validate_session_with_master($token);
            
            if ($result && $result['valid']) {
                // Cache valid session for 5 minutes
                set_transient($cache_key, true, 300);
            } else {
                // Invalid session, logout user
                wp_logout();
                wp_redirect(wp_login_url());
                exit;
            }
        }
    }
    
    /**
     * Validate session with master API
     *
     * @param string $token JWT token
     * @return array|false Response data or false
     */
    private function validate_session_with_master($token) {
        $response = wp_remote_post($this->api_url . '/validate-session', array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'token' => $token,
                'subdomain' => $this->subdomain . '.app.novarax.ae',
            )),
        ));
        
        if (is_wp_error($response)) {
            error_log('NovaRax: Session validation failed - ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return $body;
    }
    
    /**
     * Get authentication token from cookie
     *
     * @return string|false Token or false
     */
    private function get_auth_token() {
        // Check for NovaRax SSO cookie
        if (isset($_COOKIE['novarax_auth_token'])) {
            return $_COOKIE['novarax_auth_token'];
        }
        
        // Check for WordPress auth cookie (fallback)
        $user_id = get_current_user_id();
        if ($user_id) {
            return get_user_meta($user_id, 'novarax_jwt_token', true);
        }
        
        return false;
    }
    
    /**
     * Handle user login
     *
     * @param string $user_login Username
     * @param WP_User $user User object
     */
    public function handle_login($user_login, $user) {
        // Store login time
        update_user_meta($user->ID, 'novarax_last_login', current_time('mysql'));
        
        // Notify master of activity
        $this->update_tenant_activity('login');
    }
    
    /**
     * Handle user logout
     */
    public function handle_logout() {
        // Clear session cache
        $cache_key = 'novarax_session_valid_' . get_current_user_id();
        delete_transient($cache_key);
    }
    
    /**
     * Filter active plugins based on licenses
     *
     * @param array $plugins Active plugins
     * @return array Filtered plugins
     */
    public function filter_active_plugins($plugins) {
        if (!$this->tenant_id) {
            return $plugins;
        }
        
        // Get cached active modules (cache for 5 minutes)
        $cache_key = 'novarax_active_modules_' . $this->tenant_id;
        $active_modules = get_transient($cache_key);
        
        if ($active_modules === false) {
            // Fetch from master API
            $active_modules = $this->get_active_modules_from_master();
            
            if ($active_modules) {
                set_transient($cache_key, $active_modules, 300);
            } else {
                // If API fails, use last known good state
                $active_modules = get_option('novarax_last_active_modules', array());
            }
        }
        
        // Filter plugins to only include licensed modules
        $allowed_plugins = array();
        
        foreach ($active_modules as $module) {
            if ($module['has_access'] && !empty($module['plugin_path'])) {
                $allowed_plugins[] = $module['plugin_path'];
            }
        }
        
        // Intersect with currently active plugins
        if (is_array($plugins)) {
            $plugins = array_intersect($plugins, $allowed_plugins);
        }
        
        return $plugins;
    }
    
    /**
     * Get active modules from master API
     *
     * @return array Active modules
     */
    private function get_active_modules_from_master() {
        if (!$this->tenant_id) {
            return array();
        }
        
        $api_key = get_option('novarax_api_key');
        
        if (!$api_key) {
            error_log('NovaRax: No API key configured');
            return array();
        }
        
        $response = wp_remote_get($this->api_url . '/module-status?tenant_id=' . $this->tenant_id, array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'ApiKey ' . $api_key,
            ),
        ));
        
        if (is_wp_error($response)) {
            error_log('NovaRax: Failed to fetch modules - ' . $response->get_error_message());
            return array();
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['modules'])) {
            // Store as last known good state
            update_option('novarax_last_active_modules', $body['modules']);
            return $body['modules'];
        }
        
        return array();
    }
    
    /**
     * Check single module license
     *
     * @param string $module_slug Module slug
     * @return bool Has access
     */
    public function check_module_license($module_slug) {
        if (!$this->tenant_id) {
            return false;
        }
        
        $api_key = get_option('novarax_api_key');
        
        if (!$api_key) {
            return false;
        }
        
        // Check cache first
        $cache_key = 'novarax_license_' . $module_slug . '_' . $this->tenant_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached === 'valid';
        }
        
        // Check with master API
        $response = wp_remote_post($this->api_url . '/check-license', array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'ApiKey ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'tenant_id' => $this->tenant_id,
                'module_slug' => $module_slug,
            )),
        ));
        
        if (is_wp_error($response)) {
            error_log('NovaRax: License check failed for ' . $module_slug . ' - ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        $has_access = isset($body['valid']) && $body['valid'];
        
        // Cache result for 5 minutes
        set_transient($cache_key, $has_access ? 'valid' : 'invalid', 300);
        
        return $has_access;
    }
    
    /**
     * Check all module licenses (cron job)
     */
    public function check_all_licenses() {
        // Clear cache to force refresh
        $cache_key = 'novarax_active_modules_' . $this->tenant_id;
        delete_transient($cache_key);
        
        // Fetch fresh data
        $this->get_active_modules_from_master();
    }
    
    /**
     * AJAX handler for license checking
     */
    public function ajax_check_license() {
        check_ajax_referer('novarax_check_license', 'nonce');
        
        $module_slug = isset($_POST['module_slug']) ? sanitize_text_field($_POST['module_slug']) : '';
        
        if (empty($module_slug)) {
            wp_send_json_error(array('message' => 'Module slug is required'));
        }
        
        $has_access = $this->check_module_license($module_slug);
        
        if ($has_access) {
            wp_send_json_success(array('message' => 'License is valid'));
        } else {
            wp_send_json_error(array('message' => 'No valid license'));
        }
    }
    
    /**
     * Show license expiration notices
     */
    public function show_license_notices() {
        // Get active modules
        $active_modules = get_transient('novarax_active_modules_' . $this->tenant_id);
        
        if (!$active_modules) {
            return;
        }
        
        foreach ($active_modules as $module) {
            // Check if module is expiring soon
            if (isset($module['days_remaining']) && $module['days_remaining'] !== null) {
                if ($module['days_remaining'] <= 7 && $module['days_remaining'] > 0) {
                    ?>
                    <div class="notice notice-warning">
                        <p>
                            <strong><?php echo esc_html($module['name']); ?>:</strong>
                            <?php printf(__('Your subscription expires in %d days. Please renew to continue using this module.', 'novarax-tenant-bootstrap'), $module['days_remaining']); ?>
                            <a href="<?php echo esc_url(NOVARAX_MASTER_URL . '/apps'); ?>" class="button button-primary" style="margin-left: 10px;">
                                <?php _e('Renew Now', 'novarax-tenant-bootstrap'); ?>
                            </a>
                        </p>
                    </div>
                    <?php
                } elseif ($module['days_remaining'] <= 0 || $module['status'] === 'expired') {
                    ?>
                    <div class="notice notice-error">
                        <p>
                            <strong><?php echo esc_html($module['name']); ?>:</strong>
                            <?php _e('Your subscription has expired. This module is currently disabled.', 'novarax-tenant-bootstrap'); ?>
                            <a href="<?php echo esc_url(NOVARAX_MASTER_URL . '/apps'); ?>" class="button button-primary" style="margin-left: 10px;">
                                <?php _e('Reactivate', 'novarax-tenant-bootstrap'); ?>
                            </a>
                        </p>
                    </div>
                    <?php
                }
            }
        }
    }
    
    /**
     * Update tenant activity with master
     *
     * @param string $activity_type Type of activity
     * @param array $data Additional data
     */
    private function update_tenant_activity($activity_type, $data = array()) {
        if (!$this->tenant_id) {
            return;
        }
        
        $api_key = get_option('novarax_api_key');
        
        if (!$api_key) {
            return;
        }
        
        wp_remote_post($this->api_url . '/activity', array(
            'timeout' => 10,
            'blocking' => false, // Don't wait for response
            'headers' => array(
                'Authorization' => 'ApiKey ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'tenant_id' => $this->tenant_id,
                'type' => $activity_type,
                'data' => $data,
            )),
        ));
    }
    
    /**
     * Check if current page is login page
     *
     * @return bool
     */
    private function is_login_page() {
        return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
    }
    
    /**
     * Customize admin bar
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function customize_admin_bar($wp_admin_bar) {
        // Add link to master dashboard
        $wp_admin_bar->add_node(array(
            'id' => 'novarax-apps',
            'title' => '<span class="ab-icon dashicons-cart"></span> My Apps',
            'href' => NOVARAX_MASTER_URL . '/apps',
            'meta' => array(
                'target' => '_blank',
            ),
        ));
    }
    
    /**
     * Add subscription management menu
     */
    public function add_subscription_menu() {
        add_menu_page(
            __('My Subscriptions', 'novarax-tenant-bootstrap'),
            __('My Apps', 'novarax-tenant-bootstrap'),
            'read',
            'novarax-subscriptions',
            array($this, 'render_subscriptions_page'),
            'dashicons-cart',
            3
        );
    }
    
    /**
     * Render subscriptions page
     */
    public function render_subscriptions_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('My Apps & Subscriptions', 'novarax-tenant-bootstrap'); ?></h1>
            
            <div class="notice notice-info">
                <p>
                    <?php _e('Manage your app subscriptions from the master dashboard.', 'novarax-tenant-bootstrap'); ?>
                </p>
            </div>
            
            <p>
                <a href="<?php echo esc_url(NOVARAX_MASTER_URL . '/apps'); ?>" class="button button-primary button-hero" target="_blank">
                    <?php _e('Browse & Manage Apps', 'novarax-tenant-bootstrap'); ?> →
                </a>
            </p>
            
            <h2><?php _e('Currently Active Modules', 'novarax-tenant-bootstrap'); ?></h2>
            
            <?php
            $active_modules = get_transient('novarax_active_modules_' . $this->tenant_id);
            
            if (!$active_modules) {
                $active_modules = $this->get_active_modules_from_master();
            }
            
            if (empty($active_modules)) {
                echo '<p>' . __('No active modules found.', 'novarax-tenant-bootstrap') . '</p>';
                return;
            }
            ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Module', 'novarax-tenant-bootstrap'); ?></th>
                        <th><?php _e('Status', 'novarax-tenant-bootstrap'); ?></th>
                        <th><?php _e('Expires', 'novarax-tenant-bootstrap'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_modules as $module) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($module['name']); ?></strong></td>
                            <td>
                                <?php if ($module['has_access']) : ?>
                                    <span style="color: #46b450;">● <?php _e('Active', 'novarax-tenant-bootstrap'); ?></span>
                                <?php else : ?>
                                    <span style="color: #dc3232;">● <?php _e('Inactive', 'novarax-tenant-bootstrap'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if ($module['expires_at']) {
                                    $expires = strtotime($module['expires_at']);
                                    echo date_i18n(get_option('date_format'), $expires);
                                    
                                    if (isset($module['days_remaining'])) {
                                        echo ' (' . sprintf(__('%d days remaining', 'novarax-tenant-bootstrap'), $module['days_remaining']) . ')';
                                    }
                                } else {
                                    _e('Never', 'novarax-tenant-bootstrap');
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Get tenant ID
     *
     * @return int|null
     */
    public function get_tenant_id() {
        return $this->tenant_id;
    }
    
    /**
     * Get subdomain
     *
     * @return string|null
     */
    public function get_subdomain() {
        return $this->subdomain;
    }
}

// Initialize the plugin
NovaRax_Tenant_Bootstrap::get_instance();

// Helper function for modules to check their license
if (!function_exists('novarax_has_module_access')) {
    /**
     * Check if current tenant has access to a module
     *
     * @param string $module_slug Module slug
     * @return bool Has access
     */
    function novarax_has_module_access($module_slug) {
        $bootstrap = NovaRax_Tenant_Bootstrap::get_instance();
        return $bootstrap->check_module_license($module_slug);
    }
}



// Helper function to get tenant ID
if (!function_exists('novarax_get_tenant_id')) {
    /**
     * Get current tenant ID
     *
     * @return int|null Tenant ID or null
     */
    function novarax_get_tenant_id() {
        $bootstrap = NovaRax_Tenant_Bootstrap::get_instance();
        return $bootstrap->get_tenant_id();
    }
}



/**
 * Configure tenant-specific uploads directory
 */
add_filter('upload_dir', 'novarax_tenant_upload_dir');

function novarax_tenant_upload_dir($uploads) {
    // Get tenant subdomain
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
    // Extract username from subdomain (e.g., "chris.app.novarax.ae" -> "chris")
    $username = '';
    if (preg_match('/^([a-z0-9-]+)\.app\.novarax\.ae$/i', $host, $matches)) {
        $username = strtolower($matches[1]);
    }
    
    if (empty($username)) {
        // Fallback: try to get from database option
        $username = get_option('novarax_tenant_username', '');
    }
    
    if (empty($username)) {
        // Can't determine tenant, return default
        return $uploads;
    }
    
    // Set up tenant-specific paths
    $base_dir = WP_CONTENT_DIR . '/uploads/sites/' . $username;
    $base_url = WP_CONTENT_URL . '/uploads/sites/' . $username;
    
    // Create base directory if it doesn't exist
    if (!file_exists($base_dir)) {
        wp_mkdir_p($base_dir);
        
        // Create index.php for security
        $index_file = $base_dir . '/index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }
    
    // Get time-based subdirectory
    $time = current_time('mysql');
    $y = substr($time, 0, 4);
    $m = substr($time, 5, 2);
    
    // Build paths
    $subdir = "/$y/$m";
    $path = $base_dir . $subdir;
    $url = $base_url . $subdir;
    
    // Create year/month directories if they don't exist
    if (!file_exists($path)) {
        wp_mkdir_p($path);
    }
    
    // Return modified upload directory array
    return array(
        'path'    => $path,
        'url'     => $url,
        'subdir'  => $subdir,
        'basedir' => $base_dir,
        'baseurl' => $base_url,
        'error'   => false,
    );
}

/**
 * Store tenant username in options on bootstrap
 * Add this to the bootstrap initialization
 */
add_action('init', 'novarax_store_tenant_username', 1);

function novarax_store_tenant_username() {
    // Only run once
    if (get_option('novarax_tenant_username')) {
        return;
    }
    
    // Get tenant subdomain
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
    // Extract username
    if (preg_match('/^([a-z0-9-]+)\.app\.novarax\.ae$/i', $host, $matches)) {
        $username = strtolower($matches[1]);
        update_option('novarax_tenant_username', $username, false);
    }
}

/**
 * Disable WordPress media library organization by date
 * (Optional - uncomment if you want flat file structure)
 */
// add_filter('upload_dir', 'novarax_remove_upload_subdir');
// function novarax_remove_upload_subdir($uploads) {
//     $uploads['subdir'] = '';
//     $uploads['path'] = $uploads['basedir'];
//     $uploads['url'] = $uploads['baseurl'];
//     return $uploads;
// } 