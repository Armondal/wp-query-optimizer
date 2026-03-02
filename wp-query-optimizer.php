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

add_action( 'admin_init', 'wqo_register_settings' );

function wqo_register_settings() {
    // Register a setting so WordPress knows it's safe to save
    register_setting( 'wqo_settings_group', 'wqo_options_to_cache' );
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
                // Output security nonces for the registered setting
                settings_fields( 'wqo_settings_group' ); 
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Options to Cache<br><small>(One option name per line)</small></th>
                    <td>
                        <textarea name="wqo_options_to_cache" rows="10" cols="50" class="large-text code"><?php echo esc_textarea( get_option( 'wqo_options_to_cache' ) ); ?></textarea>
                        <p class="description">Enter the exact option_name from the database that you want to pre-load (e.g., houzez_gallery_w).</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Save Optimizer Settings' ); ?>
        </form>
    </div>
    <?php
}

