<?php
/**
 * Plugin Name: Payment Plugins for PayPal WooCommerce
 * Plugin URI: https://docs.paymentplugins.com/wc-paypal/config/
 * Description: Accept PayPal on your WooCommerce site.
 * Version: 2.0.14
 * Author: Payment Plugins, support@paymentplugins.com
 * Text Domain: pymntpl-paypal-woocommerce
 * Domain Path: /i18n/languages/
 * Tested up to: 7.0
 * Requires at least: 4.7
 * Requires PHP: 7.4
 * WC requires at least: 3.4
 * WC tested up to: 10.7
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __FILE__ ) . '/vendor/autoload.php';

\PaymentPlugins\WooCommerce\PPCP\PluginValidation::is_valid( function () {
	new \PaymentPlugins\WooCommerce\PPCP\Main( '2.0.14', __FILE__ );
} );
