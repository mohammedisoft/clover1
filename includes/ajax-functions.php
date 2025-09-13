<?php
// File: dashboard-qahwtea/includes/ajax-functions.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure the new wizard functions are available
require_once plugin_dir_path( __FILE__ ) . 'wizard-functions.php';

/**
 * AJAX handler to get products for the wizard's Step 2.
 */
function dq_get_products_for_wizard() {
    if ( !isset( $_POST['category'] ) ) { wp_die(); }

    $category_slug = sanitize_text_field( $_POST['category'] );
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'tax_query'      => array(array('taxonomy' => 'subscription_category', 'field' => 'slug', 'terms' => $category_slug)),
    );
    $products = new WP_Query( $args );

    if ( $products->have_posts() ) {
        while ( $products->have_posts() ) {
            $products->the_post();
            $product_id = get_the_ID();
            $thumbnail_url = get_the_post_thumbnail_url( $product_id, 'medium' ) ?: wc_placeholder_img_src();
            echo '<div class="dq-wizard-selection-btn" onclick="wizard.goToStep(3, ' . esc_attr( $product_id ) . ')">
                    <img src="' . esc_url( $thumbnail_url ) . '" alt="' . get_the_title() . '">
                    <span>' . get_the_title() . '</span>
                  </div>';
        }
    } else {
        echo '<p>No products found in this category.</p>';
    }
    wp_reset_postdata();
    wp_die();
}
add_action( 'wp_ajax_dq_get_products_for_wizard', 'dq_get_products_for_wizard' );
add_action( 'wp_ajax_nopriv_dq_get_products_for_wizard', 'dq_get_products_for_wizard' );

/**
 * AJAX handler to get the customization form for the wizard's Step 3.
 */
function dq_get_subscription_form_for_wizard() {
    if ( !isset( $_POST['product_id'] ) ) { wp_die(); }
    
    $product_id = absint( $_POST['product_id'] );
    global $product;
    $product = wc_get_product( $product_id );

    if ( ! $product ) { wp_die(); }

    $subs = get_post_meta( $product_id, 'dq_product_subscriptions', true );

    // This filter changes the button text
    add_filter( 'woocommerce_product_single_add_to_cart_text', function() { return __( 'Subscribe Now', 'dq' ); }, 20, 1 );

    ob_start();
    ?>
    <style>
        .dq-add-to-cart-wrapper { display: flex; align-items: center; gap: 15px; margin-top: 25px; }
        .dq-add-to-cart-wrapper .quantity { margin: 0; }
        .dq-add-to-cart-wrapper .single_add_to_cart_button { flex-grow: 1; background-color: var(--brand-color); color: white; padding: 15px; font-size: 16px; font-weight: bold; border-radius: 5px; border: none; cursor: pointer; }
        .dq-subscription-plans-container { margin-top: 25px; }
        .dq-subscription-plans-container h4 { margin-bottom: 10px; font-weight: 600; }
        .dq-subscription-plan-label { display: block; background: #fafafa; border: 1px solid #ddd; border-radius: 5px; padding: 12px 15px; margin-bottom: 8px; cursor: pointer; transition: all 0.2s; }
        .dq-subscription-plan-label:hover { border-color: #bbb; }
        .dq-subscription-plan-label input { margin-right: 10px; }
        /* Hiding radio button but keeping it accessible */
        .dq-subscription-plan-label input[type="radio"] { opacity: 0; position: fixed; width: 0; }
        .dq-subscription-plan-label.selected { border-color: var(--brand-color); background-color: #f0f7ff; box-shadow: 0 0 0 2px var(--brand-color); }
    </style>
    <div class="dq-product-options-container">
        <form class="cart" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post" enctype='multipart/form-data'>
            
            <div class="price" style="font-size: 24px; font-weight: bold; margin-bottom: 20px; text-align: left;"><?php echo $product->get_price_html(); ?></div>
            
            <?php 
            // Call the ISOLATED function from wizard-functions.php
            dq_wizard_display_product_addons();
            
            // Display subscription plans with improved styling
            if ( is_array( $subs ) && ! empty( $subs ) ) {
                echo '<div class="dq-subscription-plans-container">';
                echo '<h4>Choose a Subscription Plan:</h4>';
                foreach ( $subs as $sub_id ) {
                    echo '<label class="dq-subscription-plan-label"><input type="radio" name="dq_subscription_plan" value="' . esc_attr( $sub_id ) . '" required> ' . esc_html( get_the_title( $sub_id ) ) . '</label>';
                }
                echo '</div>';
            }
            
            // Wrapper for quantity and button
            echo '<div class="dq-add-to-cart-wrapper">';
            woocommerce_quantity_input( array(), $product, true );
            echo '<button type="submit" name="add-to-cart" value="' . esc_attr( $product->get_id() ) . '" class="single_add_to_cart_button button alt">' . esc_html( $product->single_add_to_cart_text() ) . '</button>';
            echo '</div>';
            ?>
        </form>
    </div>
    <?php
    
    // Call the ISOLATED script function from wizard-functions.php
    dq_wizard_addons_script();
    ?>
    <script>
        // Add a small script for better radio button selection feedback
        jQuery('.dq-subscription-plan-label input[type="radio"]').on('change', function() {
            jQuery('.dq-subscription-plan-label').removeClass('selected');
            if(this.checked) {
                jQuery(this).parent('label').addClass('selected');
            }
        });
    </script>
    <?php
    
    echo ob_get_clean();
    wp_die();
}
add_action( 'wp_ajax_dq_get_subscription_form_for_wizard', 'dq_get_subscription_form_for_wizard' );
add_action( 'wp_ajax_nopriv_dq_get_subscription_form_for_wizard', 'dq_get_subscription_form_for_wizard' );