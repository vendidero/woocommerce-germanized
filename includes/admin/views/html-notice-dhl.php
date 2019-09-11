<?php
/**
 * Importer notice.
 *
 * @package WooCommerce_Germanized/DHL/Admin
 */
defined( 'ABSPATH' ) || exit;
?>

<div id="message" class="updated woocommerce-gzd-message woocommerce-gzd-dhl-message">
	<h3><?php _e( 'DHL built-in Integration', 'woocommerce-germanized-dhl' ); ?></h3>

	<p>
		<?php _e( 'It seems like you are currently using the DHL for WooCommerce plugin. Germanized does now fully integrate DHL services and switching is as simple as can be. Check your advantages by using the DHL integration in Germanized and let Germanized import your current settings for you.', 'woocommerce-germanized-dhl' ); ?>
	</p>

	<ul>
		<li><?php _e( 'No need to use an external plugin which might lead to incompatibilities', 'woocommerce-germanized-dhl' ); ?></li>
		<li><?php _e( 'Perfectly integrated in Germanized &ndash; easily create labels for shipments', 'woocommerce-germanized-dhl' ); ?></li>
	</ul>

	<p class="submit">
		<a class="button button-secondary" href="" target="_blank"><?php esc_html_e( 'Learn more', 'woocommerce-germanized-dhl' ); ?></a>
		<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-gzd-dhl-import', 'yes' ), 'woocommerce_gzd_dhl_import_nonce' ) ); ?>"><?php esc_html_e( 'Import settings and activate', 'woocommerce-germanized-dhl' ); ?></a>
	</p>
</div>