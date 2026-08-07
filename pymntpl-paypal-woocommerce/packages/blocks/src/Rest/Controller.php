<?php

namespace PaymentPlugins\PPCP\Blocks\Rest;

use PaymentPlugins\PayPalSDK\OrderApplicationContext;

class Controller {

	public function __construct() {
		add_action( 'wc_ppcp_get_order_from_cart', [ $this, 'update_order_before_create' ], 10, 2 );
		add_filter( 'wc_ppcp_cart_order_shipping_prefix', [ $this, 'get_shipping_prefix' ], 10, 2 );
	}

	/**
	 * @param \PaymentPlugins\PayPalSDK\Order $order
	 * @param \WP_REST_Request                $request
	 */
	public function update_order_before_create( $order, $request ) {
		if ( ! empty( $request['address_provided'] ) ) {
			$context = $order->getPaymentSource()->getExperienceContext();
			if ( $context && $context->getShippingPreference() === OrderApplicationContext::GET_FROM_FILE ) {
				$purchase_unit = $order->getPurchaseUnits()->get( 0 );
				if ( ! $purchase_unit->getShipping() || ! $purchase_unit->getShipping()->getAddress() ) {
					$context->setShippingPreference( OrderApplicationContext::NO_SHIPPING );
				} else {
					$context->setShippingPreference( OrderApplicationContext::SET_PROVIDED_ADDRESS );
				}
				// order_update_callback_config's SHIPPING_ADDRESS/SHIPPING_OPTIONS callback events are only
				// valid when shipping_preference is GET_FROM_FILE. Since it was just overridden away from
				// that, the callback config set by ExperienceContextFactory::build() must be cleared too,
				// otherwise PayPal rejects the order with an invalid shipping_preference/callback combination.
				if ( $context->getOrderUpdateCallbackConfig() ) {
					unset( $context->order_update_callback_config );
				}
			}
		}

		return $order;
	}

	public function get_shipping_prefix( $shipping_prefix, $request ) {
		if ( ! empty( $request['address_provided'] ) ) {
			if ( WC()->cart->needs_shipping() ) {
				$shipping_prefix = 'shipping';
			}
		}

		return $shipping_prefix;
	}

	/**
	 * @param $fields
	 * @param $request
	 *
	 * @return mixed
	 * @deprecated 1.1.10 - WooCommerce now handles the phone number optional/required correctly in the checkout block.
	 */
	public function checkout_validation_fields( $fields, $request ) {
		// Checkout Blocks manage their own settings for if the phone or email is required. They don't
		// have a solution yet for validating those so for now unset these fields.
		if ( isset( $request['checkout_blocks'] ) && \wc_string_to_bool( $request['checkout_blocks'] ) ) {
			unset( $fields['billing']['billing_phone'] );
			unset( $fields['billing']['billing_email'] );
		}

		return $fields;
	}

}