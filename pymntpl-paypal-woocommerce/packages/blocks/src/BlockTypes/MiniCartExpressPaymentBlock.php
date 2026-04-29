<?php

namespace PaymentPlugins\PPCP\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;
use PaymentPlugins\WooCommerce\PPCP\PaymentMethodRegistry;

class MiniCartExpressPaymentBlock implements IntegrationInterface {

	private $assets;

	public function __construct( AssetsApi $assets ) {
		$this->assets = $assets;
	}

	public function get_name() {
		return 'wcPPCPMiniCartExpressPayment';
	}

	public function initialize() {
		add_filter( 'render_block_woocommerce/mini-cart-footer-block', [ $this, 'prepend_express_container' ], 100 );

		$this->assets->register_script_module(
			'@wc-ppcp/mini-cart-block',
			'build/mini-cart-block.js'
		);
	}

	/**
	 * @param $block_content
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function prepend_express_container( $block_content ) {
		/**
		 * @var PaymentMethodRegistry $registry ;
		 */
		$registry = wc_ppcp_get_container()->get( PaymentMethodRegistry::class );
		$gateways = $registry->get_minicart_payment_gateways();

		if ( empty( $gateways ) ) {
			return $block_content;
		}

		wp_enqueue_script_module( '@wc-ppcp/mini-cart-block' );

		$anchors = '';
		foreach ( $gateways as $gateway ) {
			$anchors .= '<a class="wc-ppcp-minicart-' . esc_attr( $gateway->id ) . '" style="display:none;"></a>';
		}

		$container = '<div'
		             . ' data-wp-interactive="wc-ppcp/mini-cart-express"'
		             . ' data-wp-init="callbacks.onInit"'
		             . ' data-wp-watch--open="callbacks.onDrawerOpen"'
		             . ' data-wp-watch--cart="callbacks.onCartUpdated"'
		             . ' class="wc-ppcp-mini-cart-express"'
		             . '>' . $anchors . '</div>';

		return str_replace(
			'<div class="wc-block-mini-cart__footer-actions">',
			'<div class="wc-block-mini-cart__footer-actions">' . $container,
			$block_content
		);
	}

	public function get_script_handles() {
		return [];
	}

	public function get_editor_script_handles() {
		return [];
	}

	public function get_script_data() {
		return [];
	}
}