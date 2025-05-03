<?php

namespace PaymentPlugins\WooCommerce\PPCP;

use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\PayPalSDK\PayPalClient;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Payments\PaymentGateways;
use PaymentPlugins\WooCommerce\PPCP\Tokens\AbstractToken;
use PaymentPlugins\WooCommerce\PPCP\Utilities\PaymentMethodUtils;

class CustomerController {

	public function initialize() {
		add_action( 'init', [ $this, 'init_action' ] );
		add_action( 'wc_ppcp_save_order_meta_data', [ $this, 'save_order_meta' ], 10, 4 );
	}

	/**
	 * @return void
	 */
	public function init_action() {
		if ( is_user_logged_in() ) {
			$customer = Customer::instance( get_current_user_id() );
			if ( $customer->try_migration() ) {
				/**
				 * @var PayPalClient $client
				 */
				$client = wc_ppcp_get_container()->get( PayPalClient::class );

				$response = $client->paymentTokensV3->all( [
					'customer_id' => $customer->get_id(),
					'page_size'   => 20
				] );

				if ( ! is_wp_error( $response ) ) {
					/**
					 * @var PaymentGateways $gateways
					 */
					$gateways        = wc_ppcp_get_container()->get( PaymentGateways::class );
					$payment_methods = $gateways->get_payment_method_registry()->get_registered_integrations();
					foreach ( $response->payment_tokens as $payment_token ) {
						foreach ( $payment_methods as $payment_method ) {
							/**
							 * @var \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway $payment_method
							 */
							if ( isset( $payment_token->payment_source[ $payment_method->get_payment_method_type() ] ) ) {
								$token = $payment_method->get_payment_method_token_instance();
								$token->initialize_from_payment_token( $payment_token );
								$token->set_user_id( $customer->get_user_id() );

								// make sure this token doesn't already exist.
								$args = [
									'user_id'    => $token->get_user_id(),
									'gateway_id' => $token->get_gateway_id()
								];
								// for the paypal type, we only want a single token at any given time, so we don't include the 'token' arg.
								if ( $payment_method->get_payment_method_type() !== 'paypal' ) {
									$args['token'] = $token->get_token();
								}
								if ( ! PaymentMethodUtils::token_exists( $args ) ) {
									$token->save();
								}
							}
						}
					}
				}
			}
		}
	}

	public function save_order_meta( \WC_Order $order, Order $paypal_order, AbstractGateway $payment_method, AbstractToken $token ) {
		// Store payment method in order meta for 3rd party plugins
		if ( $payment_method->supports( 'vault' ) && $payment_method->should_save_after_payment_complete( $paypal_order ) ) {
			if ( $order->get_customer_id() ) {
				$token->set_user_id( $order->get_customer_id() );
				$token->save();

				$customer = Customer::instance( $order->get_customer_id() );
				if ( ! $customer->has_id() ) {
					$customer->set_id( $token->get_customer_id() );
					$customer->save();
				}
			}
			$order->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
		}
	}

}