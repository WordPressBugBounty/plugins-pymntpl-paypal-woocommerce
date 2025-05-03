<?php

namespace PaymentPlugins\PPCP\Stripe;

use PaymentPlugins\WooCommerce\PPCP\Container\Container;
use PaymentPlugins\WooCommerce\PPCP\Package\PackageInterface;
use PaymentPlugins\WooCommerce\PPCP\PaymentMethodRegistry;
use PaymentPlugins\WooCommerce\PPCP\Payments\PaymentGateways;

class PaymentButtonController {

	private $container;

	private $settings;

	public function __construct( Container $container, \PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings $settings ) {
		$this->container = $container;
		$this->settings  = $settings;
		$this->initialize();
	}

	private function initialize() {
		if ( $this->is_stripe_express_enabled() ) {
			add_action( 'woocommerce_ppcp_payment_methods_registration', [ $this, 'register_payment_methods' ], 20 );
			if ( did_action( 'woocommerce_ppcp_payment_methods_registration' ) ) {
				$this->register_payment_methods(
					$this->container->get( PaymentMethodRegistry::class )
				);
			}
			add_filter( 'wc_stripe_product_payment_methods', [ $this, 'get_product_payment_methods' ] );
			add_filter( 'wc_stripe_cart_payment_methods', [ $this, 'get_cart_payment_methods' ] );
			add_filter( 'wc_stripe_express_payment_methods', [ $this, 'get_express_checkout_payment_methods' ] );
			$this->container->get( \PaymentPlugins\WooCommerce\PPCP\PaymentButtonController::class )->set_render_cart_buttons( false );
			$this->container->get( \PaymentPlugins\WooCommerce\PPCP\PaymentButtonController::class )->set_render_product_buttons( false );
			$this->container->get( \PaymentPlugins\WooCommerce\PPCP\PaymentButtonController::class )->set_render_express_buttons( false );
		}
	}

	private function is_stripe_express_enabled() {
		return wc_string_to_bool( $this->settings->get_option( 'stripe_express' ) );
	}

	/**
	 * @param PaymentMethodRegistry registry
	 *
	 * @throws \Exception
	 */
	public function register_payment_methods( $registry ) {
		$registry->register( $this->container->get( PayPalPaymentGateway::class ) );
	}

	public function get_product_payment_methods( $gateways ) {
		$gateway = $this->container->get( PayPalPaymentGateway::class );
		if ( $gateway->product_checkout_enabled() ) {
			foreach ( $gateways as $idx => $gw ) {
				if ( $gw->id === $gateway->id ) {
					$gateways[ $idx ] = $gateway;
					break;
				}
			}
		}

		return $gateways;
	}

	public function get_cart_payment_methods( $gateways ) {
		$gateway = $this->container->get( PayPalPaymentGateway::class );
		if ( $gateway->cart_checkout_enabled() ) {
			$gateways[ $gateway->id ] = $gateway;
		}

		return $gateways;
	}

	public function get_express_checkout_payment_methods( $gateways ) {
		$gateway = $this->container->get( PayPalPaymentGateway::class );
		if ( $gateway->banner_checkout_enabled() ) {
			$gateways[ $gateway->id ] = $gateway;
		}

		return $gateways;
	}

}