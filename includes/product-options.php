<?php
/**
 * Custom Product Addons with Overall Price Update
 *
 * This code removes the default WooCommerce price output from its original location
 * and displays custom addon options within the add-to-cart form.
 * The displayed price is updated dynamically based on the selected variation
 * and any custom addon selections.
 *
 * File: wp-content/plugins/dashboard-qahwtea/includes/product-options.php
 */

// 1. Remove the default price output from its original locations.
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );

// 2. Display custom addon options inside the add-to-cart form.
add_action( 'woocommerce_before_add_to_cart_button', 'cpa_display_product_addons_frontend', 5 );
function cpa_display_product_addons_frontend() {
    global $product;
    $addons = get_post_meta( $product->get_id(), '_custom_product_addons', true );
    if ( ! $addons || ! is_array( $addons ) ) {
        return;
    }
    
    echo '<div class="custom-product-addons" style="margin:20px 0;">';
    foreach ( $addons as $addon_index => $addon ) {
        // Check if this addon is required.
        $required = ( isset( $addon['required'] ) && $addon['required'] === 'on' ) ? true : false;
        ?>
        <div class="custom-addon" data-addon-index="<?php echo esc_attr( $addon_index ); ?>" data-max-options="<?php echo esc_attr( $addon['max_options'] ); ?>" data-required="<?php echo $required ? 'on' : 'off'; ?>" style="margin-bottom:20px; padding:10px; border:1px solid #ddd;">
            <p class="addon-title" style="font-weight:bold; margin-bottom:5px;">
                <?php echo esc_html( $addon['title'] ); ?>
                <?php if ( $required ) : ?>
                    <span class="required-indicator" style="color:red;">*</span>
                <?php endif; ?>
            </p>
            <?php if ( $addon['type'] === 'single' ) : ?>
                <select name="custom_addon[<?php echo esc_attr( $addon_index ); ?>]" class="custom-addon-single" style="width:100%;">
                    <option value=""><?php esc_html_e( 'Select an option', 'custom-product-addons' ); ?></option>
                    <?php
                    if ( isset( $addon['options'] ) && is_array( $addon['options'] ) ) {
                        foreach ( $addon['options'] as $option_index => $option ) {
                            ?>
                            <option value="<?php echo esc_attr( $option_index ); ?>" data-price="<?php echo esc_attr( $option['price'] ); ?>" data-default-qty="<?php echo esc_attr( $option['quantity'] ); ?>">
                                <?php echo esc_html( $option['label'] ) . ' (+' . wc_price( $option['price'] ) . ')'; ?>
                            </option>
                            <?php
                        }
                    }
                    ?>
                </select>
            <?php elseif ( $addon['type'] === 'multiple' ) : ?>
                <div class="custom-addon-multiple-wrapper" style="border:1px solid #ccc; border-radius:4px; overflow:hidden; margin-top:10px;">
                    <div class="custom-addon-multiple-header" style="cursor:pointer; background-color:#f9f9f9; padding:8px 12px;">
                        <span><?php echo esc_html( $addon['title'] ); ?></span>
                        <i class="toggle-icon" style="float:right;">&#9660;</i>
                    </div>
                    <p style="font-size:12px; color:#666; margin:5px 12px;">
                        Maximum of <?php echo esc_html( $addon['max_options'] ); ?> options allowed.
                    </p>
                    <div class="custom-addon-multiple-options" style="display:none; padding:10px;">
                        <?php if ( isset( $addon['options'] ) && is_array( $addon['options'] ) ) : ?>
                            <?php foreach ( $addon['options'] as $option_index => $option ) : ?>
                                <div class="custom-addon-multiple-option" data-option-index="<?php echo esc_attr( $option_index ); ?>" data-price="<?php echo esc_attr( $option['price'] ); ?>" data-max-quantity="<?php echo esc_attr( $option['quantity'] ); ?>" style="margin-bottom:10px; display:flex; align-items:center;">
                                    <span class="option-label" style="flex:1;">
                                        <?php
                                        echo esc_html( $option['label'] ) . ' (+' . wc_price( $option['price'] ) . ')';
                                        ?>
                                        <span style="font-size:10px; color:#999;"> (max: <?php echo esc_html( $option['quantity'] ); ?>)</span>
                                    </span>
                                    <div class="quantity-selector" style="display:flex; align-items:center;">
                                        <button type="button" class="minus btn-addon" style="padding:4px 8px; margin-right:5px;">–</button>
                                        <input type="number" name="custom_addon[<?php echo esc_attr( $addon_index ); ?>][<?php echo esc_attr( $option_index ); ?>]" value="0" min="0" max="<?php echo esc_attr( $option['quantity'] ); ?>" style="width:50px; text-align:center; border:1px solid #ccc; padding:4px;" />
                                        <button type="button" class="plus btn-addon" style="padding:4px 8px; margin-left:5px;">+</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ( $required ) : ?>
                <div class="addon-error" style="color:red; margin-top:5px; display:none;">
                    <?php esc_html_e( 'This field is required.', 'custom-product-addons' ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    echo '</div>';
}

// 3. Display the updated price element before the add-to-cart button.
add_action( 'woocommerce_before_add_to_cart_button', 'cpa_display_updated_price', 7 );
function cpa_display_updated_price() {
    global $product;
    ?>
    <div class="woocommerce-variation-price" style="font-size:18px; font-weight:bold; margin:15px 0;">
        <span class="price"><?php echo wc_price( $product->get_price() ); ?></span>
    </div>
    <?php
}

// 4. Add JavaScript for dynamic price updates and addon interactions.
add_action( 'wp_footer', 'cpa_custom_addons_script' );
function cpa_custom_addons_script() {
    if ( ! is_product() ) {
        return;
    }
    global $product;
    ?>
    <script>
    jQuery(document).ready(function($){
        // Get the currency symbol from WooCommerce and set the base price from PHP.
        var currencySymbol = '<?php echo get_woocommerce_currency_symbol(); ?>';
        var basePrice = parseFloat("<?php echo $product->get_price(); ?>");

        // Function to format a number as a price string.
        function formatPrice(value) {
            return currencySymbol + parseFloat(value).toFixed(2);
        }

        // Recalculate overall price based on addon selections.
        function recalcOverallPrice() {
            var additionalTotal = 0;
            // Process single choice addons.
            $('.custom-addon-single').each(function() {
                var selectedVal = $(this).val();
                if ( selectedVal !== '' ) {
                    var selectedOption = $(this).find('option:selected');
                    var price = parseFloat(selectedOption.data('price'));
                    if (!isNaN(price)) {
                        additionalTotal += price;
                    }
                }
            });
            // Process multiple choice addons.
            $('.custom-addon-multiple-option').each(function(){
                var price = parseFloat($(this).data('price'));
                var qty = parseInt($(this).find('input[type="number"]').val());
                if (!isNaN(price) && !isNaN(qty)) {
                    additionalTotal += price * qty;
                }
            });
            var overall = basePrice + additionalTotal;
            // Update the price in any default variation price element.
            $('.woocommerce-variation-price').find('.price').html(formatPrice(overall));
        }

        // For variable products: update basePrice when a variation is selected.
        $('.variations_form').on('found_variation', function(event, variation) {
            basePrice = parseFloat(variation.display_price);
            recalcOverallPrice();
        });

        // Toggle the multiple addon dropdown.
        $('.custom-addon-multiple-header').on('click', function(){
            var optionsContainer = $(this).nextAll('.custom-addon-multiple-options').first();
            optionsContainer.slideToggle();
            var icon = $(this).find('.toggle-icon');
            if ( icon.html().trim() === "▼" || icon.html().trim() === "&#9660;" ) {
                icon.html("&#9650;"); // Arrow up.
            } else {
                icon.html("&#9660;"); // Arrow down.
            }
        });

        // Plus/minus functionality for multiple addon options.
        $('.custom-addon-multiple-options').on('click', '.plus', function(){
            var input = $(this).siblings('input');
            var currentVal = parseInt(input.val());
            var max = parseInt(input.attr('max'));
            var addonDiv = $(this).closest('.custom-addon');
            var addonMax = parseInt(addonDiv.data('max-options'));
            var total = 0;
            addonDiv.find('input[type="number"]').each(function(){
                total += parseInt($(this).val());
            });
            if ( currentVal < max && total < addonMax ) {
                input.val(currentVal + 1).change();
                recalcOverallPrice();
            } else {
                alert('Maximum quantity reached for this addon.');
            }
        });
        $('.custom-addon-multiple-options').on('click', '.minus', function(){
            var input = $(this).siblings('input');
            var currentVal = parseInt(input.val());
            if ( currentVal > 0 ) {
                input.val(currentVal - 1).change();
                recalcOverallPrice();
            }
        });

        // Update overall price when a single addon dropdown changes.
        $('.custom-addon-single').on('change', function(){
            recalcOverallPrice();
        });

        // Client-side validation for required addon fields on form submission.
        $('form.cart').on('submit', function(e){
            var valid = true;
            $('.custom-product-addons .custom-addon').each(function(){
                var required = $(this).data('required');
                if ( required === 'on' ) {
                    if ($(this).find('select').length) {
                        if ($(this).find('select').val() === '') {
                            $(this).find('.addon-error').show();
                            valid = false;
                        } else {
                            $(this).find('.addon-error').hide();
                        }
                    } else {
                        var total = 0;
                        $(this).find('input[type="number"]').each(function(){
                            total += parseInt($(this).val());
                        });
                        if ( total <= 0 ) {
                            $(this).find('.addon-error').show();
                            valid = false;
                        } else {
                            $(this).find('.addon-error').hide();
                        }
                    }
                }
            });
            if (!valid) {
                e.preventDefault();
                return false;
            }
        });
    });
    </script>
    <?php
}

// 5. Server-side validation for required addon fields when adding to cart.
add_filter( 'woocommerce_add_to_cart_validation', 'cpa_validate_addons', 10, 3 );
function cpa_validate_addons( $passed, $product_id, $quantity ) {
    $addons = get_post_meta( $product_id, '_custom_product_addons', true );
    if ( $addons && is_array( $addons ) ) {
        foreach ( $addons as $addon_index => $addon ) {
            if ( isset( $addon['required'] ) && $addon['required'] === 'on' ) {
                if ( $addon['type'] === 'single' ) {
                    if ( empty( $_REQUEST['custom_addon'][ $addon_index ] ) ) {
                        wc_add_notice( __( 'Please select an option for addon: ', 'custom-product-addons' ) . $addon['title'], 'error' );
                        $passed = false;
                    }
                } elseif ( $addon['type'] === 'multiple' ) {
                    if ( isset( $_REQUEST['custom_addon'][ $addon_index ] ) && is_array( $_REQUEST['custom_addon'][ $addon_index ] ) ) {
                        $sum = 0;
                        foreach ( $_REQUEST['custom_addon'][ $addon_index ] as $option_quantity ) {
                            $sum += intval( $option_quantity );
                        }
                        if ( $sum <= 0 ) {
                            wc_add_notice( __( 'Please select at least one option for addon: ', 'custom-product-addons' ) . $addon['title'], 'error' );
                            $passed = false;
                        }
                    } else {
                        wc_add_notice( __( 'Please select at least one option for addon: ', 'custom-product-addons' ) . $addon['title'], 'error' );
                        $passed = false;
                    }
                }
            }
        }
    }
    return $passed;
}
?>