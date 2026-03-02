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



// 3. The Core Optimizer Engine (Runs immediately to beat other plugins)
wqo_preload_targeted_options();

function wqo_preload_targeted_options() {
    global $wpdb;

    $raw_text = get_option( 'wqo_options_to_cache' );
    if ( empty( $raw_text ) ) {
        return;
    }

    $options_list = array_filter( array_map( 'trim', explode( "\n", $raw_text ) ) );
    if ( empty( $options_list ) ) {
        return;
    }

    $exact_matches = array();
    $like_matches = array();

    // Sort into exact matches and wildcard matches
    foreach ( $options_list as $opt ) {
        if ( strpos( $opt, '%' ) !== false ) {
            $like_matches[] = esc_sql( $opt );
        } else {
            $exact_matches[] = $opt;
        }
    }

    // Build the SQL WHERE clause dynamically
    $where_clauses = array();
    
    if ( ! empty( $exact_matches ) ) {
        $placeholders = implode( "','", esc_sql( $exact_matches ) );
        $where_clauses[] = "option_name IN ('$placeholders')";
    }
    
    if ( ! empty( $like_matches ) ) {
        foreach ( $like_matches as $like_opt ) {
            $where_clauses[] = "option_name LIKE '$like_opt'";
        }
    }

    $where_sql = implode( ' OR ', $where_clauses );

    // Run ONE single query for everything
    $results = $wpdb->get_results( "
        SELECT option_name, option_value 
        FROM {$wpdb->options} 
        WHERE $where_sql
    " );

    $found_options = array();

    if ( $results ) {
        foreach ( $results as $row ) {
            $unserialized_value = maybe_unserialize( $row->option_value );
            wp_cache_add( $row->option_name, $unserialized_value, 'options' );
            $found_options[] = $row->option_name; 
        }
    }

    // Only run the "notoptions" negative cache logic for EXACT matches, not wildcards
    $missing_options = array_diff( $exact_matches, $found_options );
    if ( ! empty( $missing_options ) ) {
        $notoptions = wp_cache_get( 'notoptions', 'options' );
        if ( ! is_array( $notoptions ) ) {
            $notoptions = array();
        }
        foreach ( $missing_options as $missing ) {
            $notoptions[ $missing ] = true; 
        }
        wp_cache_set( 'notoptions', $notoptions, 'options' );
    }
}

// 4. Cache Cleaning Engine 🧹
function wqo_clear_custom_cache() {
    $raw_text = get_option( 'wqo_options_to_cache' );
    if ( empty( $raw_text ) ) {
        return;
    }

    $options_list = array_filter( array_map( 'trim', explode( "\n", $raw_text ) ) );
    
    // Loop through the list and delete each from WordPress memory
    foreach ( $options_list as $option_name ) {
        wp_cache_delete( $option_name, 'options' );
    }
}

// Trigger 1: Automatic (Runs whenever ANY setting is updated in WordPress)
add_action( 'updated_option', 'wqo_clear_custom_cache' );