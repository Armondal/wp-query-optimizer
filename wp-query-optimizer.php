<?php 
/*
Plugin Name: WP Query Optimizer
Plugin URI: #
Description: Caches specific database queries and options to reduce N+1 loads.
Version: 1.0.0
Author: Dev Team
Text Domain: wp-query-optimizer
*/

// Exit if accessed directly for security
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Hook into the admin menu
add_action( 'admin_menu', 'wqo_add_settings_page' );

function wqo_add_settings_page() {
    // Add a sub-menu under the main "Settings" tab
    add_options_page(
        'WP Query Optimizer Settings', // Page title (browser tab)
        'Query Optimizer',             // Menu title (sidebar link)
        'manage_options',              // Capability required (admin only)
        'wp-query-optimizer',          // Menu slug (URL)
        'wqo_render_settings_page'     // Function to draw the HTML page
    );
}

// 2. Draw the HTML settings page
function wqo_render_settings_page() {
    // Security check
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>WP Query Optimizer 🛠️</h1>
        <p>Configure which database queries and options should be cached.</p>
        
        <form method="post" action="options.php">
            <?php 
                // WordPress security and settings registration will go here
            ?>
        </form>
    </div>
    <?php
}