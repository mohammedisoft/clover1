<?php
/**
 * Plugin Name: Custom Product Addons
 * Description: Adds a custom tab for product addons with main and sub options.
 * Version: 1.2
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Add Custom Tab to WooCommerce Product Data Panel
add_filter( 'woocommerce_product_data_tabs', 'cpa_add_product_data_tab' );
function cpa_add_product_data_tab( $tabs ) {
    $tabs['custom_addons'] = array(
        'label'    => __( 'Addons', 'custom-product-addons' ),
        'target'   => 'custom_addons_tab',
        'priority' => 21,
        'class'    => array( 'show_if_simple', 'show_if_variable' )
    );
    return $tabs;
}

// Create Custom Tab Content
add_action( 'woocommerce_product_data_panels', 'cpa_add_product_data_panel' );
function cpa_add_product_data_panel() {
    global $post;
    $addons = get_post_meta( $post->ID, '_custom_product_addons', true );
    if ( ! is_array( $addons ) ) {
        $addons = array();
    }
    ?>
    <div id="custom_addons_tab" class="panel woocommerce_options_panel hidden">
        <h2><?php esc_html_e( 'Manage Addons', 'custom-product-addons' ); ?></h2>
        <table class="widefat" id="addons-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Addon Title', 'custom-product-addons' ); ?></th>
                    <th><?php esc_html_e( 'Choice Type', 'custom-product-addons' ); ?></th>
                    <th><?php esc_html_e( 'Max Options', 'custom-product-addons' ); ?></th>
                    <th><?php esc_html_e( 'Required', 'custom-product-addons' ); ?></th>
                    <th><?php esc_html_e( 'Action', 'custom-product-addons' ); ?></th>
                </tr>
            </thead>
            <tbody id="cpa-addons-container">
                <?php foreach ( $addons as $addon_index => $addon ) : ?>
                    <!-- Main Addon Row -->
                    <tr class="cpa-addon-box">
                        <td>
                            <input type="text" name="custom_product_addons[<?php echo esc_attr( $addon_index ); ?>][title]" value="<?php echo esc_attr( $addon['title'] ); ?>" placeholder="<?php esc_attr_e( 'Addon Title', 'custom-product-addons' ); ?>" required>
                        </td>
                        <td>
                            <select name="custom_product_addons[<?php echo esc_attr( $addon_index ); ?>][type]">
                                <option value="single" <?php selected( $addon['type'], 'single' ); ?>><?php esc_html_e( 'Single Choice', 'custom-product-addons' ); ?></option>
                                <option value="multiple" <?php selected( $addon['type'], 'multiple' ); ?>><?php esc_html_e( 'Multiple Choices', 'custom-product-addons' ); ?></option>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="custom_product_addons[<?php echo esc_attr( $addon_index ); ?>][max_options]" value="<?php echo esc_attr( $addon['max_options'] ); ?>" min="1" placeholder="<?php esc_attr_e( 'Max Options', 'custom-product-addons' ); ?>">
                        </td>
                        <td>
                            <input type="checkbox" name="custom_product_addons[<?php echo esc_attr( $addon_index ); ?>][required]" <?php checked( $addon['required'], 'on' ); ?>>
                        </td>
                        <td>
                            <button type="button" class="button remove-addon"><?php esc_html_e( 'Delete', 'custom-product-addons' ); ?></button>
                        </td>
                    </tr>
                    <!-- Sub Options Row -->
                    <tr>
                        <td colspan="5">
                            <table class="widefat cpa-suboptions-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Option Name', 'custom-product-addons' ); ?></th>
                                        <th><?php esc_html_e( 'Quantity', 'custom-product-addons' ); ?></th>
                                        <th><?php esc_html_e( 'Price', 'custom-product-addons' ); ?></th>
                                        <th><?php esc_html_e( 'Action', 'custom-product-addons' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody class="cpa-sub-options">
                                    <?php if ( isset( $addon['options'] ) && is_array( $addon['options'] ) ) : ?>
                                        <?php foreach ( $addon['options'] as $option_index => $option ) : ?>
                                            <tr class="cpa-sub-option">
                                                <td>
                                                    <input type="text" name="custom_product_addons[<?php echo esc_attr( $addon_index ); ?>][options][<?php echo esc_attr( $option_index ); ?>][label]" value="<?php echo esc_attr( $option['label'] ); ?>" placeholder="<?php esc_attr_e( 'Option Name', 'custom-product-addons' ); ?>" required>
                                                </td>
                                                <td>
                                                    <input type="number" name="custom_product_addons[<?php echo esc_attr( $addon_index ); ?>][options][<?php echo esc_attr( $option_index ); ?>][quantity]" value="<?php echo esc_attr( $option['quantity'] ); ?>" min="1" placeholder="<?php esc_attr_e( 'Quantity', 'custom-product-addons' ); ?>">
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" name="custom_product_addons[<?php echo esc_attr( $addon_index ); ?>][options][<?php echo esc_attr( $option_index ); ?>][price]" value="<?php echo esc_attr( $option['price'] ); ?>" min="0" placeholder="<?php esc_attr_e( 'Price', 'custom-product-addons' ); ?>">
                                                </td>
                                                <td>
                                                    <button type="button" class="button remove-sub-option"><?php esc_html_e( 'Delete', 'custom-product-addons' ); ?></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <button type="button" class="button button-secondary add-sub-option"><?php esc_html_e( 'Add Sub Option', 'custom-product-addons' ); ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" class="button button-primary" id="add-addon"><?php esc_html_e( 'Add New Addon', 'custom-product-addons' ); ?></button>

        <!-- Embedded CSS -->
        <style>
            #addons-table { margin-bottom: 15px; }
            .cpa-suboptions-table { margin-top: 10px; }
            .remove-addon, .remove-sub-option { color: #ff0000; border-color: #ff0000; }
        </style>

       
    </div>
    <?php
}

// Save Product Addons Data
add_action( 'woocommerce_process_product_meta', 'cpa_save_product_addons' );
function cpa_save_product_addons( $post_id ) {
    if ( isset( $_POST['custom_product_addons'] ) ) {
        $sanitized_addons = array();
        foreach ( $_POST['custom_product_addons'] as $addon ) {
            $sanitized_addons[] = array(
                'title'       => sanitize_text_field( $addon['title'] ),
                'type'        => sanitize_text_field( $addon['type'] ),
                'max_options' => absint( $addon['max_options'] ),
                'required'    => isset( $addon['required'] ) ? 'on' : 'off',
                'options'     => isset( $addon['options'] ) && is_array( $addon['options'] ) ? array_map( function( $option ) {
                    return array(
                        'label'    => sanitize_text_field( $option['label'] ),
                        'quantity' => absint( $option['quantity'] ),
                        'price'    => floatval( $option['price'] )
                    );
                }, $addon['options'] ) : array()
            );
        }
        update_post_meta( $post_id, '_custom_product_addons', $sanitized_addons );
    }
}
?>
