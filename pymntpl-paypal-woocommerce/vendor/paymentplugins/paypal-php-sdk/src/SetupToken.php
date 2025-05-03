<?php

namespace PaymentPlugins\PayPalSDK;

/**
 * @property PaymentSource $payment_source
 * @property Collection $links
 * @property string $id
 * @property int $ordinal
 * @property Customer $customer
 * @property string $status
 */
class SetupToken extends AbstractObject {

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
	 * @return int
	 */
	public function getOrdinal() {
		return $this->ordinal;
	}

	/**
	 * @param int $ordinal
	 */
	public function setOrdinal( $ordinal ) {
		$this->ordinal = $ordinal;

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

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @param string $status
	 */
	public function setStatus( $status ) {
		$this->status = $status;

		return $this;
	}

	public function getApprovalUrl() {
		foreach ( $this->getLinks()->getValues() as $link ) {
			if ( $link->getRel() === 'approve' ) {
				return $link->getHref();
			}
		}

		return '';
	}

}