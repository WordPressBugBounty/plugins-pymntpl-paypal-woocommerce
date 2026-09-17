<?php

namespace PaymentPlugins\WooCommerce\PPCP\Cache;

class CacheHandler implements CacheInterface {

	private $key;

	public function __construct( $key ) {
		$this->key = $key;
		$this->initialize();
	}

	public function initialize() {
		add_action( 'wc_ppcp_order_payment_complete', [ $this, 'clear_cache' ] );
	}

	public function set( $key, $value ) {
		$data         = $this->get_data();
		$data[ $key ] = $value;
		$this->stash( $data );
	}

	public function get( $key ) {
		$data = $this->get_data();

		return isset( $data[ $key ] ) ? $data[ $key ] : null;
	}

	public function delete( $key ) {
		$data = $this->get_data();
		unset( $data[ $key ] );
		$this->stash( $data );
	}

	public function exists( $key ) {
		$data = $this->get_data();

		return isset( $data[ $key ] );
	}

	public function clear_cache() {
		$session = $this->get_session();
		if ( $session ) {
			unset( $session->{$this->key} );
		}
	}

	/**
	 * Reads the current data straight from WC()->session on every call rather than from a value
	 * snapshotted in the constructor. This class is a container singleton (built once per request),
	 * and it can be constructed before WC()->session exists - e.g. when a 3rd party plugin
	 * instantiates a gateway during plugins_loaded, or before WooCommerce swaps in its token-based
	 * Store API session handler for /wc/store/* requests (Checkout Block completion). A snapshot
	 * taken then left get()/exists() reading stale (often empty) data for the rest of the request.
	 *
	 * @return array
	 */
	private function get_data() {
		$session = $this->get_session();

		return $session ? (array) $session->get( $this->key, [] ) : [];
	}

	private function stash( $data ) {
		$session = $this->get_session();
		if ( $session ) {
			$session->set( $this->key, $data );
		}
	}

	private function get_session() {
		return WC()->session;
	}

}