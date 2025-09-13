<?php
// File: dashboard-qahwtea/includes/wizard-functions.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Displays product addons specifically for the wizard context.
 */
function dq_wizard_display_product_addons() {
    global $product;
    if ( ! $product ) return;

    $addons = get_post_meta( $product->get_id(), '_custom_product_addons', true );
    if ( ! $addons || ! is_array( $addons ) ) return;
    
    echo '<div class="custom-product-addons" style="margin-bottom: 20px;">';
    foreach ( $addons as $addon_index => $addon ) {
        $required = ( isset( $addon['required'] ) && $addon['required'] === 'on' );
        ?>
        <div class="custom-addon" data-addon-index="<?php echo esc_attr( $addon_index ); ?>" data-max-options="<?php echo esc_attr( $addon['max_options'] ); ?>" style="margin-bottom:20px; padding:15px; border:1px solid #e0e0e0; border-radius: 5px;">
            <p style="font-weight:bold; margin-top:0; margin-bottom:10px;">
                <?php echo esc_html( $addon['title'] ); ?>
                <?php if ( $required ) : ?><span style="color:red;">*</span><?php endif; ?>
            </p>
            <?php if ( $addon['type'] === 'single' ) : ?>
                <select name="custom_addon[<?php echo esc_attr( $addon_index ); ?>]" class="custom-addon-single">
                    <option value=""><?php esc_html_e( 'Choose an option', 'dq' ); ?></option>
                    <?php if ( !empty($addon['options']) && is_array($addon['options'])) foreach ( $addon['options'] as $option_index => $option ) : ?>
                        <option value="<?php echo esc_attr( $option_index ); ?>" data-price="<?php echo esc_attr( $option['price'] ); ?>">
                            <?php echo esc_html( $option['label'] ) . ' (+' . wc_price( $option['price'] ) . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ( $addon['type'] === 'multiple' ) : ?>
                <p style="font-size:12px; color:#666; margin: -5px 0 10px;"><?php printf( 'Maximum of %s selections allowed.', esc_html( $addon['max_options'] ) ); ?></p>
                <?php if ( !empty($addon['options']) && is_array($addon['options'])) foreach ( $addon['options'] as $option_index => $option ) : ?>
                    <div class="custom-addon-multiple-option" data-price="<?php echo esc_attr( $option['price'] ); ?>" data-max-quantity="<?php echo esc_attr( $option['quantity'] ); ?>" style="margin-bottom:10px; display:flex; align-items:center; justify-content: space-between;">
                        <span class="option-label">
                            <?php echo esc_html( $option['label'] ) . ' (+' . wc_price( $option['price'] ) . ')'; ?>
                            <span style="font-size:10px; color:#999;"> (max: <?php echo esc_html( $option['quantity'] ); ?>)</span>
                        </span>
                        <div class="quantity-selector" style="display:flex; align-items:center;">
                            <button type="button" class="minus btn-addon" style="padding:4px 8px; line-height: 1; border: 1px solid #ccc; background: #f1f1f1; cursor: pointer;">–</button>
                            <input type="number" name="custom_addon[<?php echo esc_attr( $addon_index ); ?>][<?php echo esc_attr( $option_index ); ?>]" value="0" min="0" max="<?php echo esc_attr( $option['quantity'] ); ?>" style="width:50px; text-align:center; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; border-left: none; border-right: none; margin: 0;" readonly />
                            <button type="button" class="plus btn-addon" style="padding:4px 8px; line-height: 1; border: 1px solid #ccc; background: #f1f1f1; cursor: pointer;">+</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    echo '</div>';
}

/**
 * Outputs the JavaScript for the wizard's addons.
 * This script now uses robust event delegation to ensure it always works.
 */
function dq_wizard_addons_script() {
    global $product;
    if ( ! $product ) return;
    ?>
    <script>
    (function($) {
        // Use a selector for the content that is always present when this script runs.
        var $form = $('#dq-wizard-step-3-content form.cart');

        // If the form doesn't exist, do nothing.
        if ($form.length === 0) {
            return;
        }

        var basePrice = 0;
        var priceElement = $form.find('.price').first();
        var currencySymbol = '<?php echo get_woocommerce_currency_symbol(); ?>';
        var priceFormat = '<?php echo esc_js( get_woocommerce_price_format() ); ?>';

        function getPriceFromElement(element) {
            if (!element.length) return parseFloat("<?php echo $product->get_price(); ?>");
            var priceText = element.text().replace(currencySymbol, '').trim();
            return parseFloat(priceText) || 0;
        }

        basePrice = getPriceFromElement(priceElement);

        function formatPrice(price) {
            var formattedPrice = price.toFixed(2);
            return priceFormat.replace('%1$s', currencySymbol).replace('%2$s', formattedPrice);
        }

        function recalcOverallPrice() {
            var additionalTotal = 0;
            $form.find('.custom-addon-single option:selected').each(function() {
                additionalTotal += parseFloat($(this).data('price')) || 0;
            });
            $form.find('.custom-addon-multiple-option').each(function(){
                var price = parseFloat($(this).data('price')) || 0;
                var qty = parseInt($(this).find('input[type="number"]').val()) || 0;
                additionalTotal += price * qty;
            });
            var overall = basePrice + additionalTotal;
            priceElement.html(formatPrice(overall));
        }

        // --- THE FIX: EVENT DELEGATION ---
        // We listen for events on the form, which always exists,
        // instead of attaching events directly to buttons that might not exist yet.
        $form.on('change', '.custom-addon-single', recalcOverallPrice);

        $form.on('click', '.btn-addon', function() {
            var $button = $(this);
            var $input = $button.siblings('input[type="number"]');
            var currentVal = parseInt($input.val());
            var $optionDiv = $button.closest('.custom-addon-multiple-option');
            var max = parseInt($optionDiv.data('max-quantity'));
            var $addonDiv = $button.closest('.custom-addon');
            var addonMax = parseInt($addonDiv.data('max-options'));
            var total = 0;

            if ($button.hasClass('plus')) {
                $addonDiv.find('input[type="number"]').each(function(){ total += parseInt($(this).val()); });
                if ( currentVal < max && total < addonMax ) {
                    $input.val(currentVal + 1);
                }
            } else if ($button.hasClass('minus')) {
                if ( currentVal > 0 ) {
                    $input.val(currentVal - 1);
                }
            }
            recalcOverallPrice();
        });
        
        recalcOverallPrice(); // Initial calculation
    })(jQuery);
    </script>
    <?php
}