<?php


namespace PaymentPlugins\PPCP\WooCommerceSubscriptions;

use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\WooCommerce\PPCP\Constants;
use PaymentPlugins\WooCommerce\PPCP\Factories\CoreFactories;
use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\Main;
use PaymentPlugins\WooCommerce\PPCP\PaymentHandler;
use PaymentPlugins\WooCommerce\PPCP\PaymentMethodRegistry;
use PaymentPlugins\WooCommerce\PPCP\PaymentResult;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Payments\PaymentGateways;
use PaymentPlugins\WooCommerce\PPCP\Rest\Routes\CartCheckout;
use PaymentPlugins\WooCommerce\PPCP\Tokens\AbstractToken;
use PaymentPlugins\WooCommerce\PPCP\Utilities\NumberUtil;
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
		add_action( 'woocommerce_scheduled_subscription_payment_ppcp', [
			$this,
			'scheduled_subscription_payment'
		], 10, 2 );
		add_action( 'woocommerce_scheduled_subscription_payment_ppcp_card', [
			$this,
			'scheduled_subscription_payment'
		], 10, 2 );
		add_action( 'woocommerce_scheduled_subscription_payment_ppcp_applepay', [
			$this,
			'scheduled_subscription_payment'
		], 10, 2 );
		add_filter( 'woocommerce_subscription_payment_meta', [ $this, 'add_subscription_payment_meta' ], 10, 2 );
		add_filter( 'woocommerce_subscription_failing_payment_method_updated_ppcp', [
			$this,
			'update_failing_payment_method'
		], 10, 2 );
		add_filter( 'woocommerce_subscription_failing_payment_method_updated_ppcp_card', [
			$this,
			'update_failing_payment_method'
		], 10, 2 );
		add_filter( 'woocommerce_subscription_failing_payment_method_updated_ppcp_applepay', [
			$this,
			'update_failing_payment_method'
		], 10, 2 );
		add_filter( 'wc_ppcp_show_card_save_checkbox', [ $this, 'show_card_save_checkbox' ] );
		add_filter( 'wc_ppcp_add_payment_method_data', [ $this, 'add_payment_method_data' ], 10, 3 );
		add_filter( 'wc_ppcp_payment_method_save_required', [ $this, 'get_payment_method_save_required' ], 10, 2 );
		add_filter( 'wc_ppcp_checkout_payment_method_save_required', [
			$this,
			'get_checkout_payment_method_save_required'
		], 10, 3 );
		add_filter( 'woocommerce_subscription_note_new_payment_method_title', [
			$this,
			'update_new_payment_method_title'
		], 10, 3 );
		add_filter( 'wc_ppcp_product_payment_gateways', [ $this, 'filter_product_payment_gateways' ], 10, 2 );
		add_filter( 'wc_ppcp_express_checkout_payment_gateways', [ $this, 'filter_express_payment_gateways' ] );
		add_filter( 'wc_ppcp_cart_payment_gateways', [ $this, 'filter_cart_payment_gateways' ] );
		//add_filter( 'woocommerce_available_payment_gateways', [ $this, 'get_available_payment_gateways' ] );
		add_action( 'wc_ppcp_get_order_from_cart', [ $this, 'maybe_authorize_order_for_vaulting' ], 10, 2 );
		add_filter( 'wc_ppcp_cart_data', [ $this, 'add_recurring_total_to_cart_data' ], 10, 2 );
		add_action( 'wc_ppcp_get_order_from_order_pay', [ $this, 'maybe_authorize_order_pay_for_vaulting' ], 10, 3 );

		/**
		 * Filter called when cart or checkout block is enabled.
		 */
		add_filter( 'wc_ppcp_blocks_get_extended_data', [ $this, 'get_extended_schema_data' ] );

		add_filter( 'wc_ppcp_cart_shipping_packages', [ $this, 'get_shipping_packages' ] );
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
			} elseif ( $this->is_manual_renewal_enabled() && ! $payment_method->supports( 'subscriptions' ) ) {
				// Gateway can't do automatic recurring billing (e.g. Google Pay) and the customer will
				// pay renewals manually anyway.
				if ( 0 == $order->get_total() ) {
					// Nothing due today and no payment method to save for automatic billing - complete
					// the order directly instead of asking PayPal to create a $0 order.
					$result = $this->payment_controller->process_zero_total_order( $order, $payment_method );
				}
				// Otherwise (immediate payment due) fall through unchanged - the gateway already
				// created/confirmed its own PayPal order before submitting checkout.
			} else {
				$result = $this->payment_controller->process_payment_for_billing_agreement( $result, $order, $payment_method );
			}
		}

		return $result;
	}

	private function process_change_payment_method_request( \WC_Order $order, AbstractGateway $payment_method ) {
		if ( $payment_method->supports( 'vault' ) ) {
			if ( ! $payment_method->should_use_saved_payment_method() && ! $payment_method->supports( 'vault_setup_token' ) ) {
				// This gateway can only be vaulted as an attribute of a real, authorized order
				// (e.g. Apple Pay) - it was already created/authorized client-side against the
				// subscription's real total, with intent forced to AUTHORIZE. See
				// maybe_authorize_order_pay_for_vaulting().
				return $this->payment_controller->process_change_payment_method_via_order( $order, $payment_method );
			}

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
		if ( $flow === Constants::VAULT || $this->is_manual_renewal_required() ) {
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
		/**
		 * @var PaymentMethodRegistry $payment_registry
		 */
		$payment_registry = wc_ppcp_get_container()->get( PaymentMethodRegistry::class );

		foreach ( $payment_registry->get_registered_integrations() as $integration ) {
			if ( $integration->supports( 'vault' ) ) {
				$payment_meta[ $integration->id ] = [
					'post_meta' => [
						Constants::PAYMENT_METHOD_TOKEN => [
							'value' => $subscription->get_meta( Constants::PAYMENT_METHOD_TOKEN ),
							'label' => __( 'Payment Method Token', 'pymntpl-paypal-woocommerce' ),
						]
					]
				];
			}
			if ( $integration->supports( 'billing_agreement' ) ) {
				$payment_meta[ $integration->id ]['post_meta'][ Constants::BILLING_AGREEMENT_ID ] = [
					'value' => $subscription->get_meta( Constants::BILLING_AGREEMENT_ID ),
					'label' => __( 'Billing Agreement ID', 'pymntpl-paypal-woocommerce' ),
				];
			}
		}

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
			if ( ! $this->is_manual_renewal_required() ) {
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
			if ( ! $this->is_manual_renewal_required() ) {
				// wcs_order_contains_subscription()/wcs_order_contains_renewal() check whether
				// $order is a parent/renewal/resubscribe/switch order that relates to a
				// subscription - neither is true when $order IS the subscription itself, as it is
				// on the "Change Payment Method" page (which uses the subscription's own ID as
				// the order/pay target - see SubscriptionController::maybe_authorize_order_pay_for_vaulting()).
				if ( wcs_order_contains_subscription( $order ) || \wcs_is_subscription( $order ) ) {
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
	 * @return array
	 */
	public function add_payment_method_data( $data, $context, $payment_method ) {
		if ( ! $this->is_manual_renewal_required() ) {
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
				// When there is a subscription associated with the order, the order pay page redirects to the checkout page.
				// The same applies for failed renewal orders.
			} else {
				if ( \WC_Subscriptions_Cart::cart_contains_free_trial() && WC()->cart->get_total( 'edit' ) == 0 ) {
					$data['needsSetupToken'] = true;
				}
			}
		}


		return $data;
	}

	/**
	 * Returns the highest recurring total across the cart's recurring carts (i.e. the largest
	 * future charge amount for a multi-subscription cart), formatted the same way the "vault via
	 * order" authorization amount is built (see maybe_authorize_order_for_vaulting()) - both must
	 * match exactly, since PayPal rejects the payment if a wallet's displayed amount doesn't match
	 * the order it's confirming.
	 *
	 * @return string
	 */
	private function get_recurring_cart_total() {
		$recurring_total = 0;
		if ( ! empty( WC()->cart->recurring_carts ) ) {
			foreach ( WC()->cart->recurring_carts as $recurring_cart ) {
				$recurring_total = max( $recurring_total, (float) $recurring_cart->total );
			}
		}

		return NumberUtil::round_incl_currency( $recurring_total, get_woocommerce_currency() );
	}

	/**
	 * Exposes the recurring cart total on the cart data sent to the client (via
	 * PayPalDataTransformer::transform_cart(), so it stays current across cart/shipping/item AJAX
	 * updates - not just the initial page load). Gateways that can only vault a payment method as
	 * part of a real order (e.g. Apple Pay) need this to show the same amount in their own payment
	 * sheet that the server will use to build that order - see
	 * packages/woocommerce-subscriptions/assets/js/checkout.js.
	 *
	 * @param array    $data
	 * @param \WC_Cart $cart
	 *
	 * @return array
	 * @since 2.0.23
	 *
	 */
	public function add_recurring_total_to_cart_data( $data, $cart ) {
		if ( ! $this->is_manual_renewal_required() && \WC_Subscriptions_Cart::cart_contains_free_trial() && 0 == $cart->get_total( 'edit' ) ) {
			$data['recurringTotal'] = $this->get_recurring_cart_total();
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

	public function filter_product_payment_gateways( $payment_gateways, $product ) {
		if ( \WC_Subscriptions_Product::is_subscription( $product ) ) {
			if ( $this->is_manual_renewal_required() && \WC_Subscriptions_Product::get_trial_length( $product ) > 0 ) {
				// WC Subscriptions itself doesn't require a payment method here (a free trial
				// with automatic payments turned off site-wide) - no gateway should be offered.
				return [];
			}
			if ( ! $this->is_manual_renewal_enabled() ) {
				// Manual renewals aren't accepted, so a gateway must be able to bill the
				// subscription automatically to be usable here.
				foreach ( $payment_gateways as $gateway ) {
					if ( ! $gateway->supports( 'subscriptions' ) ) {
						unset( $payment_gateways[ $gateway->id ] );
					}
				}
			}
		}

		return $payment_gateways;
	}

	public function filter_express_payment_gateways( $payment_gateways ) {
		if ( \WC_Subscriptions_Cart::cart_contains_subscription() ) {
			if ( WC()->cart && ! WC()->cart->needs_payment() ) {
				// WC Subscriptions itself doesn't require a payment method here (e.g. a $0 cart
				// with automatic payments turned off site-wide) - no gateway should be offered.
				return [];
			}
			if ( ! $this->is_manual_renewal_enabled() ) {
				// Manual renewals aren't accepted, so a gateway must be able to bill the
				// subscription automatically to be usable here.
				foreach ( $payment_gateways as $gateway ) {
					if ( ! $gateway->supports( 'subscriptions' ) ) {
						unset( $payment_gateways[ $gateway->id ] );
					}
				}
			}
		}

		return $payment_gateways;
	}

	public function filter_cart_payment_gateways( $payment_gateways ) {
		if ( \WC_Subscriptions_Cart::cart_contains_subscription() ) {
			if ( WC()->cart && ! WC()->cart->needs_payment() ) {
				// WC Subscriptions itself doesn't require a payment method here (e.g. a $0 cart
				// with automatic payments turned off site-wide) - no gateway should be offered.
				return [];
			}
			if ( ! $this->is_manual_renewal_enabled() ) {
				// Manual renewals aren't accepted, so a gateway must be able to bill the
				// subscription automatically to be usable here.
				foreach ( $payment_gateways as $gateway ) {
					if ( ! $gateway->supports( 'subscriptions' ) ) {
						unset( $payment_gateways[ $gateway->id ] );
					}
				}
			}
		}

		return $payment_gateways;
	}

	public function get_available_payment_gateways( $gateways ) {
		if ( is_checkout() ) {
			if ( \WC_Subscriptions_Cart::cart_contains_free_trial() && WC()->cart->get_total( 'edit' ) == 0 ) {
				unset( $gateways['ppcp_applepay'] );
			}
		}

		return $gateways;
	}

	/**
	 * @return bool
	 * @since 1.1.3
	 */
	private function is_manual_renewal_required() {
		return function_exists( 'wcs_is_manual_renewal_required' ) && \wcs_is_manual_renewal_required();
	}

	/**
	 * @return bool
	 * @since 2.0.23
	 */
	private function is_manual_renewal_enabled() {
		return function_exists( 'wcs_is_manual_renewal_enabled' ) && \wcs_is_manual_renewal_enabled();
	}

	/**
	 * @param $packages
	 *
	 * @return array
	 */
	public function get_shipping_packages( $packages ) {
		if ( ! empty( $packages ) ) {
			return $packages;
		}
		if ( \WC_Subscriptions_Cart::cart_contains_free_trial() ) {
			if ( isset( WC()->cart->recurring_carts ) ) {
				$count = 0;
				\WC_Subscriptions_Cart::set_calculation_type( 'recurring_total' );
				foreach ( WC()->cart->recurring_carts as $recurring_cart_key => $recurring_cart ) {
					foreach ( $recurring_cart->get_shipping_packages() as $i => $base_package ) {
						$packages[ $recurring_cart_key . '_' . $count ] = WC()->shipping()->calculate_shipping_for_package( $base_package );
					}
					$count ++;
				}
				\WC_Subscriptions_Cart::set_calculation_type( 'none' );
			}
		}

		return $packages;
	}

	/**
	 * Some gateways (e.g. Apple Pay - see VaultSetupTokenTrait) can only vault a payment method
	 * as an attribute of a real, authorized order - PayPal's Orders API rejects a $0 amount, so a
	 * free trial with nothing due today has no real amount to create an order against. Override
	 * the order to authorize the subscription's recurring total instead. This order is voided
	 * immediately after the payment method is vaulted (see PaymentController::process_vault_via_order()),
	 * so only the amount needs to be correct - the breakdown/items are dropped since PayPal
	 * requires them to reconcile with the amount and neither is meaningful for a throwaway
	 * authorization.
	 *
	 * @param \PaymentPlugins\PayPalSDK\Order $order
	 * @param \WP_REST_Request                $request
	 */
	public function maybe_authorize_order_for_vaulting( $order, $request ) {
		$payment_gateways = wc_ppcp_get_container()->get( PaymentGateways::class );
		$payment_method   = $payment_gateways->get_gateway( $request->get_param( 'payment_method' ) );

		if ( ! $payment_method || ! $payment_method->supports( 'vault' ) || $payment_method->supports( 'vault_setup_token' ) ) {
			return;
		}

		if ( ! $payment_method->is_payment_method_save_required() ) {
			return;
		}

		if ( ! \WC_Subscriptions_Cart::cart_contains_free_trial() || 0 != WC()->cart->get_total( 'edit' ) ) {
			return;
		}

		if ( empty( WC()->cart->recurring_carts ) ) {
			return;
		}

		$recurring_total = $this->get_recurring_cart_total();

		if ( $recurring_total <= 0 ) {
			return;
		}

		$order->setIntent( Order::AUTHORIZE );

		/**
		 * @var \PaymentPlugins\PayPalSDK\PurchaseUnit $purchase_unit
		 */
		$purchase_unit = $order->getPurchaseUnits()->get( 0 );
		$purchase_unit->getAmount()->setValue( $recurring_total );
		unset( $purchase_unit->getAmount()->breakdown );
		unset( $purchase_unit->items );
	}

	/**
	 * Same problem as maybe_authorize_order_for_vaulting(), but for the "Change Payment Method"
	 * page - which reuses WC's "pay for order" page/route (order/pay - see OrderPay.php) with the
	 * subscription ID standing in as the order ID, rather than cart/order. Unlike the free-trial
	 * signup case, the amount here is already correct - WC Subscriptions only zeroes the
	 * subscription's total on the final form POST (WC_Subscriptions_Change_Payment_Gateway::maybe_zero_total()),
	 * not on this REST call - so only the intent needs to be forced to AUTHORIZE.
	 *
	 * Can't use is_change_payment_method_request() here - this fires from a REST request to
	 * order/pay (made when confirming the wallet payment, before the checkout form ever submits),
	 * a separate HTTP request from the original page load that doesn't carry the
	 * change_payment_method query arg WC_Subscriptions_Change_Payment_Gateway's flag depends on.
	 * Checking whether $order is itself a subscription is reliable instead - the change-payment
	 * flow is the only order/pay scenario where the "order" being paid is the subscription's own
	 * ID rather than a derived order.
	 *
	 * @param \PaymentPlugins\PayPalSDK\Order $paypal_order
	 * @param \WC_Order                       $order
	 * @param AbstractGateway                 $payment_method
	 */
	public function maybe_authorize_order_pay_for_vaulting( $paypal_order, $order, $payment_method ) {
		if ( \wcs_is_subscription( $order )
		     && $payment_method->supports( 'vault' )
		     && ! $payment_method->supports( 'vault_setup_token' ) ) {
			$paypal_order->setIntent( Order::AUTHORIZE );

			// This order never represents a real invoice - it's voided immediately after
			// vaulting - so its deterministic invoice_id (derived from the subscription's order
			// number, see PurchaseUnitFactory::generate_invoice_id()) would collide with any
			// other attempt for the same subscription (e.g. a retry) and get rejected by PayPal
			// as a duplicate. Give it a unique one instead.
			$purchase_unit = $paypal_order->getPurchaseUnits()->get( 0 );
			if ( $purchase_unit->getInvoiceId() ) {
				$purchase_unit->setInvoiceId( $purchase_unit->getInvoiceId() . '-' . Utils::random_string( 6 ) );
			}
		}
	}

}