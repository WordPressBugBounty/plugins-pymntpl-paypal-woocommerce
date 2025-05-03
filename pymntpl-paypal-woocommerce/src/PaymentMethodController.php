<?php

namespace PaymentPlugins\WooCommerce\PPCP;

use PaymentPlugins\PayPalSDK\PaymentSource;
use PaymentPlugins\PayPalSDK\PaymentToken;
use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings;
use PaymentPlugins\WooCommerce\PPCP\Tokens\AbstractToken;
use PaymentPlugins\WooCommerce\PPCP\Tokens\CreditCardToken;
use PaymentPlugins\WooCommerce\PPCP\Tokens\PayPalToken;

class PaymentMethodController {

	private $client;

	private $log;

	private $classnames;

	public function __construct( WPPayPalClient $client, Logger $log ) {
		$this->client     = $client;
		$this->log        = $log;
		$this->classnames = [
			'PaymentPlugins\WooCommerce\PPCP\Tokens\PayPalToken',
			'PaymentPlugins\WooCommerce\PPCP\Tokens\CreditCardToken'
		];
	}

	public function initialize() {
		add_filter( 'woocommerce_payment_token_class', [ $this, 'get_payment_token_class' ] );
		add_filter( 'woocommerce_payment_methods_list_item', [ $this, 'get_payment_method_list_item' ], 20, 2 );
		add_action( 'woocommerce_payment_token_deleted', [ $this, 'handle_payment_token_deleted' ], 10, 2 );
		add_filter( 'woocommerce_get_customer_payment_tokens', [ $this, 'get_customer_payment_tokens' ], 10 );
	}

	public function get_payment_token_class( $type ) {
		foreach ( $this->classnames as $classname ) {
			try {
				$reflection_class = new \ReflectionClass( $classname );
				$properties       = $reflection_class->getDefaultProperties();
				if ( isset( $properties['type'] ) ) {
					if ( 'WC_Payment_Token_' . $properties['type'] === $type ) {
						$type = $classname;
						break;
					}
				}
			} catch ( \ReflectionException $e ) {
				$this->log->error( sprintf( 'Error establishing ReflectionClass. Classname: %s', $classname ) );
			}
		}

		return $type;
	}

	public function get_payment_method_list_item( $item, $payment_token ) {
		if ( $payment_token instanceof AbstractToken ) {
			$item = $payment_token->get_payment_method_item( $item );
		}

		return $item;
	}

	/**
	 * @param int               $id
	 * @param \WC_Payment_Token $token
	 *
	 * @return void
	 */
	public function handle_payment_token_deleted( $id, $token ) {
		if ( ! is_account_page() ) {
			return;
		}
		if ( $token instanceof AbstractToken ) {
			if ( $token->get_token() ) {
				$result = $this->client->environment( $token->get_environment() )->paymentTokensV3->delete( $token->get_token() );
				if ( is_wp_error( $result ) ) {
					$this->log->error(
						sprintf( 'Error deleting payment token %s. Reason: %s', $token->get_token(), $result->get_error_message() ) );
				} else {
					$this->log->info( sprintf( 'Payment token %s deleted.', $token->get_token() ) );
				}
			}
		}
	}

	/**
	 * @param string         $setup_token_id
	 * @param \WC_Order|null $order
	 *
	 * @return PaymentToken
	 */
	public function create_payment_token_from_setup_token( $setup_token_id, $order = null ) {
		if ( $order ) {
			$this->client->orderMode( $order );
		}
		$setup_token = $this->client->setupTokens->retrieve( $setup_token_id );

		if ( ! is_wp_error( $setup_token ) ) {
			$request = new PaymentToken();
			$request->setPaymentSource( new PaymentSource( [
				'token' => [
					'id'   => $setup_token_id,
					'type' => 'SETUP_TOKEN'
				]
			] ) );
			$request->setCustomer( new \PaymentPlugins\PayPalSDK\Customer( [
				'id' => $setup_token->getCustomer()->getId()
			] ) );

			return $this->client->paymentTokensV3->create( $request );
		}
	}

	public function get_customer_payment_tokens( $tokens ) {
		/**
		 * @var AdvancedSettings $settings
		 */
		$settings = wc_ppcp_get_container()->get( AdvancedSettings::class );
		if ( ! $settings->is_vault_enabled() ) {
			$tokens = \array_filter( $tokens, function ( $token ) {
				return ! $token instanceof AbstractToken;
			} );
		}

		return $tokens;
	}

}