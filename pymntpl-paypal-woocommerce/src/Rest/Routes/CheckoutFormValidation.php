<?php

namespace PaymentPlugins\WooCommerce\PPCP\Rest\Routes;

use PaymentPlugins\WooCommerce\PPCP\CheckoutValidator;

class CheckoutFormValidation extends AbstractRoute {

	private CheckoutValidator $validator;

	public function __construct() {
		$this->validator = new CheckoutValidator();
	}

	public function get_path() {
		return '/checkout-validation';
	}

	public function get_routes() {
		return [
			[
				'methods'  => \WP_REST_Server::CREATABLE,
				'callback' => [ $this, 'handle_request' ]
			]
		];
	}

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return array
	 */
	public function handle_post_request( \WP_REST_Request $request ) {
		$this->populate_post_data( $request );
		$this->validator->validate_checkout( $request );

		return [
			'success' => true
		];
	}

	/**
	 * @param $error
	 *
	 * @return mixed|void|\WP_Error
	 */
	public function get_error_response( $error ) {
		if ( $error instanceof \Exception && $this->validator->has_errors() ) {
			return $this->validator->get_failure_response();
		}

		return parent::get_error_response( $error );
	}
}