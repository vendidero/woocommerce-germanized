<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shipments = wc_gzd_get_shipments(
	array(
		'limit' => 50,
		'type'  => array( 'simple', 'return' ),
	)
);

foreach ( $shipments as $shipment ) {

	$weight_unit    = get_option( 'woocommerce_weight_unit', 'kg' );
	$dimension_unit = get_option( 'woocommerce_dimension_unit', 'cm' );

	if ( '' !== $shipment->get_weight_unit( 'edit' ) ) {
		continue;
	}

	if ( '' !== $shipment->get_weight( 'edit' ) ) {
		$shipment->set_weight( wc_get_weight( $shipment->get_weight( 'edit' ), $weight_unit, 'kg' ) );
	}

	if ( '' !== $shipment->get_width( 'edit' ) ) {
		$shipment->set_width( wc_get_dimension( $shipment->get_width( 'edit' ), $dimension_unit, 'cm' ) );
	}

	if ( '' !== $shipment->get_length( 'edit' ) ) {
		$shipment->set_length( wc_get_dimension( $shipment->get_length( 'edit' ), $dimension_unit, 'cm' ) );
	}

	if ( '' !== $shipment->get_height( 'edit' ) ) {
		$shipment->set_height( wc_get_dimension( $shipment->get_height( 'edit' ), $dimension_unit, 'cm' ) );
	}

	foreach ( $shipment->get_items() as $item ) {

		if ( '' !== $item->get_weight() ) {
			$item->set_weight( wc_get_weight( $item->get_weight(), $weight_unit, 'kg' ) );
		}

		if ( '' !== $item->get_width() ) {
			$item->set_width( wc_get_dimension( $item->get_width(), $dimension_unit, 'cm' ) );
		}

		if ( '' !== $item->get_length() ) {
			$item->set_length( wc_get_dimension( $item->get_length(), $dimension_unit, 'cm' ) );
		}

		if ( '' !== $item->get_height() ) {
			$item->set_height( wc_get_dimension( $item->get_height(), $dimension_unit, 'cm' ) );
		}

		$item->save();
	}

	$shipment->set_dimension_unit( $dimension_unit );
	$shipment->set_weight_unit( $weight_unit );

	$shipment->save();
}
