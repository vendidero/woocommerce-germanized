<?php
/**
 * Checkout digital terms and conditions checkbox
 *
 * @author 		vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.6.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p class="form-row data-download terms legal">
	<input type="checkbox" class="input-checkbox" name="download-revocate" id="data-download" />
	<label for="data-download" class="checkbox"><?php echo wc_gzd_get_legal_text_digital(); ?></label>
</p>