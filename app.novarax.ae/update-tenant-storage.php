<?php
/**
 * Manual Storage Update Script
 * 
 * Place this file at: /var/www/app.novarax.ae/update-tenant-storage.php
 * Run manually: php /var/www/app.novarax.ae/update-tenant-storage.php
 * Or visit: https://app.novarax.ae/update-tenant-storage.php
 */

require_once __DIR__ . '/wp-load.php';

// Check if running from CLI or web
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    // Add basic security check for web access
    if (!current_user_can('manage_options')) {
        die('Access denied. Administrator privileges required.');
    }
    echo "<pre>";
}

echo "=== NovaRax Tenant Storage Update ===\n\n";

// Check if the storage calculator class exists
if (!class_exists('NovaRax_Storage_Calculator')) {
    echo "ERROR: NovaRax_Storage_Calculator class not found!\n";
    echo "Make sure the class file is loaded in the plugin.\n";
    exit(1);
}

// Initialize the calculator
$calculator = new NovaRax_Storage_Calculator();

// Option 1: Calculate all tenants
if (isset($_GET['all']) || (isset($argv[1]) && $argv[1] === 'all')) {
    echo "Calculating storage for ALL tenants...\n\n";
    $calculator->calculate_all_tenants_storage();
    echo "\n✓ Complete!\n";
}
// Option 2: Calculate specific tenant
elseif (isset($_GET['tenant_id']) || (isset($argv[1]) && is_numeric($argv[1]))) {
    $tenant_id = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : (int)$argv[1];
    
    echo "Calculating storage for tenant ID: {$tenant_id}\n\n";
    
    $result = $calculator->force_recalculate($tenant_id);
    
    if ($result['success']) {
        echo "✓ Success!\n";
        echo "Storage Used: {$result['storage_formatted']}\n";
    } else {
        echo "✗ Failed: {$result['message']}\n";
    }
}
// Show usage
else {
    echo "Usage:\n";
    echo "  Calculate all tenants:      php update-tenant-storage.php all\n";
    echo "  Calculate specific tenant:  php update-tenant-storage.php <tenant_id>\n";
    echo "  Web access all:             ?all=1\n";
    echo "  Web access specific:        ?tenant_id=<id>\n";
}

if (!$is_cli) {
    echo "</pre>";
}
?>