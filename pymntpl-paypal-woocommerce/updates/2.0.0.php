<?php
if ( function_exists( 'WC' ) ) {
	// add the block_checkout option to the PayPal gateway
	/**
	 * @var \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\PayPalGateway $paypal_gateway
	 */
	$paypal_gateway     = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\PayPalGateway::class );
	$payment_sections   = $paypal_gateway->get_option( 'sections', [] );
	$payment_sections[] = 'block_checkout';
	$paypal_gateway->update_option( 'sections', $payment_sections );

	// copy the Credit Card gateway's 3ds settings to the Advanced Settings.
	$cc_gateway = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\CreditCardGateway::class );
	/**
	 * @var \PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings $advanced_settings
	 */
	$advanced_settings = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings::class );

	$advanced_settings->update_option(
		'3ds_config',
		$cc_gateway->get_option( '3ds_config', $advanced_settings->get_3ds_actions() )
	);

	/**
	 * @var \PaymentPlugins\WooCommerce\PPCP\Logger $logger
	 */
	$logger = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Logger::class );
	$logger->info( 'Update 2.0.0 complete' );
}