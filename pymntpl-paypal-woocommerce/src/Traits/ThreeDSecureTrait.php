<?php

namespace PaymentPlugins\WooCommerce\PPCP\Traits;

trait ThreeDSecureTrait {

	/**
	 * Return true if 3DS should be forced for all transactions.
	 *
	 * @return bool
	 */
	public function is_force_3ds_enabled() {
		return wc_string_to_bool( $this->get_option( '3ds_forced', 'no' ) );
	}

	public function is_card_save_enabled() {
		return wc_string_to_bool( $this->get_option( 'card_save_enabled', 'yes' ) );
	}

}