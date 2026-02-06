<?php

namespace PaymentPlugins\WooCommerce\PPCP\Rest\Routes;

use PaymentPlugins\WooCommerce\PPCP\Assets\PayPalDataTransformer;
use PaymentPlugins\WooCommerce\PPCP\Rest\Routes\AbstractRoute;
use PaymentPlugins\WooCommerce\PPCP\Rest\Validators\RouteValidator;
use PaymentPlugins\WooCommerce\PPCP\Utils;

class CartBilling extends AbstractRoute {

	/**
	 * @var RouteValidator
	 */
	private $validator;

	public function __construct() {
		$this->validator = new RouteValidator();
	}

	public function get_path() {
		return 'cart/billing';
	}

	public function get_routes() {
		return [
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'handle_request' ],
				'args'     => [
					'payment_method' => [
						'required'          => true,
						'validate_callback' => [ $this->validator, 'validate_payment_method' ]
					]
				]
			]
		];
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function handle_post_request( \WP_REST_Request $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		$cart = WC()->cart;

		// Step 1: Update WooCommerce cart with new shipping information
		$this->update_cart_billing_address( $request['address'] );

		$this->populate_post_data( $request );

		$cart->calculate_totals();

		$cart_data = ( new PayPalDataTransformer() )->transform_cart( $cart );

		return [
			'cart' => $cart_data
		];

	}

	private function update_cart_billing_address( $address ) {
		$customer = WC()->customer;
		$location = [
			'country'  => isset( $address['country'] ) ? $address['country'] : null,
			'state'    => isset( $address['state'] ) ? $address['state'] : null,
			'postcode' => isset( $address['postcode'] ) ? $address['postcode'] : null,
			'city'     => isset( $address['city'] ) ? $address['city'] : null
		];

		$location['state'] = Utils::normalize_address_state( $location['state'], $location['country'] );

		$customer->set_billing_location( ...array_values( $location ) );
		$customer->save();
	}
}