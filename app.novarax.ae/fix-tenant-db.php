<?php
/**
 * Fix existing tenant database - populate with WordPress schema and data
 */
require_once __DIR__ . '/wp-load.php';

if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

echo "<h1>Fix Tenant Database</h1>";
echo "<style>
    body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .info { color: #17a2b8; }
    pre { background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto; }
    .btn { padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin: 5px; }
</style>";

// Get tenant from query string or show list
$tenant_id = isset($_GET['tenant_id']) ? intval($_GET['tenant_id']) : 0;

$tenant_ops = new NovaRax_Tenant_Operations();

if (!$tenant_id) {
    // Show list of tenants
    echo "<h2>Select a Tenant to Fix</h2>";
    $tenants = $tenant_ops->get_tenants(array('limit' => 100));
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Database</th><th>Status</th><th>Action</th></tr>";
    
    foreach ($tenants as $t) {
        echo "<tr>";
        echo "<td>{$t->id}</td>";
        echo "<td>{$t->tenant_username}</td>";
        echo "<td>{$t->database_name}</td>";
        echo "<td>{$t->status}</td>";
        echo "<td><a href='?tenant_id={$t->id}' class='btn'>Fix This Database</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

// Get the tenant
$tenant = $tenant_ops->get_tenant($tenant_id);

if (!$tenant) {
    die("<p class='error'>Tenant not found!</p>");
}

echo "<h2>Fixing Database for: {$tenant->tenant_username}</h2>";
echo "<p>Database: {$tenant->database_name}</p>";

// Get credentials from metadata
$metadata = $tenant->metadata;
$db_username = isset($metadata['db_username']) ? $metadata['db_username'] : substr($tenant->database_name, 0, 16);

// We need the password - try to get it from encrypted storage or use root connection
echo "<h3>Step 1: Connect to Database</h3>";

// Use root connection to work on the database
$root_user = defined('DB_ROOT_USER') ? DB_ROOT_USER : 'root';
$root_pass = defined('DB_ROOT_PASSWORD') ? DB_ROOT_PASSWORD : '';

$mysqli = new mysqli('localhost', $root_user, $root_pass, $tenant->database_name);

if ($mysqli->connect_error) {
    die("<p class='error'>Failed to connect: " . $mysqli->connect_error . "</p>");
}

echo "<p class='success'>Connected to database successfully!</p>";

// Check current tables
$result = $mysqli->query("SHOW TABLES");
$tables = array();
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}
echo "<p>Existing tables: " . implode(', ', $tables) . "</p>";

echo "<h3>Step 2: Create/Update Tables</h3>";

$charset_collate = "DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

// Table definitions
$tables_sql = array(
    'wp_users' => "CREATE TABLE IF NOT EXISTS `wp_users` (
        `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `user_login` varchar(60) NOT NULL DEFAULT '',
        `user_pass` varchar(255) NOT NULL DEFAULT '',
        `user_nicename` varchar(50) NOT NULL DEFAULT '',
        `user_email` varchar(100) NOT NULL DEFAULT '',
        `user_url` varchar(100) NOT NULL DEFAULT '',
        `user_registered` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `user_activation_key` varchar(255) NOT NULL DEFAULT '',
        `user_status` int(11) NOT NULL DEFAULT 0,
        `display_name` varchar(250) NOT NULL DEFAULT '',
        PRIMARY KEY (`ID`),
        KEY `user_login_key` (`user_login`),
        KEY `user_nicename` (`user_nicename`),
        KEY `user_email` (`user_email`)
    ) ENGINE=InnoDB {$charset_collate}",
    
    'wp_usermeta' => "CREATE TABLE IF NOT EXISTS `wp_usermeta` (
        `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `user_id` bigint(20) unsigned NOT NULL DEFAULT 0,
        `meta_key` varchar(255) DEFAULT NULL,
        `meta_value` longtext,
        PRIMARY KEY (`umeta_id`),
        KEY `user_id` (`user_id`),
        KEY `meta_key` (`meta_key`(191))
    ) ENGINE=InnoDB {$charset_collate}",
    
    'wp_options' => "CREATE TABLE IF NOT EXISTS `wp_options` (
        `option_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `option_name` varchar(191) NOT NULL DEFAULT '',
        `option_value` longtext NOT NULL,
        `autoload` varchar(20) NOT NULL DEFAULT 'yes',
        PRIMARY KEY (`option_id`),
        UNIQUE KEY `option_name` (`option_name`),
        KEY `autoload` (`autoload`)
    ) ENGINE=InnoDB {$charset_collate}",
    
    'wp_posts' => "CREATE TABLE IF NOT EXISTS `wp_posts` (
        `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `post_author` bigint(20) unsigned NOT NULL DEFAULT 0,
        `post_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `post_date_gmt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
        `post_modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `post_modified_gmt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
    ) ENGINE=InnoDB {$charset_collate}",
    
    'wp_postmeta' => "CREATE TABLE IF NOT EXISTS `wp_postmeta` (
        `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `post_id` bigint(20) unsigned NOT NULL DEFAULT 0,
        `meta_key` varchar(255) DEFAULT NULL,
        `meta_value` longtext,
        PRIMARY KEY (`meta_id`),
        KEY `post_id` (`post_id`),
        KEY `meta_key` (`meta_key`(191))
    ) ENGINE=InnoDB {$charset_collate}",
    
    'wp_comments' => "CREATE TABLE IF NOT EXISTS `wp_comments` (
        `comment_ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `comment_post_ID` bigint(20) unsigned NOT NULL DEFAULT 0,
        `comment_author` tinytext NOT NULL,
        `comment_author_email` varchar(100) NOT NULL DEFAULT '',
        `comment_author_url` varchar(200) NOT NULL DEFAULT '',
        `comment_author_IP` varchar(100) NOT NULL DEFAULT '',
        `comment_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `comment_date_gmt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
        KEY `comment_parent` (`comment_parent`)
    ) ENGINE=InnoDB {$charset_collate}",
    
    'wp_commentmeta' => "CREATE TABLE IF NOT EXISTS `wp_commentmeta` (
        `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `comment_id` bigint(20) unsigned NOT NULL DEFAULT 0,
        `meta_key` varchar(255) DEFAULT NULL,
        `meta_value` longtext,
        PRIMARY KEY (`meta_id`),
        KEY `comment_id` (`comment_id`),
        KEY `meta_key` (`meta_key`(191))
    ) ENGINE=InnoDB {$charset_collate}",
    
    'wp_terms' => "CREATE TABLE IF NOT EXISTS `wp_terms` (
        `term_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(200) NOT NULL DEFAULT '',
        `slug` varchar(200) NOT NULL DEFAULT '',
        `term_group` bigint(10) NOT NULL DEFAULT 0,
        PRIMARY KEY (`term_id`),
        KEY `slug` (`slug`(191)),
        KEY `name` (`name`(191))
    ) ENGINE=InnoDB {$charset_collate}",
    
    'wp_termmeta' => "CREATE TABLE IF NOT EXISTS `wp_termmeta` (
        `meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
        `meta_key` varchar(255) DEFAULT NULL,
        `meta_value` longtext,
        PRIMARY KEY (`meta_id`),
        KEY `term_id` (`term_id`),
        KEY `meta_key` (`meta_key`(191))
    ) ENGINE=InnoDB {$charset_collate}",
    
    'wp_term_taxonomy' => "CREATE TABLE IF NOT EXISTS `wp_term_taxonomy` (
        `term_taxonomy_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `term_id` bigint(20) unsigned NOT NULL DEFAULT 0,
        `taxonomy` varchar(32) NOT NULL DEFAULT '',
        `description` longtext NOT NULL,
        `parent` bigint(20) unsigned NOT NULL DEFAULT 0,
        `count` bigint(20) NOT NULL DEFAULT 0,
        PRIMARY KEY (`term_taxonomy_id`),
        UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
        KEY `taxonomy` (`taxonomy`)
    ) ENGINE=InnoDB {$charset_collate}",
    
    'wp_term_relationships' => "CREATE TABLE IF NOT EXISTS `wp_term_relationships` (
        `object_id` bigint(20) unsigned NOT NULL DEFAULT 0,
        `term_taxonomy_id` bigint(20) unsigned NOT NULL DEFAULT 0,
        `term_order` int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`object_id`,`term_taxonomy_id`),
        KEY `term_taxonomy_id` (`term_taxonomy_id`)
    ) ENGINE=InnoDB {$charset_collate}",
    
    'wp_links' => "CREATE TABLE IF NOT EXISTS `wp_links` (
        `link_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `link_url` varchar(255) NOT NULL DEFAULT '',
        `link_name` varchar(255) NOT NULL DEFAULT '',
        `link_image` varchar(255) NOT NULL DEFAULT '',
        `link_target` varchar(25) NOT NULL DEFAULT '',
        `link_description` varchar(255) NOT NULL DEFAULT '',
        `link_visible` varchar(20) NOT NULL DEFAULT 'Y',
        `link_owner` bigint(20) unsigned NOT NULL DEFAULT 1,
        `link_rating` int(11) NOT NULL DEFAULT 0,
        `link_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `link_rel` varchar(255) NOT NULL DEFAULT '',
        `link_notes` mediumtext NOT NULL,
        `link_rss` varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY (`link_id`),
        KEY `link_visible` (`link_visible`)
    ) ENGINE=InnoDB {$charset_collate}"
);

foreach ($tables_sql as $table_name => $sql) {
    if ($mysqli->query($sql)) {
        echo "<p class='success'>✓ Table {$table_name} created/verified</p>";
    } else {
        echo "<p class='error'>✗ Failed to create {$table_name}: " . $mysqli->error . "</p>";
    }
}

echo "<h3>Step 3: Insert Essential WordPress Data</h3>";

$site_url = 'https://' . $tenant->subdomain;

// Essential options
$options = array(
    array('siteurl', $site_url),
    array('home', $site_url),
    array('blogname', $tenant->account_name),
    array('blogdescription', 'A NovaRax Tenant Dashboard'),
    array('users_can_register', '0'),
    array('admin_email', $tenant->billing_email),
    array('start_of_week', '1'),
    array('use_balanceTags', '0'),
    array('use_smilies', '1'),
    array('require_name_email', '1'),
    array('comments_notify', '1'),
    array('posts_per_rss', '10'),
    array('rss_use_excerpt', '0'),
    array('mailserver_url', 'mail.example.com'),
    array('mailserver_login', 'login@example.com'),
    array('mailserver_pass', 'password'),
    array('mailserver_port', '110'),
    array('default_category', '1'),
    array('default_comment_status', 'open'),
    array('default_ping_status', 'open'),
    array('default_pingback_flag', '0'),
    array('posts_per_page', '10'),
    array('date_format', 'F j, Y'),
    array('time_format', 'g:i a'),
    array('links_updated_date_format', 'F j, Y g:i a'),
    array('comment_moderation', '0'),
    array('moderation_notify', '1'),
    array('permalink_structure', '/%postname%/'),
    array('active_plugins', 'a:0:{}'),
    array('template', 'flavor'),
    array('stylesheet', 'flavor'),
    array('novarax_tenant_id', $tenant->id),
    array('novarax_master_url', 'https://app.novarax.ae'),
);

foreach ($options as $opt) {
    $name = $mysqli->real_escape_string($opt[0]);
    $value = $mysqli->real_escape_string($opt[1]);
    
    $sql = "INSERT INTO wp_options (option_name, option_value, autoload) 
            VALUES ('{$name}', '{$value}', 'yes') 
            ON DUPLICATE KEY UPDATE option_value = '{$value}'";
    
    if ($mysqli->query($sql)) {
        echo "<p class='info'>Option '{$name}' set</p>";
    } else {
        echo "<p class='error'>Failed to set '{$name}': " . $mysqli->error . "</p>";
    }
}

// Generate and store API key
echo "<h3>Step 4: Generate API Key</h3>";

if (class_exists('NovaRax_API_Authentication')) {
    $auth = new NovaRax_API_Authentication();
    $api_result = $auth->generate_api_key($tenant->id);
    
    if ($api_result['success']) {
        $api_key = $mysqli->real_escape_string($api_result['api_key']);
        $mysqli->query("INSERT INTO wp_options (option_name, option_value, autoload) 
                        VALUES ('novarax_api_key', '{$api_key}', 'yes') 
                        ON DUPLICATE KEY UPDATE option_value = '{$api_key}'");
        echo "<p class='success'>API key generated and stored: {$api_result['api_key']}</p>";
    }
}

// Insert default category
echo "<h3>Step 5: Create Default Category</h3>";
$mysqli->query("INSERT IGNORE INTO wp_terms (term_id, name, slug, term_group) VALUES (1, 'Uncategorized', 'uncategorized', 0)");
$mysqli->query("INSERT IGNORE INTO wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (1, 1, 'category', '', 0, 0)");
echo "<p class='success'>Default category created</p>";

$mysqli->close();

echo "<h2 class='success'>Database Fix Complete!</h2>";