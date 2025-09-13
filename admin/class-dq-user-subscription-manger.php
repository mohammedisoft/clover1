<?php
/**
 * File: dashboard-qahwtea/admin/class-dq-user-subscription-manager.php
 *
 * Admin page to manage user subscriptions using WP_List_Table.
 */

require_once plugin_dir_path( __FILE__ ) . 'class-dq-user-subscription-list-table.php';

/**
 * Custom search filter to allow searching in post title OR meta fields (customer_name, customer_email).
 */
function dq_modify_subscription_search( $search, $wp_query ) {
    global $wpdb;

    if ( empty( $search ) || $wp_query->get('post_type') !== 'dq_user_subscription' ) {
        return $search;
    }

    $search_term = esc_sql( $wp_query->get( 's' ) );
    if ( ! $search_term ) {
        return $search;
    }

    // Custom search clause for searching post title OR meta fields.
    $search = " AND (
        ({$wpdb->posts}.post_title LIKE '%{$search_term}%')
        OR EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} pm
            WHERE pm.post_id = {$wpdb->posts}.ID
            AND pm.meta_key IN ('customer_name', 'customer_email')
            AND pm.meta_value LIKE '%{$search_term}%'
        )
    ) ";

    return $search;
}
add_filter( 'posts_search', 'dq_modify_subscription_search', 10, 2 );

/**
 * Register the admin menu page.
 */
function dq_register_manage_subscriptions_page() {
    add_menu_page(
        'Manage Subscriptions',        
        'Subscriptions',               
        'manage_options',              
        'dq-manage-subscriptions',     
        'dq_render_manage_subscriptions_page', 
        'dashicons-list-view',         
        25                             
    );
}
add_action( 'admin_menu', 'dq_register_manage_subscriptions_page' );

/**
 * Render the admin page content.
 */
function dq_render_manage_subscriptions_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }

    dq_process_subscription_actions();

    $list_table = new DQ_User_Subscription_List_Table();
    $list_table->prepare_items();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Manage User Subscriptions</h1>
        <form method="get">
            <input type="hidden" name="page" value="dq-manage-subscriptions" />
            <?php $list_table->search_box( 'Search Subscriptions', 'subscription' ); ?>
        </form>
        <form method="post">
            <?php $list_table->display(); ?>
        </form>
    </div>
    <?php
}

/**
 * Handle subscription actions: cancel, activate, delete.
 */
function dq_process_subscription_actions() {
    if ( isset( $_GET['action'] ) && isset( $_GET['subscription_id'] ) ) {
        $subscription_id = absint( $_GET['subscription_id'] );
        $action          = sanitize_text_field( $_GET['action'] );

        if ( $action === 'cancel' ) {
            update_post_meta( $subscription_id, 'subscription_status', 'cancelled' );
            clean_post_cache( $subscription_id );
            echo '<div class="notice notice-success is-dismissible"><p>Subscription cancelled successfully.</p></div>';
        } elseif ( $action === 'activate' ) {
            update_post_meta( $subscription_id, 'subscription_status', 'active' );
            clean_post_cache( $subscription_id );
            echo '<div class="notice notice-success is-dismissible"><p>Subscription reactivated successfully.</p></div>';
        } elseif ( $action === 'delete' ) {
            wp_delete_post( $subscription_id, true ); // Permanent delete.
            echo '<div class="notice notice-success is-dismissible"><p>Subscription deleted successfully.</p></div>';
        }

        wp_redirect( admin_url( 'admin.php?page=dq-manage-subscriptions' ) );
        exit;
    }
}
?>
