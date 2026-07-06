<?php


namespace PaymentPlugins\PayPalSDK;

/**
 * Class PaymentSource
 *
 * @package PaymentPlugins\PayPalSDK
 *
 * @property \PaymentPlugins\PayPalSDK\Token      $token
 * @property \PaymentPlugins\PayPalSDK\Token      $paypal
 * @property \PaymentPlugins\PayPalSDK\CreditCard $card
 * @property                                      $apple_pay
 * @property                                      $google_pay
 * @property                                      $venmo
 */
class PaymentSource extends AbstractObject {

	/**
	 * @return \PaymentPlugins\PayPalSDK\Token
	 */
	public function getToken() {
		return $this->token;
	}

	/**
	 * @param \PaymentPlugins\PayPalSDK\Token $token
	 */
	public function setToken( $token ) {
		$this->token = $token;

		return $this;
	}

	public function setPayPal( $token ) {
		$this->paypal = $token;
	}

	public function getPayPal() {
		return $this->paypal;
	}

	/**
	 * @return CreditCard
	 */
	public function getCard() {
		return $this->card;
	}

	/**
	 * @param CreditCard $card
	 */
	public function setCard( $card ) {
		$this->card = $card;

		return $this;
	}

	/**
	 * @return ExperienceContext
	 */
	public function getExperienceContext() {
		foreach ( $this->_values as $key => $value ) {
			if ( ! empty( $value->experience_context ) ) {
				return $this->_values[ $key ]->experience_context;
			}
		}

		return null;
	}
}