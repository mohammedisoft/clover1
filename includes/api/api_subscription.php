<?php
/**
 * File: dashboard-qahwtea/includes/api.php
 * Registers a custom REST API endpoint to fetch customer orders and subscriptions.
 * Namespace: "qahwtea"
 * 
 * Usage:
 * 1. For a specific customer: 
 *    GET https://azure-wombat-889486.hostingersite.com/wp-json/qahwtea/customer-data/123
 * 2. For all customers (admin only):
 *    GET https://azure-wombat-889486.hostingersite.com/wp-json/qahwtea/customer-data
 */




 if ( ! function_exists( 'dq_woocommerce_api_permission_callback' ) ) {
    function dq_woocommerce_api_permission_callback( $request ) {
        $user = wp_get_current_user();
        if ( $user && $user->ID > 0 ) {
            return true;
        }
        return new WP_Error(
            'woocommerce_rest_cannot_view',
            __( 'Sorry, you cannot view this resource.' ),
            array( 'status' => rest_authorization_required_code() )
        );
    }
}





add_filter( 'woocommerce_rest_prepare_product_object', 'dq_add_subscription_data_to_product', 10, 3 );
function dq_add_subscription_data_to_product( $response, $post, $request ) {
    $subscription_ids = get_post_meta( $post->get_id(), 'dq_product_subscriptions', true );
    if ( ! is_array( $subscription_ids ) ) {
        $subscription_ids = array();
    }
    $subscriptions_data = array();
    if ( ! empty( $subscription_ids ) ) {
        foreach ( $subscription_ids as $sub_id ) {
            $subscriptions_data[] = array(
                'id'                               => $sub_id,
                'title'                            => get_the_title( $sub_id ),
                'subscription_duration'            => get_post_meta( $sub_id, 'dq_subscription_duration', true ),
                'subscription_plan_name'           => get_post_meta( $sub_id, 'dq_subscription_plan_name', true ),
                'subscription_effective_plan_name' => get_post_meta( $sub_id, 'dq_subscription_effective_plan_name', true ),
            );
        }
    }
    $response->data['subscriptions'] = $subscriptions_data;
    return $response;
}





// Register subscription categories endpoint under WooCommerce namespace.
add_action( 'rest_api_init', 'dq_register_subscription_categories_endpoint' );
function dq_register_subscription_categories_endpoint() {
    register_rest_route( 'wc/v3', '/subscription-categories', array(
        'methods'             => 'GET',
        'callback'            => 'dq_get_subscription_categories',
        // You can use WooCommerce's default authentication by returning true if the user is authenticated.
        'permission_callback' => function() {
            // If authentication is successful via consumer key/secret, WooCommerce will set the current user.
            return ( get_current_user_id() > 0 ) ? true : new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view this resource.' ), array( 'status' => 401 ) );
        },
    ) );
}

function dq_get_subscription_categories( $request ) {
    $categories = get_terms( array(
        'taxonomy'   => 'subscription_category',
        'hide_empty' => false,
    ) );
    
    if ( is_wp_error( $categories ) ) {
        return rest_ensure_response( array() );
    }
    
    $data = array();
    foreach ( $categories as $category ) {
        $data[] = array(
            'id'   => $category->term_id,
            'name' => $category->name,
            'slug' => $category->slug,
        );
    }
    
    return rest_ensure_response( $data );
}






// Register products-by-category endpoint under WooCommerce namespace.
add_action( 'rest_api_init', 'dq_register_products_by_category_endpoint' );
function dq_register_products_by_category_endpoint() {
    register_rest_route( 'wc/v3', '/subscription-categories/(?P<id>\d+)/products', array(
        'methods'             => 'GET',
        'callback'            => 'dq_get_products_by_subscription_category',
        'permission_callback' => function() {
            return ( get_current_user_id() > 0 ) ? true : new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view this resource.' ), array( 'status' => 401 ) );
        },
    ) );
}

function dq_get_products_by_subscription_category( $request ) {
    $category_id = (int) $request->get_param( 'id' );
    
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'tax_query'      => array(
            array(
                'taxonomy' => 'subscription_category',
                'field'    => 'term_id',
                'terms'    => $category_id,
            ),
        ),
    );
    
    $products = get_posts( $args );
    $data = array();
    
    if ( ! empty( $products ) ) {
        foreach ( $products as $product ) {
            $data[] = array(
                'id'    => $product->ID,
                'title' => get_the_title( $product->ID ),
            );
        }
    }
    
    return rest_ensure_response( $data );
}






// Register subscriptions-by-product endpoint under WooCommerce namespace.
add_action( 'rest_api_init', 'dq_register_subscriptions_by_product_endpoint' );
function dq_register_subscriptions_by_product_endpoint() {
    register_rest_route( 'wc/v3', '/products/(?P<id>\d+)/subscriptions', array(
        'methods'             => 'GET',
        'callback'            => 'dq_get_subscriptions_by_product',
        'permission_callback' => function() {
            return ( get_current_user_id() > 0 ) ? true : new WP_Error( 'rest_forbidden', esc_html__( 'You cannot view this resource.' ), array( 'status' => 401 ) );
        },
    ) );
}

function dq_get_subscriptions_by_product( $request ) {
    $product_id = (int) $request->get_param( 'id' );
    $subscription_ids = get_post_meta( $product_id, 'dq_product_subscriptions', true );
    
    if ( ! is_array( $subscription_ids ) ) {
        $subscription_ids = array();
    }
    
    $data = array();
    if ( ! empty( $subscription_ids ) ) {
        foreach ( $subscription_ids as $sub_id ) {
            $data[] = array(
                'id'                              => $sub_id,
                'title'                           => get_the_title( $sub_id ),
                'subscription_duration'           => get_post_meta( $sub_id, 'dq_subscription_duration', true ),
                'subscription_plan_name'          => get_post_meta( $sub_id, 'dq_subscription_plan_name', true ),
                'subscription_effective_plan_name'=> get_post_meta( $sub_id, 'dq_subscription_effective_plan_name', true ),
            );
        }
    }
    
    return rest_ensure_response( $data );
}



