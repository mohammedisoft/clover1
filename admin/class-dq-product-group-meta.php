<?php
/**
 * File: admin/class-dq-product-group-meta.php
 *
 * Creates a meta box for the "dq_product_group" custom post type.
 * It allows setting the filter type, selecting specific WooCommerce products (for Best Products),
 * and choosing the number of products to display.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DQ_Product_Group_Meta {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
    }

    /**
     * Register meta box for product group details.
     */
    public function register_meta_boxes() {
        add_meta_box(
            'dq_product_group_details',
            'Product Group Details',
            array( $this, 'render_meta_box' ),
            'dq_product_group',
            'normal',
            'default'
        );
    }

    /**
     * Render the product group details meta box.
     *
     * @param WP_Post $post The current post object.
     */
    public function render_meta_box( $post ) {
        // Retrieve existing meta values.
        $filter_type       = get_post_meta( $post->ID, 'filter_type', true );
        $selected_products = get_post_meta( $post->ID, 'selected_products', true );
        $api_product_count = get_post_meta( $post->ID, 'api_product_count', true );

        // Ensure $selected_products is an array.
        $selected_products = ! empty( $selected_products ) ? maybe_unserialize( $selected_products ) : array();

        // Define filter type options.
        $filter_options = array(
            'most_sold'      => 'Most Sold',
            'recently_added' => 'Recently Added',
            'best_products'  => 'Best Products'
        );

        // Define API product count options.
        $api_count_options = array( 2, 4, 6, 8 );

        // Output nonce for security.
        wp_nonce_field( 'dq_product_group_meta_nonce', 'dq_product_group_meta_nonce_field' );
        ?>
        <div class="product-group-meta-box">
            <!-- Filter Type Selection -->
            <p>
                <label for="filter_type">Filter Type:</label>
                <select name="filter_type" id="filter_type">
                    <?php foreach ( $filter_options as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filter_type, $key ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <!-- Selected Products (shown only for Best Products filter) -->
            <div id="selected-products-container" style="display: <?php echo ( $filter_type === 'best_products' ) ? 'block' : 'none'; ?>;">
                <p>
                    <label for="selected_products">Select Products (multiple selection):</label>
                    <select name="selected_products[]" id="selected_products" multiple style="width:100%;">
                        <?php
                        // Query published WooCommerce products.
                        $products = get_posts( array(
                            'post_type'      => 'product',
                            'posts_per_page' => -1,
                            'post_status'    => 'publish',
                        ) );
                        if ( $products ) {
                            foreach ( $products as $product ) {
                                ?>
                                <option value="<?php echo esc_attr( $product->ID ); ?>" <?php echo ( is_array( $selected_products ) && in_array( $product->ID, $selected_products ) ) ? 'selected' : ''; ?>>
                                    <?php echo esc_html( $product->post_title ); ?>
                                </option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                </p>
            </div>

            <!-- API Product Count Selection (Label Updated) -->
            <p>
                <label for="api_product_count">Number of Products to Display:</label>
                <select name="api_product_count" id="api_product_count">
                    <?php foreach ( $api_count_options as $count ) : ?>
                        <option value="<?php echo esc_attr( $count ); ?>" <?php selected( $api_product_count, $count ); ?>>
                            <?php echo esc_html( $count ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
        </div>

        <script>
        (function($){
            $(document).ready(function(){
                $('#filter_type').change(function(){
                    if ($(this).val() === 'best_products') {
                        $('#selected-products-container').slideDown();
                    } else {
                        $('#selected-products-container').slideUp();
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Save the meta box data when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save_meta_boxes( $post_id ) {
        // Verify nonce.
        if ( ! isset( $_POST['dq_product_group_meta_nonce_field'] ) ||
             ! wp_verify_nonce( $_POST['dq_product_group_meta_nonce_field'], 'dq_product_group_meta_nonce' ) ) {
            return $post_id;
        }
        // Prevent autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        // Check user permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }

        // Save Filter Type.
        if ( isset( $_POST['filter_type'] ) ) {
            update_post_meta( $post_id, 'filter_type', sanitize_text_field( $_POST['filter_type'] ) );
        }

        // Save Selected Products (only if filter type is "best_products").
        if ( isset( $_POST['selected_products'] ) && is_array( $_POST['selected_products'] ) ) {
            $selected_products = array_map( 'intval', $_POST['selected_products'] );
            update_post_meta( $post_id, 'selected_products', $selected_products );
        } else {
            delete_post_meta( $post_id, 'selected_products' );
        }

        // Save API Product Count.
        if ( isset( $_POST['api_product_count'] ) ) {
            update_post_meta( $post_id, 'api_product_count', intval( $_POST['api_product_count'] ) );
        }
    }
}

new DQ_Product_Group_Meta();
