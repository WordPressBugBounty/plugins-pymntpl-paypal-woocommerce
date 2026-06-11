<?php

namespace PaymentPlugins\PPCP\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;
use PaymentPlugins\WooCommerce\PPCP\PaymentMethodRegistry;

class ReCaptchaBlock implements IntegrationInterface {

	private $assets;

	private $payment_method_registry;

	private $settings = [];

	public function __construct( AssetsApi $assets, PaymentMethodRegistry $payment_method_registry ) {
		$this->assets                  = $assets;
		$this->payment_method_registry = $payment_method_registry;
	}

	public function get_name() {
		return 'ppcpReCaptcha';
	}

	public function initialize() {
		$this->settings = get_option( 'woocommerce_ppcp_advanced_settings', [ 'recaptcha_config' => [] ] );
	}

	public function get_script_handles() {
		if ( ! $this->is_enabled() || $this->get_setting( 'v3_site_key', '' ) === '' ) {

			return [];
		}
		$this->assets->register_script( 'wc-ppcp-blocks-recaptcha', 'build/recaptcha-block.js' );

		return [ 'wc-ppcp-recaptcha-external', 'wc-ppcp-blocks-recaptcha' ];
	}

	public function get_editor_script_handles() {
		return [];
	}

	public function get_script_data() {
		return [
			'siteKey'        => $this->get_setting( 'v3_site_key' ),
			'paymentMethods' => array_keys( $this->payment_method_registry->get_active_integrations() ),
		];
	}

	private function get_setting( $key, $default = null ) {
		return isset( $this->settings['recaptcha_config'][ $key ] ) ? $this->settings['recaptcha_config'][ $key ] : $default;
	}

	private function is_enabled() {
		return \wc_string_to_bool( $this->get_setting( 'enabled', 'no' ) );
	}

}