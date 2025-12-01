<?php
// Simulate what happens when wp-cron.php runs
define('DOING_CRON', true);
require_once('wp-load.php');

echo "=== Testing Cron Execution ===\n\n";

// Check if our hook exists
global $wp_filter;
echo "1. Checking if hook 'novarax_process_provisioning_queue' has callbacks:\n";
if (isset($wp_filter['novarax_process_provisioning_queue'])) {
    echo "   ✓ Hook exists with " . count($wp_filter['novarax_process_provisioning_queue']->callbacks) . " priority levels\n";
    foreach ($wp_filter['novarax_process_provisioning_queue']->callbacks as $priority => $callbacks) {
        echo "   Priority {$priority}: " . count($callbacks) . " callback(s)\n";
    }
} else {
    echo "   ✗ Hook has NO callbacks!\n";
}

// Check scheduled events
echo "\n2. Checking scheduled events:\n";
$crons = _get_cron_array();
$found = false;
foreach ($crons as $timestamp => $cron) {
    if (isset($cron['novarax_process_provisioning_queue'])) {
        $found = true;
        echo "   ✓ Event scheduled for: " . date('Y-m-d H:i:s', $timestamp) . "\n";
    }
}
if (!$found) {
    echo "   ✗ Event not found in schedule\n";
}

// Try to spawn cron
echo "\n3. Running wp_cron():\n";
spawn_cron();
echo "   ✓ Cron spawned\n";

// Wait a moment
sleep(2);

// Check if queue was processed
echo "\n4. Checking queue status:\n";
$queue = new NovaRax_Provisioning_Queue();
$stats = $queue->get_statistics();
echo "   Pending: {$stats['pending']}\n";
echo "   Processing: " . ($stats['is_processing'] ? 'YES' : 'NO') . "\n";

echo "\n=== Test Complete ===\n";
?>