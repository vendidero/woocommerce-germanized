<?php
/**
 * WooCommerce GZD Uninstall
 *
 * Uninstalling WooCommerce Germanized deletes pages, tables, and options.
 *
 * @author      WooThemes
 * @category    Core
 * @package     WooCommerce/Uninstaller
 * @version     2.3.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb, $wp_version;

wp_clear_scheduled_hook( 'woocommerce_gzd_customer_cleanup' );
wp_clear_scheduled_hook( 'woocommerce_gzd_trusted_shops_reviews' );
wp_clear_scheduled_hook( 'woocommerce_gzd_ekomi' );

if ( defined( 'WC_GZD_REMOVE_ALL_DATA' ) && true === WC_GZD_REMOVE_ALL_DATA ) {

	include_once( 'includes/class-wc-gzd-install.php' );

	// Delete digital rates
	$wpdb->delete( $wpdb->prefix . 'woocommerce_tax_rates', array( 'tax_rate_class' => 'virtual-rate' ), array( '%s' ) );

	// delete pages
	wp_trash_post( get_option( 'woocommerce_revocation_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_data_security_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_imprint_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_terms_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_shipping_costs_page_id' ) );
	wp_trash_post( get_option( 'woocommerce_payment_methods_page_id' ) );

	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce_gzd\_%';" );
	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'wc_gzd\_%';" );

	$meta_keys = array(
		'_unit_base',
		'_unit_product',
		'_unit_price_auto',
		'_unit_price_regular',
		'_unit_price',
		'_unit_price_sale',
		'_mini_desc',
		'_free_shipping',
		'_unit',
		'_wc_gzd_ekomi_added',
		'_service',
		'_sale_price_label',
		'_sale_price_regular_label',
		'_legal_text',
		'_trusted_shops_review_mail_sent',
		'_direct_debit_bic',
		'_direct_debit_iban',
		'_direct_debit_holder',
		'_parcel_delivery_opted_in',
		'_shipping_parcelshop_post_number',
		'_shipping_parcelshop',
		'_shipping_title',
		'_billing_title',
		'_woocommerce_activation'
	);

	// Delete gzd meta data
	$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta WHERE meta.meta_key IN ('".join("','", $meta_keys)."');" );

	// Delete ekomi data
	$user_id = get_user_by( 'email', 'ekomi@loremipsumdolorom.com' );

	if ( $user_id ) {
		$wpdb->query( "DELETE meta FROM {$wpdb->commentmeta} LEFT JOIN {$wpdb->comments} comments ON comments.comment_ID = meta.comment_id WHERE user_id = {$user_id};" );
		$wpdb->query( "DELETE comments FROM {$wpdb->comments} WHERE user_id = {$user_id};" );
		wp_delete_user( $user_id );
	}

	// Delete terms if > WP 4.2 (term splitting was added in 4.2)
	if ( version_compare( $wp_version, '4.2', '>=' ) ) {
		// Delete term taxonomies
		foreach ( array( 'product_delivery_time', 'product_unit', 'product_price_label' ) as $taxonomy ) {
			$wpdb->delete(
				$wpdb->term_taxonomy,
				array(
					'taxonomy' => $taxonomy,
				)
			);
		}

		// Delete orphan relationships
		$wpdb->query( "DELETE tr FROM {$wpdb->term_relationships} tr LEFT JOIN {$wpdb->posts} posts ON posts.ID = tr.object_id WHERE posts.ID IS NULL;" );

		// Delete orphan terms
		$wpdb->query( "DELETE t FROM {$wpdb->terms} t LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE tt.term_id IS NULL;" );

		// Delete orphan term meta
		if ( ! empty( $wpdb->termmeta ) ) {
			$wpdb->query( "DELETE tm FROM {$wpdb->termmeta} tm LEFT JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id WHERE tt.term_id IS NULL;" );
		}
	}

	// Clear any cached data that has been removed
	wp_cache_flush();
}
