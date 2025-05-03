<?php
/**
 *
 */
?>
<label class="ppcp-save-card-checkbox-container woocommerce-SavedPaymentMethods-saveNew">
    <input type="checkbox" name="<?php echo esc_attr( $name ) ?>"/>
	<?php wc_ppcp_load_template( 'icons/checkmark.php' ) ?>
    <span>
		<?php esc_html_e( 'Save payment information to my account for future purchases.', 'pymntpl-paypal-woocommerce' ) ?>
	</span>
</label>
