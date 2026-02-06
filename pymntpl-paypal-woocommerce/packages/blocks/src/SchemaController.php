<?php

namespace PaymentPlugins\PPCP\Blocks;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use PaymentPlugins\PPCP\Blocks\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Assets\PayPalDataTransformer;

class SchemaController {

	private $extend_schema;

	/**
	 * @var PaymentMethodRegistry
	 */
	private $payment_method_registry;

	public function __construct( ExtendSchema $extend_schema ) {
		$this->extend_schema = $extend_schema;
		//$this->$payment_method_registry = $payment_method_registry;
	}

	public function initialize() {
		$this->extend_schema->register_endpoint_data( [
			'endpoint'      => CartSchema::IDENTIFIER,
			'namespace'     => 'wc_ppcp',
			'schema_type'   => ARRAY_A,
			'data_callback' => [ $this, 'get_extension_data' ]
		] );
	}

	public function get_extension_data() {
		$data = [
			'needsSetupToken' => false
		];

		$cart = WC()->cart;

		$transformer = new PayPalDataTransformer();

		$data['cart'] = $transformer->transform_cart( $cart );

		/**
		 * @var \PaymentPlugins\WooCommerce\PPCP\PaymentMethodRegistry $ppcp_registry
		 */
		$ppcp_registry = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\PaymentMethodRegistry::class );
		/**
		 * @var \PaymentPlugins\PPCP\Blocks\Payments\Api $payments_api
		 */
		$payments_api = wc_ppcp_get_container()->get( \PaymentPlugins\PPCP\Blocks\Payments\Api::class );
		foreach ( $payments_api->get_payment_gateways() as $payment_method ) {
			if ( $payment_method instanceof AbstractGateway ) {
				$data = $payment_method->add_schema_payment_data( $data, $ppcp_registry->get( $payment_method->get_name() ) );
			}
		}

		return apply_filters( 'wc_ppcp_blocks_get_extended_data', $data );
	}

}