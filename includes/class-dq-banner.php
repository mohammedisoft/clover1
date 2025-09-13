<?php
/**
 * File: includes/class-dq-banner.php
 *
 * Registers the custom post type for Banners.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DQ_Banner {

    public function __construct() {
        // Register the custom post type on init.
        add_action( 'init', array( $this, 'register_banner_post_type' ) );
    }

    /**
     * Registers the custom post type "dq_banner".
     */
    public function register_banner_post_type() {
        $labels = array(
            'name'               => 'Banners',
            'singular_name'      => 'Banner',
            'menu_name'          => 'Banners',
            'name_admin_bar'     => 'Banner',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Banner',
            'new_item'           => 'New Banner',
            'edit_item'          => 'Edit Banner',
            'view_item'          => 'View Banner',
            'all_items'          => 'Banners',
            'search_items'       => 'Search Banners',
            'not_found'          => 'No banners found.',
            'not_found_in_trash' => 'No banners found in Trash.',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false, // Not viewable on the front end.
            'show_ui'            => 'dashboard-qahwtea',  // Manage in admin.
            'menu_position'      => 11,
            'supports'           => array( 'title' ), // Use the post title for the banner name.
            'show_in_rest'       => true,
            'rest_base'          => 'banners',
        );

        register_post_type( 'dq_banner', $args );
    }
}

new DQ_Banner();
