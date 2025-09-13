<?php
/**
 * File: includes/class-dq-product-group.php
 *
 * Registers the custom post type for Product Groups.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DQ_Product_Group {

    public function __construct() {
        // Register the custom post type on init.
        add_action( 'init', array( $this, 'register_product_group_post_type' ) );
    }

    /**
     * Registers the custom post type "dq_product_group".
     */
    public function register_product_group_post_type() {
        $labels = array(
            'name'               => 'Product Groups',
            'singular_name'      => 'Product Group',
            'menu_name'          => 'Product Groups',
            'name_admin_bar'     => 'Product Group',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Product Group',
            'new_item'           => 'New Product Group',
            'edit_item'          => 'Edit Product Group',
            'view_item'          => 'View Product Group',
            'all_items'          => 'Product Groups',
            'search_items'       => 'Search Product Groups',
            'not_found'          => 'No product groups found.',
            'not_found_in_trash' => 'No product groups found in Trash.',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false, // Not viewable on the front end.
            'show_ui'            => true,  // Manage in admin.
            'show_in_menu'       => 'dashboard-qahwtea', // Change this as needed.
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'product-group' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 10,
            'supports'           => array( 'title' ), // Use the post title for the group name.
            'show_in_rest'       => true,
            'rest_base'          => 'product-groups'
        );

        register_post_type( 'dq_product_group', $args );
    }
}

new DQ_Product_Group();
