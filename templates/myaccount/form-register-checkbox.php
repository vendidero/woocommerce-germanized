<?php
/**
 * 
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>

<p class="form-row form-row-wide">
	<input type="checkbox" class="input-checkbox" value="1" name="privacy" id="reg_data_privacy" />
	<label for="reg_data_privacy" class="inline"><?php echo wc_gzd_get_legal_text( get_option( 'woocommerce_gzd_customer_account_text' ) ); ?><span class="required">*</span></label>
</p>