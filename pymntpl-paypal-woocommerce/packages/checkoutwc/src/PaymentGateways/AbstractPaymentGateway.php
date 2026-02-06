<?php

namespace PaymentPlugins\PPCP\CheckoutWC\PaymentGateways;

use PaymentPlugins\PayPalSDK\Amount;
use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\PayPalSDK\Refund;
use PaymentPlugins\PPCP\CheckoutWC\OrderBumps\Factories\OrderFactory;
use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\PaymentResult;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Utilities\NumberUtil;
use PaymentPlugins\WooCommerce\PPCP\Utilities\OrderLock;
use PaymentPlugins\WooCommerce\PPCP\Utilities\PayPalFee;
use PaymentPlugins\WooCommerce\PPCP\WPPayPalClient;

class AbstractPaymentGateway {

	protected $supports_api_refund = true;

	/**
	 * @var OrderFactory
	 */
	private $order_factory;

	/**
	 * @var WPPayPalClient
	 */
	private $client;

	/**
	 * @var Logger
	 */
	private $logger;

	public function __construct( WPPayPalClient $client, OrderFactory $order_factory, Logger $logger ) {
		$this->client        = $client;
		$this->order_factory = $order_factory;
		$this->logger        = $logger;
	}

	public static function get_instance() {
		return wc_ppcp_get_container()->get( static::class );
	}

	public function is_api_refund() {
		return $this->supports_api_refund;
	}

	/**
	 * @param \WC_Order $order The order.
	 * @param array $product The product.
	 *
	 * @return bool
	 */
	public function process_offer_payment( \WC_Order $order, array $product ): bool {

		try {
			/**
			 * @var AbstractGateway $payment_method
			 */
			$payment_method = wc_get_payment_gateway_by_order( $order );
			// create the order
			$paypal_order = $this->client->orderMode( $order )->orders->create(
				$this->order_factory->from_order( $order, $product )
			);
			if ( is_wp_error( $paypal_order ) ) {
				throw new \Exception( $paypal_order->get_error_message() );
			}
			if ( $paypal_order->isApproved() || $paypal_order->isCreated() ) {
				if ( Order::CAPTURE == $paypal_order->intent ) {
					OrderLock::set_order_lock( $order );
					$paypal_order = $this->client->orders->capture( $paypal_order->id );
				} else {
					$paypal_order = $this->client->orders->authorize( $paypal_order->id );
				}
			}
			$result = new PaymentResult( $paypal_order, $order, $payment_method );

			if ( $result->needs_approval() ) {
				\wp_send_json( [
					'status'   => 'success',
					'redirect' => ''
				] );
			} elseif ( $result->success() ) {
				// update the order metadata
				// add the PayPal transaction ID
				$capture = $result->get_capture();
				$order->update_meta_data( 'cfw_offer_txn_resp_' . $product['bump_id'], $capture->getId() );
				if ( $result->is_captured() ) {
					PayPalFee::add_fee_to_order( $order, $result->get_capture()->getSellerReceivableBreakdown(), false );
				}
				$order->save();
			}

			return true;
		} catch ( \Exception $e ) {
			// add error log
			$this->logger->error(
				sprintf(
					'Error processing payment for CheckoutWC order bump. Order ID: %1$s. Bump ID: %2$s. Error: %3$s',
					$order->get_id(),
					$product['bump_id'],
					$e->getMessage()
				)
			);

			return false;
		}
	}

	/**
	 * @param \WC_Order $order
	 * @param array $offer_data
	 *
	 * @return string|false
	 */
	public function process_offer_refund( \WC_Order $order, array $offer_data ) {
		// process a refund using the PayPal Refund API.¬
		$txn_id = $offer_data['transaction_id'];
		$amount = $offer_data['refund_amount'];
		$refund = new Refund();
		$refund->setAmount(
			( new Amount() )
				->setValue( NumberUtil::round_incl_currency( $amount, $order->get_currency() ) )
				->setCurrencyCode( $order->get_currency() )
		);
		/**
		 * @var Refund $result
		 */
		$result = $this->client->orderMode( $order )->captures->refund( $txn_id, $refund );

		if ( is_wp_error( $result ) ) {
			$order->add_order_note(
				sprintf(
					__( 'Error processing refund. Reason: %s', 'pymntpl-paypal-woocommerce' ),
					$result->get_error_message()
				)
			);

			return false;
		} else {

			return $result->getId();
		}

	}
}