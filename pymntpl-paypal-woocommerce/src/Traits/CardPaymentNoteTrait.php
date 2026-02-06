<?php

namespace PaymentPlugins\WooCommerce\PPCP\Traits;

use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\PaymentResult;

/**
 * Trait CardPaymentNoteTrait
 *
 * Provides enhanced order note functionality for card-based payment gateways.
 * Adds detailed payment information including the payment method title from the token.
 *
 * @package PaymentPlugins\WooCommerce\PPCP\Traits
 */
trait CardPaymentNoteTrait {

	/**
	 * Add a payment complete note to the order with card payment details.
	 *
	 * @param \WC_Order     $order  The WooCommerce order object.
	 * @param PaymentResult $result The payment result object.
	 *
	 * @return void
	 */
	public function add_payment_complete_note( \WC_Order $order, PaymentResult $result ) {
		if ( $result->is_captured() ) {
			$charge_text = sprintf( __( 'Capture ID: %s', 'pymntpl-paypal-woocommerce' ), $result->get_capture_id() );
		} else {
			$charge_text = sprintf( __( 'Authorization ID: %s', 'pymntpl-paypal-woocommerce' ), $result->get_authorization_id() );
		}
		$token = $this->get_payment_method_token_instance();
		$token->initialize_from_payment_source( $result->get_paypal_order()->getPaymentSource() );

		$order->add_order_note(
			sprintf(
				__( 'PayPal order %1$s created. %2$s. Payment method: %3$s', 'pymntpl-paypal-woocommerce' ),
				$result->paypal_order->id,
				$charge_text,
				$token->get_payment_method_title()
			)
		);
	}

}