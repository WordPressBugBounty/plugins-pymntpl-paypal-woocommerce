<?php

use PaymentPlugins\WooCommerce\PPCP\Constants;

/**
 * @since 1.0.40
 * @return mixed|\PaymentPlugins\WooCommerce\PPCP\Container\Container
 */
function wc_ppcp_get_container() {
	return \PaymentPlugins\WooCommerce\PPCP\Main::container();
}

/**
 * @param $template_name
 * @param $args
 *
 * @since 1.1.0
 * @return mixed
 * @throws \Exception
 */
function wc_ppcp_load_template( $template_name, $args = [] ) {
	$templates = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\TemplateLoader::class );

	return $templates->load_template( $template_name, $args );
}

/**
 * @param $template_name
 * @param $args
 *
 * @since 1.1.0
 * @return mixed
 * @throws \Exception
 */
function wc_ppcp_load_template_html( $template_name, $args = [] ) {
	$templates = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\TemplateLoader::class );

	return $templates->load_template_html( $template_name, $args );
}

function wc_ppcp_get_order_mode( $order ) {
	$settings    = wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\Admin\Settings\APISettings::class );
	$environment = $settings->get_option( 'environment', 'production' );
	if ( $order instanceof \WC_Order ) {
		$environment = $order->get_meta( Constants::PPCP_ENVIRONMENT );
	}

	return $environment;
}