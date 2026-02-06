<?php

namespace PaymentPlugins\PPCP\Blocks\Payments\Gateways;

use PaymentPlugins\PPCP\Blocks\Utils\ActionUtils;

class ApplePayGateway extends AbstractGateway {

	protected $name = 'ppcp_applepay';

	public function get_payment_method_script_handles() {
		$this->assets_api->register_script( 'wc-ppcp-blocks-applepay', 'build/applepay.js' );

		return [ 'wc-ppcp-blocks-applepay' ];
	}

	public function get_payment_method_data() {
		$gateway = $this->get_payment_method();
		$token   = $gateway->get_payment_method_token_instance();
		$format  = $token->get_payment_method_format( $gateway->get_option( 'payment_format', 'type_ending_in' ) );
		$data    = [
			'sections'            => $this->get_setting( 'sections', [] ),
			'button'              => [
				'style'  => $this->get_setting( 'button_style', 'black' ),
				'type'   => $this->get_setting( 'button_type', 'plain' ),
				'radius' => $this->get_setting( 'button_radius', '4' ) . 'px',
				'height' => $this->get_setting( 'button_height', '40' ) . 'px',
			],
			'displayName'         => $this->get_setting( 'display_name', get_bloginfo( 'name' ) ),
			'editorIcons'         => array(
				'white' => $this->assets_api->assets_url( 'assets/img/applepay_button_white.svg' ),
				'black' => $this->assets_api->assets_url( 'assets/img/applepay_button_black.svg' )
			),
			'paymentFormat'       => $format,
			'supportedCurrencies' => $this->get_payment_method()->get_supported_currencies(),
		];

		$data = array_merge( parent::get_payment_method_data(), $data );

		return ActionUtils::apply_payment_data_filter(
			$data,
			$this
		);
	}

	public function get_payment_method_icons() {
		return [
			'id'  => 'ApplePay',
			'src' => $this->assets_api->assets_url( '../../assets/img/applepay/applepay.svg' ),
			'alt' => 'Apple Pay'
		];
	}
}