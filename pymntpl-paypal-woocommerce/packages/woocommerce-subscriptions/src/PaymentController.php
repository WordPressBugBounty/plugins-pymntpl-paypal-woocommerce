<?php

namespace PaymentPlugins\PPCP\WooCommerceSubscriptions;

use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\PayPalSDK\PaymentSource;
use PaymentPlugins\PayPalSDK\PaymentToken;
use PaymentPlugins\PayPalSDK\Token;
use PaymentPlugins\WooCommerce\PPCP\Constants;
use PaymentPlugins\WooCommerce\PPCP\Customer;
use PaymentPlugins\WooCommerce\PPCP\Factories\CoreFactories;
use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\PaymentResult;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Tokens\AbstractToken;
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
	 * @param                                                                    $result
	 * @param \WC_Order                                                          $order
	 * @param \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway $payment_method
	 *
	 * @return bool|mixed|\WP_Error
	 */
	public function process_payment( $result, \WC_Order $order, AbstractGateway $payment_method ) {
		/**
		 * 1. Get the setup token from the request. If there is no setup token, return an error;
		 * 2. Create a payment token from the setup token
		 * 3. Save the token in the database and associate with customer.
		 */
		try {
			/**
			 * This code should only be called if the order total is zero. That means this is a trial subscription.
			 */
			if ( 0 == $order->get_total() ) {
				$this->factories->initialize( $order, $payment_method );

				if ( $payment_method->should_use_saved_payment_method() ) {
					$payment_token_id = $payment_method->get_saved_payment_method_token_id_from_request( $order );
					$payment_token    = $this->client->orderMode( $order )->paymentTokensV3->retrieve( $payment_token_id );
					if ( is_wp_error( $payment_token ) ) {
						throw new \Exception( __( 'The selected payment method could not be used. Please try another payment method.', 'pymntpl-paypal-woocommerce' ) );
					}
					$token = $payment_method->get_payment_method_token_instance();
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

					$customer = Customer::instance( $order->get_customer_id(), wc_ppcp_get_order_mode( $order ) );

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
				$this->save_subscription_meta( $order, $token );

				if ( $payment_method->get_option( 'intent' ) === 'capture' ) {
					$order->payment_complete();
				} else {
					$order->update_status( apply_filters( 'wc_ppcp_authorized_order_status', $payment_method->get_option( 'authorize_status', 'on-hold' ) ) );
				}
				$result = true;
			}
		} catch ( \Exception $e ) {
			$result = new \WP_Error( 'subscription_error', $e->getMessage() );
		}

		return $result;
	}

	public function process_payment_for_billing_agreement( $result, \WC_Order $order, AbstractGateway $payment_method ) {
		// Order contains a subscription. Create the billing agreement
		$billing_token = $payment_method->get_billing_token_from_request();
		if ( ! $billing_token ) {
			// There is no billing token so create one and redirect to approval page.
			$this->factories->initialize( $order );
			$this->factories->billingAgreement->set_needs_shipping( false );
			$params = $this->factories->billingAgreement->from_order();
			$token  = $this->client->orderMode( $order )->billingAgreementTokens->create( $params );
			if ( is_wp_error( $token ) ) {
				return $token;
			}

			return [
				'result'   => 'success',
				'redirect' => $token->getApprovalUrl()
			];
		}

		$this->log->info( sprintf( 'Creating billing agreement via %s. Billing agreement token: %s. Order ID: %s', __METHOD__, $billing_token, $order->get_id() ), 'payment' );


		$billing_agreement = $this->client->billingAgreements->create( [ 'token_id' => $billing_token ] );
		if ( is_wp_error( $billing_agreement ) ) {
			return $billing_agreement;
		}

		$this->log->info( sprintf( 'Billing agreement %s created via %s. Billing agreement token: %s. Order ID: %s', $billing_agreement->id, __METHOD__, $billing_token, $order->get_id() ), 'payment' );

		$token = $payment_method->get_payment_method_token_instance();
		$token->initialize_from_payer( $billing_agreement->payer->payer_info );
		$order->set_payment_method_title( $token->get_payment_method_title() );
		$order->update_meta_data( Constants::BILLING_AGREEMENT_ID, $billing_agreement->id );
		$order->update_meta_data( Constants::PPCP_ENVIRONMENT, $this->client->getEnvironment() );
		$order->update_meta_data( Constants::PAYER_ID, $token->get_payer_id() );
		$order->save();
		$this->save_billing_agreement_subscription_meta( $order, $token );
		$payment_method->payment_handler->set_use_billing_agreement( true );
		if ( 0 == $order->get_total() ) {
			if ( $payment_method->get_option( 'intent' ) === 'capture' ) {
				$order->payment_complete();
			} else {
				$order->update_status( apply_filters( 'wc_ppcp_authorized_order_status', $payment_method->get_option( 'authorize_status', 'on-hold' ) ) );
			}
			$result = true;
		} else {
			$result = false;
		}

		return $result;
	}

	private function save_subscription_meta( \WC_Order $order, AbstractToken $token ) {
		foreach ( wcs_get_subscriptions_for_order( $order ) as $subscription ) {
			$subscription->set_payment_method_title( $token->get_payment_method_title() );
			$subscription->update_meta_data( Constants::PPCP_ENVIRONMENT, $order->get_meta( Constants::PPCP_ENVIRONMENT ) );
			$subscription->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
			$subscription->save();
		}
	}

	private function save_billing_agreement_subscription_meta( \WC_Order $order, AbstractToken $token ) {
		foreach ( wcs_get_subscriptions_for_order( $order ) as $subscription ) {
			$subscription->set_payment_method_title( $token->get_payment_method_title() );
			$subscription->update_meta_data( Constants::PPCP_ENVIRONMENT, $order->get_meta( Constants::PPCP_ENVIRONMENT ) );
			$subscription->update_meta_data( Constants::BILLING_AGREEMENT_ID, $order->get_meta( Constants::BILLING_AGREEMENT_ID ) );
			$subscription->update_meta_data( Constants::PAYER_ID, $order->get_meta( Constants::PAYER_ID ) );
			$subscription->save();
		}
	}

	/**
	 * Process a payment for a renewal order.
	 *
	 * @param float     $amount
	 * @param \WC_Order $order
	 *
	 * @return void
	 */
	public function process_renewal_payment( $amount, \WC_Order $order ) {
		try {
			/**
			 * @var AbstractGateway $payment_method
			 */
			$payment_method = wc_get_payment_gateway_by_order( $order );
			$this->factories->initialize( $order, $payment_method );

			$request = $this->factories->order->from_order( $payment_method->get_option( 'intent' ) );
			$request->setPaymentSource( $this->factories->paymentSource->from_order() );

			OrderFilterUtil::filter_order( $request );

			$request = apply_filters( 'wc_ppcp_renewal_order_params', $request, $order, $payment_method->payment_handler );


			$response = $this->client->orderMode( $order )->orders->create( $request );

			if ( is_wp_error( $response ) ) {
				throw new \Exception( $response->get_error_message() );
			}

			$result = new PaymentResult( $response, $order, $payment_method );

			if ( $result->success() ) {
				if ( $result->is_captured() ) {
					PayPalFee::add_fee_to_order( $order, $result->get_capture()->getSellerReceivableBreakdown(), false );
					$order->payment_complete( $result->get_capture_id() );
				} else {
					$order->update_meta_data( Constants::AUTHORIZATION_ID, $result->get_authorization_id() );
					$order->set_status( apply_filters( 'wc_ppcp_authorized_renewal_order_status', $payment_method->get_option( 'authorize_status', 'on-hold' ), $order, $response, $this ) );
				}
				$payment_method->payment_handler->save_order_meta_data( $order, $response );
				$payment_method->payment_handler->add_payment_complete_message( $order, $result );

				do_action( 'wc_ppcp_renewal_payment_processed', $order, $result );
			} else {
				throw new \Exception( $result->get_error_message() );
			}
		} catch ( \Exception $e ) {
			$order->update_status( 'failed' );
			$order->add_order_note( sprintf( __( 'Recurring payment failed. Reason: %s', 'pymntpl-paypal-woocommerce' ), $e->getMessage() ) );
		}
	}

	public function process_change_payment_method( \WC_Order $order, AbstractGateway $payment_method ) {
		try {
			if ( $payment_method->should_use_saved_payment_method() ) {
				$payment_token_id = $payment_method->get_saved_payment_method_token_id_from_request();
				$payment_token    = $this->client->orderMode( $order )->paymentTokensV3->retrieve( $payment_token_id );
				if ( is_wp_error( $payment_token ) ) {
					throw new \Exception( $payment_token->get_error_message() );
				}
				$token = $payment_method->get_payment_method_token_instance();
				$token->initialize_from_payment_token( $payment_token );
				$token->set_user_id( $order->get_customer_id() );
			} else {
				$payment_token_id = $this->get_payment_token_id_from_request();

				if ( ! $payment_token_id ) {
					throw new \Exception( __( 'A payment token ID is required when adding a payment method.', 'pymntpl-paypal-woocommerce' ) );
				}

				$payment_token = $this->client->orderMode( $order )->paymentTokensV3->retrieve( $payment_token_id );

				if ( is_wp_error( $payment_token ) ) {
					throw new \Exception( $payment_token->get_error_message() );
				}

				$token = $payment_method->get_payment_method_token_instance();
				$token->initialize_from_payment_token( $payment_token );
				$token->set_user_id( $order->get_customer_id() );
				$token->save();
			}

			$order->set_payment_method_title( $token->get_payment_method_title() );
			$order->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
			$order->save();

			return [ 'result' => 'success', 'redirect' => wc_get_page_permalink( 'myaccount' ) ];
		} catch ( \Exception $e ) {
			return new \WP_Error( sprintf( __( 'Error saving payment method for subscription. Reason: %s', 'pymntpl-paypal-woocommerce' ), $e->getMessage() ) );
		}
	}

	public function process_change_payment_method_with_billing_agreement( \WC_Order $order, AbstractGateway $payment_method ) {
		// create billing agreement and associate to the subscription
		$billing_token = $payment_method->get_billing_token_from_request();
		try {
			if ( $billing_token ) {
				$billing_agreement = $this->client->billingAgreements->create( [ 'token_id' => $billing_token ] );
				if ( is_wp_error( $billing_agreement ) ) {
					throw new \Exception( $billing_agreement->get_error_message() );
				}
				// save the payment method info to the subscription
				$token = $payment_method->get_payment_method_token_instance();
				$token->initialize_from_payer( $billing_agreement->payer->payer_info );
				$order->set_payment_method_title( $token->get_payment_method_title() );
				$order->update_meta_data( Constants::BILLING_AGREEMENT_ID, $billing_agreement->id );
				$order->update_meta_data( Constants::PAYER_ID, $token->get_payer_id() );
				$order->save();
			} else {
				// There is no billing token so create one and redirect to approval page.
				$this->factories->initialize( $order );
				$this->factories->billingAgreement->set_needs_shipping( false );
				$params                                               = $this->factories->billingAgreement->from_order();
				$params['plan']['merchant_preferences']['return_url'] = add_query_arg( [
					'change_payment_method' => $order->get_id()
				], $params['plan']['merchant_preferences']['return_url'] );

				$params['plan']['merchant_preferences']['cancel_url'] = add_query_arg( [ 'change_payment_method' => $order->get_id(), '_wpnonce' => wp_create_nonce() ], $order->get_checkout_payment_url() );

				$token = $this->client->orderMode( $order )->billingAgreementTokens->create( $params );
				if ( is_wp_error( $token ) ) {
					throw new \Exception( ( $token->get_error_message() ) );
				}

				return [
					'result'   => 'success',
					'redirect' => $token->getApprovalUrl()
				];
			}
		} catch ( \Exception $e ) {
			return new \WP_Error( sprintf( __( 'Error saving payment method for subscription. Reason: %s', 'pymntpl-paypal-woocommerce' ), $e->getMessage() ) );
		}

		return [ 'result' => 'success', 'redirect' => wc_get_page_permalink( 'myaccount' ) ];
	}

}