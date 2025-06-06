<?php

namespace PaymentPlugins\PPCP\Blocks\Payments\Gateways;

use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings;

class CreditCardGateway extends AbstractGateway {

	protected $name = 'ppcp_card';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wc-ppcp-blocks-cards', 'build/credit-cards.js', [ 'wc-ppcp-blocks-commons' ] );
		wp_enqueue_style( 'wc-ppcp-blocks-styles' );
		wp_enqueue_style( 'wc-ppcp-style' );

		return [ 'wc-ppcp-blocks-cards' ];
	}

	public function get_payment_method_data() {
		$base_url        = \plugins_url( 'assets/images/payment-methods/', WC_PLUGIN_FILE );
		$card_gateway    = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\CreditCardGateway::class );
		$token           = $card_gateway->get_payment_method_token_instance();
		$format          = $token->get_payment_method_format( $card_gateway->get_option( 'payment_format', 'type_ending_in' ) );
		$email_detection = $this->get_setting( 'fastlane_flow' ) === 'email_detection';
		$show_signup     = $email_detection && \wc_string_to_bool( $this->get_setting( 'fastlane_signup', 'yes' ) );

		return array_merge(
			parent::get_payment_method_data(),
			[
				'fields'                => [
					'name'   => [
						'placeholder' => __( 'Cardholder name (optional)', 'pymntpl-paypal-woocommerce' )
					],
					'number' => [
						'placeholder' => __( 'Card number', 'pymntpl-paypal-woocommerce' )
					],
					'cvv'    => [
						'placeholder' => __( 'CVV', 'pymntpl-paypal-woocommerce' )
					],
					'expiry' => __( 'MM / YY', 'pymntpl-paypal-woocommerce' )
				],
				'i18n'                  => [
					'cardHolderLabel'        => __( 'Cardholder name', 'pymntpl-paypal-woocommerce' ),
					'cardNumberLabel'        => __( 'Card number', 'pymntpl-paypal-woocommerce' ),
					'cardExpiryLabel'        => __( 'Expiration date', 'pymntpl-paypal-woocommerce' ),
					'cardCvvLabel'           => __( 'Security code', 'pymntpl-paypal-woocommerce' ),
					'incomplete_form'        => __( 'The credit card form is incomplete.', 'pymntpl-paypal-woocommerce' ),
					'error_codes'            => [
						'INVALID_NUMBER' => __( 'Your card number is incomplete', 'pymntpl-paypal-woocommerce' ),
						'INVALID_EXPIRY' => __( 'Your card\'s expiration date is incomplete.', 'pymntpl-paypal-woocommerce' ),
						'INVALID_CVV'    => __( 'Your card\'s security code is incomplete.', 'pymntpl-paypal-woocommerce' )
					],
					'cancel'                 => __( 'Cancel', 'pymntpl-paypal-woocommerce' ),
					'change'                 => __( 'Change', 'pymntpl-paypal-woocommerce' ),
					'fastlane_signup'        => __( 'Sign up for', 'pymntpl-paypal-woocommerce' ),
					'continue'               => __( 'Continue', 'pymntpl-paypal-woocommerce' ),
					'cardsNotAvailableAdmin' => __( 'Advanced card processing is not available. Login to developer.paypal.com > Apps & Credentials and click your application. Under "Features" check "Advanced Card Processing".', 'pymntpl-paypal-woocommerce' ),
					'cardsNotAvailable'      => __( 'Credit card processing is not available. Please use another payment method.', 'pymntpl-paypal-woocommerce' )
				],
				'styles'                => [
					'input'          => [
						'padding'       => '0.75rem',
						'border'        => '1px solid #e6e6e6',
						'box-shadow'    => '0px 1px 1px rgba(0, 0, 0, 0.03), 0px 3px 6px rgba(0, 0, 0, 0.02)',
						'border-radius' => '5px',
						'transition'    => 'background 0.15s ease, border 0.15s ease, box-shadow 0.15s ease, color 0.15s ease'
					],
					':focus'         => [
						'border'     => '1px solid #0570de',
						'box-shadow' => '0px 1px 1px rgba(0, 0, 0, 0.03), 0px 3px 6px rgba(0, 0, 0, 0.02), 0 0 0 3px hsla(210, 96%, 45%, 25%), 0 1px 1px 0 rgba(0, 0, 0, 0.08)'
					],
					'.invalid'       => [
						'color'      => '#df1b41',
						'border'     => '1px solid #df1b41',
						'box-shadow' => '0px 1px 1px rgba(0, 0, 0, 0.03), 0px 3px 6px rgba(0, 0, 0, 0.02), 0 0 0 1px #df1b41'
					],
					':focus.invalid' => [
						'box-shadow' => '0px 1px 1px rgba(0, 0, 0, 0.03), 0px 3px 6px rgba(0, 0, 0, 0.02), 0 0 0 1px #df1b41'
					]
				],
				'cardHolderNameEnabled' => wc_string_to_bool( $this->get_setting( 'cardholder_name', 'yes' ) ),
				'icon'                  => $this->get_payment_method_icons(),
				'icons'                 => [
					'amex'       => $base_url . 'amex.svg',
					'diners'     => $base_url . 'diners.svg',
					'discover'   => $base_url . 'discover.svg',
					'jcb'        => $base_url . 'jcb.svg',
					'maestro'    => $base_url . 'maestro.svg',
					'mastercard' => $base_url . 'mastercard.svg',
					'visa'       => $base_url . 'visa.svg'
				],
				'payment_format'        => $format,
				'fastlane_logo'         => $card_gateway->assets->assets_url( 'assets/img/fastlane.svg' ),
				'showSignup'            => $show_signup,
				'showSaveOption'        => $card_gateway->show_card_save_checkbox()
			]
		);
	}

	public function get_payment_method_icons() {
		return [
			'id'  => 'CreditCards',
			'src' => $this->get_setting( 'card_icons_url', '' ),
			'alt' => 'Credit Cards'
		];
	}

}