<?php
/**
 * Admin View: Notice - Install
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div id="message" class="updated woocommerce-message woocommerce-gzd-message wc-connect">
	<h3><?php _e( '<strong>Update WooCommerce Germanized</strong>', 'woocommerce-germanized' ); ?></h3>
	<p><?php echo _e( 'If you are selling virtual products to EU countries different from your base country, you will have to charge the customers country VAT rate by 01.01.2015. If you want to install EU VAT rates automatically please choose the option below.', 'woocommerce-germanized' ); ?></p>
	<form name="" method="get">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php _e( 'Generate EU VAT Rates', 'woocommerce-germanized' );?></th>
					<td>
						<label for="install_woocommerce_gzd_tax_rates">
							<input id="install_woocommerce_gzd_tax_rates" type="checkbox" value="true" name="install_woocommerce_gzd_tax_rates">
							<?php _e( 'We will automatically insert EU VAT Rates for selling virtual products.', 'woocommerce-germanized' );?>
						</label>
					</td>
				</tr>
			</tbody>
			<input type="hidden" name="do_update_woocommerce_gzd" value="true" />
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e( 'Update WooCommerce Germanized', 'woocommerce-germanized' );?>" /> </p>
	</form>
</div>