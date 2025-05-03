<?php

namespace PaymentPlugins\WooCommerce\PPCP\Rest\Routes;

use PaymentPlugins\PayPalSDK\OrderApplicationContext;
use PaymentPlugins\PayPalSDK\PaymentSource;
use PaymentPlugins\PayPalSDK\PaymentToken;
use PaymentPlugins\PayPalSDK\SetupToken;
use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings;
use PaymentPlugins\WooCommerce\PPCP\CheckoutValidator;
use PaymentPlugins\WooCommerce\PPCP\ContextHandler;
use PaymentPlugins\WooCommerce\PPCP\Customer;
use PaymentPlugins\WooCommerce\PPCP\Factories\CoreFactories;
use PaymentPlugins\WooCommerce\PPCP\Payments\PaymentGateways;
use PaymentPlugins\WooCommerce\PPCP\Traits\CheckoutRouteTrait;
use PaymentPlugins\WooCommerce\PPCP\WPPayPalClient;

class VaultSetupTokensRoute extends AbstractRoute {

	use CheckoutRouteTrait;

	private $client;

	private $factories;

	private $settings;

	private $validator;

	public function __construct( WPPayPalClient $client, CoreFactories $factories, AdvancedSettings $settings ) {
		$this->client    = $client;
		$this->factories = $factories;
		$this->settings  = $settings;
		$this->validator = new CheckoutValidator();
	}

	public function get_path() {
		return 'vault/setup-tokens';
	}

	public function get_routes() {
		return [
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'handle_request' ],
				'args'     => [
					'context'        => [
						'required' => true
					],
					'payment_method' => [
						'required' => true
					]
				]
			]
		];
	}

	public function handle_post_request( \WP_REST_Request $request ) {
		/**
		 * @var ContextHandler $context
		 */
		$context = wc_ppcp_get_container()->get( ContextHandler::class );
		$context->set_context( $request->get_param( 'context' ) );

		/**
		 * @var PaymentGateways $payment_gateways
		 */
		$payment_gateways = wc_ppcp_get_container()->get( PaymentGateways::class );

		$payment_method = $payment_gateways->get_gateway( $request->get_param( 'payment_method' ) );

		if ( ! $payment_method ) {
			throw new \Exception( __( 'Invalid payment method provided.', 'pymntpl-paypal-woocommerce' ) );
		}

		if ( $context->is_checkout() ) {
			if ( $this->is_checkout_validation_enabled( $request ) ) {
				$this->validator->validate_checkout( $request, false );
			}
			/**
			 * 3rd party code can use this action to perform custom validations.
			 *
			 * @since 1.0.31
			 */
			do_action( 'wc_ppcp_validate_checkout_fields', $request, $this->validator );

			if ( $this->validator->has_errors() ) {
				return $this->validator->get_failure_response();
			}
		}

		$this->factories->initialize( $payment_method );

		$request = $this->factories->setupToken->create( $context->get_context() );

		$result = $this->client->setupTokens->create( $request );

		return $result;
	}

}