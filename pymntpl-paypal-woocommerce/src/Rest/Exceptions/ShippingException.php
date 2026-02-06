<?php

namespace PaymentPlugins\WooCommerce\PPCP\Rest\Exceptions;

/**
 * Exception for shipping-related errors.
 *
 * Provides additional metadata for wallet payment methods (GPay, Apple Pay)
 * to properly format error responses for their payment sheets.
 */
class ShippingException extends \Exception {

	/**
	 * Error reason code for wallet payment methods.
	 *
	 * @var string
	 */
	private $reason;

	/**
	 * Error intent (what caused the error).
	 *
	 * @var string
	 */
	private $intent;

	/**
	 * Additional error data.
	 *
	 * @var array
	 */
	private $error_data;

	/**
	 * Create a new ShippingException.
	 *
	 * @param string $message User-facing error message
	 * @param string $reason Error reason code (e.g., 'SHIPPING_ADDRESS_INVALID', 'SHIPPING_OPTION_INVALID')
	 * @param string $intent Error intent (e.g., 'SHIPPING_ADDRESS', 'SHIPPING_OPTION')
	 * @param int $code HTTP status code (default: 400)
	 * @param array $error_data Additional error data
	 */
	public function __construct( $message, $reason = 'OTHER_ERROR', $intent = 'SHIPPING_ADDRESS', $code = 400, $error_data = [] ) {
		parent::__construct( $message, $code );
		$this->reason     = $reason;
		$this->intent     = $intent;
		$this->error_data = $error_data;
	}

	/**
	 * Get the error reason code.
	 *
	 * @return string
	 */
	public function getReason() {
		return $this->reason;
	}

	/**
	 * Get the error intent.
	 *
	 * @return string
	 */
	public function getIntent() {
		return $this->intent;
	}

	/**
	 * Get additional error data.
	 *
	 * @return array
	 */
	public function getErrorData() {
		return $this->error_data;
	}

	/**
	 * Convert exception to array for API response.
	 *
	 * @return array
	 */
	public function toArray() {
		return [
			'message' => $this->getMessage(),
			'reason'  => $this->reason,
			'intent'  => $this->intent,
			'data'    => $this->error_data
		];
	}
}