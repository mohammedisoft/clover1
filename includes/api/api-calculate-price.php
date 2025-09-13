<?php
/**
 * File: dashboard-qahwtea/includes/api-calculate-price.php
 * Registers a custom REST API endpoint to calculate the final price of a product
 * with custom addons.
 * Namespace: "qahwtea/v1"
 * 
 * Endpoint URL:
 * POST https://azure-wombat-889486.hostingersite.com/wp-json/qahwtea/v1/calculate-price
 * 
 * Expected JSON payload:
 * {
 *   "product_id": 2143,
 *   "variation_id": 2144, // optional, if product is variable
 *   "custom_addon": {
 *       "0": "1741032547413",              // For single choice addon (e.g., Coffee Strength)
 *       "1": {
 *           "1741032575648": 2             // For multiple choice addon (e.g., Flavor Options with quantity)
 *       }
 *   }
 * }
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

// Register the custom endpoint.
function dq_register_calculate_price_endpoint() {
    register_rest_route( 'wc/v3', '/calculate-price', array(
        'methods'             => 'POST',
        'callback'            => 'dq_calculate_custom_product_price',
        'permission_callback' => 'dq_woocommerce_api_permission_callback',
    ) );
}
add_action( 'rest_api_init', 'dq_register_calculate_price_endpoint' );

function dq_calculate_custom_product_price( WP_REST_Request $request ) {
    $params = $request->get_json_params();

    if ( empty( $params['product_id'] ) ) {
        return new WP_Error( 'missing_product_id', 'Product id is required', array( 'status' => 400 ) );
    }

    $product_id   = intval( $params['product_id'] );
    $variation_id = isset( $params['variation_id'] ) ? intval( $params['variation_id'] ) : 0;
    $custom_addon = isset( $params['custom_addon'] ) ? $params['custom_addon'] : array();

    // Retrieve the product data: if variation_id exists, use it.
    if ( $variation_id ) {
        $product = wc_get_product( $variation_id );
    } else {
        $product = wc_get_product( $product_id );
    }
    if ( ! $product ) {
        return new WP_Error( 'invalid_product', 'Product not found', array( 'status' => 404 ) );
    }

    // Get the base price from the product.
    $base_price = floatval( $product->get_price() );

    // For variable products, if product is a variation, get addons from the parent product.
    if ( $product->is_type( 'variation' ) ) {
        $parent_product_id = $product->get_parent_id();
    } else {
        $parent_product_id = $product_id;
    }

    // Retrieve custom addons meta from the parent product.
    $addons = get_post_meta( $parent_product_id, '_custom_product_addons', true );
    $addon_total = 0;
    if ( $addons && is_array( $addons ) && ! empty( $custom_addon ) ) {
        foreach ( $addons as $addon_index => $addon ) {
            if ( isset( $custom_addon[ $addon_index ] ) ) {
                if ( $addon['type'] === 'single' ) {
                    // For single choice addon.
                    $option_index = $custom_addon[ $addon_index ];
                    if ( isset( $addon['options'][ $option_index ] ) ) {
                        $option = $addon['options'][ $option_index ];
                        $addon_total += floatval( $option['price'] );
                    }
                } elseif ( $addon['type'] === 'multiple' ) {
                    // For multiple choice addon, force the value as an array.
                    $selected_options = (array) $custom_addon[ $addon_index ];
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

    $final_price = $base_price + $addon_total;

    return rest_ensure_response( array(
        'product_id'   => $product_id,
        'variation_id' => $variation_id,
        'base_price'   => $base_price,
        'addon_total'  => $addon_total,
        'final_price'  => $final_price,
    ) );
}
