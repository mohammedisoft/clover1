<?php
/**
 * wp-content/plugins/dashboard-qahwtea/includes/custom-addons-cart.php
 * Custom Product Addons - Cart Price Update and Cart Item Meta Display
 *
 * This file handles:
 * 1. Saving the custom addons data to the cart item.
 * 2. Updating the product price in the cart based on selected addons.
 *    (Modified for variable products by retrieving addons from the parent product if needed.)
 * 3. Displaying the custom addon selections in the cart item meta.
 * 4. (Optional) Saving the custom addons data to the order item meta.
 *
 * Place this file in your plugin folder (e.g., wp-content/plugins/dashboard-qahwtea/includes/custom-addons-cart.php)
 * and include it from your main plugin file.
 */

// 1. Save custom addons data to the cart item data.
function cpa_add_custom_addon_to_cart_item_data( $cart_item_data, $product_id ) {
    error_log( 'Request Data: ' . json_encode($_REQUEST) );

    // Check if custom addons are sent.
    if ( isset( $_REQUEST['custom_addon'] ) ) {
        $cart_item_data['custom_addon'] = $_REQUEST['custom_addon'];
        // Generate a unique key to avoid merging products with different addon selections.
        $cart_item_data['unique_key'] = md5( microtime() . rand() );
    }
    
    // For variable products, check if a variation_id was provided.
    if ( isset( $_REQUEST['variation_id'] ) && ! empty( $_REQUEST['variation_id'] ) ) {
        $variation_id = absint( $_REQUEST['variation_id'] );
        $product = wc_get_product( $variation_id );
    } else {
        $product = wc_get_product( $product_id );
    }
    
    // Store the original product price (for the selected variation if available).
    $cart_item_data['original_price'] = floatval( $product->get_price() );
    
    return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'cpa_add_custom_addon_to_cart_item_data', 10, 2 );

// 1a. Retrieve custom data from session so the original price is preserved.
function cpa_get_cart_item_from_session( $cart_item, $values ) {
    if ( isset( $values['original_price'] ) ) {
        $cart_item['original_price'] = $values['original_price'];
    }
    return $cart_item;
}
add_filter( 'woocommerce_get_cart_item_from_session', 'cpa_get_cart_item_from_session', 20, 2 );

// 2. Update the cart item price based on selected addons.
function cpa_update_cart_item_price( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }
    if ( ! empty( $cart->get_cart() ) ) {
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            // Use the stored original price if available.
            $base_price = isset( $cart_item['original_price'] ) ? $cart_item['original_price'] : floatval( $cart_item['data']->get_price() );
            $addon_total = 0;
            
            if ( isset( $cart_item['custom_addon'] ) && ! empty( $cart_item['custom_addon'] ) ) {
                $custom_addons = $cart_item['custom_addon'];
                $product = $cart_item['data'];
                $product_id = $product->get_id();
                // For variable products, get addons from the parent product.
                if ( $product->is_type( 'variation' ) ) {
                    $product_id = $product->get_parent_id();
                }
                // Retrieve defined addons from product meta.
                $addons = get_post_meta( $product_id, '_custom_product_addons', true );
                if ( $addons && is_array( $addons ) ) {
                    foreach ( $addons as $addon_index => $addon ) {
                        if ( isset( $custom_addons[ $addon_index ] ) ) {
                            if ( $addon['type'] === 'single' ) {
                                // Single choice addon.
                                $option_index = $custom_addons[ $addon_index ];
                                if ( isset( $addon['options'][ $option_index ] ) ) {
                                    $option = $addon['options'][ $option_index ];
                                    $addon_total += floatval( $option['price'] );
                                }
                            } elseif ( $addon['type'] === 'multiple' ) {
                                // Multiple choice addon.
                                $selected_options = (array) $custom_addons[ $addon_index ];
                                foreach ( $selected_options as $option_index => $qty ) {
                                    if ( isset( $addon['options'][ $option_index ] ) ) {
                                        $option = $addon['options'][ $option_index ];
                                        $addon_total += floatval( $option['price'] ) * intval( $qty );
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // Calculate the new price using the original base price.
            $new_price = $base_price + $addon_total;
            $cart_item['data']->set_price( $new_price );
        }
    }
}
add_action( 'woocommerce_before_calculate_totals', 'cpa_update_cart_item_price', 10, 1 );

// 3. Display custom addon selections in the cart item meta.
function cpa_display_custom_addons_cart( $item_data, $cart_item ) {
    if ( isset( $cart_item['custom_addon'] ) ) {
        $custom_addons = $cart_item['custom_addon'];
        $product = $cart_item['data'];
        // For variable products, get addons from the parent product.
        $product_id = $product->get_id();
        if ( $product->is_type( 'variation' ) ) {
            $product_id = $product->get_parent_id();
        }
        $addons = get_post_meta( $product_id, '_custom_product_addons', true );
        if ( $addons && is_array( $addons ) ) {
            foreach ( $addons as $addon_index => $addon ) {
                if ( isset( $custom_addons[ $addon_index ] ) ) {
                    $addon_title = isset( $addon['title'] ) ? $addon['title'] : __( 'Addon', 'custom-product-addons' );
                    if ( $addon['type'] === 'single' ) {
                        $option_index = $custom_addons[ $addon_index ];
                        if ( isset( $addon['options'][ $option_index ] ) ) {
                            $option_label = $addon['options'][ $option_index ]['label'];
                            $item_data[] = array(
                                'key'   => $addon_title,
                                'value' => $option_label,
                            );
                        }
                    } elseif ( $addon['type'] === 'multiple' ) {
                        $selected_options = (array) $custom_addons[ $addon_index ];
                        $options = array();
                        foreach ( $selected_options as $option_index => $qty ) {
                            if ( isset( $addon['options'][ $option_index ] ) && $qty > 0 ) {
                                $option_label = $addon['options'][ $option_index ]['label'];
                                $options[] = $option_label . ' x ' . $qty;
                            }
                        }
                        if ( ! empty( $options ) ) {
                            $item_data[] = array(
                                'key'   => $addon_title,
                                'value' => implode( ', ', $options ),
                            );
                        }
                    }
                }
            }
        }
    }
    return $item_data;
}


add_filter( 'woocommerce_get_item_data', 'cpa_display_custom_addons_cart', 10, 2 );

// 4. Save custom addon data to order item meta.
function cpa_add_custom_addon_to_order_items( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['custom_addon'] ) ) {
        $addon_info = array();
        $custom_addons = $values['custom_addon'];
        $product = $values['data'];
        $product_id = $product->get_id();
        if ( $product->is_type( 'variation' ) ) {
            $product_id = $product->get_parent_id();
        }
        $addons = get_post_meta( $product_id, '_custom_product_addons', true );
        if ( $addons && is_array( $addons ) ) {
            foreach ( $addons as $addon_index => $addon ) {
                if ( isset( $custom_addons[ $addon_index ] ) ) {
                    $addon_title = isset( $addon['title'] ) ? $addon['title'] : __( 'Addon', 'custom-product-addons' );
                    if ( $addon['type'] === 'single' ) {
                        $option_index = $custom_addons[ $addon_index ];
                        if ( isset( $addon['options'][ $option_index ] ) ) {
                            $option_label = $addon['options'][ $option_index ]['label'];
                            $addon_info[] = $addon_title . ': ' . $option_label;
                        }
                    } elseif ( $addon['type'] === 'multiple' ) {
                        $selected_options = (array) $custom_addons[ $addon_index ];
                        $options = array();
                        foreach ( $selected_options as $option_index => $qty ) {
                            if ( isset( $addon['options'][ $option_index ] ) && $qty > 0 ) {
                                $option_label = $addon['options'][ $option_index ]['label'];
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
            $item->add_meta_data( __( 'Custom Addons', 'custom-product-addons' ), implode( ' | ', $addon_info ) );
        }
    }
}
add_action( 'woocommerce_checkout_create_order_line_item', 'cpa_add_custom_addon_to_order_items', 10, 4 );


