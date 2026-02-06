<?php

namespace PaymentPlugins\WooCommerce\PPCP\Assets;

/**
 * Simple data storage for asset-related data.
 * This class has one responsibility: store and retrieve data.
 */
class AssetDataApi {

	private $data = [];

	public function add( $key, $data ) {
		$this->data[ $key ] = $data;
	}

	public function get( $key ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
	}

	public function remove( $key ) {
		unset( $this->data[ $key ] );
	}

	public function get_data() {
		return $this->data;
	}

	public function has_data() {
		return ! empty( $this->data );
	}

	public function exists( $key ) {
		return \array_key_exists( $key, $this->data );
	}

	/**
	 * @param $data
	 * @param $name
	 *
	 * @return void
	 * @deprecated
	 */
	public function print_script_data( $data, $name ) {

	}

	public function print_data( $name, $data ) {
		$data = rawurlencode( wp_json_encode( $data ) );
		echo "<script id=\"$name\">
				window['$name'] = JSON.parse( decodeURIComponent( '" . esc_js( $data ) . "' ) );
		</script>";
	}

}