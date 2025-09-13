<?php
/**
 * File: dashboard-qahwtea/includes/class-dq-user-subscription-cpt.php
 *
 * Registers the custom post type "dq_user_subscription" for storing user subscriptions.
 */

function dq_register_user_subscription_post_type() {
    $labels = array(
        'name'               => 'User Subscriptions',
        'singular_name'      => 'User Subscription',
        'menu_name'          => 'User Subscriptions',
        'name_admin_bar'     => 'User Subscription',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New User Subscription',
        'new_item'           => 'New User Subscription',
        'edit_item'          => 'Edit User Subscription',
        'view_item'          => 'View User Subscription',
        'all_items'          => 'All User Subscriptions',
        'search_items'       => 'Search User Subscriptions',
        'not_found'          => 'No subscriptions found.',
        'not_found_in_trash' => 'No subscriptions found in Trash.',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false, // Make it public so it's queryable and available via the REST API.
        'show_ui'            => false,
        'has_archive'        => false,
        'hierarchical'       => false,
        'publicly_queryable' => false,
        'menu_position'      => 40,
        'supports'           => array( 'title' ),
        'show_in_rest'       => true,
        'rest_base'          => 'dq_user_subscriptions', // Custom REST base (optional)
    );

    register_post_type( 'dq_user_subscription', $args );
}
add_action( 'init', 'dq_register_user_subscription_post_type' );
