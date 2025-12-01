<?php
/**
 * Add database passwords to existing tenant metadata
 * This retrieves passwords from MySQL and stores them in metadata
 */
require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Fix Existing Tenant Passwords</h1>";
echo "<style>
    body { font-family: sans-serif; padding: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
</style>";

// Database credentials
$root_user = defined('DB_ROOT_USER') ? DB_ROOT_USER : 'root';
$root_pass = defined('DB_ROOT_PASSWORD') ? DB_ROOT_PASSWORD : '';

$mysqli = new mysqli('localhost', $root_user, $root_pass);

if ($mysqli->connect_error) {
    die("<p class='error'>Failed to connect to MySQL: " . $mysqli->connect_error . "</p>");
}

global $wpdb;

// Get all tenants without passwords in metadata
$tenants = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}novarax_tenants");

echo "<p>Processing " . count($tenants) . " tenants...</p>";

foreach ($tenants as $tenant) {
    echo "<hr>";
    echo "<h3>{$tenant->tenant_username}</h3>";
    
    $metadata = !empty($tenant->metadata) ? json_decode($tenant->metadata, true) : array();
    
    // Check if password already exists
    if (isset($metadata['db_password']) && !empty($metadata['db_password'])) {
        echo "<p class='success'>✓ Password already in metadata</p>";
        continue;
    }
    
    // Get database username (first 16 chars of database name)
    $db_username = substr($tenant->database_name, 0, 16);
    
    // Check if database user exists in MySQL
    $result = $mysqli->query("SELECT User, Host FROM mysql.user WHERE User = '{$db_username}'");
    
    if ($result && $result->num_rows > 0) {
        echo "<p class='warning'>⚠ User exists but we cannot retrieve the password from MySQL.</p>";
        echo "<p>We need to regenerate a new password and update the database user.</p>";
        
        // Generate new password
        $new_password = wp_generate_password(24, false);
        
        // Update MySQL user password
        $escaped_pass = $mysqli->real_escape_string($new_password);
        $update_sql = "ALTER USER '{$db_username}'@'localhost' IDENTIFIED BY '{$escaped_pass}'";
        
        if ($mysqli->query($update_sql)) {
            echo "<p class='success'>✓ New password set for database user</p>";
            
            // Store in metadata
            $metadata['db_username'] = $db_username;
            $metadata['db_password'] = $new_password;
            
            $wpdb->update(
                $wpdb->prefix . 'novarax_tenants',
                array('metadata' => json_encode($metadata)),
                array('id' => $tenant->id),
                array('%s'),
                array('%d')
            );
            
            echo "<p class='success'>✓ Password stored in metadata</p>";
            echo "<p><strong>New Password:</strong> <code>{$new_password}</code></p>";
        } else {
            echo "<p class='error'>✗ Failed to update password: " . $mysqli->error . "</p>";
        }
    } else {
        echo "<p class='error'>✗ Database user does not exist! Database may not be created.</p>";
    }
}

$mysqli->close();

echo "<hr>";
echo "<h2 class='success'>Done!</h2>";
echo "<p>Refresh <a href='/update-tenant-metadata.php'>update-tenant-metadata.php</a> to verify all passwords are set.</p>";
?>