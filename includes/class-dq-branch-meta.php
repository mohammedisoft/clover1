<?php
/**
 * File: includes/class-dq-branch-meta.php
 *
 * This file creates a meta box for the "dq_branch" custom post type.
 * It displays branch details and a dynamic operating hours section with a user-friendly interface.
 * It also includes a button that opens a popup where the user can enter a branch URL.
 * Clicking "Get" fetches (simulated) latitude and longitude values and fills the respective input fields.
 * The URL is not saved.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DQ_Branch_Meta {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ) );
        add_action('admin_footer', array($this, 'add_scripts'));
        add_action('wp_ajax_dq_get_tax_rates', array($this, 'dq_get_tax_rates_callback'));
    }

    /**
     * Register meta box for branch details.
     */
    public function register_meta_boxes() {
        add_meta_box(
            'dq_branch_details',
            'Branch Details',
            array( $this, 'render_meta_box' ),
            'dq_branch',
            'normal',
            'default'
        );
    }

    /**
     * Render the branch details meta box.
     *
     * @param WP_Post $post The current post object.
     */
    public function render_meta_box( $post ) {
        // Retrieve existing values.
        $mobile             = get_post_meta( $post->ID, 'branch_mobile', true );
        $address            = get_post_meta( $post->ID, 'branch_address', true );
        $latitude           = get_post_meta( $post->ID, 'branch_latitude', true );
        $longitude          = get_post_meta( $post->ID, 'branch_longitude', true );
        $delivery_radius    = get_post_meta( $post->ID, 'delivery_radius', true );
        $branch_hours       = get_post_meta( $post->ID, 'branch_hours', true );
        $branch_hours_mode  = get_post_meta( $post->ID, 'branch_hours_mode', true );

        $tax_classes = WC_Tax::get_tax_classes();
        array_unshift($tax_classes, ''); 
        $selected_tax_class = get_post_meta($post->ID, 'branch_tax_class', true);
        $selected_tax_rate_id = get_post_meta($post->ID, 'branch_tax_rate_id', true);

       

        foreach ($tax_classes as $tax_class) {
            $rates = WC_Tax::get_rates_for_tax_class(sanitize_title($tax_class));
            foreach ($rates as $rate) {
                $tax_rates[$rate->tax_rate_id] = sprintf(
                    '%s (%s-%s: %s%%)',
                    $rate->tax_rate_name,
                    $rate->tax_rate_country,
                    $rate->tax_rate_state,
                    $rate->tax_rate
                );
            }
        }
        
        $branch_hours_data = array();
        if ( ! empty( $branch_hours ) ) {
            $branch_hours_data = json_decode( $branch_hours, true );
        }
        if ( empty( $branch_hours_mode ) ) {
            $branch_hours_mode = 'custom';
        }
        // For "All Days" mode, the data is stored under key 'all'.
        $all_shifts = isset( $branch_hours_data['all'] ) ? $branch_hours_data['all'] : array();
        // Define days for custom mode.
        $days = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );

        // Output nonce field.
        wp_nonce_field( 'dq_branch_meta_nonce', 'dq_branch_meta_nonce_field' );
        ?>



        <div class="branch-meta-box">


        <div class="tax-class-section" style="margin-top:20px; padding:15px; background:#f5f5f5;">
            <h3>Tax Settings</h3>
            
            <label for="tax_class">Tax Class:</label>
            <select name="branch_tax_class" id="tax_class" style="width:100%; margin-bottom:15px;">
                <option value="">— Select Tax Class —</option>
                <?php foreach ($tax_classes as $class) : 
                    $class_label = $class === '' ? 'Standard' : $class;
                ?>
                    <option value="<?= esc_attr($class) ?>" <?php selected($selected_tax_class, $class) ?>>
                        <?= esc_html($class_label) ?>
                    </option>
                <?php endforeach; ?>
            </select>

           
            <div id="tax_rates_container" style="display:<?= $selected_tax_class ? 'block' : 'none' ?>;">
                <label for="tax_rate_id">Tax Rate:</label>
                <select name="branch_tax_rate_id" id="tax_rate_id" style="width:100%;">
                    <option value="">— Select Tax Rate —</option>
                    <?php if ($selected_tax_class) : 
                        $rates = WC_Tax::get_rates_for_tax_class($selected_tax_class);
                        foreach ($rates as $rate) : ?>
                            <option value="<?= esc_attr($rate->tax_rate_id) ?>" <?php selected($selected_tax_rate_id, $rate->tax_rate_id) ?>>
                                <?= esc_html("{$rate->tax_rate_name} ({$rate->tax_rate_country}-{$rate->tax_rate_state}: {$rate->tax_rate}%") ?>
                            </option>
                        <?php endforeach; 
                    endif; ?>
                </select>
            </div>
        </div>



            <!-- Basic Branch Details -->
            <p>
                <label for="branch_mobile">Mobile Number:</label>
                <input type="text" name="branch_mobile" id="branch_mobile" value="<?php echo esc_attr( $mobile ); ?>" />
            </p>
            <p>
                <label for="branch_address">Branch Address:</label>
                <textarea name="branch_address" id="branch_address" rows="3"><?php echo esc_textarea( $address ); ?></textarea>
            </p>
            <p>
                <label for="branch_latitude">Branch Latitude:</label>
                <input type="text" name="branch_latitude" id="branch_latitude" value="<?php echo esc_attr( $latitude ); ?>" />
            </p>
            <p>
                <label for="branch_longitude">Branch Longitude:</label>
                <input type="text" name="branch_longitude" id="branch_longitude" value="<?php echo esc_attr( $longitude ); ?>" />
            </p>
            <p>
                <label for="delivery_radius">Delivery Radius (km):</label>
                <input type="number" name="delivery_radius" id="delivery_radius" value="<?php echo esc_attr( $delivery_radius ); ?>" min="0" step="0.1" />
            </p>
            <!-- Button to trigger the Get Coordinates popup -->
            <p>
                <button type="button" id="get-coordinates-btn">Get Coordinates</button>
            </p>

            <!-- Operating Hours Mode Selection -->
            <p class="operating-mode">
                <label for="operating_mode">Operating Hours Mode:</label>
                <select name="operating_mode" id="operating_mode">
                    <option value="all" <?php selected( $branch_hours_mode, 'all' ); ?>>All Days (Same Hours)</option>
                    <option value="custom" <?php selected( $branch_hours_mode, 'custom' ); ?>>Custom (Different Hours per Day)</option>
                </select>
            </p>
            <!-- Operating Hours Container -->
            <div id="branch_hours_container">
                <!-- All Days Section -->
                <div id="all_days_hours" class="hours-section" style="display: <?php echo ($branch_hours_mode == 'all') ? 'block' : 'none'; ?>;">
                    <h4>All Days Operating Hours</h4>
                    <div class="shifts-container">
                        <?php if ( ! empty( $all_shifts ) ) : 
                            foreach ( $all_shifts as $index => $shift ) : ?>
                                <div class="shift">
                                    <input type="time" name="branch_hours[all][<?php echo esc_attr( $index ); ?>][start]" value="<?php echo esc_attr( $shift['start'] ); ?>" />
                                    <input type="time" name="branch_hours[all][<?php echo esc_attr( $index ); ?>][end]" value="<?php echo esc_attr( $shift['end'] ); ?>" />
                                    <button type="button" class="remove-shift">Remove</button>
                                </div>
                            <?php endforeach;
                        endif; ?>
                    </div>
                    <button type="button" class="add-shift-all" data-mode="all">Add Shift</button>
                </div>
                <!-- Custom Days Section -->
                <div id="custom_days_hours" class="hours-section" style="display: <?php echo ($branch_hours_mode == 'custom') ? 'block' : 'none'; ?>;">
                    <?php foreach ( $days as $day ) : 
                        $day_data = isset( $branch_hours_data[$day] ) ? $branch_hours_data[$day] : array();
                        $shifts = isset( $day_data['shifts'] ) ? $day_data['shifts'] : array();
                        $closed = isset( $day_data['closed'] ) ? $day_data['closed'] : 0;
                        ?>
                        <div class="day-hours" data-day="<?php echo esc_attr( $day ); ?>" style="margin-bottom:20px; padding:15px; background:#fff; border:1px solid #ddd; border-radius:4px;">
                            <h4><?php echo esc_html( $day ); ?></h4>
                            <p>
                                <label>
                                    <input type="checkbox" name="branch_hours[<?php echo esc_attr( $day ); ?>][closed]" value="1" <?php checked( $closed, 1 ); ?> />
                                    Closed
                                </label>
                            </p>
                            <div class="shifts-container">
                                <?php if ( ! empty( $shifts ) ) : 
                                    foreach ( $shifts as $index => $shift ) : ?>
                                        <div class="shift">
                                            <input type="time" name="branch_hours[<?php echo esc_attr( $day ); ?>][shifts][<?php echo esc_attr( $index ); ?>][start]" value="<?php echo esc_attr( $shift['start'] ); ?>" />
                                            <input type="time" name="branch_hours[<?php echo esc_attr( $day ); ?>][shifts][<?php echo esc_attr( $index ); ?>][end]" value="<?php echo esc_attr( $shift['end'] ); ?>" />
                                            <button type="button" class="remove-shift">Remove</button>
                                        </div>
                                    <?php endforeach;
                                endif; ?>
                            </div>
                            <button type="button" class="add-shift" data-day="<?php echo esc_attr( $day ); ?>">Add Shift</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <p class="description">Select the operating hours mode and specify shifts accordingly. If using "All Days", the same schedule applies to every day. If "Custom", set each day individually.</p>
        </div>

        <!-- Modal Popup for Getting Coordinates -->
        <div id="get-coordinates-modal">
            <div class="modal-content">
                <h3>Enter Branch URL</h3>
                <input type="text" id="branch-url-input" placeholder="Enter branch URL here">
                <button type="button" id="fetch-coordinates-btn">Get</button>
                <button type="button" id="close-modal-btn" class="cancel-btn">Cancel</button>
            </div>
        </div>
        <?php
    }

    /**
     * Save the meta box data when the post is saved.
     *
     * @param int $post_id The ID of the post being saved.
     */
    public function save_meta_boxes( $post_id ) {
        // Verify nonce.
        if ( ! isset( $_POST['dq_branch_meta_nonce_field'] ) || 
             ! wp_verify_nonce( $_POST['dq_branch_meta_nonce_field'], 'dq_branch_meta_nonce' ) ) {
            return $post_id;
        }
        // Avoid autosave.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        // Check permissions.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }
        // Sanitize and update basic fields.
        $fields = array(
            'branch_mobile',
            'branch_address',
            'branch_latitude',
            'branch_longitude',
            'delivery_radius'
        );
        foreach ( $fields as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
        // Save operating mode.
        if ( isset( $_POST['operating_mode'] ) ) {
            update_post_meta( $post_id, 'branch_hours_mode', sanitize_text_field( $_POST['operating_mode'] ) );
        }
        // Save operating hours data.
        if ( isset( $_POST['branch_hours'] ) && is_array( $_POST['branch_hours'] ) ) {
            $hours_data = $_POST['branch_hours'];
            update_post_meta( $post_id, 'branch_hours', wp_json_encode( $hours_data ) );
        }


    

        if ( isset( $_POST['branch_tax_class'] ) ) {
            update_post_meta( $post_id, 'branch_tax_class', sanitize_text_field( $_POST['branch_tax_class'] ) );
        }
        if ( isset( $_POST['branch_tax_rate_id'] ) ) {
            update_post_meta( $post_id, 'branch_tax_rate_id', sanitize_text_field( $_POST['branch_tax_rate_id'] ) );
        }
        
    }



public function add_scripts() {
    global $post;

    // -- BEGIN FIX --
    // نتأكد أولاً أننا في صفحة تعديل منشور من نوع "فرع" قبل تنفيذ الكود
    if ( ! is_a( $post, 'WP_Post' ) || 'dq_branch' !== $post->post_type ) {
        return; // نخرج من الدالة إذا لم يكن الشرط صحيحاً
    }
    // -- END FIX --

    // Fetch saved rate ID so we can pre-select it in JS
    $saved_rate = get_post_meta( $post->ID, 'branch_tax_rate_id', true );
    ?>
    <script>
    jQuery(document).ready(function($) {
        var savedRate = '<?php echo esc_js( $saved_rate ); ?>';
 
        function loadRates(taxClass) {
            var container  = $('#tax_rates_container');
            var rateSelect = $('#tax_rate_id');
 
            // Show the container and reset options
            container.show();
            rateSelect.html('<option value="">— Select Tax Rate —</option>');
 
            // Fetch rates via AJAX
            $.post( ajaxurl, {
                action:    'dq_get_tax_rates',
                tax_class: $( '#tax_class' ).val() 
                
            }, function(response) {
                if ( response.success && response.data.rates ) {
                    var options = '<option value="">— Select Tax Rate —</option>';
                    $.each(response.data.rates, function(id, label) {
                        options += '<option value="' + id + '"' +
                                   (id === savedRate ? ' selected' : '') +
                                   '>' + label + '</option>';
                    });
                    rateSelect.html(options);
                } else {
                    rateSelect.html('<option value="">— No Rates Found —</option>');
                }
            });
        }
 
        // On tax-class change, load its rates
        $('#tax_class').on('change', function() {
            loadRates( $(this).val() );
        });
 
        // Initial load: if a class was already selected, load its rates
        var initialClass = $('#tax_class').val();
        if ( initialClass !== undefined ) {
            loadRates( initialClass );
        }
    });
    </script>
    <?php
}
    

   
    function dq_get_tax_rates_callback() {
    $tax_class = sanitize_title($_POST['tax_class']);
    $rates = WC_Tax::get_rates_for_tax_class($tax_class);
    $formatted_rates = array();

    foreach ($rates as $rate) {
        $formatted_rates[$rate->tax_rate_id] = sprintf(
            '%s (%s-%s: %s%%)',
            $rate->tax_rate_name,
            $rate->tax_rate_country,
            $rate->tax_rate_state,
            $rate->tax_rate
        );
    }

    wp_send_json_success(array('rates' => $formatted_rates));
}


    
    
}

new DQ_Branch_Meta();
