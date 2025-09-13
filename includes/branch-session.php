<?php
/**
 * File: dashboard-qahwtea/includes/branch-session.php
 * Purpose: AJAX endpoint to set the current branch in the WooCommerce session.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// For logged-in users...
add_action( 'wp_ajax_set_branch', 'dq_set_branch_session' );
// ...and for guests
add_action( 'wp_ajax_nopriv_set_branch', 'dq_set_branch_session' );

function dq_set_branch_session() {
    $branch_id = isset( $_POST['branch_id'] ) ? intval( $_POST['branch_id'] ) : 0;
    if ( $branch_id ) {
        WC()->session->set( 'current_branch_id', $branch_id );
        wp_send_json_success();
    }
    wp_send_json_error( 'Invalid branch ID' );
}

add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_script(
        'dq-branch-selector',
        plugin_dir_url( __FILE__ ) . '../assets/script_frontend.js',
        [ 'jquery' ],
        filemtime( plugin_dir_path( __FILE__ ) . '../assets/script_frontend.js' ),
        true
    );
    wp_localize_script( 'dq-branch-selector', 'dq_vars', [
        'ajax_url'           => admin_url( 'admin-ajax.php' ),
        'current_branch_id'  => WC()->session ? WC()->session->get( 'current_branch_id' ) : '',
    ] );
} );
