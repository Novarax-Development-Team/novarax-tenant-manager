<?php
/**
 * API Endpoints Class
 * 
 * Contains the actual logic for each REST API endpoint.
 * Called by NovaRax_API_Handler after authentication/routing.
 *
 * @package NovaRax\TenantManager
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class NovaRax_API_Endpoints {
    
    /**
     * Tenant operations instance
     *
     * @var NovaRax_Tenant_Operations
     */
    private $tenant_ops;
    
    /**
     * Module manager instance
     *
     * @var NovaRax_Module_Manager
     */
    private $module_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->tenant_ops = new NovaRax_Tenant_Operations();
        $this->module_manager = new NovaRax_Module_Manager();
    }
    
    /**
     * Validate user session
     * 
     * Checks if JWT token is valid and returns user/tenant information
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function validate_session($request) {
        $token = $request->get_param('token');
        $subdomain = $request->get_param('subdomain');
        
        // Validate token parameter
        if (empty($token)) {
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => __('Token is required', 'novarax-tenant-manager'),
            ), 400);
        }
        
        // Verify JWT token
        $payload = NovaRax_Security::verify_jwt($token);
        
        if (!$payload) {
            NovaRax_Logger::warning('Invalid JWT token provided', array(
                'subdomain' => $subdomain,
                'ip' => NovaRax_Security::get_client_ip(),
            ));
            
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => __('Invalid or expired token', 'novarax-tenant-manager'),
            ), 401);
        }
        
        // Extract user info from payload
        $user_id = isset($payload['user_id']) ? $payload['user_id'] : null;
        
        if (!$user_id) {
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => __('Invalid token payload', 'novarax-tenant-manager'),
            ), 401);
        }
        
        // Get user
        $user = get_userdata($user_id);
        
        if (!$user) {
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => __('User not found', 'novarax-tenant-manager'),
            ), 404);
        }
        
        // Get tenant info
        $tenant = null;
        
        if ($subdomain) {
            // Get tenant by subdomain
            $tenant = $this->tenant_ops->get_tenant_by_subdomain($subdomain);
        } else {
            // Get tenant by user ID
            $tenant = $this->tenant_ops->get_tenant_by_user_id($user_id);
        }
        
        if (!$tenant) {
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => __('Tenant not found', 'novarax-tenant-manager'),
            ), 404);
        }
        
        // Verify user belongs to this tenant
        if ($tenant->user_id !== $user_id) {
            NovaRax_Logger::warning('User attempting to access wrong tenant', array(
                'user_id' => $user_id,
                'tenant_id' => $tenant->id,
                'subdomain' => $subdomain,
            ));
            
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => __('Access denied', 'novarax-tenant-manager'),
            ), 403);
        }
        
        // Check tenant status
        if ($tenant->status !== 'active') {
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => sprintf(
                    __('Tenant account is %s', 'novarax-tenant-manager'),
                    $tenant->status
                ),
                'status' => $tenant->status,
            ), 403);
        }
        
        // Update last login
        $this->tenant_ops->update_last_login($tenant->id);
        
        // Return success with user and tenant info
        return new WP_REST_Response(array(
            'valid' => true,
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
                'roles' => $user->roles,
            ),
            'tenant' => array(
                'id' => $tenant->id,
                'username' => $tenant->tenant_username,
                'account_name' => $tenant->account_name,
                'company_name' => $tenant->company_name,
                'subdomain' => $tenant->subdomain,
                'status' => $tenant->status,
                'storage_used' => $tenant->storage_used,
                'storage_limit' => $tenant->storage_limit,
            ),
        ), 200);
    }
    
    /**
     * Check module license
     * 
     * Verifies if tenant has active license/subscription for a module
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function check_license($request) {
        $tenant_id = $request->get_param('tenant_id');
        $module_slug = $request->get_param('module_slug');
        
        // Validate parameters
        if (!$tenant_id || !$module_slug) {
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => __('tenant_id and module_slug are required', 'novarax-tenant-manager'),
            ), 400);
        }
        
        // Get tenant
        $tenant = $this->tenant_ops->get_tenant($tenant_id);
        
        if (!$tenant) {
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => __('Tenant not found', 'novarax-tenant-manager'),
            ), 404);
        }
        
        // Check tenant status
        if ($tenant->status !== 'active') {
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => __('Tenant account is not active', 'novarax-tenant-manager'),
                'tenant_status' => $tenant->status,
            ), 403);
        }
        
        // Get module
        $module = $this->module_manager->get_module_by_slug($module_slug);
        
        if (!$module) {
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => __('Module not found', 'novarax-tenant-manager'),
                'module_slug' => $module_slug,
            ), 404);
        }
        
        // Check if tenant has access to this module
        $has_access = $this->module_manager->tenant_has_module_access($tenant_id, $module->id);
        
        if (!$has_access) {
            NovaRax_Logger::info("License check failed for tenant {$tenant_id}, module {$module_slug}");
            
            return new WP_REST_Response(array(
                'valid' => false,
                'message' => __('No active license for this module', 'novarax-tenant-manager'),
                'module' => array(
                    'slug' => $module->module_slug,
                    'name' => $module->module_name,
                ),
            ), 403);
        }
        
        // Get tenant module details
        $tenant_module = $this->module_manager->get_tenant_module_by_slug($tenant_id, $module_slug);
        
        // Calculate days until expiration
        $days_until_expiration = null;
        if ($tenant_module && $tenant_module->expires_at) {
            $now = time();
            $expires = strtotime($tenant_module->expires_at);
            $days_until_expiration = max(0, ceil(($expires - $now) / 86400));
        }
        
        return new WP_REST_Response(array(
            'valid' => true,
            'module' => array(
                'id' => $module->id,
                'slug' => $module->module_slug,
                'name' => $module->module_name,
                'version' => $module->version,
                'plugin_path' => $module->plugin_path,
            ),
            'license' => array(
                'status' => $tenant_module->status,
                'activated_at' => $tenant_module->activated_at,
                'expires_at' => $tenant_module->expires_at,
                'days_until_expiration' => $days_until_expiration,
                'grace_period_ends' => $tenant_module->grace_period_ends,
            ),
        ), 200);
    }
    
    /**
     * Get tenant information
     * 
     * Returns complete tenant details including active modules
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_tenant_info($request) {
        $tenant_id = $request->get_param('tenant_id');
        $subdomain = $request->get_param('subdomain');
        
        // Must provide either tenant_id or subdomain
        if (!$tenant_id && !$subdomain) {
            return new WP_REST_Response(array(
                'error' => __('tenant_id or subdomain is required', 'novarax-tenant-manager'),
            ), 400);
        }
        
        // Get tenant
        if ($tenant_id) {
            $tenant = $this->tenant_ops->get_tenant($tenant_id);
        } else {
            $tenant = $this->tenant_ops->get_tenant_by_subdomain($subdomain);
        }
        
        if (!$tenant) {
            return new WP_REST_Response(array(
                'error' => __('Tenant not found', 'novarax-tenant-manager'),
            ), 404);
        }
        
        // Get user info
        $user = get_userdata($tenant->user_id);
        
        if (!$user) {
            return new WP_REST_Response(array(
                'error' => __('User not found', 'novarax-tenant-manager'),
            ), 404);
        }
        
        // Get active modules
        $active_modules = $this->module_manager->get_tenant_active_modules($tenant->id);
        
        // Format modules array
        $modules_data = array();
        foreach ($active_modules as $module) {
            $has_access = $this->module_manager->tenant_has_module_access($tenant->id, $module->module_id);
            
            $modules_data[] = array(
                'id' => $module->module_id,
                'slug' => $module->module_slug,
                'name' => $module->module_name,
                'status' => $module->status,
                'has_access' => $has_access,
                'activated_at' => $module->activated_at,
                'expires_at' => $module->expires_at,
                'grace_period_ends' => $module->grace_period_ends,
            );
        }
        
        // Calculate storage percentage
        $storage_percentage = $tenant->storage_limit > 0 
            ? round(($tenant->storage_used / $tenant->storage_limit) * 100, 2) 
            : 0;
        
        return new WP_REST_Response(array(
            'tenant' => array(
                'id' => $tenant->id,
                'username' => $tenant->tenant_username,
                'account_name' => $tenant->account_name,
                'company_name' => $tenant->company_name,
                'subdomain' => $tenant->subdomain,
                'database_name' => $tenant->database_name,
                'status' => $tenant->status,
                'storage_used' => $tenant->storage_used,
                'storage_limit' => $tenant->storage_limit,
                'storage_percentage' => $storage_percentage,
                'user_limit' => $tenant->user_limit,
                'phone_number' => $tenant->phone_number,
                'address' => $tenant->address,
                'billing_email' => $tenant->billing_email,
                'created_at' => $tenant->created_at,
                'last_login' => $tenant->last_login,
            ),
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'display_name' => $user->display_name,
            ),
            'modules' => $modules_data,
            'module_count' => count($modules_data),
            'active_module_count' => count(array_filter($modules_data, function($m) {
                return $m['has_access'];
            })),
        ), 200);
    }
    
    /**
     * Get module status
     * 
     * Returns status of all modules for a tenant
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_module_status($request) {
        $tenant_id = $request->get_param('tenant_id');
        
        if (!$tenant_id) {
            return new WP_REST_Response(array(
                'error' => __('tenant_id is required', 'novarax-tenant-manager'),
            ), 400);
        }
        
        // Verify tenant exists
        $tenant = $this->tenant_ops->get_tenant($tenant_id);
        
        if (!$tenant) {
            return new WP_REST_Response(array(
                'error' => __('Tenant not found', 'novarax-tenant-manager'),
            ), 404);
        }
        
        // Get all active modules
        $active_modules = $this->module_manager->get_tenant_active_modules($tenant_id);
        
        $modules = array();
        
        foreach ($active_modules as $module) {
            $has_access = $this->module_manager->tenant_has_module_access($tenant_id, $module->module_id);
            
            // Calculate days remaining
            $days_remaining = null;
            if ($module->expires_at) {
                $now = time();
                $expires = strtotime($module->expires_at);
                $days_remaining = max(0, ceil(($expires - $now) / 86400));
            }
            
            $modules[] = array(
                'slug' => $module->module_slug,
                'name' => $module->module_name,
                'status' => $module->status,
                'has_access' => $has_access,
                'plugin_path' => $module->plugin_path,
                'activated_at' => $module->activated_at,
                'expires_at' => $module->expires_at,
                'days_remaining' => $days_remaining,
                'grace_period_ends' => $module->grace_period_ends,
                'last_checked' => $module->last_checked,
            );
        }
        
        return new WP_REST_Response(array(
            'tenant_id' => $tenant_id,
            'tenant_status' => $tenant->status,
            'modules' => $modules,
            'total_modules' => count($modules),
            'active_modules' => count(array_filter($modules, function($m) {
                return $m['has_access'];
            })),
            'checked_at' => current_time('mysql'),
        ), 200);
    }
    
    /**
     * Verify tenant access
     * 
     * Quick check if tenant/user combination is valid and active
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function verify_access($request) {
        $tenant_id = $request->get_param('tenant_id');
        $user_id = $request->get_param('user_id');
        
        if (!$tenant_id || !$user_id) {
            return new WP_REST_Response(array(
                'access' => false,
                'message' => __('tenant_id and user_id are required', 'novarax-tenant-manager'),
            ), 400);
        }
        
        // Get tenant
        $tenant = $this->tenant_ops->get_tenant($tenant_id);
        
        if (!$tenant) {
            return new WP_REST_Response(array(
                'access' => false,
                'message' => __('Tenant not found', 'novarax-tenant-manager'),
            ), 404);
        }
        
        // Verify user belongs to tenant
        if ($tenant->user_id !== $user_id) {
            NovaRax_Logger::warning('Access verification failed: user mismatch', array(
                'tenant_id' => $tenant_id,
                'expected_user' => $tenant->user_id,
                'provided_user' => $user_id,
            ));
            
            return new WP_REST_Response(array(
                'access' => false,
                'message' => __('User does not belong to this tenant', 'novarax-tenant-manager'),
            ), 403);
        }
        
        // Check tenant status
        if ($tenant->status !== 'active') {
            return new WP_REST_Response(array(
                'access' => false,
                'message' => sprintf(
                    __('Tenant account is %s', 'novarax-tenant-manager'),
                    $tenant->status
                ),
                'status' => $tenant->status,
            ), 403);
        }
        
        // All checks passed
        return new WP_REST_Response(array(
            'access' => true,
            'tenant_id' => $tenant->id,
            'status' => $tenant->status,
            'subdomain' => $tenant->subdomain,
        ), 200);
    }
    
    /**
     * Update tenant activity
     * 
     * Records tenant activity for analytics and monitoring
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function update_activity($request) {
        $tenant_id = $request->get_param('tenant_id');
        $activity_type = $request->get_param('type');
        $activity_data = $request->get_param('data');
        
        if (!$tenant_id) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('tenant_id is required', 'novarax-tenant-manager'),
            ), 400);
        }
        
        // Verify tenant exists
        $tenant = $this->tenant_ops->get_tenant($tenant_id);
        
        if (!$tenant) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => __('Tenant not found', 'novarax-tenant-manager'),
            ), 404);
        }
        
        // Update last login time
        $this->tenant_ops->update_last_login($tenant_id);
        
        // Log activity if type is provided
        if ($activity_type) {
            NovaRax_Logger::info("Tenant activity: {$activity_type}", array(
                'tenant_id' => $tenant_id,
                'type' => $activity_type,
                'data' => $activity_data,
                'ip' => NovaRax_Security::get_client_ip(),
            ));
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'timestamp' => current_time('mysql'),
        ), 200);
    }
    
    /**
     * Get tenant statistics
     * 
     * Returns usage statistics for a tenant
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function get_tenant_statistics($request) {
        $tenant_id = $request->get_param('tenant_id');
        
        if (!$tenant_id) {
            return new WP_REST_Response(array(
                'error' => __('tenant_id is required', 'novarax-tenant-manager'),
            ), 400);
        }
        
        $tenant = $this->tenant_ops->get_tenant($tenant_id);
        
        if (!$tenant) {
            return new WP_REST_Response(array(
                'error' => __('Tenant not found', 'novarax-tenant-manager'),
            ), 404);
        }
        
        // Get database size
        $db_manager = new NovaRax_Database_Manager();
        $db_size = $db_manager->get_database_size($tenant->database_name);
        
        // Get module count
        $active_modules = $this->module_manager->get_tenant_active_modules($tenant_id);
        
        // Calculate account age
        $created = strtotime($tenant->created_at);
        $account_age_days = floor((time() - $created) / 86400);
        
        return new WP_REST_Response(array(
            'tenant_id' => $tenant_id,
            'statistics' => array(
                'storage_used' => $tenant->storage_used,
                'storage_limit' => $tenant->storage_limit,
                'storage_percentage' => round(($tenant->storage_used / $tenant->storage_limit) * 100, 2),
                'database_size' => $db_size,
                'total_modules' => count($active_modules),
                'active_modules' => count(array_filter($active_modules, function($m) use ($tenant_id) {
                    return $this->module_manager->tenant_has_module_access($tenant_id, $m->module_id);
                })),
                'account_age_days' => $account_age_days,
                'created_at' => $tenant->created_at,
                'last_login' => $tenant->last_login,
            ),
            'generated_at' => current_time('mysql'),
        ), 200);
    }
    
    /**
     * Health check endpoint
     * 
     * Simple endpoint to verify API is responding
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response
     */
    public function health_check($request) {
        return new WP_REST_Response(array(
            'status' => 'healthy',
            'timestamp' => current_time('mysql'),
            'version' => NOVARAX_TM_VERSION,
        ), 200);
    }
}