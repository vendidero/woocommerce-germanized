<?php
/**
 * Debug/Status page
 *
 * @author      vendidero
 * @category    Admin
 * @package     WooCommerceGermanized/Admin/System Status
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_GZD_Status Class
 */
class WC_GZD_Admin_Status extends WC_Admin_Status {

	public static $tax_tables = array(
		'woocommerce_tax_rates',
		'woocommerce_tax_rate_locations',
	);

	public static function output() {
		include_once 'views/html-page-status-germanized.php';
	}

	public static function status_default( $status ) {
		/**
		 * Admin status screen.
		 *
		 * Executes for a default status page.
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_gzd_status_' . $status );
	}

	public static function get_legal_pages() {

		$return = array();

		$pages = array(
			'terms'           => __( 'Terms & Conditions', 'woocommerce-germanized' ),
			'revocation'      => __( 'Cancellation Policy', 'woocommerce-germanized' ),
			'imprint'         => __( 'Imprint', 'woocommerce-germanized' ),
			'data_security'   => __( 'Privacy Policy', 'woocommerce-germanized' ),
			'payment_methods' => __( 'Payment Methods', 'woocommerce-germanized' ),
			'shipping_costs'  => __( 'Shipping Methods', 'woocommerce-germanized' ),
		);

		foreach ( $pages as $page => $title ) {
			$return[ $page ] = array(
				'title' => $title,
				'id'    => get_option( 'woocommerce_' . $page . '_page_id' ),
			);
		}

		return $return;
	}

	public static function tax_tables_exist() {
		global $wpdb;

		foreach ( self::$tax_tables as $table ) {
			$table_name = "{$wpdb->prefix}{$table}";

			if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s;', $table_name ) ) ) {
				return false;
			}
		}

		return true;
	}

	public static function get_missing_tax_tables() {
		global $wpdb;
		$missing = array();

		foreach ( self::$tax_tables as $table ) {
			$table_name = "{$wpdb->prefix}{$table}";

			if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s;', $table_name ) ) ) {
				array_push( $missing, $table );
			}
		}

		return $missing;
	}
}
