<?php

namespace PaymentPlugins\PPCP\FunnelKit\Checkout;

use PaymentPlugins\PPCP\FunnelKit\Checkout\Compatibility\PayPal;
use PaymentPlugins\PPCP\FunnelKit\Checkout\Compatibility\GooglePay;
use PaymentPlugins\PPCP\FunnelKit\Checkout\Compatibility\ApplePay;
use PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi;
use PaymentPlugins\WooCommerce\PPCP\Main;
use PaymentPlugins\WooCommerce\PPCP\PaymentButtonController;
use PaymentPlugins\WooCommerce\PPCP\Payments\Gateways\PayPalGateway;

class ExpressIntegration {

	private $id = 'paymentplugins_wc_ppcp';

	private $settings;

	private $assets;

	/**
	 * @var \PaymentPlugins\PPCP\FunnelKit\Checkout\Compatibility\AbstractGateway[]
	 */
	private $payment_gateways = [];

	public function __construct( AssetsApi $assets ) {
		$this->assets = $assets;
		$this->initialize();
	}

	protected function initialize() {
		add_action( 'wfacp_after_checkout_page_found', [ $this, 'handle_checkout_page_found' ] );
		add_filter( 'wfacp_smart_buttons', [ $this, 'add_buttons' ], 20 );
		add_filter( 'wfacp_smart_button_hide_timeout', function ( $timeout ) {
			if ( $this->has_express_buttons() ) {
				$timeout = 100;
			}

			return $timeout;
		} );
		add_filter( 'wfacp_template_localize_data', function ( $data ) {
			if ( $this->has_express_buttons() ) {
				$data['smart_button_wrappers']['no_conflict_buttons'] = array_merge(
					$data['smart_button_wrappers']['no_conflict_buttons'],
					[
						'#wfacp_smart_button_ppcp',
						'#wfacp_smart_button_ppcp_googlepay',
						'#wfacp_smart_button_ppcp_applepay'
					]
				);
			}

			return $data;
		} );
		foreach ( $this->get_payment_gateways() as $gateway ) {
			add_action( 'wfacp_smart_button_container_' . $gateway->get_id(), function () use ( $gateway ) {
				$this->render_express_buttons( $gateway );
			} );
		}
	}

	public function handle_checkout_page_found() {
		$this->settings = \WFACP_Common::get_page_settings( \WFACP_Common::get_id() );
		if ( $this->has_express_buttons() ) {
			$handles = wc_ppcp_get_container()->get( PayPalGateway::class )->get_express_checkout_script_handles();
			if ( ! empty( $handles ) ) {
				foreach ( $handles as $handle ) {
					wp_enqueue_script( $handle );
				}
				$this->assets->enqueue_script( 'wc-ppcp-funnelkit-checkout', 'build/wc-ppcp-funnelkit-checkout.js' );
			}
		}
	}

	private function has_express_buttons() {
		foreach ( $this->get_payment_gateways() as $gateway ) {
			if ( $gateway->is_active() && $gateway->is_express_enabled() ) {
				return true;
			}
		}

		return false;
	}

	private function get_payment_gateways() {
		$this->initialize_gateways();

		return $this->payment_gateways;
	}

	private function initialize_gateways() {
		if ( empty( $this->payment_gateways ) ) {
			$payment_methods = WC()->payment_gateways()->payment_gateways();
			$classes         = [
				'ppcp'           => PayPal::class,
				'ppcp_googlepay' => GooglePay::class,
				'ppcp_applepay'  => ApplePay::class
			];
			foreach ( $classes as $id => $clazz ) {
				if ( isset( $payment_methods[ $id ] ) ) {
					$this->payment_gateways[ $id ] = new $clazz( $payment_methods[ $id ] );
				}
			}
		}
	}

	public function add_buttons( $buttons ) {
		if ( $this->has_express_buttons() ) {
			remove_action( 'woocommerce_checkout_before_customer_details', [
				wc_ppcp_get_container()->get( PaymentButtonController::class ),
				'render_express_buttons'
			] );
			foreach ( $this->get_payment_gateways() as $gateway ) {
				$buttons[ $gateway->get_id() ] = [
					'iframe' => true
				];
			}
		}

		return $buttons;
	}

	public function render_express_buttons( $gateway ) {
		?>
        <div id="wc-<?php echo \esc_attr( $gateway->get_id() ) ?>-express-button" class="wc-ppcp-express-button"></div>
		<?php
	}

}