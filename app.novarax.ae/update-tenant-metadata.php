<?php
require_once('wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Update Tenant Metadata with Database Passwords</h1>";

global $wpdb;

// Get all tenants
$tenants = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}novarax_tenants");

echo "<p>Found " . count($tenants) . " tenants</p>";

foreach ($tenants as $tenant) {
    $metadata = !empty($tenant->metadata) ? json_decode($tenant->metadata, true) : array();
    
    // Check if password already exists
    if (isset($metadata['db_password']) && !empty($metadata['db_password'])) {
        echo "<p>✓ {$tenant->tenant_username} - Password already set</p>";
        continue;
    }
    
    // We need to get the password from MySQL users table
    // For security, MySQL doesn't let us retrieve passwords
    // So we'll need to check what's actually stored or regenerate
    
    echo "<p>⚠️  {$tenant->tenant_username} - Password NOT in metadata</p>";
    echo "<p style='margin-left: 20px;'>Database: {$tenant->database_name}</p>";
    
    // You'll need to manually add passwords or regenerate them
    // For now, just list which ones need fixing
}

echo "<hr>";
echo "<h2>Action Required:</h2>";
echo "<p>Tenants without passwords in metadata will fail to connect.</p>";
echo "<p>Options:</p>";
echo "<ul>";
echo "<li>1. Provision new tenants (they will have passwords stored automatically)</li>";
echo "<li>2. Manually add passwords to metadata for existing tenants</li>";
echo "<li>3. Use the fix-tenant-db.php script to rebuild databases</li>";
echo "</ul>";
?>