<?php

namespace PaymentPlugins\WooCommerce\PPCP\Shortcodes;

use PaymentPlugins\WooCommerce\PPCP\ContextHandler;
use PaymentPlugins\WooCommerce\PPCP\Utils;

class CartPaymentButtons extends AbstractPaymentButtons {

	public $id = 'ppcp_cart_buttons';

	public function is_supported_page( ContextHandler $context ) {
		return $context->is_cart();
	}

	public function get_supported_pages() {
		return [ 'cart' ];
	}

	public function get_script_handles() {
		$handles = [];
		foreach ( $this->get_gateways() as $gateway ) {
			$handles = array_merge( $handles, $gateway->get_cart_script_handles() );
		}

		return $handles;
	}

	public function render() {
		$this->templates->load_template( 'cart/payment-methods.php', [
			'payment_methods'   => [ $this->get_gateway() ],
			'below_add_to_cart' => $this->attributes->get( 'location' ) === 'below'
		] );
	}

}