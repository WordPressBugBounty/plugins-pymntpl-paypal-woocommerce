<?php


namespace PaymentPlugins\PPCP\WooCommerceSubscriptions;

use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\WooCommerce\PPCP\Constants;
use PaymentPlugins\WooCommerce\PPCP\Factories\CoreFactories;
use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\Main;
use PaymentPlugins\WooCommerce\PPCP\PaymentHandler;
use PaymentPlugins\WooCommerce\PPCP\PaymentResult;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Rest\Routes\CartCheckout;
use PaymentPlugins\WooCommerce\PPCP\Tokens\AbstractToken;
use PaymentPlugins\WooCommerce\PPCP\Utilities\PayPalFee;
use PaymentPlugins\WooCommerce\PPCP\Utils;
use PaymentPlugins\WooCommerce\PPCP\WPPayPalClient;

/**
 * Class SubscriptionController
 *
 * @package PaymentPlugins\WooCommerce\PPCP\Integrations
 */
class SubscriptionController {

	private $payment_controller;

	private $client;

	private $factories;

	private $log;

	public function __construct( PaymentController $payment_controller, WPPayPalClient $client, CoreFactories $factories, Logger $log ) {
		$this->payment_controller = $payment_controller;
		$this->client             = $client;
		$this->factories          = $factories;
		$this->log                = $log;
	}

	public function initialize() {
		add_filter( 'wc_ppcp_process_payment_result', [ $this, 'process_payment' ], 10, 3 );
		add_action( 'wc_ppcp_save_order_meta_data', [ $this, 'save_order_metadata' ], 10, 4 );
		add_filter( 'wc_ppcp_get_paypal_flow', [ $this, 'get_paypal_flow' ], 10, 2 );
		add_filter( 'wc_ppcp_get_formatted_cart_item', [ $this, 'get_formatted_cart_item' ], 10, 2 );
		add_action( 'wc_ppcp_rest_handle_checkout_validation', [ $this, 'handle_checkout_validation' ] );
		add_action( 'woocommerce_scheduled_subscription_payment_ppcp', [ $this, 'scheduled_subscription_payment' ], 10, 2 );
		add_action( 'woocommerce_scheduled_subscription_payment_ppcp_card', [ $this, 'scheduled_subscription_payment' ], 10, 2 );
		add_filter( 'woocommerce_subscription_payment_meta', [ $this, 'add_subscription_payment_meta' ], 10, 2 );
		add_filter( 'woocommerce_subscription_failing_payment_method_updated_ppcp', [ $this, 'update_failing_payment_method' ], 10, 2 );
		add_filter( 'woocommerce_subscription_failing_payment_method_updated_ppcp_card', [ $this, 'update_failing_payment_method' ], 10, 2 );
		add_filter( 'wc_ppcp_show_card_save_checkbox', [ $this, 'show_card_save_checkbox' ] );
		add_filter( 'wc_ppcp_add_payment_method_data', [ $this, 'add_payment_method_data' ], 10, 3 );
		add_filter( 'wc_ppcp_payment_method_save_required', [ $this, 'get_payment_method_save_required' ], 10, 2 );
		add_filter( 'wc_ppcp_checkout_payment_method_save_required', [ $this, 'get_checkout_payment_method_save_required' ], 10, 3 );
		add_filter( 'woocommerce_subscription_note_new_payment_method_title', [ $this, 'update_new_payment_method_title' ], 10, 3 );

		/**
		 * Filter called when cart or checkout block is enabled.
		 */
		add_filter( 'wc_ppcp_blocks_get_extended_data', [ $this, 'get_extended_schema_data' ] );
	}

	/**
	 * @param mixed           $result
	 * @param \WC_Order       $order
	 * @param AbstractGateway $payment_method
	 */
	public function process_payment( $result, \WC_Order $order, AbstractGateway $payment_method ) {
		if ( $this->is_change_payment_method_request() && \wcs_is_subscription( $order ) ) {
			return $this->process_change_payment_method_request( $order, $payment_method );
		} elseif ( wcs_order_contains_subscription( $order ) || wcs_order_contains_renewal( $order ) ) {
			if ( $payment_method->supports( 'vault' ) ) {
				$result = $this->payment_controller->process_payment( $result, $order, $payment_method );
			} else {
				$result = $this->payment_controller->process_payment_for_billing_agreement( $result, $order, $payment_method );
			}
		}

		return $result;
	}

	private function process_change_payment_method_request( \WC_Order $order, AbstractGateway $payment_method ) {
		if ( $payment_method->supports( 'vault' ) ) {
			return $this->payment_controller->process_change_payment_method( $order, $payment_method );
		} else {
			return $this->payment_controller->process_change_payment_method_with_billing_agreement( $order, $payment_method );
		}
	}

	private function is_change_payment_method_request() {
		return did_action( 'woocommerce_subscriptions_pre_update_payment_method' )
		       || \WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment;
	}

	public function save_order_metadata( \WC_Order $order, Order $paypal_order, AbstractGateway $payment_method, AbstractToken $token ) {
		if ( wcs_order_contains_subscription( $order ) ) {
			foreach ( wcs_get_subscriptions_for_order( $order ) as $subscription ) {
				$subscription->set_payment_method_title( $token->get_payment_method_title() );
				$subscription->update_meta_data( Constants::PPCP_ENVIRONMENT, wc_ppcp_get_order_mode( $order ) );
				if ( $token->get_token() ) {
					$subscription->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
				}
				$subscription->save();
			}
		}
		if ( $token->get_token() ) {
			$order->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $token->get_token() );
		}
	}

	/**
	 * @param                                                 $flow
	 * @param \PaymentPlugins\WooCommerce\PPCP\ContextHandler $context
	 *
	 * @return mixed|string
	 * @deprecated  - no longer need vault parameter
	 */
	public function get_paypal_flow( $flow, $context ) {
		if ( $flow === Constants::VAULT ) {
			return $flow;
		}
		if ( ! $context->is_order_pay() && ! $context->is_product() ) {
			if ( \WC_Subscriptions_Cart::cart_contains_subscription() || \wcs_cart_contains_renewal() ) {
				$flow = Constants::VAULT;
			} elseif ( \WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ) {
				$flow = Constants::VAULT;
			}
		} elseif ( $context->is_order_pay() ) {
			$order = Utils::get_order_from_query_vars();
			if ( \WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment || \wcs_order_contains_subscription( $order ) ) {
				$flow = Constants::VAULT;
			}
		} elseif ( $context->is_product() ) {
			global $product;
			if ( \WC_Subscriptions_Cart::cart_contains_subscription() ) {
				$flow = Constants::VAULT;
			} elseif ( is_a( $product, 'WC_Product' ) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
				$flow = Constants::VAULT;
			}
		}

		return $flow;
	}

	/**
	 * @param float     $amount
	 * @param \WC_Order $order
	 */
	public function scheduled_subscription_payment( $amount, \WC_Order $order ) {
		$this->payment_controller->process_renewal_payment( $amount, $order );
	}

	/**
	 * @param array            $payment_meta
	 * @param \WC_Subscription $subscription
	 */
	public function add_subscription_payment_meta( $payment_meta, $subscription ) {
		$payment_meta['ppcp']      = [
			'post_meta' => [
				Constants::BILLING_AGREEMENT_ID => [
					'value' => $subscription->get_meta( Constants::BILLING_AGREEMENT_ID ),
					'label' => __( 'Billing Agreement ID', 'pymntpl-paypal-woocommerce' ),
				],
				Constants::PAYMENT_METHOD_TOKEN => [
					'value' => $subscription->get_meta( Constants::PAYMENT_METHOD_TOKEN ),
					'label' => __( 'Payment Method Token', 'pymntpl-paypal-woocommerce' ),
				]
			]
		];
		$payment_meta['ppcp_card'] = [
			'post_meta' => [
				Constants::PAYMENT_METHOD_TOKEN => [
					'value' => $subscription->get_meta( Constants::PAYMENT_METHOD_TOKEN ),
					'label' => __( 'Payment Method Token', 'pymntpl-paypal-woocommerce' ),
				]
				/*Constants::CUSTOMER_ID          => [
					'value' => $subscription->get_meta( Constants::CUSTOMER_ID ),
					'label' => __( 'Customer ID', 'pymntpl-paypal-woocommerce' ),
				]*/
			]
		];

		return apply_filters( 'wc_ppcp_add_subscription_payment_meta', $payment_meta, $subscription );
	}

	/**
	 * @param array      $data
	 * @param array|null $cart_item
	 *
	 * @return mixed
	 */
	public function get_formatted_cart_item( $data, $cart_item ) {
		if ( $cart_item && \WC_Subscriptions_Product::is_subscription( $cart_item['data'] ) ) {
			if ( \WC_Subscriptions_Product::get_trial_length( $cart_item['data'] ) > 0 ) {
				$data['unit_amount']['value'] = 0;
			}
		}

		return $data;
	}

	/**
	 * @param \WC_Subscription $subscription
	 * @param \WC_Order        $renewal_order
	 */
	public function update_failing_payment_method( $subscription, $renewal_order ) {
		$payment_method = wc_get_payment_gateway_by_order( $renewal_order );
		if ( $payment_method->supports( 'vault' ) ) {
			$payment_method_token = $renewal_order->get_meta( Constants::PAYMENT_METHOD_TOKEN );
			if ( $payment_method_token ) {
				$payment_token = $this->client->orderMode( $renewal_order )->paymentTokensV3->retrieve( $payment_method_token );
				if ( ! is_wp_error( $payment_token ) ) {
					$token = $payment_method->get_payment_method_token_instance();
					$token->initialize_from_payment_token( $payment_token );
					$subscription->set_payment_method_title( $token->get_payment_method_title() );
				}
				$subscription->update_meta_data( Constants::PAYMENT_METHOD_TOKEN, $payment_method_token );
				$subscription->save();
			}
		} else {
			$billing_agreement = $renewal_order->get_meta( Constants::BILLING_AGREEMENT_ID );
			if ( $billing_agreement ) {
				$result = $this->client->orderMode( $renewal_order )->billingAgreements->retrieve( $billing_agreement );
				if ( ! is_wp_error( $result ) ) {
					$token = $payment_method->get_payment_method_token_instance();
					$token->initialize_from_payer( $result->payer->payer_info );
					$subscription->set_payment_method_title( $token->get_payment_method_title() );
				}
				$subscription->update_meta_data( Constants::BILLING_AGREEMENT_ID, $billing_agreement );
				$subscription->save();
			}
		}
	}

	/**
	 * If the cart contains a subscription and shipping is required, redirect to the checkout page
	 * so the customer can select their shipping method
	 *
	 * @param CartCheckout $route
	 */
	public function handle_checkout_validation( $route ) {
		if ( in_array( $route->request->get_param( 'context' ), [ 'product', 'cart' ] ) ) {
			$key = "{$route->payment_method->id}_billing_token";
			if ( \WC_Subscriptions_Cart::cart_contains_subscription() && isset( $route->request[ $key ] ) ) {
				if ( WC()->cart->needs_shipping() ) {
					wc_add_notice( __( 'Please select a shipping method for your order.', 'pymntpl-paypal-woocommerce' ), 'notice' );
					wp_send_json(
						[
							'result'   => 'success',
							'redirect' => $route->get_order_review_url( [ $key => $route->request->get_param( $key ) ] ),
							'reload'   => false,
						],
						200
					);
				}
			}
		}
	}

	public function show_card_save_checkbox( $bool ) {
		if ( $bool ) {
			if ( is_checkout() && ! is_checkout_pay_page() ) {
				if ( \WC_Subscriptions_Cart::cart_contains_subscription() ) {
					$bool = false;
				}
				if ( \wcs_cart_contains_renewal() ) {
					$bool = false;
				}
			} elseif ( \WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ) {
				$bool = false;
			}
		}

		return $bool;
	}

	public function get_payment_method_save_required( $bool, AbstractGateway $payment_method ) {
		if ( ! $bool && $payment_method->supports( 'subscriptions' ) ) {
			if ( ! \wcs_is_manual_renewal_required() ) {
				if ( \WC_Subscriptions_Cart::cart_contains_subscription() ) {
					$bool = true;
				} elseif ( wcs_cart_contains_renewal() ) {
					$bool = true;
				}
			}
		}

		return $bool;
	}

	/**
	 * @param bool                                                               $bool
	 * @param \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway $payment_method
	 * @param \WC_Order                                                          $order
	 *
	 * @return bool|mixed
	 */
	public function get_checkout_payment_method_save_required( $bool, AbstractGateway $payment_method, \WC_Order $order ) {
		if ( ! $bool && $payment_method->supports( 'subscriptions' ) ) {
			if ( ! \wcs_is_manual_renewal_required() ) {
				if ( wcs_order_contains_subscription( $order ) ) {
					$bool = true;
				} elseif ( wcs_order_contains_renewal( $order ) ) {
					$bool = true;
				}
			}
		}

		return $bool;
	}

	/**
	 * @param array                                           $data
	 * @param \PaymentPlugins\WooCommerce\PPCP\ContextHandler $context
	 * @param AbstractGateway                                 $payment_method
	 *
	 * @return void
	 */
	public function add_payment_method_data( $data, $context, $payment_method ) {
		if ( ! \wcs_is_manual_renewal_required() ) {
			if ( $context->is_checkout() || $context->is_cart() ) {
				if ( \WC_Subscriptions_Cart::cart_contains_free_trial() && WC()->cart->get_total( 'edit' ) == 0 ) {
					$data['needsSetupToken'] = true;
				}
			} elseif ( $context->is_product() && $context->get_product_id() ) {
				$product = \wc_get_product( $context->get_product_id() );
				if ( \WC_Subscriptions_Product::is_subscription( $product ) ) {
					$data['needsSetupToken'] = \WC_Subscriptions_Product::get_trial_length( $product ) > 0;
				}
			} elseif ( \WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment ) {
				$data['needsSetupToken'] = true;
			} elseif ( $context->is_order_pay() ) {
				// @todo - add code for order pay
			} else {
				if ( \WC_Subscriptions_Cart::cart_contains_free_trial() && WC()->cart->total == 0 ) {
					$data['needsSetupToken'] = true;
				}
			}
		}


		return $data;
	}

	/**
	 * @param string           $new_payment_method_title
	 * @param string           $gateway_id
	 * @param \WC_Subscription $subscription
	 *
	 * @return void
	 */
	public function update_new_payment_method_title( $new_payment_method_title, $gateway_id, $subscription ) {
		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		$payment_method   = $payment_gateways[ $gateway_id ] ?? null;
		if ( $payment_method && $payment_method instanceof AbstractGateway ) {
			if ( $payment_method->supports( 'vault' ) ) {
				if ( $payment_method->should_use_saved_payment_method() ) {
					$payment_token_id = $payment_method->get_saved_payment_method_token_id_from_request();
				} else {
					$payment_token_id = $payment_method->get_payment_token_id_from_request();
				}
				if ( $payment_token_id ) {
					$payment_token = $this->client->orderMode( $subscription )->paymentTokensV3->retrieve( $payment_token_id );
					if ( ! is_wp_error( $payment_token ) ) {
						$token = $payment_method->get_payment_method_token_instance();
						$token->initialize_from_payment_token( $payment_token );
						$new_payment_method_title = $token->get_payment_method_title();
					}
				}
			}
		}

		return $new_payment_method_title;
	}

	public function get_extended_schema_data( $data ) {
		if ( empty( $data['needsSetupToken'] ) ) {
			if ( \WC_Subscriptions_Cart::cart_contains_free_trial() && WC()->cart->total == 0 ) {
				$data['needsSetupToken'] = true;
			}
		}

		return $data;
	}

}