<?php

namespace PaymentPlugins\WooCommerce\PPCP;

class ClientRequestRetryController {

	public function initialize() {
		add_filter( 'wc_ppcp_client_request_retry', [ $this, 'process_retry' ], 10, 3 );
	}

	/**
	 * @param bool                                             $bool
	 * @param \PaymentPlugins\PayPalSDK\Exception\ApiException $exception
	 * @param array                                            $args
	 *
	 * @return mixed
	 */
	public function process_retry( $bool, $exception, $args ) {
		list( $method, $path, $response_class, $params, $options ) = $args;

		switch ( $path ) {
			case 'v2/checkout/orders':
				$data = $exception->getData();
				if ( is_array( $data ) && isset( $data['details'][0]['field'] ) ) {
					if ( $data['details'][0]['field'] === '/application_context/locale' ) {
						unset( $params['application_context']['locale'] );
						$bool = $params;
					}
				}
				break;
		}

		return $bool;
	}

}