<?php
/**
 * Checkout service terms and conditions checkbox
 *
 * @author 		vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.7.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p class="form-row data-service terms legal terms-service validate-required">
    <label for="data-service" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
	    <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="service-revocate" id="data-service" />
        <span class="woocommerce-gzd-service-terms-checkbox-text"><?php echo wc_gzd_get_legal_text_service(); ?></span>
    </label>
</p>