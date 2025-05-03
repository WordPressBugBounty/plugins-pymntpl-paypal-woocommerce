<?php

namespace PaymentPlugins\WooCommerce\PPCP\Tokens;

use PaymentPlugins\PayPalSDK\Payer;
use PaymentPlugins\PayPalSDK\PayerInfo;
use PaymentPlugins\PayPalSDK\PaymentSource;
use PaymentPlugins\WooCommerce\PPCP\Utilities\PaymentMethodUtils;

class PayPalToken extends AbstractToken {

	protected $type = 'PPCP';

	protected $extra_data = [
		'email'       => '',
		'first_name'  => '',
		'last_name'   => '',
		'customer_id' => '',
		'environment' => ''
	];

	public function set_email( $value ) {
		$this->set_prop( 'email', $value );
	}

	public function set_first_name( $value ) {
		$this->set_prop( 'first_name', $value );
	}

	public function set_last_name( $value ) {
		$this->set_prop( 'last_name', $value );
	}

	public function set_payer_id( $value ) {
		$this->set_prop( 'payer_id', $value );
	}

	public function get_email() {
		return $this->get_prop( 'email' );
	}

	public function get_first_name() {
		return $this->get_prop( 'first_name' );
	}

	public function get_last_name() {
		return $this->get_prop( 'last_name' );
	}

	public function get_payer_id() {
		return $this->get_prop( 'payer_id' );
	}

	public function get_payment_method_formats() {
		return apply_filters( 'wc_ppcp_payment_method_formats', [
			'name'       => [
				'format'  => __( 'PayPal', 'pymntpl-paypal-woocommerce' ),
				'example' => __( 'PayPal', 'pymntpl-paypal-woocommerce' ),
				'label'   => __( 'PayPal', 'pymntpl-paypal-woocommerce' )
			],
			'name_email' => [
				'format'  => __( 'PayPal', 'pymntpl-paypal-woocommerce' ) . ' - {email}',
				'example' => __( 'PayPal - john@paypal.com', 'pymntpl-paypal-woocommerce' ),
				'label'   => __( 'Name plus email', 'pymntpl-paypal-woocommerce' )
			]
		], $this );
	}

	protected function get_default_format() {
		return 'name_email';
	}

	/**
	 * @param Payer|PayerInfo $payer
	 *
	 * @return mixed|void
	 */
	public function initialize_from_payer( $payer ) {
		if ( $payer instanceof Payer ) {
			$this->set_first_name( $payer->name->given_name );
			$this->set_last_name( $payer->name->surname );
			$this->set_email( $payer->email_address );
			$this->set_payer_id( $payer->payer_id );
		} elseif ( $payer instanceof PayerInfo ) {
			$this->set_first_name( $payer->first_name );
			$this->set_last_name( $payer->last_name );
			$this->set_email( $payer->email );
			$this->set_payer_id( $payer->payer_id );
		}
	}

	public function initialize_from_paypal_order( $order ) {
		$this->initialize_from_payment_source( $order->getPaymentSource() );
	}

	public function get_payment_method_item( $item ) {
		$item['method']['brand'] = $this->get_payment_method_title();

		return $item;
	}

	public function initialize_from_payment_source( PaymentSource $payment_source ) {
		if ( isset( $payment_source->paypal ) ) {
			$this->set_token( $payment_source->paypal->attributes->vault->id ?? '' );
			$this->set_first_name( $payment_source->paypal->name->given_name ?? '' );
			$this->set_last_name( $payment_source->paypal->name->surname ?? '' );
			$this->set_email( $payment_source->paypal->email_address ?? '' );
			$this->set_customer_id( $payment_source->paypal->attributes->vault->customer->id ?? '' );
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
			$this->set_first_name( $payment_token->payment_source->paypal->name->given_name ?? '' );
			$this->set_last_name( $payment_token->payment_source->paypal->name->surname ?? '' );
			$this->set_email( $payment_token->payment_source->paypal->email_address ?? '' );
		}
		if ( isset( $payment_token->customer ) ) {
			$this->set_customer_id( $payment_token->customer->id );
		}
	}

	public function save() {
		/**
		 * For PayPal, we only want one token saved in the database at any give time. Check to see if a token already exists.
		 * If it does, just use that ID and update instead of creating a new entry.
		 */
		if ( ! $this->get_id() && $this->get_gateway_id() && $this->get_user_id() ) {
			$tokens = \WC_Payment_Tokens::get_tokens( [
				'user_id'    => $this->get_user_id(),
				'gateway_id' => $this->get_gateway_id()
			] );
			if ( ! empty( $tokens ) ) {
				$token = current( $tokens );
				$this->set_id( $token->get_id() );
			}
		}

		return parent::save();
	}

}