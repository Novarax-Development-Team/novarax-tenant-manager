<?php
require_once('wp-load.php');

echo "=== WordPress Scheduled Events Check ===\n\n";

// Check if NovaRax event is scheduled
$next_run = wp_next_scheduled('novarax_process_provisioning_queue');

if ($next_run) {
    echo "✓ NovaRax provisioning cron IS scheduled\n";
    echo "  Next run: " . date('Y-m-d H:i:s', $next_run) . "\n";
    echo "  Current time: " . date('Y-m-d H:i:s', time()) . "\n";
    echo "  Time until next run: " . ($next_run - time()) . " seconds\n";
} else {
    echo "✗ NovaRax provisioning cron is NOT scheduled!\n";
    echo "  This is why provisioning doesn't work.\n\n";
    echo "Running fix...\n";
    
    // Schedule it now
    if (wp_schedule_event(time(), 'every_minute', 'novarax_process_provisioning_queue')) {
        echo "✓ Successfully scheduled the cron event\n";
        
        $next_run = wp_next_scheduled('novarax_process_provisioning_queue');
        echo "  Next run: " . date('Y-m-d H:i:s', $next_run) . "\n";
    } else {
        echo "✗ Failed to schedule cron event\n";
    }
}

echo "\n=== All Scheduled Events ===\n";
$crons = _get_cron_array();
if (empty($crons)) {
    echo "No cron events scheduled at all!\n";
} else {
    foreach ($crons as $timestamp => $cron) {
        echo "\nTime: " . date('Y-m-d H:i:s', $timestamp) . "\n";
        foreach ($cron as $hook => $data) {
            echo "  - Hook: {$hook}\n";
        }
    }
}

echo "\n=== Provisioning Queue Status ===\n";
if (class_exists('NovaRax_Provisioning_Queue')) {
    $queue = new NovaRax_Provisioning_Queue();
    $stats = $queue->get_statistics();
    print_r($stats);
    
    echo "\nQueue items:\n";
    $items = $queue->get_queue();
    foreach ($items as $item) {
        echo "  Tenant ID: {$item['tenant_id']} - Status: {$item['status']} - Attempts: {$item['attempts']}\n";
    }
} else {
    echo "✗ NovaRax_Provisioning_Queue class not found!\n";
}
?>