<?php
/**
 * Plugin Name: Novarax Dashboard Optimizer
 * Plugin URI: https://novarax.com
 * Description: Disables unnecessary WordPress features for a lightweight, fast dashboard application
 * Version: 1.0.0
 * Author: Novarax
 * Author URI: https://novarax.com
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Novarax_Dashboard_Optimizer {
    
    public function __construct() {
        $this->init_optimizations();
    }
    
    private function init_optimizations() {
        // SECURITY & EXTERNAL COMMUNICATION
        add_filter('xmlrpc_enabled', '__return_false'); // Disable XML-RPC (prevents brute force attacks)
        add_filter('wp_headers', [$this, 'remove_x_pingback']); // Remove X-Pingback header
        // add_action('init', [$this, 'disable_embeds']); // Disable oEmbed feature
        
        // CONTENT FEATURES (typically not needed for dashboards)
         add_filter('use_block_editor_for_post', '__return_false'); // Disable Gutenberg editor
        // add_filter('use_block_editor_for_post_type', '__return_false'); // Disable for all post types
        // add_action('wp_enqueue_scripts', [$this, 'disable_emoji']); // Remove emoji scripts
        // add_action('admin_enqueue_scripts', [$this, 'disable_emoji']); // Remove emoji from admin too
        
        // DATABASE OPTIMIZATION
         define('WP_POST_REVISIONS', 3); // Limit post revisions (or use false to disable)
         define('AUTOSAVE_INTERVAL', 300); // Autosave every 5 minutes instead of 1
         define('EMPTY_TRASH_DAYS', 7); // Empty trash after 7 days instead of 30
        
        // PERFORMANCE - HEARTBEAT API
        // add_action('init', [$this, 'modify_heartbeat']); // Reduce heartbeat frequency
        
        // ADMIN INTERFACE CLEANUP
        // add_action('admin_menu', [$this, 'remove_admin_menus'], 999); // Remove unused admin menus
        // add_action('wp_dashboard_setup', [$this, 'remove_dashboard_widgets']); // Clean dashboard
        // add_action('admin_bar_menu', [$this, 'remove_admin_bar_items'], 999); // Clean admin bar
        
        // SCRIPT & STYLE OPTIMIZATION
         add_action('wp_enqueue_scripts', [$this, 'dequeue_frontend_scripts'], 100); // Remove frontend bloat
         add_action('admin_enqueue_scripts', [$this, 'dequeue_admin_scripts'], 100); // Remove admin bloat
        
        // REST API OPTIMIZATION
        // add_filter('rest_endpoints', [$this, 'disable_unused_rest_endpoints']); // Disable unused REST routes
        
        // PINGBACKS & TRACKBACKS
         add_action('pre_ping', [$this, 'disable_pingback']); // Disable self-pingbacks
         add_filter('wp_headers', [$this, 'remove_x_pingback']); // Remove pingback header
        
        // FEEDS (typically not needed for dashboards)
         add_action('do_feed', [$this, 'disable_feeds'], 1);
         add_action('do_feed_rdf', [$this, 'disable_feeds'], 1);
         add_action('do_feed_rss', [$this, 'disable_feeds'], 1);
         add_action('do_feed_rss2', [$this, 'disable_feeds'], 1);
         add_action('do_feed_atom', [$this, 'disable_feeds'], 1);
        
        // THEME FEATURES (disable if not using front-end)
        // add_action('after_setup_theme', [$this, 'disable_theme_features']);
        
        // CRON OPTIMIZATION
        // define('DISABLE_WP_CRON', true); // Disable WP-Cron, use system cron instead
        
        // UPDATE CHECKS (if managing updates manually)
        // add_filter('pre_site_transient_update_core', '__return_null'); // Disable core update checks
        // add_filter('pre_site_transient_update_plugins', '__return_null'); // Disable plugin update checks
        // add_filter('pre_site_transient_update_themes', '__return_null'); // Disable theme update checks
    }
    
    // SECURITY & EXTERNAL COMMUNICATION METHODS
    public function remove_x_pingback($headers) {
        unset($headers['X-Pingback']);
        return $headers;
    }
    
    public function disable_embeds() {
        // Remove embed script
        wp_deregister_script('wp-embed');
        
        // Remove oEmbed discovery links
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        
        // Remove oEmbed-specific JavaScript
        remove_action('wp_head', 'wp_oembed_add_host_js');
        
        // Remove REST API endpoint for oEmbed
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
        
        // Turn off oEmbed auto discovery
        add_filter('embed_oembed_discover', '__return_false');
    }
    
    // EMOJI REMOVAL
    public function disable_emoji() {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        
        // Remove from TinyMCE
        add_filter('tiny_mce_plugins', [$this, 'disable_emojis_tinymce']);
        add_filter('wp_resource_hints', [$this, 'disable_emojis_dns_prefetch'], 10, 2);
    }
    
    public function disable_emojis_tinymce($plugins) {
        if (is_array($plugins)) {
            return array_diff($plugins, ['wpemoji']);
        }
        return [];
    }
    
    public function disable_emojis_dns_prefetch($urls, $relation_type) {
        if ('dns-prefetch' == $relation_type) {
            $emoji_svg_url = apply_filters('emoji_svg_url', 'https://s.w.org/images/core/emoji/');
            $urls = array_diff($urls, [$emoji_svg_url]);
        }
        return $urls;
    }
    
    // HEARTBEAT MODIFICATION
    public function modify_heartbeat() {
        // Slow down heartbeat to every 60 seconds (default is 15)
        add_filter('heartbeat_settings', function($settings) {
            $settings['interval'] = 60;
            return $settings;
        });
        
        // Or completely disable on frontend
        // wp_deregister_script('heartbeat');
    }
    
    // ADMIN MENU CLEANUP
    public function remove_admin_menus() {
        // Remove unnecessary admin menus
        // remove_menu_page('edit.php'); // Posts
        // remove_menu_page('upload.php'); // Media
        // remove_menu_page('edit.php?post_type=page'); // Pages
        // remove_menu_page('edit-comments.php'); // Comments
        // remove_menu_page('themes.php'); // Appearance
        // remove_menu_page('plugins.php'); // Plugins (be careful!)
        // remove_menu_page('tools.php'); // Tools
        // remove_menu_page('options-general.php'); // Settings (be careful!)
    }
    
    // DASHBOARD WIDGETS CLEANUP
    public function remove_dashboard_widgets() {
        // Remove default dashboard widgets
        remove_meta_box('dashboard_primary', 'dashboard', 'side'); // WordPress Events and News
        remove_meta_box('dashboard_quick_press', 'dashboard', 'side'); // Quick Draft
        remove_meta_box('dashboard_activity', 'dashboard', 'normal'); // Activity
        remove_meta_box('dashboard_right_now', 'dashboard', 'normal'); // At a Glance
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal'); // Recent Comments
        remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal'); // Incoming Links
        remove_meta_box('dashboard_plugins', 'dashboard', 'normal'); // Plugins
        remove_meta_box('dashboard_site_health', 'dashboard', 'normal'); // Site Health
    }
    
    // ADMIN BAR CLEANUP
    public function remove_admin_bar_items($wp_admin_bar) {
        // Remove WordPress logo and related links
        $wp_admin_bar->remove_node('wp-logo');
        
        // Remove other items as needed
        // $wp_admin_bar->remove_node('updates'); // Update notifications
        // $wp_admin_bar->remove_node('comments'); // Comments
        // $wp_admin_bar->remove_node('new-content'); // New content dropdown
        // $wp_admin_bar->remove_node('search'); // Search
    }
    
    // SCRIPT DEQUEUE (FRONTEND)
    public function dequeue_frontend_scripts() {
        // Remove jQuery Migrate (if not needed)
        // wp_deregister_script('jquery-migrate');
        
        // Remove block library CSS (if not using Gutenberg blocks)
         wp_dequeue_style('wp-block-library');
         wp_dequeue_style('wp-block-library-theme');
        // wp_dequeue_style('wc-blocks-style'); // WooCommerce blocks
        
        // Remove global styles (Gutenberg)
        // wp_dequeue_style('global-styles');
        
        // Remove classic theme styles
        // wp_dequeue_style('classic-theme-styles');
    }
    
    // SCRIPT DEQUEUE (ADMIN)
    public function dequeue_admin_scripts() {
        // Remove Gutenberg block editor assets if not using
        // wp_dequeue_style('wp-block-editor');
        // wp_dequeue_style('wp-editor');
        // wp_dequeue_script('wp-block-editor');
    }
    
    // REST API OPTIMIZATION
    public function disable_unused_rest_endpoints($endpoints) {
        // Example: Remove unused endpoints
        // Keep only what your dashboard modules need
        
        // Remove user endpoints (if not needed)
        // if (isset($endpoints['/wp/v2/users'])) {
        //     unset($endpoints['/wp/v2/users']);
        // }
        // if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
        //     unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
        // }
        
        // Remove post type endpoints you don't use
        // unset($endpoints['/wp/v2/posts']);
        // unset($endpoints['/wp/v2/pages']);
        // unset($endpoints['/wp/v2/media']);
         unset($endpoints['/wp/v2/comments']);
        
        return $endpoints;
    }
    
    // PINGBACK DISABLE
    public function disable_pingback(&$links) {
        foreach ($links as $l => $link) {
            if (strpos($link, get_option('home')) === 0) {
                unset($links[$l]);
            }
        }
    }
    
    // FEEDS DISABLE
    public function disable_feeds() {
        wp_die(__('No feed available, please visit the <a href="' . esc_url(home_url('/')) . '">homepage</a>!'));
    }
    
    // THEME FEATURES DISABLE
    public function disable_theme_features() {
        // Remove theme support for features you don't need
        // remove_theme_support('custom-header');
        // remove_theme_support('custom-background');
        // remove_theme_support('widgets');
        // remove_theme_support('menus');
        // remove_theme_support('post-thumbnails');
    }
}

// Initialize the optimizer
new Novarax_Dashboard_Optimizer();

// ADDITIONAL wp-config.php CONSTANTS (add these to your wp-config.php file)
/*
// Memory limits
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');

// Disable file editing from admin
define('DISALLOW_FILE_EDIT', true);

// Post revisions
define('WP_POST_REVISIONS', 3); // or false to disable

// Autosave interval (in seconds)
define('AUTOSAVE_INTERVAL', 300);

// Trash auto-empty (in days)
define('EMPTY_TRASH_DAYS', 7);

// Disable WP Cron (use system cron instead)
define('DISABLE_WP_CRON', true);

// Concatenate scripts (experimental)
define('CONCATENATE_SCRIPTS', false);

// Disable debug (in production)
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
*/