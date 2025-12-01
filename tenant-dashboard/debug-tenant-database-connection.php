<?php
/**
 * Debug Tenant Database Connection
 * 
 * Save as: /var/www/vhosts/novarax.ae/tenant-dashboard/debug-connection.php
 * Access: https://alain123.app.novarax.ae/debug-connection.php
 */

echo "<h1>NovaRax Tenant Connection Debug</h1>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; }
    .box { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #0073aa; }
    .success { border-left-color: #46b450; }
    .error { border-left-color: #dc3232; }
    .warning { border-left-color: #ffb900; }
    pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
    h2 { margin-top: 30px; color: #0073aa; }
</style>";

// Step 1: Check basic PHP info
echo "<div class='box'>";
echo "<h2>Step 1: PHP Environment</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "</div>";

// Step 2: Check subdomain detection
echo "<div class='box'>";
echo "<h2>Step 2: Subdomain Detection</h2>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "<br>";

$host = $_SERVER['HTTP_HOST'] ?? '';
$pattern = '/^([a-z0-9-]+)\.app\.novarax\.ae$/i';
if (preg_match($pattern, $host, $matches)) {
    $subdomain = strtolower($matches[1]);
    echo "<strong>✓ Subdomain detected: {$subdomain}</strong><br>";
} else {
    echo "<strong style='color: red;'>✗ Failed to detect subdomain from: {$host}</strong><br>";
    $parts = explode('.', $host);
    echo "Parts: " . implode(' | ', $parts) . "<br>";
    $subdomain = $parts[0];
    echo "Using fallback subdomain: {$subdomain}<br>";
}
echo "</div>";

// Step 3: Connect to master database
echo "<div class='box'>";
echo "<h2>Step 3: Master Database Connection</h2>";

$master_config = array(
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'nova_app',
    'user' => 'nova_app147',
    'password' => 'NsZa@0NPj5O0RKn*',
    'prefix' => 'RAX147_'
);

echo "Connecting to: {$master_config['database']}@{$master_config['host']}:{$master_config['port']}<br>";

$mysqli = @new mysqli(
    $master_config['host'],
    $master_config['user'],
    $master_config['password'],
    $master_config['database'],
    $master_config['port']
);

if ($mysqli->connect_error) {
    echo "<div class='error'><strong>✗ Failed to connect to master database</strong><br>";
    echo "Error: " . $mysqli->connect_error . "</div>";
    exit;
}

echo "<div class='success'><strong>✓ Connected to master database</strong></div>";
echo "</div>";

// Step 4: Query tenant information
echo "<div class='box'>";
echo "<h2>Step 4: Query Tenant Information</h2>";

$full_subdomain = $subdomain . '.app.novarax.ae';
echo "Looking for tenant: {$full_subdomain}<br><br>";

$stmt = $mysqli->prepare(
    "SELECT id, tenant_username, database_name, status, metadata, subdomain 
     FROM {$master_config['prefix']}novarax_tenants 
     WHERE subdomain = ? LIMIT 1"
);

if (!$stmt) {
    echo "<div class='error'><strong>✗ Failed to prepare query</strong><br>";
    echo "Error: " . $mysqli->error . "</div>";
    exit;
}

$stmt->bind_param('s', $full_subdomain);
$stmt->execute();
$result = $stmt->get_result();
$tenant = $result->fetch_assoc();

if (!$tenant) {
    echo "<div class='error'><strong>✗ No tenant found for: {$full_subdomain}</strong></div>";
    
    // Show all tenants for debugging
    echo "<h3>All Available Tenants:</h3>";
    $all = $mysqli->query("SELECT subdomain, status FROM {$master_config['prefix']}novarax_tenants");
    echo "<pre>";
    while ($row = $all->fetch_assoc()) {
        echo "- " . $row['subdomain'] . " (status: " . $row['status'] . ")\n";
    }
    echo "</pre>";
    exit;
}

echo "<div class='success'><strong>✓ Tenant found!</strong></div>";
echo "<pre>";
echo "ID: " . $tenant['id'] . "\n";
echo "Username: " . $tenant['tenant_username'] . "\n";
echo "Database: " . $tenant['database_name'] . "\n";
echo "Status: " . $tenant['status'] . "\n";
echo "Subdomain: " . $tenant['subdomain'] . "\n";
echo "</pre>";

// Step 5: Check metadata for credentials
echo "<h3>Step 5: Database Credentials</h3>";

$metadata = !empty($tenant['metadata']) ? json_decode($tenant['metadata'], true) : array();

if (empty($metadata)) {
    echo "<div class='warning'><strong>⚠ Metadata is empty!</strong></div>";
} else {
    echo "<strong>Metadata keys found:</strong> " . implode(', ', array_keys($metadata)) . "<br><br>";
}

$db_username = isset($metadata['db_username']) ? $metadata['db_username'] : substr($tenant['database_name'], 0, 16);
$db_password = isset($metadata['db_password']) ? $metadata['db_password'] : '';

echo "Database Name: <code>{$tenant['database_name']}</code><br>";
echo "Database User: <code>{$db_username}</code><br>";
echo "Password in metadata: " . (empty($db_password) ? "<span style='color: red;'><strong>NO - THIS IS THE PROBLEM!</strong></span>" : "<span style='color: green;'><strong>YES</strong></span>") . "<br>";

if (!empty($db_password)) {
    echo "Password length: " . strlen($db_password) . " characters<br>";
    echo "Password preview: " . substr($db_password, 0, 4) . "..." . substr($db_password, -4) . "<br>";
}

echo "</div>";

// Step 6: Test tenant database connection
echo "<div class='box'>";
echo "<h2>Step 6: Test Tenant Database Connection</h2>";

if (empty($db_password)) {
    echo "<div class='error'><strong>✗ Cannot test connection - password not found in metadata</strong><br>";
    echo "This is why you're getting 'Error establishing a database connection'<br>";
    echo "<br><strong>Solution:</strong> Run the fix-existing-tenant-passwords.php script</div>";
} else {
    echo "Attempting to connect to: {$tenant['database_name']}@localhost:3306<br>";
    echo "Using credentials: {$db_username} / [password]<br><br>";
    
    $tenant_mysqli = @new mysqli(
        'localhost',
        $db_username,
        $db_password,
        $tenant['database_name'],
        3306
    );
    
    if ($tenant_mysqli->connect_error) {
        echo "<div class='error'><strong>✗ Failed to connect to tenant database</strong><br>";
        echo "Error: " . $tenant_mysqli->connect_error . "<br>";
        echo "Error Number: " . $tenant_mysqli->connect_errno . "<br><br>";
        
        if ($tenant_mysqli->connect_errno == 1045) {
            echo "This means: <strong>Access denied (wrong password)</strong><br>";
            echo "The password in metadata might be incorrect.<br>";
        } elseif ($tenant_mysqli->connect_errno == 1049) {
            echo "This means: <strong>Database doesn't exist</strong><br>";
            echo "The database '{$tenant['database_name']}' was not created.<br>";
        }
        echo "</div>";
    } else {
        echo "<div class='success'><strong>✓ Successfully connected to tenant database!</strong></div>";
        
        // Check tables
        $tables_result = $tenant_mysqli->query("SHOW TABLES");
        $table_count = $tables_result->num_rows;
        
        echo "<br>Tables in database: <strong>{$table_count}</strong><br>";
        
        if ($table_count == 0) {
            echo "<div class='warning'><strong>⚠ Database is empty (no tables)</strong></div>";
        } else {
            echo "<pre>";
            while ($row = $tables_result->fetch_row()) {
                echo "- " . $row[0] . "\n";
            }
            echo "</pre>";
        }
        
        $tenant_mysqli->close();
    }
}

$stmt->close();
$mysqli->close();

echo "</div>";

// Summary
echo "<div class='box'>";
echo "<h2>Summary & Next Steps</h2>";

if (empty($db_password)) {
    echo "<div class='error'>";
    echo "<strong>Problem Found: Password not in metadata</strong><br><br>";
    echo "This tenant's database password is not stored in the metadata field.<br>";
    echo "This is why WordPress can't connect to the database.<br><br>";
    echo "<strong>To fix:</strong><br>";
    echo "1. Go to: <a href='https://app.novarax.ae/fix-existing-tenant-passwords.php'>fix-existing-tenant-passwords.php</a><br>";
    echo "2. This will regenerate passwords and store them in metadata<br>";
    echo "3. Then try accessing this subdomain again<br>";
    echo "</div>";
} else {
    echo "<div class='success'>";
    echo "<strong>✓ Tenant configuration looks good!</strong><br><br>";
    echo "If you're still seeing the error, check:<br>";
    echo "1. Is wp-config.php in /tenant-dashboard/ using the correct master DB credentials?<br>";
    echo "2. Is the table prefix correct? (should be RAX148_)<br>";
    echo "3. Check WordPress debug.log: /tenant-dashboard/wp-content/debug.log<br>";
    echo "</div>";
}

echo "</div>";
?>