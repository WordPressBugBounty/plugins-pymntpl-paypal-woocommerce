<?php

namespace PaymentPlugins\PayPalSDK;

/**
 * @property string $id
 */
class Customer extends AbstractObject {

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

}