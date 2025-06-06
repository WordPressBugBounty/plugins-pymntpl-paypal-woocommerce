<?php

namespace PaymentPlugins\PayPalSDK;

/**
 * @property string $authentication_status
 * @property string $enrollment_status
 */
class ThreeDSecure extends AbstractObject {
	/**
	 * @return string
	 */
	public function getAuthenticationStatus() {
		return $this->authentication_status;
	}

	/**
	 * @param string $authentication_status
	 */
	public function setAuthenticationStatus( $authentication_status ) {
		$this->authentication_status = $authentication_status;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getEnrollmentStatus() {
		return $this->enrollment_status;
	}

	/**
	 * @param string $enrollment_status
	 */
	public function setEnrollmentStatus( $enrollment_status ) {
		$this->enrollment_status = $enrollment_status;

		return $this;
	}
}