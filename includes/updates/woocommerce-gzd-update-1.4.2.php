<?php
/**
 * Update WC to 2.0.9
 *
 * @author        WooThemes
 * @category    Admin
 * @package    WooCommerce/Admin/Updates
 * @version     2.0.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb, $woocommerce_germanized;

if ( get_option( 'woocommerce_gzd_trusted_review_reminder_days' ) ) {
	update_option( 'woocommerce_gzd_trusted_shops_review_reminder_days', get_option( 'woocommerce_gzd_trusted_review_reminder_days' ) );
	delete_option( 'woocommerce_gzd_trusted_review_reminder_days' );
}
