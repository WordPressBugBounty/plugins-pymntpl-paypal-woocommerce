<?php

namespace PaymentPlugins\PPCP\CheckoutWC;

use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use PaymentPlugins\PayPalSDK\PayPalClient;
use PaymentPlugins\PPCP\CheckoutWC\OrderBumps\Factories\OrderFactory;
use PaymentPlugins\PPCP\CheckoutWC\OrderBumps\OrderBumpsController;
use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings;
use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;
use PaymentPlugins\WooCommerce\PPCP\Config;
use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\Package\AbstractPackage;
use PaymentPlugins\WooCommerce\PPCP\Payments\PaymentGateways;

class Package extends AbstractPackage {

	public $id = 'checkoutwc';

	public function initialize() {
		$this->container->get( FrontendAssets::class )->initialize();
		$this->container->get( PaymentGatewaysController::class );
		$this->container->get( OrderBumpsController::class )->initialize();
	}

	public function is_active() {
		return defined( 'CFW_NAME' );
	}

	public function register_dependencies() {
		$this->container->register( FrontendAssets::class, function () {
			return new FrontendAssets(
				new AssetsApi(
					new Config( $this->version, dirname( __FILE__ ) )
				)
			);
		} );
		$this->container->register( PaymentGatewaysController::class, function ( $container ) {
			$instance = PaymentGatewaysController::instance();
			$instance->set_payment_gateways( $container->get( PaymentGateways::class ) );
			$instance->init();

			return $instance;
		} );
		$this->container->register( PayPalPaymentGateway::class, function ( $container ) {
			$instance = PayPalPaymentGateway::instance();
			$instance->set_assets_api( $container->get( AssetsApi::class ) );
			$instance->set_payment_gateways( $container->get( PaymentGateways::class ) );
			$instance->init();

			return $instance;
		} );
		$this->container->register( OrderFactory::class, function ( $container ) {
			return new OrderFactory();
		} );
		$this->container->register( OrderBumpsController::class, function ( $container ) {
			return new OrderBumpsController(
				new Config( $this->version, dirname( __FILE__ ) ),
				$container->get( AdvancedSettings::class )
			);
		} );
		$this->container->register( \PaymentPlugins\PPCP\CheckoutWC\PaymentGateways\PayPalPaymentGateway::class, function ( $container ) {
			return new \PaymentPlugins\PPCP\CheckoutWC\PaymentGateways\PayPalPaymentGateway(
				$container->get( PayPalClient::class ),
				$container->get( OrderFactory::class ),
				$container->get( Logger::class )
			);
		} );
		$this->container->register( \PaymentPlugins\PPCP\CheckoutWC\PaymentGateways\CreditCardPaymentGateway::class, function ( $container ) {
			return new \PaymentPlugins\PPCP\CheckoutWC\PaymentGateways\CreditCardPaymentGateway(
				$container->get( PayPalClient::class ),
				$container->get( OrderFactory::class ),
				$container->get( Logger::class )
			);
		} );
		$this->container->register( \PaymentPlugins\PPCP\CheckoutWC\PaymentGateways\ApplePayPaymentGateway::class, function ( $container ) {
			return new \PaymentPlugins\PPCP\CheckoutWC\PaymentGateways\ApplePayPaymentGateway(
				$container->get( PayPalClient::class ),
				$container->get( OrderFactory::class ),
				$container->get( Logger::class )
			);
		} );
	}

}