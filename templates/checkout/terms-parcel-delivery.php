<?php
/**
 * Checkout parcel delivery terms checkbox
 *
 * @author 		vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.7.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p class="form-row data-parcel-delivery terms legal terms-parcel-delivery <?php echo ( get_option( 'woocommerce_gzd_checkout_legal_parcel_delivery_checkbox_required' ) === 'yes' ? 'validate-required' : '' ); ?>" style="<?php echo ( ! $show ) ? 'display: none;' : ''; ?>">
    <label for="parcel-delivery-checkbox" class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
	    <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="parcel-delivery" id="parcel-delivery-checkbox" />
        <span class="woocommerce-gzd-parcel-delivery-terms-checkbox-text"><?php echo wc_gzd_get_legal_text_parcel_delivery( $titles ); ?></span>
    </label>
</p>