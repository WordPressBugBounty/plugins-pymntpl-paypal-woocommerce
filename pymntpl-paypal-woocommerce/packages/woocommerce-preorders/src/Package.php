<?php

namespace PaymentPlugins\PPCP\WooCommercePreOrders;

use PaymentPlugins\PayPalSDK\PayPalClient;
use PaymentPlugins\WooCommerce\PPCP\Factories\CoreFactories;
use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\Package\AbstractPackage;

class Package extends AbstractPackage {

	public $id = 'woocommerce_preorders';

	public function is_active() {
		return class_exists( 'WC_Pre_Orders' );
	}

	public function initialize() {
		$this->container->get( PreOrdersController::class )->initialize();
	}

	public function register_dependencies() {
		$this->container->register( PreOrdersController::class, function ( $container ) {
			return new PreOrdersController(
				$container->get( PaymentController::class )
			);
		} );
		$this->container->register( PaymentController::class, function ( $container ) {
			return new PaymentController(
				$container->get( PayPalClient::class ),
				$container->get( CoreFactories::class ),
				$container->get( Logger::class )
			);
		} );
	}

}