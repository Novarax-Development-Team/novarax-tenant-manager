<?php
/**
 * MySQL User Diagnostic Script
 * 
 * This script checks what MySQL users actually exist and compares them
 * to what's stored in tenant metadata.
 * 
 * Upload to: /var/www/vhosts/novarax.ae/app.novarax.ae/diagnose-mysql-users.php
 * Access: https://app.novarax.ae/diagnose-mysql-users.php
 */

require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied - Admin only');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>MySQL User Diagnostics</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px; 
            background: #f5f5f5;
            max-width: 1400px;
            margin: 0 auto;
        }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #0073aa; border-bottom: 3px solid #0073aa; padding-bottom: 10px; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
            font-size: 13px;
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #ddd;
        }
        th { 
            background: #0073aa; 
            color: white;
            position: sticky;
            top: 0;
        }
        tr:hover { background: #f5f5f5; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { 
            background: #d1ecf1; 
            border-left: 4px solid #17a2b8; 
            padding: 15px; 
            margin: 20px 0;
            border-radius: 4px;
        }
        code { 
            background: #f4f4f4; 
            padding: 2px 6px; 
            border-radius: 3px;
            font-family: monospace;
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 MySQL User Diagnostics</h1>
        
        <?php
        global $wpdb;
        
        // Get database root credentials
        $root_user = defined('DB_ROOT_USER') ? DB_ROOT_USER : 'root';
        $root_pass = defined('DB_ROOT_PASSWORD') ? DB_ROOT_PASSWORD : '';
        
        if (empty($root_pass)) {
            echo '<div class="error">❌ Missing root credentials in wp-config.php</div>';
            die();
        }
        
        // Connect to MySQL as root
        $mysqli = new mysqli('localhost', $root_user, $root_pass);
        
        if ($mysqli->connect_error) {
            echo '<div class="error">❌ Cannot connect to MySQL: ' . $mysqli->connect_error . '</div>';
            die();
        }
        
        echo '<div class="success">✓ Connected to MySQL as root</div>';
        
        // Get all MySQL users that start with 'novarax'
        echo '<h2>MySQL Users (from mysql.user table)</h2>';
        
        $mysql_users_result = $mysqli->query("
            SELECT User, Host 
            FROM mysql.user 
            WHERE User LIKE 'novarax%' 
            ORDER BY User
        ");
        
        $mysql_users = [];
        if ($mysql_users_result) {
            echo '<table>';
            echo '<tr><th>#</th><th>MySQL Username</th><th>Host</th><th>Status</th></tr>';
            $i = 1;
            while ($row = $mysql_users_result->fetch_assoc()) {
                $mysql_users[$row['User']] = $row['Host'];
                echo '<tr>';
                echo '<td>' . $i++ . '</td>';
                echo '<td><code>' . htmlspecialchars($row['User']) . '</code></td>';
                echo '<td>' . htmlspecialchars($row['Host']) . '</td>';
                echo '<td class="success">✓ Exists</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
        // Get all tenants from NovaRax
        echo '<h2>NovaRax Tenants vs MySQL Users</h2>';
        
        $tenants = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}novarax_tenants 
            ORDER BY id ASC
        ");
        
        echo '<table>';
        echo '<tr>';
        echo '<th>Tenant</th>';
        echo '<th>Database Name</th>';
        echo '<th>Expected Username</th>';
        echo '<th>Password in Metadata</th>';
        echo '<th>MySQL User Exists</th>';
        echo '<th>Can Connect</th>';
        echo '<th>Action</th>';
        echo '</tr>';
        
        $issues = [];
        
        foreach ($tenants as $tenant) {
            $metadata = !empty($tenant->metadata) ? json_decode($tenant->metadata, true) : [];
            
            $db_name = $tenant->database_name;
            $expected_username = substr($db_name, 0, 16); // MySQL username limit
            $has_password = isset($metadata['db_password']) && !empty($metadata['db_password']);
            $mysql_user_exists = isset($mysql_users[$expected_username]);
            
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($tenant->tenant_username) . '</strong></td>';
            echo '<td><code>' . htmlspecialchars($db_name) . '</code></td>';
            echo '<td><code>' . htmlspecialchars($expected_username) . '</code></td>';
            
            // Password in metadata
            if ($has_password) {
                $pass_preview = substr($metadata['db_password'], 0, 4) . '...' . substr($metadata['db_password'], -4);
                echo '<td class="success">✓ Yes (' . $pass_preview . ')</td>';
            } else {
                echo '<td class="error">✗ NO</td>';
                $issues[] = [
                    'tenant' => $tenant->tenant_username,
                    'issue' => 'No password in metadata',
                    'fix' => 'Need to generate password'
                ];
            }
            
            // MySQL user exists
            if ($mysql_user_exists) {
                echo '<td class="success">✓ Yes</td>';
            } else {
                echo '<td class="error">✗ NO</td>';
                $issues[] = [
                    'tenant' => $tenant->tenant_username,
                    'issue' => 'MySQL user does not exist',
                    'fix' => 'Need to create MySQL user'
                ];
            }
            
            // Test connection
            if ($has_password && $mysql_user_exists) {
                $test_conn = @new mysqli('localhost', $expected_username, $metadata['db_password'], $db_name);
                
                if ($test_conn->connect_error) {
                    echo '<td class="error">✗ FAILED<br><small>' . htmlspecialchars($test_conn->connect_error) . '</small></td>';
                    $issues[] = [
                        'tenant' => $tenant->tenant_username,
                        'issue' => 'Connection failed: ' . $test_conn->connect_error,
                        'fix' => 'Password mismatch or permissions issue'
                    ];
                    echo '<td class="warning">Reset Password</td>';
                } else {
                    echo '<td class="success">✓ OK</td>';
                    echo '<td class="success">No action needed</td>';
                    $test_conn->close();
                }
            } else {
                echo '<td class="warning">Cannot test</td>';
                echo '<td class="warning">Fix missing items first</td>';
            }
            
            echo '</tr>';
        }
        
        echo '</table>';
        
        // Show issues summary
        if (!empty($issues)) {
            echo '<h2>🚨 Issues Found</h2>';
            echo '<div class="info">';
            echo '<strong>Total issues: ' . count($issues) . '</strong>';
            echo '<table style="margin-top: 15px;">';
            echo '<tr><th>Tenant</th><th>Issue</th><th>Recommended Fix</th></tr>';
            foreach ($issues as $issue) {
                echo '<tr>';
                echo '<td><strong>' . htmlspecialchars($issue['tenant']) . '</strong></td>';
                echo '<td>' . htmlspecialchars($issue['issue']) . '</td>';
                echo '<td>' . htmlspecialchars($issue['fix']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
        } else {
            echo '<h2>✅ All Good!</h2>';
            echo '<div class="info">All tenants have proper MySQL users and passwords configured.</div>';
        }
        
        // Show databases
        echo '<h2>MySQL Databases</h2>';
        $databases = $mysqli->query("SHOW DATABASES LIKE 'novarax%'");
        
        if ($databases) {
            echo '<table>';
            echo '<tr><th>#</th><th>Database Name</th><th>Has Tenant Record</th></tr>';
            $i = 1;
            $tenant_dbs = array_column($wpdb->get_results("SELECT database_name FROM {$wpdb->prefix}novarax_tenants", ARRAY_A), 'database_name');
            
            while ($row = $databases->fetch_array()) {
                $db_name = $row[0];
                $has_tenant = in_array($db_name, $tenant_dbs);
                
                echo '<tr>';
                echo '<td>' . $i++ . '</td>';
                echo '<td><code>' . htmlspecialchars($db_name) . '</code></td>';
                echo '<td>' . ($has_tenant ? '<span class="success">✓ Yes</span>' : '<span class="warning">⚠ Orphaned</span>') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
        $mysqli->close();
        
        // Recommendations
        echo '<h2>💡 Recommendations</h2>';
        echo '<div class="info">';
        echo '<ol style="margin: 0; padding-left: 20px;">';
        
        if (!empty($issues)) {
            echo '<li><strong>Run the password reset script:</strong> Create a script to reset passwords for all affected tenants</li>';
            echo '<li><strong>Ensure MySQL users are created:</strong> Check that all tenant database users exist</li>';
            echo '<li><strong>Update metadata:</strong> Store all passwords in tenant metadata</li>';
        } else {
            echo '<li>All tenants are properly configured</li>';
            echo '<li>Continue with normal testing</li>';
        }
        
        echo '</ol>';
        echo '</div>';
        
        // Show sample fix command
        if (!empty($issues)) {
            echo '<h2>🔧 Fix Command</h2>';
            echo '<div class="info">';
            echo '<p>Based on the issues found, here\'s what needs to be done:</p>';
            echo '<pre>';
            echo 'For each tenant with issues:' . "\n";
            echo '1. Generate new password' . "\n";
            echo '2. Create/update MySQL user' . "\n";
            echo '3. Grant permissions to database' . "\n";
            echo '4. Store password in metadata' . "\n";
            echo '5. Test connection' . "\n";
            echo '</pre>';
            echo '<p><strong>Use the updated fix-tenant-issues.php script to do this automatically.</strong></p>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>