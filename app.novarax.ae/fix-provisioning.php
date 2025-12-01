<?php
/**
 * Fix Provisioning - Manually process pending tenants
 */
require_once __DIR__ . '/wp-load.php';

// Only allow admins
if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo "<h1>Fix Provisioning Queue</h1>";
echo "<style>
    body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; padding: 20px; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .info { color: #17a2b8; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
</style>";

// Clear any stuck processing lock
delete_transient('novarax_provisioning_processing');
echo "<p class='info'>Cleared processing lock.</p>";

// Get queue
$queue = new NovaRax_Provisioning_Queue();
$items = $queue->get_queue();

echo "<h2>Queue Items: " . count($items) . "</h2>";

if (empty($items)) {
    echo "<p>No items in queue.</p>";
    exit;
}

// Process each item
foreach ($items as $item) {
    $tenant_id = $item['tenant_id'];
    echo "<h3>Processing Tenant ID: {$tenant_id}</h3>";
    
    $tenant_ops = new NovaRax_Tenant_Operations();
    $tenant = $tenant_ops->get_tenant($tenant_id);
    
    if (!$tenant) {
        echo "<p class='error'>Tenant not found! Removing from queue.</p>";
        $queue->remove_from_queue($tenant_id);
        continue;
    }
    
    echo "<p>Username: {$tenant->tenant_username}</p>";
    echo "<p>Status: {$tenant->status}</p>";
    echo "<p>Database: {$tenant->database_name}</p>";
    
    if ($tenant->status === 'active') {
        echo "<p class='success'>Already active! Removing from queue.</p>";
        $queue->remove_from_queue($tenant_id);
        continue;
    }
    
    // Try to provision
    echo "<p class='info'>Attempting provisioning...</p>";
    
    try {
        $result = $tenant_ops->provision_tenant($tenant_id);
        
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        
        if ($result['success']) {
            $queue->remove_from_queue($tenant_id);
            echo "<p class='success'>✓ Provisioned successfully!</p>";
        } else {
            echo "<p class='error'>✗ Failed: " . $result['error'] . "</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Exception: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
}

// Check remaining queue
$remaining = $queue->get_queue();
echo "<h2>Remaining in Queue: " . count($remaining) . "</h2>";

// Check logs
echo "<h2>Recent Logs</h2>";
$logs = NovaRax_Logger::get_logs(array('limit' => 20));
echo "<pre>";
foreach ($logs as $log) {
    echo "[{$log->created_at}] [{$log->log_level}] {$log->message}\n";
}
echo "</pre>";