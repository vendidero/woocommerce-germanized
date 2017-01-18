<?php
/**
 * Checkout terms and conditions checkbox
 *
 * @author 		vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.6.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Make sure only template calls from germanized are accepted
if ( ! isset( $gzd_checkbox ) || ! $gzd_checkbox )
	return;

?>

<?php if ( apply_filters( 'woocommerce_germanized_checkout_show_terms', true ) ) : ?>

<p class="form-row legal terms wc-terms-and-conditions">
	<?php if ( get_option( 'woocommerce_gzd_display_checkout_legal_no_checkbox' ) === 'no' ) : ?>
		<input type="checkbox" class="input-checkbox" name="legal" id="legal" />
	<?php endif; ?>
	<label for="legal" class="woocommerce-form__label woocommerce-form__label--for-checkbox checkbox"><?php echo wc_gzd_get_legal_text(); ?></label>
</p>

<?php endif; ?>