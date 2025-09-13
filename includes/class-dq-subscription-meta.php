<?php
/**
 * File: dashboard-qahwtea/includes/class-dq-subscription-meta.php
 *
 * Creates a meta box for the "dq_subscription" post type to enter additional subscription plan details.
 * (For example: subscription duration, plan name, effective plan name, etc.)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DQ_Subscription_Meta {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
    }

    /**
     * Registers the meta box for subscription details.
     */
    public function register_meta_boxes() {
        add_meta_box(
            'dq_subscription_details',
            'Subscription Details',
            array( $this, 'render_meta_box' ),
            'dq_subscription',
            'normal',
            'default'
        );
    }

    /**
     * Renders the meta box.
     *
     * @param WP_Post $post The current post object.
     */
    public function render_meta_box( $post ) {
        $subscription_duration = get_post_meta( $post->ID, 'dq_subscription_duration', true );
        $plan_name             = get_post_meta( $post->ID, 'dq_subscription_plan_name', true );
        $effective_plan_name   = get_post_meta( $post->ID, 'dq_subscription_effective_plan_name', true );

        // Define available durations.
        $durations = array(
            'daily'    => 'Daily',
            'weekly'   => 'Weekly',
            'biweekly' => 'Biweekly',
            '4_weeks'  => '4 Weeks',
            '6_weeks'  => '6 Weeks',
            'monthly'  => 'Monthly'
        );

        wp_nonce_field( 'dq_subscription_meta_nonce', 'dq_subscription_meta_nonce_field' );
        ?>
        <div class="subscription-meta-box">
            <p>
                <label for="dq_subscription_duration">Subscription Duration:</label>
                <select name="dq_subscription_duration" id="dq_subscription_duration">
                    <?php foreach ( $durations as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $subscription_duration, $key ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="dq_subscription_plan_name">Plan Name:</label>
                <input type="text" name="dq_subscription_plan_name" id="dq_subscription_plan_name" value="<?php echo esc_attr( $plan_name ); ?>" placeholder="Enter plan name" />
            </p>
            <p>
                <label for="dq_subscription_effective_plan_name">Effective Plan Name:</label>
                <input type="text" name="dq_subscription_effective_plan_name" id="dq_subscription_effective_plan_name" value="<?php echo esc_attr( $effective_plan_name ); ?>" placeholder="Enter effective plan name" />
            </p>
            <p class="description">Additional details for the subscription plan.</p>
        </div>
        <?php
    }

    /**
     * Saves the meta box data.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save_meta_boxes( $post_id ) {
        if ( ! isset( $_POST['dq_subscription_meta_nonce_field'] ) || 
             ! wp_verify_nonce( $_POST['dq_subscription_meta_nonce_field'], 'dq_subscription_meta_nonce' ) ) {
            return $post_id;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }

        if ( isset( $_POST['dq_subscription_duration'] ) ) {
            update_post_meta( $post_id, 'dq_subscription_duration', sanitize_text_field( $_POST['dq_subscription_duration'] ) );
        }
        if ( isset( $_POST['dq_subscription_plan_name'] ) ) {
            update_post_meta( $post_id, 'dq_subscription_plan_name', sanitize_text_field( $_POST['dq_subscription_plan_name'] ) );
        }
        if ( isset( $_POST['dq_subscription_effective_plan_name'] ) ) {
            update_post_meta( $post_id, 'dq_subscription_effective_plan_name', sanitize_text_field( $_POST['dq_subscription_effective_plan_name'] ) );
        }
    }



    
}

new DQ_Subscription_Meta();
