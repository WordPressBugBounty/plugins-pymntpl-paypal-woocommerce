<?php

namespace PaymentPlugins\WooCommerce\PPCP;

use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;

class FrontendScripts {

	private $assets;

	public function __construct( AssetsApi $assets ) {
		$this->assets = $assets;
	}

	public function initialize() {
		add_action( 'init', [ $this, 'register_scripts' ] );
	}

	public function register_scripts() {
		$this->assets->register_script( 'wc-ppcp-frontend-vendors', 'build/js/wc-ppcp-frontend-vendors.js' );
		$this->assets->register_script( 'wc-ppcp-utils', 'build/js/utils.js' );
		$this->assets->register_script( 'wc-ppcp-product', 'build/js/product.js' );
		$this->assets->register_script( 'wc-ppcp-cart', 'build/js/cart.js' );
		$this->assets->register_script( 'wc-ppcp-order', 'build/js/order.js' );
		$this->assets->register_script( 'wc-ppcp-actions', 'build/js/actions.js' );
		$this->assets->register_script( 'wc-ppcp-context', 'build/js/context.js' );
		$this->assets->register_script( 'wc-ppcp-controllers', 'build/js/controllers.js' );
		$this->assets->register_script( 'wc-ppcp-payment-methods', 'build/js/payment-methods.js' );
		$this->assets->register_script( 'wc-ppcp-fastlane-checkout', 'build/js/fastlane-checkout.js', [ 'wc-ppcp-card-gateway' ] );
		$this->assets->register_script( 'wc-ppcp-paylater-messages', 'build/js/paylater-messages.js' );

		wp_register_script( 'wc-ppcp-googlepay-external', 'https://pay.google.com/gp/p/js/pay.js' );

		$this->assets->register_style( 'wc-ppcp-style', 'build/css/styles.css' );
	}

}