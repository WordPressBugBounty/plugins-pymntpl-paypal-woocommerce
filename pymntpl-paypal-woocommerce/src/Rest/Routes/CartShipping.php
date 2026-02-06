<?php


namespace PaymentPlugins\WooCommerce\PPCP\Rest\Routes;


use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\PayPalSDK\PatchRequest;
use PaymentPlugins\PayPalSDK\PurchaseUnit;
use PaymentPlugins\WooCommerce\PPCP\Assets\PayPalDataTransformer;
use PaymentPlugins\WooCommerce\PPCP\Constants;
use PaymentPlugins\WooCommerce\PPCP\Rest\Exceptions\ShippingException;
use PaymentPlugins\WooCommerce\PPCP\Utils;

/**
 * Route that handles shipping address and method updates for payment wallets.
 *
 * Supports multiple payment methods:
 * - PayPal: Updates both WooCommerce cart and PayPal order (requires order_id)
 * - Google Pay: Updates WooCommerce cart only (no order_id needed)
 * - Apple Pay: Updates WooCommerce cart only (no order_id needed)
 */
class CartShipping extends AbstractCart {

	public function get_path() {
		return 'cart/shipping';
	}

	public function get_routes() {
		return [
			[
				'methods'  => \WP_REST_Server::EDITABLE,
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
	 * Override error response handler to provide wallet-specific error formatting.
	 *
	 * For wallet payment methods (GPay, Apple Pay), exceptions are converted to
	 * structured error responses that can be used to update the payment sheet.
	 *
	 * @param \Exception|\WP_Error $error
	 *
	 * @return \WP_Error
	 */
	public function get_error_response( $error ) {
		if ( $error instanceof ShippingException ) {
			// Return structured error with wallet-specific fields
			return new \WP_Error(
				'shipping_error',
				$error->getMessage(),
				[
					'status' => $error->getCode() ?: 400,
					'error'  => [
						'reason'  => $error->getReason(),
						'message' => $error->getMessage(),
						'intent'  => $error->getIntent()
					]
				]
			);
		} elseif ( $error instanceof \Exception ) {
			// Convert standard exceptions to wallet-compatible format
			return new \WP_Error(
				'shipping_error',
				$error->getMessage(),
				[
					'status' => $error->getCode() ?: 400,
					'error'  => [
						'reason'  => 'SHIPPING_ADDRESS_INVALID',
						'message' => $error->getMessage(),
						'intent'  => 'SHIPPING_ADDRESS'
					]
				]
			);
		}

		// Fallback to parent implementation
		return parent::get_error_response( $error );
	}

	/**
	 * Handle shipping address and method updates.
	 *
	 * This method processes shipping changes for all payment methods:
	 * 1. Updates WooCommerce cart with new address/shipping method
	 * 2. Recalculates totals
	 * 3. For PayPal: Updates the PayPal order if order_id is provided
	 * 4. For other methods (GPay, Apple Pay): Returns cart data for payment sheet updates
	 *
	 * @param \WP_REST_Request $request Request object with address, shipping_method, order_id (optional)
	 *
	 * @return array Response data (format depends on payment method)
	 * @throws \Exception If shipping is unavailable or PayPal API errors occur
	 */
	public function handle_post_request( \WP_REST_Request $request ) {
		wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );

		// Step 1: Update WooCommerce cart with new shipping information
		$this->update_cart_shipping_data( $request );

		// Step 2: Recalculate cart totals
		$this->recalculate_cart_totals( $request );

		// Step 3: Validate shipping methods are available
		$this->validate_shipping_availability( $request );

		// Step 4: Return appropriate response based on payment method
		return $this->build_response( $request );
	}

	/**
	 * Update WooCommerce cart with new shipping address and methods.
	 *
	 * @param \WP_REST_Request $request
	 */
	private function update_cart_shipping_data( \WP_REST_Request $request ) {
		if ( isset( $request['address'] ) ) {
			$this->update_shipping_address( $request['address'] );
		}
		if ( isset( $request['shipping_method'] ) ) {
			$this->update_shipping_methods( $request['shipping_method'] );
		}
	}

	/**
	 * Recalculate cart totals with updated shipping information.
	 *
	 * @param \WP_REST_Request $request
	 */
	private function recalculate_cart_totals( \WP_REST_Request $request ) {
		$this->populate_post_data( $request );
		$this->add_shipping_hooks();
		$this->clear_cached_shipping_rates();
		$this->calculate_totals();
	}

	/**
	 * Validate that shipping methods are available for the address.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @throws \Exception If no shipping methods available for complete address
	 */
	private function validate_shipping_availability( \WP_REST_Request $request ) {
		if ( ! $this->validate_shipping_methods( WC()->shipping()->get_packages() ) ) {
			if ( $this->is_intermediate_address_complete( $request->get_param( 'address' ) ) ) {
				// only throw exception if this is a complete intermediary address
				throw new ShippingException(
					__( 'There are no shipping options available for the provided address.', 'pymntpl-paypal-woocommerce' ),
					'NO_SHIPPING_OPTIONS',
					'SHIPPING_ADDRESS',
					404
				);
			}
		}
	}

	/**
	 * Build response based on payment method.
	 *
	 * PayPal: Updates PayPal order and returns success with order_id
	 * Other methods: Returns cart data (currency, totals, shipping options)
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array Response data
	 * @throws \Exception If PayPal order update fails
	 */
	private function build_response( \WP_REST_Request $request ) {
		// PayPal flow: Update the PayPal order with new shipping information
		if ( isset( $request['order_id'] ) ) {
			return $this->build_paypal_response( $request['order_id'] );
		}

		// GPay/Apple Pay flow: Return cart data for payment sheet update
		return $this->build_wallet_response( $request );
	}

	/**
	 * Build response for PayPal payment method.
	 *
	 * Fetches and updates the PayPal order with new shipping information.
	 *
	 * @param string $order_id PayPal order ID
	 *
	 * @return array Success response with order_id
	 * @throws \Exception If PayPal API call fails
	 */
	private function build_paypal_response( $order_id ) {
		$order = $this->client->orders->retrieve( $order_id );
		if ( is_wp_error( $order ) ) {
			throw new \Exception( sprintf( __( 'Error fetching order %s. Reason: %s', 'pymntpl-paypal-woocommerce' ), $order_id, $order->get_error_message() ) );
		}

		$this->update_paypal_order( $order );

		return [
			'success'  => true,
			'order_id' => $order->getId()
		];
	}

	/**
	 * Build response for wallet payment methods (GPay, Apple Pay).
	 *
	 * Returns cart data needed to update the payment sheet.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array Cart data (currency, total, display_items, shipping_options)
	 */
	private function build_wallet_response( \WP_REST_Request $request ) {
		/**
		 * @var \PaymentPlugins\WooCommerce\PPCP\Traits\DisplayItemsTrait $payment_method
		 */
		$payment_method = $this->get_payment_method_from_request( $request );

		$transformer = new PayPalDataTransformer();

		return $transformer->transform_cart( WC()->cart );
	}

	/**
	 * Update PayPal order with new shipping information via API.
	 *
	 * Creates patch requests to update the PayPal order's purchase units
	 * with the latest cart totals and shipping information.
	 *
	 * @param \PaymentPlugins\PayPalSDK\Order $order PayPal order object
	 */
	private function update_paypal_order( Order $order ) {
		$patches = [];
		$this->factories->initialize( WC()->cart, WC()->customer );
		$pu = $this->factories->purchaseUnit->from_cart();

		/**
		 * @var PurchaseUnit $purchase_unit
		 */
		foreach ( $order->purchase_units as $purchase_unit ) {
			if ( $purchase_unit->getReferenceId() ) {
				$pu->setReferenceId( $purchase_unit->getReferenceId() );
			} else {
				$pu->setReferenceId( 'default' );
			}
			$pu->setPayee( $purchase_unit->getPayee() );
			$pu->getShipping()->remove( 'address' )->remove( 'name' );
			$patches[] = $pu->getPatchRequest( '', PatchRequest::REPLACE );
		}
		$result = $this->client->orders->update( $order->getId(), $patches );
		if ( ! is_wp_error( $result ) ) {
			$this->logger->info( sprintf( 'Shipping updated for PayPal order %s. Patches: %s', $order->getId(), print_r( $patches, true ) ), 'payment' );

			$this->cache->delete( Constants::PPCP_ORDER_SESSION_KEY );
		}
	}

	private function update_shipping_address( $address ) {
		$customer = WC()->customer;
		$location = [
			'country'  => isset( $address['country'] ) ? $address['country'] : null,
			'state'    => isset( $address['state'] ) ? $address['state'] : null,
			'postcode' => isset( $address['postcode'] ) ? $address['postcode'] : null,
			'city'     => isset( $address['city'] ) ? $address['city'] : null
		];

		$location['state'] = Utils::normalize_address_state( $location['state'], $location['country'] );

		$customer->set_billing_location( ...array_values( $location ) );
		$customer->set_shipping_location( ...array_values( $location ) );
		WC()->customer->set_calculated_shipping( true );
		WC()->customer->save();
	}

	/**
	 * Update chosen shipping methods in WooCommerce session.
	 *
	 * Shipping methods come in format: "0:flat_rate:1", "0:flat_rate:2", etc.
	 * where the format is: index:method_id
	 *
	 * @param array|string $shipping_methods Shipping method(s) to update
	 */
	private function update_shipping_methods( $shipping_methods ) {
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods', [] );

		// Handle single shipping method string
		if ( is_string( $shipping_methods ) ) {
			$shipping_methods = [ $shipping_methods ];
		}

		foreach ( $shipping_methods as $idx => $method ) {
			// Parse format: "0:flat_rate:1" -> index: 0, id: flat_rate:1
			// Or format: "flat_rate:1" -> use $idx as index, id: flat_rate:1
			if ( preg_match( '/^(?:(?P<index>\d+):)?(?P<id>.+)$/', $method, $matches ) ) {
				$index                             = $matches['index'] !== '' ? $matches['index'] : $idx;
				$id                                = $matches['id'];
				$chosen_shipping_methods[ $index ] = $id;
			}
		}

		WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
	}

	private function validate_shipping_methods( $packages ) {
		foreach ( $packages as $i => $package ) {
			if ( ! empty( $package['rates'] ) ) {
				return true;
			}
		}

		return false;
	}

	private function add_shipping_hooks() {
		add_filter( 'woocommerce_cart_ready_to_calc_shipping', '__return_true', 1000 );
	}

	private function is_intermediate_address_complete( $address ) {
		if ( ! $address ) {
			return false;
		}
		$address = array_merge(
			array(
				'country'  => '',
				'state'    => '',
				'postcode' => '',
				'city'     => ''
			),
			$address
		);
		if ( ! $address['country'] ) {
			return false;
		}
		$fields = WC()->countries->get_address_fields( $address['country'], 'shipping_' );
		foreach ( $address as $key => $value ) {
			$key2 = 'shipping_' . $key;
			if ( isset( $fields[ $key2 ] ) ) {
				if ( array_key_exists( 'required', $fields[ $key2 ] ) ) {
					if ( $fields[ $key2 ]['required'] ) {
						if ( empty( $address[ $key ] ) ) {
							return false;
						}
					}
				}
			}
		}

		return true;
	}

	private function clear_cached_shipping_rates() {
		$key = 'shipping_for_package_0';
		unset( WC()->session->{$key} );
	}

}