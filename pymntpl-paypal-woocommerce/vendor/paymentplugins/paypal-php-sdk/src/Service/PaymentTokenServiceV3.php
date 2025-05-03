<?php

namespace PaymentPlugins\PayPalSDK\Service;

use PaymentPlugins\PayPalSDK\Collection;
use PaymentPlugins\PayPalSDK\PaymentToken;

class PaymentTokenServiceV3 extends BaseService {

	protected $path = 'v3/vault';

	/**
	 * @param $params
	 * @param $options
	 *
	 * @return PaymentToken
	 */
	public function create( $params, $options = [] ) {
		return $this->post( $this->buildPath( '/payment-tokens/' ), PaymentToken::class, $params, $options );
	}

	/**
	 * @param array $params customer_id
	 * @param array $options
	 *
	 * @return mixed|object|void
	 */
	public function all( $params = [], $options = [] ) {
		return $this->get( $this->buildPath( '/payment-tokens' ), \stdClass::class, $params, $options );
	}

	/**
	 * @param $id
	 * @param $options
	 *
	 * @return PaymentToken
	 */
	public function retrieve( $id, $options = [] ) {
		return $this->get( $this->buildPath( '/payment-tokens/%s', $id ), PaymentToken::class, null, $options );
	}

	public function delete( $id, $options = [] ) {
		$this->request( 'DELETE', $this->buildPath( '/payment-tokens/%s', $id ), null, null, $options );
	}

}