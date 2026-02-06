<?php

namespace PaymentPlugins\PPCP\Blocks;

use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;

class FrontendScripts {

	private $assets;

	private $context;

	public function __construct( AssetsApi $assets ) {
		$this->assets = $assets;
	}

	public function initialize() {
		add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after', [ $this, 'enqueue_scripts' ] );
		add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after', [ $this, 'enqueue_scripts' ] );
		$this->register_scripts();
	}

	private function register_scripts() {
		$this->assets->register_script( 'wc-ppcp-block-data', 'build/block-data.js' );
		$this->assets->register_script( 'wc-ppcp-blocks-vendors', 'build/wc-ppcp-blocks-vendors.js' );
		$this->assets->register_script( 'wc-ppcp-blocks-legacy-vendors', 'build/legacy/wc-ppcp-blocks-legacy-vendors.js' );
		$this->assets->register_script( 'wc-ppcp-blocks-checkout', 'build/checkout-block.js' );
		$this->assets->register_script( 'wc-ppcp-blocks-fastlane', 'build/fastlane-block.js' );

		$this->assets->register_style( 'wc-ppcp-blocks-styles', 'build/styles.css' );
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'wc-ppcp-blocks-styles' );
		wp_enqueue_style( 'wc-ppcp-style' );
	}

}