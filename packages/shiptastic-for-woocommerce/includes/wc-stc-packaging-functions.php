<?php
/**
 *
 * @package Shiptastic/Functions
 * @version 3.3.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * Get Packaging.
 *
 * @param mixed $packaging_id (default: false) Packaging id to get or empty if new.
 *
 * @return \Vendidero\Shiptastic\Packaging|bool
 */
function wc_stc_get_packaging( $packaging_id = false ) {
	return \Vendidero\Shiptastic\PackagingFactory::get_packaging( $packaging_id );
}

function wc_stc_get_packaging_types() {
	$types = array(
		'cardboard' => _x( 'Cardboard', 'shipments', 'woocommerce-germanized' ),
		'letter'    => _x( 'Letter', 'shipments', 'woocommerce-germanized' ),
	);

	return apply_filters( 'woocommerce_shiptastic_packaging_types', $types );
}

/**
 * @return \Vendidero\Shiptastic\Packaging[] $packaging_list
 */
function wc_stc_get_packaging_list( $args = array() ) {
	$list = \Vendidero\Shiptastic\Packaging\Helper::get_packaging_list( $args );

	return $list;
}

function wc_stc_get_packaging_weight_unit() {
	return apply_filters( 'woocommerce_shiptastic_packaging_weight_unit', get_option( 'woocommerce_weight_unit', 'kg' ) );
}

function wc_stc_get_packaging_dimension_unit() {
	return apply_filters( 'woocommerce_shiptastic_packaging_dimension_unit', get_option( 'woocommerce_dimension_unit', 'cm' ) );
}

function wc_stc_get_packaging_select( $args = array() ) {
	$list   = wc_stc_get_packaging_list( $args );
	$select = array(
		'' => _x( 'None', 'shipments-packaging', 'woocommerce-germanized' ),
	);

	foreach ( $list as $packaging ) {
		$select[ $packaging->get_id() ] = $packaging->get_title();
	}

	return $select;
}
