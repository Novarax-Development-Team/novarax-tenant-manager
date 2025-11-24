<?php
/**
 * Debug Provisioning - Run this to diagnose and manually process the queue
 */
require_once __DIR__ . '/wp-load.php';

echo "<h1>Provisioning Debug & Manual Process</h1>";

// 1. Check cron scheduled events
echo "<h2>1. Cron Status</h2>";
$next_scheduled = wp_next_scheduled('novarax_process_provisioning_queue');
if ($next_scheduled) {
    echo "Next scheduled run: " . date('Y-m-d H:i:s', $next_scheduled) . "<br>";
    echo "Current time: " . date('Y-m-d H:i:s', time()) . "<br>";
    echo "Difference: " . ($next_scheduled - time()) . " seconds<br>";
} else {
    echo "<span style='color:red'>Cron NOT scheduled!</span><br>";
    // Schedule it
    wp_schedule_event(time(), 'every_minute', 'novarax_process_provisioning_queue');
    echo "Scheduled cron job now.<br>";
}

// 2. Check queue status
echo "<h2>2. Queue Status</h2>";
$queue = new NovaRax_Provisioning_Queue();
$stats = $queue->get_statistics();
echo "<pre>";
print_r($stats);
echo "</pre>";

// 3. Check for processing lock
echo "<h2>3. Processing Lock</h2>";
$processing = get_transient('novarax_provisioning_processing');
if ($processing) {
    echo "<span style='color:orange'>Processing lock is SET - another process may be running</span><br>";
    echo "Force clear lock? <a href='?clear_lock=1'>Click here</a><br>";
} else {
    echo "<span style='color:green'>No processing lock - ready to process</span><br>";
}

// Clear lock if requested
if (isset($_GET['clear_lock'])) {
    delete_transient('novarax_provisioning_processing');
    echo "<span style='color:green'>Lock cleared!</span><br>";
}

// 4. Get pending items
echo "<h2>4. Pending Items</h2>";
$items = $queue->get_queue();
if (empty($items)) {
    echo "No items in queue.<br>";
} else {
    foreach ($items as $item) {
        echo "Tenant ID: {$item['tenant_id']}, Status: {$item['status']}, Attempts: {$item['attempts']}<br>";
    }
}

// 5. Manual process option
echo "<h2>5. Manual Processing</h2>";
if (isset($_GET['process'])) {
    echo "<h3>Starting manual process...</h3>";
    
    // Clear any stuck lock first
    delete_transient('novarax_provisioning_processing');
    
    // Get first pending item
    $queue_items = $queue->get_queue();
    if (!empty($queue_items)) {
        $item = $queue_items[0];
        $tenant_id = $item['tenant_id'];
        
        echo "Processing tenant ID: {$tenant_id}<br>";
        
        // Get tenant
        $tenant_ops = new NovaRax_Tenant_Operations();
        $tenant = $tenant_ops->get_tenant($tenant_id);
        
        if (!$tenant) {
            echo "<span style='color:red'>ERROR: Tenant not found!</span><br>";
        } else {
            echo "Tenant found: {$tenant->tenant_username}<br>";
            echo "Current status: {$tenant->status}<br>";
            echo "Database name: {$tenant->database_name}<br>";
            
            // Try to provision
            echo "<h4>Starting provisioning...</h4>";
            $result = $tenant_ops->provision_tenant($tenant_id);
            
            echo "<pre>";
            print_r($result);
            echo "</pre>";
            
            if ($result['success']) {
                // Remove from queue
                $queue->remove_from_queue($tenant_id);
                echo "<span style='color:green'>SUCCESS! Tenant provisioned and removed from queue.</span><br>";
            } else {
                echo "<span style='color:red'>FAILED: " . $result['error'] . "</span><br>";
            }
        }
    }
} else {
    echo "<a href='?process=1' style='padding:10px 20px; background:#0073aa; color:white; text-decoration:none; border-radius:4px;'>Process Queue Now</a><br><br>";
}

// 6. Check WordPress cron
echo "<h2>6. WordPress Cron Check</h2>";
if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
    echo "<span style='color:orange'>WP_CRON is DISABLED in wp-config.php</span><br>";
    echo "You need to set up a system cron job or enable WP_CRON.<br>";
} else {
    echo "<span style='color:green'>WP_CRON is enabled</span><br>";
}

// 7. Check if cron schedules are registered
echo "<h2>7. Cron Schedules</h2>";
$schedules = wp_get_schedules();
if (isset($schedules['every_minute'])) {
    echo "<span style='color:green'>every_minute schedule is registered</span><br>";
} else {
    echo "<span style='color:red'>every_minute schedule is NOT registered!</span><br>";
}

echo "<h2>8. Force Trigger Cron</h2>";
if (isset($_GET['trigger_cron'])) {
    do_action('novarax_process_provisioning_queue');
    echo "<span style='color:green'>Cron action triggered!</span><br>";
} else {
    echo "<a href='?trigger_cron=1' style='padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:4px;'>Trigger Cron Action</a><br>";
}