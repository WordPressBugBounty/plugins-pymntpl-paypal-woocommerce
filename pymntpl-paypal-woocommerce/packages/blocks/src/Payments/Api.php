<?php


namespace PaymentPlugins\PPCP\Blocks\Payments;


use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use PaymentPlugins\PPCP\Blocks\Payments\Gateways\ApplePayGateway;
use PaymentPlugins\PPCP\Blocks\Payments\Gateways\CreditCardGateway;
use PaymentPlugins\PPCP\Blocks\Payments\Gateways\FastlaneGateway;
use PaymentPlugins\PPCP\Blocks\Payments\Gateways\GooglePayGateway;
use PaymentPlugins\PPCP\Blocks\Payments\Gateways\PayPalGateway;
use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\APISettings;
use PaymentPlugins\WooCommerce\PPCP\Container\Container;
use PaymentPlugins\WooCommerce\PPCP\Messages;
use PaymentPlugins\WooCommerce\PPCP\Rest\RestController;

class Api {

	private $container;

	private $api_settings;

	private $rest_controller;

	private $data_api;

	private $payment_gateways = [];

	public function __construct( Container $container, APISettings $api_settings, RestController $rest_controller, AssetDataRegistry $data_api ) {
		$this->container       = $container;
		$this->api_settings    = $api_settings;
		$this->rest_controller = $rest_controller;
		$this->data_api        = $data_api;
		$this->initialize();
	}

	public function initialize() {
		add_filter( 'woocommerce_blocks_payment_method_type_registration', [ $this, 'register_payment_gateways' ] );
		add_action( 'woocommerce_blocks_checkout_enqueue_data', [ $this, 'add_checkout_payment_method_data' ] );
		add_action( 'woocommerce_blocks_cart_enqueue_data', [ $this, 'add_cart_payment_method_data' ] );
		add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after', [ $this, 'dequeue_cart_scripts' ] );
		add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_before', [ $this, 'dequeue_cart_scripts' ] );
	}

	public function register_payment_gateways( PaymentMethodRegistry $registry ) {
		$this->register( $this->container->get( PayPalGateway::class ), $registry );
		$this->register( $this->container->get( CreditCardGateway::class ), $registry );
		$this->register( $this->container->get( GooglePayGateway::class ), $registry );
		$this->register( $this->container->get( ApplePayGateway::class ), $registry );
		$this->register( $this->container->get( FastlaneGateway::class ), $registry );
	}

	private function register( $instance, PaymentMethodRegistry $registry ) {
		$registry->register( $instance );
		$this->payment_gateways[ $instance->get_name() ] = $instance;
	}

	public function get_payment_gateways() {
		return $this->payment_gateways;
	}

	public function add_cart_payment_method_data() {
		$this->add_payment_method_data( 'cart' );
	}

	public function add_checkout_payment_method_data() {
		$this->add_payment_method_data( 'checkout' );
	}

	public function add_payment_method_data( $context ) {
		if ( ! $this->data_api->exists( 'ppcpGeneralData' ) ) {
			$admin_only = false;
			if ( wc_ppcp_get_container()->get( APISettings::class )->is_admin_only_mode() ) {
				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					$admin_only = true;
				}
			}
			$card_icon_url = \plugins_url( 'assets/images/payment-methods/', WC_PLUGIN_FILE );
			$data          = [
				'clientId'      => $this->api_settings->get_client_id(),
				'environment'   => $this->api_settings->get_environment(),
				'context'       => $context,
				'isAdmin'       => current_user_can( 'manage_woocommerce' ),
				'adminOnly'     => $admin_only,
				'blocksVersion' => \Automattic\WooCommerce\Blocks\Package::get_version(),
				'i18n'          => wc_ppcp_get_container()->get( Messages::class )->get_messages(),
				'cardIcons'     => [
					'amex'       => $card_icon_url . 'amex.svg',
					'diners'     => $card_icon_url . 'diners.svg',
					'discover'   => $card_icon_url . 'discover.svg',
					'jcb'        => $card_icon_url . 'jcb.svg',
					'maestro'    => $card_icon_url . 'maestro.svg',
					'mastercard' => $card_icon_url . 'mastercard.svg',
					'visa'       => $card_icon_url . 'visa.svg'
				],
			];
			$this->data_api->add( 'ppcpGeneralData', $this->rest_controller->add_asset_data( $data ) );
		}
	}

	public function dequeue_cart_scripts() {
		wp_dequeue_script( 'wc-ppcp-minicart-gateway' );
	}

	private function is_rest_request() {
		if ( method_exists( WC(), 'is_rest_api_request' ) ) {
			return WC()->is_rest_api_request();
		}

		return false;
	}

}