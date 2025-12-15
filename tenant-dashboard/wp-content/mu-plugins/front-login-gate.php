<?php
/**
 * Plugin Name: Force Sitewide Login Redirect
 * Description: Redirect all non-logged-in visitors to /wp-login (site-wide), with safe exceptions (admin, AJAX, REST, feeds, login pages).
 * Author: Novarax
 * Version: 1.0.1
 */

if (!defined('ABSPATH')) exit;

/**
 * Build the full current URL (for future use if needed).
 */
function fs_current_url_full(): string {
    $scheme = (is_ssl() ? 'https' : 'http');
    $host   = $_SERVER['HTTP_HOST'] ?? '';
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    return esc_url_raw("{$scheme}://{$host}{$uri}");
}

add_action('template_redirect', function () {
    // ---- Exceptions: contexts we must not intercept ----
    if ( is_admin() ) return;             // WP admin (/wp-admin)
    if ( wp_doing_ajax() ) return;        // admin-ajax.php
    if ( wp_doing_cron() ) return;        // cron
    if ( is_feed() ) return;              // RSS/Atom feeds

    $uri  = $_SERVER['REQUEST_URI'] ?? '';
    $path = rtrim(parse_url($uri, PHP_URL_PATH) ?? '/', '/');

    // REST API & xmlrpc
    if ( strpos($path, '/wp-json/') === 0 ) return;
    if ( $path === '/xmlrpc.php' ) return;

    // Allow WordPress native login / lost password / register / logout endpoints
    if ( $path === '/wp-login.php' ) return;

    // If already logged in, do nothing
    if ( is_user_logged_in() ) return;

    // ---- Enforce redirect site-wide for guests ----
    if ( ! defined('DONOTCACHEPAGE') ) {
        define('DONOTCACHEPAGE', true);
    }
    nocache_headers();

    // Simple: just send them to the login page, NO redirect_to param
    $login_url = wp_login_url(); // this returns /wp-login.php with no query string by default

    wp_safe_redirect($login_url, 302);
    exit;
}, 0);
