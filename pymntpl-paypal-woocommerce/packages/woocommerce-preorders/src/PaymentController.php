<?php

namespace PaymentPlugins\PPCP\WooCommercePreOrders;

use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\PayPalSDK\PaymentSource;
use PaymentPlugins\PayPalSDK\PaymentToken;
use PaymentPlugins\WooCommerce\PPCP\Constants;
use PaymentPlugins\WooCommerce\PPCP\Customer;
use PaymentPlugins\WooCommerce\PPCP\Factories\CoreFactories;
use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\Main;
use PaymentPlugins\WooCommerce\PPCP\PaymentHandler;
use PaymentPlugins\WooCommerce\PPCP\PaymentResult;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Utilities\OrderFilterUtil;
use PaymentPlugins\WooCommerce\PPCP\Utilities\PayPalFee;
use PaymentPlugins\WooCommerce\PPCP\WPPayPalClient;

class PaymentController {

	private $client;

	private $factories;

	private $log;

	public function __construct( WPPayPalClient $client, CoreFactories $factories, Logger $log ) {
		$this->client    = $client;
		$this->factories = $factories;
		$this->log       = $log;
	}

	/**
	 * @param \WC_Order                                                          $order
	 * @param \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway $payment_method
	 *
	 * @return void
	 */
	public function process_payment( $result, \WC_Order $order, AbstractGateway $payment_method ) {
		/**
		 * 1. Check if this request is using a saved payment method.
		 * 2. If new payment method, create the payment token
		 */

		try {
			$this->factories->initialize( $order, $payment_method );

			$customer = Customer::instance( $order->get_customer_id(), wc_ppcp_get_order_mode( $order ) );

			if ( $payment_method->should_use_saved_payment_method() ) {
				$payment_method_token = $payment_method->get_saved_payment_method_token_id_from_request();
				if ( ! $payment_method_token ) {
					throw new \Exception( __( 'A valid payment method ID is required to process this order.', 'pymntpl-paypal-woocommerce' ) );
				}
				$payment_token = $this->client->orderMode( $order )->paymentTokensV3->retrieve( $payment_method_token );
				$token         = $payment_method->get_payment_method_token_instance();
				$token->set_user_id( $order->get_customer_id() );
				$token->initialize_from_payment_token( $payment_token );
			} else {
				$payment_token_id = $payment_method->get_payment_token_id_from_request();
				if ( ! $payment_token_id ) {
					$setup_token = $this->client->orderMode( $order )->setupTokens->create( $this->factories->setupToken->create( 'checkout' ) );

					if ( is_wp_error( $setup_token ) ) {
						throw new \Exception( __( 'A payment token is required to process this order.', 'pymntpl-paypal-woocommerce' ) );
					}

					return [
						'result'   => 'success',
						'redirect' => $setup_token->getApprovalUrl()
					];
				}

				$payment_token = $this->client->orderMode( $order )->paymentTokensV3->retrieve( $payment_token_id );

				if ( is_wp_error( $payment_token ) ) {
					throw new \Exception( $payment_token->get_error_message() );
				}

				if ( ! $customer->has_id() ) {
					$customer->set_id( $payment_token->getCustomer()->getId() );
					$customer->save();
				} else {
					if ( $payment_token->getCustomer()->getId() !== $customer->get_id() ) {
						throw new \Exception( __( 'Customer ID for payment method does not match customer ID for logged in user.', 'pymntpl-paypal-woocommerce' ) );
					}
				}

				$token = $payment_method->get_payment_method_token_instance();
				$token->set_user_id( $order->get_customer_id() );
				$token->initialize_from_payment_token( $payment_token );

				$token->save();
			}

			$order->set_payment_method_title( $token->get_payment_method_title() );
			$order->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
			$order->update_meta_data( Constants::PPCP_ENVIRONMENT, $this->client->getEnvironment() );
			$order->save();

			\WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );

			$result = true;
		} catch ( \Exception $e ) {
			$result = new \WP_Error( 'preorders_error', $e->getMessage() );
		}

		return $result;
	}

	/**
	 * @param \WC_Order                                                          $order
	 * @param \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway $payment_method
	 *
	 * @return array|\PaymentPlugins\PayPalSDK\BillingAgreement|\PaymentPlugins\PayPalSDK\BillingAgreementToken|void
	 * @throws \WC_Data_Exception
	 */
	public function process_payment_with_billing_agreement( $result, \WC_Order $order, AbstractGateway $payment_method ) {
		$billing_token = $payment_method->get_billing_token_from_request();
		if ( $billing_token ) {
			$billing_agreement = $this->client->billingAgreements->create( [ 'token_id' => $billing_token ] );
			if ( is_wp_error( $billing_agreement ) ) {
				return $billing_agreement;
			}
			$token = $payment_method->get_payment_method_token_instance();
			$token->initialize_from_payer( $billing_agreement->payer->payer_info );
			$order->set_payment_method_title( $token->get_payment_method_title() );
			$order->update_meta_data( Constants::BILLING_AGREEMENT_ID, $billing_agreement->id );
			$order->update_meta_data( Constants::PPCP_ENVIRONMENT, $this->client->getEnvironment() );
			$order->update_meta_data( Constants::PAYER_ID, $token->get_payer_id() );
			$order->save();
			$payment_method->payment_handler->set_use_billing_agreement( true );
			\WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );
			$result = true;
		} else {
			$this->factories->initialize( $order );
			$this->factories->billingAgreement->set_needs_shipping( false );
			$params = $this->factories->billingAgreement->from_order( $payment_method );
			$token  = $this->client->orderMode( $order )->billingAgreementTokens->create( $params );
			if ( is_wp_error( $token ) ) {
				return $token;
			}

			return [
				'result'   => 'success',
				'redirect' => $token->getApprovalUrl()
			];
		}

		return $result;
	}

	public function process_order_completion_payment( \WC_Order $order, AbstractGateway $payment_method ) {
		$this->factories->initialize( $order, $payment_method );

		try {
			$request = $this->factories->order->from_order( $payment_method->get_option( 'intent' ) );
			$request->setPaymentSource( $this->factories->paymentSource->from_order() );
			$request = apply_filters( 'wc_ppcp_preorder_order_params', $request, $order, $payment_method->payment_handler );

			OrderFilterUtil::filter_order( $request );

			$response = $this->client->orderMode( $order )->orders->create( $request );

			if ( is_wp_error( $response ) ) {
				throw new \Exception( $response->get_error_message() );
			}

			$result = new PaymentResult( $response, $order, null );

			if ( $result->success() ) {
				if ( $result->is_captured() ) {
					PayPalFee::add_fee_to_order( $order, $result->get_capture()->getSellerReceivableBreakdown(), false );
					$order->payment_complete( $result->get_capture_id() );
				} else {
					$order->update_meta_data( Constants::AUTHORIZATION_ID, $result->get_authorization_id() );
					$order->set_status( apply_filters( 'wc_ppcp_authorized_preorder_status', $payment_method->get_option( 'authorize_status', 'on-hold' ), $order, $paypal_order, $this ) );
				}
				$payment_method->payment_handler->save_order_meta_data( $order, $response );
				$payment_method->payment_handler->add_payment_complete_message( $order, $result );
			} else {
				throw new \Exception( $result->get_error_message() );
			}
		} catch ( \Exception $e ) {
			$order->update_status( 'failed' );
			$order->add_order_note( sprintf( __( 'Payment for pre-order failed. Reason: %s', 'pymntpl-paypal-woocommerce' ), $e->getMessage() ) );
		}
	}

}