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

		// Core supported features for all gateways
		$supports = array_merge(
			$supports,
			[
				'products',
				'default_credit_card_form',
				'refunds',
			]
		);

		// Get traits from the class and all parent classes
		$traits = [];
		$class  = \get_class( $this );
		do {
			$traits = array_merge( \class_uses( $class ), $traits );
		} while ( $class = \get_parent_class( $class ) );

		foreach ( $traits as $trait ) {
			$property_name = substr( $trait, strrpos( $trait, '\\' ) + 1 ) . 'Features';
			if ( property_exists( $this, $property_name ) ) {
				$features = $this::$$property_name;
				if ( \is_array( $features ) ) {
					$supports = array_merge( $supports, $features );
				}
			}
		}

		if ( $this->id === 'ppcp_applepay' ) {
			$supports = array_diff( $supports, [ 'subscription_payment_method_change_customer' ] );
		}

		if ( ! $vault_enabled && \in_array( 'billing_agreement', $supports ) ) {
			unset( $supports[ array_search( 'vault', $supports ) ] );
		}
		if ( $vault_enabled && \in_array( 'billing_agreement', $supports ) ) {
			unset( $supports[ array_search( 'billing_agreement', $supports ) ] );
		}
		// If vault is supported, then add tokenization and add_payment_method
		if ( \in_array( 'vault', $supports ) ) {
			$supports[] = 'tokenization';
			$supports[] = 'add_payment_method';
		}

		/**
		 * Allow external packages to add payment gateway features.
		 *
		 * @param array  $supports Array of feature strings to add.
		 * @param string $gateway_id The payment gateway ID.
		 * @param object $gateway The payment gateway instance.
		 *
		 * @since 1.0.0
		 */
		$supports = apply_filters(
			'wc_ppcp_payment_gateway_features',
			$supports,
			$this->id,
			$this
		);

		$this->supports = $supports;
	}

}