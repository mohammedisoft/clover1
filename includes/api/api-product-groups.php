<?php
/**
 * File: includes/api/api-product-groups.php
 *
 * Registers a custom REST API endpoint for the "dq_product_group" post type.
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
if ( ! function_exists( 'dq_product_group_api_permission_callback' ) ) {
    function dq_product_group_api_permission_callback( $request ) {
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
 * Register product group endpoint.
 */
add_action( 'rest_api_init', 'dq_register_product_group_endpoints' );
function dq_register_product_group_endpoints() {
    // Single endpoint for retrieving all product groups with filtered products.
    register_rest_route( 'wc/v3', '/product-groups', array(
        'methods'             => 'GET',
        'callback'            => 'dq_get_product_groups',
        'permission_callback' => 'dq_product_group_api_permission_callback',
    ) );
}

/**
 * Callback to retrieve all product groups.
 *
 * For each product group, based on the filter_type the corresponding products are queried:
 * - "most_sold": Queries published products with total_sales > 0 ordered descending.
 * - "recently_added": Queries published products ordered by date (newest first).
 * - "best_products": Uses the manually selected product IDs.
 *
 * The returned group object includes a "selected_products" key that holds the filtered products.
 *
 * @param WP_REST_Request $request The REST API request.
 * @return WP_REST_Response The response containing product group data.
 */
function dq_get_product_groups( $request ) {
    $args  = array(
        'post_type'      => 'dq_product_group',
        'posts_per_page' => -1,
    );
    $query = new WP_Query( $args );
    $data  = array();

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();
            $group_id            = get_the_ID();
            $filter_type         = get_post_meta( $group_id, 'filter_type', true );
            $selected_products_meta = get_post_meta( $group_id, 'selected_products', true );
            $api_product_count   = get_post_meta( $group_id, 'api_product_count', true );
            $api_product_count   = ! empty( $api_product_count ) ? intval( $api_product_count ) : 4; // Default to 4 if not set

            $products = array();

            if ( 'most_sold' === $filter_type ) {
                // Query published products with total_sales > 0, ordered descending by total_sales.
                $args_products = array(
                    'post_type'      => 'product',
                    'posts_per_page' => $api_product_count,
                    'meta_key'       => 'total_sales',
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                    'meta_query'     => array(
                        array(
                            'key'     => 'total_sales',
                            'value'   => 0,
                            'compare' => '>',
                            'type'    => 'NUMERIC'
                        )
                    ),
                    'post_status'    => 'publish',
                );
                $query_products = new WP_Query( $args_products );
                if ( $query_products->have_posts() ) {
                    while ( $query_products->have_posts() ) {
                        $query_products->the_post();
                        $products[] = array(
                            'id'    => get_the_ID(),
                            'title' => get_the_title(),
                        );
                    }
                    wp_reset_postdata();
                }
            } elseif ( 'recently_added' === $filter_type ) {
                // Query published products ordered by publish date descending.
                $args_products = array(
                    'post_type'           => 'product',
                    'posts_per_page'      => $api_product_count,
                    'orderby'             => 'date',
                    'order'               => 'DESC',
                    'post_status'         => 'publish',
                    'ignore_sticky_posts' => true,
                );
                $query_products = new WP_Query( $args_products );
                if ( $query_products->have_posts() ) {
                    while ( $query_products->have_posts() ) {
                        $query_products->the_post();
                        $products[] = array(
                            'id'    => get_the_ID(),
                            'title' => get_the_title(),
                        );
                    }
                    wp_reset_postdata();
                }
            } elseif ( 'best_products' === $filter_type ) {
                // Use the manually selected product IDs.
                $selected_products = ! empty( $selected_products_meta ) ? maybe_unserialize( $selected_products_meta ) : array();
                if ( ! empty( $selected_products ) && is_array( $selected_products ) ) {
                    $args_products = array(
                        'post_type'      => 'product',
                        'posts_per_page' => $api_product_count,
                        'post__in'       => $selected_products,
                        'orderby'        => 'post__in',
                        'post_status'    => 'publish',
                    );
                    $query_products = new WP_Query( $args_products );
                    if ( $query_products->have_posts() ) {
                        while ( $query_products->have_posts() ) {
                            $query_products->the_post();
                            $products[] = array(
                                'id'    => get_the_ID(),
                                'title' => get_the_title(),
                            );
                        }
                        wp_reset_postdata();
                    }
                }
            }

            $data[] = array(
                'id'                => $group_id,
                'title'             => get_the_title( $group_id ),
                'filter_type'       => $filter_type,
                'api_product_count' => $api_product_count,
                'selected_products' => $products, // Contains the filtered products.
            );
        }
        wp_reset_postdata();
    }

    return rest_ensure_response( $data );
}
