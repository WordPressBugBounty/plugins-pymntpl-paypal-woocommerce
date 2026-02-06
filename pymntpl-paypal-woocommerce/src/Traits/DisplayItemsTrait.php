<?php

namespace PaymentPlugins\WooCommerce\PPCP\Traits;

/**
 * Trait for handling display items for payment gateways (Google Pay, Apple Pay, etc.)
 *
 * This trait provides the business logic for determining what items to display
 * in a payment sheet. Gateways that use this trait must implement the
 * get_display_item() method to format items according to their specific requirements.
 *
 * Pattern matches the Stripe plugin implementation for consistency.
 */
trait DisplayItemsTrait {

	/**
	 * Get all display items for cart
	 *
	 * This method contains the business logic for determining which items
	 * from the cart should be displayed. The actual formatting is delegated
	 * to the get_display_item() method which must be implemented by the gateway.
	 *
	 * @param \WC_Cart $cart
	 * @param array    $items Optional starting items array
	 *
	 * @return array
	 */
	public function get_display_items_for_cart( $cart, $items = [] ) {
		$incl_tax = $this->display_prices_including_tax( $cart );

		// Add cart line items
		foreach ( $cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			$qty     = $cart_item['quantity'];
			$label   = $qty > 1 ? sprintf( '%s X %s', $product->get_name(), $qty ) : $product->get_name();
			$price   = $incl_tax
				? wc_get_price_including_tax( $product, [ 'qty' => $qty ] )
				: wc_get_price_excluding_tax( $product, [ 'qty' => $qty ] );
			$items[] = $this->get_display_item( $price, $label, 'product' );
		}

		// Add shipping
		if ( $cart->needs_shipping() ) {
			$price   = $incl_tax
				? $cart->get_shipping_total() + $cart->get_shipping_tax()
				: $cart->get_shipping_total();
			$items[] = $this->get_display_item( $price, __( 'Shipping', 'pymntpl-paypal-woocommerce' ), 'shipping' );
		}

		// Add fees
		foreach ( $cart->get_fees() as $fee ) {
			$price   = $incl_tax ? $fee->total + $fee->tax : $fee->total;
			$items[] = $this->get_display_item( $price, $fee->name, 'fee' );
		}

		// Add discount
		if ( 0 < $cart->discount_cart ) {
			$price   = - 1 * abs( $incl_tax
					? $cart->discount_cart + $cart->discount_cart_tax
					: $cart->discount_cart );
			$items[] = $this->get_display_item( $price, __( 'Discount', 'pymntpl-paypal-woocommerce' ), 'discount' );
		}

		// Add taxes separately if not included in prices
		if ( ! $incl_tax && wc_tax_enabled() ) {
			$items[] = $this->get_display_item( $cart->get_taxes_total(), __( 'Tax', 'pymntpl-paypal-woocommerce' ), 'tax' );
		}

		return $items;
	}

	/**
	 * Get all display items for order
	 *
	 * This method contains the business logic for determining which items
	 * from the order should be displayed. The actual formatting is delegated
	 * to the get_display_item() method which must be implemented by the gateway.
	 *
	 * @param \WC_Order $order
	 * @param array     $items Optional starting items array
	 *
	 * @return array
	 */
	public function get_display_items_for_order( $order, $items = [] ) {
		// Add order line items
		foreach ( $order->get_items() as $item ) {
			$qty     = $item->get_quantity();
			$label   = $qty > 1 ? sprintf( '%s X %s', $item->get_name(), $qty ) : $item->get_name();
			$items[] = $this->get_display_item( $item->get_subtotal(), $label, 'item' );
		}

		// Add shipping
		if ( 0 < $order->get_shipping_total() ) {
			$items[] = $this->get_display_item(
				$order->get_shipping_total(),
				__( 'Shipping', 'pymntpl-paypal-woocommerce' ),
				'shipping'
			);
		}

		// Add discount
		if ( 0 < $order->get_total_discount() ) {
			$items[] = $this->get_display_item(
				- 1 * $order->get_total_discount(),
				__( 'Discount', 'pymntpl-paypal-woocommerce' ),
				'discount'
			);
		}

		// Add fees (combined)
		if ( 0 < count( $order->get_fees() ) ) {
			$fee_total = 0;
			foreach ( $order->get_fees() as $fee ) {
				$fee_total += $fee->get_total();
			}
			$items[] = $this->get_display_item( $fee_total, __( 'Fees', 'pymntpl-paypal-woocommerce' ), 'fee' );
		}

		// Add taxes
		if ( 0 < $order->get_total_tax() ) {
			$items[] = $this->get_display_item(
				$order->get_total_tax(),
				__( 'Tax', 'pymntpl-paypal-woocommerce' ),
				'tax'
			);
		}

		return $items;
	}

	/**
	 * Get display item for a single product
	 *
	 * This is typically used on product pages for "Buy Now" type buttons.
	 *
	 * @param \WC_Product $product
	 *
	 * @return array
	 */
	public function get_display_items_for_product( $product ) {
		return [ $this->get_display_item_for_product( $product ) ];
	}

	/**
	 * Get all shipping options for cart
	 *
	 * This method contains the business logic for determining which shipping
	 * options are available. The actual formatting is delegated to the
	 * get_shipping_option() method which must be implemented by the gateway.
	 *
	 * @param array $options Optional starting options array
	 *
	 * @return array
	 */
	public function get_shipping_options( $options = [] ) {
		$packages = $this->get_shipping_packages();
		$incl_tax = $this->display_prices_including_tax( WC()->cart );

		foreach ( $packages as $i => $package ) {
			foreach ( $package['rates'] as $rate ) {
				$cost      = (float) $rate->get_cost();
				$price     = $incl_tax ? $cost + (float) $rate->get_shipping_tax() : $cost;
				$options[] = $this->get_shipping_option( $price, $rate, $i, $package, $incl_tax );
			}
		}

		return $options;
	}

	/**
	 * Get shipping packages from WooCommerce session
	 *
	 * @return array
	 */
	protected function get_shipping_packages() {
		return WC()->shipping()->get_packages();
	}

	/**
	 * Get formatted shipping method ID
	 *
	 * @param string $id    The shipping rate ID
	 * @param int    $index The package index
	 *
	 * @return string
	 */
	public function get_shipping_method_id( $id, $index ) {
		return sprintf( '%s:%s', $index, $id );
	}

	/**
	 * Returns the customer's chosen shipping method, if any is selected. Returns an empty string if no method is chosen.
	 *
	 * @return string
	 */
	public function get_selected_shipping_method() {
		$shipping_method = '';
		if ( ! WC()->session ) {
			return $shipping_method;
		}
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods', [] );

		foreach ( $chosen_methods as $idx => $method ) {
			$shipping_method = $this->get_shipping_method_id( $method, $idx );
			break;
		}

		return $shipping_method;
	}

	/**
	 * Determine if prices should be displayed including tax
	 *
	 * @param \WC_Cart $cart
	 *
	 * @return bool
	 */
	protected function display_prices_including_tax( $cart ) {
		return wc_tax_enabled() && $cart->display_prices_including_tax();
	}

	/**
	 * Format a single display item
	 *
	 * This method MUST be implemented by the gateway class using this trait.
	 * Each gateway formats display items according to their payment provider's
	 * specific requirements (e.g., Google Pay vs Apple Pay format).
	 *
	 * @param float  $price The price/amount for this line item
	 * @param string $label The label/description for this line item
	 * @param string $type  The type of item (product, shipping, fee, discount, tax)
	 *
	 * @return array Formatted display item array
	 */
	abstract protected function get_display_item( $price, $label, $type );

	/**
	 * Format a display item for a product
	 *
	 * This method MUST be implemented by the gateway class using this trait.
	 * Used specifically for product page "Buy Now" scenarios.
	 *
	 * @param \WC_Product $product
	 *
	 * @return array Formatted display item array
	 */
	abstract protected function get_display_item_for_product( $product );

	/**
	 * Format a single shipping option
	 *
	 * This method MUST be implemented by the gateway class using this trait.
	 * Each gateway formats shipping options according to their payment provider's
	 * specific requirements (e.g., Google Pay vs Apple Pay format).
	 *
	 * @param float             $price    The price/amount for this shipping method
	 * @param \WC_Shipping_Rate $rate     The shipping rate object
	 * @param int               $index    The package index
	 * @param array             $package  The shipping package
	 * @param bool              $incl_tax Whether prices include tax
	 *
	 * @return array Formatted shipping option array
	 */
	abstract protected function get_shipping_option( $price, $rate, $index, $package, $incl_tax );

}