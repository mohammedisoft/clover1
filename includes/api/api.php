<?php
/**
 * File: combined-api.php
 *
 * This function processes both custom addon data and subscription data when an order is created via the REST API.
 *
 * It hooks into the REST API order insertion process using the 'woocommerce_rest_insert_shop_order_object' hook.
 *
 * The function:
 * 1. Checks each order item for a 'custom_addon' meta value,
 *    retrieves the product's custom addon settings from '_custom_product_addons',
 *    formats the addon information, calculates the addon cost, and updates the item subtotal and total.
 * 2. For each order item, it also checks if there is a 'dq_subscription_plan_id' meta value.
 *    If found, it retrieves the corresponding subscription plan title and then combines the ID and title
 *    into a single meta value (e.g., "1940 – Every 6 weeks") stored as "Subscription".
 *
 * Example final meta for an order item might be:
 * "Custom Addons: Coffee Strength: Strong | Flavor Options: Vanilla x 1, Caramel x 2, Hazelnut x 1"
 * "Subscription: 1940 – Every 6 weeks"
 *
 * Finally, the order totals are recalculated and saved.
 *
 * @param WC_Order        $order   The order object.
 * @param WP_REST_Request $request The REST API request object.
 */
function cpa_rest_api_process_order( $order, $request ) {
   // error_log( 'cpa_rest_api_process_order triggered for Order ID: ' . $order->get_id() );

    // Process each order item.
    foreach ( $order->get_items() as $item_id => $item ) {
        //error_log( 'Order item ID ' . $item_id . ' meta data: ' . print_r( $item->get_meta_data(), true ) );

        // --- Process Custom Addons ---
        $custom_addon = $item->get_meta( 'custom_addon', true );
        //error_log( 'Order item ID ' . $item_id . ' custom_addon: ' . print_r( $custom_addon, true ) );

        if ( ! empty( $custom_addon ) && is_array( $custom_addon ) ) {
            $addon_info = array();
            $addon_cost = 0;

            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }
            $product_id = $product->get_id();
            if ( $product->is_type( 'variation' ) ) {
                $product_id = $product->get_parent_id();
            }

            // Retrieve the custom addon settings for the product.
            $addons = get_post_meta( $product_id, '_custom_product_addons', true );
            //error_log( 'Product ID ' . $product_id . ' addons: ' . print_r( $addons, true ) );

            if ( $addons && is_array( $addons ) ) {
                foreach ( $addons as $addon_index => $addon ) {
                    if ( isset( $custom_addon[ $addon_index ] ) ) {
                        $addon_title = isset( $addon['title'] ) ? $addon['title'] : __( 'Addon', 'custom-product-addons' );

                        // Single-type addon.
                        if ( $addon['type'] === 'single' ) {
                            $option_index = $custom_addon[ $addon_index ];
                            if ( isset( $addon['options'][ $option_index ] ) ) {
                                $option_label = $addon['options'][ $option_index ]['label'];
                                $option_price = floatval( $addon['options'][ $option_index ]['price'] );
                                $addon_cost += $option_price;
                                $addon_info[] = $addon_title . ': ' . $option_label;
                            }
                        }
                        // Multiple-type addon.
                        elseif ( $addon['type'] === 'multiple' ) {
                            $selected_options = (array) $custom_addon[ $addon_index ];
                            $options = array();
                            foreach ( $selected_options as $option_index => $qty ) {
                                if ( isset( $addon['options'][ $option_index ] ) && $qty > 0 ) {
                                    $option_label = $addon['options'][ $option_index ]['label'];
                                    $option_price = floatval( $addon['options'][ $option_index ]['price'] );
                                    $addon_cost += $option_price * $qty;
                                    $options[] = $option_label . ' x ' . $qty;
                                }
                            }
                            if ( ! empty( $options ) ) {
                                $addon_info[] = $addon_title . ': ' . implode( ', ', $options );
                            }
                        }
                    }
                }
            }
            if ( ! empty( $addon_info ) ) {
                $formatted_addons = implode( ' | ', $addon_info );
                $item->update_meta_data( __( 'Custom Addons', 'custom-product-addons' ), $formatted_addons );
                error_log( 'Order item ID ' . $item_id . ' meta updated: ' . $formatted_addons );
            }
            if ( $addon_cost > 0 ) {
                $quantity = $item->get_quantity();
                $addon_cost_total = $addon_cost * $quantity;

                $old_subtotal = $item->get_subtotal();
                $old_total    = $item->get_total();

                $new_subtotal = $old_subtotal + $addon_cost_total;
                $new_total    = $old_total + $addon_cost_total;

                $item->set_subtotal( $new_subtotal );
                $item->set_total( $new_total );

              //  error_log( 'Order item ID ' . $item_id . ' addon cost: ' . $addon_cost . ' per item, total addon cost: ' . $addon_cost_total );
               // error_log( 'New subtotal: ' . $new_subtotal . ', New total: ' . $new_total );
            }
        }

        // --- Process Subscription Data for each order item ---
        // Check if the order item has the subscription plan meta.
        $subscription_plan_id = $item->get_meta( 'dq_subscription_plan_id', true );
        if ( ! empty( $subscription_plan_id ) ) {
            $subscription_plan_title = get_the_title( $subscription_plan_id );
            if ( $subscription_plan_title ) {
                // Combine the subscription ID and plan title into one meta value.
                $combined_subscription = $subscription_plan_id . ' – ' . $subscription_plan_title;
                // Update the meta with the combined value.
                $item->update_meta_data( 'Subscription', $combined_subscription );
               // error_log( 'Order item ID ' . $item_id . ' updated with combined Subscription: ' . $combined_subscription );
            }
        }
    }




$branch_id = (int) $request->get_param( 'branch_id' );
if ( $branch_id > 0 ) {
  
    $order->update_meta_data( 'branch_id', $branch_id );

 
    $branch_post = get_post( $branch_id );
    if ( $branch_post && $branch_post->post_type === 'dq_branch' ) {
   
        $branch_title = $branch_post->post_title;
        $order->update_meta_data( 'branch_name', $branch_title );
        error_log( 'Order updated with branch id: ' . $branch_id . ' and branch name: ' . $branch_title );
    } else {
        error_log( 'Branch not found or invalid post type for branch id: ' . $branch_id );
    }
}


$customer_latitude  = $request->get_param( 'customer_latitude' );
$customer_longitude = $request->get_param( 'customer_longitude' );

if ( ! empty( $customer_latitude ) && ! empty( $customer_longitude ) ) {
    // Save customer location as order meta.
    $order->update_meta_data( 'customer_latitude', sanitize_text_field( $customer_latitude ) );
    $order->update_meta_data( 'customer_longitude', sanitize_text_field( $customer_longitude ) );
    error_log( 'Order updated with customer location: ' . $customer_latitude . ', ' . $customer_longitude );
}






    // Recalculate and save the order totals.
    $order->calculate_totals();
    $order->save();
}
add_action( 'woocommerce_rest_insert_shop_order_object', 'cpa_rest_api_process_order', 10, 2 );





add_action( 'woocommerce_rest_insert_shop_order_object', 'dq_set_branch_tax_for_api_order', 10, 2 );
function dq_set_branch_tax_for_api_order( $order, $request ) {
    // Grab branch_id from the incoming payload
    $branch_id = $request->get_param( 'branch_id' );
    if ( ! $branch_id ) {
        return;
    }

    // Make sure WooCommerce session exists
    if ( ! WC()->session || ! ( WC()->session instanceof WC_Session_Handler ) ) {
        WC()->session = new WC_Session_Handler();
        WC()->session->init();
    }

    // Seed it for your tax filters (customer/store address overrides)
    WC()->session->set( 'current_branch_id', absint( $branch_id ) );

    // Persist branch_id on the order record itself
    $order->update_meta_data( 'branch_id', absint( $branch_id ) );
}
