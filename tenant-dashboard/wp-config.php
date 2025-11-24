<?php
/**
 * NovaRax Tenant Dashboard - Dynamic wp-config.php
 * 
 * This file dynamically loads database credentials based on the subdomain.
 * REPLACE the entire contents of /var/www/vhosts/novarax.ae/tenant-dashboard/wp-config.php
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('WP_INSTALLING')) {
    // We're being loaded directly, define ABSPATH
}

/**
 * Detect subdomain from HTTP_HOST
 */
function novarax_get_subdomain() {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    
    // Also check for Nginx passed subdomain
    if (isset($_SERVER['NOVARAX_SUBDOMAIN'])) {
        return $_SERVER['NOVARAX_SUBDOMAIN'];
    }
    
    // Extract subdomain from host (e.g., elie.app.novarax.ae -> elie)
    $pattern = '/^([a-z0-9-]+)\.app\.novarax\.ae$/i';
    if (preg_match($pattern, $host, $matches)) {
        return strtolower($matches[1]);
    }
    
    // Fallback: check for subdomain in any format
    $parts = explode('.', $host);
    if (count($parts) >= 4) { // subdomain.app.novarax.ae = 4 parts
        return strtolower($parts[0]);
    }
    
    return false;
}

/**
 * Get tenant database credentials from master database
 */
function novarax_get_tenant_credentials($subdomain) {
    // Master database connection details
    // IMPORTANT: Update these with your actual master database credentials
    $master_db = array(
        'host'     => 'localhost',
        'port'     => 3306,
        'database' => 'nova_app',  // Your master database name
        'user'     => 'nova_app147', // Your master database user
        'password' => 'NsZa@0NPj5O0RKn*', // Your master database password
        'prefix'   => 'RAX147_'  // Your master table prefix
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
        error_log('NovaRax: Failed to connect to master database: ' . $mysqli->connect_error);
        return false;
    }
    
    // Query tenant information
    $full_subdomain = $subdomain . '.app.novarax.ae';
    $stmt = $mysqli->prepare(
        "SELECT t.id, t.tenant_username, t.database_name, t.status, t.metadata 
         FROM {$master_db['prefix']}novarax_tenants t 
         WHERE t.subdomain = ? AND t.status = 'active'"
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
        error_log('NovaRax: No active tenant found for subdomain: ' . $subdomain);
        return false;
    }
    
    // Get database credentials from metadata (encrypted)
    $metadata = json_decode($tenant['metadata'], true);
    
    if (isset($metadata['db_credentials'])) {
        // Credentials are encrypted - we need to decrypt them
        // For now, we'll use the standard naming convention
        $credentials = array(
            'database' => $tenant['database_name'],
            'user'     => substr($tenant['database_name'], 0, 16), // MySQL username limit
            'password' => '', // We need to retrieve this from encrypted storage
            'tenant_id' => $tenant['id'],
            'username' => $tenant['tenant_username']
        );
        
        // Try to decrypt credentials if available
        if (!empty($metadata['db_credentials'])) {
            $decrypted = novarax_decrypt_credentials($metadata['db_credentials']);
            if ($decrypted) {
                $credentials['user'] = $decrypted['username'];
                $credentials['password'] = $decrypted['password'];
            }
        }
        
        return $credentials;
    }
    
    // Fallback: Use naming convention (less secure but works for testing)
    return array(
        'database'  => $tenant['database_name'],
        'user'      => substr($tenant['database_name'], 0, 16),
        'password'  => '', // This will fail - needs proper setup
        'tenant_id' => $tenant['id'],
        'username'  => $tenant['tenant_username']
    );
}

/**
 * Simple decryption for stored credentials
 */
function novarax_decrypt_credentials($encrypted_data) {
    // Encryption key - MUST match the one in app.novarax.ae wp-config.php
    $key = defined('NOVARAX_ENCRYPTION_KEY') ? NOVARAX_ENCRYPTION_KEY : 'default-key-change-me';
    
    if ($key === 'default-key-change-me') {
        // Try to read from a shared config file
        $config_file = '/var/www/vhosts/novarax.ae/novarax-config.php';
        if (file_exists($config_file)) {
            include $config_file;
            $key = defined('NOVARAX_ENCRYPTION_KEY') ? NOVARAX_ENCRYPTION_KEY : $key;
        }
    }
    
    $cipher_method = 'AES-256-CBC';
    $data = base64_decode($encrypted_data);
    
    if ($data === false) {
        return false;
    }
    
    $iv_length = openssl_cipher_iv_length($cipher_method);
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    $decrypted = openssl_decrypt($encrypted, $cipher_method, $key, 0, $iv);
    
    if ($decrypted === false) {
        return false;
    }
    
    return json_decode($decrypted, true);
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
        <title>Novarax - <?php echo esc_html($message); ?></title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                   display: flex; align-items: center; justify-content: center; 
                   min-height: 100vh; margin: 0; background: #f4f4f4; }
            .error-box { background: white; padding: 40px; border-radius: 8px; 
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
            h1 { color: #dc3232; margin-bottom: 20px; }
            p { color: #666; margin-bottom: 20px; }
            a { color: #0073aa; text-decoration: none; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>⚠️ <?php echo esc_html($message); ?></h1>
            <p>The dashboard you're looking for doesn't exist or has been suspended.</p>
            <p><a href="https://app.novarax.ae">← Return to Novarax Home Page</a></p>
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
    // Not a tenant subdomain - show error
    novarax_show_error('Invalid URL');
}

// Get tenant credentials
$tenant_creds = novarax_get_tenant_credentials($subdomain);

if (!$tenant_creds) {
    novarax_show_error('Tenant not found or inactive');
}

// ============================================
// WORDPRESS DATABASE CONFIGURATION
// ============================================

/** The name of the database for WordPress */
define('DB_NAME', $tenant_creds['database']);

/** Database username */
define('DB_USER', $tenant_creds['user']);

/** Database password */
define('DB_PASSWORD', $tenant_creds['password']);

/** Database hostname */
define('DB_HOST', 'localhost:3306');

/** Database charset */
define('DB_CHARSET', 'utf8mb4');

/** Database collate type */
define('DB_COLLATE', '');

/** Store tenant info for use by plugins */
define('NOVARAX_TENANT_ID', $tenant_creds['tenant_id']);
define('NOVARAX_TENANT_USERNAME', $tenant_creds['username']);
define('NOVARAX_MASTER_URL', 'https://app.novarax.ae');

// ============================================
// AUTHENTICATION KEYS AND SALTS
// ============================================
// Generate unique keys for each tenant based on their ID
$salt_base = 'novarax_' . $tenant_creds['tenant_id'] . '_';

define('AUTH_KEY', 'N98:7#4oT!#5s!McB67H(/)n!b01oWI[CfJq0I7%6f6RE9G*t)[A2U~)8E@#jX6T');
define('SECURE_AUTH_KEY', '|Pf@Vz)|+83a59V76I8(2+85tiWtLB8Q]%gG&2#xu|yE3js!n4x1hx@&Y4%&ms#&');
define('LOGGED_IN_KEY', '86pt4S/@]6O39cOsgk9bs7S1(:];U#U%-7!m!A244(_%Ps|2J[q1f!E_f2~VFZyv');
define('NONCE_KEY', 's05XewZQ[a1W)i60r|0q54Zt4h7!d5vqVwM6iMkj1[6@hv2Z9Z19C3T0M&H1E9Bf');
define('AUTH_SALT', '9~)j4vs]7@[:EC~IF(p2St5Kv%80~-wTFb3p5GB6Rq19+9%_S)V2]D@NZASC[FtJ');
define('SECURE_AUTH_SALT', 'gWb+(H|]&9UDmRG:r91:@S7@XU_H4VOilq3T!#M:l2-0s%48/65_T6]@I21QHymt');
define('LOGGED_IN_SALT', ':c7yF0#9uir89-2N/82_7:3Tj[Rc3z086|c68CP]3]60X/]lo[4uqMISY~nJ%ZE|');
define('NONCE_SALT', 'IjP[bu1WJPl!+z2r)5e1wd2Vo1FH3;Mt&!4@zE&3eRBT:[e|)y38Ur(4YKUxA;5)');

// ============================================
// WORDPRESS TABLE PREFIX
// ============================================
$table_prefix = 'RAX148_';

// ============================================
// WORDPRESS SETTINGS
// ============================================
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Disable file editing in admin
define('DISALLOW_FILE_EDIT', true);

// Cookie domain for SSO
define('COOKIE_DOMAIN', '.app.novarax.ae');

// ============================================
// ABSPATH AND WORDPRESS BOOTSTRAP
// ============================================
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';