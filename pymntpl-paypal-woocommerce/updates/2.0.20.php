<?php
if ( function_exists( 'WC' ) ) {
	/**
	 * @var \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\PayPalGateway $paypal_gateway
	 */
	$paypal_gateway = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\PayPalGateway::class );

	// Disable server-side shipping callback for existing installs. New installs default to 'yes'.
	$paypal_gateway->update_option( 'server_side_shipping_callback', 'no' );

	/**
	 * @var \PaymentPlugins\WooCommerce\PPCP\Logger $logger
	 */
	$logger = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Logger::class );
	$logger->info( 'Update 2.0.20 complete' );
}