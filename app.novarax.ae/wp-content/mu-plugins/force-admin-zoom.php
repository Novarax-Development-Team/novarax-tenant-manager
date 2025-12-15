<?php
/**
 * Force wp-admin content zoom (80%) on all dashboard pages
 */
add_action('admin_head', function () {
    ?>
    <style>
        /* ✅ Scale only the main admin content */
        #wpbody{
            zoom: 0.8; /* Chrome / Edge */
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        /* Firefox fallback */
        @supports not (zoom: 1) {
            #wpbody{
                transform: scale(0.8);
                transform-origin: top left;
                width: 125%; /* 1 / 0.8 */
            }
        }

        /* Prevent horizontal scroll */
        #wpcontent{
            overflow-x: hidden;
        }
    </style>
    <?php
});
