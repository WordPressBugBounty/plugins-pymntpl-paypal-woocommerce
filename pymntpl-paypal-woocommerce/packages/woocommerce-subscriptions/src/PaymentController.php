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
		 * This code should only be called if the order total is zero. That means this is a trial subscription.
		 *
		 * 1. Get the setup token from the request. If there is no setup token, return an error;
		 * 2. Create a payment token from the setup token
		 * 3. Save the token in the database and associate with customer.
		 */
		if ( 0 != $order->get_total() ) {
			return $result;
		}

		try {
			if ( $payment_method->should_use_saved_payment_method() ) {
				// Customer already has a saved payment method for this gateway - there's nothing
				// new to vault (no PayPal order was ever created for this request), just
				// associate the existing token with this $0 order/subscription and complete it.
				$payment_token_id = $payment_method->get_saved_payment_method_token_id_from_request( $order );
				$payment_token    = $this->client->orderMode( $order )->paymentTokensV3->retrieve( $payment_token_id );
				if ( is_wp_error( $payment_token ) ) {
					throw new \Exception( __( 'The selected payment method could not be used. Please try another payment method.', 'pymntpl-paypal-woocommerce' ) );
				}
				$token = $payment_method->get_payment_method_token_instance();
				$token->set_user_id( $order->get_customer_id() );
				$token->initialize_from_payment_token( $payment_token );

				$this->complete_zero_total_order( $order, $payment_method, $token );

				return true;
			}

			if ( ! $payment_method->supports( 'vault_setup_token' ) ) {
				// This gateway can only be vaulted as an attribute of a real, authorized order
				// (e.g. Apple Pay) - it was already created/authorized client-side against the
				// subscription's recurring total. See SubscriptionController::maybe_authorize_order_for_vaulting().
				return $this->process_vault_via_order( $order, $payment_method );
			}

			$this->factories->initialize( $order, $payment_method );

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

			$this->complete_zero_total_order( $order, $payment_method, $token );

			return true;
		} catch ( \Exception $e ) {
			return new \WP_Error( 'subscription_error', $e->getMessage() );
		}
	}

	/**
	 * Shared tail for finalizing a $0-today subscription order once its payment method token is
	 * known: saves the token to the order/subscription meta and completes/holds the order based
	 * on the gateway's configured intent.
	 *
	 * @param \WC_Order       $order
	 * @param AbstractGateway $payment_method
	 * @param AbstractToken   $token
	 *
	 * @return void
	 */
	private function complete_zero_total_order( \WC_Order $order, AbstractGateway $payment_method, AbstractToken $token ) {
		$order->set_payment_method_title( $token->get_payment_method_title() );
		$order->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
		$order->update_meta_data( Constants::PPCP_ENVIRONMENT, $this->client->getEnvironment() );
		$order->save();
		$this->save_subscription_meta( $order, $payment_method, $token );

		if ( $payment_method->get_option( 'intent' ) === 'capture' ) {
			$order->payment_complete();
		} else {
			$order->update_status( apply_filters( 'wc_ppcp_authorized_order_status', $payment_method->get_option( 'authorize_status', 'on-hold' ) ) );
		}
	}

	/**
	 * For gateways that don't support 'vault_setup_token' (e.g. Apple Pay - see
	 * VaultSetupTokenTrait), a payment method can only be vaulted as an attribute of a real,
	 * authorized order, since PayPal's Orders API rejects a $0 amount. The order was already
	 * created for the subscription's recurring total (not the live $0 total) with intent
	 * AUTHORIZE and confirmed client-side - see
	 * SubscriptionController::maybe_authorize_order_for_vaulting(). This authorizes it
	 * server-side, saves the resulting vaulted payment method, voids the authorization (nothing
	 * should actually be charged for a $0 trial), and completes the WC order directly.
	 *
	 * @param \WC_Order       $order
	 * @param AbstractGateway $payment_method
	 *
	 * @return bool|\WP_Error
	 */
	public function process_vault_via_order( \WC_Order $order, AbstractGateway $payment_method ) {
		try {
			$paypal_order_id = $payment_method->payment_handler->get_paypal_order_id_from_request();
			if ( ! $paypal_order_id ) {
				throw new \Exception( __( 'A PayPal order ID is required to save this payment method.', 'pymntpl-paypal-woocommerce' ) );
			}

			$paypal_order = $this->client->orderMode( $order )->orders->authorize( $paypal_order_id );
			if ( is_wp_error( $paypal_order ) ) {
				throw new \Exception( $paypal_order->get_error_message() );
			}

			$result = new PaymentResult( $paypal_order, $order, $payment_method );
			if ( ! $result->success() ) {
				throw new \Exception( $result->get_error_message() ?: __( 'The payment method could not be verified.', 'pymntpl-paypal-woocommerce' ) );
			}

			$token = $payment_method->payment_handler->get_payment_method_token_from_paypal_order( $paypal_order );

			// This bypasses PaymentHandler::payment_complete()/save_order_meta_data(), so
			// wc_ppcp_save_order_meta_data never fires and CustomerController::save_order_meta()
			// never runs - replicate what it does here so the token is actually persisted as a
			// saved payment method and the customer's PayPal customer id gets recorded, instead
			// of only ever living in the order/subscription meta.
			if ( $order->get_customer_id() ) {
				$token->set_user_id( $order->get_customer_id() );
				$token->save();

				$customer = Customer::instance( $order->get_customer_id() );
				if ( ! $customer->has_id() ) {
					$customer->set_id( $token->get_customer_id() );
					$customer->save();
				}
			}

			// Nothing should actually be charged for a $0 trial - void the authorization that was
			// only created to get the payment method vaulted. Called directly against the API
			// (not PaymentHandler::process_void()) since that also cancels the WC order, which
			// isn't right here - the order completes as paid ($0) below instead.
			$void_result = $this->client->orderMode( $order )->authorizations->void( $result->get_authorization_id() );
			if ( is_wp_error( $void_result ) ) {
				$this->log->error(
					sprintf(
						'Failed to void vaulting authorization %s for order %s. Reason: %s',
						$result->get_authorization_id(), $order->get_id(), $void_result->get_error_message()
					), 'payment'
				);
				$order->add_order_note(
					sprintf(
						__( 'Failed to void the PayPal authorization (ID: %1$s) created to save the payment method. Reason: %2$s. This authorization may still be held on the customer\'s account and should be voided manually in PayPal if it does not expire on its own.', 'pymntpl-paypal-woocommerce' ),
						$result->get_authorization_id(),
						$void_result->get_error_message()
					)
				);
			}

			$this->complete_zero_total_order( $order, $payment_method, $token );

			return true;
		} catch ( \Exception $e ) {
			return new \WP_Error( 'subscription_error', $e->getMessage() );
		}
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

	private function save_subscription_meta( \WC_Order $order, AbstractGateway $payment_method, ?AbstractToken $token = null ) {
		foreach ( wcs_get_subscriptions_for_order( $order ) as $subscription ) {
			if ( $token ) {
				$subscription->set_payment_method_title( $token->get_payment_method_title() );
				$subscription->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
			} else {
				$subscription->set_payment_method_title( $payment_method->get_title() );
			}
			$subscription->update_meta_data( Constants::PPCP_ENVIRONMENT, $order->get_meta( Constants::PPCP_ENVIRONMENT ) );
			$subscription->save();
		}
	}

	/**
	 * Completes a $0-today subscription order for a gateway that doesn't support automatic
	 * recurring billing (no vault/billing agreement) - e.g. Google Pay with manual renewals
	 * accepted. There's no payment method to save for future automatic charges, so this just
	 * marks the order paid/authorized directly instead of asking PayPal to create a $0 order,
	 * which its API rejects.
	 *
	 * @param \WC_Order       $order
	 * @param AbstractGateway $payment_method
	 *
	 * @return bool
	 */
	public function process_zero_total_order( \WC_Order $order, AbstractGateway $payment_method ) {
		$order->set_payment_method_title( $payment_method->get_title() );
		$order->update_meta_data( Constants::PPCP_ENVIRONMENT, $this->client->getEnvironment() );
		$order->save();

		$this->save_subscription_meta( $order, $payment_method );

		if ( $payment_method->get_option( 'intent' ) === 'capture' ) {
			$order->payment_complete();
		} else {
			$order->update_status( apply_filters( 'wc_ppcp_authorized_order_status', $payment_method->get_option( 'authorize_status', 'on-hold' ) ) );
		}

		return true;
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
			$request->setPaymentSource( $this->factories->paymentSource->from_order( 'recurring' ) );

			OrderFilterUtil::filter_order( $request );

			$request = apply_filters( 'wc_ppcp_renewal_order_params', $request, $order, $payment_method->payment_handler );

			$this->log->info(
				sprintf(
					'Creating PayPal order for subscription renewal via %s. Order ID: %s. Args: %s',
					__METHOD__, $order->get_id(), print_r( $request->toArray(), true )
				),
				'payment'
			);

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
			$this->log->error( sprintf(
				'Recurring payment failed for. Order ID: %s. Reason: %s',
				$order->get_id(), $e->getMessage()
			) );
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
				$payment_token_id = $payment_method->get_payment_token_id_from_request();

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

	/**
	 * For gateways that don't support 'vault_setup_token' (e.g. Apple Pay - see
	 * VaultSetupTokenTrait), changing a subscription's payment method needs the same
	 * authorize-then-void workaround as process_vault_via_order(), since the payment method can
	 * only be vaulted as an attribute of a real, authorized order. The order was already
	 * created/authorized client-side against the subscription's real total, with intent forced to
	 * AUTHORIZE - see SubscriptionController::maybe_authorize_order_pay_for_vaulting(). Unlike
	 * process_vault_via_order(), there's no order being purchased here - just a payment method
	 * being swapped - so this finishes the same way process_change_payment_method() does.
	 *
	 * @param \WC_Order       $order
	 * @param AbstractGateway $payment_method
	 *
	 * @return array|\WP_Error
	 */
	public function process_change_payment_method_via_order( \WC_Order $order, AbstractGateway $payment_method ) {
		try {
			$paypal_order_id = $payment_method->payment_handler->get_paypal_order_id_from_request();
			if ( ! $paypal_order_id ) {
				throw new \Exception( __( 'A PayPal order ID is required to save this payment method.', 'pymntpl-paypal-woocommerce' ) );
			}

			$paypal_order = $this->client->orderMode( $order )->orders->authorize( $paypal_order_id );
			if ( is_wp_error( $paypal_order ) ) {
				throw new \Exception( $paypal_order->get_error_message() );
			}

			$result = new PaymentResult( $paypal_order, $order, $payment_method );
			if ( ! $result->success() ) {
				throw new \Exception( $result->get_error_message() ?: __( 'The payment method could not be verified.', 'pymntpl-paypal-woocommerce' ) );
			}

			$token = $payment_method->payment_handler->get_payment_method_token_from_paypal_order( $paypal_order );
			$token->set_user_id( $order->get_customer_id() );
			$token->save();

			// Nothing should actually be charged here - void the authorization that was only
			// created to get the payment method vaulted. See process_vault_via_order() for why
			// this is called directly against the API instead of PaymentHandler::process_void().
			$void_result = $this->client->orderMode( $order )->authorizations->void( $result->get_authorization_id() );
			if ( is_wp_error( $void_result ) ) {
				$this->log->error( sprintf(
					'Failed to void vaulting authorization %s for order %s. Reason: %s',
					$result->get_authorization_id(), $order->get_id(), $void_result->get_error_message()
				), 'payment' );
				$order->add_order_note( sprintf(
					__( 'Failed to void the PayPal authorization (ID: %1$s) created to save the payment method. Reason: %2$s. This authorization may still be held on the customer\'s account and should be voided manually in PayPal if it does not expire on its own.', 'pymntpl-paypal-woocommerce' ),
					$result->get_authorization_id(),
					$void_result->get_error_message()
				) );
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

				$params['plan']['merchant_preferences']['cancel_url'] = add_query_arg( [
					'change_payment_method' => $order->get_id(),
					'_wpnonce'              => wp_create_nonce()
				], $order->get_checkout_payment_url() );

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