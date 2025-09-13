<?php
/**
 * File: includes/functions.php
 *
 * Ensure that this code is executed after the REST API has been initialized.
 */

 


// Fetch products based on the selected subscription category.
add_action('wp_ajax_fetch_subscription_products', 'fetch_subscription_products_callback');
add_action('wp_ajax_nopriv_fetch_subscription_products', 'fetch_subscription_products_callback');
function fetch_subscription_products_callback(){
    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    if(empty($category)){
        echo '<p>Please select a category.</p>';
        wp_die();
    }
    // Query WooCommerce products that are eligible for subscription.
    // For example, assume each product has a meta field 'subscription_category' with the category slug.
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key'     => 'subscription_category',
                'value'   => $category,
                'compare' => '='
            )
        )
    );
    $products = get_posts($args);
    if($products){
        echo '<label for="subscription-product-selector">Choose a Product:</label>';
        echo '<select id="subscription-product-selector">';
        echo '<option value="">Select a Product</option>';
        foreach($products as $product){
            echo '<option value="'.esc_attr($product->ID).'">'.esc_html($product->post_title).'</option>';
        }
        echo '</select>';
    } else {
        echo '<p>No products found for this category.</p>';
    }
    wp_die();
}

// Fetch subscription plans based on the selected product.
add_action('wp_ajax_fetch_subscription_plans', 'fetch_subscription_plans_callback');
add_action('wp_ajax_nopriv_fetch_subscription_plans', 'fetch_subscription_plans_callback');
function fetch_subscription_plans_callback(){
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    if(!$product_id){
        echo '<p>Please select a product.</p>';
        wp_die();
    }
    // Query subscription plans (CPT "dq_subscription") where the associated product meta matches.
    $args = array(
        'post_type' => 'dq_subscription',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key'     => 'dq_subscription_product',
                'value'   => $product_id,
                'compare' => '='
            )
        )
    );
    $subscriptions = get_posts($args);
    if($subscriptions){
        echo '<h2>Available Subscription Plans:</h2>';
        echo '<ul>';
        foreach($subscriptions as $subscription){
            echo '<li><a href="#" class="select-subscription" data-id="'.esc_attr($subscription->ID).'">'.esc_html($subscription->post_title).'</a></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No subscription plans available for this product.</p>';
    }
    wp_die();
}





add_action( 'woocommerce_order_status_completed', 'dq_set_subscription_start_date', 10, 1 );
function dq_set_subscription_start_date( $order_id ) {
    // Get the WooCommerce order object
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    
    // Get the current time (you can change the format if needed)
    $start_date = current_time( 'mysql' );
    
    // Loop through each order item to check for a subscription
    foreach ( $order->get_items() as $item_id => $item ) {
        // Assume that when the order was created, a meta key 'subscription_id' was stored with the item
        $subscription_id = $item->get_meta( 'subscription_id' );
        if ( $subscription_id ) {
            // Update the subscription's start date meta field
            update_post_meta( $subscription_id, 'subscription_start_date', $start_date );
        }
    }
}




add_filter('woocommerce_rest_prepare_customer', 'dq_add_custom_data_to_customer_response', 10, 3);
function dq_add_custom_data_to_customer_response($response, $customer, $request) {
    // Retrieve the customer ID either from the object method or from the response data.
    if ( is_object($customer) && method_exists($customer, 'get_id') ) {
        $customer_id = $customer->get_id();
    } elseif ( isset( $response->data['id'] ) ) {
        $customer_id = $response->data['id'];
    } else {
        $customer_id = 0;
    }
    
    error_log("Customer ID: " . $customer_id);
    
    // Retrieve all subscription records for this customer.
    $subscriptions = get_posts(array(
        'post_type'      => 'dq_user_subscription',
        'post_status'    => 'publish',
        'author'         => $customer_id,
        'posts_per_page' => -1,
    ));
    //error_log("Subscriptions for customer " . $customer_id . ": " . print_r($subscriptions, true));

    if (!empty($subscriptions)) {
        $subscriptions_data = array();
        foreach ($subscriptions as $subscription) {
            $subscriptions_data[] = array(
                'id'                      => $subscription->ID,
                'order_id'                => get_post_meta($subscription->ID, 'order_id', true),
                'product_id'              => get_post_meta($subscription->ID, 'product_id', true),
                'subscription_plan_id'    => get_post_meta($subscription->ID, 'subscription_plan_id', true),
                'subscription_status'     => get_post_meta($subscription->ID, 'subscription_status', true),
                'subscription_start_date' => get_post_meta($subscription->ID, 'subscription_start_date', true),
                'customer_name'           => get_post_meta($subscription->ID, 'customer_name', true),
                'customer_email'          => get_post_meta($subscription->ID, 'customer_email', true),
            );
        }
        $response->data['subscriptions'] = $subscriptions_data;
    } else {
        $response->data['subscriptions'] = array();
    }

    // Retrieve WooCommerce orders for this customer.
    $orders = wc_get_orders(array(
        'customer' => $customer_id,
        'limit'    => -1,
        // You can add a 'status' parameter if needed, e.g. 'status' => array('completed','processing','on-hold')
    ));
    if (!empty($orders)) {
        $order_details = array();
        foreach ($orders as $order) {
            $order_details[] = array(
                'order_id' => $order->get_id(),
                // You can add additional order details here such as total, status, date_created, etc.
            );
        }
        $response->data['orders'] = $order_details;
    } else {
        $response->data['orders'] = array();
    }
    
    //error_log("Orders count for customer " . $customer_id . ": " . count($orders));
    //error_log("Orders data: " . print_r($orders, true));

    return $response;
}


add_filter( 'jwt_auth_token_before_dispatch', 'add_user_id_to_jwt_response', 10, 2 );
function add_user_id_to_jwt_response( $data, $user ) {
    $data['user_id'] = $user->ID;
    return $data;
}



add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'dq_change_subscription_meta_label', 10, 2 );
function dq_change_subscription_meta_label( $formatted_meta, $item ) {
    foreach ( $formatted_meta as $key => $meta ) {
        if ( 'dq_subscription_plan_id' === $meta->key ) {
            // Change the displayed label for this meta key.
            $formatted_meta[$key]->display_key = __( 'Subscription Plan ID', 'custom-product-addons' );
        }
    }
    return $formatted_meta;
}


add_action( 'woocommerce_admin_order_data_after_order_details', 'display_branch_name_in_order_header' );
function display_branch_name_in_order_header( $order ) {
 
    $branch_name = $order->get_meta( 'branch_name' );
    
 
    if ( ! empty( $branch_name ) ) {
        echo '<div class="order_data_column">';
            echo '<h4>' . __( 'Branch', 'your-text-domain' ) . '</h4>';
            echo '<p>' . esc_html( $branch_name ) . '</p>';
        echo '</div>';
    }
}




add_action( 'woocommerce_admin_order_data_after_order_details', 'display_customer_location_in_order_header' );
function display_customer_location_in_order_header( $order ) {
    $customer_latitude  = $order->get_meta( 'customer_latitude' );
    $customer_longitude = $order->get_meta( 'customer_longitude' );
    
    if ( ! empty( $customer_latitude ) && ! empty( $customer_longitude ) ) {
        // Construct a Google Maps URL using the customer's coordinates.
        $map_url = 'https://www.google.com/maps?q=' . urlencode( $customer_latitude . ',' . $customer_longitude );
        
        echo '<div class="order_data_column">';
            echo '<h4>' . __( 'Customer Location', 'your-text-domain' ) . '</h4>';
            // You can optionally include an icon by using dashicons if desired.
            // Example with dashicon:
             echo '<p><a href="' . esc_url( $map_url ) . '" target="_blank"><span class="dashicons dashicons-location-alt"></span> ' . __( 'View Map', 'your-text-domain' ) . '</a></p>';
            // echo '<p><a href="' . esc_url( $map_url ) . '" target="_blank">' . __( 'View Map', 'your-text-domain' ) . '</a></p>';
        echo '</div>';
    }
}
