<?php

namespace PaymentPlugins\WooCommerce\PPCP\Rest\Validators;

use PaymentPlugins\WooCommerce\PPCP\Payments\PaymentGateways;
use WP_Error;

/**
 * Validator for REST API route parameters.
 *
 * Provides reusable validation logic that can be shared across multiple routes.
 */
class RouteValidator {

	/**
	 * Validate that the payment method exists and is valid for the request.
	 *
	 * @param mixed            $param   The payment method ID to validate
	 * @param \WP_REST_Request $request The REST request object
	 *
	 * @return bool|WP_Error True if valid, WP_Error otherwise
	 */
	public function validate_payment_method( $param, $request ) {
		/**
		 * @var PaymentGateways $gateways
		 */
		$gateways = wc_ppcp_get_container()->get( PaymentGateways::class );

		if ( ! $gateways->has_gateway( $param ) ) {
			return new WP_Error(
				'invalid_payment_method',
				sprintf(
					__( 'Payment method %s is invalid for this request.', 'pymntpl-paypal-woocommerce' ),
					$param
				)
			);
		}

		return true;
	}

}