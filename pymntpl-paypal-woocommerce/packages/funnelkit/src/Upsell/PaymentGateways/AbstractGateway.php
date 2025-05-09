<?php

namespace PaymentPlugins\PPCP\FunnelKit\Upsell\PaymentGateways;

use PaymentPlugins\PayPalSDK\Amount;
use PaymentPlugins\PayPalSDK\Breakdown;
use PaymentPlugins\PayPalSDK\Collection;
use PaymentPlugins\PayPalSDK\Item;
use PaymentPlugins\PayPalSDK\Money;
use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\PayPalSDK\OrderApplicationContext;
use PaymentPlugins\PayPalSDK\PaymentSource;
use PaymentPlugins\PayPalSDK\Token;
use PaymentPlugins\PayPalSDK\PurchaseUnit;
use PaymentPlugins\PayPalSDK\Refund;
use PaymentPlugins\PayPalSDK\Shipping;
use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;
use PaymentPlugins\WooCommerce\PPCP\Constants;
use PaymentPlugins\WooCommerce\PPCP\Factories\CoreFactories;
use PaymentPlugins\WooCommerce\PPCP\FeeCalculation;
use PaymentPlugins\WooCommerce\PPCP\PaymentHandler;
use PaymentPlugins\WooCommerce\PPCP\PaymentResult;
use PaymentPlugins\WooCommerce\PPCP\Utilities\NumberUtil;
use PaymentPlugins\WooCommerce\PPCP\Utilities\OrderLock;
use PaymentPlugins\WooCommerce\PPCP\Utilities\PayPalFee;
use PaymentPlugins\WooCommerce\PPCP\Utils;

class AbstractGateway extends \WFOCU_Gateway {

	public $id = '';

	public $refund_supported = true;

	protected $assets;

	protected $payment_handler;

	/**
	 * @var \WFOCU_Logger
	 */
	protected $logger;

	protected $paypal_order = null;

	public function __construct( AssetsApi $assets, PaymentHandler $payment_handler, $logger ) {
		$this->assets          = $assets;
		$this->payment_handler = $payment_handler;
		$this->logger          = $logger;
	}

	public static function get_instance() {
		return wc_ppcp_get_container()->get( static::class );
	}

	public function get_payment_method_script_handles() {
		return [];
	}

	public function supports_payment_method_vaulting() {
		return true;
	}

	public function process_charge( $order ) {
		$this->handle_client_error();
		$payment_method = $this->get_wc_gateway();
		$this->payment_handler->set_payment_method( $payment_method );
		$client = $this->payment_handler->client;
		try {
			if ( $this->is_processing_redirect() ) {
				$paypal_order = $this->paypal_order;
			} else {
				$paypal_order = $client->orders->create( $this->get_create_order_params( $order ) );
				if ( is_wp_error( $paypal_order ) ) {
					throw new \Exception( $paypal_order->get_error_message() );
				}
			}
			if ( $paypal_order->isApproved() || $paypal_order->isCreated() ) {
				if ( Order::CAPTURE == $paypal_order->intent ) {
					OrderLock::set_order_lock( $order );
					$paypal_order = $client->orders->capture( $paypal_order->id );
				} else {
					$paypal_order = $client->orders->authorize( $paypal_order->id );
				}
			}
			$result = new PaymentResult( $paypal_order, $order, $payment_method );
			if ( $result->success() ) {
				WFOCU_Core()->data->set( '_transaction_id', $result->get_capture_id() );
				$this->update_order_fee( $result, $order );

				return $this->handle_result( true );
			} else {
				throw new \Exception( $result->get_error_message() );
			}
		} catch ( \Exception $e ) {
			$this->logger->log( sprintf( 'WC-PPCP - could not complete upsell payment. Order: %1$s. Error: %2$s', $order->get_id(), $e->getMessage() ) );
			throw new \WFOCU_Payment_Gateway_Exception( $e->getMessage(), 400 );
		}
	}

	public function handle_client_error() {
		$package = WFOCU_Core()->data->get( '_upsell_package' );
		if ( $package && isset( $package['_client_error'] ) ) {
			$this->logger->log( sprintf( 'PPCP client error: %s', sanitize_text_field( $package['_client_error'] ) ) );
		}
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return \PaymentPlugins\PayPalSDK\Order
	 */
	public function get_create_order_params( $order ) {
		$payment_method = wc_get_payment_gateway_by_order( $order );
		$currency       = $order->get_currency();
		$package        = WFOCU_Core()->data->get( '_upsell_package' );
		/**
		 * @var CoreFactories $factories
		 */
		$factories = wc_ppcp_get_container()->get( CoreFactories::class );
		$factories->initialize( $order );

		$current_offer = WFOCU_Core()->data->get( 'current_offer' );
		list( $item_total, $needs_shipping ) = array_reduce( $package['products'], function ( $carry, $product ) {
			$carry[0] = $carry[0] + $product['price'];
			if ( isset( $product['data'] ) && $product['data']->needs_shipping() ) {
				$carry[1] = true;
			}

			return $carry;
		}, [ 0, false ] );
		$purchase_units = new Collection();
		$purchase_unit  = ( new PurchaseUnit() )
			->setAmount( ( new Amount() )
				->setValue( NumberUtil::round_incl_currency( $package['total'], $currency ) )
				->setCurrencyCode( $currency )
				->setBreakdown( ( new Breakdown() )
					->setItemTotal( ( new Money() )
						->setCurrencyCode( $currency )
						->setValue( NumberUtil::round_incl_currency( $item_total, $currency ) ) )
					->setShipping( ( new Money() )
						->setCurrencyCode( $currency )
						->setValue( NumberUtil::round_incl_currency( $package['shipping'], $currency ) ) )
					->setTaxTotal( ( new Money() )
						->setCurrencyCode( $currency )
						->setValue( NumberUtil::round_incl_currency( $package['taxes'], $currency ) ) )
					->setDiscount( ( new Money() )
						->setCurrencyCode( $currency )
						->setValue( NumberUtil::round_incl_currency( 0, $currency ) ) )
					->setHandling( ( new Money() )
						->setCurrencyCode( $currency )
						->setValue( NumberUtil::round_incl_currency( 0, $currency ) ) ) ) )
			->setItems( array_reduce( $package['products'], function ( $collection, $product ) use ( $currency ) {
				return $collection->add( ( new Item() )
					->setName( \substr( $product['data']->get_name(), 0, 127 ) )
					->setQuantity( $product['qty'] )
					->setUnitAmount( ( new Money() )
						->setCurrencyCode( $currency )
						->setValue( NumberUtil::round_incl_currency( $product['price'] / $product['qty'], $currency ) ) ) );
			}, new Collection() ) )
			->setInvoiceId( sprintf( '%s-%s', $order->get_id(), $current_offer ? $current_offer : 'wfocu' ) )
			->setCustomId( sprintf( '%1$s_%2$s', 'wfocu', $order->get_id() ) );

		if ( $needs_shipping ) {
			$purchase_unit->setShipping( $factories->shipping->from_order( 'shipping' ) );
			if ( ! Utils::is_valid_address( $purchase_unit->getShipping()->getAddress(), 'shipping' ) ) {
				unset( $purchase_unit->getShipping()->address );
			}
		}

		$factories->purchaseUnit->filter_purchase_unit( $purchase_unit, $purchase_unit->getAmount()->getValue() );
		$purchase_units->add( $purchase_unit );

		$application_context = $factories->applicationContext->get( $needs_shipping, true );

		$result = ( new Order() )
			->setIntent( $payment_method->get_option( 'intent' ) )
			->setPayer( $factories->payer->from_order() )
			->setApplicationContext( $application_context )
			->setPurchaseUnits( $purchase_units );

		if ( $order->get_meta( Constants::PAYMENT_METHOD_TOKEN ) ) {
			$result->setPaymentSource( ( new PaymentSource() )
				->setToken( ( new Token() )
					->setId( $order->get_meta( Constants::PAYMENT_METHOD_TOKEN ) )
					->setType( Token::PAYMENT_METHOD_TOKEN ) ) );
		} elseif ( $order->get_meta( Constants::BILLING_AGREEMENT_ID ) ) {
			$result->setPaymentSource( ( new PaymentSource() )
				->setToken( ( new Token() )
					->setId( $order->get_meta( Constants::BILLING_AGREEMENT_ID ) )
					->setType( Token::BILLING_AGREEMENT ) ) );
		}

		return $result;
	}

	/**
	 * @param \WC_Order $order
	 *
	 * @return bool|void
	 */
	public function process_refund_offer( $order ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$transaction_id = isset( $_POST['txn_id'] ) ? wc_clean( wp_unslash( $_POST['txn_id'] ) ) : false;
		$amount         = isset( $_POST['amt'] ) ? round( wc_clean( wp_unslash( $_POST['amt'] ) ), 2 ) : false;
		if ( ! $transaction_id || ! $amount ) {
			return;
		}
		$client = $this->payment_handler->client->orderMode( $order );
		$result = $client->captures->refund( $transaction_id, new Refund( [
			'amount'     => new Amount( [
				'value'         => NumberUtil::round_incl_currency( $amount, $order->get_currency() ),
				'currency_code' => $order->get_currency()
			] ),
			'invoice_id' => $transaction_id
		] ) );

		if ( is_wp_error( $result ) ) {
			$this->logger->log( sprintf( 'Error processing refund for order %s. Reason: %s', $order->get_id(), $result->get_error_message() ) );

			return false;
		} else {
			$this->logger->log( sprintf( 'Transaction %s refunded: Amount: %s', $order->get_id(), $amount ) );
			PayPalFee::update_net_from_refund( $result, $order, true );

			return true;
		}
	}

	public function get_transaction_link( $transaction_id, $order_id ) {
		$order = wc_get_order( $order_id );
		$url   = 'https://www.paypal.com/activity/payment/%s';
		if ( $order->get_meta( Constants::PPCP_ENVIRONMENT ) === 'sandbox' ) {
			$url = 'https://www.sandbox.paypal.com/activity/payment/%s';
		}

		return sprintf( $url, $transaction_id );
	}

	public function set_paypal_order( $order ) {
		$this->paypal_order = $order;
	}

	protected function is_processing_redirect() {
		return $this->paypal_order;
	}

	private function update_order_fee( PaymentResult $result, \WC_Order $order ) {
		if ( $result->is_captured() ) {
			$order_behavior = WFOCU_Core()->funnels->get_funnel_option( 'order_behavior' );
			$use_main_order = $order_behavior === 'batching';
			if ( $use_main_order ) {
				// If using the main order update the net and fees
				$calculation = new FeeCalculation( $order );
				$calculation->calculate_from_receivable_breakdown( $result->get_capture()->seller_receivable_breakdown );
				$calculation->save();
			} else {
				add_action( 'wfocu_offer_new_order_created_' . $this->get_key(), function ( $order ) use ( $result ) {
					$calculation = new FeeCalculation( $order, true );
					$calculation->calculate_from_receivable_breakdown( $result->get_capture()->seller_receivable_breakdown );
					$calculation->save();
				} );
			}
		}
	}

}