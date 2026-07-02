<?php

namespace PaymentPlugins\PayPalSDK;

use PaymentPlugins\PayPalSDK\AbstractObject;

/**
 * @property string                     $brand_name
 * @property string                     $locale
 * @property string                     $landing_page
 * @property string                     $shipping_preference
 * @property string                     $context_preference
 * @property string                     $payment_method_preference
 * @property string                     $user_action
 * @property string                     $return_url
 * @property string                     $cancel_url
 * @property  OrderUpdateCallbackConfig $order_update_callback_config
 */
class ExperienceContext extends AbstractObject {
	/**
	 * @return string
	 */
	public function getBrandName() {
		return $this->brand_name;
	}

	/**
	 * @param string $brand_name
	 */
	public function setBrandName( $brand_name ) {
		$this->brand_name = $brand_name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 * @param string $locale
	 */
	public function setLocale( $locale ) {
		$this->locale = $locale;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getLandingPage() {
		return $this->landing_page;
	}

	/**
	 * @param string $landing_page
	 */
	public function setLandingPage( $landing_page ) {
		$this->landing_page = $landing_page;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getShippingPreference() {
		return $this->shipping_preference;
	}

	/**
	 * @param string $shipping_preference
	 */
	public function setShippingPreference( $shipping_preference ) {
		$this->shipping_preference = $shipping_preference;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getContextPreference() {
		return $this->context_preference;
	}

	/**
	 * @param string $context_preference
	 */
	public function setContextPreference( $context_preference ) {
		$this->context_preference = $context_preference;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getPaymentMethodPreference() {
		return $this->payment_method_preference;
	}

	/**
	 * @param string $payment_method_preference
	 */
	public function setPaymentMethodPreference( $payment_method_preference ) {
		$this->payment_method_preference = $payment_method_preference;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getUserAction() {
		return $this->user_action;
	}

	/**
	 * @param string $user_action
	 */
	public function setUserAction( $user_action ) {
		$this->user_action = $user_action;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getReturnUrl() {
		return $this->return_url;
	}

	/**
	 * @param string $return_url
	 */
	public function setReturnUrl( $return_url ) {
		$this->return_url = $return_url;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCancelUrl() {
		return $this->cancel_url;
	}

	/**
	 * @param string $cancel_url
	 */
	public function setCancelUrl( $cancel_url ) {
		$this->cancel_url = $cancel_url;

		return $this;
	}

	/**
	 * @return OrderUpdateCallbackConfig
	 */
	public function getOrderUpdateCallbackConfig() {
		return $this->order_update_callback_config;
	}

	/**
	 * @param OrderUpdateCallbackConfig $order_update_callback_config
	 */
	public function setOrderUpdateCallbackConfig( $order_update_callback_config ) {
		$this->order_update_callback_config = $order_update_callback_config;

		return $this;
	}


}