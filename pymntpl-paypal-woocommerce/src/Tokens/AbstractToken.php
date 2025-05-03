<?php

namespace PaymentPlugins\WooCommerce\PPCP\Tokens;

use PaymentPlugins\PayPalSDK\Payer;
use PaymentPlugins\PayPalSDK\PayerInfo;
use PaymentPlugins\PayPalSDK\PaymentSource;

/**
 * Class AbstractToken
 */
abstract class AbstractToken extends \WC_Payment_Token {

	protected $format;

	public function __construct( $token = '' ) {
		parent::__construct( $token );
	}

	public abstract function get_payment_method_formats();

	public function get_payment_method_format( $format ) {
		return $this->get_payment_method_formats()[ $format ]['format'] ?? '';
	}

	protected abstract function get_default_format();

	public function set_environment( $value ) {
		$this->set_prop( 'environment', $value );
	}

	public function set_customer_id( $value ) {
		$this->set_prop( 'customer_id', $value );
	}

	public function set_format( $format ) {
		$this->format = $format;
	}

	public function get_environment() {
		return $this->get_prop( 'environment' );
	}

	public function get_customer_id() {
		return $this->get_prop( 'customer_id' );
	}

	public function get_display_name( $deprecated = '' ) {
		return $this->get_payment_method_title();
	}

	public function get_payment_method_title( $format = '' ) {
		if ( ! $format && $this->format ) {
			$format = $this->format;
		}
		$format = ! $format ? $this->get_default_format() : $format;
		$format = $this->get_payment_method_formats()[ $format ]['format'];
		$data   = [];
		foreach ( array_keys( $this->data ) as $key ) {
			$method = 'get_' . $key;
			if ( method_exists( $this, $method ) ) {
				$data["{{$key}}"] = $this->$method();
			} else {
				$data["{{$key}}"] = $this->get_prop( $key );
			}
		}

		return apply_filters( 'wc_ppcp_token_payment_method_title', str_replace( array_keys( $data ), $data, $format ), $this, $data );
	}

	/**
	 * @param Payer|PayerInfo $payer
	 *
	 * @return mixed
	 */
	public abstract function initialize_from_payer( $payer );

	/**
	 * @param \PaymentPlugins\PayPalSDK\Order $order
	 *
	 * @since 1.1.0
	 * @return mixed
	 */
	public abstract function initialize_from_paypal_order( $order );

	public abstract function initialize_from_payment_source( PaymentSource $payment_source );

	/**
	 * @param array $item
	 *
	 * @return array
	 */
	public abstract function get_payment_method_item( $item );

}