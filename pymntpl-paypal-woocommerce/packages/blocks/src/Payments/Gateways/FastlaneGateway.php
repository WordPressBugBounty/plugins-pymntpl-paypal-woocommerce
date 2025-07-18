<?php

namespace PaymentPlugins\PPCP\Blocks\Payments\Gateways;

use PaymentPlugins\PPCP\Blocks\Utils\ActionUtils;
use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\APISettings;
use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;

class FastlaneGateway extends AbstractGateway {

	public $name = 'ppcp_fastlane';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wc-ppcp-blocks-fastlane-express', 'build/fastlane-express.js', [ 'wc-ppcp-blocks-commons' ] );

		return [ 'wc-ppcp-blocks-fastlane-express' ];
	}

	public function is_active() {
		return \wc_string_to_bool( $this->get_setting( 'enabled' ) ) && \wc_string_to_bool( $this->get_setting( 'fastlane_enabled', 'no' ) )
		       && $this->get_setting( 'fastlane_flow' ) === 'express_button';
	}

	public function initialize() {
		$this->settings = \get_option( "woocommerce_ppcp_card_settings", [] );

		add_filter( 'wc_ppcp_blocks_get_extended_data', [ $this, 'get_schema_extended_data' ] );
		add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before', [ $this, 'enqueue_checkout_scripts' ] );
	}

	public function enqueue_checkout_scripts() {
		if ( \wc_string_to_bool( $this->get_setting( 'enabled' ) ) ) {
			if ( \wc_string_to_bool( $this->get_setting( 'fastlane_enabled', 'no' ) ) ) {
				if ( ! wc_ppcp_get_container()->get( APISettings::class )->is_admin_only_mode() ) {
					wp_enqueue_script( 'wc-ppcp-blocks-fastlane' );
				}
			}
		}
	}

	public function get_schema_extended_data( $data ) {
		$data['fastlane'] = $this->get_payment_method_data();

		return $data;
	}

	public function get_payment_method_data() {
		$data = [
			'features'              => $this->get_supported_features(),
			'icon_url'              => wc_ppcp_get_container()->get( AssetsApi::class )->assets_url( 'assets/img/fastlane.svg' ),
			'fastlane_flow'         => $this->get_setting( 'fastlane_flow', 'email_detection' ),
			'fastlane_pageload'     => \wc_string_to_bool( $this->get_setting( 'fastlane_pageload', 'no' ) ),
			'iconEnabled'           => \wc_string_to_bool( $this->get_setting( 'fastlane_icon_enabled', 'yes' ) ),
			'emailDetectionEnabled' => $this->get_setting( 'fastlane_flow', 'email_detection' ) === 'email_detection',
			'i18n'                  => [
				'cancel'        => __( 'Cancel', 'pymntpl-paypal-woocommerce' ),
				'change'        => __( 'Change', 'pymntpl-paypal-woocommerce' ),
				'continue'      => __( 'Continue', 'pymntpl-paypal-woocommerce' ),
				'email_empty'   => __( 'Please provide an email address before using Fastlane.', 'pymntpl-paypal-woocommerce' ),
				'email_invalid' => __( 'Please enter a valid email address before using Fastlane.', 'pymntpl-paypal-woocommerce' )
			]
		];

		return ActionUtils::apply_payment_data_filter( $data, $this );
	}

}