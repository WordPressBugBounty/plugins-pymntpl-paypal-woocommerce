<?php

namespace PaymentPlugins\PPCP\Blocks\Utils;

/**
 * @since 1.1.9
 */
class ActionUtils {

	/**
	 * @param $data
	 *
	 * @since 1.1.9
	 * @return mixed|null
	 */
	public static function apply_payment_data_filter( $data, $payment_gateway ) {
		return apply_filters(
			'wc_ppcp_blocks_add_payment_method_data',
			$data,
			$payment_gateway
		);
	}

}