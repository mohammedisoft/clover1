<?php
/**
 * File: dashboard-qahwtea/admin/class-dq-user-subscription-list-table.php
 *
 * Custom list table for displaying and managing user subscriptions.
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class DQ_User_Subscription_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => 'dq_user_subscription',
            'plural'   => 'dq_user_subscriptions',
            'ajax'     => false,
        ) );
    }

    /**
     * Define table columns.
     */
    public function get_columns() {
        return array(
            'cb'                  => '<input type="checkbox" />',
            'title'               => 'Subscription ID',
            'order_id'            => 'Order ID',
            'product'             => 'Product',
            'plan'                => 'Plan',
            'subscription_status' => 'Status',
            'start_date'          => 'Start Date',
            'customer_name'       => 'Customer Name',
            'customer_email'      => 'Customer Email',
            'actions'             => 'Actions',
        );
    }

    /**
     * Fetch and prepare data for display.
     */
    public function prepare_items() {
        $per_page = 10;
        $current_page = $this->get_pagenum();

        $args = array(
            'post_type'      => 'dq_user_subscription',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $current_page,
        );

        $query = new WP_Query( $args );
        $this->items = $query->posts;

        $this->set_pagination_args( array(
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
            'total_pages' => ceil( $query->found_posts / $per_page ),
        ) );

        $this->_column_headers = array(
            $this->get_columns(),
            array(), 
            array(), 
        );
    }

    /**
     * Render table rows.
     */
    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'title':
                return esc_html( $item->ID );
            case 'order_id':
                return esc_html( get_post_meta( $item->ID, 'order_id', true ) ?: 'N/A' );
            case 'product':
                $product_id = get_post_meta( $item->ID, 'product_id', true );
                return $product_id ? esc_html( get_the_title( $product_id ) ) : 'N/A';
            case 'plan':
                $plan_id = get_post_meta( $item->ID, 'subscription_plan_id', true );
                return $plan_id ? esc_html( get_the_title( $plan_id ) ) : 'N/A';
            case 'subscription_status':
                return esc_html( ucfirst( get_post_meta( $item->ID, 'subscription_status', true ) ?: 'Unknown' ) );
            case 'start_date':
                return esc_html( get_post_meta( $item->ID, 'subscription_start_date', true ) ?: 'Not started' );
            case 'customer_name':
                return esc_html( get_post_meta( $item->ID, 'customer_name', true ) ?: 'Unknown' );
            case 'customer_email':
                return esc_html( get_post_meta( $item->ID, 'customer_email', true ) ?: 'Unknown' );
            case 'actions':
                // إعداد روابط الإجراءات للإدارة.
                $cancel_url = add_query_arg( array(
                    'page'            => 'dq-manage-subscriptions',
                    'action'          => 'cancel',
                    'subscription_id' => $item->ID,
                ), admin_url( 'admin.php' ) );
                $activate_url = add_query_arg( array(
                    'page'            => 'dq-manage-subscriptions',
                    'action'          => 'activate',
                    'subscription_id' => $item->ID,
                ), admin_url( 'admin.php' ) );
                $delete_url = add_query_arg( array(
                    'page'            => 'dq-manage-subscriptions',
                    'action'          => 'delete',
                    'subscription_id' => $item->ID,
                ), admin_url( 'admin.php' ) );

                $status = get_post_meta( $item->ID, 'subscription_status', true );
                $actions = array();
                if ( $status !== 'cancelled' ) {
                    $actions[] = '<a href="' . esc_url( $cancel_url ) . '">Cancel</a>';
                } else {
                    $actions[] = '<a href="' . esc_url( $activate_url ) . '">Reactivate</a>';
                }
                $actions[] = '<a href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'Are you sure you want to delete this subscription?\');" style="color:red;">Delete</a>';

                return implode(' | ', $actions);
            default:
                return '';
        }
    }

    /**
     * Render checkbox for bulk actions.
     */
    protected function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="bulk-ids[]" value="%d" />',
            $item->ID
        );
    }
}
?>
