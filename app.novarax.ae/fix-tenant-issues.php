<?php
/**
 * NovaRax Tenant Fix Script
 * 
 * This script fixes three critical issues:
 * 1. Makes existing tenant users administrators in their databases
 * 2. Ensures database passwords are stored in metadata
 * 3. Repairs broken database connections
 * 
 * Upload to: /var/www/vhosts/novarax.ae/app.novarax.ae/fix-tenant-issues.php
 * Access: https://app.novarax.ae/fix-tenant-issues.php
 */

require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied - Admin only');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>NovaRax Tenant Fix Tool</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px; 
            background: #f5f5f5;
            max-width: 1200px;
            margin: 0 auto;
        }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #0073aa; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #333; margin-top: 30px; }
        .success { 
            background: #d4edda; 
            border-left: 4px solid #28a745; 
            padding: 15px; 
            margin: 10px 0;
            border-radius: 4px;
        }
        .error { 
            background: #f8d7da; 
            border-left: 4px solid #dc3545; 
            padding: 15px; 
            margin: 10px 0;
            border-radius: 4px;
        }
        .warning { 
            background: #fff3cd; 
            border-left: 4px solid #ffc107; 
            padding: 15px; 
            margin: 10px 0;
            border-radius: 4px;
        }
        .info { 
            background: #d1ecf1; 
            border-left: 4px solid #17a2b8; 
            padding: 15px; 
            margin: 10px 0;
            border-radius: 4px;
        }
        .tenant-box {
            border: 1px solid #ddd;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            background: #fafafa;
        }
        .tenant-box h3 { margin-top: 0; color: #0073aa; }
        pre { 
            background: #f4f4f4; 
            padding: 10px; 
            border-radius: 4px; 
            overflow-x: auto;
            font-size: 12px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 5px;
        }
        .btn:hover { background: #005a87; }
        .stat { 
            display: inline-block;
            margin: 10px 15px;
            padding: 10px 15px;
            background: #0073aa;
            color: white;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 NovaRax Tenant Fix Tool</h1>
        
        <?php
        global $wpdb;
        
        // Get database root credentials
        $root_user = defined('DB_ROOT_USER') ? DB_ROOT_USER : 'root';
        $root_pass = defined('DB_ROOT_PASSWORD') ? DB_ROOT_PASSWORD : '';
        
        if (empty($root_pass)) {
            echo '<div class="error">';
            echo '<strong>❌ Missing Root Credentials</strong><br>';
            echo 'Please add these to your wp-config.php:<br>';
            echo "<pre>define('DB_ROOT_USER', 'root');\ndefine('DB_ROOT_PASSWORD', 'your_root_password');</pre>";
            echo '</div>';
            die();
        }
        
        // Connect to MySQL as root
        $mysqli_root = new mysqli('localhost', $root_user, $root_pass);
        
        if ($mysqli_root->connect_error) {
            echo '<div class="error">';
            echo '<strong>❌ Cannot connect to MySQL as root</strong><br>';
            echo 'Error: ' . $mysqli_root->connect_error;
            echo '</div>';
            die();
        }
        
        echo '<div class="success"><strong>✓ Connected to MySQL server</strong></div>';
        
        // Get all tenants
        $tenants = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}novarax_tenants 
            ORDER BY id ASC
        ");
        
        echo '<div class="info">';
        echo '<strong>📊 Found ' . count($tenants) . ' tenant(s)</strong>';
        echo '</div>';
        
        $stats = [
            'total' => count($tenants),
            'fixed_passwords' => 0,
            'fixed_roles' => 0,
            'errors' => 0,
            'skipped' => 0
        ];
        
        foreach ($tenants as $tenant) {
            echo '<div class="tenant-box">';
            echo '<h3>👤 ' . htmlspecialchars($tenant->tenant_username) . '</h3>';
            echo '<p><strong>Subdomain:</strong> ' . htmlspecialchars($tenant->subdomain) . '</p>';
            echo '<p><strong>Database:</strong> ' . htmlspecialchars($tenant->database_name) . '</p>';
            echo '<p><strong>Status:</strong> ' . htmlspecialchars($tenant->status) . '</p>';
            
            // Parse metadata
            $metadata = !empty($tenant->metadata) ? json_decode($tenant->metadata, true) : [];
            
            // =============================
            // ISSUE 1: Fix Missing Password
            // =============================
            $db_username = substr($tenant->database_name, 0, 16);
            $db_password = isset($metadata['db_password']) ? $metadata['db_password'] : '';
            $needs_password_fix = empty($db_password);
            
            if ($needs_password_fix) {
                echo '<div class="warning"><strong>⚠️  No password in metadata - fixing...</strong></div>';
                
                // Generate new secure password
                $new_password = wp_generate_password(24, false);
                
                // Update MySQL user password
                $update_user_sql = "ALTER USER '{$db_username}'@'localhost' IDENTIFIED BY '{$mysqli_root->real_escape_string($new_password)}'";
                
                if ($mysqli_root->query($update_user_sql)) {
                    // Store in metadata
                    $metadata['db_username'] = $db_username;
                    $metadata['db_password'] = $new_password;
                    
                    $update_result = $wpdb->update(
                        $wpdb->prefix . 'novarax_tenants',
                        ['metadata' => json_encode($metadata)],
                        ['id' => $tenant->id],
                        ['%s'],
                        ['%d']
                    );
                    
                    if ($update_result !== false) {
                        echo '<div class="success"><strong>✓ Password generated and stored</strong></div>';
                        $stats['fixed_passwords']++;
                    } else {
                        echo '<div class="error"><strong>❌ Failed to store password in metadata</strong></div>';
                        $stats['errors']++;
                    }
                } else {
                    echo '<div class="error"><strong>❌ Failed to update MySQL user</strong><br>';
                    echo 'Error: ' . $mysqli_root->error . '</div>';
                    $stats['errors']++;
                }
            } else {
                echo '<div class="success"><strong>✓ Password already in metadata</strong></div>';
                $stats['skipped']++;
            }
            
            // =============================
            // ISSUE 2: Fix User Roles
            // =============================
            
            // Connect to tenant database
            if (!empty($metadata['db_password'])) {
                $tenant_mysqli = new mysqli('localhost', $db_username, $metadata['db_password'], $tenant->database_name);
                
                if ($tenant_mysqli->connect_error) {
                    echo '<div class="error"><strong>❌ Cannot connect to tenant database</strong><br>';
                    echo 'Error: ' . $tenant_mysqli->connect_error . '</div>';
                    $stats['errors']++;
                } else {
                    echo '<div class="success"><strong>✓ Connected to tenant database</strong></div>';
                    
                    // Find the user in tenant database
                    $user_query = $tenant_mysqli->prepare("
                        SELECT ID, user_login 
                        FROM wp_users 
                        WHERE user_login = ? 
                        LIMIT 1
                    ");
                    
                    $user_query->bind_param('s', $tenant->tenant_username);
                    $user_query->execute();
                    $user_result = $user_query->get_result();
                    $tenant_user = $user_result->fetch_assoc();
                    
                    if ($tenant_user) {
                        echo '<p><strong>User ID in tenant DB:</strong> ' . $tenant_user['ID'] . '</p>';
                        
                        // Check current capabilities
                        $check_caps = $tenant_mysqli->prepare("
                            SELECT meta_value 
                            FROM wp_usermeta 
                            WHERE user_id = ? AND meta_key = 'wp_capabilities'
                        ");
                        
                        $check_caps->bind_param('i', $tenant_user['ID']);
                        $check_caps->execute();
                        $caps_result = $check_caps->get_result();
                        $caps_row = $caps_result->fetch_assoc();
                        
                        $current_caps = $caps_row ? maybe_unserialize($caps_row['meta_value']) : [];
                        
                        if (!isset($current_caps['administrator']) || $current_caps['administrator'] !== true) {
                            echo '<div class="warning"><strong>⚠️  User is not administrator - fixing...</strong></div>';
                            
                            // Set as administrator
                            $admin_caps = serialize(['administrator' => true]);
                            
                            $update_caps = $tenant_mysqli->prepare("
                                UPDATE wp_usermeta 
                                SET meta_value = ? 
                                WHERE user_id = ? AND meta_key = 'wp_capabilities'
                            ");
                            
                            $update_caps->bind_param('si', $admin_caps, $tenant_user['ID']);
                            
                            if ($update_caps->execute()) {
                                // Also update user level
                                $update_level = $tenant_mysqli->prepare("
                                    INSERT INTO wp_usermeta (user_id, meta_key, meta_value) 
                                    VALUES (?, 'wp_user_level', '10')
                                    ON DUPLICATE KEY UPDATE meta_value = '10'
                                ");
                                
                                $update_level->bind_param('i', $tenant_user['ID']);
                                $update_level->execute();
                                
                                echo '<div class="success"><strong>✓ User role updated to Administrator</strong></div>';
                                $stats['fixed_roles']++;
                            } else {
                                echo '<div class="error"><strong>❌ Failed to update role</strong></div>';
                                $stats['errors']++;
                            }
                        } else {
                            echo '<div class="success"><strong>✓ User already has administrator role</strong></div>';
                        }
                    } else {
                        echo '<div class="error"><strong>❌ User not found in tenant database</strong></div>';
                        $stats['errors']++;
                    }
                    
                    $tenant_mysqli->close();
                }
            }
            
            echo '</div>'; // End tenant-box
        }
        
        $mysqli_root->close();
        
        // Display summary statistics
        echo '<h2>📈 Summary</h2>';
        echo '<div class="info">';
        echo '<span class="stat">Total: ' . $stats['total'] . '</span>';
        echo '<span class="stat">Passwords Fixed: ' . $stats['fixed_passwords'] . '</span>';
        echo '<span class="stat">Roles Fixed: ' . $stats['fixed_roles'] . '</span>';
        echo '<span class="stat">Errors: ' . $stats['errors'] . '</span>';
        echo '</div>';
        
        echo '<h2>✅ Next Steps</h2>';
        echo '<div class="info">';
        echo '<ol>';
        echo '<li>Test each tenant by accessing their subdomain (e.g., username.app.novarax.ae)</li>';
        echo '<li>Try logging in with the tenant username and password</li>';
        echo '<li>Verify you can access /wp-admin and see the dashboard</li>';
        echo '<li>If issues persist, check the debug logs:<br>';
        echo '<code>/var/www/vhosts/novarax.ae/tenant-dashboard/wp-content/debug.log</code></li>';
        echo '</ol>';
        echo '</div>';
        
        echo '<h2>🔍 Additional Checks</h2>';
        echo '<div class="info">';
        echo '<p><strong>Test a specific tenant:</strong></p>';
        echo '<p>You can use the debug script to test individual tenants:</p>';
        echo '<p><code>https://username.app.novarax.ae/debug-connection.php</code></p>';
        echo '<p>Replace "username" with an actual tenant username.</p>';
        echo '</div>';
        ?>
        
        <a href="<?php echo admin_url('admin.php?page=novarax-tenant-manager'); ?>" class="btn">← Back to Tenant Manager</a>
    </div>
</body>
</html>