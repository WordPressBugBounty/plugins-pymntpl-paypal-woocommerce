<?php

namespace PaymentPlugins\WooCommerce\PPCP\Cache;

class CacheHandler implements CacheInterface {

	private $key;

	private $data = [];

	public function __construct( $key ) {
		$this->key = $key;
		$session   = $this->get_session();
		if ( $session ) {
			$this->data = $session->get( $this->key, [] );
		}
		$this->initialize();
	}

	public function initialize() {
		add_action( 'wc_ppcp_order_payment_complete', [ $this, 'clear_cache' ] );
	}

	public function set( $key, $value ) {
		$this->data[ $key ] = $value;
		$this->stash();
	}

	public function get( $key ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
	}

	public function delete( $key ) {
		unset( $this->data[ $key ] );
		$this->stash();
	}

	public function exists( $key ) {
		return isset( $this->data[ $key ] );
	}

	public function clear_cache() {
		$session = $this->get_session();
		if ( $session ) {
			unset( $session->{$this->key} );
		}
		$this->data = [];
	}

	private function stash() {
		$session = $this->get_session();
		if ( $session && ! empty( $this->data ) ) {
			$session->set( $this->key, $this->data );
		}
	}

	/**
	 * Always reads WC()->session fresh rather than caching a reference to it - this class is
	 * registered as a container singleton (constructed once per request), but WC()->session can
	 * itself be reassigned to a different object instance mid-request (e.g. WooCommerce's Store
	 * API swaps it to a separate, token-based session handler for /wc/store/* requests, which the
	 * Checkout Block's own checkout-completion request is). Caching the reference at construction
	 * time meant every operation after such a swap silently wrote to/cleared an orphaned session
	 * object that never actually gets persisted, instead of the one WordPress/WooCommerce
	 * actually saves on shutdown.
	 *
	 * @return \WC_Session|null
	 */
	private function get_session() {
		return WC()->session;
	}

}