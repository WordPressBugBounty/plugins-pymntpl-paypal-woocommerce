<?php

namespace PaymentPlugins\PPCP\Blocks;

use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;

class FrontendScripts {

	private $assets;

	public function __construct( AssetsApi $assets ) {
		$this->assets = $assets;
	}

	public function initialize() {
		$this->register_scripts();
	}

	private function register_scripts() {
		$this->assets->register_script( 'wc-ppcp-block-data', 'build/block-data.js' );
		$this->assets->register_script( 'wc-ppcp-blocks-commons', 'build/blocks-commons.js' );
		$this->assets->register_script( 'wc-ppcp-blocks-legacy-commons', 'build/legacy/blocks-legacy-commons.js' );
		$this->assets->register_script( 'wc-ppcp-blocks-checkout', 'build/checkout-block.js', [ 'wc-ppcp-blocks-commons' ] );
		$this->assets->register_script( 'wc-ppcp-blocks-fastlane', 'build/fastlane-block.js', [ 'wc-ppcp-blocks-commons' ] );

		$this->assets->register_style( 'wc-ppcp-blocks-styles', 'build/styles.css' );
	}

}