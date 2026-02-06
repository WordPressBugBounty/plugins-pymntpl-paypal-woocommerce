<?php
/**
 *
 */
?>
<div class="wc-ppcp_googlepay-payment-method__container wc-payment-form">
    <div class="wc-ppcp_googlepay-order-review-message__container" style="display: none">
        <div class="wc-ppcp_googlepay-order-review__message">
			<?php esc_html_e( 'Your Google Pay payment method is ready to be processed. Please review your order details then click %s',
				'pymntpl-paypal-woocommerce' ) ?>
        </div>
        <a href="#"
           class="wc-ppcp_googlepay-cancel__payment"><?php esc_html_e( 'Cancel', 'pymntpl-paypal-woocommerce' ) ?></a>
    </div>
</div>
