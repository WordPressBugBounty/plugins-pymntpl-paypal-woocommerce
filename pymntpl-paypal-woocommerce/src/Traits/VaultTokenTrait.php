<?php

namespace PaymentPlugins\WooCommerce\PPCP\Traits;

use PaymentPlugins\PayPalSDK\Order;

trait VaultTokenTrait {

	protected static array $VaultTokenTraitFeatures = [
		'vault'
	];

	/**
	 * @var string
	 */
	protected $payment_token_id;

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
			if ( $order instanceof \WC_Order && $order->get_customer_id() > 0 ) {
				$user_id = $order->get_customer_id();
			} else {
				$user_id = get_current_user_id();
			}

			$token = \WC_Payment_Tokens::get( (int) $value );
			if ( $token && $token->get_user_id() > 0 ) {
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
	 * @return bool
	 */
	public function should_use_saved_payment_method() {
		$key = 'wc-' . $this->id . '-payment-token';

		//phpcs:disable WordPress.Security.NonceVerification.Missing
		return ! empty( $_POST[ $key ] ) && \wc_clean( \wp_unslash( $_POST[ $key ] ) ) !== 'new';
	}

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

		//phpcs:disable WordPress.Security.NonceVerification.Missing
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