<?php

namespace PaymentPlugins\WooCommerce\PPCP\Traits;

use PaymentPlugins\PayPalSDK\Order;

trait VaultTokenTrait {

	/**
	 * @var string
	 */
	protected $payment_token_id;

	/**
	 * Returns the payment token ID from the $_POST.
	 *
	 * @return mixed|null
	 */
	public function get_payment_token_id_from_request() {
		$key = $this->id . '_payment_token';

		if ( $this->payment_token_id ) {
			return $this->payment_token_id;
		}

		return isset( $_POST[ $key ] ) ? \wc_clean( \wp_unslash( $_POST[ $key ] ) ) : null;
	}

	/**
	 * Given a PayPal order object, determine if the customer's payment method needs to be saved.
	 *
	 * @param \PaymentPlugins\PayPalSDK\Order $order
	 *
	 * @return void
	 */
	public function should_save_after_payment_complete( Order $order ) {
		$result       = false;
		$payment_type = $this->get_payment_method_type();
		if ( isset( $order->payment_source->$payment_type->attributes->vault->status ) ) {
			if ( $order->payment_source->$payment_type->attributes->vault->status === 'VAULTED' ) {
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * @param string $value
	 *
	 * @return void
	 */
	public function set_payment_token_id( $value ) {
		$this->payment_token_id = $value;
	}

	/**
	 * Returns the payment_token_id.
	 *
	 * @return string
	 */
	public function get_payment_token_id() {
		return $this->payment_token_id;
	}

}