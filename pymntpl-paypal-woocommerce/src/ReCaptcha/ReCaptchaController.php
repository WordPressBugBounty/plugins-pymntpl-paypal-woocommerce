<?php

namespace PaymentPlugins\WooCommerce\PPCP\ReCaptcha;

use PaymentPlugins\WooCommerce\PPCP\Assets\AssetDataApi;
use PaymentPlugins\WooCommerce\PPCP\PaymentMethodRegistry;

class ReCaptchaController {

	private $settings = [];

	private $payment_method_registry;

	public function __construct( PaymentMethodRegistry $payment_method_registry ) {
		$this->payment_method_registry = $payment_method_registry;
		$this->settings                = get_option( 'woocommerce_ppcp_advanced_settings', [ 'recaptcha_config' => [] ] );
	}

	public function initialize() {
		add_filter( 'wc_ppcp_script_dependencies', function ( array $handles ) {
			return $this->get_script_handles( $handles );
		}, 10 );
		add_action( 'wc_ppcp_validate_checkout_fields', function ( \WP_REST_Request $request ) {
			$this->validate_checkout( $request );
		}, 10 );
		add_action( 'wc_ppcp_add_script_data', function ( AssetDataApi $data_api ) {
			$this->add_script_data( $data_api );
		}, 10 );

		$this->register_scripts();
	}

	private function get_setting( $key, $default = null ) {
		return isset( $this->settings['recaptcha_config'][ $key ] ) ? $this->settings['recaptcha_config'][ $key ] : $default;
	}

	private function register_scripts() {
		wp_register_script(
			'wc-ppcp-recaptcha-external',
			add_query_arg( [ 'render' => $this->get_setting( 'v3_site_key' ) ], 'https://www.google.com/recaptcha/api.js'
			)
		);
	}

	private function get_script_handles( array $handles ): array {
		if ( empty( $handles ) ) {
			return $handles;
		}
		if ( ! $this->is_enabled() || $this->get_setting( 'v3_site_key', '' ) === '' ) {
			return $handles;
		}
		if ( is_user_logged_in() && ! \wc_string_to_bool( $this->get_setting( 'verify_logged_in', 'no' ) ) ) {
			return $handles;
		}
		$handles[] = 'wc-ppcp-recaptcha';

		return $handles;
	}

	private function is_enabled() {
		return \wc_string_to_bool( $this->get_setting( 'enabled', 'no' ) );
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function validate_checkout( \WP_REST_Request $request ) {
		if ( ! $this->is_enabled() ) {
			return;
		}
		if ( ! $this->payment_method_registry->has_payment_method( $request->get_param( 'payment_method' ) ) ) {
			return;
		}
		if ( is_user_logged_in() && ! \wc_string_to_bool( $this->get_setting( 'verify_logged_in', 'no' ) ) ) {
			return;
		}
		$token = sanitize_text_field( $request->get_param( 'ppcp_recaptcha_response' ) ?? '' );
		$this->verify_recaptcha( $token );
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	private function verify_recaptcha( string $token = '' ) {
		if ( empty( $token ) ) {
			$token = isset( $_POST['ppcp_recaptcha_response'] ) ? sanitize_text_field( wp_unslash( $_POST['ppcp_recaptcha_response'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
		if ( empty( $token ) ) {
			throw new \Exception( __( 'reCAPTCHA verification failed. Please try again.', 'pymntpl-paypal-woocommerce' ) );
		}

		$response = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', [
			'body' => [
				'secret'   => $this->get_setting( 'v3_secret_key', '' ),
				'response' => $token,
			],
		] );

		if ( is_wp_error( $response ) ) {
			throw new \Exception( __( 'reCAPTCHA verification request failed. Please try again.', 'pymntpl-paypal-woocommerce' ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );
		if ( empty( $body ) || ! $body->success ) {
			throw new \Exception( __( 'reCAPTCHA verification failed. Please try again.', 'pymntpl-paypal-woocommerce' ) );
		}

		$min_score = (float) $this->get_setting( 'v3_score', 0.5 );
		if ( (float) $body->score < $min_score ) {
			throw new \Exception( __( 'reCAPTCHA verification failed. Please try again.', 'pymntpl-paypal-woocommerce' ) );
		}
	}

	/**
	 * @param AssetDataApi $data_api
	 *
	 * @return void
	 */
	private function add_script_data( AssetDataApi $data_api ) {
		$data_api->add(
			'recaptcha',
			[
				'site_key'       => $this->get_setting( 'v3_site_key' ),
				'i18n'           => [
					'error' => __( 'reCAPTCHA error: %s', 'pymntpl-paypal-woocommerce' )
				],
				'paymentMethods' => array_keys( $this->payment_method_registry->get_active_integrations() )
			]
		);
	}

}