<?php

namespace PaymentPlugins\PPCP\WooCommerceSubscriptions;

use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;

class FrontendScripts {

	private $assets;

	public function __construct( AssetsApi $assets ) {
		$this->assets = $assets;
	}

	public function initialize() {
		// Enqueued unconditionally whenever other PPCP scripts are loading (so it's already scoped
		// to pages with a PPCP button, and this whole package only loads at all when WC
		// Subscriptions is active - see Package::is_active()). The script's own filter callbacks
		// are no-ops on every page/product that isn't a $0-trial subscription anyway, since
		// recurringTotal is only ever present on cart data in that scenario - see
		// SubscriptionController::add_recurring_total_to_cart_data().
		add_filter( 'wc_ppcp_script_dependencies', function ( array $handles ) {
			if ( ! empty( $handles ) ) {
				$handles[] = 'wc-ppcp-subscriptions-checkout';
			}

			return $handles;
		}, 10 );

		// Blocks Cart/Checkout equivalent of the filter above - fired only while the respective
		// block is actually rendering, so no separate "is this the block" check is needed.
		add_action( 'woocommerce_blocks_enqueue_checkout_block_scripts_after', [ $this, 'enqueue_blocks_script' ] );
		add_action( 'woocommerce_blocks_enqueue_cart_block_scripts_after', [ $this, 'enqueue_blocks_script' ] );

		$this->register_scripts();
	}

	public function enqueue_blocks_script() {
		wp_enqueue_script( 'wc-ppcp-subscriptions-blocks-checkout' );
		$this->dequeue_classic_script_on_render();
	}

	/**
	 * The classic script can still end up enqueued alongside the blocks one - e.g. on order/pay
	 * (Change Payment Method), which PaymentGateways::enqueue_payment_scripts() never excludes
	 * from the classic wc_ppcp_script_dependencies path even when that page is rendered via the
	 * Checkout block. Only one should ever run, since they register the same filters against
	 * different (classic vs blocks) gateway implementations. Mirrors the equivalent fix in the
	 * woo-stripe-payment plugin's subscriptions package.
	 *
	 * Block themes render the block's callback (and thus this method) before wp_enqueue_scripts
	 * fires, so the dequeue must be deferred to a later priority on that same hook. Classic themes
	 * that embed the block inline via the_content() render it after wp_enqueue_scripts has already
	 * fired (and after the classic script was already enqueued) - deferring there would never
	 * fire, so dequeue immediately instead.
	 */
	private function dequeue_classic_script_on_render() {
		if ( did_action( 'wp_enqueue_scripts' ) ) {
			wp_dequeue_script( 'wc-ppcp-subscriptions-checkout' );
		} else {
			add_action( 'wp_enqueue_scripts', function () {
				wp_dequeue_script( 'wc-ppcp-subscriptions-checkout' );
			}, 20 );
		}
	}

	private function register_scripts() {
		$this->assets->register_script( 'wc-ppcp-subscriptions-checkout', 'build/checkout.js' );
		$this->assets->register_script( 'wc-ppcp-subscriptions-blocks-checkout', 'build/blocks-checkout.js' );
	}

}