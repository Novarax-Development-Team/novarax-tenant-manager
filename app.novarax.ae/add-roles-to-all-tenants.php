<?php
/**
 * Add wp_user_roles to All Existing Tenants
 * 
 * Upload to: /var/www/vhosts/novarax.ae/app.novarax.ae/add-roles-to-all-tenants.php
 * Access: https://app.novarax.ae/add-roles-to-all-tenants.php
 */

require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Add wp_user_roles to All Tenants</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px; 
            background: #f5f5f5;
        }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 1200px; margin: 0 auto; }
        h1 { color: #0073aa; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 10px 0; }
        .tenant-box { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 4px; }
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
        <h1>🔧 Add wp_user_roles to All Tenants</h1>
        
        <div class="info">
            <strong>What this does:</strong> Adds the missing <code>wp_user_roles</code> option to all tenant databases.
            This is required for WordPress to understand what roles (administrator, subscriber, etc.) exist.
        </div>
        
        <?php
        global $wpdb;
        
        $root_user = defined('DB_ROOT_USER') ? DB_ROOT_USER : 'root';
        $root_pass = defined('DB_ROOT_PASSWORD') ? DB_ROOT_PASSWORD : '';
        
        if (empty($root_pass)) {
            echo '<div class="error">Missing DB_ROOT_PASSWORD</div>';
            die();
        }
        
        // WordPress default roles
        $default_roles = array(
            'administrator' => array(
                'name' => 'Administrator',
                'capabilities' => array(
                    'switch_themes' => true,
                    'edit_themes' => true,
                    'activate_plugins' => true,
                    'edit_plugins' => true,
                    'edit_users' => true,
                    'edit_files' => true,
                    'manage_options' => true,
                    'moderate_comments' => true,
                    'manage_categories' => true,
                    'manage_links' => true,
                    'upload_files' => true,
                    'import' => true,
                    'unfiltered_html' => true,
                    'edit_posts' => true,
                    'edit_others_posts' => true,
                    'edit_published_posts' => true,
                    'publish_posts' => true,
                    'edit_pages' => true,
                    'read' => true,
                    'level_10' => true,
                    'level_9' => true,
                    'level_8' => true,
                    'level_7' => true,
                    'level_6' => true,
                    'level_5' => true,
                    'level_4' => true,
                    'level_3' => true,
                    'level_2' => true,
                    'level_1' => true,
                    'level_0' => true,
                    'edit_others_pages' => true,
                    'edit_published_pages' => true,
                    'publish_pages' => true,
                    'delete_pages' => true,
                    'delete_others_pages' => true,
                    'delete_published_pages' => true,
                    'delete_posts' => true,
                    'delete_others_posts' => true,
                    'delete_published_posts' => true,
                    'delete_private_posts' => true,
                    'edit_private_posts' => true,
                    'read_private_posts' => true,
                    'delete_private_pages' => true,
                    'edit_private_pages' => true,
                    'read_private_pages' => true,
                    'delete_users' => true,
                    'create_users' => true,
                    'unfiltered_upload' => true,
                    'edit_dashboard' => true,
                    'customize' => true,
                    'delete_site' => true,
                ),
            ),
            'subscriber' => array(
                'name' => 'Subscriber',
                'capabilities' => array(
                    'read' => true,
                    'level_0' => true,
                ),
            ),
        );
        
        $serialized_roles = serialize($default_roles);
        
        // Get all tenants
        $tenants = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}novarax_tenants 
            ORDER BY id ASC
        ");
        
        echo '<div class="info"><strong>Found ' . count($tenants) . ' tenant(s)</strong></div>';
        
        $stats = [
            'total' => count($tenants),
            'success' => 0,
            'already_had' => 0,
            'errors' => 0
        ];
        
        foreach ($tenants as $tenant) {
            echo '<div class="tenant-box">';
            echo '<h3>' . esc_html($tenant->tenant_username) . '</h3>';
            echo '<p>Database: <code>' . esc_html($tenant->database_name) . '</code></p>';
            
            // Connect to tenant database
            $tenant_db = new mysqli('localhost', $root_user, $root_pass, $tenant->database_name);
            
            if ($tenant_db->connect_error) {
                echo '<div class="error">✗ Cannot connect: ' . $tenant_db->connect_error . '</div>';
                $stats['errors']++;
                echo '</div>';
                continue;
            }
            
            // Check if wp_user_roles already exists
            $check = $tenant_db->query("
                SELECT option_value 
                FROM wp_options 
                WHERE option_name = 'wp_user_roles'
            ");
            
            if ($check && $check->num_rows > 0) {
                echo '<div class="info">ℹ️  wp_user_roles already exists (skipping)</div>';
                $stats['already_had']++;
            } else {
                // Insert wp_user_roles
                $escaped_roles = $tenant_db->real_escape_string($serialized_roles);
                
                $insert = $tenant_db->query("
                    INSERT INTO wp_options (option_name, option_value, autoload) 
                    VALUES ('wp_user_roles', '{$escaped_roles}', 'yes')
                ");
                
                if ($insert) {
                    echo '<div class="success">✓ Successfully added wp_user_roles</div>';
                    $stats['success']++;
                } else {
                    echo '<div class="error">✗ Failed to insert: ' . $tenant_db->error . '</div>';
                    $stats['errors']++;
                }
            }
            
            $tenant_db->close();
            echo '</div>';
        }
        
        // Summary
        echo '<h2>📊 Summary</h2>';
        echo '<div class="info">';
        echo '<span class="stat">Total: ' . $stats['total'] . '</span>';
        echo '<span class="stat" style="background: #28a745;">Added: ' . $stats['success'] . '</span>';
        echo '<span class="stat">Already Had: ' . $stats['already_had'] . '</span>';
        echo '<span class="stat" style="background: #dc3545;">Errors: ' . $stats['errors'] . '</span>';
        echo '</div>';
        
        if ($stats['success'] > 0 || $stats['already_had'] > 0) {
            echo '<div class="success">';
            echo '<h3>✅ Done!</h3>';
            echo '<p>All tenants now have the wp_user_roles option.</p>';
            echo '<p><strong>Next steps:</strong></p>';
            echo '<ol>';
            echo '<li>Try accessing a tenant dashboard (e.g., https://elie160.app.novarax.ae/wp-admin)</li>';
            echo '<li>The "Sorry, you are not allowed" error should be fixed</li>';
            echo '<li>Update your class-database-manager.php to include wp_user_roles for new tenants</li>';
            echo '</ol>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>