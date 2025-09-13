<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Clover_Blocks_Support class.
 *
 * @extends AbstractPaymentMethodType
 */
final class WC_Clover_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = 'clover_payments';

	/**
	 * Initializes payment method.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->settings = get_option( 'woocommerce_clover_payments_settings', array() );
	}

	/**
	 * Checks if the gateway is enabled, the status of SSL, and if there are any missing fields.
	 * True if gateway is available and active, false otherwise.
	 *
	 * @return boolean
	 */
	public function is_active(): bool {
		$test_mode = $this->is_test_mode();

		if ( $this->get_setting( 'enabled' ) === 'no' ) {
			wc_get_logger()->error( 'Clover Payments is not enabled.' );
			return false;
		}
		if ( empty( $this->get_merchant_id( $test_mode ) ) ) {
			wc_get_logger()->error( 'Merchant ID is not set.' );
			return false;
		}
		if ( empty( $this->get_public_key( $test_mode ) ) ) {
			wc_get_logger()->error( 'Public Key is not set.' );
			return false;
		}
		if ( empty( $this->get_private_key( $test_mode ) ) ) {
			wc_get_logger()->error( 'Private Key is not set.' );
			return false;
		}
		if ( ! $test_mode && ! is_ssl() ) {
			wc_get_logger()->error( 'Page is not using SSL.' );
			return false;
		}

		//If the Checkout page contains a Checkout Block, return true; else return false.
		//Replace with return true if shortcode form is ever removed.
		//return WC_Blocks_Utils::has_block_in_page( wc_get_page_id( 'checkout' ), 'woocommerce/checkout' );
		return true;
	}

	/**
	 * Registers Style, Clover SDK, and JS script.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles(): array {
		$script_path       = '/build/index.js';
		$style_path        = '/build/index.css';
		$script_asset_path = plugin_abspath() . 'build/index.asset.php';

		$script_url = plugin_url() . $script_path;
		$style_url  = plugin_url() . $style_path;

		$version      = WC_CLOVER_PAYMENTS_VERSION;
		$dependencies = array();

		if ( file_exists( $script_asset_path ) ) {
			$asset = require $script_asset_path;

			$version      = is_array( $asset ) && isset( $asset['version'] )
				? $asset['version']
				: $version;
			$dependencies = is_array( $asset ) && isset( $asset['dependencies'] )
				? $asset['dependencies']
				: $dependencies;
		}

		$test_mode = $this->is_test_mode();
		$this->register_clover_sdk( $test_mode );

		wp_register_script(
			'wc-clover-payments-checkout-block-integration',
			$script_url,
			array_merge( array( 'clover' ), $dependencies ),
			$version,
			true
		);

		wp_enqueue_style(
			'wc-clover-payments-checkout-block-style',
			$style_url,
			array(),
			$version
		);

		wp_set_script_translations(
			'wc-clover-payments-checkout-block-integration',
			'woo-clv-payments',
			plugin_abspath() . 'languages'
		);

		return array( 'wc-clover-payments-checkout-block-integration' );
	}

	/**
	 * @param $test_mode
	 *
	 * @return void
	 */
	private function register_clover_sdk( $test_mode ): void {
		if ( $test_mode ) {
			wp_register_script(
				'clover',
				'https://checkout.sandbox.dev.clover.com/sdk.js',
				array(),
				WC_CLOVER_PAYMENTS_VERSION,
				true
			);
		} else {
			wp_register_script(
				'clover',
				'https://checkout.clover.com/sdk.js',
				array(),
				WC_CLOVER_PAYMENTS_VERSION,
				true
			);
		}
	}

	/**
	 * Collects and returns values needed in front-end JS script.
	 *
	 * @return array
	 */
	public function get_payment_method_data(): array {
		$test_mode = $this->is_test_mode();
		return array(
			'merchantId' => $this->get_merchant_id( $test_mode ),
			'publicKey'  => $this->get_public_key( $test_mode ),
			'locale'     => $this->get_clover_formatted_locale(),
			'title'      => $this->get_setting( 'title' ),
		);
	}

	/**
	 * Checks if Sandbox is selected.
	 *
	 * @return bool
	 */
	private function is_test_mode(): bool {
		return $this->get_setting( 'environment' ) === 'sandbox';
	}

	/**
	 * Gets respective Merchant ID for Production or Sandbox.
	 *
	 * @param $test_mode
	 *
	 * @return string is true if in Sandbox, otherwise false.
	 */
	private function get_merchant_id( $test_mode ): string {
		return $test_mode
			? $this->get_setting( 'test_merchant_id' )
			: $this->get_setting( 'merchant_id' );
	}

	/**
	 * Gets respective public key for Production or Sandbox.
	 *
	 * @param $test_mode
	 * is true if in Sandbox, otherwise false.
	 *
	 * @return string
	 */
	private function get_public_key( $test_mode ): string {
		return $test_mode
			? $this->get_setting( 'test_publishable_key' )
			: $this->get_setting( 'publishable_key' );
	}

	/**
	 * Gets respective private key for Production or Sandbox.
	 *
	 * @param $test_mode
	 * is true if in Sandbox, otherwise false.
	 *
	 * @return string
	 */
	private function get_private_key( $test_mode ): string {
		return $test_mode
			? $this->get_setting( 'test_private_key' )
			: $this->get_setting( 'private_key' );
	}

	/**
	 * Replaces WP underscored locale with a Clover compatible dash.
	 *
	 * @return string
	 */
	private function get_clover_formatted_locale(): string {
		$wp_locale = get_locale();
		return str_replace( '_', '-', $wp_locale );
	}
}
