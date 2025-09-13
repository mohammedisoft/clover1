<?php

/**
 * admin/product-subscription-meta.php
 * 
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add Subscription Meta Box to WooCommerce Products.
 */
function dq_add_product_subscription_meta_box() {
    add_meta_box(
        'dq_product_subscriptions',
        'Subscription Options',
        'dq_render_product_subscriptions_meta_box',
        'product',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'dq_add_product_subscription_meta_box' );

/**
 * Render the Subscription Selection Meta Box.
 */
function dq_render_product_subscriptions_meta_box( $post ) {
    // Get the previously saved subscription selections
    $selected_subscriptions = get_post_meta( $post->ID, 'dq_product_subscriptions', true );
    if ( ! is_array( $selected_subscriptions ) ) {
        $selected_subscriptions = array();
    }

    // Fetch available subscription plans from "dq_subscription" post type
    $args = array(
        'post_type'      => 'dq_subscription',
        'posts_per_page' => -1,
    );
    $subscriptions = get_posts( $args );

    if ( ! empty( $subscriptions ) ) {
        echo '<p>Select subscription options for this product:</p>';
        foreach ( $subscriptions as $subscription ) {
            $sub_id    = $subscription->ID;
            $sub_title = get_the_title( $sub_id );
            ?>
            <label>
                <input type="checkbox" name="dq_product_subscriptions[]" value="<?php echo esc_attr( $sub_id ); ?>"
                    <?php checked( in_array( $sub_id, $selected_subscriptions ) ); ?>>
                <?php echo esc_html( $sub_title ); ?>
            </label><br>
            <?php
        }
    } else {
        echo '<p>No subscriptions found. Please add subscriptions first.</p>';
    }
}

/**
 * Save the Selected Subscription Options for the Product.
 */
function dq_save_product_subscription_meta( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['dq_product_subscriptions'] ) && is_array( $_POST['dq_product_subscriptions'] ) ) {
        update_post_meta( $post_id, 'dq_product_subscriptions', array_map( 'absint', $_POST['dq_product_subscriptions'] ) );
    } else {
        delete_post_meta( $post_id, 'dq_product_subscriptions' );
    }
}
add_action( 'save_post', 'dq_save_product_subscription_meta' );
