<?php
/**
 * admin/admin-page.php
 *
 * This file registers the main menu and submenu pages for the Dashboard Qahwtea plugin.
 * It creates a top-level menu and several submenus (Subscriptions, Coupons, Branches, and Settings).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Remove the default Branches submenu (automatically added by CPT registration).
 */
add_action('admin_menu', 'remove_default_dq_branch_submenu', 999);
function remove_default_dq_branch_submenu(){
    // The default submenu slug for the Branches CPT is 'edit.php?post_type=dq_branch'
    remove_submenu_page( 'dashboard-qahwtea', 'edit.php?post_type=dq_branch' );
}

add_action('admin_menu', 'remove_default_dq_subscription_submenu', 999);
function remove_default_dq_subscription_submenu(){
    // The default submenu slug for the Subscriptions CPT is 'edit.php?post_type=dq_subscription'
    remove_submenu_page( 'dashboard-qahwtea', 'edit.php?post_type=dq_subscription' );
}

/**
 * Adds the main menu and all submenu pages to the WordPress admin panel.
 */
function dq_add_admin_menu() {
    // Create the top-level menu page.
    add_menu_page(
        __('Dashboard Qahwtea', 'dashboard-qahwtea'),   // Page title.
        __('Qahwtea', 'dashboard-qahwtea'),               // Menu title.
        'manage_options',                                 // Required capability.
        'dashboard-qahwtea',                              // Menu slug.
        'dq_admin_dashboard',                             // Callback function to render the dashboard.
        'dashicons-coffee',                               // Menu icon.
        25                                                // Position in the admin menu.
    );
    // Add the "Subscriptions" submenu (for the Subscriptions CPT).
    add_submenu_page(
        'dashboard-qahwtea',                              // Parent slug.
        __('Subscriptions', 'dashboard-qahwtea'),         // Page title.
        __('Subscriptions', 'dashboard-qahwtea'),         // Menu title.
        'manage_options',                                 // Required capability.
        'edit.php?post_type=dq_subscription'              // Menu slug (CPT screen for subscriptions).
    );

    
    // Add the "Coupons" submenu.
    add_submenu_page(
        'dashboard-qahwtea',                              // Parent slug.
        __('Coupons', 'dashboard-qahwtea'),               // Page title.
        __('Coupons', 'dashboard-qahwtea'),               // Menu title.
        'manage_options',                                 // Required capability.
        'dq_coupons',                                     // Menu slug.
        'dq_coupons_page'                                 // Callback function to render the page.
    );

    // Re-add the "Branches" submenu.
    add_submenu_page(
        'dashboard-qahwtea',                 // Parent slug.
        __('Branches', 'dashboard-qahwtea'),   // Page title.
        __('Branches', 'dashboard-qahwtea'),   // Menu title.
        'manage_options',                      // Required capability.
        'edit.php?post_type=dq_branch'         // Menu slug (CPT screen for branches).
    );

    // Add the "Settings" submenu.
    add_submenu_page(
        'dashboard-qahwtea',                              // Parent slug.
        __('Settings', 'dashboard-qahwtea'),              // Page title.
        __('Settings', 'dashboard-qahwtea'),              // Menu title.
        'manage_options',                                 // Required capability.
        'dq_settings',                                    // Menu slug.
        'dq_settings_page'                                // Callback function to render the page.
    );
}
add_action('admin_menu', 'dq_add_admin_menu');

/**
 * Callback function to render the main Dashboard page.
 */
if ( ! function_exists( 'dq_admin_dashboard' ) ) {
    function dq_admin_dashboard() {
        echo '<div class="wrap"><h1>Dashboard Qahwtea</h1><p>Welcome to the Qahwtea management dashboard.</p></div>';
    }
}

/**
 * Callback function to render the Coupons page.
 */
if ( ! function_exists( 'dq_coupons_page' ) ) {
    function dq_coupons_page() {
        echo '<div class="wrap"><h1>Manage Coupons</h1><p>Here you can manage WooCommerce coupons.</p></div>';
    }
}

/**
 * Callback function to render the Settings page.
 */
if ( ! function_exists( 'dq_settings_page' ) ) {
    function dq_settings_page() {
        echo '<div class="wrap"><h1>Dashboard Qahwtea Settings</h1><p>Customize plugin settings here.</p></div>';
    }
}
