<?php
/**
 * Germanized Packaging Functions
 *
 * @package Germanized/Shipments/Functions
 * @version 3.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get Packaging.
 *
 * @param mixed $packaging_id (default: false) Packaging id to get or empty if new.
 *
 * @return \Vendidero\Germanized\Shipments\Packaging|bool
 */
function wc_gzd_get_packaging( $packaging_id = false ) {
	return \Vendidero\Germanized\Shipments\PackagingFactory::get_packaging( $packaging_id );
}

function wc_gzd_get_packaging_types() {
	$types = array(
		'cardboard' => _x( 'Cardboard', 'shipments', 'woocommerce-germanized' ),
		'letter'    => _x( 'Letter', 'shipments', 'woocommerce-germanized' ),
	);

	return apply_filters( 'woocommerce_gzd_packaging_types', $types );
}

/**
 * @return \Vendidero\Germanized\Shipments\Packaging[] $packaging_list
 */
function wc_gzd_get_packaging_list() {
	$data_store = \WC_Data_Store::load( 'packaging' );
	$list       = $data_store->get_packaging_list();

	return $list;
}

function wc_gzd_get_packaging_weight_unit() {
	return apply_filters( 'woocommerce_gzd_packaging_weight_unit', 'kg' );
}

function wc_gzd_get_packaging_dimension_unit() {
	return apply_filters( 'woocommerce_gzd_packaging_dimension_unit', 'cm' );
}

function wc_gzd_get_packaging_select() {
	$list   = wc_gzd_get_packaging_list();
	$select = array(
		'' => _x( 'None', 'shipments-packaging', 'woocommerce-germanized' ),
	);

	foreach ( $list as $packaging ) {
		$select[ $packaging->get_id() ] = $packaging->get_title();
	}

	return $select;
}
