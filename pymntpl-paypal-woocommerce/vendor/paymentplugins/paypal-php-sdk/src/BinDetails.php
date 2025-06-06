<?php

namespace PaymentPlugins\PayPalSDK;

/**
 * @property string $bin
 * @property string $issuing_bank
 * @property array $products
 * @property string $bin_country_code
 */
class BinDetails extends AbstractObject {
	/**
	 * @return string
	 */
	public function getBin() {
		return $this->bin;
	}

	/**
	 * @param string $bin
	 */
	public function setBin( $bin ) {
		$this->bin = $bin;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getIssuingBank() {
		return $this->issuing_bank;
	}

	/**
	 * @param string $issuing_bank
	 */
	public function setIssuingBank( $issuing_bank ) {
		$this->issuing_bank = $issuing_bank;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getProducts() {
		return $this->products;
	}

	/**
	 * @param array $products
	 */
	public function setProducts( $products ) {
		$this->products = $products;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBinCountryCode() {
		return $this->bin_country_code;
	}

	/**
	 * @param string $bin_country_code
	 */
	public function setBinCountryCode( $bin_country_code ) {
		$this->bin_country_code = $bin_country_code;

		return $this;
	}
}