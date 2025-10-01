<?php

namespace PaymentPlugins\PayPalSDK;

/**
 * @property string $payee_preferred
 * @property string $standard_entry_class_codeenum
 */
class PaymentMethod extends AbstractObject {

	const UNRESTRICTED = 'UNRESTRICTED';

	const IMMEDIATE_PAYMENT_REQUIRED = 'IMMEDIATE_PAYMENT_REQUIRED';

	const TEL = 'TEL';

	const WEB = 'WEB';

	const CCD = 'CCD';

	const PPD = 'PPD';

	/**
	 * @return string
	 */
	public function getPayeePreferred() {
		return $this->payee_preferred;
	}

	/**
	 * @param string $payee_preferred
	 */
	public function setPayeePreferred( $payee_preferred ) {
		$this->payee_preferred = $payee_preferred;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getStandardEntryClassCodeenum() {
		return $this->standard_entry_class_codeenum;
	}

	/**
	 * @param string $standard_entry_class_codeenum
	 */
	public function setStandardEntryClassCodeenum( $standard_entry_class_codeenum ) {
		$this->standard_entry_class_codeenum = $standard_entry_class_codeenum;

		return $this;
	}
}