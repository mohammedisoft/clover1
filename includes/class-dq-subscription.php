<?php
/**
 * File: dashboard-qahwtea/includes/class-dq-subscription.php
 *
 * Registers the custom post type "dq_subscription" for subscription pricing plans.
 * This post type supports only the title.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DQ_Subscription {

    public function __construct() {
        add_action( 'init', array( $this, 'register_subscription_post_type' ) );
    }

    /**
     * Registers the "dq_subscription" post type.
     */
    public function register_subscription_post_type() {
        $labels = array(
            'name'               => 'Subscriptions',
            'singular_name'      => 'Subscription',
            'menu_name'          => 'Subscriptions',
            'name_admin_bar'     => 'Subscription',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Subscription',
            'new_item'           => 'New Subscription',
            'edit_item'          => 'Edit Subscription',
            'view_item'          => 'View Subscription',
            'all_items'          => 'All Subscriptions',
            'search_items'       => 'Search Subscriptions',
            'not_found'          => 'No subscriptions found.',
            'not_found_in_trash' => 'No subscriptions found in Trash.',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,   // Not viewable on the front end.
            'show_ui'            => true,
            'show_in_menu'       => 'dashboard-qahwtea',
            'rewrite'            => array( 'slug' => 'subscriptions' ),
            'query_var'          => true,
            'capability_type'    => 'post',
            'supports'           => array( 'title' ), // Only support title; taxonomy box will appear for subscription_category.
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 30,
            'show_in_rest'       => true,
            'rest_base'          => 'subscriptions'
        );

        register_post_type( 'dq_subscription', $args );
    }
}

new DQ_Subscription();


