<?php
/**
 * File: includes/class-dq-branch.php
 *
 * Registers the custom post type "dq_branch" for managing branch data.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DQ_Branch {

    public function __construct() {
        // Hook into init to register our custom post type.
        add_action( 'init', array( $this, 'register_branch_post_type' ) );
        
    }

    /**
     * Registers the custom post type "dq_branch".
     */
    public function register_branch_post_type() {
        $labels = array(
            'name'               => 'Branches',
            'singular_name'      => 'Branch',
            'menu_name'          => 'Branches',
            'name_admin_bar'     => 'Branch',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Branch',
            'new_item'           => 'New Branch',
            'edit_item'          => 'Edit Branch',
            'view_item'          => 'View Branch',
            'all_items'          => 'All Branches',
            'search_items'       => 'Search Branches',
            'not_found'          => 'No branches found.',
            'not_found_in_trash' => 'No branches found in Trash.',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,    // Not viewable on the front end.
            'show_ui'            => true,     // Manage in admin.
            'show_in_menu'       => 'dashboard-qahwtea',
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'branch' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 9,
            'supports'           => array( 'title' ), // The branch name is stored in the post title.
            'show_in_rest'       => true,
            'rest_base'          => 'branches'
        );

        register_post_type( 'dq_branch', $args );
    }


    
}



// Instantiate the branch class.
new DQ_Branch();
