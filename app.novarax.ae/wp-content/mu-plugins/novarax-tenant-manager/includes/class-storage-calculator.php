<?php
/**
 * Storage Calculator Class
 * 
 * Calculates and updates storage usage for tenants including
 * database size and file uploads
 *
 * @package NovaRax\TenantManager
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class NovaRax_Storage_Calculator {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register cron hooks
        add_action('novarax_calculate_storage', array($this, 'calculate_all_tenants_storage'));
        add_filter('cron_schedules', array($this, 'add_cron_schedule'));
        // Schedule cron event if not already scheduled
        if (!wp_next_scheduled('novarax_calculate_storage')) {
            wp_schedule_event(time(), 'every_minute', 'novarax_calculate_storage');
        }
    }
    
/**
 * Add custom cron schedule
 *
 * @param array $schedules Existing schedules
 * @return array Modified schedules
 */
public function add_cron_schedule($schedules) {
    $schedules['every_minute'] = array(
        'interval' => 60,
        'display' => __('Every Minute', 'novarax-tenant-manager'),
    );
    
    return $schedules;
} 

    /**
     * Calculate storage for all active tenants
     */
    public function calculate_all_tenants_storage() {
        global $wpdb;
        
        NovaRax_Logger::log('Starting storage calculation for all tenants', 'info');
        
        $db_manager = new NovaRax_Database_Manager();
        $table_tenants = $db_manager->get_table_name('tenants');
        
        // Get all active tenants
        $tenants = $wpdb->get_results(
            "SELECT id, tenant_username, database_name, subdomain 
             FROM {$table_tenants} 
             WHERE status = 'active'"
        );
        
        if (empty($tenants)) {
            NovaRax_Logger::log('No active tenants found', 'info');
            return;
        }
        
        $updated_count = 0;
        
        foreach ($tenants as $tenant) {
            try {
                $storage_used = $this->calculate_tenant_storage($tenant->id);
                
                if ($storage_used !== false) {
                    // Update tenant storage_used
                    $wpdb->update(
                        $table_tenants,
                        array('storage_used' => $storage_used),
                        array('id' => $tenant->id),
                        array('%d'),
                        array('%d')
                    );
                    
                    $updated_count++;
                    
                    NovaRax_Logger::log(
                        "Updated storage for tenant {$tenant->tenant_username}: " . size_format($storage_used, 2),
                        'info'
                    );
                }
            } catch (Exception $e) {
                NovaRax_Logger::log(
                    "Failed to calculate storage for tenant {$tenant->tenant_username}: " . $e->getMessage(),
                    'error'
                );
            }
        }
        
        NovaRax_Logger::log("Storage calculation completed. Updated {$updated_count} tenants.", 'info');
    }
    
    /**
     * Calculate storage for a single tenant
     *
     * @param int $tenant_id Tenant ID
     * @return int|false Total storage in bytes or false on failure
     */
    public function calculate_tenant_storage($tenant_id) {
        global $wpdb;
        
        $db_manager = new NovaRax_Database_Manager();
        $table_tenants = $db_manager->get_table_name('tenants');
        
        // Get tenant info
        $tenant = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT database_name, subdomain FROM {$table_tenants} WHERE id = %d",
                $tenant_id
            )
        );
        
        if (!$tenant) {
            return false;
        }
        
        $total_storage = 0;
        
        // 1. Calculate database size
        $db_size = $db_manager->get_database_size($tenant->database_name);
        $total_storage += $db_size;
        
        // 2. Calculate uploads directory size
        $uploads_size = $this->calculate_uploads_directory_size($tenant->subdomain);
        $total_storage += $uploads_size;
        
        return $total_storage;
    }
    
    /**
     * Calculate the size of a tenant's uploads directory
     *
     * @param string $subdomain Tenant subdomain
     * @return int Size in bytes
     */
    private function calculate_uploads_directory_size($subdomain) {
        // Extract just the username from subdomain (e.g., "chris.app.novarax.ae" -> "chris")
        $username = explode('.', $subdomain)[0];
        
        // Construct the uploads path for this tenant
        // Adjust this path based on your actual setup
        $tenant_uploads_path = '/var/www/vhosts/novarax.ae/tenant-dashboard/wp-content/uploads/sites/' . $username;
        
        // Also check alternative path structure
        if (!is_dir($tenant_uploads_path)) {
            $tenant_uploads_path = '/var/www/vhosts/novarax.ae/tenant-dashboard/wp-content/uploads/' . $username;
        }
        
        if (!is_dir($tenant_uploads_path)) {
            NovaRax_Logger::log("Uploads directory not found for {$username}", 'warning');
            return 0;
        }
        
        return $this->get_directory_size($tenant_uploads_path);
    }
    
    /**
     * Recursively calculate directory size
     *
     * @param string $directory Directory path
     * @return int Size in bytes
     */
    private function get_directory_size($directory) {
        $size = 0;
        
        if (!is_dir($directory)) {
            return 0;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (Exception $e) {
            NovaRax_Logger::log("Error calculating directory size for {$directory}: " . $e->getMessage(), 'error');
        }
        
        return $size;
    }
    
    /**
     * Force recalculate storage for a specific tenant (manual trigger)
     *
     * @param int $tenant_id Tenant ID
     * @return array Result with success status and storage info
     */
    public function force_recalculate($tenant_id) {
        global $wpdb;
        
        $storage_used = $this->calculate_tenant_storage($tenant_id);
        
        if ($storage_used === false) {
            return array(
                'success' => false,
                'message' => 'Failed to calculate storage',
            );
        }
        
        $db_manager = new NovaRax_Database_Manager();
        $table_tenants = $db_manager->get_table_name('tenants');
        
        $updated = $wpdb->update(
            $table_tenants,
            array('storage_used' => $storage_used),
            array('id' => $tenant_id),
            array('%d'),
            array('%d')
        );
        
        if ($updated !== false) {
            return array(
                'success' => true,
                'storage_used' => $storage_used,
                'storage_formatted' => size_format($storage_used, 2),
                'message' => 'Storage recalculated successfully',
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Failed to update database',
        );
    }
}