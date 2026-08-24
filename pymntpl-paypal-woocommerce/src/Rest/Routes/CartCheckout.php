<?php


namespace PaymentPlugins\WooCommerce\PPCP\Rest\Routes;


use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Utils;

/**
 * Route that handles requests to process the checkout.
 */
class CartCheckout extends AbstractCart {

	/**
	 * @var AbstractGateway
	 */
	public $payment_method;

	/**
	 * @var \WP_REST_Request
	 */
	public $request;

	private function initialize() {
		add_action( 'woocommerce_after_checkout_validation', [ $this, 'handle_checkout_validation' ], 10, 2 );
	}

	public function get_path() {
		return 'cart/checkout';
	}

	public function get_routes() {
		return [
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'handle_request' ],
				'args'     => [
					'payment_method' => [
						'required'          => true,
						'validate_callback' => [ $this->validator, 'validate_payment_method' ]
					]
				]
			]
		];
	}

	public function handle_post_request( \WP_REST_Request $request ) {
		$this->request = $request;

		$this->initialize();

		$this->prepare_request_params( $request );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST = array_merge( $_POST, $request->get_json_params() );

		$this->payment_method = $this->get_payment_method_from_request( $request );
		$this->set_required_fields();

		// set the checkout nonce so no exceptions are thrown.
		$_REQUEST['_wpnonce'] = $_POST['_wpnonce'] = wp_create_nonce( 'woocommerce-process_checkout' );

		$this->add_checkout_block_filters();

		$this->remove_billing_address_requirements();

		WC()->checkout()->process_checkout();
	}

	/**
	 * PayPal doesn't return a billing address for the customer, and when shipping isn't required,
	 * there's no shipping address to backfill billing with (see CheckoutFieldPopulator.populateShipping()'s
	 * billing backfill, which only runs when shipping data is present). Relax the billing address
	 * requirement for this express (cart/mini-cart/product page) request so it doesn't get sent back
	 * to the checkout page over billing details PayPal never provided.
	 *
	 * @since 2.0.25
	 */
	private function remove_billing_address_requirements() {
		// 'optional_billing_address' is only declared on PayPalGateway's form fields, so other
		// gateways resolve to '' here and fall back to 'no' (see WC_Settings_API::get_option()).
		if ( ! \wc_string_to_bool( $this->payment_method->get_option( 'optional_billing_address', 'no' ) ) ) {
			return;
		}
		if ( ! WC()->cart || WC()->cart->needs_shipping() ) {
			return;
		}
		add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
			foreach (
				[
					'billing_address_1',
					'billing_address_2',
					'billing_city',
					'billing_state',
					'billing_postcode',
					'billing_country',
					'billing_phone',
				] as $field
			) {
				if ( isset( $fields['billing'][ $field ] ) ) {
					$fields['billing'][ $field ]['required'] = false;
				}
			}

			return $fields;
		}, 100 );

		// billing_address_1 empty means PayPal genuinely didn't provide an address (rather than
		// this customer's checkout just lacking one) - don't let WC_Checkout::update_customer_data()
		// overwrite a logged-in customer's saved billing address with these blank fields.
		if ( empty( $_POST['billing_address_1'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			add_filter( 'woocommerce_checkout_update_customer_data', '__return_false' );
		}
	}

	public function handle_checkout_validation( $data, $errors ) {
		do_action( 'wc_ppcp_rest_handle_checkout_validation', $this, $data, $errors );
		if ( $errors->get_error_codes() ) {
			$this->logger->info( sprintf( 'Redirecting to checkout page. Required fields missing: %s', print_r( $errors->get_error_codes(), true ) ) );
			WC()->session->set( 'chosen_payment_method', $this->payment_method->id );
			foreach ( $errors->errors as $code => $messages ) {
				foreach ( $messages as $msg ) {
					\wc_add_notice( $msg, 'error', $errors->get_error_data( $code ) );
				}
			}
			wc_add_notice(
				apply_filters(
					'wc_ppcp_checkout_validation_notice',
					__( 'Please fill out all required fields then click Place Order.', 'pymntpl-paypal-woocommerce' ),
					$data,
					$errors
				),
				'notice'
			);
			wp_send_json(
				[
					'result'   => 'success',
					'redirect' => $this->get_order_review_url(),
					'reload'   => false,
				],
				200
			);
		}
	}

	/**
	 * @param       $payment_method
	 * @param array $fields
	 *
	 * @return string
	 */
	public function get_order_review_url( $fields = [] ) {
		return add_query_arg(
			[
				'_ppcp_order_review' => rawurlencode( base64_encode( wp_json_encode( [
					'payment_method' => $this->payment_method->id,
					'paypal_order'   => $this->payment_method->payment_handler->get_paypal_order_id_from_request(),
					'fields'         => $fields
				] ) ) ),
			],
			wc_get_checkout_url()
		);
	}

	private function set_required_fields() {
		if ( WC()->cart->needs_shipping() ) {
			$_POST['ship_to_different_address'] = true;
		}
		if ( wc_get_page_id( 'terms' ) > 0 ) {
			$_POST['terms'] = 1;
		}
	}

	/**
	 * If the checkout page is a block, check to see if the billing phone is required. If not required, update the
	 * WC checkout fields.
	 *
	 * @return void
	 * @since 1.0.45
	 */
	private function add_checkout_block_filters() {
		$checkout_page_id = wc_get_page_id( 'checkout' );
		if ( function_exists( 'has_block' ) && $checkout_page_id && has_block( 'woocommerce/checkout', $checkout_page_id ) ) {
			$post   = get_post( $checkout_page_id );
			$result = parse_blocks( $post->post_content );
			if ( $result ) {
				foreach ( $result as $block ) {
					if ( $block['blockName'] === 'woocommerce/checkout' ) {
						if ( empty( $block['attrs']['requirePhoneField'] ) ) {
							add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
								if ( isset( $fields['billing']['billing_phone'] ) ) {
									$fields['billing']['billing_phone']['required'] = false;
								}

								return $fields;
							} );
						}
						break;
					}
				}
			}
		}
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return void
	 */
	private function prepare_request_params( $request ) {
		$customer = WC()->customer;
		if ( $customer && $customer->get_id() ) {
			if ( empty( $request['billing_phone'] ) && $customer->get_billing_phone() ) {
				$request['billing_phone'] = $customer->get_billing_phone();
			}
		}
		if ( isset( $request['shipping_state'], $request['shipping_country'] ) ) {
			$request['shipping_state'] = Utils::normalize_address_state( $request['shipping_state'], $request['shipping_country'] );
		}
		if ( isset( $request['billing_state'], $request['billing_country'] ) ) {
			$request['billing_state'] = Utils::normalize_address_state( $request['billing_state'], $request['billing_country'] );
		}
	}

}