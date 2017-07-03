<?php
/**
 * Checkout SEPA terms checkbox
 *
 * @author 		vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.6.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p class="form-row legal direct-debit-checkbox">
	<input type="checkbox" class="input-checkbox" name="direct_debit_legal" id="direct-debit-checkbox" />
	<label class="checkbox" for="direct-debit-checkbox">
		<?php echo $checkbox_label; ?>
		<a href="" rel="prettyPhoto" id="show-direct-debit-pretty" class="hidden"></a>
	</label>
</p>