<?php
/**
 * File: dashboard-qahwtea/includes/my-account-subscriptions.php
 *
 * Adds a "My Subscriptions" endpoint to the My Account page.
 */

// Register new endpoint.
function dq_add_my_subscriptions_endpoint() {
    add_rewrite_endpoint( 'my-subscriptions', EP_ROOT | EP_PAGES );

}
add_action( 'init', 'dq_add_my_subscriptions_endpoint' );

// Add query variable for the endpoint.
function dq_my_subscriptions_query_vars( $vars ) {
    $vars[] = 'my-subscriptions';
    return $vars;
}
add_filter( 'query_vars', 'dq_my_subscriptions_query_vars', 0 );

// Insert endpoint into the My Account menu.
function dq_add_my_subscriptions_link_my_account( $items ) {
    $items['my-subscriptions'] = 'My Subscriptions';
    return $items;
}
add_filter( 'woocommerce_account_menu_items', 'dq_add_my_subscriptions_link_my_account' );

// Display content for the My Subscriptions endpoint.
function dq_my_subscriptions_content() {
    // Get the current user ID.
    $user_id = get_current_user_id();
    
    // Query the user's subscription records.
    $args = array(
        'post_type'      => 'dq_user_subscription',
        'post_status'    => 'publish',
        'author'         => $user_id,
        'posts_per_page' => -1,
    );
    $subscriptions = get_posts( $args );
    
    echo '<h2>My Subscriptions</h2>';
    if ( ! empty( $subscriptions ) ) {
        echo '<table>';
        echo '<thead><tr>
                <th>Subscription ID</th>
                <th>Order ID</th>
                <th>Product</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Actions</th>
              </tr></thead>';
        echo '<tbody>';
        foreach ( $subscriptions as $subscription ) {
            $order_id             = get_post_meta( $subscription->ID, 'order_id', true );
            $product_id           = get_post_meta( $subscription->ID, 'product_id', true );
            $subscription_plan_id = get_post_meta( $subscription->ID, 'subscription_plan_id', true );
            $status               = get_post_meta( $subscription->ID, 'subscription_status', true );
            
            // If the status is cancelled, show "Activate" button; otherwise, show "Cancel" button.
            if ( $status === 'cancelled' ) {
                $action_label = 'Activate';
                $action_value = 'activate';
            } else {
                $action_label = 'Cancel';
                $action_value = 'cancel';
            }
            
            // Build the URL using the unified GET keys.
            $action_url = esc_url( add_query_arg( array(
                'action'          => $action_value,
                'subscription_id' => $subscription->ID,
            ) ) );
            
            echo '<tr>';
            echo '<td>' . esc_html( $subscription->ID ) . '</td>';
            echo '<td>' . esc_html( $order_id ) . '</td>';
            echo '<td>' . esc_html( get_the_title( $product_id ) ) . '</td>';
            echo '<td>' . esc_html( get_the_title( $subscription_plan_id ) ) . '</td>';
            echo '<td>' . esc_html( ucfirst( $status ) ) . '</td>';
            echo '<td><a href="' . $action_url . '">' . esc_html( $action_label ) . '</a></td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>You have no active subscriptions.</p>';
    }
}
add_action( 'woocommerce_account_my-subscriptions_endpoint', 'dq_my_subscriptions_content' );

// Handle subscription cancellation and activation for clients.
function dq_handle_subscription_status_change() {
    if ( is_user_logged_in() ) {
        $user_id = get_current_user_id();

        // Cancel Subscription.
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'cancel' && isset( $_GET['subscription_id'] ) ) {
            $subscription_id = absint( $_GET['subscription_id'] );
            $subscription    = get_post( $subscription_id );
            if ( $subscription && $subscription->post_type === 'dq_user_subscription' && $subscription->post_author == $user_id ) {
                update_post_meta( $subscription_id, 'subscription_status', 'cancelled' );
                clean_post_cache( $subscription_id ); // Clear cache to reflect the updated value.
                wc_add_notice( 'Subscription cancelled successfully.', 'success' );
                wp_redirect( wc_get_account_endpoint_url( 'my-subscriptions' ) );
                exit;
            }
        }

        // Activate Subscription.
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'activate' && isset( $_GET['subscription_id'] ) ) {
            $subscription_id = absint( $_GET['subscription_id'] );
            $subscription    = get_post( $subscription_id );
            if ( $subscription && $subscription->post_type === 'dq_user_subscription' && $subscription->post_author == $user_id ) {
                update_post_meta( $subscription_id, 'subscription_status', 'active' );
                clean_post_cache( $subscription_id );
                wc_add_notice( 'Subscription activated successfully.', 'success' );
                wp_redirect( wc_get_account_endpoint_url( 'my-subscriptions' ) );
                exit;
            }
        }
    }
}
add_action( 'template_redirect', 'dq_handle_subscription_status_change' );
?>
