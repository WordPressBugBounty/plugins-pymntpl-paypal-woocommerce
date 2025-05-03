<?php

namespace PaymentPlugins\WooCommerce\PPCP;

use PaymentPlugins\PayPalSDK\PayPalClient;
use PaymentPlugins\WooCommerce\PPCP\Payments\PaymentGateways;

/**
 *
 */
class OrderApplicationUrlHandler {

	private $payment_gateways;

	public function __construct( PaymentGateways $payment_gateways ) {
		$this->payment_gateways = $payment_gateways;
	}

	public function initialize() {
		add_action( 'woocommerce_api_ppcp_checkout_return', [ $this, 'handle_checkout_return' ] );
		add_action( 'woocommerce_api_ppcp_order_return', [ $this, 'handle_order_return' ] );
	}

	public function handle_checkout_return() {
		try {
			check_ajax_referer( 'checkout-nonce', '_checkoutnonce' );
			$order_id = absint( WC()->session->get( 'order_awaiting_payment', 0 ) );
			if ( $order_id ) {
				$order           = wc_get_order( $order_id );
				$token           = isset( $_GET['token'] ) ? \wc_clean( wp_unslash( $_GET['token'] ) ) : null;
				$setup_token_id  = isset( $_GET['approval_token_id'] ) ? \wc_clean( wp_unslash( $_GET['approval_token_id'] ) ) : null;
				$payment_gateway = $this->payment_gateways->get_gateway( $order->get_payment_method() );

				// Set the order ID so it can be retrieved
				$_POST["{$payment_gateway->id}_paypal_order_id"] = $token;

				if ( $setup_token_id && $payment_gateway->supports( 'vault' ) ) {
					/**
					 * @var PaymentMethodController $payment_controller
					 */
					$payment_controller = wc_ppcp_get_container()->get( PaymentMethodController::class );
					$payment_token      = $payment_controller->create_payment_token_from_setup_token( $setup_token_id );
					if ( is_wp_error( $payment_token ) ) {
						throw new \Exception( sprintf( __( 'Error saving payment method. Reason: %s', 'pymntpl-paypal-woocommerce' ), $payment_token->get_error_message() ) );
					}
					$payment_gateway->set_payment_token_id( $payment_token->getId() );
				}

				$result = $payment_gateway->process_payment( $order_id );

				if ( isset( $result['result'] ) && $result['result'] === 'success' ) {
					$redirect = $result['redirect'];
				} else {
					$redirect = wc_get_checkout_url();
				}
				wp_safe_redirect( $redirect );
				exit;
			}
		} catch ( \Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}
	}

	public function handle_order_return() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$order_id          = isset( $_GET['order_id'] ) ? absint( \wc_clean( \wp_unslash( $_GET['order_id'] ) ) ) : null;
		$order_key         = $_GET['order_key'] ?? null;
		$payment_method_id = $_GET['payment_method'] ?? null;
		$token             = isset( $_GET['token'] ) ? \wc_clean( \wp_unslash( $_GET['token'] ) ) : null;
		$setup_token_id    = isset( $_GET['approval_token_id'] ) ? \wc_clean( wp_unslash( $_GET['approval_token_id'] ) ) : null;

		if ( $order_id && $order_key && $payment_method_id ) {
			$order = wc_get_order( $order_id );
			if ( $order && $order->key_is_valid( $order_key ) ) {
				$payment_gateway = $this->payment_gateways->get_gateway( $payment_method_id );
				// Set the order ID so it can be retrieved
				$_POST["{$payment_gateway->id}_paypal_order_id"] = $token;

				if ( $setup_token_id && $payment_gateway->supports( 'vault' ) ) {
					/**
					 * @var PaymentMethodController $payment_controller
					 */
					$payment_controller = wc_ppcp_get_container()->get( PaymentMethodController::class );
					$payment_token      = $payment_controller->create_payment_token_from_setup_token( $setup_token_id );
					if ( is_wp_error( $payment_token ) ) {
						throw new \Exception( sprintf( __( 'Error saving payment method. Reason: %s', 'pymntpl-paypal-woocommerce' ), $payment_token->get_error_message() ) );
					}
					$payment_gateway->set_payment_token_id( $payment_token->getId() );
				}

				$result = $payment_gateway->process_payment( $order_id );

				if ( isset( $result['result'] ) && $result['result'] === 'success' ) {
					wp_safe_redirect( $result['redirect'] );
				} else {
					wp_safe_redirect( $order->get_checkout_payment_url() );
					exit;
				}
			}
		}
	}

}