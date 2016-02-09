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

?>

<p class="form-row legal terms wc-terms-and-conditions">
	
	<?php if ( get_option( 'woocommerce_gzd_display_checkout_legal_no_checkbox' ) == 'no' ) : ?>
		<input type="checkbox" class="input-checkbox" name="legal" id="legal" />
	<?php endif; ?>

	<label for="legal" class="checkbox">
		<?php echo wc_gzd_get_legal_text(); ?>
	</label>
	
</p>