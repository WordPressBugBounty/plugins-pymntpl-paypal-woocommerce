<?php

namespace PaymentPlugins\WooCommerce\PPCP\Traits;

use PaymentPlugins\PayPalSDK\Order;
use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings;
use PaymentPlugins\WooCommerce\PPCP\Messages;

trait ThreeDSecureTrait {

	protected static array $ThreeDSecureTraitFeatures = [
		'3ds'
	];

	/**
	 * Returns true if 3DS is enabled.
	 *
	 * @return bool
	 */
	public function is_3ds_enabled() {
		return wc_string_to_bool( $this->get_option( '3ds_enabled', 'no' ) );
	}

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

	/**
	 * @param Order     $paypal_order
	 * @param \WC_Order $order
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function validate_3ds_order( $paypal_order, $order ) {
		if ( $this->is_3ds_enabled() ) {
			// 3DS is enabled so check conditions for payments.
			$payment_source = $paypal_order->getPaymentSource();

			// For payment methods like google_pay, the card is in payment_source > google_pay > card
			if ( ! $payment_source->getCard() && isset( $payment_source->{$this->payment_method_type}->card ) ) {
				$card = $payment_source->{$this->payment_method_type}->card;
			} else {
				$card = $payment_source->getCard();
			}

			$authentication_result = $card ? $card->getAuthenticationResult() : null;

			if ( ! $authentication_result || ! $authentication_result->getThreeDSecure() ) {
				$key = 'N_N_NO';
			} else {
				$threeds_result = $authentication_result->getThreeDSecure();

				$key = sprintf(
					'%1$s_%2$s_%3$s',
					$threeds_result->getEnrollmentStatus(),
					$threeds_result->getAuthenticationStatus(),
					$authentication_result->getLiabilityShift()
				);
			}

			$settings = wc_ppcp_get_container()->get( AdvancedSettings::class );

			$actions = $settings->get_3ds_actions();

			$recommended_actions = wp_parse_args( $settings->get_option( '3ds_config', [] ), $actions );

			if ( isset( $recommended_actions[ $key ] ) ) {
				$messages = wc_ppcp_get_container()->get( Messages::class );
				$action   = $recommended_actions[ $key ];

				switch ( $action ) {
					case 'reject':
						$text = $messages->get_message( $key, __( '3DS payment has been rejected.', 'pymntpl-paypal-woocommerce' ) );
						throw new \Exception( $text );
				}
			}
		}
	}

}