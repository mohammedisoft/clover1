<?php
/**
 * Error mapper class
 *
 * @package woo-clover-payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WOO_CLV_ERRORMAPPER
 */
class WOO_CLV_ERRORMAPPER {

	/**
	 * Error mapper array.
	 *
	 * @return type
	 */
	public static function get_localized_messages() {
		return apply_filters(
			'wc_clv_localized_messages',
			array(
				'amount_too_large'        => __( 'Transaction could not be processed. Please contact support.', 'woo-clv-payments' ),
				'card_declined'           => __( 'Transaction declined. Please verify card information or use a different card.', 'woo-clv-payments' ),
				'card_on_file_missing'    => __( 'Transaction failed; card information is incorrect.', 'woo-clv-payments' ),
				'charge_already_captured' => __( 'The transaction has already been processed.', 'woo-clv-payments' ),
				'charge_already_refunded' => __( 'The transaction has already been refunded.', 'woo-clv-payments' ),
				'email_invalid'           => __( 'Email ID is invalid; please try again with a valid email ID.', 'woo-clv-payments' ),
				'expired_card'            => __( 'Card expired: please try again with a valid card number.', 'woo-clv-payments' ),
				'incorrect_cvc'           => __( 'Incorrect CVV: please try again with a valid CVV.', 'woo-clv-payments' ),
				'incorrect_number'        => __( 'Incorrect card number: please try again with a valid card number.', 'woo-clv-payments' ),
				'invalid_card_type'       => __( 'Card brand is invalid or not supported. Please use a valid card and try again.', 'woo-clv-payments' ),
				'invalid_charge_amount'   => __( 'Invalid transaction amount. Please contact support.', 'woo-clv-payments' ),
				'invalid_request'         => __( 'Card is invalid: please try again with a valid card.', 'woo-clv-payments' ),
				'invalid_tip_amount'      => __( 'Invalid tip amount: please correct and try again.', 'woo-clv-payments' ),
				'invalid_tax_amount'      => __( 'Invalid tax amount: please correct and try again.', 'woo-clv-payments' ),
				'missing'                 => __( 'Unable to process transaction.', 'woo-clv-payments' ),
				'order_already_paid'      => __( 'The order has already been paid for.', 'woo-clv-payments' ),
				'processing_error'        => __( 'Transaction could not be processed.', 'woo-clv-payments' ),
				'rate_limit'              => __( 'Transaction could not be processed. Please contact support.', 'woo-clv-payments' ),
				'resource_missing'        => __( 'Transaction could not be processed due to incorrect or invalid information.', 'woo-clv-payments' ),
				'token_already_used'      => __( 'Transaction could not be processed; please re-enter card details and try again.', 'woo-clv-payments' ),
				'invalid_key'             => __( 'Unauthorized. Please contact support.', 'woo-clv-payments' ),
				'invalid_details'         => __( 'Transaction failed; invalid information provided.', 'woo-clv-payments' ),
				'unexpected'              => __( 'Transaction could not be processed. Please try again.', 'woo-clv-payments' ),
			)
		);
	}

	/**
	 * Method to invoke array.
	 *
	 * @param type $response Code filter.
	 * @return type
	 */
	public static function get_localized_error_message( $response ) {
		$localized_messages        = self::get_localized_messages();
				$localized_message = isset( $localized_messages[ $response['error_code'] ] ) ? $localized_messages[ $response['error_code'] ] : $response['message'];
		return $localized_message;
	}

}
