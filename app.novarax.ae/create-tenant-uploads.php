<?php
/**
 * Create Tenant Uploads Directories
 * 
 * Place at: /var/www/app.novarax.ae/create-tenant-uploads.php
 * Run: https://app.novarax.ae/create-tenant-uploads.php
 * 
 * This creates uploads directories for all tenants and sets proper permissions
 */

require_once __DIR__ . '/wp-load.php';

if (!current_user_can('manage_options')) {
    die('Access denied. Admin access required.');
}

echo "<style>
body { font-family: monospace; padding: 20px; background: #f5f5f5; }
.success { color: #46b450; font-weight: bold; }
.error { color: #dc3232; font-weight: bold; }
.info { color: #0073aa; }
.warning { color: #f0b849; }
pre { background: #fff; padding: 15px; border-left: 4px solid #0073aa; }
</style>";

echo "<h1>Create Tenant Uploads Directories</h1>";
echo "<p>This script will create uploads directories for all tenants.</p><hr>";

global $wpdb;
$db_manager = new NovaRax_Database_Manager();
$table = $db_manager->get_table_name('tenants');

// Get all active tenants
$tenants = $wpdb->get_results("SELECT id, tenant_username, subdomain FROM {$table} WHERE status = 'active'");

if (empty($tenants)) {
    echo "<p class='error'>No active tenants found.</p>";
    exit;
}

echo "<p class='info'>Found " . count($tenants) . " active tenants</p><br>";

// Base paths to try
$base_paths = array(
    '/var/www/vhosts/novarax.ae/tenant-dashboard/wp-content/uploads',
    '/var/www/tenant-dashboard/wp-content/uploads',
);

// Find which base path exists
$uploads_base = null;
foreach ($base_paths as $path) {
    if (is_dir($path)) {
        $uploads_base = $path;
        echo "<p class='success'>✓ Found uploads directory: {$path}</p>";
        break;
    }
}

if (!$uploads_base) {
    echo "<p class='error'>✗ Could not find tenant uploads base directory!</p>";
    echo "<p>Checked paths:</p><pre>";
    print_r($base_paths);
    echo "</pre>";
    exit;
}

echo "<br><h2>Creating Directories</h2>";

$created = 0;
$exists = 0;
$failed = 0;

foreach ($tenants as $tenant) {
    $username = explode('.', $tenant->subdomain)[0];
    
    echo "<strong>Tenant: {$username}</strong><br>";
    
    // Try both directory structures
    $tenant_paths = array(
        $uploads_base . '/sites/' . $username,
        $uploads_base . '/' . $username,
    );
    
    $created_path = false;
    
    foreach ($tenant_paths as $tenant_path) {
        if (is_dir($tenant_path)) {
            echo "  <span class='warning'>⚠ Already exists: {$tenant_path}</span><br>";
            $exists++;
            $created_path = true;
            
            // Check permissions
            $perms = substr(sprintf('%o', fileperms($tenant_path)), -4);
            echo "  Permissions: {$perms}<br>";
            
            // Make sure it's writable
            if (!is_writable($tenant_path)) {
                echo "  <span class='warning'>⚠ Not writable, fixing...</span><br>";
                chmod($tenant_path, 0755);
                chown($tenant_path, 'www-data');
                chgrp($tenant_path, 'www-data');
                echo "  <span class='success'>✓ Fixed permissions</span><br>";
            }
            
            break;
        }
    }
    
    // If doesn't exist, create it
    if (!$created_path) {
        // Use first path structure (sites/username)
        $tenant_path = $tenant_paths[0];
        
        // Create directory with subdirectories
        if (wp_mkdir_p($tenant_path)) {
            echo "  <span class='success'>✓ Created: {$tenant_path}</span><br>";
            
            // Set proper permissions
            chmod($tenant_path, 0755);
            
            // Try to set owner (might fail without sudo)
            @chown($tenant_path, 'www-data');
            @chgrp($tenant_path, 'www-data');
            
            // Create year/month subdirectories
            $current_year = date('Y');
            $current_month = date('m');
            $year_month_path = $tenant_path . '/' . $current_year . '/' . $current_month;
            
            if (wp_mkdir_p($year_month_path)) {
                chmod($year_month_path, 0755);
                @chown($year_month_path, 'www-data');
                @chgrp($year_month_path, 'www-data');
                echo "  <span class='success'>✓ Created year/month structure</span><br>";
            }
            
            // Create index.php for security
            $index_file = $tenant_path . '/index.php';
            file_put_contents($index_file, '<?php // Silence is golden');
            chmod($index_file, 0644);
            
            // Create .htaccess for media serving
            $htaccess_file = $tenant_path . '/.htaccess';
            $htaccess_content = "# Protect PHP files
<Files *.php>
    Deny from all
</Files>

# Allow images and media
<FilesMatch \"\.(jpg|jpeg|png|gif|svg|webp|pdf|doc|docx|zip)$\">
    Allow from all
</FilesMatch>";
            file_put_contents($htaccess_file, $htaccess_content);
            chmod($htaccess_file, 0644);
            
            $created++;
        } else {
            echo "  <span class='error'>✗ Failed to create: {$tenant_path}</span><br>";
            $failed++;
        }
    }
    
    echo "<br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p class='success'>Created: {$created} directories</p>";
echo "<p class='warning'>Already existed: {$exists} directories</p>";

if ($failed > 0) {
    echo "<p class='error'>Failed: {$failed} directories</p>";
}

echo "<br><h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Run storage calculation again: <a href='/fix-storage-now.php'>Fix Storage Now</a></li>";
echo "<li>Check tenant can upload files by visiting a tenant dashboard</li>";
echo "<li>Set up automatic directory creation for new tenants</li>";
echo "</ol>";

echo "<br><h3>Manual Command (if needed):</h3>";
echo "<pre>sudo chown -R www-data:www-data {$uploads_base}
sudo chmod -R 755 {$uploads_base}</pre>";

echo "<br><a href='/wp-admin/admin.php?page=novarax-tenants'>← Back to Tenants</a>";
?>