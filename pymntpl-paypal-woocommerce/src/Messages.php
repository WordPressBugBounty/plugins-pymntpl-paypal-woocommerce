<?php


namespace PaymentPlugins\WooCommerce\PPCP;


class Messages {

	private $messages = [];

	private $error_messages;

	public function __construct() {
		$this->initialize();
	}

	private function initialize() {
		add_filter( 'wc_ppcp_api_request_error_message', [ $this, 'get_error_message' ], 10, 2 );
		$this->messages = [
			'terms'                         => __( 'Please check the terms and conditions before proceeding.', 'pymntpl-paypal-woocommerce' ),
			'invalid_client_id'             => __( 'Invalid PayPal client ID. Please check your API Settings.', 'pymntpl-paypal-woocommerce' ),
			'invalid_currency'              => __( 'PayPal does not support currency %. Please use a supported currency.', 'pymntpl-paypal-woocommerce' ),
			'order_button_click'            => __( 'Please click the PayPal button before placing your order.', 'pymntpl-paypal-woocommerce' ),
			'order_missing_address'         => __( 'Please fill out all billing and shipping fields before clicking PayPal.', 'pymntpl-paypal-woocommerce' ),
			'order_missing_billing_address' => __( 'Please fill out all billing fields before clicking PayPal.', 'pymntpl-paypal-woocommerce' ),
			'cancel'                        => __( 'Cancel', 'pymntpl-paypal-woocommerce' ),
			'Y_N_NO'                        => __( '3DS authentication failed.', 'pymntpl-paypal-woocommerce' ),
			'Y_R_NO'                        => __( '3DS authentication was rejected.', 'pymntpl-paypal-woocommerce' ),
			'Y_U_UNKNOWN'                   => __( 'Unable to complete 3DS authentication. Please try again.', 'pymntpl-paypal-woocommerce' ),
			'Y_U_NO'                        => __( 'Unable to complete 3DS authentication. Please try again.', 'pymntpl-paypal-woocommerce' ),
			'Y_C_UNKNOWN'                   => __( '3DS authentication challenge required but could not be completed. Please try again.', 'pymntpl-paypal-woocommerce' ),
			'Y__NO'                         => __( '3DS authentication could not be processed. Please try again.', 'pymntpl-paypal-woocommerce' ),
			'U__UNKNOWN'                    => __( '3DS system is currently unavailable. Please try again later.', 'pymntpl-paypal-woocommerce' ),
			'___UNKNOWN'                    => __( '3DS authentication status unknown. Please try again.', 'pymntpl-paypal-woocommerce' ),
		];

		$this->error_messages = [
			'REFUSED_MARK_REF_TXN_NOT_ENABLED' => __( 'This merchant account is not permitted to create Merchant Initiated Billing Agreements. Please contact PayPal support and request reference transaction access.', 'pymntpl-paypal-woocommerce' )
		];
	}

	public function get_message( $key, $default = '' ) {
		$messages = $this->get_messages();

		return $messages[ $key ] ?? $default;
	}

	public function get_messages() {
		return apply_filters( 'wc_ppcp_get_messages', $this->messages );
	}

	/**
	 * @param string                                           $msg
	 * @param \PaymentPlugins\PayPalSDK\Exception\ApiException $error
	 *
	 * @return void
	 */
	public function get_error_message( $msg, $error ) {
		if ( $error && isset( $this->error_messages[ $error->getErrorCode() ] ) ) {
			$msg = $this->error_messages[ $error->getErrorCode() ];
		}

		return $msg;
	}

}