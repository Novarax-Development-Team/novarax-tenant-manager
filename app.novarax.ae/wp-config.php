<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'nova_app' );

/** Database username */
define( 'DB_USER', 'nova_app147' );

/** Database password */
define( 'DB_PASSWORD', 'NsZa@0NPj5O0RKn*' );

/** Database hostname */
define( 'DB_HOST', 'localhost:3306' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'Z_h8kq3EK_X50k!14y271ea(0*8iq6O9%6*@!:QflP6Z9X4fw5[DJ]gr7A]%Y/s9');
define('SECURE_AUTH_KEY', '@32c@Oh@p09D*b1h26M9D(o5v8tsW5d+RPk);n7q_ff093Ws~Ecib@56-fV8:Vku');
define('LOGGED_IN_KEY', '&kra6Q(a2616eO6+eg2Z4h!@cDd]KtD0m9o9nI|M&:PWux2Qvmfy26mNV4NTc8HP');
define('NONCE_KEY', 'mQ7MXRJb/82(1mIk%C2v%yE)Kr4qNVicD5|X&j138ZgS_2Fja~27#JExA#*%]!-#');
define('AUTH_SALT', '~Sw~k:6!x]9Q37/]RrQw9nWkST&cIITg+M/A5(99mYq0DGS(R|7a*89~80L27@5g');
define('SECURE_AUTH_SALT', '2dnGbv:&jJf%tD~3tlU]FjSq6vJ3|FZ88!NnqW4czJr)1HH(&;8e9WLLyf7|+NQZ');
define('LOGGED_IN_SALT', '80u]B*;G/04nE&|0s2TpyA]1WZ5%H5UH0I(HUz*ty9wc6oB/02zta8|UL&7&zv-a');
define('NONCE_SALT', 'V3k2OCe7(2z%J]d9V)4RUTSVavYw(F)E&E@_/FGI%]9]YZ2-8I/xqBfjubBx[&2i');


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'RAX147_';


/* Add any custom values between this line and the "stop editing" line. */

define('WP_ALLOW_MULTISITE', true);
/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}



/**
 * NovaRax Tenant Manager Configuration
 */

// Database root credentials for creating tenant databases
define('DB_ROOT_USER', 'root');
define('DB_ROOT_PASSWORD', 'U2z!8kPq#T7jL4rW');

// Tenant configuration
define('NOVARAX_TENANT_CODEBASE_PATH', '/var/www/vhosts/novarax.ae/tenant-dashboard');
define('NOVARAX_SUBDOMAIN_SUFFIX', '.app.novarax.ae');

// Encryption key (generate unique key)
define('NOVARAX_ENCRYPTION_KEY', 'xGf8N5yLqT4wP2jA0kH3zV7mB1eR6sC9');

// API configuration
define('NOVARAX_API_ENABLED', true);
define('NOVARAX_API_RATE_LIMIT', 1000); // Requests per hour

// Debug mode (set to false in production)
define('NOVARAX_DEBUG', true);


/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
