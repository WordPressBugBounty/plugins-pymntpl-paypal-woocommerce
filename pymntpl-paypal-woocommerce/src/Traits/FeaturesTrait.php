<?php

namespace PaymentPlugins\WooCommerce\PPCP\Traits;

use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\AdvancedSettings;

trait FeaturesTrait {

	public function init_supports( $supports = [] ) {
		/**
		 * @var AdvancedSettings $advanced_settings
		 */
		$advanced_settings = wc_ppcp_get_container()->get( AdvancedSettings::class );
		$vault_enabled     = $advanced_settings->is_vault_enabled();

		$traits = \class_uses( \get_class( $this ) );

		foreach ( $traits as $trait ) {
			switch ( $trait ) {
				case 'PaymentPlugins\WooCommerce\PPCP\Traits\ThreeDSecureTrait':
					$supports[] = '3ds';
					break;
				case 'PaymentPlugins\WooCommerce\PPCP\Traits\VaultTokenTrait':
					$supports[] = 'vault';
					break;
				case 'PaymentPlugins\WooCommerce\PPCP\Traits\BillingAgreementTrait':
					if ( ! $vault_enabled ) {
						$supports[] = 'billing_agreement';
					}
					break;
			}
		}


		if ( \in_array( 'billing_agreement', $supports ) ) {
			unset( $supports[ array_search( 'vault', $supports ) ] );
		}


		$this->supports = array_merge( [
			'tokenization',
			'products',
			'subscriptions',
			'add_payment_method',
			'subscription_cancellation',
			'multiple_subscriptions',
			'subscription_amount_changes',
			'subscription_date_changes',
			'default_credit_card_form',
			'refunds',
			'pre-orders',
			'subscription_payment_method_change_admin',
			'subscription_reactivation',
			'subscription_suspension',
			'subscription_payment_method_change_customer',
		], $supports );
	}

}