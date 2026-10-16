<?php

namespace PaymentPlugins\WooCommerce\PPCP\Rest\Routes;

use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\WooCommerce\PPCP\Constants;
use PaymentPlugins\WooCommerce\PPCP\ContextHandler;

/**
 * Returns the payer and shipping details of a PayPal order the customer created.
 *
 * Fallback for when the JS SDK's actions.order.get() fails client side (e.g. PayPal returning
 * NOT_AUTHORIZED for some Venmo orders). The PayPal order ID is never trusted from the request
 * alone, so a caller can't read the details of someone else's PayPal order by guessing or
 * replaying its ID:
 * - order-pay: the caller must provide the WC order's key, and the ID must match the one
 *   OrderPay saved to the order's meta. A guest paying from an email link may not have a
 *   persistent WC session, so the order is used instead.
 * - all other contexts: the ID must match the one CartOrder/CartItem cached in this session.
 *
 * @since 2.0.28
 */
class PayPalOrderRoute extends AbstractCart {

	public function get_path() {
		return 'paypal-order';
	}

	public function get_routes() {
		return [
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'handle_request' ],
				'args'     => [
					'payment_method'  => [
						'required'          => true,
						'validate_callback' => [ $this->validator, 'validate_payment_method' ]
					],
					'paypal_order_id' => [
						'required' => true
					],
					'context'         => [
						'required' => false
					]
				]
			]
		];
	}

	public function handle_post_request( \WP_REST_Request $request ) {
		$paypal_order_id = (string) $request->get_param( 'paypal_order_id' );

		if ( $request->get_param( 'context' ) === ContextHandler::ORDER_PAY ) {
			$order = wc_get_order( absint( $request->get_param( 'order_id' ) ) );
			if ( ! $order ) {
				throw new \Exception( __( 'Invalid order Id.', 'pymntpl-paypal-woocommerce' ), 404 );
			}
			if ( ! hash_equals( $order->get_order_key(), (string) $request->get_param( 'order_key' ) ) ) {
				throw new \Exception( __( 'Invalid order key provided.', 'pymntpl-paypal-woocommerce' ), 403 );
			}
			$bound_order_id = $order->get_meta( Constants::ORDER_ID );
			$client         = $this->client->orderMode( $order );
		} else {
			$payment_method = $this->get_payment_method_from_request( $request );
			$bound_order_id = $this->cache->get( sprintf( '%s_%s', $payment_method->id, Constants::PAYPAL_ORDER_ID ) );
			$client         = $this->client;
		}

		if ( ! $bound_order_id || (string) $bound_order_id !== $paypal_order_id ) {
			throw new \Exception( __( 'PayPal order not found.', 'pymntpl-paypal-woocommerce' ), 404 );
		}

		$paypal_order = $client->orders->retrieve( $paypal_order_id );

		if ( is_wp_error( $paypal_order ) ) {
			$this->logger->error( sprintf( 'Error retrieving PayPal order %1$s via %2$s. Msg: %3$s', $paypal_order_id, __METHOD__, $paypal_order->get_error_message() ) );

			/**
			 * @var \WP_Error $paypal_order
			 */
			throw new \Exception( $paypal_order->get_error_message(), 400 );
		}

		return $this->get_paypal_order_response( $paypal_order );
	}

	/**
	 * Only returns what the frontend needs to populate the checkout fields. The structure
	 * matches the actions.order.get() response so existing consumers work unchanged.
	 *
	 * @param Order $paypal_order
	 *
	 * @return array
	 */
	private function get_paypal_order_response( Order $paypal_order ) {
		$payer         = $paypal_order->getPayer();
		$purchase_unit = $paypal_order->getPurchaseUnits() && $paypal_order->getPurchaseUnits()->count() > 0
			? $paypal_order->getPurchaseUnits()->get( 0 )
			: null;
		$shipping      = $purchase_unit ? $purchase_unit->getShipping() : null;

		return [
			'id'             => $paypal_order->getId(),
			'intent'         => $paypal_order->getIntent(),
			'status'         => $paypal_order->getStatus(),
			'payer'          => $payer ? $payer->toArray() : null,
			'purchase_units' => [
				[
					'shipping' => $shipping ? array_intersect_key( $shipping->toArray(), array_flip( [ 'name', 'address' ] ) ) : null
				]
			]
		];
	}

}