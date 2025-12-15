<?php
/**
 * Debug Storage Calculation
 * 
 * Place at: /var/www/app.novarax.ae/debug-storage-calc.php
 * Run: https://app.novarax.ae/debug-storage-calc.php?tenant_id=50
 */

require_once __DIR__ . '/wp-load.php';

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<pre>";
echo "=== Storage Calculation Debug ===\n\n";

$tenant_id = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : 50;

echo "Tenant ID: {$tenant_id}\n\n";

// 1. Check if class exists
echo "1. Checking if NovaRax_Storage_Calculator class exists:\n";
if (class_exists('NovaRax_Storage_Calculator')) {
    echo "   ✓ Class exists\n\n";
} else {
    echo "   ✗ Class NOT found!\n";
    echo "   File location: " . NOVARAX_TM_PLUGIN_DIR . "includes/class-storage-calculator.php\n";
    echo "   File exists: " . (file_exists(NOVARAX_TM_PLUGIN_DIR . "includes/class-storage-calculator.php") ? "YES" : "NO") . "\n\n";
}

// 2. Get tenant info
echo "2. Getting tenant information:\n";
global $wpdb;
$db_manager = new NovaRax_Database_Manager();
$table = $db_manager->get_table_name('tenants');

$tenant = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $tenant_id));

if (!$tenant) {
    echo "   ✗ Tenant not found!\n";
    exit;
}

echo "   Username: {$tenant->tenant_username}\n";
echo "   Subdomain: {$tenant->subdomain}\n";
echo "   Database: {$tenant->database_name}\n";
echo "   Current storage_used: " . size_format($tenant->storage_used, 2) . "\n";
echo "   Storage limit: " . size_format($tenant->storage_limit, 2) . "\n\n";

// 3. Calculate database size
echo "3. Calculating database size:\n";
$db_size = $db_manager->get_database_size($tenant->database_name);
echo "   Database size: " . size_format($db_size, 2) . " ({$db_size} bytes)\n\n";

// 4. Check uploads directories
echo "4. Checking uploads directories:\n";
$username = explode('.', $tenant->subdomain)[0];

$paths_to_check = array(
    "/var/www/vhosts/novarax.ae/tenant-dashboard/wp-content/uploads/sites/{$username}",
    "/var/www/vhosts/novarax.ae/tenant-dashboard/wp-content/uploads/{$username}",
    "/var/www/tenant-dashboard/wp-content/uploads/sites/{$username}",
    "/var/www/tenant-dashboard/wp-content/uploads/{$username}",
);

$uploads_path = null;
$uploads_size = 0;

foreach ($paths_to_check as $path) {
    echo "   Checking: {$path}\n";
    if (is_dir($path)) {
        echo "   ✓ Found!\n";
        $uploads_path = $path;
        
        // Calculate directory size
        $size = 0;
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
            $uploads_size = $size;
            echo "   Uploads size: " . size_format($uploads_size, 2) . " ({$uploads_size} bytes)\n";
        } catch (Exception $e) {
            echo "   ✗ Error calculating size: " . $e->getMessage() . "\n";
        }
        break;
    } else {
        echo "   ✗ Not found\n";
    }
}

if (!$uploads_path) {
    echo "   ⚠ No uploads directory found\n";
}

echo "\n";

// 5. Calculate total
$total_storage = $db_size + $uploads_size;
echo "5. Total calculated storage:\n";
echo "   Database: " . size_format($db_size, 2) . "\n";
echo "   Uploads:  " . size_format($uploads_size, 2) . "\n";
echo "   ---------\n";
echo "   TOTAL:    " . size_format($total_storage, 2) . " ({$total_storage} bytes)\n\n";

// 6. Try to update
if (isset($_GET['update'])) {
    echo "6. Updating database:\n";
    $updated = $wpdb->update(
        $table,
        array('storage_used' => $total_storage),
        array('id' => $tenant_id),
        array('%d'),
        array('%d')
    );
    
    if ($updated !== false) {
        echo "   ✓ Updated successfully!\n";
        echo "   New value: " . size_format($total_storage, 2) . "\n";
    } else {
        echo "   ✗ Update failed!\n";
        echo "   Error: " . $wpdb->last_error . "\n";
    }
} else {
    echo "6. To update the database, add &update=1 to the URL\n";
}

echo "\n=== End Debug ===\n";
echo "</pre>";
?>