<?php
require_once __DIR__ . '/wp-load.php';

$queue = new NovaRax_Provisioning_Queue();
$stats = $queue->get_statistics();

echo "<pre>";
print_r($stats);
echo "</pre>";

// Check queue items
$items = $queue->get_queue();
print_r($items);