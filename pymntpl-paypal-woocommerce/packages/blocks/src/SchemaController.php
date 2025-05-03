<?php

namespace PaymentPlugins\PPCP\Blocks;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;

class SchemaController {

	private $extend_schema;

	public function __construct( ExtendSchema $extend_schema ) {
		$this->extend_schema = $extend_schema;
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
		return apply_filters( 'wc_ppcp_blocks_get_extended_data', [
			'needsSetupToken' => false
		] );
	}

}