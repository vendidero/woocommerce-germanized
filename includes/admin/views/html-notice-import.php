<?php
/**
 * Admin View: Notice - Import
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<div id="message" class="updated woocommerce-message woocommerce-gzd-message wc-connect">
	<h3><strong><?php _e( 'Data import available', 'woocommerce-germanized' ); ?></strong></h3>
	<p><?php echo _e( 'It seems like as if you already had a german market extension for WooCommerce installed. Do you want to import some data? This may take a while depending on the number of products.', 'woocommerce-germanized' ); ?></p>
	<p class="submit">
		<a class="button-primary" href="<?php echo add_query_arg( array( 'import' => 'true', 'nonce' => wp_create_nonce( 'wc-gzd-import' ) ), admin_url( 'admin.php?page=wc-settings&tab=germanized&section' ) ); ?>"><?php _e( 'Import data', 'woocommerce-germanized' );?></a>
		<a class="wc-gzd-skip button-secondary" href="<?php echo add_query_arg( array( 'skip-import' => 'true', 'nonce' => wp_create_nonce( 'wc-gzd-skip-import' ) ), admin_url( 'admin.php?page=wc-settings&tab=germanized&section' ) ); ?>"><?php _e( 'Skip import', 'woocommerce-germanized' ); ?></a>
	</p>
</div>