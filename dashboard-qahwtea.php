<?php
// File: dashboard-qahwtea/dashboard-qahwtea.php

/**
 * Plugin Name: Dashboard Qahwtea
 * Plugin URI:  https://zaadtech.com
 * Description: Advanced WooCommerce management plugin for coffee shops with Flutter app integration.
 * Version:     1.0
 * Author:      zaadtech
 * Author URI:  https://zaadtech.com
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include core admin files
include_once plugin_dir_path( __FILE__ ) . 'admin/admin-page.php';
include_once plugin_dir_path( __FILE__ ) . 'admin/product-options-admin.php';
include_once plugin_dir_path( __FILE__ ) . 'admin/product-subscription-meta.php';
include_once plugin_dir_path( __FILE__ ) . 'admin/class-dq-product-group-meta.php';

// Include core front-end and functionality files
include_once plugin_dir_path( __FILE__ ) . 'includes/product-options.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/coupons.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/notifications.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/payments.php';

// Include custom post type registration files
require_once plugin_dir_path( __FILE__ ) . 'includes/class-dq-branch.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-dq-branch-meta.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/functions.php'; 
include_once plugin_dir_path( __FILE__ ) . 'includes/class-dq-product-group.php';

// Include subscription related files
require_once plugin_dir_path( __FILE__ ) . 'includes/class-dq-subscription.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-dq-subscription-meta.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/custom-addons-cart.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/class-dq-subscription-taxonomy.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/ajax-functions.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/integration-woocommerce.php';

// Include subscription block registration file (registers block and enqueues its script)
include_once plugin_dir_path( __FILE__ ) . 'includes/blocks/subscription-list.php';

// Include new subscription files:
// 1. File to create a user subscription record after order completion.
require_once plugin_dir_path( __FILE__ ) . 'includes/order-subscription-creator.php';
// 2. File to register the custom post type for user subscriptions.
require_once plugin_dir_path( __FILE__ ) . 'admin/class-dq-user-subscription-manger.php';
// 3. File to add a "My Subscriptions" endpoint to the My Account page.
include_once plugin_dir_path( __FILE__ ) . 'includes/my-account-subscriptions.php';

require_once plugin_dir_path( __FILE__ ) . 'includes/class-dq-user-subscription-cpt.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/api/api_subscription.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/api/api_branches.php';



include_once plugin_dir_path( __FILE__ ) . 'includes/api/api-product-groups.php';





include_once plugin_dir_path( __FILE__ ) . 'admin/class-dq-banner-meta.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/class-dq-banner.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/api/api_banner.php';
//include_once plugin_dir_path( __FILE__ ) . 'includes/api/api-calculate-price.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/api/api.php';
//include_once plugin_dir_path( __FILE__ ) . 'includes/custom-addons-price-calculation.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/branch-tax.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/branch-session.php';
include_once plugin_dir_path( __FILE__ ) . 'includes/branch-checkout.php';



include_once plugin_dir_path( __FILE__ ) . 'includes/wizard-functions.php';




/**
 * Enqueue CSS and JS files for frontend and backend.
 */
function dq_enqueue_assets() {
    // Enqueue frontend styles only on the frontend.
    if ( ! is_admin() ) {
        // Get the path for the frontend CSS file.
        $frontend_style_path = plugin_dir_path( __FILE__ ) . 'assets/style_frontend.css';

        // Check if the file exists and use filemtime() for versioning.
        if ( file_exists( $frontend_style_path ) ) {
            wp_enqueue_style(
                'dq_frontend_style',
                plugin_dir_url( __FILE__ ) . 'assets/style_frontend.css',
                array(),
                filemtime( $frontend_style_path ) // Auto-update version based on last modification time.
            );
        }
    }

    // Enqueue backend styles and scripts only in the admin panel.
    if ( is_admin() ) {
        // Get the path for the backend CSS file.
        $backend_style_path = plugin_dir_path( __FILE__ ) . 'assets/style_backend.css';

        // Check if the file exists and use filemtime() for versioning.
        if ( file_exists( $backend_style_path ) ) {
            wp_enqueue_style(
                'dq_backend_style',
                plugin_dir_url( __FILE__ ) . 'assets/style_backend.css',
                array(),
                filemtime( $backend_style_path ) // Auto-update version based on last modification time.
            );
        }

        // Get the path for the backend JS file.
        $script_path = plugin_dir_path( __FILE__ ) . 'assets/script_backend.js';
        if ( file_exists( $script_path ) ) {
            wp_enqueue_script(
                'dq_backend_script',
                plugin_dir_url( __FILE__ ) . 'assets/script_backend.js',
                array( 'jquery' ),
                filemtime( $script_path ), // Auto-update version based on last modification time.
                true
            );
        }
    }
}

add_action( 'wp_enqueue_scripts', 'dq_enqueue_assets' );
add_action( 'admin_enqueue_scripts', 'dq_enqueue_assets' );




/**
 * Plugin activation hook.
 */
function dq_activate() {
    add_option( 'dq_notification_enabled', 'yes' );
}
register_activation_hook( __FILE__, 'dq_activate' );

/**
 * Plugin deactivation hook.
 */
function dq_deactivate() {
    // Cleanup actions if needed.
}
register_deactivation_hook( __FILE__, 'dq_deactivate' );






/**
 * Render the branch-selection modal in the footer on every front-end page.
 */
add_action( 'wp_footer', 'dq_render_branch_selector_modal' );
function dq_render_branch_selector_modal() {
    if ( is_admin() ) {
        return;
    }
    $branches = get_posts( [
        'post_type'   => 'dq_branch',
        'post_status' => 'publish',
        'numberposts' => -1,
    ] );
    ?>
    <div id="dq-branch-selector-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
      <div style="background:#fff; max-width:400px; margin:100px auto; padding:20px; border-radius:4px; position:relative;">
        <h2>Select Your Branch</h2>
        <select id="branch-selector" style="width:100%; padding:8px; font-size:16px;">
          <option value=""><?php esc_html_e( '— Choose a branch —', 'dashboard-qahwtea' ); ?></option>
          <?php foreach ( $branches as $b ) : ?>
            <option value="<?php echo esc_attr( $b->ID ); ?>">
              <?php echo esc_html( get_the_title( $b ) ); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button id="branch-selector-confirm" style="margin-top:12px;padding:8px 12px;">
          <?php esc_html_e( 'Confirm', 'dashboard-qahwtea' ); ?>
        </button>
        <button id="branch-selector-close" style="position:absolute; top:8px; right:12px; background:none; border:none; font-size:18px; cursor:pointer;">×</button>
      </div>
    </div>
    <?php
}


add_action( 'wp_enqueue_scripts', function(){
    wp_enqueue_script(
        'dq-branch-selector',
        plugin_dir_url( __FILE__ ) . 'assets/script_frontend.js',
        [ 'jquery', 'wc-checkout' ],
        filemtime( plugin_dir_path( __FILE__ ) . 'assets/script_frontend.js' ),
        true
      );
      wp_localize_script( 'dq-branch-selector', 'dq_vars', [
        'ajax_url'           => admin_url( 'admin-ajax.php' ),
        'current_branch_id'  => WC()->session ? WC()->session->get( 'current_branch_id' ) : '',
      ] );
} );

