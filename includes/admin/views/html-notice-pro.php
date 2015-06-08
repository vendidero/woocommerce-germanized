<?php
/**
 * Admin View: Notice - Review
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$dismiss_url = add_query_arg( 'notice', 'wc-gzd-hide-pro-notice', add_query_arg( 'nonce', wp_create_nonce( 'wc-gzd-hide-pro-notice' ) ) );
?>

<div class="updated fade">
	<h3><?php _e( 'For professionals: Upgrade to Pro-Version', 'woocommerce-germanized' ); ?></h3>
	<p>
		<?php _e( 'Do you enjoy WooCommerce Germanized? Do you want to benefit from even more and better features? You may consider an uprade to Pro. Check out some of the main Pro features:', 'woocommerce-germanized' ); ?>
	</p>
	<ul>
		<li>✓ <?php _e( 'PDF invoices and packing slips', 'woocommerce-germanized' ); ?></li>
		<li>✓ <?php _e( 'Generator for terms & conditions and right of recission', 'woocommerce-germanized' ); ?></li>
		<li>✓ <?php _e( 'Multistep Checkout', 'woocommerce-germanized' ); ?></li>
		<li>✓ <strong><?php _e( 'Premium Ticket Support', 'woocommerce-germanized' ); ?></strong></li>
	</ul>
	<p class="alignleft wc-gzd-button-wrapper">
		<a class="button button-primary" href="https://vendidero.de/woocommerce-germanized" target="_blank"><?php _e( 'Learn more about Pro Version', 'woocommerce-germanized' );?></a>
	</p>
	<p class="alignright">
		<a href="<?php echo esc_url( $dismiss_url );?>"><?php _e( 'Hide this notice', 'woocommerce-germanized' ); ?></a>
	</p>
	<div class="clear"></div>
</div>