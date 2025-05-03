<?php
if ( function_exists( 'WC' ) ) {
	/**
	 * @var \PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings $advanced_settings
	 */
	$advanced_settings = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings::class );

	// Disable vault for existing merchant. They must manually enable it of they're not a new user of the plugin.
	$advanced_settings->update_option( 'vault_enabled', 'no' );
}