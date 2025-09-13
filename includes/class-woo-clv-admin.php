<?php

/**
 * Admin class
 *
 * @package woo-clover-payments
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Class WOO_CLV_ADMIN
 */
class WOO_CLV_ADMIN extends WOO_CLV_GATEWAY {

	use ApiHelper;

	public $id;

	public string $environment;
	public bool $testmode;
	public string $private_key;
	public string $publishable_key;
	public string $merchant;
	public bool $debugmode;
	public bool $ischarge;

	/**
	 *  Constructor
	 */
	public function __construct() {
		$this->id = 'clover_payments';
		$this->has_fields = true;
		$this->method_title = __('Clover Payments', 'woo-clv-payments');
		$this->method_description = __('Clover simplifies the lives of small businesses with tailored, all-in-one payments, and business management systems that can be implemented quickly and grow with the business.', 'woo-clv-payments');
		$this->supports = array(
			'products',
			'refunds',
			'tokenization',
			'add_payment_method',
		);

		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		$this->title = $this->get_option('title');
		$this->enabled = $this->get_option('enabled');
		$this->environment = $this->get_option('environment');
		$this->testmode = ('sandbox' === $this->get_option('environment'));
		$this->private_key = $this->testmode ? $this->get_option('test_private_key') : $this->get_option('private_key');
		$this->publishable_key = $this->testmode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');
		$this->merchant = $this->testmode ? $this->get_option('test_merchant_id') : $this->get_option('merchant_id');
		$this->debugmode = ('yes' === $this->get_option('debug'));
		$this->ischarge = ('charge' === $this->get_option('payment_action'));
		$this->update_option('capture', ('charge' === $this->get_option('payment_action')) ? 'yes' : 'no');

		// Calls sanitize_settings() to sanitize merchant entered plugin admin settings.
		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );

		// This action hook saves the settings.
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

		// We need custom JavaScript to obtain a token.
		add_action('admin_enqueue_scripts', array($this, 'clover_admin_scripts'));
		add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
		add_action('woocommerce_order_item_add_action_buttons', array($this, 'add_capture_button'));
		//This action hook  lets us modify content of after order details for Admin
		add_action('woocommerce_admin_order_data_after_order_details', array($this, 'wc_clv_payment_card_info_on_order_details'), 10, 1);

		// This filter hook lets us modify content of order details table to add card brand and last4 digits
		// other option add action hook to add card details outside order details table
		add_filter('woocommerce_get_order_item_totals', array($this, 'add_card_details_to_account_order'), 10, 3);
	}

	/**
	 * Updates Order detail table on 'My account/order-view' page
	 * adds card details to a new column.
	 */
	public function add_card_details_to_account_order($total_rows, $order) {
		// add card details to $total_rows to be displayed in order details table
		$card_details = $this->get_card_details($order);
		if (isset($card_details) && !empty($card_details)) {
			$total_rows['card_details'] = array(
				'label' => __('Card details:', 'woocommerce'),
				'value' => esc_html($card_details)
			);

			// 1. saving the values of items totals to be reordered
			$order_total = $total_rows['order_total'];

			// 2. remove items totals to be reordered
			unset($total_rows['order_total']);

			// 3 Reinsert removed items totals in the right order
			$total_rows['order_total'] = $order_total;
		}
		return $total_rows;

	}

	/**
	 * Display payment card fields in to Admin order details
	 */
	public function wc_clv_payment_card_info_on_order_details($order) {
		/*
		* get all the meta data values we need to display payment details
		*/
		$card_details = $this->get_card_details($order);
		if (isset($card_details) && !empty($card_details)) {
			?>
            <br class="clear"/>
            <h4>Payment Information </h4>
            <br class="clear"/>
			<?php
			echo $card_details;
			?>

			<?php
		}
	}


	/**
	 * Load scripts at admin.
	 */
	public function clover_admin_scripts() {
		if (is_admin()) {
			wp_enqueue_script(
				'admin_js',
				plugins_url('../admin/js/woo-clv-admin.js', __FILE__),
				null,
				'1.0.0',
				true
			);
		}
	}

	/**
	 * Configuration fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enabled', 'woo-clv-payments'),
				'type' => 'select',
				'options' => array(
					'yes' => __('Yes', 'woo-clv-payments'),
					'no' => __('No', 'woo-clv-payments'),
				),
				'description' => __('Clover Payments is available in the United States and Canada. Please select "Yes" to enable in checkout.', 'woo-clv-payments'),
				'default' => 'no',
				'js_trigger' => true,
			),
			'title' => array(
				'title' => __('Title', 'woo-clv-payments'),
				'type' => 'text',
				'description' => __('Appears as the title of the payment form on the checkout page.', 'woo-clv-payments'),
				'default' => __( 'Credit/Debit Card', 'woo-clv-payments' )
			),
			'environment' => array(
				'title' => __('Environment', 'woo-clv-payments'),
				'type' => 'select',
				'description' => __('We provide Merchants and Developers the option to test their integrations against their Clover Sandbox accounts before going live. Select “Production” to send transactions to your live Clover account.', 'woo-clv-payments'),
				'default' => 'sandbox',
				'options' => array(
					'sandbox' => __('Sandbox', 'woo-clv-payments'),
					'production' => __('Production', 'woo-clv-payments'),
				),
			),
			'test_merchant_id' => array(
				'title' => __('Sandbox Merchant ID', 'woo-clv-payments'),
				'type' => 'text',
				'class' => 'clvsdfields',
			),
			'test_publishable_key' => array(
				'title' => __('Sandbox Public Key', 'woo-clv-payments'),
				'type' => 'text',
				'description' => __('Please visit <a href="https://sandbox.dev.clover.com/" target="_blank" rel="noopener noreferrer">Clover Developer Portal</a> to obtain a Public Sandbox API Key.', 'woo-clv-payments'),
				'class' => 'clvsdfields',
			),
			'test_private_key' => array(
				'title' => __('Sandbox Private Key', 'woo-clv-payments'),
				'type' => 'password',
				'description' => __('Please visit <a href="https://sandbox.dev.clover.com/" target="_blank" rel="noopener noreferrer">Clover Developer Portal</a> to obtain a Private Sandbox API Key.', 'woo-clv-payments'),
				'class' => 'clvsdfields',
			),
			'merchant_id' => array(
				'title' => __('Merchant ID', 'woo-clv-payments'),
				'type' => 'text',
				'class' => 'clvfields',
			),
			'publishable_key' => array(
				'title' => __('Public Key', 'woo-clv-payments'),
				'type' => 'text',
				'description' => __('Please visit <a href="https://clover.com/" target="_blank" rel="noopener noreferrer">Clover Merchant Portal</a> to obtain a Public API Key.', 'woo-clv-payments'),
				'class' => 'clvfields',
			),
			'private_key' => array(
				'title' => __('Private Key', 'woo-clv-payments'),
				'type' => 'password',
				'description' => __('Please visit <a href="https://clover.com/" target="_blank" rel="noopener noreferrer">Clover Merchant Portal</a> to obtain a Private API Key.', 'woo-clv-payments'),
				'class' => 'clvfields',
			),
			'payment_action' => array(
				'title' => __('Payment Action', 'woo-clv-payments'),
				'type' => 'select',
				'default' => 'charge',
				'options' => array(
					'charge' => __('Authorize and Capture', 'woo-clv-payments'),
					'authorize' => __('Authorize', 'woo-clv-payments'),
				),
			),
			'debug' => array(
				'title' => __('Debug', 'woo-clv-payments'),
				'type' => 'select',
				'options' => array(
					'yes' => __('Yes', 'woo-clv-payments'),
					'no' => __('No', 'woo-clv-payments'),
				),
				'default' => 'yes',
			),
		);
	}


	/**
	 * Sanitizes plugin admin settings.
	 *
	 * Currently, this method capitalizes any lower case letter that was entered for the Production and Sandbox Merchant
	 * IDs. Can be expanded to modify other settings if need be.
	 *
	 * @since  2.2.0
	 * @param  array $settings Array of admin settings values.
	 * @return array Modified admin settings array.
	 */
	public function sanitize_settings( array $settings ): array {
		if ( isset( $settings['merchant_id'] ) ) {
			$settings['merchant_id'] = trim( strtoupper( $settings['merchant_id'] ) );
		}
		if ( isset( $settings['test_merchant_id'] ) ) {
			$settings['test_merchant_id'] = trim( strtoupper( $settings['test_merchant_id'] ) );
		}
		return $settings;
	}

	/**
	 * Validates Merchant ID.
	 *
	 * Takes the merchant entered Production Merchant ID and checks if it meets the following criteria: it is exactly 13
	 * characters, and only contains numbers and letters. If either condition is not met, and the currently selected
	 * environment is "Production", an error message is logged and displayed on the admin settings page after "Save changes"
	 * is clicked.
	 *
	 * @since  2.2.0
	 * @param  string $key merchant_id in admin settings array.
	 * @param  string $value value of merchant_id in admin settings array.
	 * @return string
	 */
	public function validate_merchant_id_field( string $key, string $value ): string {
		$is_production = $_POST[ $this->plugin_id . $this->id . '_environment' ] === 'production';

		if ( ! preg_match('/^(|[A-Za-z0-9]{13})$/', $value ) && $is_production ) {
			WC_Admin_Settings::add_error( esc_html__( 'Merchant ID is invalid.', 'woo-clv-payments' ) );
			wc_get_logger()->error( 'Merchant ID is invalid.', array(
				'merchant_id' => $value
			) );
    	}
    	return $value;
	}

	/**
	 * Validates Sandbox Merchant ID.
	 *
	 * Takes the merchant entered Sandbox Merchant ID and checks if it meets the following criteria: it is exactly 13
	 * characters, and only contains numbers and letters. If either condition is not met, and the currently selected
	 * environment is "Sandbox", an error message is logged and displayed on the admin settings page after "Save changes"
	 * is clicked.
	 *
	 * @since  2.2.0
	 * @param  string $key merchant_id in admin settings array.
	 * @param  string $value value of merchant_id in admin settings array.
	 * @return string
	 */
	public function validate_test_merchant_id_field( string $key, string $value ): string {
		$is_sandbox = $_POST[ $this->plugin_id . $this->id . '_environment' ] === 'sandbox';

		if ( ! preg_match('/^(|[A-Za-z0-9]{13})$/', $value ) && $is_sandbox ) {
			WC_Admin_Settings::add_error( esc_html__( 'Sandbox Merchant ID is invalid.', 'woo-clv-payments' ) );
			wc_get_logger()->error( 'Sandbox Merchant ID is invalid.', array(
				'sandbox_merchant_id' => $value
			) );
		}
		return $value;
	}

	/**
	 * Display checkout form.
	 */
	public function payment_fields()
	{
		ob_start();
		$this->elements_form();
		ob_end_flush();
	}

	/**
	 * Build checkout form.
	 */
	public function elements_form()
	{
		?>
		<fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
            <span id="clover-ssl-message" style=" font-size: 12px;color: red;">
            <?php
				if (!$this->testmode && !is_ssl()) {
					esc_html_e('Enable SSL to continue payment in production mode', 'woo-clv-payments');
					return;
				}
			?>
                    </span>
			<span id="clover-surcharge-details">
            <?php
				$surcharge = $this->getSurcharge($this->testmode);
				$message = $surcharge['message'];
				if ($surcharge['supported'] && isset($message)) {
					echo esc_html($message);
				}
			?>
                    </span>
			<div id="gap_form"><input type="hidden" name="PostVar"/>
				<form action="/charge" method="post" class="clover-gateway" id="payment-form">

					<div class="form-row top-row">
						<div id="card-number" class="field card-number"></div>
						<div class="input-errors" id="card-number-errors" role="alert"></div>
					</div>

					<div class="form-row">
						<div class="form-row clv-midfield">
							<div id="card-date" class="field third-width"></div>
							<div class="input-errors" id="card-date-errors" role="alert"></div>
						</div>

						<div class="form-row clv-midfield">
							<div id="card-cvv" class="field third-width"></div>
							<div class="input-errors" id="card-cvv-errors" role="alert"></div>
						</div>
					</div>

					<div class="form-row">
						<div id="card-postal-code" class="field third-width"></div>
						<div class="input-errors" id="card-postal-code-errors" role="alert"></div>
					</div>

					<div id="card-errors" role="alert"></div>
					<div id="card-response" role="alert"></div>
				</form>
			</div>

			<div class="control">
				<input type="hidden" id="cloverToken" name="clover_token" class="input" data-bind="value:cloverToken">
				<?php $clover_token_once = wp_create_nonce('clover-token-nonce'); ?>
				<input type="hidden" id="cloverTokenNonce" name="clover_token_nonce" class="input" value="<?php echo esc_attr($clover_token_once); ?>">
				<input type="hidden" id="transresult" name="transaction_result" class="input" data-bind="value:transactionResult">
			</div>

			<br/>

			<div class="clear"></div>
		</fieldset>
		<?php
	}

	/**
	 * *
	 *
	 * @param  type $test_mode Testmode.
	 * @return type
	 */
	private function getSurcharge($test_mode) {
		$merchantid = $this->merchant;
		$surcharge_url = $this->get_surcharge_url($merchantid, $test_mode);
		$response = $this->call_api_post($surcharge_url, array(), array(), 'GET');
		$parse = $this->parse_surcharge($response);
		return $parse;
	}

	/**
	 * Load frontend scripts.
	 *
	 * @return type
	 */
	public function payment_scripts() {
		if ( $this->enabled === 'no' ) {
			wc_get_logger()->error( 'Clover Payments is not enabled.' );
			return;
		}
		if ( empty( $this->merchant ) ) {
			wc_get_logger()->error( 'Merchant ID is not set.' );
			return;
		}
		if ( empty( $this->publishable_key ) ) {
			wc_get_logger()->error( 'Public Key is not set.' );
			return;
		}
		if ( empty( $this->private_key) ) {
			wc_get_logger()->error( 'Private Key is not set.' );
			return;
		}
		if ( ! $this->testmode && ! is_ssl() ) {
			wc_get_logger()->error( 'Page is not using SSL.' );
			return;
		}

		//If the Checkout page contains a Checkout Block, return;
		if ( WC_Blocks_Utils::has_block_in_page( wc_get_page_id( 'checkout' ), 'woocommerce/checkout' ) ) {
			return;
		}

		if (!$this->testmode) {
			wp_enqueue_script(
				'clover_js',
				'https://checkout.clover.com/sdk.js',
				array(),
				'1.0.0',
				true
			);
		} else {
			wp_enqueue_script(
				'clover_js',
				'https://checkout.sandbox.dev.clover.com/sdk.js',
				array(),
				'1.0.0',
				true
			);
		}

		wp_register_style(
			'custom_styles',
			plugins_url('../public/css/woo-clv-custom.css', __FILE__),
			array(),
			'1.0.0'
		);

		wp_register_script(
			'custom_scripts',
			plugins_url('../public/js/woo-clv-custom.js',__FILE__),
			array('jquery', 'clover_js'),
			'1.0.0',
			false
		);

		wp_localize_script(
			'custom_scripts',
			'clover_params',
			array(
				'publishableKey' => $this->publishable_key,
				'locale' => get_locale(),
				'merchant' => $this->merchant,
			)
		);

		wp_enqueue_script('custom_scripts');
		wp_enqueue_style('custom_styles');
	}

	/**
	 * Process checkout.
	 *
	 * @param  type $order_id Order id.
	 * @return array
	 * @global type $woocommerce Woocommerce.
	 */
	public function process_payment($order_id) {
		try {
			global $woocommerce;
			$order = new WC_Order($order_id);
			$success_link = $this->get_return_url($order);
			$environment = $this->environment;
			$charge_url = $this->get_charge_url($environment);
			$private_key = $this->private_key;
			$uuid = $this->uuidv4();
			$ip_address = $this->get_ip_address();
			$header = $this->buildHeader($private_key, $uuid, $ip_address);

			// get clover token value, if empty notify's user that transaction could not be processed and
			// at admin's end shows the woocommerce order has been failed
			$clovertokennonce = $this->get_token();
			if( empty( trim( $clovertokennonce['clovertoken'] ) ) ) {
				// log the information
				if ( $this->debugmode ) {
					wc_get_logger()->warning( "Transaction could not be processed: Clover Token does not exist." );
				};

				$order->update_status('failed');
				return array(
					'result' => 'failed',
					'message' => __("Transaction could not be processed. Please try again.", 'woo-clv-payments'),
					'error_code' => 'Unexpected',
				);

			}
			// continue if token has been created and retrieved successfully
			$charge_data = $this->getChargeData($order,$clovertokennonce['clovertoken']);
			$response = $this->call_api_post($charge_url, $header, $charge_data, 'POST');
			$parseresponse = $this->handle_response($charge_data, $response);

			$charge_data['customer']['first_name'] = "-REDACTED-";
			$charge_data['customer']['last_name'] = "-REDACTED-";

			if ( $this->debugmode ) {
				wc_get_logger()->info( "Charge Request.", [ "Request" => $charge_data ] );
				wc_get_logger()->info( "Charge Response.", [ "Response" => $response ] );
			}

			if ($parseresponse['captured']) {
				$woocommerce->cart->empty_cart();

				// adding card details( card brand and last4 to order's meta data in post meta table
				$this->add_card_details($order_id, $response);

				if ($this->ischarge) {
					$order->payment_complete($parseresponse['TXN_ID']);
				} else {
					$order->set_transaction_id($parseresponse['TXN_ID']);
					$order->update_status('on-hold', __('Awaiting offline payment.', 'woo-clv-payments'));
				}

				return array(
					'result' => 'success',
					'redirect' => $success_link,
				);
			} else {

				$failure_message = WOO_CLV_ERRORMAPPER::get_localized_error_message($parseresponse);
				$order->update_status('failed');
				//wc_add_notice($failure_message, 'error');
				return array(
					'result' => 'failed',
					'message' => $failure_message,
					'error_code' => $parseresponse['error_code'],
				);
			}

		} catch (Exception $e) {

			$order->update_status('failed');
			//wc_add_notice(__('An error has occurred, please try again', 'woo-clv-payments'), 'error');
			return array(
				'result' => 'failed',
				'exceptionMessage' => $e->getMessage(),
				'message' => __('An error has occurred; please try again.', 'woo-clv-payments'),
				'error_code' => 'Unexpected',
			);
		}
	}

	/**
	 * Build charge data.    *
	 *
	 * @param  type $order Order id.
	 * @return type
	 */
	private function getChargeData($order,$token) {
		// get the ID of the order
		$order_id = $order->get_id();
		// get Customer Data for the order
		$customer_data = $this->get_customer_data($order_id);
		$currency = $order->get_currency();
		$amount = $order->get_total();
		$tax = $order->get_total_tax();
		$charge_data = array(
			'amount' => $this->converttocents($amount, $currency),
			'currency' => strtolower( $currency ),
			'source' => $token,
			'capture' => $this->ischarge,
			'description' => $this->ischarge ? 'Authorize and Capture' : 'Authorize',
			'metadata' => array('shopping_cart' => $this->framework_version()),
			'customer' => $customer_data,
			'tax_amount' => $this->converttocents($tax, $currency),
			'skip_default_convenience_fee' => true
		);
		return $charge_data;
	}

	/**
	 * Build Header.
	 *
	 * @param  type $private_key For validation.
	 * @param  type $uuid        Unique Field.
	 * @return type
	 */
	private function buildHeader($private_key, $uuid, $ip) {
		$header = array(
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
			'authorization' => 'Bearer ' . $private_key,
			'Idempotency-key' => $uuid,
			'x-forwarded-for' => $ip
		);
		return $header;
	}

	/**
	 * Admin Capture button.
	 *
	 * @param  type $order Order id.
	 * @return type
	 */
	public function add_capture_button($order) {
		if (!($order->payment_method === $this->id)) {
			return;
		}
		if (in_array($order->get_status(), array('cancelled', 'refunded', 'failed'), true)) {
			return;
		}
		if ($order->get_date_paid()) {
			return;
		}

		$order_id_nonce = wp_create_nonce('order-id-nonce');
		?>
        <button data-order_id_nonce="<?php echo esc_attr($order_id_nonce); ?>"
                data-order_id="<?php echo esc_attr($order->get_id()); ?>" type="button"
                class="button button-primary clv-wc-payment-gateway-capture"><?php esc_html_e('Capture Charge', 'woo-clv-payments'); ?></button>
		<?php
	}

	/**
	 * Capture action.   *
	 *
	 * @param  type $order        Order id.
	 * @param  type $bulk_capture Optional.
	 * @return type
	 */
	public function process_capture($order, $bulk_capture = false) {
		if (!($order->payment_method === $this->id)) {
			return array(
				'success' => false,
				'message' => __('Please select the correct order.', 'woo-clv-payments'),
				'code' => 400,
				'processed' => false,
			);
		}
		if (in_array($order->get_status(), array('cancelled', 'refunded', 'failed'), true)) {
			return array(
				'success' => false,
				'message' => __('Unable to capture canceled, refunded, or failed orders.', 'woo-clv-payments'),
				'code' => 400,
				'processed' => false,
			);
		}
		if ($order->get_date_paid()) {
			return array(
				'success' => false,
				'message' => __('Already captured: unable to process again.', 'woo-clv-payments'),
				'code' => 400,
				'processed' => false,
			);
		}
		try {
			$private_key = $this->private_key;
			$charge_id = $order->get_transaction_id();
			$environment = $this->environment;
			$amount = $order->get_total();
			$header = $this->buildRefundHeader($private_key);
			$capture_url = $this->get_capture_url($environment, $charge_id);
			$capture_data = $this->getCaptureData($order);
			$response = $this->call_api_post($capture_url, $header, $capture_data, 'POST');
			$parseresponse = $this->handle_response($capture_data, $response);

			if ( $this->debugmode ) {
				wc_get_logger()->info( "Capture Request.", [ "Request" => $capture_data ] );
				wc_get_logger()->info( "Capture Response.", [ "Response" => $response ] );
			};

			if ($parseresponse['captured']) {
				$message = $parseresponse['message'];
				/* translators: %1$s %2$s %3$s: amount txid message */
				$message = sprintf(__('Captured %1$s - Capture ID: %2$s - Status: %3$s', 'woo-clv-payments'), $amount, $parseresponse['TXN_ID'], $message);
				$order->update_meta_data('_clover_capture_id', $parseresponse['TXN_ID']);
				$order->add_order_note($message);
				$order->payment_complete($parseresponse['TXN_ID']);
				return array(
					'success' => true,
					'code' => 200,
					'message' => $message,
					'processed' => true,
				);
			} else {
				$failure_message = WOO_CLV_ERRORMAPPER::get_localized_error_message($parseresponse);

				return array(
					'success' => false,
					'message' => $failure_message,
					'code' => $parseresponse['error_code'],
					'processed' => true,
				);
			}
		} catch (Exception $e) {
			$order->update_status('failed');
			wc_add_notice(__('An error has occurred; please try again.', 'woo-clv-payments'), 'error');
			return array(
				'success' => false,
				'message' => $e->getMessage(),
				'code' => 500,
				'processed' => true,
			);
		}
	}

	/**
	 * Build data.
	 *
	 * @param  type $order Order id.
	 * @return type
	 */
	private function getCaptureData($order) {
		$currency = $order->get_currency();
		$amount = $order->get_total();
		$charge_data = array(
			'amount' => $this->converttocents($amount, $currency),
			'description' => 'capture_charge',
			'metadata' => array('shopping_cart' => $this->framework_version()),
		);
		return $charge_data;
	}

	/**
	 * returns a customer object to be passed along with charge data retrieving information using user's order id.
	 * @return type
	 */
	private function get_customer_data($order_id) {

		$customer_data = array();
		$order = new WC_Order($order_id);
		// Get the user ID from an Order ID
		$user_id = get_post_meta( $order_id, '_customer_user', true );
		$user = get_userdata( $user_id );
		if($user){

			// Get an instance of the WC_Customer Object from the user ID
			$customer = new WC_Customer( $user_id );
			$customer_data['first_name']   = $customer->get_first_name();
			$customer_data['last_name']    = $customer->get_last_name();
			$customer_data['phone'] = $customer->get_billing_phone();

		}
		else
		{
			$customer_data['first_name']   = $order->get_billing_first_name();
			$customer_data['last_name']    = $order->get_billing_last_name();
			$customer_data['phone']    = $order->get_billing_phone();

		}
		$customer_data['email']   = $order->get_billing_email();

		return $customer_data;
	}

	/**
	 * checks for clover token value and returns the token to be used in getchargedata call.
	 * @return type
	 */
	private function get_token() {
		$clover_token_nonce[ 'clovertoken' ] = sanitize_text_field( wp_unslash( $_POST[ 'clover_token' ] ) );
		return $clover_token_nonce;
	}

	public function get_ip_address() {
		return WC_Geolocation::get_ip_address();
	}
}
