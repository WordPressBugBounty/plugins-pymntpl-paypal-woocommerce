<?php

namespace PaymentPlugins\WooCommerce\PPCP\Rest\Routes;

use PaymentPlugins\PayPalSDK\PayPalClient;
use PaymentPlugins\PayPalSDK\PurchaseUnit;
use PaymentPlugins\WooCommerce\PPCP\Cache\CacheInterface;
use PaymentPlugins\WooCommerce\PPCP\Factories\CoreFactories;
use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\Payments\PaymentGateways;
use PaymentPlugins\WooCommerce\PPCP\Rest\Validators\RouteValidator;
use PaymentPlugins\WooCommerce\PPCP\Utilities\OrderFilterUtil;
use PaymentPlugins\WooCommerce\PPCP\Utils;
use WP_Error;

class AbstractCart extends AbstractRoute {

	protected $client;

	protected $logger;

	protected $factories;

	protected $cache;

	protected $validator;

	public function __construct( PayPalClient $client, Logger $logger, CoreFactories $factories, CacheInterface $cache ) {
		$this->client    = $client;
		$this->logger    = $logger;
		$this->factories = $factories;
		$this->cache     = $cache;
		$this->validator = new RouteValidator();
	}

	public function get_path() {
		return 'cart';
	}

	public function get_routes() {
		// TODO: Implement get_routes() method.
	}

	protected function get_order_from_cart( $request ) {
		$payment_method = $this->get_payment_method_from_request( $request );
		$payment_method->set_save_payment_method( ! empty( $request["{$payment_method->id}_save_payment"] ) );
		$intent = $payment_method->get_option( 'intent' );
		$order  = $this->factories->initialize( WC()->cart, WC()->customer, $payment_method )->order->from_cart( $intent );

		/**
		 * @var PurchaseUnit $purchase_unit
		 */
		$purchase_unit = $order->getPurchaseUnits()->get( 0 );
		// filter the shipping methods
		if ( $purchase_unit->getShipping() ) {
			$shipping = $purchase_unit->getShipping();
			unset( $shipping->options );

			// validate the address info
			$address = $shipping->getAddress();
			if ( ! Utils::is_valid_address( $address, 'shipping' ) ) {
				unset( $shipping->address );
				// If the payer's address is valid, use that as the default address used by PayPal
				if ( Utils::is_valid_address( $order->getPayer()->getAddress(), 'shipping' ) ) {
					$shipping->setAddress( $order->getPayer()->getAddress() );
				} else {
					unset( $purchase_unit->shipping );
				}
			}
		}
		OrderFilterUtil::filter_order( $order );
		do_action( 'wc_ppcp_get_order_from_cart', $order, $request );

		return $order;
	}

	/**
	 * @param $request
	 *
	 * @return \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway
	 */
	protected function get_payment_method_from_request( $request ) {
		return WC()->payment_gateways()->payment_gateways()[ $request['payment_method'] ];
	}

	protected function calculate_totals() {
		WC()->cart->calculate_totals();
	}

}