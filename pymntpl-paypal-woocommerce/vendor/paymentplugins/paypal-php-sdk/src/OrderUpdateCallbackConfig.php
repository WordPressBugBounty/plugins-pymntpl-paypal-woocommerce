<?php

namespace PaymentPlugins\PayPalSDK;

use PaymentPlugins\PayPalSDK\AbstractObject;

/**
 * @property array  $callback_events
 * @property string $callback_url
 */
class OrderUpdateCallbackConfig extends AbstractObject {
	/**
	 * @return array
	 */
	public function getCallbackEvents() {
		return $this->callback_events;
	}

	/**
	 * @param array $callback_events
	 */
	public function setCallbackEvents( $callback_events ) {
		$this->callback_events = $callback_events;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCallbackUrl() {
		return $this->callback_url;
	}

	/**
	 * @param string $callback_url
	 */
	public function setCallbackUrl( $callback_url ) {
		$this->callback_url = $callback_url;

		return $this;
	}


}