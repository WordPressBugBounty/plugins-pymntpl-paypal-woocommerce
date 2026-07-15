<?php

namespace PaymentPlugins\WooCommerce\PPCP\Traits;

/**
 * For gateways that don't need a PayPal order id returned synchronously when a product is added
 * to cart (Apple Pay, Google Pay) - unlike PayPal's own Buttons SDK, which must return an order
 * id from its createOrder callback. These gateways only need an order once payment is actually
 * authorized, by which point any shipping selection made in their own payment sheet has already
 * been applied to the cart - so creating the order early and never updating it risks the order
 * amount going stale relative to the final cart total.
 */
trait DeferredOrderCreationTrait {

	protected static array $DeferredOrderCreationTraitFeatures = [
		'deferred_order_creation'
	];

}