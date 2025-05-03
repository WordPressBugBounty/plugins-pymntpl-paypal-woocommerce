<?php

namespace PaymentPlugins\PayPalSDK;

/**
 * @property string $id
 * @property PaymentSource $payment_source
 * @property Collection $links
 * @property Customer $customer
 */
class PaymentToken extends AbstractObject {
	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param string $id
	 */
	public function setId( $id ) {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return PaymentSource
	 */
	public function getPaymentSource() {
		return $this->payment_source;
	}

	/**
	 * @param PaymentSource $payment_source
	 */
	public function setPaymentSource( $payment_source ) {
		$this->payment_source = $payment_source;

		return $this;
	}

	/**
	 * @return Collection
	 */
	public function getLinks() {
		return $this->links;
	}

	/**
	 * @param Collection $links
	 */
	public function setLinks( $links ) {
		$this->links = $links;

		return $this;
	}

	/**
	 * @return Customer
	 */
	public function getCustomer() {
		return $this->customer;
	}

	/**
	 * @param Customer $customer
	 */
	public function setCustomer( $customer ) {
		$this->customer = $customer;

		return $this;
	}
}