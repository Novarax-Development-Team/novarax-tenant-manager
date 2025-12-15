<?php
/**
 * Quick Fix - Update All Tenant Storage
 * 
 * Place at: /var/www/app.novarax.ae/fix-storage-now.php
 * Run: https://app.novarax.ae/fix-storage-now.php
 * 
 * This script manually updates storage for all tenants RIGHT NOW
 */

require_once __DIR__ . '/wp-load.php';

if (!current_user_can('manage_options')) {
    die('Access denied. Admin access required.');
}

set_time_limit(300); // 5 minutes

echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.success { color: #46b450; }
.error { color: #dc3232; }
.info { color: #0073aa; }
</style>";

echo "<h1>Quick Storage Fix</h1>";
echo "<p>Updating storage for all active tenants...</p><hr>";

global $wpdb;
$db_manager = new NovaRax_Database_Manager();
$table = $db_manager->get_table_name('tenants');

// Get all active tenants
$tenants = $wpdb->get_results("SELECT id, tenant_username, subdomain, database_name, storage_used FROM {$table} WHERE status = 'active'");

if (empty($tenants)) {
    echo "<p class='error'>No active tenants found.</p>";
    exit;
}

echo "<p class='info'>Found " . count($tenants) . " active tenants</p><br>";

$updated = 0;
$failed = 0;

foreach ($tenants as $tenant) {
    echo "<strong>Tenant: {$tenant->tenant_username}</strong><br>";
    
    // Calculate database size
    $db_size = $db_manager->get_database_size($tenant->database_name);
    echo "  Database: " . size_format($db_size, 2) . "<br>";
    
    // Calculate uploads size
    $username = explode('.', $tenant->subdomain)[0];
    $uploads_size = 0;
    
    $paths = array(
        "/var/www/vhosts/novarax.ae/tenant-dashboard/wp-content/uploads/sites/{$username}",
        "/var/www/vhosts/novarax.ae/tenant-dashboard/wp-content/uploads/{$username}",
    );
    
    foreach ($paths as $path) {
        if (is_dir($path)) {
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $uploads_size += $file->getSize();
                    }
                }
            } catch (Exception $e) {
                echo "  <span class='error'>Error reading uploads: {$e->getMessage()}</span><br>";
            }
            break;
        }
    }
    
    echo "  Uploads: " . size_format($uploads_size, 2) . "<br>";
    
    $total = $db_size + $uploads_size;
    echo "  <strong>Total: " . size_format($total, 2) . "</strong><br>";
    
    // Update database
    $result = $wpdb->update(
        $table,
        array('storage_used' => $total),
        array('id' => $tenant->id),
        array('%d'),
        array('%d')
    );
    
    if ($result !== false) {
        echo "  <span class='success'>✓ Updated</span><br>";
        $updated++;
    } else {
        echo "  <span class='error'>✗ Failed: " . $wpdb->last_error . "</span><br>";
        $failed++;
    }
    
    echo "<br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p class='success'>Successfully updated: {$updated} tenants</p>";
if ($failed > 0) {
    echo "<p class='error'>Failed: {$failed} tenants</p>";
}

echo "<br><a href='/wp-admin/admin.php?page=novarax-tenants'>← Back to Tenants</a>";
?>