<?php

namespace PaymentPlugins\PayPalSDK\Service;

use PaymentPlugins\PayPalSDK\SetupToken;

class SetupTokenService extends BaseService {

	protected $path = 'v3/vault';

	/**
	 * @param $params
	 * @param $options
	 *
	 * @return SetupToken
	 */
	public function create( $params, $options = array() ) {
		return $this->post( $this->buildPath( '/setup-tokens' ), SetupToken::class, $params, $options );
	}

	/**
	 * @param $id
	 * @param $options
	 *
	 * @return SetupToken
	 */
	public function retrieve( $id, $options = array() ) {
		return $this->get( $this->buildPath( '/setup-tokens/%s', $id ), SetupToken::class, null, $options );
	}
}