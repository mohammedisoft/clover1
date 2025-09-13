<?php
/**
 * File: dashboard-qahwtea/includes/class-dq-user-subscription.php
 *
 * Admin page to manage user subscriptions.
 */

function dq_register_manage_subscriptions_page() {
    add_menu_page(
        'Manage Subscriptions',        // Page title.
        'Subscriptions',               // Menu title.
        'manage_options',              // Capability.
        'dq-manage-subscriptions',     // Menu slug.
        'dq_render_manage_subscriptions_page', // Callback function.
        'dashicons-list-view',         // Icon.
        25                             // Position.
    );
}
add_action( 'admin_menu', 'dq_register_manage_subscriptions_page' );

function dq_render_manage_subscriptions_page() {
    // Check if the user has the required capability.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    // Process actions (e.g., updating subscription status) if any.
    if ( isset( $_GET['action'] ) && isset( $_GET['subscription_id'] ) ) {
        $subscription_id = absint( $_GET['subscription_id'] );
        $action = sanitize_text_field( $_GET['action'] );

        if ( $action === 'cancel' ) {
            update_post_meta( $subscription_id, 'subscription_status', 'cancelled' );
            echo '<div class="notice notice-success is-dismissible"><p>Subscription cancelled successfully.</p></div>';
        } elseif ( $action === 'activate' ) {
            update_post_meta( $subscription_id, 'subscription_status', 'active' );
            echo '<div class="notice notice-success is-dismissible"><p>Subscription activated successfully.</p></div>';
        }
        // Additional actions (e.g., modifying renewal settings) can be added here.
    }

    // Retrieve all subscription records.
    $args = array(
        'post_type'      => 'dq_user_subscription',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    );
    $subscriptions = get_posts( $args );
    ?>
    <div class="wrap">
        <h1>Manage User Subscriptions</h1>
        <?php if ( ! empty( $subscriptions ) ) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order ID</th>
                        <th>Product</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>Auto Renew</th>
                        <th>Payment Method</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $subscriptions as $subscription ) : 
                        $order_id                = get_post_meta( $subscription->ID, 'order_id', true );
                        $product_id              = get_post_meta( $subscription->ID, 'product_id', true );
                        $subscription_plan_id    = get_post_meta( $subscription->ID, 'subscription_plan_id', true );
                        $subscription_plan_title = get_the_title( $subscription_plan_id );
                        $subscription_status     = get_post_meta( $subscription->ID, 'subscription_status', true );
                        $start_date              = get_post_meta( $subscription->ID, 'subscription_start_date', true );
                        $auto_renew              = get_post_meta( $subscription->ID, 'auto_renew', true );
                        $payment_method          = get_post_meta( $subscription->ID, 'payment_method', true );

                        // Build URLs to change the subscription status.
                        $cancel_url = add_query_arg( array(
                            'page'            => 'dq-manage-subscriptions',
                            'action'          => 'cancel',
                            'subscription_id' => $subscription->ID,
                        ), admin_url( 'admin.php' ) );

                        $activate_url = add_query_arg( array(
                            'page'            => 'dq-manage-subscriptions',
                            'action'          => 'activate',
                            'subscription_id' => $subscription->ID,
                        ), admin_url( 'admin.php' ) );
                        ?>
                        <tr>
                            <td><?php echo esc_html( $subscription->ID ); ?></td>
                            <td><?php echo esc_html( $order_id ); ?></td>
                            <td><?php echo esc_html( get_the_title( $product_id ) ); ?></td>
                            <td><?php echo esc_html( $subscription_plan_title ); ?></td>
                            <td><?php echo esc_html( ucfirst( $subscription_status ) ); ?></td>
                            <td><?php echo esc_html( $start_date ); ?></td>
                            <td><?php echo esc_html( $auto_renew ); ?></td>
                            <td><?php echo esc_html( $payment_method ); ?></td>
                            <td>
                                <?php if ( $subscription_status !== 'cancelled' ) : ?>
                                    <a href="<?php echo esc_url( $cancel_url ); ?>">Cancel</a>
                                <?php else: ?>
                                    <a href="<?php echo esc_url( $activate_url ); ?>">Activate</a>
                                <?php endif; ?>
                                <!-- Additional links for modifying renewal settings or other options can be added here -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No subscriptions found.</p>
        <?php endif; ?>
    </div>
    <?php
}
