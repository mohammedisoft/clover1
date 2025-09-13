<?php
/** includes/class-dq-subscription-taxonomy.php */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DQ_Subscription_Taxonomy {

    public function __construct() {
        add_action( 'init', array( $this, 'register_subscription_taxonomy' ) );
    }

    public function register_subscription_taxonomy() {
        $labels = array(
            'name'              => 'Subscription Categories',
            'singular_name'     => 'Subscription Category',
            'search_items'      => 'Search Categories',
            'all_items'         => 'All Categories',
            'edit_item'         => 'Edit Category',
            'update_item'       => 'Update Category',
            'add_new_item'      => 'Add New Category',
            'new_item_name'     => 'New Category Name',
            'menu_name'         => 'Subscription Categories',
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'subscription-category' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'subscription_category', array( 'product' ), $args );
    }
}



new DQ_Subscription_Taxonomy();
