<?php


namespace PaymentPlugins\WooCommerce\PPCP\Container;


abstract class AbstractResolver {

	private $callback;

	protected $singleton;

	public function __construct( $value, $singleton ) {
		$this->callback  = $value;
		$this->singleton = $singleton;
	}

	public function resolve( $container ) {
		$callback = $this->callback;

		return \is_callable( $callback ) ? $callback( $container ) : $this->callback;
	}

	public abstract function get( $container );

}