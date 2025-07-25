<?php


namespace PaymentPlugins\WooCommerce\PPCP;


use PaymentPlugins\PayPalSDK\Authorization;
use PaymentPlugins\PayPalSDK\Capture;
use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Utilities\MessageUtils;

class PaymentResult {

	private $success;

	private $order;

	private $error_message;

	private $error_code;

	private $payment_method;

	/**
	 * @var Order
	 */
	public $paypal_order;

	private $paypal_order_id;

	private $environment;

	const PAYPAL_CHECKOUT_URL = 'https://paypal.com/checkoutnow';

	const PAYPAL_SANDBOX_CHECKOUT_URL = 'https://sandbox.paypal.com/checkoutnow';

	/**
	 * PaymentResult constructor.
	 *
	 * @param \PaymentPlugins\PayPalSDK\Order|\WP_Error $paypal_order
	 * @param \WC_Order                                 $order
	 * @param AbstractGateway                           $payment_method
	 * @param string                                    $error_message
	 */
	public function __construct( $paypal_order, \WC_Order $order, AbstractGateway $payment_method = null, $error_message = '' ) {
		$this->order          = $order;
		$this->payment_method = $payment_method;
		$this->initialize( $paypal_order, $error_message );
	}

	public function initialize( $paypal_order, $error_message = '' ) {
		if ( is_wp_error( $paypal_order ) ) {
			$this->success       = false;
			$this->paypal_order  = $paypal_order;
			$this->error_message = $paypal_order->get_error_message();
			$this->error_code    = $paypal_order->get_error_code();
		} elseif ( $paypal_order === false ) {
			$this->success       = false;
			$this->error_message = $error_message;
			$this->error_code    = 'unknown_error';
		} else {
			$this->paypal_order = $paypal_order;
			if ( $paypal_order ) {
				$this->determine_success( $paypal_order );
				$this->paypal_order_id = $paypal_order->getId();
			}
		}
	}

	/**
	 * @param $paypal_order
	 *
	 * @return void
	 * @siince 1.1.2
	 */
	private function determine_success( $paypal_order ) {
		if ( $this->is_captured() && $this->has_payments() ) {
			$this->success = \in_array( $this->get_capture_status(), [ Capture::COMPLETED, Capture::PENDING ] );
			if ( $this->has_payments() && $this->get_capture()->getProcessorResponse() ) {
				if ( ! $this->success && $this->get_capture()->getProcessorResponse()->getResponseCode() !== '0000' ) {
					$this->error_code    = $this->get_capture()->getProcessorResponse()->getResponseCode();
					$this->error_message = MessageUtils::get_process_code_message( $this->get_capture()->getProcessorResponse()->getResponseCode() );
				}
			}
		} elseif ( $this->is_authorized() && $this->has_payments() ) {
			$this->success = \in_array( $this->get_authorization_status(), [ Authorization::CREATED, Authorization::PENDING ] );
			if ( $this->get_authorization() && $this->get_authorization()->getProcessorResponse() ) {
				if ( ! $this->success && $this->get_authorization()->getProcessorResponse()->getResponseCode() !== '0000' ) {
					$this->error_code    = $this->get_authorization()->getProcessorResponse()->getResponseCode();
					$this->error_message = MessageUtils::get_process_code_message( $this->get_authorization()->getProcessorResponse()->getResponseCode() );
				}
			}
		} else {
			$this->success = false;
		}
	}

	public function success() {
		return $this->success;
	}

	public function is_captured() {
		return $this->paypal_order->intent === Order::CAPTURE;
	}

	public function is_authorized() {
		return $this->paypal_order->intent === Order::AUTHORIZE;
	}

	/**
	 * Returns the ID of the capture
	 *
	 * @return string
	 */
	public function get_capture_id() {
		return $this->paypal_order->purchase_units[0]->payments->captures[0]->id;
	}

	private function has_payments() {
		return $this->paypal_order->purchase_units[0]->payments !== null;
	}

	/**
	 * @return \PaymentPlugins\PayPalSDK\Capture
	 */
	public function get_capture() {
		return $this->paypal_order->purchase_units[0]->payments->captures[0];
	}

	/**
	 * @return \PaymentPlugins\PayPalSDK\Authorization
	 */
	public function get_authorization() {
		return $this->paypal_order->purchase_units[0]->payments->authorizations[0];
	}

	/**
	 * Returns the ID of the authorization
	 *
	 * @return string
	 */
	public function get_authorization_id() {
		return $this->get_authorization()->getId();
	}

	/**
	 * @since 1.1.2
	 * @return mixed
	 */
	private function get_authorization_status() {
		return $this->get_authorization()->getStatus();
	}

	/**
	 * @since 1.1.2
	 * @return mixed
	 */
	private function get_capture_status() {
		return $this->get_capture()->getStatus();
	}

	public function get_error_message() {
		return $this->error_message;
	}

	/**
	 * @since 1.0.24
	 * @return \PaymentPlugins\PayPalSDK\Order|\WP_Error
	 */
	public function get_paypal_order() {
		return $this->paypal_order;
	}

	public function set_error_message( $message ) {
		$this->error_message = $message;
	}

	public function get_failure_response() {
		if ( $this->needs_approval() ) {
			$data = [
				'result'   => 'success',
				'redirect' => $this->get_approval_url()
			];
		} else {
			$data = [
				'result'           => 'failure',
				'redirect'         => '',
				'ppcpErrorMessage' => $this->get_error_message()
			];
		}

		return apply_filters( 'wc_ppcp_process_payment_error_response', $data, $this->order, $this->payment_method );
	}

	public function get_approval_response() {
		$data = [
			'result'   => 'success',
			'redirect' => $this->get_approval_url()
		];

		return apply_filters( 'wc_ppcp_process_payment_approval_response', $data, $this->order, $this->payment_method );
	}

	public function get_success_response() {
		return apply_filters( 'wc_ppcp_process_payment_success_response', [
			'result'   => 'success',
			'redirect' => $this->payment_method->get_return_url( $this->order )
		], $this->order, $this->payment_method );
	}

	/**
	 * Returns true if the order requires another approval.
	 *
	 * @return void
	 */
	public function needs_approval() {
		if ( $this->paypal_order && $this->paypal_order instanceof Order ) {
			return $this->paypal_order->isCreated() || $this->paypal_order->isActionRequired()
			       || $this->paypal_order->getStatus() === 'ORDER_NOT_APPROVED';
		} else {
			return ! $this->success()
			       && ( $this->error_code === 'PAYER_ACTION_REQUIRED'
			            || $this->error_code === 'ORDER_NOT_APPROVED' );
		}
	}

	/**
	 * @since 1.0.34
	 * @return bool
	 */
	public function already_captured() {
		return ! $this->success() && $this->error_code === 'ORDER_ALREADY_CAPTURED';
	}

	/**
	 * @since 1.0.34
	 * @return bool
	 */
	public function already_authorized() {
		return ! $this->success() && $this->error_code === 'ORDER_ALREADY_AUTHORIZED';
	}

	private function get_approval_url() {
		if ( $this->paypal_order && $this->paypal_order instanceof Order ) {
			if ( $this->paypal_order->isActionRequired() && $this->paypal_order->getLinks()->count() > 0 ) {
				foreach ( $this->paypal_order->getLinks() as $link ) {
					/**
					 * @var \PaymentPlugins\PayPalSDK\Link $link
					 */
					if ( $link->getRel() === 'payer-action' ) {
						return $link->getHref();
					}
				}
			}
		}
		if ( $this->is_production() ) {
			$base_url = self::PAYPAL_CHECKOUT_URL;
		} else {
			$base_url = self::PAYPAL_SANDBOX_CHECKOUT_URL;
		}

		return add_query_arg( [ 'token' => $this->paypal_order_id ], $base_url );
	}

	private function is_production() {
		return $this->environment === Constants::PRODUCTION;
	}

	public function set_paypal_order_id( $id ) {
		$this->paypal_order_id = $id;
	}

	public function set_environment( $env ) {
		$this->environment = $env;
	}

	public function get_environment() {
		return $this->environment;
	}

	/**
	 * @since 1.1.2
	 * @return bool
	 */
	public function is_wp_error() {
		return \is_wp_error( $this->paypal_order );
	}

}