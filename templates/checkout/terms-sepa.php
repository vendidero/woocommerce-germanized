<?php
/**
 * Checkout SEPA terms checkbox
 *
 * @author 		vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.7.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p class="form-row legal direct-debit-checkbox terms-sepa validate-required">
	<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="direct-debit-checkbox">
        <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="direct_debit_legal" id="direct-debit-checkbox" />
        <span class="woocommerce-gzd-sepa-terms-checkbox-text"><?php echo $checkbox_label; ?></span>
        <a href="" rel="prettyPhoto" id="show-direct-debit-pretty" class="hidden"></a>
	</label>
</p>