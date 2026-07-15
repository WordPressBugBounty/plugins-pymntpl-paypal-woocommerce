<?php

namespace PaymentPlugins\WooCommerce\PPCP\Rest\Exceptions;

/**
 * Exception for PayPal order update callback errors.
 *
 * Produces a 422 Unprocessable Entity response in the format PayPal expects:
 * { "name": "UNPROCESSABLE_ENTITY", "details": [{ "issue": "<ISSUE_CODE>" }] }
 *
 * Supported issue codes:
 *
 * Shipping address events:
 *   ADDRESS_ERROR   - No shipping options available for the address
 *   COUNTRY_ERROR   - Cannot ship to this country
 *   STATE_ERROR     - Cannot ship to this state
 *   ZIP_ERROR       - Cannot ship to this zip code
 *
 * Shipping option events:
 *   METHOD_UNAVAILABLE  - Selected shipping method is unavailable
 *   STORE_UNAVAILABLE   - Part of the order is unavailable at this store
 */
class OrderUpdateCallbackException extends \Exception {

	const ADDRESS_ERROR       = 'ADDRESS_ERROR';
	const COUNTRY_ERROR       = 'COUNTRY_ERROR';
	const STATE_ERROR         = 'STATE_ERROR';
	const ZIP_ERROR           = 'ZIP_ERROR';
	const METHOD_UNAVAILABLE  = 'METHOD_UNAVAILABLE';
	const STORE_UNAVAILABLE   = 'STORE_UNAVAILABLE';

	private string $issue;

	public function __construct( string $issue, string $message = '', ?\Throwable $previous = null ) {
		parent::__construct( $message, 422, $previous );
		$this->issue = $issue;
	}

	public function getIssue(): string {
		return $this->issue;
	}

}