<?php
/**
 * Plugin Name: NovaRax Cron Fix
 * Description: Ensures provisioning queue processes correctly
 */

// Register the action directly
add_action('novarax_process_provisioning_queue', function() {
    if (class_exists('NovaRax_Provisioning_Queue')) {
        $queue = new NovaRax_Provisioning_Queue();
        $queue->process_queue();
    }
}, 10);

// Ensure the schedule exists
add_filter('cron_schedules', function($schedules) {
    if (!isset($schedules['every_minute'])) {
        $schedules['every_minute'] = array(
            'interval' => 60,
            'display' => 'Every Minute'
        );
    }
    return $schedules;
});

// Schedule if not scheduled
add_action('init', function() {
    if (!wp_next_scheduled('novarax_process_provisioning_queue')) {
        wp_schedule_event(time(), 'every_minute', 'novarax_process_provisioning_queue');
    }
});