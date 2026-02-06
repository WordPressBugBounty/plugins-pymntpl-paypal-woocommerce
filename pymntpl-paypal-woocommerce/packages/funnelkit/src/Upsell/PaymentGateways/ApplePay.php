<?php

namespace PaymentPlugins\PPCP\FunnelKit\Upsell\PaymentGateways;

use PaymentPlugins\PPCP\FunnelKit\Upsell\PaymentGateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Constants;

class ApplePay extends AbstractGateway {

	public $id = 'ppcp_applepay';

	protected $key = 'ppcp_applepay';

	public function has_token( $order ) {
		$token = $order->get_meta( Constants::PAYMENT_METHOD_TOKEN );

		return ! empty( $token );
	}
}