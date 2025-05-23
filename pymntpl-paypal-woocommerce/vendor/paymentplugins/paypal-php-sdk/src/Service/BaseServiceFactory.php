<?php


namespace PaymentPlugins\PayPalSDK\Service;

/**
 * Class BaseServiceFactory
 *
 * @package PaymentPlugins\PayPalSDK\Service
 *
 */
class BaseServiceFactory extends AbstractServiceFactory {

	private $classmap = array(
		'orders'                 => OrderService::class,
		'auth'                   => OAuthTokenService::class,
		'partner'                => PartnerService::class,
		'captures'               => PaymentCaptureService::class,
		'authorizations'         => PaymentAuthorizationService::class,
		'billingAgreements'      => BillingAgreementService::class,
		'billingAgreementTokens' => BillingAgreementTokenService::class,
		'webhooks'               => WebhookService::class,
		'paymentTokens'          => PaymentTokenService::class,
		'paymentTokensV3'        => PaymentTokenServiceV3::class,
		'tracking'               => TrackingService::class,
		'setupTokens'            => SetupTokenService::class
	);

	public function getClass( $classname ) {
		return isset( $this->classmap[ $classname ] ) ? $this->classmap[ $classname ] : null;
	}

}