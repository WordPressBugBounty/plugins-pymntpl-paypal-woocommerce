<?php

namespace PaymentPlugins\PPCP\FunnelKit\Upsell\PaymentGateways;

use PaymentPlugins\WooCommerce\PPCP\Constants;

class CreditCard extends AbstractGateway {

	public $id = 'ppcp_card';

	protected $key = 'ppcp_card';

	public function has_token( $order ) {
		$token = $order->get_meta( Constants::PAYMENT_METHOD_TOKEN );

		return ! empty( $token );
	}

}