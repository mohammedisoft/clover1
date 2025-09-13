<?php
/**
 * File: includes/api/api-banners.php
 *
 * Registers a custom REST API endpoint for the "dq_banner" post type.
 * The endpoint is registered under the WooCommerce namespace (wc/v3) so that WooCommerce's
 * authentication (Consumer Key/Secret) is applied.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Permission callback using WooCommerce authentication.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return bool|WP_Error True if permitted; WP_Error otherwise.
 */
if ( ! function_exists( 'dq_banner_api_permission_callback' ) ) {
    function dq_banner_api_permission_callback( $request ) {
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

/**
 * Register banner endpoint.
 */
add_action( 'rest_api_init', 'dq_register_banner_endpoints' );
function dq_register_banner_endpoints() {
    // Single endpoint for retrieving all banners with their images and associated products.
    register_rest_route( 'wc/v3', '/banners', array(
        'methods'             => 'GET',
        'callback'            => 'dq_get_banners',
        'permission_callback' => 'dq_banner_api_permission_callback',
    ) );
}

/**
 * Callback to retrieve all banners.
 *
 * For each banner, it retrieves the banner meta "banner_images" which includes
 * the image ID and associated product ID.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response containing banner data.
 */
function dq_get_banners( $request ) {
    $args  = array(
        'post_type'      => 'dq_banner',
        'posts_per_page' => -1,
    );
    $query = new WP_Query( $args );
    $data  = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $banner_id     = get_the_ID();
            $banner_images = get_post_meta( $banner_id, 'banner_images', true );
            $banner_images = ! empty( $banner_images ) ? maybe_unserialize( $banner_images ) : array();
            $images        = array();

            if ( ! empty( $banner_images ) && is_array( $banner_images ) ) {
                foreach ( $banner_images as $banner ) {
                    $image_data = array(
  
                        'image'  => $banner['image_id'],
                        'product' => $banner['product_id'],
                    );
                    $images[] = $image_data;
                }
            }

            $data[] = array(
                'id'            => $banner_id,
                'title'         => get_the_title( $banner_id ),
                'banners' => $images,
            );
        }
        wp_reset_postdata();
    }

    return rest_ensure_response( $data );
}
