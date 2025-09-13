<?php
/**
 * Plugin Name: Custom Addons Order Price Calculation
 * Description: Updates order line item prices based on custom addons after order creation (for API orders).
 * Version: 1.0
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

add_action('woocommerce_checkout_order_processed', 'update_order_prices_for_custom_addons', 10, 3);
function update_order_prices_for_custom_addons($order_id, $posted_data, $order) {
    // Loop through each order item.
    foreach ($order->get_items() as $item_id => $item) {
        // Get the associated product.
        $product = $item->get_product();
        if ( ! $product ) {
            continue;
        }
        // Get the product's base price.
        $base_price = floatval( $product->get_price() );
        $addon_total = 0;
        
        // Retrieve the "Custom Addons" meta from the order item.
        $custom_addons = $item->get_meta('Custom Addons');
        if ( ! empty( $custom_addons ) ) {
            // Decode the JSON encoded string.
            $addons = json_decode( $custom_addons, true );
            if ( is_array( $addons ) ) {
                // Loop through each addon dynamically.
                foreach ( $addons as $addon_name => $addon_data ) {
                    // For single selection addon (contains an "option" key).
                    if ( isset( $addon_data['option'] ) ) {
                        if ( isset( $addon_data['price'] ) ) {
                            $addon_total += floatval( $addon_data['price'] );
                        }
                    }
                    // For multiple selection addon: iterate through options.
                    elseif ( is_array( $addon_data ) ) {
                        foreach ( $addon_data as $option ) {
                            if ( isset( $option['price'] ) && isset( $option['quantity'] ) ) {
                                $addon_total += floatval( $option['price'] ) * intval( $option['quantity'] );
                            }
                        }
                    }
                }
            }
        }
        
        // Calculate the new price (base price plus addon total).
        $new_price = $base_price + $addon_total;
        // Update order item prices.
        $item->set_subtotal( $new_price );
        $item->set_total( $new_price );
        $item->save();
    }
    
    // Recalculate order totals and save the order.
    $order->calculate_totals();
    $order->save();
}
