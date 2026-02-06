<?php

namespace PaymentPlugins\WooCommerce\PPCP\Orders;

class OrderAttributionController {

	public function initialize() {
		add_filter( 'wc_order_attribution_stamp_checkout_html_actions', function ( $actions ) {
			return $this->add_attribution_filters( $actions );
		} );
	}

	private function add_attribution_filters( $actions ) {
		$actions[] = 'wc_ppcp_before_product_payment_methods';
		$actions[] = 'wc_ppcp_before_cart_payment_methods';

		return $actions;
	}
}