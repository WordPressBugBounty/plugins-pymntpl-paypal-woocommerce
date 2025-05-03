<?php

namespace PaymentPlugins\WooCommerce\PPCP\Utilities;

class PaymentMethodUtils {

	public static function token_exists( $args ) {
		global $wpdb;
		$args  = wp_parse_args(
			$args,
			[
				'token'      => '',
				'user_id'    => '',
				'gateway_id' => ''
			]
		);
		$where = [];
		if ( ! empty( $args['token'] ) ) {
			$where[] = $wpdb->prepare( 'token = %s', $args['token'] );
		}
		if ( ! empty( $args['user_id'] ) ) {
			$where[] = $wpdb->prepare( 'user_id = %d', $args['user_id'] );
		}
		if ( ! empty( $args['gateway_id'] ) ) {
			$where[] = $wpdb->prepare( 'gateway_id = %s', $args['gateway_id'] );
		} else {
			$where[] = $wpdb->prepare( 'gateway_id LIKE %s', '%ppcp_%' );
		}

		$where_clause = ' WHERE ' . implode( ' AND ', $where );

		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_payment_tokens {$where_clause}" );

		return absint( $count ) > 0;
	}

}