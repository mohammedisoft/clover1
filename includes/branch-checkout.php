<?php
/**
 * File: dashboard-qahwtea/includes/branch-checkout.php
 * Purpose: Inject a branch selector into the Checkout form via actions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1) Render the branch <select> before the customer details.
 */
add_action( 'woocommerce_checkout_before_customer_details', 'dq_render_branch_field' );
function dq_render_branch_field() {
    $branches = get_posts( [
        'post_type'   => 'dq_branch',
        'post_status' => 'publish',
        'numberposts' => -1,
    ] );

    echo '<div class="checkout-branch-selector" style="margin:1em 0;padding:1em;background:#f9f9f9;border:1px solid #ddd;">';
    echo '<label for="branch-selector">' . esc_html__( 'Select Your Branch', 'dashboard-qahwtea' ) . '</label>';
    echo '<select name="branch_selector" id="branch-selector" style="width:100%;padding:8px;margin-top:4px;">';
    echo '<option value="">' . esc_html__( '— choose a branch —', 'dashboard-qahwtea' ) . '</option>';

    $current = WC()->session ? WC()->session->get( 'current_branch_id' ) : 0;
    foreach ( $branches as $b ) {
        $sel = ( $current == $b->ID ) ? ' selected' : '';
        printf(
            '<option value="%1$d"%2$s>%3$s</option>',
            intval( $b->ID ),
            $sel,
            esc_html( get_the_title( $b ) )
        );
    }

    echo '</select>';
    echo '</div>';

    // DEBUG: show session + branch meta
   /* if ( $current ) {
        echo '<div style="background:#fffbdd;border:1px solid #ddd;padding:10px;margin:1em 0;">';
        echo '<strong>🔍 Session branch_id:</strong> ' . esc_html( $current ) . '<br>';
        $keys = [ 'branch_mobile','branch_address','branch_latitude','branch_longitude','delivery_radius','branch_tax_class','branch_tax_rate_id' ];
        echo '<ul>';
        foreach ( $keys as $key ) {
            $val = get_post_meta( $current, $key, true );
            echo '<li><code>' . esc_html( $key ) . '</code>: ' . esc_html( var_export( $val, true ) ) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }*/
}

/**
 * 2) Validate that the user picked a branch.
 */
add_action( 'woocommerce_checkout_process', 'dq_validate_branch_choice' );
function dq_validate_branch_choice() {
    if ( empty( $_POST['branch_selector'] ) ) {
        wc_add_notice( __( 'Please select a branch.', 'dashboard-qahwtea' ), 'error' );
    }
}

/**
 * 3) Save the branch choice into WC()->session so taxes use it.
 */
add_action( 'woocommerce_checkout_update_order_meta', 'dq_save_branch_choice_to_session' );
function dq_save_branch_choice_to_session( $order_id ) {
    if ( ! empty( $_POST['branch_selector'] ) ) {
        $branch_id = intval( $_POST['branch_selector'] );
        WC()->session->set( 'current_branch_id', $branch_id );
        update_post_meta( $order_id, 'branch_selector', $branch_id );
    }
}
