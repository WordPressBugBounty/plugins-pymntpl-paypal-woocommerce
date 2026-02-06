<?php

namespace PaymentPlugins\WooCommerce\PPCP\Conversion;

use PaymentPlugins\WooCommerce\PPCP\Conversion\GeneralPayPalPlugin;

class CheckoutPluginsPayPalWooCommerce extends GeneralPayPalPlugin {

	public $id = 'cppw_paypal';

	protected $payment_token_id = '_cppw_agreement_id';
}