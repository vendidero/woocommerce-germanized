<?php
/**
 * Legacy Shipments Functions
 *
 * @author  Vendidero
 * @version 3.19.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

function wc_gzd_get_formatted_state( $country = '', $state = '' ) {
	return function_exists( 'wc_stc_get_formatted_state' ) ? wc_stc_get_formatted_state( $country, $state ) : '';
}

function wc_gzd_country_to_alpha3( $country ) {
	return function_exists( 'wc_stc_country_to_alpha3' ) ? wc_stc_country_to_alpha3( $country ) : $country;
}

function wc_gzd_get_customer_preferred_shipping_provider( $user_id ) {
	return function_exists( 'wc_stc_get_customer_preferred_shipping_provider' ) ? wc_stc_get_customer_preferred_shipping_provider( $user_id ) : '';
}

function wc_gzd_country_to_alpha2( $country ) {
	return function_exists( 'wc_stc_country_to_alpha2' ) ? wc_stc_country_to_alpha2( $country ) : $country;
}

function wc_gzd_get_shipment_order( $order ) {
	return function_exists( 'wc_stc_get_shipment_order' ) ? wc_stc_get_shipment_order( $order ) : false;
}

function wc_gzd_get_shipment_label_title( $type, $plural = false ) {
	return function_exists( 'wc_stc_get_shipment_label_title' ) ? wc_stc_get_shipment_label_title( $type, $plural ) : '';
}

function wc_gzd_get_shipping_label_zones() {
	return function_exists( 'wc_stc_get_shipping_label_zones' ) ? wc_stc_get_shipping_label_zones() : array();
}

function wc_gzd_get_shipping_label_zone_title( $zone ) {
	return function_exists( 'wc_stc_get_shipping_label_zone_title' ) ? wc_stc_get_shipping_label_zone_title( $zone ) : '';
}

function wc_gzd_get_shipping_shipments_label_zone_title( $zone ) {
	return function_exists( 'wc_stc_get_shipping_shipments_label_zone_title' ) ? wc_stc_get_shipping_shipments_label_zone_title( $zone ) : '';
}

function wc_gzd_get_shipment_types() {
	return function_exists( 'wc_stc_get_shipment_types' ) ? wc_stc_get_shipment_types() : array();
}

function wc_gzd_get_shipment_type_data( $type = false ) {
	return function_exists( 'wc_stc_get_shipment_type_data' ) ? wc_stc_get_shipment_type_data( $type ) : array();
}

function wc_gzd_get_shipments_by_order( $order ) {
	return function_exists( 'wc_stc_get_shipments_by_order' ) ? wc_stc_get_shipments_by_order( $order ) : array();
}

function wc_gzd_get_shipment_order_shipping_statuses() {
	return function_exists( 'wc_stc_get_shipment_order_shipping_statuses' ) ? wc_stc_get_shipment_order_shipping_statuses() : array();
}

function wc_gzd_get_shipment_order_return_statuses() {
	return function_exists( 'wc_stc_get_shipment_order_return_statuses' ) ? wc_stc_get_shipment_order_return_statuses() : array();
}

function wc_gzd_get_shipping_provider_method( $instance_id ) {
	return function_exists( 'wc_stc_get_shipping_provider_method' ) ? wc_stc_get_shipping_provider_method( $instance_id ) : false;
}

function wc_gzd_get_current_shipping_method_id() {
	return function_exists( 'wc_stc_get_current_shipping_method_id' ) ? wc_stc_get_current_shipping_method_id() : '';
}

function wc_gzd_get_current_shipping_provider_method() {
	return function_exists( 'wc_stc_get_current_shipping_provider_method' ) ? wc_stc_get_current_shipping_provider_method() : false;
}

function wc_gzd_get_shipment_order_shipping_status_name( $status ) {
	if ( 'gzd-' !== substr( $status, 0, 4 ) ) {
		$status = 'gzd-' . $status;
	}

	return function_exists( 'wc_stc_get_shipment_order_shipping_status_name' ) ? wc_stc_get_shipment_order_shipping_status_name( $status ) : '';
}

function wc_gzd_get_shipment_order_return_status_name( $status ) {
	if ( 'gzd-' !== substr( $status, 0, 4 ) ) {
		$status = 'gzd-' . $status;
	}

	return function_exists( 'wc_stc_get_shipment_order_return_status_name' ) ? wc_stc_get_shipment_order_return_status_name( $status ) : '';
}

function wc_gzd_get_shipments( $args ) {
	return function_exists( 'wc_stc_get_shipments' ) ? wc_stc_get_shipments( $args ) : array();
}

function wc_gzd_get_shipment_customer_visible_statuses( $shipment_type = 'simple' ) {
	return function_exists( 'wc_stc_get_shipment_customer_visible_statuses' ) ? wc_stc_get_shipment_customer_visible_statuses( $shipment_type ) : array();
}

function wc_gzd_get_shipment( $the_shipment ) {
	return function_exists( 'wc_stc_get_shipment' ) ? wc_stc_get_shipment( $the_shipment ) : false;
}

function wc_gzd_get_shipment_statuses() {
	return function_exists( 'wc_stc_get_shipment_statuses' ) ? wc_stc_get_shipment_statuses() : array();
}

function wc_gzd_get_shipment_selectable_statuses( $shipment ) {
	return function_exists( 'wc_stc_get_shipment_selectable_statuses' ) ? wc_stc_get_shipment_selectable_statuses( $shipment ) : array();
}

function wc_gzd_create_return_shipment( $order_shipment, $args = array() ) {
	return function_exists( 'wc_stc_create_return_shipment' ) ? wc_stc_create_return_shipment( $order_shipment, $args ) : false;
}

function wc_gzd_create_shipment( $order_shipment, $args = array() ) {
	return function_exists( 'wc_stc_create_shipment' ) ? wc_stc_create_shipment( $order_shipment, $args ) : false;
}

function wc_gzd_create_shipment_item( $shipment, $order_item, $args = array() ) {
	return function_exists( 'wc_stc_create_shipment_item' ) ? wc_stc_create_shipment_item( $shipment, $order_item, $args ) : false;
}

function wc_gzd_allow_customer_return_empty_return_reason( $order ) {
	return function_exists( 'wc_stc_allow_customer_return_empty_return_reason' ) ? wc_stc_allow_customer_return_empty_return_reason( $order ) : false;
}

function wc_gzd_get_return_shipment_reasons( $order_item = false ) {
	return function_exists( 'wc_stc_get_return_shipment_reasons' ) ? wc_stc_get_return_shipment_reasons( $order_item ) : array();
}

function wc_gzd_return_shipment_reason_exists( $maybe_reason, $shipment = false ) {
	return function_exists( 'wc_stc_return_shipment_reason_exists' ) ? wc_stc_return_shipment_reason_exists( $maybe_reason, $shipment ) : false;
}

function _wc_gzd_sort_return_shipment_reasons( $a, $b ) {
	return function_exists( '_wc_stc_sort_return_shipment_reasons' ) ? _wc_stc_sort_return_shipment_reasons( $a, $b ) : 0;
}

function wc_gzd_shipment_wp_error_has_errors( $error ) {
	return function_exists( 'wc_stc_shipment_wp_error_has_errors' ) ? wc_stc_shipment_wp_error_has_errors( $error ) : false;
}

function wc_gzd_create_return_shipment_item( $shipment, $shipment_item, $args = array() ) {
	return function_exists( 'wc_stc_create_return_shipment_item' ) ? wc_stc_create_return_shipment_item( $shipment, $shipment_item, $args ) : false;
}

function wc_gzd_get_shipment_editable_statuses() {
	return function_exists( 'wc_stc_get_shipment_editable_statuses' ) ? wc_stc_get_shipment_editable_statuses() : array();
}

function wc_gzd_get_shipment_address_addition( $shipment ) {
	return function_exists( 'wc_stc_get_shipment_address_addition' ) ? wc_stc_get_shipment_address_addition( $shipment ) : '';
}

function wc_gzd_split_shipment_street( $street_str ) {
	return function_exists( 'wc_stc_split_shipment_street' ) ? wc_stc_split_shipment_street( $street_str ) : array();
}

function wc_gzd_get_shipping_providers() {
	return function_exists( 'wc_stc_get_shipping_providers' ) ? wc_stc_get_shipping_providers() : array();
}

function wc_gzd_get_available_shipping_providers() {
	return function_exists( 'wc_stc_get_available_shipping_providers' ) ? wc_stc_get_available_shipping_providers() : array();
}

function wc_gzd_get_shipping_provider( $name ) {
	return function_exists( 'wc_stc_get_shipping_provider' ) ? wc_stc_get_shipping_provider( $name ) : false;
}

function wc_gzd_get_default_shipping_provider() {
	return function_exists( 'wc_stc_get_default_shipping_provider' ) ? wc_stc_get_default_shipping_provider() : '';
}

function wc_gzd_get_shipping_provider_select( $include_none = true ) {
	return function_exists( 'wc_stc_get_shipping_provider_select' ) ? wc_stc_get_shipping_provider_select( $include_none ) : array();
}

function wc_gzd_get_shipping_provider_title( $slug ) {
	return function_exists( 'wc_stc_get_shipping_provider_title' ) ? wc_stc_get_shipping_provider_title( $slug ) : '';
}

function wc_gzd_get_shipment_shipping_provider_title( $shipment ) {
	return function_exists( 'wc_stc_get_shipment_shipping_provider_title' ) ? wc_stc_get_shipment_shipping_provider_title( $shipment ) : '';
}

function wc_gzd_get_shipping_provider_service_locations() {
	return function_exists( 'wc_stc_get_shipping_provider_service_locations' ) ? wc_stc_get_shipping_provider_service_locations() : array();
}

function wc_gzd_get_shipping_provider_slug( $provider ) {
	return function_exists( 'wc_stc_get_shipping_provider_slug' ) ? wc_stc_get_shipping_provider_slug( $provider ) : '';
}

function _wc_gzd_shipments_keep_force_filename( $new_filename ) {
	return function_exists( '_wc_shiptastic_keep_force_filename' ) ? _wc_shiptastic_keep_force_filename( $new_filename ) : true;
}

function wc_gzd_shipments_upload_data( $filename, $bits, $relative = true ) {
	return function_exists( 'wc_shiptastic_upload_data' ) ? wc_shiptastic_upload_data( $filename, $bits, $relative ) : false;
}

function wc_gzd_get_shipment_setting_default_address_fields( $type = 'shipper' ) {
	return function_exists( 'wc_stc_get_shipment_setting_default_address_fields' ) ? wc_stc_get_shipment_setting_default_address_fields( $type ) : array();
}

function wc_gzd_get_shipment_setting_address_fields( $address_type = 'shipper' ) {
	return function_exists( 'wc_stc_get_shipment_setting_address_fields' ) ? wc_stc_get_shipment_setting_address_fields( $address_type ) : array();
}

function wc_gzd_get_shipment_return_address( $shipment_order = false ) {
	return function_exists( 'wc_stc_get_shipment_return_address' ) ? wc_stc_get_shipment_return_address( $shipment_order ) : array();
}

function wc_gzd_get_shipment_order_shipping_method( $order ) {
	return function_exists( 'wc_stc_get_shipment_order_shipping_method' ) ? wc_stc_get_shipment_order_shipping_method( $order ) : false;
}

function wc_gzd_get_shipment_order_shipping_method_id( $order ) {
	return function_exists( 'wc_stc_get_shipment_order_shipping_method_id' ) ? wc_stc_get_shipment_order_shipping_method_id( $order ) : '';
}

function wc_gzd_render_shipment_action_buttons( $actions ) {
	return function_exists( 'wc_stc_render_shipment_action_buttons' ) ? wc_stc_render_shipment_action_buttons( $actions ) : '';
}

function wc_gzd_get_shipment_status_name( $status ) {
	return function_exists( 'wc_stc_get_shipment_status_name' ) ? wc_stc_get_shipment_status_name( $status ) : '';
}

function wc_gzd_get_shipment_sent_statuses() {
	return function_exists( 'wc_stc_get_shipment_sent_statuses' ) ? wc_stc_get_shipment_sent_statuses() : array();
}

function wc_gzd_get_shipment_counts( $type = '' ) {
	return function_exists( 'wc_stc_get_shipment_counts' ) ? wc_stc_get_shipment_counts( $type ) : array();
}

function wc_gzd_get_shipment_count( $status, $type = '' ) {
	return function_exists( 'wc_stc_get_shipment_count' ) ? wc_stc_get_shipment_count( $status, $type ) : 0;
}

function wc_gzd_is_shipment_status( $maybe_status ) {
	return function_exists( 'wc_stc_is_shipment_status' ) ? wc_stc_is_shipment_status( $maybe_status ) : false;
}

function wc_gzd_get_shipment_item( $the_item = false, $item_type = 'simple' ) {
	return function_exists( 'wc_stc_get_shipment_item' ) ? wc_stc_get_shipment_item( $the_item, $item_type ) : false;
}

function wc_gzd_get_shipment_item_id( $item ) {
	return function_exists( 'wc_stc_get_shipment_item_id' ) ? wc_stc_get_shipment_item_id( $item ) : false;
}

function wc_gzd_format_shipment_dimensions( $dimensions, $unit = '' ) {
	return function_exists( 'wc_stc_format_shipment_dimensions' ) ? wc_stc_format_shipment_dimensions( $dimensions, $unit ) : $dimensions;
}

function wc_gzd_format_shipment_weight( $weight, $unit = '' ) {
	return function_exists( 'wc_stc_format_shipment_weight' ) ? wc_stc_format_shipment_weight( $weight, $unit ) : $weight;
}

function wc_gzd_get_account_shipments_columns( $type = 'simple' ) {
	return function_exists( 'wc_stc_get_account_shipments_columns' ) ? wc_stc_get_account_shipments_columns( $type ) : array();
}

function wc_gzd_get_order_customer_add_return_url( $order ) {
	return function_exists( 'wc_stc_get_order_customer_add_return_url' ) ? wc_stc_get_order_customer_add_return_url( $order ) : '';
}

function wc_gzd_order_is_customer_returnable( $order, $check_date = true ) {
	return function_exists( 'wc_stc_order_is_customer_returnable' ) ? wc_stc_order_is_customer_returnable( $order, $check_date ) : false;
}

function wc_gzd_get_order_shipping_provider( $order ) {
	return function_exists( 'wc_stc_get_order_shipping_provider' ) ? wc_stc_get_order_shipping_provider( $order ) : false;
}

function wc_gzd_get_customer_order_return_request_key() {
	return function_exists( 'wc_stc_get_customer_order_return_request_key' ) ? wc_stc_get_customer_order_return_request_key() : '';
}

function wc_gzd_shipments_additional_costs_include_tax() {
	return function_exists( 'wc_shiptastic_additional_costs_include_tax' ) ? wc_shiptastic_additional_costs_include_tax() : false;
}

function wc_gzd_customer_can_add_return_shipment( $order_id ) {
	return function_exists( 'wc_stc_customer_can_add_return_shipment' ) ? wc_stc_customer_can_add_return_shipment( $order_id ) : false;
}

function wc_gzd_customer_return_needs_manual_confirmation( $order ) {
	return function_exists( 'wc_stc_customer_return_needs_manual_confirmation' ) ? wc_stc_customer_return_needs_manual_confirmation( $order ) : false;
}

function wc_gzd_get_account_shipments_actions( $shipment ) {
	return function_exists( 'wc_stc_get_account_shipments_actions' ) ? wc_stc_get_account_shipments_actions( $shipment ) : array();
}

function wc_gzd_shipments_get_product( $the_product ) {
	return function_exists( 'wc_shiptastic_get_product' ) ? wc_shiptastic_get_product( $the_product ) : false;
}

function wc_gzd_get_volume_dimension( $dimension, $to_unit, $from_unit = '' ) {
	return function_exists( 'wc_stc_get_volume_dimension' ) ? wc_stc_get_volume_dimension( $dimension, $to_unit, $from_unit ) : $dimension;
}

function wc_gzd_shipments_allow_deferred_sync( $type = 'shipments' ) {
	return function_exists( 'wc_shiptastic_allow_deferred_sync' ) ? wc_shiptastic_allow_deferred_sync( $type ) : false;
}

function wc_gzd_get_shipment_error( $error ) {
	return function_exists( 'wc_stc_get_shipment_error' ) ? wc_stc_get_shipment_error( $error ) : false;
}

function wc_gzd_shipments_substring( $str, $start, $length = null ) {
	return function_exists( 'wc_shiptastic_substring' ) ? wc_shiptastic_substring( $str, $start, $length ) : '';
}

function wc_gzd_get_packaging( $packaging_id = false ) {
	return function_exists( 'wc_stc_get_packaging' ) ? wc_stc_get_packaging( $packaging_id ) : false;
}

function wc_gzd_get_packaging_types() {
	return function_exists( 'wc_stc_get_packaging_types' ) ? wc_stc_get_packaging_types() : array();
}

function wc_gzd_get_packaging_list( $args = array() ) {
	return function_exists( 'wc_stc_get_packaging_list' ) ? wc_stc_get_packaging_list( $args ) : array();
}

function wc_gzd_get_packaging_weight_unit() {
	return function_exists( 'wc_stc_get_packaging_weight_unit' ) ? wc_stc_get_packaging_weight_unit() : '';
}

function wc_gzd_get_packaging_dimension_unit() {
	return function_exists( 'wc_stc_get_packaging_dimension_unit' ) ? wc_stc_get_packaging_dimension_unit() : '';
}

function wc_gzd_get_packaging_select( $args = array() ) {
	return function_exists( 'wc_stc_get_packaging_select' ) ? wc_stc_get_packaging_select( $args ) : array();
}

function wc_gzd_get_shipment_labels( $args ) {
	return function_exists( 'wc_stc_get_shipment_labels' ) ? wc_stc_get_shipment_labels( $args ) : array();
}

function wc_gzd_get_label_type_by_shipment( $shipment ) {
	return function_exists( 'wc_stc_get_label_type_by_shipment' ) ? wc_stc_get_label_type_by_shipment( $shipment ) : '';
}

function wc_gzd_get_shipment_label_types() {
	return function_exists( 'wc_stc_get_shipment_label_types' ) ? wc_stc_get_shipment_label_types() : array();
}

function wc_gzd_get_label_by_shipment( $the_shipment, $type = '' ) {
	return function_exists( 'wc_stc_get_label_by_shipment' ) ? wc_stc_get_label_by_shipment( $the_shipment, $type ) : false;
}

function wc_gzd_get_shipment_label( $the_label = false, $shipping_provider = '', $type = 'simple' ) {
	return function_exists( 'wc_stc_get_shipment_label' ) ? wc_stc_get_shipment_label( $the_label, $shipping_provider, $type ) : false;
}

function wc_gzd_get_shipment_label_weight( $shipment, $net_weight = false, $unit = 'kg' ) {
	return function_exists( 'wc_stc_get_shipment_label_weight' ) ? wc_stc_get_shipment_label_weight( $shipment, $net_weight, $unit ) : '0.0';
}

function wc_gzd_get_shipment_label_dimensions( $shipment, $unit = 'cm' ) {
	return function_exists( 'wc_stc_get_shipment_label_dimensions' ) ? wc_stc_get_shipment_label_dimensions( $shipment, $unit ) : array();
}
