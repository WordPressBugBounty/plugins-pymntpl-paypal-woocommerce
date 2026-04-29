<?php

namespace PaymentPlugins\PPCP\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use PaymentPlugins\PPCP\Blocks\BlockTypes\MiniCartExpressPaymentBlock;
use PaymentPlugins\WooCommerce\PPCP\Container\Container;

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
}