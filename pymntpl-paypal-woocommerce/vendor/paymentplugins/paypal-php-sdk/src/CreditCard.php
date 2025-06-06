<?php

namespace PaymentPlugins\PayPalSDK;

/**
 * @property string $name
 * @property string $last_digits
 * @property string $available_networks
 * @property string $brand
 * @property string $type
 * @property AuthenticationResult $authentication_result
 * @property $attributes
 * @property string $expiry
 * @property BinDetails $bin_details
 */
class CreditCard extends AbstractObject {
	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName( $name ) {
		$this->name = $name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLastDigits() {
		return $this->last_digits;
	}

	/**
	 * @param string $last_digits
	 */
	public function setLastDigits( $last_digits ) {
		$this->last_digits = $last_digits;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getAvailableNetworks() {
		return $this->available_networks;
	}

	/**
	 * @param string $available_networks
	 */
	public function setAvailableNetworks( $available_networks ) {
		$this->available_networks = $available_networks;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBrand() {
		return $this->brand;
	}

	/**
	 * @param string $brand
	 */
	public function setBrand( $brand ) {
		$this->brand = $brand;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType( $type ) {
		$this->type = $type;

		return $this;
	}

	/**
	 * @return AuthenticationResult
	 */
	public function getAuthenticationResult() {
		return $this->authentication_result;
	}

	/**
	 * @param AuthenticationResult $authentication_result
	 */
	public function setAuthenticationResult( $authentication_result ) {
		$this->authentication_result = $authentication_result;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * @param mixed $attributes
	 */
	public function setAttributes( $attributes ) {
		$this->attributes = $attributes;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getExpiry() {
		return $this->expiry;
	}

	/**
	 * @param string $expiry
	 */
	public function setExpiry( $expiry ) {
		$this->expiry = $expiry;

		return $this;
	}

	/**
	 * @return BinDetails
	 */
	public function getBinDetails() {
		return $this->bin_details;
	}

	/**
	 * @param BinDetails $bin_details
	 */
	public function setBinDetails( $bin_details ) {
		$this->bin_details = $bin_details;

		return $this;
	}
}