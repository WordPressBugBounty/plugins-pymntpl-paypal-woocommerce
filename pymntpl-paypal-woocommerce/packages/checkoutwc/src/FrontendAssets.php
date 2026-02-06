<?php

namespace PaymentPlugins\PPCP\CheckoutWC;

use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;

class FrontendAssets {

	private $assets;

	public function __construct( AssetsApi $assets ) {
		$this->assets = $assets;
	}

	public function initialize() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		$this->register_scripts();;
	}

	public function enqueue_scripts() {
		if ( is_checkout() ) {
			wp_enqueue_script( 'wc-ppcp-checkout-wc' );
			wp_enqueue_style( 'wc-ppcp-checkoutwc-style' );
		}
	}

	private function register_scripts() {
		$this->assets->register_script( 'wc-ppcp-checkout-wc', 'build/checkout.js' );
		$this->assets->register_style( 'wc-ppcp-checkoutwc-style', 'build/styles.css' );
	}
}