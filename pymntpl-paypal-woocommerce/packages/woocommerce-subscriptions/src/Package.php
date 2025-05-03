<?php

namespace PaymentPlugins\PPCP\WooCommerceSubscriptions;

use Automattic\WooCommerce\Internal\Admin\Settings\PaymentsController;
use PaymentPlugins\PayPalSDK\PayPalClient;
use PaymentPlugins\WooCommerce\PPCP\Factories\CoreFactories;
use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\Package\AbstractPackage;

class Package extends AbstractPackage {

	public $id = 'woocommerce_subscriptions';

	public function is_active() {
		return function_exists( 'wcs_is_subscription' );
	}

	public function initialize() {
		$this->container->get( SubscriptionController::class )->initialize();
	}

	public function register_dependencies() {
		$this->container->register( SubscriptionController::class, function ( $container ) {
			return new SubscriptionController(
				$container->get( PaymentController::class ),
				$container->get( PayPalClient::class ),
				$container->get( CoreFactories::class ),
				$container->get( Logger::class )
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