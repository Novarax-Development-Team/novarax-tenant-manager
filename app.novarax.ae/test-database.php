<?php
require_once __DIR__ . '/wp-load.php';

echo "<h1>MySQL Connection Test</h1>";

// Check constants
echo "<h2>Constants Check:</h2>";
echo "DB_ROOT_USER defined: " . (defined('DB_ROOT_USER') ? 'YES - ' . DB_ROOT_USER : 'NO') . "<br>";
echo "DB_ROOT_PASSWORD defined: " . (defined('DB_ROOT_PASSWORD') ? 'YES' : 'NO') . "<br>";

// Test connection
echo "<h2>Connection Test:</h2>";
$db_manager = new NovaRax_Database_Manager();
$result = $db_manager->create_tenant_database('novarax_test_db123', 'novarax_test');

echo "<pre>";
print_r($result);
echo "</pre>";

// Cleanup if successful
if ($result['success']) {
    echo "<h3>SUCCESS! Cleaning up test database...</h3>";
    $db_manager->delete_tenant_database('novarax_test_db123', 'novarax_test');
}