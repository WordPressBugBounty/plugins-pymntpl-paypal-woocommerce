<?php


namespace PaymentPlugins\PayPalSDK;

/**
 * Class ProcessorResponse
 * @package PaymentPlugins\PayPalSDK
 * @property string $avs_code
 * @property string $cvv_code
 * @property string $response_code
 * @property string $payment_advice_code
 */
class ProcessorResponse extends AbstractObject {

	/**
	 * @return string
	 */
	public function getAvsCode() {
		return $this->avs_code;
	}

	/**
	 * @param string $avs_code
	 */
	public function setAvsCode( $avs_code ) {
		$this->avs_code = $avs_code;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCvvCode() {
		return $this->cvv_code;
	}

	/**
	 * @param string $cvv_code
	 */
	public function setCvvCode( $cvv_code ) {
		$this->cvv_code = $cvv_code;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getResponseCode() {
		return $this->response_code;
	}

	/**
	 * @param string $response_code
	 */
	public function setResponseCode( $response_code ) {
		$this->response_code = $response_code;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPaymentAdviceCode() {
		return $this->payment_advice_code;
	}

	/**
	 * @param string $payment_advice_code
	 */
	public function setPaymentAdviceCode( $payment_advice_code ) {
		$this->payment_advice_code = $payment_advice_code;

		return $this;
	}


}