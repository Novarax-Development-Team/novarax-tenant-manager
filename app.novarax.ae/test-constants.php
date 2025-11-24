<?php
// Load WordPress
require_once __DIR__ . '/wp-load.php';

echo "=== DB Constants Check ===\n";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";
echo "DB_ROOT_USER: " . (defined('DB_ROOT_USER') ? DB_ROOT_USER : 'NOT DEFINED') . "\n";
echo "DB_ROOT_PASSWORD: " . (defined('DB_ROOT_PASSWORD') ? 'SET (hidden)' : 'NOT DEFINED') . "\n";

echo "\n=== Direct MariaDB Connection Test ===\n";

$host = 'localhost';
$user = 'root';  // Hardcoded for test
$pass = 'U2z!8kPq#T7jL4rW';  // Your password
$port = 3306;

echo "Attempting connection to {$host}:{$port} as '{$user}'...\n";

$mysqli = @new mysqli($host, $user, $pass, '', $port);

if ($mysqli->connect_error) {
    echo "FAILED: " . $mysqli->connect_error . "\n";
    echo "Error code: " . $mysqli->connect_errno . "\n";
    
    // Try without port
    echo "\nTrying without port specification...\n";
    $mysqli2 = @new mysqli($host, $user, $pass);
    if ($mysqli2->connect_error) {
        echo "FAILED: " . $mysqli2->connect_error . "\n";
    } else {
        echo "SUCCESS without port!\n";
        $mysqli2->close();
    }
    
    // Try with socket
    echo "\nTrying with socket...\n";
    $socket = '/var/lib/mysql/mysql.sock';
    if (file_exists($socket)) {
        $mysqli3 = @new mysqli($host, $user, $pass, '', 0, $socket);
        if ($mysqli3->connect_error) {
            echo "FAILED with socket: " . $mysqli3->connect_error . "\n";
        } else {
            echo "SUCCESS with socket!\n";
            $mysqli3->close();
        }
    } else {
        echo "Socket file not found at: $socket\n";
        // Try common Plesk socket locations
        $plesk_socket = '/var/run/mysqld/mysqld.sock';
        if (file_exists($plesk_socket)) {
            echo "Found socket at: $plesk_socket\n";
        }
    }
} else {
    echo "SUCCESS! Connected to MariaDB.\n";
    echo "Server info: " . $mysqli->server_info . "\n";
    
    // Test CREATE DATABASE privilege
    echo "\n=== Testing CREATE DATABASE Privilege ===\n";
    $test_db = 'novarax_privilege_test_' . time();
    if ($mysqli->query("CREATE DATABASE `{$test_db}`")) {
        echo "SUCCESS: Can create databases!\n";
        $mysqli->query("DROP DATABASE `{$test_db}`");
        echo "Cleanup: Test database dropped.\n";
    } else {
        echo "FAILED: Cannot create database - " . $mysqli->error . "\n";
        echo "You need to grant CREATE privilege to 'admin' user.\n";
    }
    
    $mysqli->close();
}

echo "\n=== Check MySQL Socket Locations ===\n";
$possible_sockets = array(
    '/var/lib/mysql/mysql.sock',
    '/var/run/mysqld/mysqld.sock',
    '/tmp/mysql.sock',
    '/var/run/mysql/mysql.sock'
);

foreach ($possible_sockets as $sock) {
    echo "$sock: " . (file_exists($sock) ? "EXISTS" : "not found") . "\n";
}