<?php

namespace PaymentPlugins\WooCommerce\PPCP\Shortcodes;

use PaymentPlugins\WooCommerce\PPCP\ContextHandler;
use PaymentPlugins\WooCommerce\PPCP\Utils;

class ProductPaymentButtons extends AbstractPaymentButtons {

	public $id = 'ppcp_product_buttons';

	public function is_supported_page( ContextHandler $context ) {
		return $context->is_product();
	}

	public function get_supported_pages() {
		return [ 'product' ];
	}

	public function get_script_handles() {
		$handles = [];
		foreach ( $this->get_gateways() as $gateway ) {
			$handles = array_merge( $handles, $gateway->get_product_script_handles() );
		}

		return $handles;
	}

	public function render() {
		global $product;
		if ( $product ) {
			//$this->assets_data->add( 'product', Utils::get_product_data( $product ) );
			$this->templates->load_template( 'product/payment-methods.php', [
				'payment_methods' => $this->get_gateways(),
				'position'        => 'bottom'
			] );
		}
	}

}