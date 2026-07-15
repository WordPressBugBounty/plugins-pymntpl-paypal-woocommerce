<?php

namespace PaymentPlugins\WooCommerce\PPCP\Traits;

/**
 * For gateways that can be vaulted standalone via PayPal's Vault Setup Tokens API
 * (POST /v3/vault/setup-tokens) independent of any order - e.g. PayPal wallet and cards.
 *
 * Gateways that support 'vault' but NOT this feature (e.g. Apple Pay, whose wallet token is
 * single-use and transaction-scoped) can only be vaulted as an attribute of a real, authorized
 * order - see PaymentSourceFactory::create()'s store_in_vault attribute.
 */
trait VaultSetupTokenTrait {

	protected static array $VaultSetupTokenTraitFeatures = [
		'vault_setup_token'
	];

}