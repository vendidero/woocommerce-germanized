<?php
/**
 * Admin View: Notice - Review
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$dismiss_url = add_query_arg( 'notice', 'wc-gzd-hide-review-notice', add_query_arg( 'nonce', wp_create_nonce( 'wc-gzd-hide-review-notice' ) ) );
?>

<div class="updated fade">
	<h3><?php _e( 'Do you like WooCommerce Germanized?', 'woocommerce-germanized' ); ?></h3>
	<p>
		<?php _e( 'If you like WooCommerce Germanized and our Plugin does a good job it would be great if you would write a review about WooCommerce Germanized on WordPress.org. Thank you for your support!', 'woocommerce-germanized' ); ?>
	</p>
	<p class="alignleft wc-gzd-button-wrapper">
		<a class="button button-primary" href="https://wordpress.org/support/view/plugin-reviews/woocommerce-germanized?rate=5#postform" target="_blank"><?php _e( 'Write review now', 'woocommerce-germanized' );?></a>
		<a class="button button-secondary" href="https://wordpress.org/support/plugin/woocommerce-germanized" target="_blank"><?php _e( 'Found Bugs?', 'woocommerce-germanized' );?></a>
	</p>
	<p class="alignright">
		<a href="<?php echo esc_url( $dismiss_url );?>"><?php _e( 'Hide this notice', 'woocommerce-germanized' ); ?></a>
	</p>
	<div class="clear"></div>
</div>