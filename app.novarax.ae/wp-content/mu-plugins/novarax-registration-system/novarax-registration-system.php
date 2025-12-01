<?php
/**
 * NovaRax Registration & Marketplace System 
 * File: novarax-registration-system.php
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
        
        // AJAX handlers
        add_action('wp_ajax_novarax_register_user', array($this, 'ajax_register_user'));
        add_action('wp_ajax_novarax_check_username', array($this, 'ajax_check_username'));
        add_action('wp_ajax_novarax_get_phone_countries', array($this, 'ajax_get_phone_countries'));
        add_action('wp_ajax_novarax_check_provisioning', array($this, 'ajax_check_provisioning_status'));
        
        // For non-logged-in users
        add_action('wp_ajax_nopriv_novarax_register_user', array($this, 'ajax_register_user'));
        add_action('wp_ajax_nopriv_novarax_check_username', array($this, 'ajax_check_username'));
        add_action('wp_ajax_nopriv_novarax_get_phone_countries', array($this, 'ajax_get_phone_countries'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add settings tab
        add_filter('novarax_settings_tabs', array($this, 'add_settings_tab'));
        add_action('novarax_settings_tab_registration', array($this, 'render_settings_tab'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        // Only load on pages with our shortcodes
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
            plugins_url('assets/css/registration.css', __FILE__),
            array(),
            '1.0.0'
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'novarax-registration',
            plugins_url('assets/js/registration.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Localize script
        wp_localize_script('novarax-registration', 'novaraxAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('novarax_registration'),
            'redirectUrl' => $this->get_redirect_url(),
            'loginUrl' => $this->get_login_url(),
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
     * AJAX: Register user
     */
    public function ajax_register_user() {
        check_ajax_referer('novarax_registration', 'nonce');
        
        // Get form data
        $full_name = sanitize_text_field($_POST['full_name'] ?? '');
        $username = sanitize_user($_POST['username'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $company = sanitize_text_field($_POST['company'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $country_code = sanitize_text_field($_POST['country_code'] ?? '');
        $address = sanitize_textarea_field($_POST['address'] ?? '');
        
        // Validate
        if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Please fill in all required fields.'));
        }
        
        // Create tenant (status: pending)
        $tenant_ops = new NovaRax_Tenant_Operations();
        
        $tenant_data = array(
            'full_name' => $full_name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'company_name' => $company,
            'phone_number' => $country_code . $phone,
            'address' => $address,
            'status' => 'pending', // Don't provision yet
        );
        
        $result = $tenant_ops->create_tenant($tenant_data);
        
        if ($result['success']) {
            // Log user in
            $user = get_user_by('id', $result['user_id']);
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID, true);
            
            wp_send_json_success(array(
                'tenant_id' => $result['tenant_id'],
                'message' => 'Account created successfully!',
                'redirect_url' => $this->get_redirect_url(),
            ));
        } else {
            wp_send_json_error(array('message' => $result['error']));
        }
    }
    
    /**
     * AJAX: Check username availability
     */
    public function ajax_check_username() {
        check_ajax_referer('novarax_registration', 'nonce');
        
        $username = sanitize_user($_POST['username'] ?? '');
        
        if (empty($username)) {
            wp_send_json_success(array('available' => false, 'message' => 'Username is required'));
        }
        
        $validator = new NovaRax_Tenant_Validator();
        $result = $validator->check_username_availability($username);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Get phone countries
     */
    public function ajax_get_phone_countries() {
        $countries = $this->get_phone_countries();
        wp_send_json_success($countries);
    }
    
    /**
     * AJAX: Check provisioning status
     */
    public function ajax_check_provisioning_status() {
        check_ajax_referer('novarax_registration', 'nonce');
        
        $tenant_id = intval($_POST['tenant_id'] ?? 0);
        
        if (!$tenant_id) {
            wp_send_json_error(array('message' => 'Invalid tenant ID'));
        }
        
        global $wpdb;
        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT status, subdomain FROM {$wpdb->prefix}novarax_tenants WHERE id = %d",
            $tenant_id
        ));
        
        if (!$tenant) {
            wp_send_json_error(array('message' => 'Tenant not found'));
        }
        
        $progress = 0;
        $status_message = '';
        
        switch ($tenant->status) {
            case 'pending':
                $progress = 10;
                $status_message = 'Preparing your workspace...';
                break;
            case 'provisioning':
                $progress = 50;
                $status_message = 'Creating your database...';
                break;
            case 'active':
                $progress = 100;
                $status_message = 'Your dashboard is ready!';
                break;
            default:
                $progress = 0;
                $status_message = 'Initializing...';
        }
        
        wp_send_json_success(array(
            'status' => $tenant->status,
            'progress' => $progress,
            'message' => $status_message,
            'dashboard_url' => 'https://' . $tenant->subdomain . '/' . $this->get_login_url(),
        ));
    }
    
    /**
     * Get redirect URL after registration
     */
    private function get_redirect_url() {
        return get_option('novarax_post_registration_redirect', '/marketplace');
    }
    
    /**
     * Get tenant dashboard login URL
     */
    private function get_login_url() {
        return get_option('novarax_tenant_login_slug', 'wp-login.php');
    }
    
    /**
     * Get phone countries list
     */
    private function get_phone_countries() {
        return array(
            array('code' => 'US', 'name' => 'United States', 'dial' => '+1', 'flag' => '🇺🇸'),
            array('code' => 'GB', 'name' => 'United Kingdom', 'dial' => '+44', 'flag' => '🇬🇧'),
            array('code' => 'AE', 'name' => 'United Arab Emirates', 'dial' => '+971', 'flag' => '🇦🇪'),
            array('code' => 'LB', 'name' => 'Lebanon', 'dial' => '+961', 'flag' => '🇱🇧'),
            array('code' => 'FR', 'name' => 'France', 'dial' => '+33', 'flag' => '🇫🇷'),
            array('code' => 'DE', 'name' => 'Germany', 'dial' => '+49', 'flag' => '🇩🇪'),
            array('code' => 'CA', 'name' => 'Canada', 'dial' => '+1', 'flag' => '🇨🇦'),
            array('code' => 'AU', 'name' => 'Australia', 'dial' => '+61', 'flag' => '🇦🇺'),
            // Add more countries as needed
        );
    }
    
    /**
     * Add settings tab
     */
    public function add_settings_tab($tabs) {
        $tabs['registration'] = __('Registration & UX', 'novarax-tenant-manager');
        return $tabs;
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('novarax_registration_settings', 'novarax_post_registration_redirect');
        register_setting('novarax_registration_settings', 'novarax_tenant_login_slug');
        register_setting('novarax_registration_settings', 'novarax_enable_phone_verification');
        register_setting('novarax_registration_settings', 'novarax_enable_email_verification');
        register_setting('novarax_registration_settings', 'novarax_minimum_password_length');
        register_setting('novarax_registration_settings', 'novarax_allow_public_registration');
    }
    
    /**
     * Render settings tab
     */
    public function render_settings_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('novarax_registration_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="novarax_post_registration_redirect">Post-Registration Redirect</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="novarax_post_registration_redirect" 
                               name="novarax_post_registration_redirect" 
                               value="<?php echo esc_attr(get_option('novarax_post_registration_redirect', '/marketplace')); ?>" 
                               class="regular-text" 
                               placeholder="/marketplace">
                        <p class="description">URL path to redirect users after registration (e.g., /marketplace, /onboarding)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="novarax_tenant_login_slug">Tenant Login URL</label>
                    </th>
                    <td>
                        <input type="text" 
                               id="novarax_tenant_login_slug" 
                               name="novarax_tenant_login_slug" 
                               value="<?php echo esc_attr(get_option('novarax_tenant_login_slug', 'wp-login.php')); ?>" 
                               class="regular-text" 
                               placeholder="wp-login.php">
                        <p class="description">Login page slug for tenant dashboards (e.g., wp-login.php, login, dashboard)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="novarax_minimum_password_length">Minimum Password Length</label>
                    </th>
                    <td>
                        <input type="number" 
                               id="novarax_minimum_password_length" 
                               name="novarax_minimum_password_length" 
                               value="<?php echo esc_attr(get_option('novarax_minimum_password_length', '12')); ?>" 
                               min="8" 
                               max="32" 
                               class="small-text">
                        <p class="description">Minimum characters required for passwords (default: 12)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Allow Public Registration</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="novarax_allow_public_registration" 
                                   value="1" 
                                   <?php checked(get_option('novarax_allow_public_registration', '1'), '1'); ?>>
                            Enable public registration form
                        </label>
                        <p class="description">Uncheck to disable public signups (invite-only mode)</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Email Verification</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="novarax_enable_email_verification" 
                                   value="1" 
                                   <?php checked(get_option('novarax_enable_email_verification', '0'), '1'); ?>>
                            Require email verification before provisioning
                        </label>
                        <p class="description">Users must verify their email before accessing the marketplace</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Phone Verification</th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="novarax_enable_phone_verification" 
                                   value="1" 
                                   <?php checked(get_option('novarax_enable_phone_verification', '0'), '1'); ?>>
                            Require phone verification (SMS)
                        </label>
                        <p class="description">Requires SMS gateway integration (Twilio, etc.)</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Settings'); ?>
        </form>
        <?php
    }
}

// Initialize
new NovaRax_Registration_System();