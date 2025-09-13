<?php
/**
 * File: includes/branch-tax.php
 * Purpose:
 * 1) Override WooCommerce taxable & store base addresses with the selected branch’s.
 * 2) Force every cart item (and shipping) to use the branch’s tax class, 
 *    defaulting empty to 'standard'.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper: get current branch_id from session.
 *
 * @return int
 */
function dq_get_current_branch_id() {
    if ( WC()->session && WC()->session->get( 'current_branch_id' ) ) {
        return (int) WC()->session->get( 'current_branch_id' );
    }
    return 0;
}

/**
 * 1) Override customer taxable address (high priority so it applies before tax calc).
 */
add_filter( 'woocommerce_customer_taxable_address', 'dq_branch_taxable_address', 5 );
function dq_branch_taxable_address( $address ) {
    $bid = dq_get_current_branch_id();
    if ( ! $bid ) {
        return $address;
    }
    // fetch branch address parts
    $c = get_post_meta( $bid, 'branch_country',  true ) ?: $address[0];
    $s = get_post_meta( $bid, 'branch_state',    true ) ?: $address[1];
    $p = get_post_meta( $bid, 'branch_postcode', true ) ?: $address[2];
    $t = get_post_meta( $bid, 'branch_city',     true ) ?: $address[3];
    return [ $c, $s, $p, $t ];
}

/**
 * 2) Override shop base address (for “Store base address” tax mode).
 */
add_filter( 'woocommerce_store_address',   'dq_store_address_override',   5 );
add_filter( 'woocommerce_store_address_2', 'dq_store_address_override2',  5 );
add_filter( 'woocommerce_store_city',      'dq_store_city_override',      5 );
add_filter( 'woocommerce_store_postcode',  'dq_store_postcode_override',  5 );
add_filter( 'woocommerce_store_country',   'dq_store_country_override',   5 );
add_filter( 'woocommerce_store_state',     'dq_store_state_override',     5 );

function dq_store_address_override( $addr ) {
    $bid = dq_get_current_branch_id();
    return $bid ? get_post_meta( $bid, 'branch_address', true ) : $addr;
}
function dq_store_address_override2( $addr2 ) {
    $bid = dq_get_current_branch_id();
    return $bid ? get_post_meta( $bid, 'branch_address_2', true ) : '';
}
function dq_store_city_override( $city ) {
    $bid = dq_get_current_branch_id();
    return $bid ? get_post_meta( $bid, 'branch_city', true ) : $city;
}
function dq_store_postcode_override( $postcode ) {
    $bid = dq_get_current_branch_id();
    return $bid ? get_post_meta( $bid, 'branch_postcode', true ) : $postcode;
}
function dq_store_country_override( $country ) {
    $bid = dq_get_current_branch_id();
    return $bid ? get_post_meta( $bid, 'branch_country', true ) : $country;
}
function dq_store_state_override( $state ) {
    $bid = dq_get_current_branch_id();
    return $bid ? get_post_meta( $bid, 'branch_state', true ) : $state;
}

/**
 * 3) Override each cart item’s tax class to the branch’s, 
 *    defaulting empty to 'standard'.
 */
add_filter( 'woocommerce_cart_item_tax_class', 'dq_branch_cart_item_tax_class', 20, 3 );
function dq_branch_cart_item_tax_class( $tax_class, $cart_item, $cart_key ) {
    $bid = dq_get_current_branch_id();
    if ( ! $bid ) {
        return $tax_class;
    }
    $bc = get_post_meta( $bid, 'branch_tax_class', true );
    // if the branch has no explicit class, force 'standard'
    return $bc !== '' ? $bc : 'standard';
}

/**
 * 4) Override shipping method tax class likewise.
 */
add_filter( 'woocommerce_shipping_tax_class', 'dq_branch_shipping_tax_class', 20, 2 );
function dq_branch_shipping_tax_class( $shipping_tax_class, $package ) {
    $bid = dq_get_current_branch_id();
    if ( ! $bid ) {
        return $shipping_tax_class;
    }
    $bc = get_post_meta( $bid, 'branch_tax_class', true );
    return $bc !== '' ? $bc : 'standard';
}
