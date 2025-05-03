<?php

namespace PaymentPlugins\WooCommerce\PPCP;

use PaymentPlugins\WooCommerce\PPCP\Admin\Settings\APISettings;

class Customer {

	private $id;

	private $user_id;

	private $environment;

	public function __construct( $user_id, $environment = 'production' ) {
		$this->user_id     = $user_id;
		$this->environment = $environment;
	}

	public static function instance( $user_id, $environment = null ) {
		if ( ! $environment ) {
			/**
			 * @param APISettings $api_settings
			 */
			$api_settings = wc_ppcp_get_container()->get( APISettings::class );

			$environment = $api_settings->get_environment();
		}

		return new self( $user_id, $environment );
	}

	public function set_id( $value ) {
		$this->id = $value;
	}

	public function set_user_id( $value ) {
		$this->user_id = $value;
	}

	/**
	 * Returns the PayPal ID for the customer
	 *
	 * @return mixed
	 */
	public function get_id() {
		if ( ! $this->id && $this->get_user_id() ) {
			$this->id = get_user_option( $this->get_option_key(), $this->get_user_id() );
		}

		return $this->id;
	}

	/**
	 * Returns the WordPress user ID for the customer
	 *
	 * @return mixed
	 */
	public function get_user_id() {
		return $this->user_id;
	}

	public function save() {
		if ( $this->get_id() && $this->get_user_id() ) {
			update_user_option( $this->get_user_id(), $this->get_option_key(), $this->get_id() );
		}
	}

	private function get_option_key() {
		return sprintf( 'ppcp_customer_id_%s', $this->environment );
	}

	private function is_production() {
		return $this->environment === Constants::PRODUCTION;
	}

	public function has_id() {
		return ! ! $this->get_id();
	}

	public function try_migration() {
		if ( ! $this->get_id() && $this->get_user_id() ) {
			$this->id = apply_filters( 'wc_ppcp_get_customer_id', $this->id, $this );
			if ( $this->id ) {
				$this->save();

				return true;
			}
		}

		return false;
	}

}