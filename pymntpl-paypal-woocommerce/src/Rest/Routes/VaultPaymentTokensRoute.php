<?php

namespace PaymentPlugins\WooCommerce\PPCP\Rest\Routes;

use PaymentPlugins\PayPalSDK\PaymentSource;
use PaymentPlugins\PayPalSDK\PaymentToken;
use PaymentPlugins\WooCommerce\PPCP\Customer;
use PaymentPlugins\WooCommerce\PPCP\WPPayPalClient;

class VaultPaymentTokensRoute extends AbstractRoute {

	/**
	 * @var \PaymentPlugins\WooCommerce\PPCP\WPPayPalClient
	 */
	private $client;

	public function __construct( WPPayPalClient $client ) {
		$this->client = $client;
	}

	public function get_path() {
		return 'vault/payment-tokens';
	}

	public function get_routes() {
		return [
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'handle_request' ],
				'args'     => [
					'setup_token' => [
						'required' => true
					]
				]
			]
		];
	}

	public function handle_post_request( \WP_REST_Request $request ) {
		$setup_token_id = $request->get_param( 'setup_token' );
		$is_fastlane    = $request->get_param( 'fastlane' ) === true;

		if ( ! $setup_token_id ) {
			throw new \Exception( __( 'Invalid setup token ID.', 'pymntpl-paypal-woocommerce' ) );
		}

		if ( ! $is_fastlane ) {
			$response = $this->client->setupTokens->retrieve( $setup_token_id );

			if ( ! is_wp_error( $response ) ) {
				$request = new PaymentToken();
				$request->setPaymentSource( new PaymentSource( [
					'token' => [
						'id'   => $setup_token_id,
						'type' => 'SETUP_TOKEN'
					]
				] ) );
				$request->setCustomer( new \PaymentPlugins\PayPalSDK\Customer( [
					'id' => $response->getCustomer()->getId()
				] ) );

				$response = $this->client->paymentTokensV3->create( $request );
			}
		} else {
			$request = new PaymentToken();
			$request->setPaymentSource( new PaymentSource( [
				'token' => [
					'id'   => $setup_token_id,
					'type' => 'NONCE'
				]
			] ) );
			if ( is_user_logged_in() ) {
				$customer = Customer::instance( get_current_user_id() );
				if ( $customer->has_id() ) {
					$request->setCustomer( new \PaymentPlugins\PayPalSDK\Customer( [
						'id' => $customer->get_id()
					] ) );
				}
			}
			$response = $this->client->paymentTokensV3->create( $request );
		}

		return $response;
	}


}