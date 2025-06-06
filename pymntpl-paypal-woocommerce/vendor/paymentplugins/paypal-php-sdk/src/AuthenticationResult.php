<?php

namespace PaymentPlugins\PayPalSDK;

/**
 * @property string $liability_shift
 * @property ThreeDSecure $three_d_secure
 */
class AuthenticationResult extends AbstractObject {
	/**
	 * @return string
	 */
	public function getLiabilityShift() {
		return $this->liability_shift;
	}

	/**
	 * @param string $liability_shift
	 */
	public function setLiabilityShift( $liability_shift ) {
		$this->liability_shift = $liability_shift;

		return $this;
	}

	/**
	 * @return ThreeDSecure
	 */
	public function getThreeDSecure() {
		return $this->three_d_secure;
	}

	/**
	 * @param ThreeDSecure $three_d_secure
	 */
	public function setThreeDSecure( $three_d_secure ) {
		$this->three_d_secure = $three_d_secure;

		return $this;
	}
}