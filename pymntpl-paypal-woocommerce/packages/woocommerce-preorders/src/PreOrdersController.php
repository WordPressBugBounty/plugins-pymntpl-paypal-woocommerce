<?php

namespace PaymentPlugins\PPCP\WooCommercePreOrders;

use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\WooCommerce\PPCP\Constants;
use PaymentPlugins\WooCommerce\PPCP\Factories\CoreFactories;
use PaymentPlugins\WooCommerce\PPCP\Logger;
use PaymentPlugins\WooCommerce\PPCP\Main;
use PaymentPlugins\WooCommerce\PPCP\PaymentHandler;
use PaymentPlugins\WooCommerce\PPCP\PaymentResult;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Rest\Routes\CartCheckout;
use PaymentPlugins\WooCommerce\PPCP\Utilities\PayPalFee;
use PaymentPlugins\WooCommerce\PPCP\Utils;
use PaymentPlugins\WooCommerce\PPCP\WPPayPalClient;

class PreOrdersController {

	private $payment_controller;

	private $client;

	private $factories;

	private $log;

	public function __construct( PaymentController $payment_controller ) {
		$this->payment_controller = $payment_controller;
	}

	public function initialize() {
		add_filter( 'wc_ppcp_get_paypal_flow', [ $this, 'get_paypal_flow' ], 10, 2 );
		add_filter( 'wc_ppcp_process_payment_result', [ $this, 'process_payment' ], 10, 3 );
		add_action( 'wc_pre_orders_process_pre_order_completion_payment_ppcp', [ $this, 'process_order_completion_payment' ] );
		add_action( 'wc_pre_orders_process_pre_order_completion_payment_ppcp_card', [ $this, 'process_order_completion_payment' ] );
		add_action( 'wc_ppcp_rest_handle_checkout_validation', [ $this, 'handle_checkout_validation' ] );
		add_filter( 'wc_ppcp_show_card_save_checkbox', [ $this, 'show_card_save_checkbox' ] );
		add_filter( 'wc_ppcp_add_payment_method_data', [ $this, 'add_payment_method_data' ], 10, 3 );
		add_filter( 'wc_ppcp_payment_method_save_required', [ $this, 'get_payment_method_save_required' ], 10, 2 );
		add_filter( 'wc_ppcp_checkout_payment_method_save_required', [ $this, 'get_checkout_payment_method_save_required' ], 10, 3 );
	}

	/**
	 * @param                                                 $flow
	 * @param \PaymentPlugins\WooCommerce\PPCP\ContextHandler $context
	 *
	 * @return mixed|string
	 * @deprecated - vault parameter is no longer needed.
	 */
	public function get_paypal_flow( $flow, $context ) {
		if ( $flow === Constants::VAULT ) {
			return $flow;
		}
		if ( $context->has_context( [ $context::CART, $context::CHECKOUT, $context::SHOP ] ) ) {
			if ( \WC_Pre_Orders_Cart::cart_contains_pre_order() ) {
				$flow = Constants::VAULT;
			}
		} elseif ( $context->is_order_pay() ) {
			$order = Utils::get_order_from_query_vars();
			if ( \WC_Pre_Orders_Order::order_contains_pre_order( $order ) ) {
				$flow = Constants::VAULT;
			}
		} elseif ( $context->is_product() ) {
			global $product;
			if ( is_a( $product, 'WC_Product' ) && \WC_Pre_Orders_Product::product_can_be_pre_ordered( $product ) ) {
				$flow = Constants::VAULT;
			}
		}

		return $flow;
	}

	/**
	 * @param mixed           $result
	 * @param \WC_Order       $order
	 * @param AbstractGateway $payment_method
	 */
	public function process_payment( $result, \WC_Order $order, AbstractGateway $payment_method ) {
		if ( \WC_Pre_Orders_Order::order_contains_pre_order( $order ) && \WC_Pre_Orders_Order::order_requires_payment_tokenization( $order ) ) {
			if ( $payment_method->supports( 'vault' ) ) {
				$result = $this->payment_controller->process_payment( $result, $order, $payment_method );
			} else {
				$result = $this->payment_controller->process_payment_with_billing_agreement( $result, $order, $payment_method );
			}
		}

		return $result;
	}

	public function process_order_completion_payment( \WC_Order $order ) {
		$payment_method = wc_get_payment_gateway_by_order( $order );
		$this->payment_controller->process_order_completion_payment( $order, $payment_method );
	}

	/**
	 * If the cart contains a pre-order and shipping is required, redirect to the checkout page
	 * so the customer can select their shipping method
	 *
	 * @param CartCheckout $route
	 */
	public function handle_checkout_validation( $route ) {
		if ( in_array( $route->request->get_param( 'context' ), [ 'product', 'cart' ] ) ) {
			$key = "{$route->payment_method->id}_billing_token";
			if ( \WC_Pre_Orders_Cart::cart_contains_pre_order() && isset( $route->request[ $key ] ) ) {
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
				if ( \WC_Pre_Orders_Cart::cart_contains_pre_order() ) {
					$bool = ! \WC_Pre_Orders_Product::product_is_charged_upon_release( \WC_Pre_Orders_Cart::get_pre_order_product() );
				}
			}
		}

		return $bool;
	}

	public function get_payment_method_save_required( $bool, AbstractGateway $payment_method ) {
		if ( ! $bool && $payment_method->supports( 'pre-orders' ) ) {
			if ( \WC_Pre_Orders_Cart::cart_contains_pre_order() ) {
				$bool = \WC_Pre_Orders_Product::product_is_charged_upon_release( \WC_Pre_Orders_Cart::get_pre_order_product() );
			}
		}

		return $bool;
	}

	public function get_checkout_payment_method_save_required( $bool, AbstractGateway $payment_method, \WC_Order $order ) {
		if ( ! $bool && $payment_method->supports( 'pre-orders' ) ) {
			if ( \WC_Pre_Orders_Order::order_contains_pre_order( $order ) ) {
				$bool = \WC_Pre_Orders_Order::order_requires_payment_tokenization( $order );
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
		if ( $context->is_checkout() ) {
			if ( \WC_Pre_Orders_Cart::cart_contains_pre_order() ) {
				if ( \WC_Pre_Orders_Product::product_is_charged_upon_release( \WC_Pre_Orders_Cart::get_pre_order_product() ) ) {
					$data['needsSetupToken'] = true;
				}
			}
		} elseif ( $context->is_order_pay() ) {
		}

		return $data;
	}

}