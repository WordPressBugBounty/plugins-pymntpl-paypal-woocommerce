<?php

namespace PaymentPlugins\WooCommerce\PPCP\Products;

use PaymentPlugins\WooCommerce\PPCP\Assets\AssetDataApi;
use PaymentPlugins\WooCommerce\PPCP\ContextHandler;
use PaymentPlugins\WooCommerce\PPCP\PaymentMethodRegistry;
use PaymentPlugins\WooCommerce\PPCP\ProductSettings;

class ProductDataController {

	private $payment_method_registry;

	public function __construct( PaymentMethodRegistry $payment_method_registry ) {
		$this->payment_method_registry = $payment_method_registry;
	}

	public function initialize() {
		add_action( 'wc_ppcp_add_script_data', [ $this, 'add_script_data' ], 10, 2 );
		add_filter( 'wc_ppcp_product_form_fields', [ $this, 'add_product_form_fields' ] );

		add_filter( 'bulk_actions-edit-product', [ $this, 'add_bulk_actions' ] );
		add_action( 'handle_bulk_actions-edit-product', [ $this, 'process_bulk_actions' ], 10, 3 );
	}

	public function add_bulk_actions( $actions ) {
		$actions['wc_ppcp_delete'] = __( 'Delete PayPal Options', 'woo-stripe-payment' );

		return $actions;
	}

	public function process_bulk_actions( $redirect, $doaction, $post_ids ) {
		if ( $doaction !== 'wc_ppcp_delete' || ! current_user_can( 'manage_woocommerce' ) ) {
			return $redirect;
		}
		foreach ( $post_ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product ) {
				$option = new ProductSettings( $product );
				$product->delete_meta_data( $option->get_option_key() );
				$product->save();
			}
		}

		return $redirect;
	}

	public function add_script_data( AssetDataApi $data_api, ContextHandler $context_handler ) {
		if ( $context_handler->is_product() ) {
			$settings = new ProductSettings( $context_handler->get_product_id() );
			$data_api->add( 'productSettings', [
				'button_width' => $settings->get_option( 'width' ),
				'funding'      => array_keys( array_filter( [
					'paypal'   => \wc_string_to_bool( $settings->get_option( 'paypal_enabled' ) ),
					'paylater' => \wc_string_to_bool( $settings->get_option( 'paylater_enabled' ) ),
					'card'     => \wc_string_to_bool( $settings->get_option( 'card_enabled' ) )
				] ) )
			] );
		}
	}

	public function add_product_form_fields( $fields ) {
		foreach ( $this->payment_method_registry->get_registered_integrations() as $integration ) {
			$fields = $integration->get_product_form_fields( $fields );
		}

		return $fields;
	}
}