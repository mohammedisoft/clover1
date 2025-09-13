<?php
/**
 * File: admin/class-dq-banner-meta.php
 *
 * Creates a meta box for the "dq_banner" custom post type.
 * It allows setting up banner images with cropping and associated WooCommerce products.
 * If the selected image dimensions are not 700x450, a cropping interface will be shown using Cropper.js.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DQ_Banner_Meta {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function register_meta_boxes() {
        add_meta_box(
            'dq_banner_details',
            'Banner Details',
            array( $this, 'render_meta_box' ),
            'dq_banner',
            'normal',
            'default'
        );
    }

    public function enqueue_assets( $hook ) {
        global $post;
        if ( ( $hook === 'post-new.php' || $hook === 'post.php' ) && isset( $post ) && $post->post_type === 'dq_banner' ) {
            wp_enqueue_media();

            // Enqueue Cropper.js from CDN.
            wp_enqueue_script( 'cropper-js', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js', array( 'jquery' ), '1.5.12', true );
            wp_enqueue_style( 'cropper-css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css', array(), '1.5.12' );

            // Enqueue our custom JS file.
            $admin_script_path = plugin_dir_path( __FILE__ ) . '../assets/banner-admin.js';
            if ( file_exists( $admin_script_path ) ) {
                wp_enqueue_script(
                    'dq-banner-admin-script',
                    plugin_dir_url( __FILE__ ) . '../assets/banner-admin.js',
                    array( 'jquery', 'jquery-ui-dialog', 'cropper-js' ),
                    filemtime( $admin_script_path ),
                    true
                );
            }
            
            // Prepare product options.
            $product_options = '<option value="">Select a Product</option>';
            $products = get_posts( array(
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
            ) );
            if ( $products ) {
                foreach ( $products as $product ) {
                    $product_options .= '<option value="' . esc_attr( $product->ID ) . '">' . esc_html( $product->post_title ) . '</option>';
                }
            }
            
            // Localize script.
            wp_localize_script( 'dq-banner-admin-script', 'dqBannerData', array(
                'nonce'            => wp_create_nonce( 'dq_crop_banner_nonce' ),
                'productOptions'   => $product_options,
                'recommendedWidth' => 700,
                'recommendedHeight'=> 450,
                'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
            ) );
            
            // Enqueue additional CSS.
            $admin_style_path = plugin_dir_path( __FILE__ ) . '../assets/dq-banner.css';
            if ( file_exists( $admin_style_path ) ) {
                wp_enqueue_style(
                    'dq-banner-style',
                    plugin_dir_url( __FILE__ ) . '../assets/dq-banner.css',
                    array(),
                    filemtime( $admin_style_path )
                );
            }
        }
    }

    public function render_meta_box( $post ) {
        $banner_images = get_post_meta( $post->ID, 'banner_images', true );
        $banner_images = ! empty( $banner_images ) ? maybe_unserialize( $banner_images ) : array();
        ?>
        <div class="banner-meta-box">
            <p><strong>Banner Instructions:</strong> Recommended dimensions: 700x450.
                If the selected image does not match these dimensions, a cropping interface will be shown.</p>
            <div id="banner-images-container">
                <?php if ( ! empty( $banner_images ) ) : ?>
                    <?php foreach ( $banner_images as $index => $banner ) : ?>
                        <div class="banner-image-row">
                            <!-- هنا نحفظ قيمة الصورة سواء كانت معرّف أو رابط -->
                            <input type="hidden" name="banner_images[<?php echo esc_attr( $index ); ?>][image_id]" class="banner-image-id" value="<?php echo esc_attr( $banner['image_id'] ); ?>">
                            <div class="banner-image-preview">
                                <?php 
                                if ( ! empty( $banner['image_id'] ) ) {
                                    if ( is_numeric( $banner['image_id'] ) ) {
                                        echo wp_get_attachment_image( $banner['image_id'], array( 150, 150 ) );
                                    } else {
                                        echo '<img src="' . esc_url( $banner['image_id'] ) . '" width="150" height="150" />';
                                    }
                                }
                                ?>
                            </div>
                            <button type="button" class="button dq-upload-banner-image">Upload Image</button>
                            <select name="banner_images[<?php echo esc_attr( $index ); ?>][product_id]" class="banner-product-select">
                                <option value="">Select a Product</option>
                                <?php
                                $products = get_posts( array(
                                    'post_type'      => 'product',
                                    'posts_per_page' => -1,
                                    'post_status'    => 'publish',
                                ) );
                                if ( $products ) {
                                    foreach ( $products as $product ) {
                                        ?>
                                        <option value="<?php echo esc_attr( $product->ID ); ?>" <?php selected( $banner['product_id'], $product->ID ); ?>>
                                            <?php echo esc_html( $product->post_title ); ?>
                                        </option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                            <button type="button" class="button dq-remove-banner-image">Remove</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" class="button" id="add-banner-image">Add Banner Image</button>
            <?php wp_nonce_field( 'dq_banner_meta_nonce', 'dq_banner_meta_nonce_field' ); ?>
        </div>
        <?php
    }

    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['dq_banner_meta_nonce_field'] ) ||
             ! wp_verify_nonce( $_POST['dq_banner_meta_nonce_field'], 'dq_banner_meta_nonce' ) ) {
            return $post_id;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }
        if ( isset( $_POST['banner_images'] ) && is_array( $_POST['banner_images'] ) ) {
            $banner_images = array();
            foreach ( $_POST['banner_images'] as $banner ) {
                // إذا كانت القيمة رقمية، نستخدم intval؛ وإذا كانت ليست رقمية، نحفظها كما هي.
                if ( isset( $banner['image_id'] ) ) {
                    if ( is_numeric( $banner['image_id'] ) ) {
                        $image_id = intval( $banner['image_id'] );
                    } else {
                        $image_id = sanitize_text_field( $banner['image_id'] );
                    }
                } else {
                    $image_id = '';
                }
                $product_id = isset( $banner['product_id'] ) ? intval( $banner['product_id'] ) : 0;
                if ( $image_id ) {
                    $banner_images[] = array(
                        'image_id'   => $image_id,
                        'product_id' => $product_id,
                    );
                }
            }
            update_post_meta( $post_id, 'banner_images', $banner_images );
        } else {
            delete_post_meta( $post_id, 'banner_images' );
        }
    }
}

new DQ_Banner_Meta();
