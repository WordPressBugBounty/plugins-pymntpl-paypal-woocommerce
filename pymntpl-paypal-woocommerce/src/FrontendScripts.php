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
		$this->assets->register_script( 'wc-ppcp-legacy-commons', 'build/legacy/legacy-frontend-commons.js' );
		$this->assets->register_script( 'wc-ppcp-frontend-commons', 'build/js/frontend-commons.js' );
		$this->assets->register_script( 'wc-ppcp-utils', 'build/js/utils.js' );
		$this->assets->register_script( 'wc-ppcp-product', 'build/js/product.js' );
		$this->assets->register_script( 'wc-ppcp-cart', 'build/js/cart.js' );
		$this->assets->register_script( 'wc-ppcp-minicart', 'build/js/minicart.js' );
		$this->assets->register_script( 'wc-ppcp-payment-methods', 'build/js/payment-methods.js' );
		$this->assets->register_script( 'wc-ppcp-fastlane-checkout', 'build/js/fastlane-checkout.js' );

		$this->assets->register_style( 'wc-ppcp-style', 'build/css/styles.css' );
	}

}