<?php

namespace PaymentPlugins\WooCommerce\PPCP\Tokens;

use PaymentPlugins\PayPalSDK\PaymentSource;
use PaymentPlugins\PayPalSDK\PaymentToken;

/**
 * Token that represents a Credit Card within PayPal.
 */
class CreditCardToken extends AbstractToken {

	protected $type = 'PPCP_Card';

	protected $extra_data = [
		'brand'       => '',
		'last4'       => '',
		'expiry'      => '',
		'customer_id' => '',
		'environment' => ''
	];

	public function set_brand( $value ) {
		$this->set_prop( 'brand', $value );
	}

	public function set_last4( $value ) {
		$this->set_prop( 'last4', $value );
	}

	public function set_expiry( $value ) {
		$this->set_prop( 'expiry', $value );
	}

	public function get_brand() {
		$brand = $this->get_prop( 'brand' );
		if ( ! \is_string( $brand ) ) {
			$brand = '';
		}

		return wc_get_credit_card_type_label( $brand );
	}

	public function get_last4() {
		return $this->get_prop( 'last4' );
	}

	public function get_expiry() {
		return $this->get_prop( 'expiry' );
	}

	public function get_environment() {
		return $this->get_prop( 'environment' );
	}

	public function get_payment_method_formats() {
		return apply_filters( 'wc_ppcp_payment_method_formats', [
			'type_ending_in'     => [
				'format'  => __( '{brand} ending in {last4}', 'pymntpl-paypal-woocommerce' ),
				'example' => __( 'Visa ending in 1111', 'pymntpl-paypal-woocommerce' ),
				'label'   => __( 'Type ending in', 'pymntpl-paypal-woocommerce' )
			],
			'type_masked_number' => [
				'format'  => __( '{brand} ************{last4}', 'pymntpl-paypal-woocommerce' ),
				'example' => __( 'Visa ************1111', 'pymntpl-paypal-woocommerce' ),
				'label'   => __( 'Types masked number', 'pymntpl-paypal-woocommerce' )
			],
			'type_last_4'        => [
				'format'  => __( '{brand} {last4}', 'pymntpl-paypal-woocommerce' ),
				'example' => __( 'Visa 1111', 'pymntpl-paypal-woocommerce' ),
				'label'   => __( 'Types last 4', 'pymntpl-paypal-woocommerce' )
			]
		], $this );
	}

	protected function get_default_format() {
		return 'type_ending_in';
	}

	/**
	 * @inheritDoc
	 */
	public function initialize_from_payer( $payer ) {
		// TODO: Implement initialize_from_payer() method.
	}

	/**
	 * @inheritDoc
	 */
	public function initialize_from_paypal_order( $order ) {
		$this->initialize_from_payment_source( $order->getPaymentSource() );
	}

	public function get_payment_method_item( $item ) {
		$item['method']['last4'] = $this->get_last4();
		$item['method']['brand'] = $this->get_brand();
		$item['expires']         = $this->get_expiry();

		return $item;
	}

	public function initialize_from_payment_source( PaymentSource $payment_source ) {
		if ( isset( $payment_source->card ) ) {
			$this->set_token( $payment_source->card->attributes->vault->id ?? '' );
			$this->set_brand( $payment_source->card->brand ?? '' );
			$this->set_last4( $payment_source->card->last_digits ?? '' );
			$this->set_expiry( $payment_source->card->expiry ?? '' );
			$this->set_customer_id( $payment_source->card->attributes->vault->customer->id ?? '' );
		}
	}

	/**
	 * @param \PaymentPlugins\PayPalSDK\PaymentToken|\PaymentPlugins\PayPalSDK\SetupToken $payment_token
	 *
	 * @return void
	 */
	public function initialize_from_payment_token( $payment_token ) {
		$this->set_token( $payment_token->getId() );
		if ( isset( $payment_token->payment_source ) ) {
			$this->set_brand( $payment_token->payment_source->card->brand ?? '' );
			$this->set_last4( $payment_token->payment_source->card->last_digits ?? '' );
			$this->set_expiry( $payment_token->payment_source->card->expiry ?? '' );
		}
		if ( isset( $payment_token->customer ) ) {
			$this->set_customer_id( $payment_token->customer->id );
		}
	}

}