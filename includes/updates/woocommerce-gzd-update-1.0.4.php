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

WC_GZD_Install::create_tax_rates();
