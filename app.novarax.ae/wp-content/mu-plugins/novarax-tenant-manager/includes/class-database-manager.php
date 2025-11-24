<?php
/**
 * Database Manager Class
 * 
 * Handles all database operations including table creation, tenant database provisioning,
 * and database connections management.
 *
 * @package NovaRax\TenantManager
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class NovaRax_Database_Manager {
    
    /**
     * Database table names
     *
     * @var array
     */
    private $tables = array();
    
    /**
     * WordPress database object
     *
     * @var wpdb
     */
    private $wpdb;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Define table names
        $this->tables = array(
            'tenants' => $wpdb->prefix . 'novarax_tenants',
            'modules' => $wpdb->prefix . 'novarax_modules',
            'tenant_modules' => $wpdb->prefix . 'novarax_tenant_modules',
            'api_keys' => $wpdb->prefix . 'novarax_api_keys',
            'audit_logs' => $wpdb->prefix . 'novarax_audit_logs',
            'usage_stats' => $wpdb->prefix . 'novarax_usage_stats',
        );
    }
    
    /**
     * Get table name
     *
     * @param string $table Table identifier
     * @return string Full table name
     */
    public function get_table_name($table) {
        return isset($this->tables[$table]) ? $this->tables[$table] : '';
    }
    
    /**
     * Create all custom database tables
     *
     * @return bool Success status
     */
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $this->wpdb->get_charset_collate();
        $created = true;
        
        // Create tenants table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tables['tenants']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            tenant_username VARCHAR(100) NOT NULL,
            account_name VARCHAR(255) NOT NULL,
            company_name VARCHAR(255) DEFAULT NULL,
            subdomain VARCHAR(100) NOT NULL,
            database_name VARCHAR(100) NOT NULL,
            status ENUM('active', 'suspended', 'cancelled', 'pending') DEFAULT 'pending',
            storage_used BIGINT(20) DEFAULT 0,
            storage_limit BIGINT(20) DEFAULT 5368709120,
            user_limit INT DEFAULT 10,
            phone_number VARCHAR(50) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            billing_email VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME DEFAULT NULL,
            metadata LONGTEXT DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_username (tenant_username),
            UNIQUE KEY unique_subdomain (subdomain),
            UNIQUE KEY unique_database (database_name),
            KEY idx_user_id (user_id),
            KEY idx_status (status),
            FOREIGN KEY (user_id) REFERENCES {$this->wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";
        
        if (!dbDelta($sql)) {
            $created = false;
            NovaRax_Logger::log('Failed to create tenants table', 'error');
        }
        
        // Create modules table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tables['modules']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            module_name VARCHAR(255) NOT NULL,
            module_slug VARCHAR(100) NOT NULL,
            plugin_path VARCHAR(255) NOT NULL,
            product_id BIGINT(20) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            version VARCHAR(50) DEFAULT NULL,
            requires_php VARCHAR(10) DEFAULT NULL,
            requires_modules TEXT DEFAULT NULL,
            icon_url VARCHAR(500) DEFAULT NULL,
            status ENUM('active', 'inactive', 'deprecated') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_slug (module_slug),
            KEY idx_product (product_id),
            KEY idx_status (status)
        ) $charset_collate;";
        
        if (!dbDelta($sql)) {
            $created = false;
            NovaRax_Logger::log('Failed to create modules table', 'error');
        }
        
        // Create tenant_modules table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tables['tenant_modules']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tenant_id BIGINT(20) UNSIGNED NOT NULL,
            module_id BIGINT(20) UNSIGNED NOT NULL,
            subscription_id BIGINT(20) DEFAULT NULL,
            status ENUM('active', 'inactive', 'expired', 'cancelled') DEFAULT 'active',
            activated_at DATETIME DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL,
            last_checked DATETIME DEFAULT NULL,
            grace_period_ends DATETIME DEFAULT NULL,
            usage_data LONGTEXT DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_tenant_module (tenant_id, module_id),
            KEY idx_tenant (tenant_id),
            KEY idx_module (module_id),
            KEY idx_subscription (subscription_id),
            KEY idx_status (status),
            FOREIGN KEY (tenant_id) REFERENCES {$this->tables['tenants']}(id) ON DELETE CASCADE,
            FOREIGN KEY (module_id) REFERENCES {$this->tables['modules']}(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        if (!dbDelta($sql)) {
            $created = false;
            NovaRax_Logger::log('Failed to create tenant_modules table', 'error');
        }
        
        // Create api_keys table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tables['api_keys']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tenant_id BIGINT(20) UNSIGNED NOT NULL,
            api_key VARCHAR(255) NOT NULL,
            secret_hash VARCHAR(255) NOT NULL,
            permissions TEXT DEFAULT NULL,
            rate_limit INT DEFAULT 1000,
            last_used DATETIME DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL,
            status ENUM('active', 'revoked') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_api_key (api_key),
            KEY idx_tenant (tenant_id),
            KEY idx_status (status),
            FOREIGN KEY (tenant_id) REFERENCES {$this->tables['tenants']}(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        if (!dbDelta($sql)) {
            $created = false;
            NovaRax_Logger::log('Failed to create api_keys table', 'error');
        }
        
        // Create audit_logs table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tables['audit_logs']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tenant_id BIGINT(20) UNSIGNED DEFAULT NULL,
            user_id BIGINT(20) UNSIGNED DEFAULT NULL,
            action VARCHAR(255) NOT NULL,
            entity_type VARCHAR(100) DEFAULT NULL,
            entity_id BIGINT(20) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            details LONGTEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_tenant (tenant_id),
            KEY idx_user (user_id),
            KEY idx_action (action),
            KEY idx_created (created_at)
        ) $charset_collate;";
        
        if (!dbDelta($sql)) {
            $created = false;
            NovaRax_Logger::log('Failed to create audit_logs table', 'error');
        }
        
        // Create usage_stats table
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tables['usage_stats']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            tenant_id BIGINT(20) UNSIGNED NOT NULL,
            module_id BIGINT(20) UNSIGNED DEFAULT NULL,
            stat_date DATE NOT NULL,
            active_users INT DEFAULT 0,
            api_calls INT DEFAULT 0,
            storage_used BIGINT(20) DEFAULT 0,
            page_views INT DEFAULT 0,
            custom_metrics LONGTEXT DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_tenant_module_date (tenant_id, module_id, stat_date),
            KEY idx_tenant (tenant_id),
            KEY idx_date (stat_date),
            FOREIGN KEY (tenant_id) REFERENCES {$this->tables['tenants']}(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        if (!dbDelta($sql)) {
            $created = false;
            NovaRax_Logger::log('Failed to create usage_stats table', 'error');
        }
        
        if ($created) {
            NovaRax_Logger::log('All database tables created successfully', 'info');
        }
        
        return $created;
    }
    
    /**
     * Create a new tenant database
     *
     * @param string $database_name Database name
     * @param string $username Database username (optional, will be generated)
     * @return array Result with 'success', 'database', 'username', 'password'
     */
   public function create_tenant_database($database_name, $username = null) {
    try {
        // Sanitize database name
        $database_name = $this->sanitize_database_name($database_name);
        
        // Generate username and password
        if (!$username) {
            $username = substr($database_name, 0, 16);
        }
        // Ensure username is max 16 chars (MySQL limit)
        $username = substr(preg_replace('/[^a-zA-Z0-9_]/', '', $username), 0, 16);
        
        // Generate secure password
        $password = wp_generate_password(24, true, false); // No special chars that might cause issues
        
        // Get connection
        $mysqli = $this->get_mysql_connection();
        
        if (!$mysqli) {
            throw new Exception('Failed to connect to MySQL server');
        }
        
        NovaRax_Logger::info("Creating tenant database: {$database_name}");
        
        // Create database
        $sql = "CREATE DATABASE IF NOT EXISTS `{$database_name}` 
                CHARACTER SET utf8mb4 
                COLLATE utf8mb4_unicode_ci";
        
        if (!$mysqli->query($sql)) {
            throw new Exception('Failed to create database: ' . $mysqli->error);
        }
        
        NovaRax_Logger::info("Database created: {$database_name}");
        
        // For Plesk: We might need to create user differently
        // First, try to drop user if exists (ignore errors)
        $mysqli->query("DROP USER IF EXISTS '{$username}'@'localhost'");
        
        // Create user with password
        // MariaDB/MySQL 5.7+ syntax
        $sql = "CREATE USER '{$username}'@'localhost' IDENTIFIED BY '{$mysqli->real_escape_string($password)}'";
        
        if (!$mysqli->query($sql)) {
            // Try alternative syntax for older versions
            $sql = "CREATE USER '{$username}'@'localhost'";
            if (!$mysqli->query($sql)) {
                throw new Exception('Failed to create database user: ' . $mysqli->error);
            }
            // Set password separately
            $sql = "SET PASSWORD FOR '{$username}'@'localhost' = PASSWORD('{$mysqli->real_escape_string($password)}')";
            if (!$mysqli->query($sql)) {
                // Try ALTER USER for MariaDB 10.2+
                $sql = "ALTER USER '{$username}'@'localhost' IDENTIFIED BY '{$mysqli->real_escape_string($password)}'";
                if (!$mysqli->query($sql)) {
                    throw new Exception('Failed to set password for database user: ' . $mysqli->error);
                }
            }
        }
        
        NovaRax_Logger::info("Database user created: {$username}");
        
        // Grant privileges
        $sql = "GRANT ALL PRIVILEGES ON `{$database_name}`.* TO '{$username}'@'localhost'";
        
        if (!$mysqli->query($sql)) {
            throw new Exception('Failed to grant privileges: ' . $mysqli->error);
        }
        
        // Flush privileges
        $mysqli->query('FLUSH PRIVILEGES');
        
        NovaRax_Logger::info("Privileges granted to {$username} on {$database_name}");
        
        // Import WordPress core schema
        $this->import_wordpress_schema($database_name, $username, $password);
        
        $mysqli->close();
        
        NovaRax_Logger::info("Tenant database provisioned successfully: {$database_name}");
        
        return array(
            'success' => true,
            'database' => $database_name,
            'username' => $username,
            'password' => $password,
        );
        
    } catch (Exception $e) {
        NovaRax_Logger::error('Database creation failed: ' . $e->getMessage());
        
        return array(
            'success' => false,
            'error' => $e->getMessage(),
        );
    }
} 
    
    /**
     * Import WordPress core schema to tenant database
     *
     * @param string $database_name Database name
     * @param string $username Database username
     * @param string $password Database password
     * @return bool Success status
     */
   private function import_wordpress_schema($database_name, $username, $password) {
    try {
        NovaRax_Logger::info("Importing WordPress schema to: {$database_name}");
        
        // Parse DB_HOST for port
        $db_host = DB_HOST;
        $port = 3306;
        if (strpos($db_host, ':') !== false) {
            list($db_host, $port) = explode(':', $db_host);
            $port = (int) $port;
        }
        
        // Create new wpdb instance for tenant database
        $tenant_db = new wpdb($username, $password, $database_name, DB_HOST);
        $tenant_db->suppress_errors(false);
        $tenant_db->show_errors(true);
        
        // Check connection
        if (!empty($tenant_db->last_error)) {
            throw new Exception('Failed to connect to tenant database: ' . $tenant_db->last_error);
        }
        
        // Set the prefix for tenant
        $tenant_db->set_prefix('wp_');
        
        $charset_collate = $tenant_db->get_charset_collate();
        
        // Create tables manually with proper structure
        $tables_sql = array();
        
        // wp_users table
        $tables_sql[] = "CREATE TABLE IF NOT EXISTS `wp_users` (
            `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `user_login` varchar(60) NOT NULL DEFAULT '',
            `user_pass` varchar(255) NOT NULL DEFAULT '',
            `user_nicename` varchar(50) NOT NULL DEFAULT '',
            `user_email` varchar(100) NOT NULL DEFAULT '',
            `user_url` varchar(100) NOT NULL DEFAULT '',
            `user_registered` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `user_activation_key` varchar(255) NOT NULL DEFAULT '',
            `user_status` int(11) NOT NULL DEFAULT 0,
            `display_name` varchar(250) NOT NULL DEFAULT '',
            PRIMARY KEY (`ID`),
            KEY `user_login_key` (`user_login`),
            KEY `user_nicename` (`user_nicename`),
            KEY `user_email` (`user_email`)
        ) ENGINE=InnoDB {$charset_collate};";
        
        // wp_usermeta table
        $tables_sql[] = "CREATE TABLE IF NOT EXISTS `wp_usermeta` (
            `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
            `meta_key` varchar(255) DEFAULT NULL,
            `meta_value` longtext,
            PRIMARY KEY (`umeta_id`),
            KEY `user_id` (`user_id`),
            KEY `meta_key` (`meta_key`(191))
        ) ENGINE=InnoDB {$charset_collate};";
        
        // wp_options table
        $tables_sql[] = "CREATE TABLE IF NOT EXISTS `wp_options` (
            `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `option_name` varchar(191) NOT NULL DEFAULT '',
            `option_value` longtext NOT NULL,
            `autoload` varchar(20) NOT NULL DEFAULT 'yes',
            PRIMARY KEY (`option_id`),
            UNIQUE KEY `option_name` (`option_name`),
            KEY `autoload` (`autoload`)
        ) ENGINE=InnoDB {$charset_collate};";
        
        // wp_posts table
        $tables_sql[] = "CREATE TABLE IF NOT EXISTS `wp_posts` (
            `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `post_author` bigint(20) unsigned NOT NULL DEFAULT 0,
            `post_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `post_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `post_content` longtext NOT NULL,
            `post_title` text NOT NULL,
            `post_excerpt` text NOT NULL,
            `post_status` varchar(20) NOT NULL DEFAULT 'publish',
            `comment_status` varchar(20) NOT NULL DEFAULT 'open',
            `ping_status` varchar(20) NOT NULL DEFAULT 'open',
            `post_password` varchar(255) NOT NULL DEFAULT '',
            `post_name` varchar(200) NOT NULL DEFAULT '',
            `to_ping` text NOT NULL,
            `pinged` text NOT NULL,
            `post_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `post_modified_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `post_content_filtered` longtext NOT NULL,
            `post_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
            `guid` varchar(255) NOT NULL DEFAULT '',
            `menu_order` int(11) NOT NULL DEFAULT 0,
            `post_type` varchar(20) NOT NULL DEFAULT 'post',
            `post_mime_type` varchar(100) NOT NULL DEFAULT '',
            `comment_count` bigint(20) NOT NULL DEFAULT 0,
            PRIMARY KEY (`ID`),
            KEY `post_name` (`post_name`(191)),
            KEY `type_status_date` (`post_type`,`post_status`,`post_date`,`ID`),
            KEY `post_parent` (`post_parent`),
            KEY `post_author` (`post_author`)
        ) ENGINE=InnoDB {$charset_collate};";
        
        // wp_postmeta table
        $tables_sql[] = "CREATE TABLE IF NOT EXISTS `wp_postmeta` (
            `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `post_id` bigint(20) unsigned NOT NULL DEFAULT 0,
            `meta_key` varchar(255) DEFAULT NULL,
            `meta_value` longtext,
            PRIMARY KEY (`meta_id`),
            KEY `post_id` (`post_id`),
            KEY `meta_key` (`meta_key`(191))
        ) ENGINE=InnoDB {$charset_collate};";
        
        // wp_comments table
        $tables_sql[] = "CREATE TABLE IF NOT EXISTS `wp_comments` (
            `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT 0,
            `comment_author` tinytext NOT NULL,
            `comment_author_email` varchar(100) NOT NULL DEFAULT '',
            `comment_author_url` varchar(200) NOT NULL DEFAULT '',
            `comment_author_IP` varchar(100) NOT NULL DEFAULT '',
            `comment_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `comment_date_gmt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `comment_content` text NOT NULL,
            `comment_karma` int(11) NOT NULL DEFAULT 0,
            `comment_approved` varchar(20) NOT NULL DEFAULT '1',
            `comment_agent` varchar(255) NOT NULL DEFAULT '',
            `comment_type` varchar(20) NOT NULL DEFAULT 'comment',
            `comment_parent` bigint(20) unsigned NOT NULL DEFAULT 0,
            `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY (`comment_ID`),
            KEY `comment_post_ID` (`comment_post_ID`),
            KEY `comment_approved_date_gmt` (`comment_approved`,`comment_date_gmt`),
            KEY `comment_date_gmt` (`comment_date_gmt`),
            KEY `comment_parent` (`comment_parent`),
            KEY `comment_author_email` (`comment_author_email`(10))
        ) ENGINE=InnoDB {$charset_collate};";
        
        // wp_commentmeta table
        $tables_sql[] = "CREATE TABLE IF NOT EXISTS `wp_commentmeta` (
            `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
            `meta_key` varchar(255) DEFAULT NULL,
            `meta_value` longtext,
            PRIMARY KEY (`meta_id`),
            KEY `comment_id` (`comment_id`),
            KEY `meta_key` (`meta_key`(191))
        ) ENGINE=InnoDB {$charset_collate};";
        
        // wp_terms table
        $tables_sql[] = "CREATE TABLE IF NOT EXISTS `wp_terms` (
            `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(200) NOT NULL DEFAULT '',
            `slug` varchar(200) NOT NULL DEFAULT '',
            `term_group` bigint(10) NOT NULL DEFAULT 0,
            PRIMARY KEY (`term_id`),
            KEY `slug` (`slug`(191)),
            KEY `name` (`name`(191))
        ) ENGINE=InnoDB {$charset_collate};";
        
        // wp_termmeta table
        $tables_sql[] = "CREATE TABLE IF NOT EXISTS `wp_termmeta` (
            `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
            `meta_key` varchar(255) DEFAULT NULL,
            `meta_value` longtext,
            PRIMARY KEY (`meta_id`),
            KEY `term_id` (`term_id`),
            KEY `meta_key` (`meta_key`(191))
        ) ENGINE=InnoDB {$charset_collate};";
        
        // wp_term_taxonomy table
        $tables_sql[] = "CREATE TABLE IF NOT EXISTS `wp_term_taxonomy` (
            `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
            `taxonomy` varchar(32) NOT NULL DEFAULT '',
            `description` longtext NOT NULL,
            `parent` bigint(20) unsigned NOT NULL DEFAULT 0,
            `count` bigint(20) NOT NULL DEFAULT 0,
            PRIMARY KEY (`term_taxonomy_id`),
            UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
            KEY `taxonomy` (`taxonomy`)
        ) ENGINE=InnoDB {$charset_collate};";
        
        // wp_term_relationships table
        $tables_sql[] = "CREATE TABLE IF NOT EXISTS `wp_term_relationships` (
            `object_id` bigint(20) unsigned NOT NULL DEFAULT 0,
            `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT 0,
            `term_order` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`object_id`,`term_taxonomy_id`),
            KEY `term_taxonomy_id` (`term_taxonomy_id`)
        ) ENGINE=InnoDB {$charset_collate};";
        
        // wp_links table
        $tables_sql[] = "CREATE TABLE IF NOT EXISTS `wp_links` (
            `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            `link_url` varchar(255) NOT NULL DEFAULT '',
            `link_name` varchar(255) NOT NULL DEFAULT '',
            `link_image` varchar(255) NOT NULL DEFAULT '',
            `link_target` varchar(25) NOT NULL DEFAULT '',
            `link_description` varchar(255) NOT NULL DEFAULT '',
            `link_visible` varchar(20) NOT NULL DEFAULT 'Y',
            `link_owner` bigint(20) unsigned NOT NULL DEFAULT 1,
            `link_rating` int(11) NOT NULL DEFAULT 0,
            `link_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            `link_rel` varchar(255) NOT NULL DEFAULT '',
            `link_notes` mediumtext NOT NULL,
            `link_rss` varchar(255) NOT NULL DEFAULT '',
            PRIMARY KEY (`link_id`),
            KEY `link_visible` (`link_visible`)
        ) ENGINE=InnoDB {$charset_collate};";
        
        // Execute all table creation queries
        foreach ($tables_sql as $sql) {
            $result = $tenant_db->query($sql);
            if ($result === false) {
                NovaRax_Logger::warning("Table creation warning: " . $tenant_db->last_error);
            }
        }
        
        NovaRax_Logger::info("WordPress tables created for: {$database_name}");
        
        return true;
        
    } catch (Exception $e) {
        NovaRax_Logger::error('Schema import failed: ' . $e->getMessage());
        return false;
    }
}
    
    /**
     * Delete a tenant database and user
     *
     * @param string $database_name Database name
     * @param string $username Database username
     * @return bool Success status
     */
    public function delete_tenant_database($database_name, $username) {
        try {
            $mysqli = $this->get_mysql_connection();
            
            if (!$mysqli) {
                throw new Exception('Failed to connect to MySQL server');
            }
            
            // Drop database
            $sql = "DROP DATABASE IF EXISTS `{$database_name}`";
            if (!$mysqli->query($sql)) {
                throw new Exception('Failed to drop database: ' . $mysqli->error);
            }
            
            // Drop user
            $sql = "DROP USER IF EXISTS '{$username}'@'localhost'";
            if (!$mysqli->query($sql)) {
                throw new Exception('Failed to drop user: ' . $mysqli->error);
            }
            
            $mysqli->query('FLUSH PRIVILEGES');
            $mysqli->close();
            
            NovaRax_Logger::log("Tenant database deleted: {$database_name}", 'info');
            
            return true;
            
        } catch (Exception $e) {
            NovaRax_Logger::log('Database deletion failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Get MySQL connection with elevated privileges
     *
     * @return mysqli|false
     */
    private function get_mysql_connection() {
    // Get credentials from constants
    $root_user = defined('DB_ROOT_USER') ? DB_ROOT_USER : null;
    $root_pass = defined('DB_ROOT_PASSWORD') ? DB_ROOT_PASSWORD : null;
    
    if (!$root_user || $root_pass === null) {
        NovaRax_Logger::error('DB_ROOT_USER or DB_ROOT_PASSWORD not defined in wp-config.php');
        return false;
    }
    
    // Parse host - might be localhost:3306 format
    $db_host = defined('DB_HOST') ? DB_HOST : 'localhost';
    $host = $db_host;
    $port = 3306;
    $socket = null;
    
    // Handle host:port format
    if (strpos($db_host, ':') !== false) {
        $parts = explode(':', $db_host);
        $host = $parts[0];
        // Check if it's a socket path or port
        if (is_numeric($parts[1])) {
            $port = (int) $parts[1];
        } else {
            $socket = $parts[1];
        }
    }
    
    // Try multiple connection methods for Plesk compatibility
    $connection_attempts = array(
        // Attempt 1: Standard localhost
        array('host' => 'localhost', 'port' => $port, 'socket' => null),
        // Attempt 2: IP address (sometimes required by Plesk)
        array('host' => '127.0.0.1', 'port' => $port, 'socket' => null),
        // Attempt 3: With common socket paths
        array('host' => 'localhost', 'port' => 0, 'socket' => '/var/lib/mysql/mysql.sock'),
        array('host' => 'localhost', 'port' => 0, 'socket' => '/var/run/mysqld/mysqld.sock'),
        array('host' => 'localhost', 'port' => 0, 'socket' => '/tmp/mysql.sock'),
    );
    
    $last_error = '';
    
    foreach ($connection_attempts as $attempt) {
        // Suppress warnings during connection attempts
        if ($attempt['socket']) {
            if (!file_exists($attempt['socket'])) {
                continue; // Skip if socket doesn't exist
            }
            $mysqli = @new mysqli(
                $attempt['host'], 
                $root_user, 
                $root_pass, 
                '', 
                $attempt['port'],
                $attempt['socket']
            );
        } else {
            $mysqli = @new mysqli(
                $attempt['host'], 
                $root_user, 
                $root_pass, 
                '', 
                $attempt['port']
            );
        }
        
        if (!$mysqli->connect_error) {
            NovaRax_Logger::debug('MySQL connected successfully', array(
                'host' => $attempt['host'],
                'port' => $attempt['port'],
                'socket' => $attempt['socket'],
                'server_info' => $mysqli->server_info
            ));
            return $mysqli;
        }
        
        $last_error = $mysqli->connect_error;
        NovaRax_Logger::debug('Connection attempt failed', array(
            'host' => $attempt['host'],
            'port' => $attempt['port'],
            'socket' => $attempt['socket'],
            'error' => $mysqli->connect_error
        ));
    }
    
    // All attempts failed
    NovaRax_Logger::error('All MySQL connection attempts failed', array(
        'user' => $root_user,
        'last_error' => $last_error,
        'db_host_config' => $db_host
    ));
    
    return false;
}
    /**
     * Sanitize database name
     *
     * @param string $name Raw database name
     * @return string Sanitized database name
     */
    private function sanitize_database_name($name) {
        // Remove any characters that aren't alphanumeric or underscore
        $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        
        // Ensure it starts with a letter
        if (!preg_match('/^[a-zA-Z]/', $name)) {
            $name = 'db_' . $name;
        }
        
        // Limit length (MySQL database name max is 64 chars)
        $name = substr($name, 0, 64);
        
        return $name;
    }
    
    /**
     * Check if database exists
     *
     * @param string $database_name Database name
     * @return bool
     */
    public function database_exists($database_name) {
        $mysqli = $this->get_mysql_connection();
        
        if (!$mysqli) {
            return false;
        }
        
        $result = $mysqli->query("SHOW DATABASES LIKE '{$database_name}'");
        $exists = $result && $result->num_rows > 0;
        
        $mysqli->close();
        
        return $exists;
    }
    
    /**
     * Get database size in bytes
     *
     * @param string $database_name Database name
     * @return int Size in bytes
     */
    public function get_database_size($database_name) {
        $mysqli = $this->get_mysql_connection();
        
        if (!$mysqli) {
            return 0;
        }
        
        $sql = "SELECT SUM(data_length + index_length) AS size 
                FROM information_schema.TABLES 
                WHERE table_schema = '{$database_name}'";
        
        $result = $mysqli->query($sql);
        
        if ($result && $row = $result->fetch_assoc()) {
            $size = (int) $row['size'];
        } else {
            $size = 0;
        }
        
        $mysqli->close();
        
        return $size;
    }
    
    /**
     * Backup tenant database
     *
     * @param string $database_name Database name
     * @param string $backup_path Path to save backup
     * @return bool|string Backup file path on success, false on failure
     */
    public function backup_tenant_database($database_name, $backup_path = null) {
        if (!$backup_path) {
            $upload_dir = wp_upload_dir();
            $backup_dir = $upload_dir['basedir'] . '/novarax-tenants/backups';
            wp_mkdir_p($backup_dir);
            $backup_path = $backup_dir . '/' . $database_name . '_' . date('Y-m-d_H-i-s') . '.sql';
        }
        
        try {
            $mysqli = $this->get_mysql_connection();
            
            if (!$mysqli) {
                throw new Exception('Failed to connect to MySQL');
            }
            
            // Select database
            $mysqli->select_db($database_name);
            
            // Get all tables
            $tables = array();
            $result = $mysqli->query("SHOW TABLES");
            
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
            
            $sql_dump = "-- NovaRax Tenant Database Backup\n";
            $sql_dump .= "-- Database: {$database_name}\n";
            $sql_dump .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
            
            // Dump each table
            foreach ($tables as $table) {
                // Get table structure
                $result = $mysqli->query("SHOW CREATE TABLE `{$table}`");
                $row = $result->fetch_row();
                $sql_dump .= "\n\n" . $row[1] . ";\n\n";
                
                // Get table data
                $result = $mysqli->query("SELECT * FROM `{$table}`");
                
                while ($row = $result->fetch_assoc()) {
                    $sql_dump .= "INSERT INTO `{$table}` VALUES(";
                    $values = array();
                    foreach ($row as $value) {
                        $values[] = "'" . $mysqli->real_escape_string($value) . "'";
                    }
                    $sql_dump .= implode(',', $values) . ");\n";
                }
            }
            
            $mysqli->close();
            
            // Write to file
            file_put_contents($backup_path, $sql_dump);
            
            NovaRax_Logger::log("Database backup created: {$backup_path}", 'info');
            
            return $backup_path;
            
        } catch (Exception $e) {
            NovaRax_Logger::log('Database backup failed: ' . $e->getMessage(), 'error');
            return false;
        }
    }
}