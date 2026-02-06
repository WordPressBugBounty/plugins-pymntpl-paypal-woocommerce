<?php

namespace PaymentPlugins\WooCommerce\PPCP\Payments;

use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings;
use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\APISettings;
use PaymentPlugins\WooCommerce\PPCP\Assets\AssetDataApi;
use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;
use PaymentPlugins\WooCommerce\PPCP\Config;
use PaymentPlugins\WooCommerce\PPCP\Constants;
use PaymentPlugins\WooCommerce\PPCP\ContextHandler;
use PaymentPlugins\WooCommerce\PPCP\Main;
use PaymentPlugins\WooCommerce\PPCP\Messages;
use PaymentPlugins\WooCommerce\PPCP\PaymentMethodRegistry;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\ApplePayGateway;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\CreditCardGateway;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\GooglePayGateway;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\PayPalGateway;
use PaymentPlugins\WooCommerce\PPCP\Container\Container;
use PaymentPlugins\WooCommerce\PPCP\Utils;

class PaymentGateways {

	private $payment_method_registry;

	private $assets;

	private $asset_data;

	private $api_settings;

	/**
	 * @var \PaymentPlugins\WooCommerce\PPCP\ContextHandler
	 */
	private $context_handler;

	private $enqueued_handles = [];

	public function __construct( PaymentMethodRegistry $payment_method_registry, AssetsApi $assets, AssetDataApi $asset_data, APISettings $api_settings ) {
		$this->payment_method_registry = $payment_method_registry;
		$this->assets                  = $assets;
		$this->asset_data              = $asset_data;
		$this->api_settings            = $api_settings;
		add_action( 'woocommerce_ppcp_payment_methods_registration', [ $this, 'register_payment_methods' ], 10, 2 );
		add_filter( 'wc_ppcp_admin_script_dependencies', [ $this, 'add_admin_script_dependencies' ], 10, 2 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_payment_scripts' ] );
		add_action( 'wc_ppcp_add_script_data', [ $this, 'add_script_data' ] );
		add_action( 'wp_print_scripts', [ $this, 'add_minicart_scripts' ] );
		add_filter( 'woocommerce_available_payment_gateways', [ $this, 'get_available_payment_gateways' ] );
		add_action( 'woocommerce_before_mini_cart', [ $this, 'add_minicart_scripts' ] );
		add_action( 'wc_ppcp_admin_add_script_data', [ $this, 'add_admin_script_data' ] );

		$this->payment_method_registry->initialize();
	}

	public function set_page_context( ContextHandler $context_handler ) {
		$this->context_handler = $context_handler;
	}

	public function register_payment_methods( PaymentMethodRegistry $registry, Container $container ) {
		$this->payment_method_registry->register( $container->get( PayPalGateway::class ) );
		$this->payment_method_registry->register( $container->get( CreditCardGateway::class ) );
		$this->payment_method_registry->register( $container->get( GooglePayGateway::class ) );
		$this->payment_method_registry->register( $container->get( ApplePayGateway::class ) );
	}

	public function initialize_gateways( $gateways = [] ) {
		foreach ( $this->payment_method_registry->get_registered_integrations() as $payment_method ) {
			$gateways[] = $payment_method;
		}

		return $gateways;
	}

	public function has_gateway( $id ) {
		return $this->payment_method_registry->has_payment_method( $id );
	}

	/**
	 * @param $id
	 *
	 * @return AbstractGateway
	 */
	public function get_gateway( $id ) {
		return $this->payment_method_registry->get_payment_method( $id );
	}

	public function get_cart_payment_gateways() {
		return $this->payment_method_registry->get_cart_payment_gateways();
	}

	public function get_product_payment_gateways() {
		return $this->payment_method_registry->get_product_payment_gateways( Utils::get_queried_product_id() );
	}

	public function get_express_payment_gateways() {
		return $this->payment_method_registry->get_express_payment_gateways();
	}

	public function get_minicart_payment_gateways() {
		if ( $this->payment_method_registry->is_empty() ) {
			$this->payment_method_registry->initialize();
		}

		return $this->payment_method_registry->get_minicart_payment_gateways();
	}

	/**
	 * @param $payment_methods
	 *
	 * @return array
	 * @since 1.0.16
	 */
	public function filter_by_available( $payment_methods ) {
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();

		return array_filter( $payment_methods, function ( $payment_method ) use ( $available_gateways ) {
			return isset( $available_gateways[ $payment_method->id ] );
		} );
	}

	public function add_admin_script_dependencies( $handles, $section ) {
		return array_merge( $handles, $this->payment_method_registry->add_admin_script_dependencies( $section ) );
	}

	/**
	 * @param \PaymentPlugins\WooCommerce\PPCP\Assets\AssetDataApi $asset_data
	 *
	 * @return void
	 */
	public function add_admin_script_data( AssetDataApi $asset_data ) {
		foreach ( $this->payment_method_registry->get_registered_integrations() as $integration ) {
			if ( ! $asset_data->exists( $integration->id ) ) {
				$asset_data->add( $integration->id, $integration->get_admin_script_data() );
			}
		}
	}

	public function enqueue_payment_scripts() {
		$handles = [];

		if ( $this->payment_method_registry->is_empty() ) {
			$this->payment_method_registry->initialize();
		}

		$this->context_handler->initialize();

		if ( $this->context_handler->has_context( [
				'checkout',
				'add_payment_method',
				'order_pay'
			] ) || apply_filters( 'wc_ppcp_checkout_scripts', false ) ) {

			// if this is the checkout block, and there is no checkout shortcode, return.
			// order pay page doesn't use the checkout block so if this is the order pay page
			// the scripts needs to be loaded.
			if ( $this->context_handler->is_checkout_block()
			     && ! $this->context_handler->is_checkout_shortcode()
			     && ! $this->context_handler->is_order_pay() ) {
				return;
			}
			if ( $this->context_handler->is_add_payment_method() ) {
				$handles = $this->payment_method_registry->add_payment_method_script_dependencies();
			} else {
				$handles = $this->payment_method_registry->add_checkout_script_dependencies();
				$handles = array_merge( $handles, $this->payment_method_registry->add_express_checkout_script_dependencies() );
			}
		} elseif ( $this->context_handler->is_cart() ) {
			$handles = $this->payment_method_registry->add_cart_script_dependencies();
		} elseif ( $this->context_handler->is_product() ) {
			$handles = $this->payment_method_registry->add_product_script_dependencies();
		} elseif ( $this->context_handler->is_shop() ) {
			$handles = $this->payment_method_registry->add_shop_script_dependencies();
		}
		$this->enqueue_scripts( $handles );
	}

	private function enqueue_scripts( $handles ) {
		if ( ! is_admin() && ( $handles = apply_filters( 'wc_ppcp_script_dependencies', $handles, $this->assets, $this->context_handler, $this ) ) ) {
			// enqueue required script here
			foreach ( $handles as $handle ) {
				wp_enqueue_script( $handle );
				$this->enqueued_handles[] = $handle;
			}
			wp_enqueue_style( 'wc-ppcp-style' );
		}
	}

	/**
	 * @param AssetDataApi $asset_data
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function add_script_data( $asset_data ) {
		if ( empty( $this->enqueued_handles ) && ! $asset_data->has_data() ) {
			return;
		}
		$asset_data->add( 'version', wc_ppcp_get_container()->get( 'VERSION' ) );
		$asset_data->add( 'generalData', $this->get_general_asset_data() );
		$asset_data->add( 'errorMessages', wc_ppcp_get_container()->get( Messages::class )->get_messages() );
		$asset_data->add( 'i18n', [
			'locale'        => wp_json_encode( WC()->countries->get_country_locale() ),
			'locale_fields' => wp_json_encode( WC()->countries->get_country_locale_field_selectors() )
		] );
		$this->payment_method_registry->add_payment_method_data( $asset_data, $this->context_handler );
	}

	private function get_general_asset_data() {
		return apply_filters( 'wc_ppcp_general_asset_data', [
			'clientId'    => $this->api_settings->get_client_id(),
			'environment' => $this->api_settings->get_environment(),
			'partner_id'  => Constants::PARTNER_ID,
			'page'        => $this->context_handler->get_context(),
			'version'     => Main::container()->get( Config::class )->version(),
			'is_admin'    => current_user_can( 'manage_woocommerce' )
		] );
	}

	public function get_payment_method_registry() {
		return $this->payment_method_registry;
	}

	/**
	 * @param \WC_Payment_Gateway[] $gateways
	 *
	 * @return mixed
	 */
	public function get_available_payment_gateways( $gateways ) {
		/**
		 * @var APISettings $api_settings
		 */
		$api_settings = wc_ppcp_get_container()->get( APISettings::class );
		if ( $api_settings->is_admin_only_mode() && ( ! is_admin() && ! wc()->is_rest_api_request() && ! defined( 'DOING_CRON' ) ) ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				foreach ( $this->payment_method_registry->get_registered_integrations() as $integration ) {
					unset( $gateways[ $integration->id ] );
				}

				return $gateways;
			}
		}
		if ( is_add_payment_method_page() ) {
			/**
			 * @var AdvancedSettings $advanced_settings
			 */
			$advanced_settings = wc_ppcp_get_container()->get( AdvancedSettings::class );
			$vault_enabled     = $advanced_settings->is_vault_enabled();
			// Payment methods can only be added when Vault is enabled.
			if ( ! $vault_enabled ) {
				foreach ( $this->payment_method_registry->get_registered_integrations() as $integration ) {
					unset( $gateways[ $integration->id ] );
				}
			} else {
				// Until Apple Pay supports vaulting with no payment, it should be removed on add payment method page
				unset( $gateways['ppcp_applepay'] );
			}
		}
		if ( is_checkout_pay_page() ) {
			foreach ( $this->payment_method_registry->get_registered_integrations() as $integration ) {
				/**
				 * @var AbstractGateway $integration
				 */
				if ( ! $integration->is_section_enabled( 'order_pay' ) ) {
					unset( $gateways[ $integration->id ] );
				}
			}
		}

		return $gateways;
	}

	public function add_minicart_scripts() {
		if ( $this->context_handler ) {
			if ( ! $this->context_handler->is_checkout()
			     && ! $this->context_handler->is_cart()
			     && ! $this->context_handler->is_order_pay()
			     && ! $this->context_handler->is_order_received()
			     // don't load minicart on checkout block
			     && ! $this->context_handler->is_checkout_block() ) {
				$handles = $this->payment_method_registry->add_minicart_script_dependencies();
				if ( ! empty( $handles ) ) {
					$this->enqueue_scripts( $handles );
				}
			}
		}
	}

	/**
	 * @return \PaymentPlugins\WooCommerce\PPCP\Admin\Settings\APISettings
	 */
	public function get_api_settings() {
		return $this->api_settings;
	}

}