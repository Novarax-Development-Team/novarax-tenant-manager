<?php
/**
 * Plugin Name: Remove Count Parentheses
 */

add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script(
        'remove-count-parentheses',
        content_url('/mu-plugins/novarax-tenant-manager/assets/js/remove-count-parentheses.js'),
        [],
        null,
        true
    );
});
