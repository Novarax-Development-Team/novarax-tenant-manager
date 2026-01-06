<?php
/**
 * NovaRax Registration & Marketplace System - Enhanced
 * Location: /wp-content/mu-plugins/novarax-registration-system/novarax-registration-system.php
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class NovaRax_Registration_System {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add shortcodes
        add_shortcode('novarax_register', array($this, 'render_registration_form'));
        add_shortcode('novarax_marketplace', array($this, 'render_marketplace'));
        add_shortcode('novarax_provisioning_status', array($this, 'render_provisioning_status'));
        
        // AJAX handlers for logged-in users
        add_action('wp_ajax_novarax_register_user', array($this, 'ajax_register_user'));
        add_action('wp_ajax_novarax_check_username', array($this, 'ajax_check_username'));
        add_action('wp_ajax_novarax_check_email', array($this, 'ajax_check_email'));
        add_action('wp_ajax_novarax_check_provisioning', array($this, 'ajax_check_provisioning_status'));
        
        // AJAX handlers for non-logged-in users
        add_action('wp_ajax_nopriv_novarax_register_user', array($this, 'ajax_register_user'));
        add_action('wp_ajax_nopriv_novarax_check_username', array($this, 'ajax_check_username'));
        add_action('wp_ajax_nopriv_novarax_check_email', array($this, 'ajax_check_email'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        global $post;
        
        if (!is_a($post, 'WP_Post')) {
            return;
        }
        
        $has_shortcode = has_shortcode($post->post_content, 'novarax_register') ||
                        has_shortcode($post->post_content, 'novarax_marketplace') ||
                        has_shortcode($post->post_content, 'novarax_provisioning_status');
        
        if (!$has_shortcode) {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'novarax-registration',
            plugin_dir_url(__FILE__) . 'assets/css/registration.css',
            array(),
            '1.0.0'
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'novarax-registration',
            plugin_dir_url(__FILE__) . 'assets/js/registration.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Localize script
        wp_localize_script('novarax-registration', 'novaraxAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('novarax_registration'),
            'redirectUrl' => $this->get_redirect_url(),
            'loginUrl' => wp_login_url(),
        ));
    }
    
    /**
     * Render registration form
     */
    public function render_registration_form($atts) {
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/registration-form.php';
        return ob_get_clean();
    }
    
    /**
     * Render marketplace
     */
    public function render_marketplace($atts) {
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/marketplace.php';
        return ob_get_clean();
    }
    
    /**
     * Render provisioning status
     */
    public function render_provisioning_status($atts) {
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/provisioning-status.php';
        return ob_get_clean();
    }
    
    /**
     * AJAX: Check username availability
     */
    public function ajax_check_username() {
        check_ajax_referer('novarax_registration', 'nonce');
        
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        
        if (empty($username)) {
            wp_send_json_error(array('message' => 'Username is required'));
        }
        
        // Check username length
        if (strlen($username) < 3) {
            wp_send_json_error(array('message' => 'Username must be at least 3 characters'));
        }
        
        // Check if username exists
        $user_exists = username_exists($username);
        
        // Check if tenant with this username exists
        global $wpdb;
        $tenant_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}novarax_tenants WHERE tenant_username = %s",
            $username
        ));
        
        if ($user_exists || $tenant_exists) {
            wp_send_json_success(array('available' => false));
        } else {
            wp_send_json_success(array('available' => true));
        }
    }
    
    /**
     * AJAX: Check email availability
     */
    public function ajax_check_email() {
        check_ajax_referer('novarax_registration', 'nonce');
        
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        
        if (empty($email)) {
            wp_send_json_error(array('message' => 'Email is required'));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email format'));
        }
        
        // Check if email exists
        $email_exists = email_exists($email);
        
        if ($email_exists) {
            wp_send_json_success(array('available' => false));
        } else {
            wp_send_json_success(array('available' => true));
        }
    }
    
    /**
     * AJAX: Register user
     */
    public function ajax_register_user() {
        check_ajax_referer('novarax_registration', 'nonce');
        
        // Get form data
        $full_name = isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '';
        $username = isset($_POST['username']) ? sanitize_user($_POST['username']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $company = isset($_POST['company']) ? sanitize_text_field($_POST['company']) : '';
        $country_code = isset($_POST['country_code']) ? sanitize_text_field($_POST['country_code']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $marketing = isset($_POST['marketing']) ? sanitize_text_field($_POST['marketing']) : '0';
        
        // Validate required fields
        if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Please fill in all required fields.'));
        }
        
        // Validate email format
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Invalid email address.'));
        }
        
        // Validate username format
        if (!preg_match('/^[a-z0-9_-]+$/', $username)) {
            wp_send_json_error(array('message' => 'Username can only contain lowercase letters, numbers, hyphens and underscores.'));
        }
        
        // Validate password length
        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => 'Password must be at least 8 characters long.'));
        }
        
        // Check if username exists
        if (username_exists($username)) {
            wp_send_json_error(array('message' => 'Username already taken.'));
        }
        
        // Check if email exists
        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Email already registered.'));
        }
        
        // Create tenant with status: pending
        if (!class_exists('NovaRax_Tenant_Operations')) {
            wp_send_json_error(array('message' => 'Tenant system not available. Please contact support.'));
        }
        
        $tenant_ops = new NovaRax_Tenant_Operations();
        
        // Combine phone with country code
        $full_phone = !empty($phone) ? $country_code . $phone : '';
        
        $tenant_data = array(
            'full_name' => $full_name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'company_name' => $company,
            'phone_number' => $full_phone,
            'metadata' => json_encode(array(
                'marketing_consent' => $marketing,
                'registration_date' => current_time('mysql'),
                'registration_ip' => $_SERVER['REMOTE_ADDR'],
            ))
        );
        
        $result = $tenant_ops->create_tenant($tenant_data);
        
        if (!$result['success']) {
            wp_send_json_error(array(
                'message' => isset($result['error']) ? $result['error'] : 'Failed to create account. Please try again.'
            ));
        }
        
        // Trigger webhook: account_created
        if (class_exists('NovaRax_Webhook_Manager')) {
            $webhook_manager = new NovaRax_Webhook_Manager();
            $webhook_manager->trigger('account_created', $result['tenant_id']);
        }
        
        // Get redirect URL from settings
        $redirect_url = home_url($this->get_redirect_url());
        
        wp_send_json_success(array(
            'message' => 'Account created successfully! Redirecting to marketplace...',
            'tenant_id' => $result['tenant_id'],
            'subdomain' => $result['subdomain'],
            'redirect_url' => $redirect_url
        ));
    }
    
    /**
     * AJAX: Check provisioning status
     */
    public function ajax_check_provisioning_status() {
        check_ajax_referer('novarax_registration', 'nonce');
        
        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        
        if (!$tenant_id) {
            wp_send_json_error(array('message' => 'Invalid tenant ID'));
        }
        
        $tenant_ops = new NovaRax_Tenant_Operations();
        $tenant = $tenant_ops->get_tenant($tenant_id);
        
        if (!$tenant) {
            wp_send_json_error(array('message' => 'Tenant not found'));
        }
        
        // Calculate progress based on status
        $progress = 0;
        $status_message = '';
        
        switch ($tenant->status) {
            case 'pending':
                $progress = 10;
                $status_message = 'Preparing your dashboard...';
                break;
            case 'provisioning':
                $progress = 50;
                $status_message = 'Setting up database and installing WordPress...';
                break;
            case 'active':
                $progress = 100;
                $status_message = 'Your dashboard is ready!';
                break;
            case 'suspended':
            case 'cancelled':
                $progress = 0;
                $status_message = 'There was an issue provisioning your dashboard. Please contact support.';
                break;
            default:
                $progress = 0;
                $status_message = 'Initializing...';
        }
        
        wp_send_json_success(array(
            'status' => $tenant->status,
            'progress' => $progress,
            'message' => $status_message,
            'dashboard_url' => 'https://' . $tenant->subdomain,
        ));
    }
    
    /**
     * Get redirect URL after registration
     */
    private function get_redirect_url() {
        return get_option('novarax_post_registration_redirect', '/marketplace');
    }
}

// Initialize the registration system
function novarax_registration_system() {
    return new NovaRax_Registration_System();
}

// Load on plugins_loaded
add_action('plugins_loaded', 'novarax_registration_system');