<?php

namespace PaymentPlugins\WooCommerce\PPCP\Factories;

use PaymentPlugins\PayPalSDK\PaymentSource;
use PaymentPlugins\PayPalSDK\Token;
use PaymentPlugins\WooCommerce\PPCP\Constants;
use PaymentPlugins\WooCommerce\PPCP\Customer;

class PaymentSourceFactory extends AbstractFactory {

	public function from_cart() {
		$customer = null;

		if ( WC()->customer && WC()->customer->get_id() ) {
			$customer = Customer::instance( WC()->customer->get_id() );
		}
		$source = $this->create(
			$this->payment_method->is_payment_method_save_required(),
			$customer
		);

		return $source;
	}

	public function from_checkout() {
		$customer = null;
		if ( $this->order->get_customer_id() ) {
			$customer = Customer::instance( $this->order->get_customer_id() );
		}
		$source = $this->create(
			$this->payment_method->is_payment_method_save_required( $this->order ),
			$customer
		);

		/**
		 * If there is a payment token ID in the request then this is a Fastlane payment request.
		 */
		if ( $this->payment_method->supports( 'vault' ) && $this->payment_method->get_payment_token_id_from_request() ) {
			if ( $this->payment_method->get_payment_method_type() === 'card' ) {
				$source->card->single_use_token = $this->payment_method->get_payment_token_id_from_request();
			}
		}

		return $source;
	}

	public function from_order( $context = 'one_time' ) {
		$payment_source = ( new PaymentSource() )->setToken( new Token() );

		$id = $this->order->get_meta( Constants::PAYMENT_METHOD_TOKEN );

		if ( $id ) {
			$key            = $this->payment_method->get_payment_method_type();
			$payment_source = new PaymentSource( [
				$key => [
					'vault_id' => $id,
				]
			] );

			if ( $key === 'apple_pay' ) {
				$payment_source->$key->stored_credential = new \stdClass();
				if ( $context === 'one_time' ) {
					$payment_source->$key->stored_credential->payment_initiator = 'MERCHANT';
					$payment_source->$key->stored_credential->payment_type      = 'UNSCHEDULED';
				} else {
					$payment_source->$key->stored_credential->payment_initiator = 'MERCHANT';
					$payment_source->$key->stored_credential->payment_type      = 'RECURRING';
				}
			}
		} else {
			$id = $this->order->get_meta( Constants::BILLING_AGREEMENT_ID );
			if ( ! $id ) {
				$id = $this->order->get_meta( '_paypal_subscription_id' );
			}
			if ( $id ) {
				$payment_source->token->setId( $id );
				$payment_source->token->setType( Token::BILLING_AGREEMENT );
			}
		}

		/**
		 * @var PaymentSource $payment_source
		 */
		$payment_source = apply_filters( 'wc_ppcp_payment_source_from_order', $payment_source, $this->order );

		if ( $payment_source->getToken() && \is_string( $payment_source->getToken()->getId() ) ) {
			if ( strpos( $payment_source->getToken()->getId(), 'B-' ) === 0 ) {
				if ( $payment_source->getToken()->getType() !== Token::BILLING_AGREEMENT ) {
					$payment_source->getToken()->setType( Token::BILLING_AGREEMENT );
				}
			}
		}

		return $payment_source;
	}

	/**
	 * @param bool $store_in_vault
	 * @param Customer|null $customer
	 *
	 * @return \PaymentPlugins\PayPalSDK\PaymentSource|null
	 */
	private function create( $store_in_vault = false, $customer = null ) {
		$source = null;
		if ( $this->payment_method ) {
			$payment_type = $this->payment_method->get_payment_method_type();
			$source       = new PaymentSource( [
				$payment_type => [
					'attributes' => new \stdClass()
				]
			] );

			if ( $this->payment_method->supports( '3ds' ) && $this->payment_method->is_3ds_enabled() ) {
				//is_force_3ds_enabled
				$source->$payment_type->attributes->verification = (object) [
					'method' => $this->payment_method->is_force_3ds_enabled() ? 'SCA_ALWAYS' : 'SCA_WHEN_REQUIRED'
				];
			}

			if ( $this->payment_method->supports( 'vault' ) && $store_in_vault ) {
				$source->$payment_type->attributes->vault = (object) [
					'store_in_vault' => 'ON_SUCCESS'
				];
				if ( $payment_type === 'paypal' ) {
					$source->$payment_type->attributes->vault->usage_type  = 'MERCHANT';
					$source->$payment_type->permit_multiple_payment_tokens = true;
				}
				if ( $payment_type === 'apple_pay' ) {
					$source->$payment_type->stored_credential                    = new \stdClass();
					$source->$payment_type->stored_credential->payment_initiator = 'CUSTOMER';
					$source->$payment_type->stored_credential->payment_type      = 'RECURRING';
				}
				if ( $customer ) {
					if ( $customer->has_id() ) {
						$source->$payment_type->attributes->customer = (object) [
							'id' => $customer->get_id()
						];
					}
				}
			}
		}

		return $source;
	}

}