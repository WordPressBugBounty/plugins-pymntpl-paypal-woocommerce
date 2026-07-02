<?php

namespace PaymentPlugins\WooCommerce\PPCP\Utilities;

use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;

/**
 * Handles JWT-based cart token generation and validation.
 *
 * Delegates to WooCommerce's CartTokenUtils when available (WC 10.7+).
 * Falls back to calling JsonWebToken directly (available since WC 5.6) for older versions.
 */
class CartTokenHandler {

	/**
	 * Generate a cart token for the given customer ID.
	 */
	public static function get_cart_token( string $customer_id ): string {
		if ( class_exists( \Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils::class ) ) {
			return \Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils::get_cart_token( $customer_id );
		}

		return JsonWebToken::create(
			[
				'user_id' => $customer_id,
				'exp'     => self::get_expiration(),
				'iss'     => 'store-api',
			],
			self::get_secret()
		);
	}

	/**
	 * Validate a cart token.
	 */
	public static function validate_cart_token( string $token ): bool {
		if ( class_exists( \Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils::class ) ) {
			return \Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils::validate_cart_token( $token );
		}

		return JsonWebToken::validate( $token, self::get_secret() );
	}

	/**
	 * Extract the payload from a cart token.
	 *
	 * @return array{ user_id: string, exp: int, iss: string }
	 */
	public static function get_cart_token_payload( string $token ): array {
		if ( class_exists( \Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils::class ) ) {
			return \Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils::get_cart_token_payload( $token );
		}

		$parts = JsonWebToken::get_parts( $token )->payload;

		return [
			'user_id' => $parts->user_id ?? '',
			'exp'     => $parts->exp ?? 0,
			'iss'     => $parts->iss ?? '',
		];
	}

	private static function get_secret(): string {
		return '@' . wp_salt();
	}

	private static function get_expiration(): int {
		return time() + (int) apply_filters( 'wc_session_expiration', DAY_IN_SECONDS * 2 );
	}
}