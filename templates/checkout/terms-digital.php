<?php
/**
 * Checkout digital terms and conditions checkbox
 *
 * @author 		vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.7.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p class="form-row data-download terms legal terms-digital">
	<label for="data-download" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
        <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="download-revocate" id="data-download" />
        <span class="woocommerce-gzd-download-terms-checkbox-text"><?php echo wc_gzd_get_legal_text_digital(); ?></span>
    </label>
</p>