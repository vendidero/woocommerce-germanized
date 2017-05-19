<?php
/**
 * Checkout parcel delivery terms checkbox
 *
 * @author 		vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.6.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p class="form-row data-parcel-delivery terms legal" style="<?php echo ( ! $show ) ? 'display: none;' : ''; ?>">
	<input type="checkbox" class="input-checkbox" name="parcel-delivery" id="parcel-delivery-checkbox" />
	<label for="parcel-delivery-checkbox" class="checkbox"><?php echo wc_gzd_get_legal_text_parcel_delivery( $titles ); ?></label>
</p>