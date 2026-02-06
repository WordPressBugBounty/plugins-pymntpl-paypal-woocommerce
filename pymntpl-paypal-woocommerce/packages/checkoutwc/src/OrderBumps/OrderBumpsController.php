<?php

namespace PaymentPlugins\PPCP\CheckoutWC\OrderBumps;

use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings;
use PaymentPlugins\WooCommerce\PPCP\Config;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use Objectiv\Plugins\Checkout\Factories\BumpFactory;

class OrderBumpsController {

	private Config $config;

	private AdvancedSettings $advanced_settings;

	public function __construct( Config $config, AdvancedSettings $advanced_settings ) {
		$this->config            = $config;
		$this->advanced_settings = $advanced_settings;
	}

	public function initialize() {
		add_filter( 'cfw_one_click_supported_gateways', [ $this, 'add_payment_gateways' ] );
		add_filter( 'wc_ppcp_payment_method_save_required', [ $this, 'is_payment_method_save_required' ], 10, 2 );
	}

	public function add_payment_gateways( $gateways ) {
		if ( ! $this->advanced_settings->is_vault_enabled() ) {
			return $gateways;
		}
		$gateways['ppcp']          = [
			'path'  => $this->config->get_path( 'src/PaymentGateways/PayPalPaymentGateway.php' ),
			'class' => '\PaymentPlugins\PPCP\CheckoutWC\PaymentGateways\PayPalPaymentGateway'
		];
		$gateways['ppcp_card']     = [
			'path'  => $this->config->get_path( 'src/PaymentGateways/CreditCardPaymentGateway.php' ),
			'class' => '\PaymentPlugins\PPCP\CheckoutWC\PaymentGateways\CreditCardPaymentGateway'
		];
		$gateways['ppcp_applepay'] = [
			'path'  => $this->config->get_path( 'src/PaymentGateways/ApplePayPaymentGateway.php' ),
			'class' => '\PaymentPlugins\PPCP\CheckoutWC\PaymentGateways\ApplePayPaymentGateway'
		];

		return $gateways;
	}

	/**
	 * @param boolean $bool
	 * @param AbstractGateway $payment_method
	 *
	 * @return bool
	 */
	public function is_payment_method_save_required( bool $bool, AbstractGateway $payment_method ) {
		if ( $bool ) {
			return true;
		}

		return $this->has_post_purchase_bumps();
	}

	private function has_post_purchase_bumps() {
		if ( ! class_exists( '\Objectiv\Plugins\Checkout\Features\OrderBumps' ) ) {
			return false;
		}

		// Check if there are any bumps with post_purchase_one_click location
		if ( ! class_exists( '\Objectiv\Plugins\Checkout\Factories\BumpFactory' ) ) {
			return false;
		}

		$all_bumps = BumpFactory::get_all();

		foreach ( $all_bumps as $bump ) {
			$location    = $bump->get_display_location();
			$displayable = $bump->is_displayable();
			$published   = $bump->is_published();

			if ( 'post_purchase_one_click' === $location && $displayable && $published ) {
				return true;
			}
		}

		return false;
	}
}