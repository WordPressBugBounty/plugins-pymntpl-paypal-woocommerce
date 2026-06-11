<?php

namespace PaymentPlugins\WooCommerce\PPCP\Rest\Routes\Admin;

use PaymentPlugins\WooCommerce\PPCP\Utilities\ShippingUtil;

class AdminCarriers extends AbstractRoute {

	public function get_path() {
		return 'order/(?P<order_id>[\w]+)/carriers';
	}

	public function get_routes() {
		return [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_request' ],
				'permission_callback' => [ $this, 'get_admin_permission_check' ],
				'args'                => [
					'country' => [
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					]
				]
			]
		];
	}

	public function handle_get_request( \WP_REST_Request $request ) {
		$country      = strtoupper( $request->get_param( 'country' ) );
		$all_carriers = ShippingUtil::get_carriers();
		$result       = [];

		if ( isset( $all_carriers['global'] ) ) {
			$result['global'] = $all_carriers['global'];
		}

		if ( $country && isset( $all_carriers[ $country ] ) ) {
			$result[ $country ] = $all_carriers[ $country ];
		}

		if ( isset( $all_carriers['other'] ) ) {
			$result['other'] = $all_carriers['other'];
		}

		return $result;
	}

}