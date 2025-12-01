<?php
/**
 * NovaRax Tenant Dashboard - Dynamic wp-config.php
 * 
 * Save as: /var/www/vhosts/novarax.ae/tenant-dashboard/wp-config.php
 */

/**
 * Detect subdomain from HTTP_HOST
 */
function novarax_get_subdomain() {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    
    // Extract subdomain from host (e.g., elie.app.novarax.ae -> elie)
    $pattern = '/^([a-z0-9-]+)\.app\.novarax\.ae$/i';
    if (preg_match($pattern, $host, $matches)) {
        return strtolower($matches[1]);
    }
    
    // Fallback: get first part of domain
    $parts = explode('.', $host);
    if (count($parts) >= 3) {
        return strtolower($parts[0]);
    }
    
    return false;
}

/**
 * Get tenant database credentials from master database
 */
function novarax_get_tenant_credentials($subdomain) {
    // Master database connection details
    $master_db = array(
        'host'     => 'localhost',
        'port'     => 3306,
        'database' => 'nova_app',      // Your master database
        'user'     => 'nova_app147',   // Your master DB user
        'password' => 'NsZa@0NPj5O0RKn*', // Your master DB password
        'prefix'   => 'RAX147_'        // Your master table prefix
    );
    
    // Connect to master database
    $mysqli = @new mysqli(
        $master_db['host'], 
        $master_db['user'], 
        $master_db['password'], 
        $master_db['database'],
        $master_db['port']
    );
    
    if ($mysqli->connect_error) {
        error_log('NovaRax: Failed to connect to master: ' . $mysqli->connect_error);
        return false;
    }
    
    // Build full subdomain
    $full_subdomain = $subdomain . '.app.novarax.ae';
    
    // Query tenant by subdomain
    $stmt = $mysqli->prepare(
        "SELECT id, tenant_username, database_name, status, metadata 
         FROM {$master_db['prefix']}novarax_tenants 
         WHERE subdomain = ? AND status = 'active' LIMIT 1"
    );
    
    if (!$stmt) {
        error_log('NovaRax: Failed to prepare statement: ' . $mysqli->error);
        $mysqli->close();
        return false;
    }
    
    $stmt->bind_param('s', $full_subdomain);
    $stmt->execute();
    $result = $stmt->get_result();
    $tenant = $result->fetch_assoc();
    
    $stmt->close();
    $mysqli->close();
    
    if (!$tenant) {
        error_log('NovaRax: No active tenant found for: ' . $full_subdomain);
        return false;
    }
    
    // Parse metadata to get database credentials
    $metadata = !empty($tenant['metadata']) ? json_decode($tenant['metadata'], true) : array();
    
    // Get database username (usually same as database name, truncated to 16 chars)
    $db_username = isset($metadata['db_username']) ? $metadata['db_username'] : substr($tenant['database_name'], 0, 16);
    
    // Get database password from metadata
    $db_password = isset($metadata['db_password']) ? $metadata['db_password'] : '';
    
    if (empty($db_password)) {
        error_log('NovaRax: No password found in metadata for tenant: ' . $tenant['id']);
        // This will cause database connection error - we need the password!
        return false;
    }
    
    return array(
        'database'  => $tenant['database_name'],
        'user'      => $db_username,
        'password'  => $db_password,
        'tenant_id' => $tenant['id'],
        'username'  => $tenant['tenant_username']
    );
}

/**
 * Show error page for invalid tenants
 */
function novarax_show_error($message = 'Tenant not found') {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>NovaRax - <?php echo htmlspecialchars($message); ?></title>
        <style>
            body { 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                min-height: 100vh; 
                margin: 0; 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .error-box { 
                background: white; 
                padding: 40px; 
                border-radius: 12px; 
                box-shadow: 0 10px 40px rgba(0,0,0,0.2); 
                text-align: center; 
                max-width: 400px; 
            }
            h1 { color: #dc3232; margin: 0 0 20px 0; font-size: 24px; }
            p { color: #666; margin: 0 0 20px 0; line-height: 1.6; }
            a { 
                display: inline-block;
                padding: 12px 24px;
                background: #0073aa; 
                color: white; 
                text-decoration: none; 
                border-radius: 6px;
                transition: background 0.3s;
            }
            a:hover { background: #005177; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>⚠️ <?php echo htmlspecialchars($message); ?></h1>
            <p>The dashboard you're looking for doesn't exist or has been suspended.</p>
            <a href="https://app.novarax.ae">← Return to NovaRax</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================
// MAIN EXECUTION
// ============================================

// Detect subdomain
$subdomain = novarax_get_subdomain();

if (!$subdomain) {
    novarax_show_error('Invalid URL');
}

// Get tenant credentials from master database
$tenant_creds = novarax_get_tenant_credentials($subdomain);

if (!$tenant_creds) {
    novarax_show_error('Tenant not found or inactive');
}

// Check if password is actually set
if (empty($tenant_creds['password'])) {
    novarax_show_error('Database credentials not configured');
}

// ============================================
// WORDPRESS DATABASE CONFIGURATION
// ============================================

define('DB_NAME', $tenant_creds['database']);
define('DB_USER', $tenant_creds['user']);
define('DB_PASSWORD', $tenant_creds['password']);
define('DB_HOST', 'localhost:3306');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// Store tenant info for plugins
define('NOVARAX_TENANT_ID', $tenant_creds['tenant_id']);
define('NOVARAX_TENANT_USERNAME', $tenant_creds['username']);
define('NOVARAX_MASTER_URL', 'https://app.novarax.ae');
define('NOVARAX_TENANT_MODE', true);

// ============================================
// AUTHENTICATION KEYS AND SALTS
// ============================================
// IMPORTANT: These MUST match your master wp-config.php for SSO to work!
// Copy these from: /var/www/vhosts/novarax.ae/app.novarax.ae/wp-config.php

define('AUTH_KEY',         'N98:7#4oT!#5s!McB67H(/)n!b01oWI[CfJq0I7%6f6RE9G*t)[A2U~)8E@#jX6T');
define('SECURE_AUTH_KEY',  '|Pf@Vz)|+83a59V76I8(2+85tiWtLB8Q]%gG&2#xu|yE3js!n4x1hx@&Y4%&ms#&');
define('LOGGED_IN_KEY',    '86pt4S/@]6O39cOsgk9bs7S1(:];U#U%-7!m!A244(_%Ps|2J[q1f!E_f2~VFZyv');
define('NONCE_KEY',        's05XewZQ[a1W)i60r|0q54Zt4h7!d5vqVwM6iMkj1[6@hv2Z9Z19C3T0M&H1E9Bf');
define('AUTH_SALT',        '9~)j4vs]7@[:EC~IF(p2St5Kv%80~-wTFb3p5GB6Rq19+9%_S)V2]D@NZASC[FtJ');
define('SECURE_AUTH_SALT', 'gWb+(H|]&9UDmRG:r91:@S7@XU_H4VOilq3T!#M:l2-0s%48/65_T6]@I21QHymt');
define('LOGGED_IN_SALT',   ':c7yF0#9uir89-2N/82_7:3Tj[Rc3z086|c68CP]3]60X/]lo[4uqMISY~nJ%ZE|');
define('NONCE_SALT',       'IjP[bu1WJPl!+z2r)5e1wd2Vo1FH3;Mt&!4@zE&3eRBT:[e|)y38Ur(4YKUxA;5)');

// ============================================
// WORDPRESS TABLE PREFIX
// ============================================
// Use same prefix as tenant database tables (default: wp_)
$table_prefix = 'wp_';

// ============================================
// WORDPRESS SETTINGS
// ============================================
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', false);

// Disable file editing in admin for security
define('DISALLOW_FILE_EDIT', true);

// Disable WordPress cron (we use system cron)
define('DISABLE_WP_CRON', true);

// Cookie settings for SSO across subdomains
define('COOKIE_DOMAIN', '.app.novarax.ae');
define('COOKIEPATH', '/');
define('SITECOOKIEPATH', '/');
define('ADMIN_COOKIE_PATH', '/');

// Memory limits
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

// ============================================
// ABSPATH
// ============================================
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';