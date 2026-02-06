<?php
/**
 *
 */
if ( $gateway->supports( 'vault' ) && is_checkout() ) {
	$gateway->saved_payment_methods();
}
?>
<div class="wc-ppcp_applepay-payment-method__container wc-payment-form">
    <div class="wc-ppcp_applepay-order-review-message__container" style="display: none">
        <div class="wc-ppcp_applepay-order-review__message">
			<?php esc_html_e( 'Your Apple Pay payment method is ready to be processed. Please review your order details then click %s',
				'pymntpl-paypal-woocommerce' ) ?>
        </div>
        <a href="#"
           class="wc-ppcp_applepay-cancel__payment"><?php esc_html_e( 'Cancel', 'pymntpl-paypal-woocommerce' ) ?></a>
    </div>
</div>
