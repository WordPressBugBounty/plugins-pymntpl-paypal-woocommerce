<?php

namespace PaymentPlugins\PPCP\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use PaymentPlugins\PPCP\Blocks\BlockTypes\MiniCartExpressPaymentBlock;
use PaymentPlugins\PPCP\Blocks\BlockTypes\ReCaptchaBlock;
use PaymentPlugins\WooCommerce\PPCP\Container\Container;
use PaymentPlugins\WooCommerce\PPCP\PaymentMethodRegistry;

class BlocksController {

	private $container;

	public function __construct( Container $container ) {
		$this->container = $container;
	}

	public function initialize() {
		add_action( 'woocommerce_blocks_mini-cart_block_registration', [
			$this,
			'register_mini_cart_blocks'
		] );
		add_action( 'woocommerce_blocks_checkout_block_registration', [
			$this,
			'register_checkout_blocks'
		] );
		add_action( 'woocommerce_blocks_cart_block_registration', [
			$this,
			'register_cart_blocks'
		] );
	}

	public function register_mini_cart_blocks( IntegrationRegistry $registry ) {
		if ( version_compare( WC()->version, '10.4', '>=' ) ) {
			$registry->register(
				new MiniCartExpressPaymentBlock(
					$this->container->get( 'BLOCK_ASSETS' )
				)
			);
		}
	}

	public function register_checkout_blocks( IntegrationRegistry $registry ) {
		$registry->register(
			new ReCaptchaBlock(
				$this->container->get( 'BLOCK_ASSETS' ),
				$this->container->get( PaymentMethodRegistry::class )
			)
		);
	}

	public function register_cart_blocks( IntegrationRegistry $registry ) {
		$registry->register(
			new ReCaptchaBlock(
				$this->container->get( 'BLOCK_ASSETS' ),
				$this->container->get( PaymentMethodRegistry::class )
			)
		);
	}
}