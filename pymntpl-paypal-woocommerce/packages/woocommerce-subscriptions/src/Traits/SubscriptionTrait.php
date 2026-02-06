<?php

namespace PaymentPlugins\PPCP\WooCommerceSubscriptions\Traits;

trait SubscriptionTrait {

	protected static array $SubscriptionTraitFeatures = [
		'subscriptions',
		'subscription_cancellation',
		'multiple_subscriptions',
		'subscription_amount_changes',
		'subscription_date_changes',
		'subscription_payment_method_change_admin',
		'subscription_reactivation',
		'subscription_suspension',
		'subscription_payment_method_change_customer',
	];
}