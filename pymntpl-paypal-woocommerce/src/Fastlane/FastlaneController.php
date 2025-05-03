<?php

namespace PaymentPlugins\WooCommerce\PPCP\Fastlane;

use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\CreditCardGateway;
use PaymentPlugins\WooCommerce\PPCP\TemplateLoader;

class FastlaneController {

	private $client;

	public function __construct( \PaymentPlugins\PayPalSDK\PayPalClient $client ) {
		$this->client = $client;
	}

	public function initialize() {
		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ] );
		add_action( 'wc_ppcp_paypal_query_params', [ $this, 'add_paypal_query_params' ], 10, 2 );
		add_action( 'woocommerce_checkout_fields', [ $this, 'update_checkout_fields_priority' ] );
		add_action( 'wc_ppcp_before_card_container', [ $this, 'render_before_card_container' ] );
	}

	private function is_fastlane_enabled() {
		$card_gateway = wc_ppcp_get_container()->get( CreditCardGateway::class );

		return wc_string_to_bool( $card_gateway->enabled ) && $card_gateway->is_fastlane_enabled();
	}

	private function email_has_priority() {
		$card_gateway = wc_ppcp_get_container()->get( CreditCardGateway::class );

		return \wc_string_to_bool( $card_gateway->get_option( 'fastlane_email_top', 'yes' ) );
	}

	/**
	 * @param \PaymentPlugins\WooCommerce\PPCP\PayPalQueryParams $query_params
	 * @param \PaymentPlugins\WooCommerce\PPCP\ContextHandler    $context_handler
	 *
	 * @return void
	 */
	public function add_paypal_query_params( $query_params, $context_handler ) {
		// Only proceed if on checkout page and Fastlane is enabled
		if ( ! $context_handler->is_checkout() || ! $this->is_fastlane_enabled() ) {
			return;
		}

		// Try to get cached token
		$access_token = get_transient( 'wc_ppcp_client_token' );

		// If no cached token, request a new one
		if ( ! $access_token ) {
			$response = $this->client->auth->create( null, [
				'grant_type'    => 'client_credentials',
				'response_type' => 'client_token',
				'intent'        => 'sdk_init',
				'domains'       => [ 'localhost' ]
			] );

			if ( ! is_wp_error( $response ) ) {
				// Calculate expiration (token duration minus 100 seconds buffer)
				$expiration = absint( $response->expires_in ) - 100;
				set_transient( 'wc_ppcp_client_token', $response->access_token, $expiration );
				$access_token = $response->access_token;
			} else {
				return; // Exit if error occurred
			}
		}

		// Add Fastlane component and token to params
		$query_params->add_param( 'components', array_merge( $query_params->components, [ 'fastlane' ] ) );
		$query_params->add_param( 'data-sdk-client-token', $access_token );
	}

	public function wp_enqueue_scripts() {
		if ( ( is_checkout() && ! is_order_received_page() && ! is_checkout_pay_page() ) && $this->is_fastlane_enabled() ) {
			/**
			 * @var TemplateLoader $templates
			 */
			$templates    = wc_ppcp_get_container()->get( TemplateLoader::class );
			$card_gateway = wc_ppcp_get_container()->get( CreditCardGateway::class );
			$token        = $card_gateway->get_payment_method_token_instance();
			$format       = $token->get_payment_method_format( $card_gateway->get_option( 'payment_format', 'type_ending_in' ) );
			$base_url     = \plugins_url( 'assets/images/payment-methods/', WC_PLUGIN_FILE );

			$data = [
				'html'              => [
					'modal'          => $templates->load_template_html( 'fastlane/modal.php' ),
					'tokenized_card' => $templates->load_template_html( 'fastlane/tokenized-card.php' ),
				],
				'payment_format'    => $format,
				'fastlane_flow'     => $card_gateway->get_option( 'fastlane_flow', 'express_button' ),
				'fastlane_pageload' => \wc_string_to_bool( $card_gateway->get_option( 'fastlane_pageload', 'no' ) ),
				'icons'             => [
					'amex'       => $base_url . 'amex.svg',
					'diners'     => $base_url . 'diners.svg',
					'discover'   => $base_url . 'discover.svg',
					'jcb'        => $base_url . 'jcb.svg',
					'maestro'    => $base_url . 'maestro.svg',
					'mastercard' => $base_url . 'mastercard.svg',
					'visa'       => $base_url . 'visa.svg'
				],
				'i18n'              => [
					'email_empty'   => __( 'Please provide an email address before using Fastlane.', 'pymntpl-paypal-woocommerce' ),
					'email_invalid' => __( 'Please enter a valid email address before using Fastlane.', 'pymntpl-paypal-woocommerce' )
				]
			];

			wp_enqueue_script( 'wc-ppcp-fastlane-checkout' );
			wp_localize_script( 'wc-ppcp-fastlane-checkout', 'wc_ppcp_fastlane_params', $data );
		}
	}

	/**
	 * @param array $fields
	 *
	 * @return void
	 */
	public function update_checkout_fields_priority( $fields ) {
		if ( $this->is_fastlane_enabled() ) {
			if ( $this->email_has_priority() ) {
				if ( isset( $fields['billing']['billing_email'] ) ) {
					$fields['billing']['billing_email']['priority'] = 1;
				}
			}
		}


		return $fields;
	}

	/**
	 * @param CreditCardGateway $gateway
	 *
	 * @return void
	 */
	public function render_before_card_container( $gateway ) {
		if ( $gateway->is_fastlane_enabled() && $gateway->get_option( 'fastlane_flow' ) === 'email_detection' ) {
			$show_signup = \wc_string_to_bool( $gateway->get_option( 'fastlane_signup', 'yes' ) );
			if ( $show_signup && ! is_add_payment_method_page() && ! is_checkout_pay_page() ) {
				$logo_url = $gateway->assets->assets_url( 'assets/img/fastlane.svg' );
				wc_ppcp_load_template( 'fastlane/signup-link.php', [ 'logo_url' => $logo_url ] );
			}
		}
	}

}