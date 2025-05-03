<?php

namespace PaymentPlugins\WooCommerce\PPCP\Factories;

use PaymentPlugins\PayPalSDK\OrderApplicationContext;
use PaymentPlugins\PayPalSDK\PaymentSource;
use PaymentPlugins\PayPalSDK\SetupToken;
use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings;
use PaymentPlugins\WooCommerce\PPCP\ContextHandler;
use PaymentPlugins\WooCommerce\PPCP\Customer;

class SetupTokenFactory extends AbstractFactory {

	public function create( $context = 'checkout' ) {
		/**
		 * @var ContextHandler $context_handler
		 */
		$context_handler = wc_ppcp_get_container()->get( ContextHandler::class );
		/**
		 * @var AdvancedSettings $settings ;
		 */
		$settings = wc_ppcp_get_container()->get( AdvancedSettings::class );

		$context_handler->set_context( $context );

		$payment_type = $this->payment_method->get_payment_method_type();

		$setup_token = ( new SetupToken() )
			->setPaymentSource( new PaymentSource( [
					$payment_type => (object) [
						'attributes' => new \stdClass()
					]
				] )
			);

		if ( $payment_type === 'paypal' ) {
			$setup_token->payment_source->paypal->usage_type                     = 'MERCHANT';
			$setup_token->payment_source->paypal->permit_multiple_payment_tokens = true;

			if ( $context_handler->is_checkout() ) {
				if ( $settings->is_shipping_address_disabled() ) {
					$setup_token->payment_source->paypal->experience_context = (object) [
						'shipping_preference' => WC()->cart->needs_shipping() ? OrderApplicationContext::SET_PROVIDED_ADDRESS : OrderApplicationContext::NO_SHIPPING
					];
				} else {
					$setup_token->payment_source->paypal->experience_context = (object) [
						'shipping_preference' => WC()->cart->needs_shipping() ? OrderApplicationContext::GET_FROM_FILE : OrderApplicationContext::NO_SHIPPING
					];
				}

				if ( $this->get_order() ) {
					$return_url = add_query_arg( [
						'order_id'       => $this->order->get_id(),
						'order_key'      => $this->order->get_order_key(),
						'payment_method' => 'ppcp'
					], WC()->api_request_url( 'ppcp_order_return' ) );
				} else {
					$return_url = add_query_arg( [
						'_checkoutnonce' => wp_create_nonce( 'checkout-nonce' )
					], WC()->api_request_url( 'ppcp_checkout_return' ) );
				}

				$setup_token->payment_source->paypal->experience_context = (object) [
					'return_url' => $return_url,
					'cancel_url' => \wc_get_checkout_url()
				];
			} elseif ( $context_handler->is_order_pay() && $this->get_order() ) {
				$setup_token->payment_source->paypal->experience_context = (object) [
					'shipping_preference' => OrderApplicationContext::NO_SHIPPING
				];
				$return_url                                              = add_query_arg( [
					'order_id'       => $this->order->get_id(),
					'order_key'      => $this->order->get_order_key(),
					'payment_method' => 'ppcp'
				], WC()->api_request_url( 'ppcp_order_return' ) );

				$setup_token->payment_source->paypal->experience_context = (object) [
					'return_url' => $return_url,
					'cancel_url' => $this->get_order()->get_checkout_payment_url()
				];
			} elseif ( $context_handler->is_add_payment_method() ) {
				$setup_token->payment_source->paypal->experience_context = (object) [
					'shipping_preference' => OrderApplicationContext::NO_SHIPPING
				];
				$setup_token->payment_source->paypal->experience_context = (object) [
					'return_url' => \wc_get_account_endpoint_url( 'payment-methods' ),
					'cancel_url' => \wc_get_account_endpoint_url( 'add-payment-method' )
				];
			} else {
				$setup_token->payment_source->paypal->experience_context = (object) [
					'shipping_preference' => WC()->cart->needs_shipping() ? OrderApplicationContext::GET_FROM_FILE : OrderApplicationContext::NO_SHIPPING
				];
				$setup_token->payment_source->paypal->experience_context = (object) [
					'return_url' => \wc_get_checkout_url(),
					'cancel_url' => \wc_get_checkout_url()
				];
			}
		}

		if ( is_user_logged_in() ) {
			$customer = Customer::instance( get_current_user_id() );
			if ( $customer->has_id() ) {
				$setup_token->setCustomer( new \PaymentPlugins\PayPalSDK\Customer( [
					'id' => $customer->get_id()
				] ) );
			}
		}

		if ( $this->payment_method->supports( '3ds' ) && $this->payment_method->is_3ds_enabled() ) {
			//is_force_3ds_enabled
			$setup_token->payment_source->$payment_type->attributes->verification = [
				'method' => $this->payment_method->is_force_3ds_enabled() ? 'SCA_ALWAYS' : 'SCA_WHEN_REQUIRED'
			];
		}

		return apply_filters( 'wc_ppcp_get_setup_token', $setup_token, $this );
	}

}