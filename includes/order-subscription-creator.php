<?php
/**
 * File: dashboard-qahwtea/includes/order-subscription-creator.php
 *
 * Creates a user subscription record upon order completion.
 */

function dq_create_user_subscription( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    // Retrieve user data.
    $user_id   = $order->get_user_id();
    $user_data = get_userdata( $user_id );

    // Use first and last name if available, otherwise fallback to display_name.
    if ( $user_data ) {
        $first_name = $user_data->first_name;
        $last_name  = $user_data->last_name;
        $customer_name = trim( $first_name . ' ' . $last_name );
        if ( empty( $customer_name ) ) {
            $customer_name = $user_data->display_name;
        }
        $customer_email = $user_data->user_email;
    } else {
        $customer_name  = '';
        $customer_email = '';
    }

    // Retrieve payment method used in the order.
    $payment_method = $order->get_payment_method_title();

    // Get current time for subscription start date.
    $subscription_start_date = current_time( 'mysql' );

    // Loop through order items.
    foreach ( $order->get_items() as $item_id => $item ) {
        // Check if the order item has an associated subscription plan.
        $subscription_plan_id = $item->get_meta( 'dq_subscription_plan_id', true );
        if ( $subscription_plan_id ) {
            $product_id = $item->get_product_id();

            // Check if a subscription record for this order item already exists.
            $existing = get_posts( array(
                'post_type'   => 'dq_user_subscription',
                'meta_key'    => 'order_item_id',
                'meta_value'  => $item_id,
                'numberposts' => 1,
            ) );
            if ( empty( $existing ) ) {
                // Create a new subscription record.
                $subscription_data = array(
                    'post_type'   => 'dq_user_subscription',
                    'post_title'  => 'Subscription for Order ' . $order_id,
                    'post_status' => 'publish',
                    'post_author' => $user_id,
                );
                $subscription_post_id = wp_insert_post( $subscription_data );
                if ( $subscription_post_id ) {
                    // Save additional subscription meta data.
                    update_post_meta( $subscription_post_id, 'order_id', $order_id );
                    update_post_meta( $subscription_post_id, 'order_item_id', $item_id );
                    update_post_meta( $subscription_post_id, 'product_id', $product_id );
                    update_post_meta( $subscription_post_id, 'subscription_plan_id', $subscription_plan_id );
                    
                    // Store customer details.
                    update_post_meta( $subscription_post_id, 'customer_name', $customer_name );
                    update_post_meta( $subscription_post_id, 'customer_email', $customer_email );
                    
                    // Store payment method and auto-renew status (assuming auto-renew is enabled by default).
                    update_post_meta( $subscription_post_id, 'payment_method', $payment_method );
                    update_post_meta( $subscription_post_id, 'auto_renew', 'yes' ); 
                    
                    // Store subscription start date.
                    update_post_meta( $subscription_post_id, 'subscription_start_date', $subscription_start_date );
                    
                    // Set initial subscription status.
                    update_post_meta( $subscription_post_id, 'subscription_status', 'active' );
                }
            }
        }
    }
}
add_action( 'woocommerce_order_status_completed', 'dq_create_user_subscription', 10, 1 );
?>
