<?php
/**
 * Checkout service terms and conditions checkbox
 *
 * @author 		vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.6.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p class="form-row data-service terms legal">
	<input type="checkbox" class="input-checkbox" name="service-revocate" id="data-service" />
	<label for="data-service" class="checkbox"><?php echo wc_gzd_get_legal_text_service(); ?></label>
</p>