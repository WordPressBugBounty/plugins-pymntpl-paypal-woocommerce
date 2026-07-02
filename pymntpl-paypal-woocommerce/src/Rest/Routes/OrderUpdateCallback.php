<?php

namespace PaymentPlugins\WooCommerce\PPCP\Rest\Routes;

use PaymentPlugins\WooCommerce\PPCP\Factories\CoreFactories;
use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\Rest\Exceptions\OrderUpdateCallbackException;
use PaymentPlugins\WooCommerce\PPCP\Utilities\CartTokenHandler;
use PaymentPlugins\WooCommerce\PPCP\Utils;

/**
 * Handles PayPal's server-side order update callbacks for shipping address and option changes.
 * Replaces the client-side onShippingChange callback for PayPal.
 */
class OrderUpdateCallback extends AbstractRoute {

	private $factories;

	private $logger;

	public function __construct( CoreFactories $factories, Logger $logger ) {
		$this->factories = $factories;
		$this->logger    = $logger;
	}

	public function get_path() {
		return 'cart/order-update-callback';
	}

	public function get_routes() {
		return [
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'handle_request' ],
				'args'     => [
					'cart_token' => [
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			],
		];
	}

	public function handle_post_request( \WP_REST_Request $request ) {
		$cart_token = $request->get_param( 'cart_token' );

		if ( ! CartTokenHandler::validate_cart_token( $cart_token ) ) {
			throw new \Exception( __( 'Invalid cart token.', 'pymntpl-paypal-woocommerce' ), 401 );
		}

		$this->initialize_session( $cart_token );

		$body = $request->get_json_params();

		$this->logger->info(
			sprintf(
				'Order update callback received. Order ID: %s',
				$body['id'] ?? ''
			),
			'payment'
		);

		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		if ( ! empty( $body['shipping_address'] ) ) {
			$this->update_shipping_address( $body['shipping_address'] );
		}

		if ( ! empty( $body['shipping_option'] ) ) {
			$this->update_shipping_methods( [ $body['shipping_option']['id'] ] );
		}

		$this->add_shipping_hooks();
		$this->clear_cached_shipping_rates();
		WC()->cart->calculate_totals();

		$this->factories->initialize( WC()->cart, WC()->customer );

		$packages = $this->factories->shippingOptions->get_shipping_packages();
		if ( ! $this->validate_shipping_methods( $packages ) ) {
			$address = $body['shipping_address'] ?? [];
			$this->logger->info(
				sprintf(
					'No shipping methods available for address. %s',
					print_r( [
						'country'     => $address['country_code'] ?? '',
						'state'       => $address['admin_area_1'] ?? '',
						'postal_code' => $address['postal_code'] ?? '',
						'city'        => $address['admin_area_2'] ?? '',
					], true )
				),
				'payment'
			);
			throw new OrderUpdateCallbackException( OrderUpdateCallbackException::ADDRESS_ERROR );
		}

		$purchase_unit = $this->factories->purchaseUnit->from_cart();

		$pu_response = [ 'amount' => $purchase_unit->getAmount()->toArray() ];

		$reference_id = $body['purchase_units'][0]['reference_id'] ?? null;
		if ( $reference_id ) {
			$pu_response['reference_id'] = $reference_id;
		}

		$shipping_options = $purchase_unit->getShipping()->getOptions();
		if ( $shipping_options && $shipping_options->count() > 0 ) {
			$pu_response['shipping_options'] = $shipping_options->toArray();
		}

		$response = [
			'id'             => $body['id'] ?? '',
			'purchase_units' => [ $pu_response ],
		];

		$this->logger->info(
			sprintf(
				'PayPal order update callback processed. Response: %s',
				print_r( $response, true )
			),
			'payment'
		);

		return $response;
	}

	public function get_error_response( $error ) {
		$issue = $error instanceof OrderUpdateCallbackException
			? $error->getIssue()
			: OrderUpdateCallbackException::STORE_UNAVAILABLE;

		$response = rest_ensure_response( [
			'name'    => 'UNPROCESSABLE_ENTITY',
			'details' => [ [ 'issue' => $issue ] ],
		] );
		$response->set_status( 422 );

		return $response;
	}

	private function initialize_session( string $cart_token ): void {
		$_SERVER['HTTP_CART_TOKEN'] = $cart_token;

		if ( class_exists( \Automattic\WooCommerce\StoreApi\SessionHandler::class ) ) {
			add_filter( 'woocommerce_session_handler', function () {
				return \Automattic\WooCommerce\StoreApi\SessionHandler::class;
			} );
		}

		wc_load_cart();

		// wc_load_cart() creates WC()->customer via get_current_user_id() which is 0 in this
		// context (no cookie). For logged-in users, re-create it with the real user ID from the
		// session so their saved address meta loads correctly. For guests the customer ID is a
		// UUID so we leave the session-backed WC_Customer(0) that wc_load_cart() already created.
		$customer_id = WC()->session->get_customer_id();
		if ( is_numeric( $customer_id ) && (int) $customer_id > 0 ) {
			WC()->customer = new \WC_Customer( (int) $customer_id, true );
		}
	}

	private function update_shipping_address( array $address ): void {
		$location = [
			'country'  => $address['country_code'] ?? null,
			'state'    => $address['admin_area_1'] ?? null,
			'postcode' => $address['postal_code'] ?? null,
			'city'     => $address['admin_area_2'] ?? null,
		];

		$location['state'] = Utils::normalize_address_state( $location['state'], $location['country'] );

		$this->add_postcode_format_filter( $location['country'] );

		WC()->customer->set_billing_location( ...array_values( $location ) );
		WC()->customer->set_shipping_location( ...array_values( $location ) );
		WC()->customer->set_calculated_shipping( true );
		WC()->customer->save();
	}

	private function update_shipping_methods( $shipping_methods ): void {
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', [] );

		if ( is_string( $shipping_methods ) ) {
			$shipping_methods = [ $shipping_methods ];
		}

		foreach ( $shipping_methods as $idx => $method ) {
			if ( empty( $method ) ) {
				continue;
			}
			if ( substr_count( $method, ':' ) > 1 ) {
				$pos   = strpos( $method, ':' );
				$index = substr( $method, 0, $pos );
				$id    = substr( $method, $pos + 1 );
			} else {
				$index = $idx;
				$id    = $method;
			}
			$chosen_shipping_methods[ $index ] = $id;
		}

		WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
	}

	private function validate_shipping_methods( array $packages ): bool {
		foreach ( $packages as $package ) {
			if ( ! empty( $package['rates'] ) ) {
				return true;
			}
		}

		return false;
	}

	private function add_shipping_hooks(): void {
		add_filter( 'woocommerce_cart_ready_to_calc_shipping', '__return_true', 1000 );
	}

	private function clear_cached_shipping_rates(): void {
		$key = 'shipping_for_package_0';
		unset( WC()->session->{$key} );
	}

	private function add_postcode_format_filter( ?string $country ): void {
		if ( in_array( $country, [ 'CA', 'GB' ] ) ) {
			add_filter( 'woocommerce_format_postcode', function ( $formatted_postcode, $country ) {
				switch ( $country ) {
					case 'CA':
					case 'GB':
						$postcode = str_replace( ' ', '', $formatted_postcode );
						if ( strlen( $postcode ) <= 4 ) {
							$formatted_postcode = $postcode;
						}
						break;
				}

				return $formatted_postcode;
			}, 10, 2 );
		}
	}
}