<?php
/**
 * File: dashboard-qahwtea/includes/integration-woocommerce.php
 *
 * Handles WooCommerce integration for subscriptions.
 * - Saves the selected subscription plan to cart item data.
 * - Saves the subscription plan in the order meta.
 */

// Save the selected subscription plan to cart item data.
function dq_add_subscription_plan_cart_item( $cart_item_data, $product_id ) {
    if ( isset( $_POST['dq_subscription_plan'] ) ) {
        $cart_item_data['dq_subscription_plan'] = absint( $_POST['dq_subscription_plan'] );
        // Ensure items with different subscription plans are not merged.
        $cart_item_data['unique_key'] = md5( microtime() . rand() );
    }
    return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'dq_add_subscription_plan_cart_item', 10, 2 );

// Save the subscription plan to order item meta.
function dq_order_item_subscription_meta( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['dq_subscription_plan'] ) ) {
        $subscription_plan_id = absint( $values['dq_subscription_plan'] );
        $subscription_plan_title = get_the_title( $subscription_plan_id );
        $item->add_meta_data( 'Subscription Plan', $subscription_plan_title, true );
        $item->add_meta_data( 'dq_subscription_plan_id', $subscription_plan_id, true );
    }
}
add_action( 'woocommerce_checkout_create_order_line_item', 'dq_order_item_subscription_meta', 10, 4 );





