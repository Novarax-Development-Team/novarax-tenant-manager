<?php
/**
 * Admin AJAX Handler Class
 * 
 * Handles all AJAX requests from the admin interface including
 * real-time validation, quick actions, and dynamic updates.
 *
 * @package NovaRax\TenantManager
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class NovaRax_Admin_Ajax {
    
    /**
     * Constructor - Register AJAX handlers
     */
    /**
 * Constructor - Register AJAX handlers
 */
public function __construct() {
    // Username and email validation
    add_action('wp_ajax_novarax_check_username', array($this, 'check_username_availability'));
    add_action('wp_ajax_novarax_check_email', array($this, 'check_email_availability'));
    
    // Tenant quick actions
    add_action('wp_ajax_novarax_activate_tenant', array($this, 'activate_tenant'));
    add_action('wp_ajax_novarax_suspend_tenant', array($this, 'suspend_tenant'));
    add_action('wp_ajax_novarax_delete_tenant', array($this, 'delete_tenant'));
    add_action('wp_ajax_novarax_provision_tenant', array($this, 'provision_tenant'));
    
    // Tenant information
    add_action('wp_ajax_novarax_get_tenant_info', array($this, 'get_tenant_info'));
    
    // Dashboard stats
    add_action('wp_ajax_novarax_get_dashboard_stats', array($this, 'get_dashboard_stats'));
    
    // Export functionality
    add_action('wp_ajax_novarax_export_tenants', array($this, 'export_tenants'));
    
    // Email testing
    add_action('wp_ajax_novarax_send_test_email', array($this, 'send_test_email'));
    
    // Log management
    add_action('wp_ajax_novarax_clean_logs', array($this, 'clean_logs'));
    add_action('wp_ajax_novarax_get_logs', array($this, 'get_logs'));
    add_action('wp_ajax_novarax_clear_all_logs', array($this, 'clear_all_logs'));
    
    // Provisioning queue
    add_action('wp_ajax_novarax_get_queue_status', array($this, 'get_queue_status'));
    add_action('wp_ajax_novarax_process_queue', array($this, 'process_queue'));
    
    // Module management
    add_action('wp_ajax_novarax_get_module', array($this, 'get_module'));
    add_action('wp_ajax_novarax_save_module', array($this, 'save_module'));
    
    // Analytics
    add_action('wp_ajax_novarax_get_analytics_data', array($this, 'get_analytics_data'));

//Storage Calculator
add_action('wp_ajax_novarax_recalculate_storage', array($this, 'recalculate_storage'));
add_action('wp_ajax_novarax_recalculate_all_storage', array($this, 'recalculate_all_storage'));

}

/**
 * Get module data
 */
public function get_module() {
    NovaRax_Security::check_ajax_nonce();
    
    if (!isset($_POST['module_id'])) {
        wp_send_json_error(array(
            'message' => __('Module ID is required', 'novarax-tenant-manager'),
        ));
    }
    
    $module_id = intval($_POST['module_id']);
    $module_manager = new NovaRax_Module_Manager();
    $module = $module_manager->get_module($module_id);
    
    if (!$module) {
        wp_send_json_error(array(
            'message' => __('Module not found', 'novarax-tenant-manager'),
        ));
    }
    
    wp_send_json_success(array(
        'module' => $module,
    ));
}

/**
 * Save module
 */
public function save_module() {
    NovaRax_Security::check_ajax_nonce();
    
    $module_id = isset($_POST['module_id']) ? intval($_POST['module_id']) : 0;
    
    $data = array(
        'module_name' => sanitize_text_field($_POST['module_name']),
        'module_slug' => sanitize_title($_POST['module_slug']),
        'plugin_path' => sanitize_text_field($_POST['plugin_path']),
        'product_id' => isset($_POST['product_id']) ? intval($_POST['product_id']) : null,
        'description' => sanitize_textarea_field($_POST['description']),
        'version' => sanitize_text_field($_POST['version']),
        'requires_php' => sanitize_text_field($_POST['requires_php']),
        'icon_url' => esc_url_raw($_POST['icon_url']),
    );
    
    $module_manager = new NovaRax_Module_Manager();
    
    if ($module_id) {
        // Update existing module
        $success = $module_manager->update_module($module_id, $data);
        $message = __('Module updated successfully', 'novarax-tenant-manager');
    } else {
        // Register new module
        $module_id = $module_manager->register_module($data);
        $success = $module_id !== false;
        $message = __('Module registered successfully', 'novarax-tenant-manager');
    }
    
    if ($success) {
        wp_send_json_success(array(
            'message' => $message,
            'module_id' => $module_id,
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Failed to save module', 'novarax-tenant-manager'),
        ));
    }
}

/**
 * Get logs via AJAX
 */
public function get_logs() {
    NovaRax_Security::check_ajax_nonce();
    
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 50;
    $level = isset($_POST['level']) ? sanitize_text_field($_POST['level']) : null;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    
    $args = array(
        'limit' => $per_page,
        'offset' => ($page - 1) * $per_page,
        'orderby' => 'created_at',
        'order' => 'DESC',
    );
    
    if ($level) {
        $args['level'] = $level;
    }
    
    $logs = NovaRax_Logger::get_logs($args);
    $total = NovaRax_Logger::get_log_count($args);
    
    wp_send_json_success(array(
        'logs' => $logs,
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($total / $per_page),
    ));
}

/**
 * Clear all logs
 */
public function clear_all_logs() {
    NovaRax_Security::check_ajax_nonce();
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => __('Permission denied', 'novarax-tenant-manager'),
        ));
    }
    
    $success = NovaRax_Logger::clear_all_logs();
    
    if ($success) {
        wp_send_json_success(array(
            'message' => __('All logs cleared successfully', 'novarax-tenant-manager'),
        ));
    } else {
        wp_send_json_error(array(
            'message' => __('Failed to clear logs', 'novarax-tenant-manager'),
        ));
    }
}

/**
 * Get analytics data
 */
public function get_analytics_data() {
    NovaRax_Security::check_ajax_nonce();
    
    $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30days';
    
    $tenant_ops = new NovaRax_Tenant_Operations();
    $module_manager = new NovaRax_Module_Manager();
    
    // Calculate date range
    switch ($period) {
        case '7days':
            $days = 7;
            break;
        case '30days':
            $days = 30;
            break;
        case '90days':
            $days = 90;
            break;
        default:
            $days = 30;
    }
    
    $data = array(
        'tenant_growth' => $this->get_tenant_growth_data($days),
        'module_activations' => $this->get_module_activations_data(),
        'status_distribution' => $this->get_status_distribution(),
        'recent_activity' => $this->get_recent_activity(10),
    );
    
    wp_send_json_success($data);
}

/**
 * Get tenant growth data
 */
private function get_tenant_growth_data($days) {
    global $wpdb;
    $table = $wpdb->prefix . 'novarax_tenants';
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(created_at) as date, COUNT(*) as count 
         FROM {$table} 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
         GROUP BY DATE(created_at)
         ORDER BY date ASC",
        $days
    ));
    
    $data = array(
        'labels' => array(),
        'values' => array(),
    );
    
    $cumulative = 0;
    foreach ($results as $row) {
        $cumulative += $row->count;
        $data['labels'][] = date('M j', strtotime($row->date));
        $data['values'][] = $cumulative;
    }
    
    return $data;
}

/**
 * Get module activations data
 */
private function get_module_activations_data() {
    global $wpdb;
    
    $results = $wpdb->get_results(
        "SELECT m.module_name, COUNT(tm.id) as activations
         FROM {$wpdb->prefix}novarax_modules m
         LEFT JOIN {$wpdb->prefix}novarax_tenant_modules tm ON m.id = tm.module_id
         WHERE m.status = 'active'
         GROUP BY m.id
         ORDER BY activations DESC
         LIMIT 10"
    );
    
    $data = array(
        'labels' => array(),
        'values' => array(),
    );
    
    foreach ($results as $row) {
        $data['labels'][] = $row->module_name;
        $data['values'][] = (int) $row->activations;
    }
    
    return $data;
}

/**
 * Get status distribution
 */
private function get_status_distribution() {
    global $wpdb;
    $table = $wpdb->prefix . 'novarax_tenants';
    
    $results = $wpdb->get_results(
        "SELECT status, COUNT(*) as count 
         FROM {$table} 
         GROUP BY status"
    );
    
    $data = array(
        'labels' => array(),
        'values' => array(),
    );
    
    foreach ($results as $row) {
        $data['labels'][] = ucfirst($row->status);
        $data['values'][] = (int) $row->count;
    }
    
    return $data;
}

/**
 * Get recent activity
 */
private function get_recent_activity($limit = 10) {
    global $wpdb;
    $table = $wpdb->prefix . 'novarax_audit_logs';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table} 
         ORDER BY created_at DESC 
         LIMIT %d",
        $limit
    ));
}
    
    /**
     * Check username availability
     */
    public function check_username_availability() {
        NovaRax_Security::check_ajax_nonce();
        
        if (!isset($_POST['username'])) {
            wp_send_json_error(array(
                'message' => __('Username is required', 'novarax-tenant-manager'),
            ));
        }
        
        $username = sanitize_text_field($_POST['username']);
        $validator = new NovaRax_Tenant_Validator();
        
        $result = $validator->check_username_availability($username);
        
        if ($result['available']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Check email availability
     */
    public function check_email_availability() {
        NovaRax_Security::check_ajax_nonce();
        
        if (!isset($_POST['email'])) {
            wp_send_json_error(array(
                'message' => __('Email is required', 'novarax-tenant-manager'),
            ));
        }
        
        $email = sanitize_email($_POST['email']);
        
        if (!is_email($email)) {
            wp_send_json_error(array(
                'message' => __('Invalid email format', 'novarax-tenant-manager'),
            ));
        }
        
        if (email_exists($email)) {
            wp_send_json_error(array(
                'message' => __('Email already exists', 'novarax-tenant-manager'),
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Email is available', 'novarax-tenant-manager'),
        ));
    }
    
    /**
     * Activate tenant
     */
    public function activate_tenant() {
        NovaRax_Security::check_ajax_nonce();
        
        if (!isset($_POST['tenant_id'])) {
            wp_send_json_error(array(
                'message' => __('Tenant ID is required', 'novarax-tenant-manager'),
            ));
        }
        
        $tenant_id = intval($_POST['tenant_id']);
        $tenant_ops = new NovaRax_Tenant_Operations();
        
        $success = $tenant_ops->update_tenant_status($tenant_id, 'active');
        
        if ($success) {
            wp_send_json_success(array(
                'message' => __('Tenant activated successfully', 'novarax-tenant-manager'),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to activate tenant', 'novarax-tenant-manager'),
            ));
        }
    }
    
    /**
     * Suspend tenant
     */
    public function suspend_tenant() {
        NovaRax_Security::check_ajax_nonce();
        
        if (!isset($_POST['tenant_id'])) {
            wp_send_json_error(array(
                'message' => __('Tenant ID is required', 'novarax-tenant-manager'),
            ));
        }
        
        $tenant_id = intval($_POST['tenant_id']);
        $tenant_ops = new NovaRax_Tenant_Operations();
        
        $success = $tenant_ops->update_tenant_status($tenant_id, 'suspended');
        
        if ($success) {
            // Send suspension email
            $tenant = $tenant_ops->get_tenant($tenant_id);
            if ($tenant) {
                NovaRax_Email_Notifications::send_suspension_email(
                    $tenant->user_id,
                    $tenant_id,
                    __('Manual suspension by administrator', 'novarax-tenant-manager')
                );
            }
            
            wp_send_json_success(array(
                'message' => __('Tenant suspended successfully', 'novarax-tenant-manager'),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to suspend tenant', 'novarax-tenant-manager'),
            ));
        }
    }
    
    /**
     * Delete tenant
     */
    public function delete_tenant() {
        NovaRax_Security::check_ajax_nonce();
        
        if (!isset($_POST['tenant_id'])) {
            wp_send_json_error(array(
                'message' => __('Tenant ID is required', 'novarax-tenant-manager'),
            ));
        }
        
        $tenant_id = intval($_POST['tenant_id']);
        $hard_delete = isset($_POST['hard_delete']) && $_POST['hard_delete'] === 'true';
        
        $tenant_ops = new NovaRax_Tenant_Operations();
        $result = $tenant_ops->delete_tenant($tenant_id, $hard_delete);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $hard_delete 
                    ? __('Tenant permanently deleted', 'novarax-tenant-manager')
                    : __('Tenant suspended', 'novarax-tenant-manager'),
                'backup_path' => isset($result['backup_path']) ? $result['backup_path'] : null,
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['error'],
            ));
        }
    }
    
    /**
     * Provision tenant
     */
    public function provision_tenant() {
        NovaRax_Security::check_ajax_nonce();
        
        if (!isset($_POST['tenant_id'])) {
            wp_send_json_error(array(
                'message' => __('Tenant ID is required', 'novarax-tenant-manager'),
            ));
        }
        
        $tenant_id = intval($_POST['tenant_id']);
        $tenant_ops = new NovaRax_Tenant_Operations();
        
        // Check if already provisioned
        $tenant = $tenant_ops->get_tenant($tenant_id);
        
        if (!$tenant) {
            wp_send_json_error(array(
                'message' => __('Tenant not found', 'novarax-tenant-manager'),
            ));
        }
        
        if ($tenant->status === 'active') {
            wp_send_json_error(array(
                'message' => __('Tenant is already provisioned', 'novarax-tenant-manager'),
            ));
        }
        
        // Add to provisioning queue
        $queue = new NovaRax_Provisioning_Queue();
        $added = $queue->add_to_queue($tenant_id, 1); // High priority
        
        if ($added) {
            wp_send_json_success(array(
                'message' => __('Tenant added to provisioning queue', 'novarax-tenant-manager'),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to add tenant to queue', 'novarax-tenant-manager'),
            ));
        }
    }
    
    /**
     * Get tenant info
     */
    public function get_tenant_info() {
        NovaRax_Security::check_ajax_nonce();
        
        if (!isset($_POST['tenant_id'])) {
            wp_send_json_error(array(
                'message' => __('Tenant ID is required', 'novarax-tenant-manager'),
            ));
        }
        
        $tenant_id = intval($_POST['tenant_id']);
        $tenant_ops = new NovaRax_Tenant_Operations();
        $tenant = $tenant_ops->get_tenant($tenant_id);
        
        if (!$tenant) {
            wp_send_json_error(array(
                'message' => __('Tenant not found', 'novarax-tenant-manager'),
            ));
        }
        
        // Get user info
        $user = get_userdata($tenant->user_id);
        
        wp_send_json_success(array(
            'tenant' => $tenant,
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
            ),
        ));
    }
    
/**
 * Recalculate tenant storage
 */
public function recalculate_storage() {
    NovaRax_Security::check_ajax_nonce();
    
    if (!isset($_POST['tenant_id'])) {
        wp_send_json_error(array(
            'message' => __('Tenant ID is required', 'novarax-tenant-manager'),
        ));
    }
    
    $tenant_id = absint($_POST['tenant_id']);
    
    // Check if tenant exists
    $tenant_ops = new NovaRax_Tenant_Operations();
    $tenant = $tenant_ops->get_tenant($tenant_id);
    
    if (!$tenant) {
        wp_send_json_error(array(
            'message' => __('Tenant not found', 'novarax-tenant-manager'),
        ));
    }
    
    // Calculate storage
    $calculator = new NovaRax_Storage_Calculator();
    $result = $calculator->force_recalculate($tenant_id);
    
    if ($result['success']) {
        // Get updated tenant data
        $tenant = $tenant_ops->get_tenant($tenant_id);
        
        $percentage = $tenant->storage_limit > 0 
            ? round(($tenant->storage_used / $tenant->storage_limit) * 100, 2)
            : 0;
        
        wp_send_json_success(array(
            'message' => __('Storage recalculated successfully', 'novarax-tenant-manager'),
            'storage_used' => $tenant->storage_used,
            'storage_used_formatted' => size_format($tenant->storage_used, 2),
            'storage_limit_formatted' => size_format($tenant->storage_limit, 2),
            'percentage' => $percentage,
        ));
    } else {
        wp_send_json_error(array(
            'message' => $result['message'],
        ));
    }
}

/**
 * Recalculate all tenants storage
 */
public function recalculate_all_storage() {
    NovaRax_Security::check_ajax_nonce();
    
    // This could take a while
    set_time_limit(300);
    
    $calculator = new NovaRax_Storage_Calculator();
    $calculator->calculate_all_tenants_storage();
    
    wp_send_json_success(array(
        'message' => __('Storage recalculated for all tenants', 'novarax-tenant-manager'),
    ));
} 



    /**
     * Get dashboard statistics
     */
    public function get_dashboard_stats() {
        NovaRax_Security::check_ajax_nonce();
        
        $tenant_ops = new NovaRax_Tenant_Operations();
        
        $stats = array(
            'total_tenants' => $tenant_ops->get_tenant_count(),
            'active_tenants' => $tenant_ops->get_tenant_count(array('status' => 'active')),
            'pending_tenants' => $tenant_ops->get_tenant_count(array('status' => 'pending')),
            'suspended_tenants' => $tenant_ops->get_tenant_count(array('status' => 'suspended')),
        );
        
        // Get provisioning queue stats
        $queue = new NovaRax_Provisioning_Queue();
        $queue_stats = $queue->get_statistics();
        
        $stats['queue'] = $queue_stats;
        
        // Get log stats
        $log_stats = NovaRax_Logger::get_statistics();
        $stats['logs'] = $log_stats;
        
        wp_send_json_success($stats);
    }
    
    /**
     * Export tenants to CSV
     */
    public function export_tenants() {
        NovaRax_Security::check_ajax_nonce();
        
        $tenant_ops = new NovaRax_Tenant_Operations();
        
        // Get filters
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null;
        
        $args = array(
            'limit' => 999999, // All tenants
            'offset' => 0,
        );
        
        if ($status && $status !== 'all') {
            $args['status'] = $status;
        }
        
        $tenants = $tenant_ops->get_tenants($args);
        
        if (empty($tenants)) {
            wp_send_json_error(array(
                'message' => __('No tenants to export', 'novarax-tenant-manager'),
            ));
        }
        
        // Create CSV
        $filename = 'novarax-tenants-' . date('Y-m-d-H-i-s') . '.csv';
        $upload_dir = wp_upload_dir();
        $filepath = $upload_dir['basedir'] . '/novarax-exports/' . $filename;
        
        // Create directory if it doesn't exist
        wp_mkdir_p(dirname($filepath));
        
        $fp = fopen($filepath, 'w');
        
        // CSV headers
        fputcsv($fp, array(
            'ID',
            'Account Name',
            'Username',
            'Subdomain',
            'Company',
            'Email',
            'Phone',
            'Status',
            'Storage Used',
            'Storage Limit',
            'Created Date',
        ));
        
        // CSV rows
        foreach ($tenants as $tenant) {
            fputcsv($fp, array(
                $tenant->id,
                $tenant->account_name,
                $tenant->tenant_username,
                $tenant->subdomain,
                $tenant->company_name,
                $tenant->billing_email,
                $tenant->phone_number,
                $tenant->status,
                size_format($tenant->storage_used, 2),
                size_format($tenant->storage_limit, 2),
                $tenant->created_at,
            ));
        }
        
        fclose($fp);
        
        wp_send_json_success(array(
            'message' => __('Export completed', 'novarax-tenant-manager'),
            'download_url' => $upload_dir['baseurl'] . '/novarax-exports/' . $filename,
            'filename' => $filename,
            'count' => count($tenants),
        ));
    }
    
    /**
     * Send test email
     */
    public function send_test_email() {
        NovaRax_Security::check_ajax_nonce();
        
        if (!isset($_POST['email'])) {
            wp_send_json_error(array(
                'message' => __('Email address is required', 'novarax-tenant-manager'),
            ));
        }
        
        $email = sanitize_email($_POST['email']);
        
        if (!is_email($email)) {
            wp_send_json_error(array(
                'message' => __('Invalid email address', 'novarax-tenant-manager'),
            ));
        }
        
        $sent = NovaRax_Email_Notifications::send_test_email($email);
        
        if ($sent) {
            wp_send_json_success(array(
                'message' => __('Test email sent successfully', 'novarax-tenant-manager'),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to send test email', 'novarax-tenant-manager'),
            ));
        }
    }
    
    /**
     * Clean old logs
     */
    public function clean_logs() {
        NovaRax_Security::check_ajax_nonce();
        
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        
        $deleted = NovaRax_Logger::clean_old_logs($days);
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Cleaned %d old log entries', 'novarax-tenant-manager'),
                $deleted
            ),
            'deleted' => $deleted,
        ));
    }
    
    /**
     * Get provisioning queue status
     */
    public function get_queue_status() {
        NovaRax_Security::check_ajax_nonce();
        
        $queue = new NovaRax_Provisioning_Queue();
        $stats = $queue->get_statistics();
        $items = $queue->get_queue();
        
        wp_send_json_success(array(
            'stats' => $stats,
            'items' => $items,
        ));
    }
    
    /**
     * Process provisioning queue manually
     */
    public function process_queue() {
        NovaRax_Security::check_ajax_nonce();
        
        $queue = new NovaRax_Provisioning_Queue();
        
        if ($queue->is_processing()) {
            wp_send_json_error(array(
                'message' => __('Queue is already being processed', 'novarax-tenant-manager'),
            ));
        }
        
        $triggered = $queue->trigger_processing();
        
        if ($triggered) {
            wp_send_json_success(array(
                'message' => __('Queue processing triggered', 'novarax-tenant-manager'),
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to trigger queue processing', 'novarax-tenant-manager'),
            ));
        }
    }
}