<?php

namespace PaymentPlugins\WooCommerce\PPCP\Factories;

use PaymentPlugins\PayPalSDK\ExperienceContext;
use PaymentPlugins\PayPalSDK\OrderApplicationContext;
use PaymentPlugins\PayPalSDK\OrderUpdateCallbackConfig;
use PaymentPlugins\PayPalSDK\PaymentMethod;
use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings;
use PaymentPlugins\WooCommerce\PPCP\Utilities\CartTokenHandler;
use PaymentPlugins\WooCommerce\PPCP\Utilities\LocaleUtil;
use PaymentPlugins\WooCommerce\PPCP\Utilities\ShippingUtil;

class ExperienceContextFactory extends AbstractFactory {

	private $settings;

	private $shipping_preference = '';

	public function __construct( AdvancedSettings $settings, ...$args ) {
		$this->settings = $settings;
		parent::__construct( ...$args );
	}

	/**
	 * Build the experience_context object for the current payment method and cart.
	 * Only keys declared by the gateway via get_experience_context_keys() are included.
	 */
	public function from_cart(): ?object {
		return $this->build( $this->cart->needs_shipping() );
	}

	public function from_order(): ?object {
		return $this->build( $this->order->needs_shipping_address(), true );
	}

	private function build( bool $needs_shipping, bool $set_provided = false ): ?object {
		if ( ! $this->payment_method ) {
			return null;
		}

		$supported_keys = $this->payment_method->get_experience_context_keys();
		if ( empty( $supported_keys ) ) {
			return null;
		}

		$context = new ExperienceContext();

		if ( in_array( 'brand_name', $supported_keys, true ) ) {
			$brand_name = $this->settings->get_option( 'display_name' );
			if ( $brand_name ) {
				$context->setBrandName( substr( $brand_name, 0, 127 ) );
			}
		}

		if ( in_array( 'locale', $supported_keys, true ) && $this->settings->is_site_locale() ) {
			$locale = LocaleUtil::get_site_locale( true );
			if ( LocaleUtil::is_locale_supported( $locale, true ) ) {
				$context->setLocale( $locale );
			}
		}

		if ( in_array( 'shipping_preference', $supported_keys, true ) ) {
			if ( ! $needs_shipping ) {
				$context->setShippingPreference( OrderApplicationContext::NO_SHIPPING );
			} else {
				if ( $this->shipping_preference !== '' ) {
					$context->setShippingPreference( $this->shipping_preference );
				} else {
					if ( $set_provided ) {
						$context->setShippingPreference( OrderApplicationContext::SET_PROVIDED_ADDRESS );
					} else {
						$context->setShippingPreference( OrderApplicationContext::GET_FROM_FILE );
					}
				}
			}
		}

		if ( in_array( 'user_action', $supported_keys, true ) ) {
			$context->setUserAction( OrderApplicationContext::PAY_NOW );
		}

		if ( in_array( 'payment_method_preference', $supported_keys, true ) ) {
			$context->setPaymentMethodPreference(
				$this->payment_method->is_immediate_payment_required()
					? PaymentMethod::IMMEDIATE_PAYMENT_REQUIRED
					: PaymentMethod::UNRESTRICTED
			);
		}

		if ( in_array( 'order_update_callback_config', $supported_keys, true )
		     && $context->getShippingPreference() === OrderApplicationContext::GET_FROM_FILE
		     && ShippingUtil::is_server_side_callback_supported() ) {
			$cart_token = CartTokenHandler::get_cart_token( (string) WC()->session->get_customer_id() );
			$context->setOrderUpdateCallbackConfig(
				( new OrderUpdateCallbackConfig() )
					->setCallbackUrl( add_query_arg(
						[ 'cart_token' => rawurlencode( $cart_token ) ],
						rest_url( 'wc-ppcp/v1/cart/order-update-callback' )
					) )
					->setCallbackEvents( [ 'SHIPPING_ADDRESS', 'SHIPPING_OPTIONS' ] )
			);
		}

		if ( in_array( 'return_url', $supported_keys, true ) ) {
			if ( $this->order ) {
				$context->setReturnUrl( add_query_arg( [
					'order_id'       => $this->order->get_id(),
					'order_key'      => $this->order->get_order_key(),
					'payment_method' => $this->payment_method->id,
				], WC()->api_request_url( 'ppcp_order_return' ) ) );
			} else {
				$context->setReturnUrl( add_query_arg(
					[ '_checkoutnonce' => wp_create_nonce( 'checkout-nonce' ) ],
					WC()->api_request_url( 'ppcp_checkout_return' )
				) );
			}
		}

		if ( in_array( 'cancel_url', $supported_keys, true ) ) {
			$args = [ 'ppcp_action' => 'canceled' ];
			if ( $this->order ) {
				$args['order_id'] = $this->order->get_id();
			}
			$context->setCancelUrl( add_query_arg( $args, wc_get_checkout_url() ) );
		}

		return $context;
	}

	public function set_shipping_preference( $value ) {
		$this->shipping_preference = $value;
	}
}
