<?php
// File: dashboard-qahwtea/includes/blocks/subscription-list.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Subscription List Block for Gutenberg.
 */
function dq_register_subscription_list_block() {
    register_block_type( 'dashboard-qahwtea/subscription-list', array(
        'render_callback' => 'dq_render_subscription_list_block_wizard',
        'attributes'      => array(
            'title' => array('type' => 'string', 'default' => 'Build Your Subscription'),
        ),
    ) );
}
add_action( 'init', 'dq_register_subscription_list_block' );

/**
 * Renders the new STEP-BY-STEP WIZARD on the frontend.
 */
function dq_render_subscription_list_block_wizard( $attributes ) {
    ob_start();

    $categories = get_terms( array('taxonomy' => 'subscription_category', 'hide_empty' => false) );
    ?>
    <style>
        :root {
            /* TO CHANGE BRAND COLOR, EDIT THE HEX CODE BELOW */
            --brand-color: #794922; /* A nice coffee brown color */
        }
        .dq-wizard-container { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; max-width: 900px; margin: 40px auto; background: #fff; box-shadow: 0 5px 25px rgba(0,0,0,0.07); border-radius: 10px; padding: 30px 40px; }
        .dq-wizard-step { display: none; }
        .dq-wizard-step.active { display: block; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .dq-wizard-step h2 { font-size: 26px; color: #333; text-align: center; margin-bottom: 30px; }
        .dq-wizard-options-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .dq-wizard-selection-btn { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; text-align: center; cursor: pointer; transition: all 0.2s ease-in-out; }
        .dq-wizard-selection-btn:hover { transform: translateY(-5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-color: var(--brand-color); }
        .dq-wizard-selection-btn img { width: 100%; height: 160px; object-fit: cover; border-radius: 5px; margin-bottom: 15px; }
        .dq-wizard-selection-btn span { font-weight: 600; color: #444; }
        #dq-wizard-step-3-content .loading-spinner { text-align: center; padding: 50px; font-size: 18px; color: #777; }
        .dq-wizard-nav { margin-top: 35px; display: flex; justify-content: flex-start; }
        .dq-wizard-nav .prev-btn { background: #6c757d; color: #fff; border: none; padding: 10px 25px; border-radius: 5px; cursor: pointer; font-size: 16px; }
    </style>
    
    <div class="dq-wizard-container">
        <div id="dq-wizard-step-1" class="dq-wizard-step active">
            <h2>Step 1: What type of coffee?</h2>
            <div class="dq-wizard-options-grid">
                <?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
                    <?php foreach ( $categories as $category ) : ?>
                        <div class="dq-wizard-selection-btn" onclick="wizard.goToStep(2, '<?php echo esc_attr($category->slug); ?>')">
                            <span><?php echo esc_html( $category->name ); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div id="dq-wizard-step-2" class="dq-wizard-step">
            <h2>Step 2: Choose your coffee</h2>
            <div id="dq-wizard-step-2-content" class="dq-wizard-options-grid"></div>
            <div class="dq-wizard-nav"><button class="prev-btn" onclick="wizard.goToStep(1)">&larr; Back</button></div>
        </div>
        <div id="dq-wizard-step-3" class="dq-wizard-step">
            <h2>Step 3: Customize your subscription</h2>
            <div id="dq-wizard-step-3-content"></div>
            <div class="dq-wizard-nav"><button class="prev-btn" onclick="wizard.goToStep(2)">&larr; Back</button></div>
        </div>
    </div>
    
    <script>
    // The wizard navigation Javascript remains the same
    document.addEventListener("DOMContentLoaded", function() {
        window.wizard = {
            container: document.querySelector(".dq-wizard-container"),
            step2Content: document.getElementById("dq-wizard-step-2-content"),
            step3Content: document.getElementById("dq-wizard-step-3-content"),
            goToStep: function(stepNumber, param = null) {
                this.container.querySelectorAll(".dq-wizard-step").forEach(step => step.classList.remove("active"));
                this.container.querySelector(`#dq-wizard-step-${stepNumber}`).classList.add("active");
                const ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";
                let formData;
                if (stepNumber === 2 && param) {
                    this.step2Content.innerHTML = '<div>Loading...</div>';
                    formData = new URLSearchParams({ action: 'dq_get_products_for_wizard', category: param });
                    fetch(ajaxUrl, { method: 'POST', body: formData })
                        .then(response => response.text())
                        .then(html => this.step2Content.innerHTML = html);
                } else if (stepNumber === 3 && param) {
                    this.step3Content.innerHTML = '<div class="loading-spinner">Loading options...</div>';
                    formData = new URLSearchParams({ action: 'dq_get_subscription_form_for_wizard', product_id: param });
                    fetch(ajaxUrl, { method: 'POST', body: formData })
                        .then(response => response.text())
                        .then(html => { this.step3Content.innerHTML = html; });
                }
            }
        };
    });
    </script>
    <?php
    return ob_get_clean();
}