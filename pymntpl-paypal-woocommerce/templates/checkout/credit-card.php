<?php
/**
 *
 */

if ( $gateway->supports( 'vault' ) && is_checkout() ) {
	$gateway->saved_payment_methods();
}
do_action( 'wc_ppcp_before_card_container', $gateway );
?>
<div class="wc-payment-form wc-ppcp-card-payment-form">
    <div class="wc-ppcp-card-payment-method__container">
		<?php if ( $gateway->is_cardholder_name_enabled() ): ?>
            <div class="ppcp-card-fields--container card-name-field">
                <div class="ppcp-card-field--container card-name-field">
                    <label class="ppcp-card-field--label">
						<?php esc_html_e( 'Cardholder name', 'pymntpl-paypal-woocommerce' ) ?>
                    </label>
                    <div id="ppcp-card-name"></div>
                </div>
            </div>
		<?php endif; ?>
        <div class="ppcp-card-fields--container">
            <div class="ppcp-card-field--container card-number-field">
                <label class="ppcp-card-field--label">
					<?php esc_html_e( 'Card number', 'pymntpl-paypal-woocommerce' ) ?>
                </label>
                <div id="ppcp-card-number"></div>
                <div class="ppcp-card-field-error" data-parent="ppcp-card-number"></div>
            </div>
            <div class="ppcp-card-field--container card-exp-field">
                <label class="ppcp-card-field--label">
					<?php esc_html_e( 'Expiration date', 'pymntpl-paypal-woocommerce' ) ?>
                </label>
                <div id="ppcp-card-exp"></div>
                <div class="ppcp-card-field-error" data-parent="ppcp-card-exp"></div>
            </div>
            <div class="ppcp-card-field--container card-cvv-field">
                <label class="ppcp-card-field--label">
					<?php esc_html_e( 'Security code', 'pymntpl-paypal-woocommerce' ) ?>
                </label>
                <div id="ppcp-card-cvv">
                    <div class="ppcp-card-cvv-icon">
						<?php wc_ppcp_load_template( 'icons/cvv-icon.php' ) ?>
                    </div>
                </div>
                <div class="ppcp-card-field-error" data-parent="ppcp-card-cvv"></div>
            </div>
        </div>
    </div>
	<?php if ( $gateway->show_card_save_checkbox() ): ?>
        <div class="wc-ppcp-save-payment-method--container">
			<?php wc_ppcp_load_template( 'checkout/save-card-checkbox.php', [ 'name' => sprintf( '%1$s_save_payment', $gateway->id ) ] ) ?>
        </div>
	<?php endif; ?>
</div>
