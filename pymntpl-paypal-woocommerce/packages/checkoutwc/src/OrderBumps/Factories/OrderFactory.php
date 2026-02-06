<?php

namespace PaymentPlugins\PPCP\CheckoutWC\OrderBumps\Factories;

use PaymentPlugins\PayPalSDK\Amount;
use PaymentPlugins\PayPalSDK\Breakdown;
use PaymentPlugins\PayPalSDK\Collection;
use PaymentPlugins\PayPalSDK\Item;
use PaymentPlugins\PayPalSDK\Money;
use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\PayPalSDK\PaymentSource;
use PaymentPlugins\PayPalSDK\PurchaseUnit;
use PaymentPlugins\PayPalSDK\Token;
use PaymentPlugins\WooCommerce\PPCP\Constants;
use PaymentPlugins\WooCommerce\PPCP\Factories\CoreFactories;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\AbstractGateway;
use PaymentPlugins\WooCommerce\PPCP\Utilities\NumberUtil;
use PaymentPlugins\WooCommerce\PPCP\Utils;

class OrderFactory {

	/**
	 * @param \WC_Order $order
	 * @param array $product_data
	 *
	 * @return Order
	 * @throws \Exception
	 */
	public function from_order( \WC_Order $order, $product_data ): Order {
		/**
		 * @var AbstractGateway $payment_method
		 */
		$payment_method = wc_get_payment_gateway_by_order( $order );
		$currency       = $order->get_currency();
		$product_id     = ! empty( $product_data['variation_id'] ) ? $product_data['variation_id'] : $product_data['id'];
		$product        = \wc_get_product( $product_id );

		/**
		 * @var CoreFactories $factories
		 */
		$factories = wc_ppcp_get_container()->get( CoreFactories::class );
		$factories->initialize( $order, $payment_method );

		$needs_shipping = $product->needs_shipping();
		$total          = $product_data['price'];
		$subtotal       = $product_data['args']['subtotal'];
		$item_total     = $subtotal / $product_data['qty'];

		// need to get tax total
		$tax_total = NumberUtil::round( $total - $subtotal, $currency );

		$application_context = $factories->applicationContext->get( $needs_shipping, true );

		$result = ( new Order() )
			->setIntent( $payment_method->get_option( 'intent' ) )
			->setPayer( $factories->payer->from_order() )
			->setApplicationContext( $application_context );

		$purchase_units = new Collection();
		$purchase_unit  = ( new PurchaseUnit() )
			->setAmount( ( new Amount() )
				->setValue( NumberUtil::round_incl_currency( $product_data['price'], $currency ) )
				->setCurrencyCode( $currency )
				->setBreakdown( ( new Breakdown() )
					->setItemTotal( ( new Money() )
						->setCurrencyCode( $currency )
						->setValue( NumberUtil::round_incl_currency( $item_total, $currency ) ) )
					->setShipping( ( new Money() )
						->setCurrencyCode( $currency )
						->setValue( NumberUtil::round_incl_currency( 0, $currency ) ) )
					->setTaxTotal( ( new Money() )
						->setCurrencyCode( $currency )
						->setValue( $tax_total ) )
					->setDiscount( ( new Money() )
						->setCurrencyCode( $currency )
						->setValue( NumberUtil::round_incl_currency( 0, $currency ) ) )
					->setHandling( ( new Money() )
						->setCurrencyCode( $currency )
						->setValue( NumberUtil::round_incl_currency( 0, $currency ) ) ) ) )
			->setItems( array_reduce( [ $product ], function ( $collection, $product ) use ( $product_data, $currency ) {

				return $collection->add( ( new Item() )
					->setName( \substr( $product->get_name(), 0, 127 ) )
					->setQuantity( $product_data['qty'] )
					->setUnitAmount( ( new Money() )
						->setCurrencyCode( $currency )
						->setValue( NumberUtil::round_incl_currency( $product_data['args']['subtotal'] / $product_data['qty'], $currency ) ) ) );
			}, new Collection() ) )
			->setInvoiceId( sprintf( '%s-%s', $order->get_id(), $product_data['bump_id'] ) )
			->setCustomId( sprintf( '%s-%s', $order->get_id(), $product_data['bump_id'] ) );

		if ( $needs_shipping ) {
			$purchase_unit->setShipping( $factories->shipping->from_order( 'shipping' ) );
			if ( ! Utils::is_valid_address( $purchase_unit->getShipping()->getAddress(), 'shipping' ) ) {
				unset( $purchase_unit->getShipping()->address );
				$billing_address = $factories->address->from_order( 'billing' );
				if ( Utils::is_valid_address( $billing_address, 'shipping' ) ) {
					$purchase_unit->getShipping()->setAddress( $billing_address );
				}
			}
		}

		$factories->purchaseUnit->filter_purchase_unit( $purchase_unit, $purchase_unit->getAmount()->getValue() );
		$purchase_units->add( $purchase_unit );

		$result->setPurchaseUnits( $purchase_units );

		if ( $order->get_meta( Constants::PAYMENT_METHOD_TOKEN ) ) {
			$result->setPaymentSource( $factories->paymentSource->from_order() );
		} elseif ( $order->get_meta( Constants::BILLING_AGREEMENT_ID ) ) {
			$result->setPaymentSource( ( new PaymentSource() )
				->setToken( ( new Token() )
					->setId( $order->get_meta( Constants::BILLING_AGREEMENT_ID ) )
					->setType( Token::BILLING_AGREEMENT ) ) );
		}

		return $result;
	}
}