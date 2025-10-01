<?php

namespace PaymentPlugins\WooCommerce\PPCP;

/**
 * @since - 1.0.38
 */
class CheckoutValidator {

	private $errors;

	const VALIDATION_ERRORS = 8450;

	public function __construct() {
		$this->errors = new \WP_Error();
	}

	public function validate_checkout( \WP_REST_Request $request, $throw_exception = true ) {
		$checkout = WC()->checkout();

		$data = $checkout->get_posted_data();
		try {
			$class  = new \ReflectionClass( $checkout );
			$method = $class->getMethod( 'validate_posted_data' );
			$method->setAccessible( true );

			/**
			 * Used getClosure here since that's the only way to pass by reference which is required
			 * by the method WC_Checkout::validate_posted_data
			 */
			$method->getClosure( $checkout )( $data, $this->errors );
		} catch ( \ReflectionException $e ) {
			wc_ppcp_get_container()->get( Logger::class )->info(
				sprintf( 'Error invoking WC_Checkout::validate_posted_data(). Error: %s', $e->getMessage() )
			);
		}

		/**
		 * We need to trigger this WooCommerce action since 3rd party plugins use it to validate the checkout page. If we
		 * want parity with how the WooCommerce checkout validation works, this action needs to be triggered.
		 */
		do_action( 'woocommerce_after_checkout_validation', $data, $this->errors );

		/**
		 * @since 1.0.39
		 */
		do_action( 'wc_ppcp_checkout_validation', $this, $request );

		if ( $this->errors->has_errors() && $throw_exception ) {
			throw new \Exception( 'validation_errors', self::VALIDATION_ERRORS );
		}
	}

	public function get_errors() {
		return $this->errors->get_error_messages();
	}

	public function has_errors() {
		return $this->errors->has_errors();
	}

	public function add_error( $msg ) {
		$this->errors->add( 'validation_errors', $msg );
	}

	public function get_notices_html() {
		foreach ( $this->errors->errors as $code => $messages ) {
			$data = $this->errors->get_error_data( $code );
			foreach ( $messages as $message ) {
				\wc_add_notice( $message, 'error', $data );
			}
		}

		return \wc_print_notices( true );
	}

	public function get_failure_response() {
		return new \WP_Error( 'validation_errors', 'Validation errors', [
			'status'   => 400,
			'errors'   => $this->get_errors(),
			'messages' => $this->get_notices_html()
		] );
	}

}