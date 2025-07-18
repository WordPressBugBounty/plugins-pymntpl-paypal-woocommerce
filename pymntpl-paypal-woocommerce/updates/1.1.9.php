<?php
if ( function_exists( 'WC' ) ) {
	try {
		/**
		 * @var \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\CreditCardGateway $cc_gateway
		 */
		$cc_gateway = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\CreditCardGateway::class );

		/**
		 * If email recognition is not enabled, then disable the fastlane icon since that was the behavior before version 1.1.9
		 */
		if ( $cc_gateway->get_option( 'fastlane_flow' ) === 'express_button' ) {
			$cc_gateway->update_option( 'fastlane_icon_enabled', 'no' );
		}
	} catch ( \Exception $e ) {
	}
}