<?php

namespace PaymentPlugins\PPCP\CheckoutWC;

class PaymentGatewaysController extends \Objectiv\Plugins\Checkout\Compatibility\CompatibilityAbstract {

	/**
	 * @var \PaymentPlugins\WooCommerce\PPCP\Payments\PaymentGateways
	 */
	private $payment_gateways;

	public function set_payment_gateways( $payment_gateways ) {
		$this->payment_gateways = $payment_gateways;
	}

	public function is_available(): bool {
		return class_exists( '\PaymentPlugins\WooCommerce\PPCP\Main' );
	}

	public function run() {
		$this->payment_gateways->get_payment_method_registry()->initialize();

		if ( $this->is_express_enabled() ) {
			add_action( 'cfw_payment_request_buttons', [ $this, 'output_express_button' ] );
			wc_ppcp_get_container()->get( \PaymentPlugins\WooCommerce\PPCP\PaymentButtonController::class )->set_render_express_buttons( false );
		}
	}

	private function is_express_enabled(): bool {
		$gateways = $this->payment_gateways->get_express_payment_gateways();

		return count( $gateways ) > 0;
	}

	public function output_express_button() {
		?>
        <ul class="wc-ppcp-checkoutwc-express__container">
			<?php
			foreach ( $this->payment_gateways->get_express_payment_gateways() as $payment_gateway ) {
				?>
                <li class="wc-<?php echo esc_attr( $payment_gateway->id ) ?>-checkoutwc-express__payment <?php echo esc_attr( $payment_gateway->id ) ?>">
					<?php if ( $payment_gateway->id === 'ppcp_card' ): ?>
						<?php $payment_gateway->express_checkout_fields() ?>
					<?php else: ?>
                        <div id="wc-<?php echo esc_attr( $payment_gateway->id ) ?>-express-button"></div>
					<?php endif; ?>
                </li>
			<?php } ?>
        </ul>
		<?php
	}
}