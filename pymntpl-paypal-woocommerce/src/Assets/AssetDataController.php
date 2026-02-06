<?php

namespace PaymentPlugins\WooCommerce\PPCP\Assets;

use PaymentPlugins\WooCommerce\PPCP\ContextHandler;
use PaymentPlugins\WooCommerce\PPCP\Utils;

/**
 * Controller responsible for managing asset data output:
 * - Registers WordPress hooks
 * - Populates default data based on context
 * - Coordinates data output to page
 */
class AssetDataController {

	/**
	 * @var AssetDataApi
	 */
	private $data_api;

	/**
	 * @var ContextHandler
	 */
	private $context_handler;

	/**
	 * @var PayPalDataTransformer
	 */
	private $transformer;

	/**
	 * @param AssetDataApi $data_api
	 * @param ContextHandler $context_handler
	 */
	public function __construct( AssetDataApi $data_api, ContextHandler $context_handler ) {
		$this->data_api        = $data_api;
		$this->context_handler = $context_handler;
		$this->transformer     = new PayPalDataTransformer();
	}

	/**
	 * Register WordPress hooks
	 */
	public function initialize() {
		add_action( 'wp_print_footer_scripts', [ $this, 'enqueue_asset_data' ], 1 );
		add_filter( 'woocommerce_update_order_review_fragments', [ $this, 'get_update_order_review_data' ] );
		add_filter( 'wc_ppcp_before_cart_payment_methods', [ $this, 'add_cart_refresh_data' ] );
	}

	/**
	 * Output the data from the AssetDataApi to the page.
	 *
	 * @return void
	 */
	public function enqueue_asset_data() {
		if ( is_admin() ) {
			return;
		}

		$this->add_default_data();

		/**
		 * Add script data that's output to frontend pages.
		 *
		 * @param AssetDataApi $data_api The data API for adding data
		 * @param ContextHandler $context_handler The context handler
		 */
		do_action( 'wc_ppcp_add_script_data', $this->data_api, $this->context_handler );

		// Output data if any exists
		if ( $this->data_api->has_data() ) {
			$this->data_api->print_data( 'wcPPCPSettings', $this->data_api->get_data() );
		}
	}


	public function get_update_order_review_data( $fragments ) {
		$data = [
			'cart' => apply_filters( 'wc_ppcp_cart_data', $this->transformer->transform_cart( WC()->cart ) )
		];

		$data = apply_filters( 'wc_ppcp_update_order_review_data', $data );

		$fragments['wc_ppcp_data'] = $data;

		return $fragments;
	}

	public function add_cart_refresh_data() {
		if ( ! is_ajax() ) {
			return;
		}
		/**
		 * @var \PaymentPlugins\WooCommerce\PPCP\Assets\AssetDataApi
		 */
		do_action( 'wc_ppcp_cart_refresh_data', $this->data_api );

		$this->data_api->add( 'cart', $this->transformer->transform_cart( WC()->cart ) );
		$this->data_api->print_data( 'wcPPCPCartData', $this->data_api->get_data() );
	}

	/**
	 * Add default data based on current WooCommerce context
	 *
	 * @return void
	 */
	private function add_default_data() {
		global $product;

		if ( WC()->cart ) {
			$this->data_api->add( 'cart', $this->transformer->transform_cart( WC()->cart ) );
		}
		if ( $product && \is_object( $product ) ) {
			if ( $product instanceof \WP_Post && $product->post_type === 'product' ) {
				$product = wc_get_product( $product->ID );
			}
			$this->data_api->add( 'product', $this->transformer->transform_product( $product ) );
		}
		if ( $this->context_handler->is_order_pay() ) {
			$order = Utils::get_order_from_query_vars();
			if ( $order ) {
				$this->data_api->add( 'order', $this->transformer->transform_order( $order ) );
			}
		}

		// Add required fields data
		$this->data_api->add( 'requiredFields', $this->get_required_fields() );
	}

	/**
	 * Get required checkout fields
	 *
	 * @return array Array of field names that are required
	 */
	private function get_required_fields() {
		$required_fields = [];

		if ( WC()->checkout ) {
			$checkout_fields = WC()->checkout->get_checkout_fields();

			foreach ( $checkout_fields as $field_group => $fields ) {
				foreach ( $fields as $field_key => $field_data ) {
					if ( ! empty( $field_data['required'] ) ) {
						$required_fields[] = $field_key;
					}
				}
			}
		}

		return $required_fields;
	}

}