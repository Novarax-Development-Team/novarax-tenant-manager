<?php
/**
 * Diagnose and Fix WordPress Roles Issue
 * 
 * Upload to: /var/www/vhosts/novarax.ae/app.novarax.ae/diagnose-roles.php
 * Access: https://app.novarax.ae/diagnose-roles.php
 */

require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Get tenant username from URL parameter
$tenant_username = isset($_GET['tenant']) ? sanitize_text_field($_GET['tenant']) : '';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnose Roles Issue</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px; 
            background: #f5f5f5;
        }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 1200px; margin: 0 auto; }
        h1 { color: #0073aa; }
        h2 { color: #333; margin-top: 30px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #0073aa; color: white; }
        .btn { padding: 8px 16px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin: 5px; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 WordPress Roles Diagnostic Tool</h1>
        
        <?php
        global $wpdb;
        
        // Get root credentials
        $root_user = defined('DB_ROOT_USER') ? DB_ROOT_USER : 'root';
        $root_pass = defined('DB_ROOT_PASSWORD') ? DB_ROOT_PASSWORD : '';
        
        if (empty($root_pass)) {
            echo '<div class="error">Missing DB_ROOT_PASSWORD in wp-config.php</div>';
            die();
        }
        
        if (empty($tenant_username)) {
            // Show list of all tenants
            echo '<h2>Select a Tenant to Diagnose</h2>';
            $tenants = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}novarax_tenants ORDER BY id DESC");
            
            echo '<table>';
            echo '<tr><th>Username</th><th>Subdomain</th><th>Status</th><th>Action</th></tr>';
            
            foreach ($tenants as $tenant) {
                echo '<tr>';
                echo '<td>' . esc_html($tenant->tenant_username) . '</td>';
                echo '<td>' . esc_html($tenant->subdomain) . '</td>';
                echo '<td>' . esc_html($tenant->status) . '</td>';
                echo '<td><a href="?tenant=' . urlencode($tenant->tenant_username) . '" class="btn">Diagnose</a></td>';
                echo '</tr>';
            }
            
            echo '</table>';
            die();
        }
        
        // Get tenant info
        $tenant = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}novarax_tenants WHERE tenant_username = %s",
            $tenant_username
        ));
        
        if (!$tenant) {
            echo '<div class="error">Tenant not found!</div>';
            die();
        }
        
        echo '<div class="info">';
        echo '<strong>Diagnosing Tenant:</strong> ' . esc_html($tenant->tenant_username) . '<br>';
        echo '<strong>Database:</strong> ' . esc_html($tenant->database_name) . '<br>';
        echo '<a href="?" class="btn">← Back to List</a>';
        echo '</div>';
        
        // Get database credentials
        $metadata = json_decode($tenant->metadata, true);
        $db_password = $metadata['db_password'] ?? '';
        
        if (empty($db_password)) {
            echo '<div class="error">No password in metadata!</div>';
            die();
        }
        
        // Connect using ROOT
        $tenant_db = new mysqli('localhost', $root_user, $root_pass, $tenant->database_name);
        
        if ($tenant_db->connect_error) {
            echo '<div class="error">Cannot connect: ' . $tenant_db->connect_error . '</div>';
            die();
        }
        
        echo '<div class="success">✓ Connected to tenant database</div>';
        
        // ===============================================
        // Check wp_user_roles option
        // ===============================================
        echo '<h2>1. Site Roles (wp_user_roles)</h2>';
        
        $roles_result = $tenant_db->query("
            SELECT option_value 
            FROM wp_options 
            WHERE option_name = 'wp_user_roles'
        ");
        
        if ($roles_result && $roles_result->num_rows > 0) {
            $roles_row = $roles_result->fetch_assoc();
            $roles = maybe_unserialize($roles_row['option_value']);
            
            echo '<div class="info">Found ' . count($roles) . ' roles defined:</div>';
            echo '<pre>';
            foreach ($roles as $role_key => $role_data) {
                echo "Role: {$role_key}\n";
                echo "  Display Name: " . ($role_data['name'] ?? 'N/A') . "\n";
                echo "  Capabilities: " . count($role_data['capabilities'] ?? []) . "\n";
                if ($role_key === 'administrator') {
                    echo "  Admin Caps: ";
                    $admin_caps = $role_data['capabilities'] ?? [];
                    echo isset($admin_caps['manage_options']) ? '✓ manage_options ' : '✗ manage_options ';
                    echo isset($admin_caps['edit_dashboard']) ? '✓ edit_dashboard' : '✗ edit_dashboard';
                    echo "\n";
                }
                echo "\n";
            }
            echo '</pre>';
            
            // Check for the custom "tenant" role
            if (isset($roles['tenant'])) {
                echo '<div class="warning">';
                echo '<strong>⚠️  Found custom "tenant" role!</strong><br>';
                echo 'This might be causing issues. The plugin created this role.<br>';
                echo '<strong>Capabilities:</strong><br>';
                echo '<pre>' . print_r($roles['tenant'], true) . '</pre>';
                echo '</div>';
            }
            
        } else {
            echo '<div class="error">✗ wp_user_roles option not found!</div>';
        }
        
        // ===============================================
        // Check users and their roles
        // ===============================================
        echo '<h2>2. Users in Database</h2>';
        
        $users_result = $tenant_db->query("
            SELECT u.ID, u.user_login, u.user_email,
                   um.meta_value as capabilities
            FROM wp_users u
            LEFT JOIN wp_usermeta um ON u.ID = um.user_id AND um.meta_key = 'wp_capabilities'
        ");
        
        if ($users_result) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Login</th><th>Email</th><th>Capabilities</th><th>Issue?</th></tr>';
            
            while ($user = $users_result->fetch_assoc()) {
                $caps = maybe_unserialize($user['capabilities']);
                $caps_display = is_array($caps) ? implode(', ', array_keys($caps)) : 'NONE';
                
                $has_issue = false;
                $issue_msg = '';
                
                if (empty($caps)) {
                    $has_issue = true;
                    $issue_msg = 'No capabilities!';
                } elseif (isset($caps['tenant'])) {
                    $has_issue = true;
                    $issue_msg = 'Has "tenant" role (non-standard)';
                } elseif (!isset($caps['administrator']) && !isset($caps['subscriber'])) {
                    $has_issue = true;
                    $issue_msg = 'Unknown role';
                }
                
                echo '<tr>';
                echo '<td>' . $user['ID'] . '</td>';
                echo '<td>' . esc_html($user['user_login']) . '</td>';
                echo '<td>' . esc_html($user['user_email']) . '</td>';
                echo '<td>' . esc_html($caps_display) . '</td>';
                echo '<td>' . ($has_issue ? '<span style="color: red;">' . $issue_msg . '</span>' : '<span style="color: green;">✓ OK</span>') . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
        }
        
        // ===============================================
        // Check for admin-menu-editor plugin data
        // ===============================================
        echo '<h2>3. Admin Menu Editor Plugin Data</h2>';
        
        $ame_options = $tenant_db->query("
            SELECT option_name, LENGTH(option_value) as value_length
            FROM wp_options 
            WHERE option_name LIKE '%admin_menu%' OR option_name LIKE '%ame%'
        ");
        
        if ($ame_options && $ame_options->num_rows > 0) {
            echo '<div class="warning">Found Admin Menu Editor data:</div>';
            echo '<table>';
            echo '<tr><th>Option Name</th><th>Size</th></tr>';
            while ($opt = $ame_options->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . esc_html($opt['option_name']) . '</td>';
                echo '<td>' . $opt['value_length'] . ' bytes</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<div class="success">No Admin Menu Editor data found</div>';
        }
        
        // ===============================================
        // FIX BUTTON
        // ===============================================
        echo '<h2>4. Fix Options</h2>';
        
        if (isset($_GET['fix']) && $_GET['fix'] == 'reset_roles') {
            echo '<div class="info"><strong>Resetting roles to WordPress defaults...</strong></div>';
            
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
            $escaped_roles = $tenant_db->real_escape_string($serialized_roles);
            
            $update_result = $tenant_db->query("
                UPDATE wp_options 
                SET option_value = '{$escaped_roles}' 
                WHERE option_name = 'wp_user_roles'
            ");
            
            if ($update_result) {
                echo '<div class="success">✓ Roles reset to WordPress defaults</div>';
                echo '<p><a href="?tenant=' . urlencode($tenant_username) . '" class="btn">← Back to Diagnostic</a></p>';
            } else {
                echo '<div class="error">✗ Failed to update roles: ' . $tenant_db->error . '</div>';
            }
            
        } else {
            echo '<p><a href="?tenant=' . urlencode($tenant_username) . '&fix=reset_roles" class="btn btn-danger" onclick="return confirm(\'Are you sure? This will reset all roles to WordPress defaults.\')">Reset Roles to Defaults</a></p>';
            echo '<p><small>This will remove the custom "tenant" role and reset to standard WordPress roles (administrator, subscriber)</small></p>';
        }
        
        $tenant_db->close();
        ?>
    </div>
</body>
</html>