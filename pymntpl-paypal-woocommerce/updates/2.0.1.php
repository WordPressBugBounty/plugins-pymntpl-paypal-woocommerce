<?php
if ( function_exists( 'WC' ) ) {
	// add the block_checkout option to the PayPal gateway
	/**
	 * @var \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\PayPalGateway $paypal_gateway
	 */
	$paypal_gateway = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\PayPalGateway::class );
	$gpay_gateway   = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\GooglePayGateway::class );

	foreach ( [ $paypal_gateway, $gpay_gateway ] as $gateway ) {
		$payment_sections = $gateway->get_option( 'sections', [] );
		$payment_sections = str_replace( 'block_checkout', 'checkout', $payment_sections );
		$payment_sections = array_values( array_unique( $payment_sections ) );

		// Google Pay never had 'block_checkout', so add 'checkout' if enabled
		if ( $gateway->id === 'ppcp_googlepay' && $gateway->enabled === 'yes' && ! in_array( 'checkout', $payment_sections, true ) ) {
			$payment_sections[] = 'checkout';
		}

		$gateway->update_option( 'sections', $payment_sections );
	}

	/**
	 * @var \PaymentPlugins\WooCommerce\PPCP\Logger $logger
	 */
	$logger = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Logger::class );
	$logger->info( 'Update 2.0.1 complete' );
}