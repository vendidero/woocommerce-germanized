<?php
/**
 * Admin View: Settings pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $current_section;
?>
<style>
	.wc-gzd-admin-settings {
		border-right: none;
	}
</style>

<h3><?php echo $section_title; ?></h3>

<div class="wc-gzd-premium">
	<div class="wc-gzd-premium-overlay-wrapper">
		<div class="wc-gzd-premium-overlay notice notice-warning inline">
			<h3><?php _e( 'Get WooCommerce Germanized Pro to unlock', 'woocommerce-germanized' );?></h3>
			<p><?php _e( 'Enjoy even more professional features such as invoices, legal text generators, B2B VAT settings and premium support!', 'woocommerce-germanized' );?></p>
			<p><a class="button button-primary" href="https://vendidero.de/woocommerce-germanized" target="_blank"><?php _e( 'Upgrade now', 'woocommerce-germanized' ); ?></a></p>
		</div>
		<a href="https://vendidero.de/woocommerce-germanized" target="_blank">
			<img src="<?php echo WC_Germanized()->plugin_url();?>/assets/images/pro/settings-<?php echo $current_section;?>.png?v=<?php echo WC_germanized()->version;?>" />
		</a>
	</div>
</div>