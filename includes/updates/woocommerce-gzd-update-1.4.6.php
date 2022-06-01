<?php
/**
 * Update Germanized to 1.4.6
 *
 * @author        WooThemes
 * @category    Admin
 * @package    WooCommerce Germanized/Updates
 * @version     1.4.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( 'yes' === get_option( 'woocommerce_gzd_enable_virtual_vat' ) && 'base' === get_option( 'woocommerce_tax_based_on' ) ) {
	update_option( 'woocommerce_tax_based_on', 'billing' );
}
