<?php

namespace PaymentPlugins\WooCommerce\PPCP\Traits;

trait TokenizationTrait {

	protected $token_object_cache = [];

	/**
	 * Returns the payment method token ID string from the request.
	 *
	 * @param \WC_Order $order
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function get_saved_payment_method_token_id_from_request( $order = null ) {
		$key = 'wc-' . $this->id . '-payment-token';

		//phpcs:disable WordPress.Security.NonceVerification.Missing
		$value = \wc_clean( \wp_unslash( $_POST[ $key ] ?? '' ) );

		if ( \is_numeric( $value ) ) {
			if ( isset( $this->token_object_cache[ $value ] ) ) {
				return $this->token_object_cache[ $value ]->get_token();
			}
			if ( $order instanceof \WC_Order ) {
				$user_id = $order->get_customer_id();
			} else {
				$user_id = get_current_user_id();
			}

			$token = \WC_Payment_Tokens::get( (int) $value );
			if ( $token ) {
				if ( $token->get_user_id() !== $user_id ) {
					throw new \Exception( __( 'You do not have permission to use this payment method.', 'pymntpl-paypal-woocommerce' ) );
				}
			} else {
				throw new \Exception( __( 'Invalid payment method ID provided.', 'pymntpl-paypal-woocommerce' ) );
			}
		} else {
			throw new \Exception( __( 'Invalid payment method ID provided.', 'pymntpl-paypal-woocommerce' ) );
		}
		$this->token_object_cache[ $token->get_id() ] = $token;

		return $token->get_token();
	}

	/**
	 * Return true if the customer is using a saved payment method.
	 *
	 * @return void
	 */
	public function should_use_saved_payment_method() {
		$key = 'wc-' . $this->id . '-payment-token';

		//phpcs:disable WordPress.Security.NonceVerification.Missing
		return ! empty( $_POST[ $key ] ) && \wc_clean( \wp_unslash( $_POST[ $key ] ) ) !== 'new';
	}

}