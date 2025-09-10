<?php
/**
 * Template Hooks
 *
 * Action/filter hooks used for Shipments functions/templates.
 *
 * @package Vendidero/Shiptastic/Templates
 * @version 1.0.0
 */
use Vendidero\Shiptastic\Package;

defined( 'ABSPATH' ) || exit;

if ( Package::get_setting( 'customer_account_enable' ) === 'yes' ) {
	// Customer Panel
	add_action( 'woocommerce_view_order', 'woocommerce_shiptastic_template_view_shipments', 10, 1 );
	add_action( 'woocommerce_account_view-shipment_endpoint', 'woocommerce_shiptastic_template_view_shipment' );
	add_action( 'woocommerce_account_view-shipments_endpoint', 'woocommerce_shiptastic_template_view_endpoint_shipments' );

	// Returns
	add_action( 'woocommerce_account_add-return-shipment_endpoint', 'woocommerce_shiptastic_template_add_return_shipment' );
	add_action( 'woocommerce_shiptastic_view_shipment', 'woocommerce_stc_return_shipments_template_instructions', 5 );

	// View shipment details
	add_action( 'woocommerce_shiptastic_view_shipment', 'woocommerce_stc_shipment_details_table', 10 );

	// Buttons
	add_filter( 'woocommerce_my_account_my_orders_actions', 'woocommerce_stc_shipment_tracking_buttons', 10, 2 );
}

add_action( 'woocommerce_shiptastic_add_return_shipment_details_after_shipment_table', 'woocommerce_shiptastic_template_non_returnable_items_note', 10 );
