<?php
/**
 * File: dashboard-qahwtea/includes/api-branches.php
 *
 * Registers custom REST API endpoints for the "dq_branch" post type,
 * under the WooCommerce namespace (wc/v3), including branch‐specific tax objects.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Permission callback using WooCommerce authentication.
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
            [ 'status' => rest_authorization_required_code() ]
        );
    }
}

/**
 * Register our REST endpoints.
 */
add_action( 'rest_api_init', function() {
    register_rest_route( 'wc/v3', '/branches', [
        'methods'             => 'GET',
        'callback'            => 'dq_get_branches',
        'permission_callback' => 'dq_woocommerce_api_permission_callback',
    ] );
    register_rest_route( 'wc/v3', '/branches/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'dq_get_single_branch',
        'permission_callback' => 'dq_woocommerce_api_permission_callback',
    ] );
} );

/**
 * Collect every WC tax rate, keyed by rate_id.
 *
 * @return array [ rate_id => rate_data_array, ... ]
 */
function dq_get_all_wc_rates() {
    $all = [];
    // Standard rates (empty class slug)
    $standard = WC_Tax::get_rates_for_tax_class( '' );
    if ( is_array( $standard ) ) {
        $all = $standard;
    }
    // Other classes
    foreach ( WC_Tax::get_tax_classes() as $class ) {
        $slug  = sanitize_title( $class );
        $rates = WC_Tax::get_rates_for_tax_class( $slug );
        if ( is_array( $rates ) ) {
            $all = array_merge( $all, $rates );
        }
    }
    return $all;
}

/**
 * Convert a raw rate array into the full WC REST tax object.
 *
 * @param array $r
 * @return array
 */
function dq_build_tax_object_from_rate( $r ) {
    $rest = rest_url( 'wc/v3' );
    return [
        'id'        => isset( $r->tax_rate_id )       ? (int)   $r->tax_rate_id       : 0,
        'country'   => isset( $r->tax_rate_country )  ?           $r->tax_rate_country  : '',
        'state'     => isset( $r->tax_rate_state )    ?           $r->tax_rate_state    : '',
        'postcode'  => isset( $r->tax_rate_postcode ) ?           $r->tax_rate_postcode : '',
        'city'      => isset( $r->tax_rate_city )     ?           $r->tax_rate_city     : '',
        'rate'      => isset( $r->tax_rate )          ? (string) wc_format_decimal( $r->tax_rate, 4 ) : '0.0000',
        'name'      => isset( $r->tax_rate_name )     ?           $r->tax_rate_name     : '',
        'priority'  => isset( $r->tax_rate_priority ) ? (int)    $r->tax_rate_priority : 0,
        'compound'  => isset( $r->tax_rate_compound ) ? (bool)   $r->tax_rate_compound : false,
        'shipping'  => isset( $r->tax_rate_shipping ) ? (bool)   $r->tax_rate_shipping : false,
        'order'     => 0,
        'class'     => isset( $r->tax_rate_class )    && $r->tax_rate_class 
                        ? $r->tax_rate_class 
                        : 'standard',
        'postcodes' => [],
        'cities'    => [],
        '_links'    => [
            'self'       => [
                [
                    'href'        => "{$rest}/taxes/" . ( isset( $r->tax_rate_id ) ? $r->tax_rate_id : 0 ),
                    'targetHints' => [ 'allow' => [ 'GET','POST','PUT','PATCH','DELETE' ] ],
                ],
            ],
            'collection' => [
                [ 'href' => "{$rest}/taxes" ],
            ],
        ],
    ];
}

/**
 * GET /wc/v3/branches
 */

 function dq_get_branches( $request ) {
    $q    = new WP_Query( [
        'post_type'      => 'dq_branch',
        'posts_per_page' => -1,
    ] );
    $data = [];

    while ( $q->have_posts() ) {
        $q->the_post();
        $id      = get_the_ID();

        // 1) Pull the single saved rate ID for this branch
        $rate_id = get_post_meta( $id, 'branch_tax_rate_id', true );

        // 2) Use the same helper to fetch & build exactly that one rate
        $tax_object = dq_get_tax_object_by_id( $rate_id );

        $data[] = [
            'id'              => $id,
            'title'           => get_the_title( $id ),
            'mobile'          => get_post_meta( $id, 'branch_mobile',     true ),
            'address'         => get_post_meta( $id, 'branch_address',    true ),
            'latitude'        => get_post_meta( $id, 'branch_latitude',   true ),
            'longitude'       => get_post_meta( $id, 'branch_longitude',  true ),
            'delivery_radius' => get_post_meta( $id, 'delivery_radius',   true ),
            'hours_mode'      => get_post_meta( $id, 'branch_hours_mode', true ),
            'hours'           => get_post_meta( $id, 'branch_hours',      true ),
            'start_date'      => get_post_meta( $id, 'branch_start_date', true ) ?: 'Not started',
            // 3) Wrap it in an array (or empty array if null)
            'taxes'           => $tax_object ? [ $tax_object ] : [],
        ];
    }
    wp_reset_postdata();

    return rest_ensure_response( $data );
}




/**
 * Fetch a single WC tax rate by ID via direct DB query,
 * then convert it to the REST tax schema.
 *
 * @param int $rate_id
 * @return array|null
 */
function dq_get_tax_object_by_id( $rate_id ) {
    global $wpdb;
    $rate_id = absint( $rate_id );
    if ( ! $rate_id ) {
        return null;
    }
    $table = $wpdb->prefix . 'woocommerce_tax_rates';
    $row   = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM `$table` WHERE tax_rate_id = %d",
            $rate_id
        )
    );
    return $row ? dq_build_tax_object_from_rate( $row ) : null;
}



/**
 * GET /wc/v3/branches/{id}
 */
/**
 * GET /wc/v3/branches/{id}
 */
function dq_get_single_branch( $request ) {
    $id   = (int) $request->get_param( 'id' );
    $post = get_post( $id );
    if ( ! $post || $post->post_type !== 'dq_branch' ) {
        return new WP_Error( 'no_branch', __( 'Branch not found' ), [ 'status' => 404 ] );
    }

    // 1) Pull the single rate ID saved to this branch
    $rate_id  = get_post_meta( $id, 'branch_tax_rate_id', true );

    // 2) Fetch exactly that one rate from the DB & build the REST object
    $tax_obj  = dq_get_tax_object_by_id( $rate_id );  // uses direct DB query helper

    // 3) Build your branch payload, fixing the delivery_radius key
    $data = [
        'id'              => $id,
        'title'           => get_the_title( $id ),
        'mobile'          => get_post_meta( $id, 'branch_mobile',       true ),
        'address'         => get_post_meta( $id, 'branch_address',      true ),
        'latitude'        => get_post_meta( $id, 'branch_latitude',     true ),
        'longitude'       => get_post_meta( $id, 'branch_longitude',    true ),
        'delivery_radius' => get_post_meta( $id, 'delivery_radius',     true ),  // <-- corrected
        'hours_mode'      => get_post_meta( $id, 'branch_hours_mode',   true ),
        'hours'           => get_post_meta( $id, 'branch_hours',        true ),
        'start_date'      => get_post_meta( $id, 'branch_start_date',   true ) ?: 'Not started',
        'taxes'           => $tax_obj ? [ $tax_obj ] : [],                       // single rate only
    ];

    return rest_ensure_response( $data );
}
