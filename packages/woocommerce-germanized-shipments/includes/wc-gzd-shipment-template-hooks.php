<?php
/**
 * WooCommerce Germanized Shipments Template Hooks
 *
 * Action/filter hooks used for Shipments functions/templates.
 *
 * @package Vendidero/Germanized/Shipments/Templates
 * @version 1.0.0
 */

use Vendidero\Germanized\Shipments\Package;

defined( 'ABSPATH' ) || exit;

if ( Package::get_setting( 'customer_account_enable' ) === 'yes' ) {

	// Customer Panel
	add_action( 'woocommerce_view_order', 'woocommerce_gzd_shipments_template_view_shipments', 10, 1 );
	add_action( 'woocommerce_account_view-shipment_endpoint', 'woocommerce_gzd_shipments_template_view_shipment' );
	add_action( 'woocommerce_account_view-shipments_endpoint', 'woocommerce_gzd_shipments_template_view_shipments' );

	// Returns
	add_action( 'woocommerce_account_add-return-shipment_endpoint', 'woocommerce_gzd_shipments_template_add_return_shipment' );
	add_action( 'woocommerce_gzd_view_shipment', 'woocommerce_gzd_return_shipments_template_instructions', 5 );

	// View shipment details
	add_action( 'woocommerce_gzd_view_shipment', 'woocommerce_gzd_shipment_details_table', 10 );
}
