<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Vendidero\Germanized\Shiptastic::define_tables();

global $wpdb;
$wpdb->hide_errors();

$shipments_table_name      = $wpdb->prefix . 'woocommerce_stc_shipments';
$shipment_items_table_name = $wpdb->prefix . 'woocommerce_stc_shipment_items';
$shipments                 = $wpdb->get_results( "SELECT * FROM `{$shipments_table_name}` ORDER BY shipment_id DESC LIMIT 50" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

if ( ! empty( $shipments ) ) {
	$weight_unit    = get_option( 'woocommerce_weight_unit', 'kg' );
	$dimension_unit = get_option( 'woocommerce_dimension_unit', 'cm' );

	foreach ( $shipments as $shipment ) {
		$shipment_weight_unit = get_metadata( 'stc_shipment', $shipment->shipment_id, '_weight_unit', true );

		if ( '' !== $shipment_weight_unit ) {
			continue;
		}

		$shipment_weight = get_metadata( 'stc_shipment', $shipment->shipment_id, '_weight', true );

		if ( '' !== $shipment_weight ) {
			$shipment_weight = wc_get_weight( wc_format_decimal( $shipment_weight ), $weight_unit, 'kg' );
			update_metadata( 'stc_shipment', $shipment->shipment_id, '_weight', $shipment_weight );
		}

		$shipment_width = get_metadata( 'stc_shipment', $shipment->shipment_id, '_width', true );

		if ( '' !== $shipment_width ) {
			$shipment_width = wc_get_dimension( wc_format_decimal( $shipment_width ), $dimension_unit, 'cm' );
			update_metadata( 'stc_shipment', $shipment->shipment_id, '_width', $shipment_width );
		}

		$shipment_length = get_metadata( 'stc_shipment', $shipment->shipment_id, '_length', true );

		if ( '' !== $shipment_length ) {
			$shipment_length = wc_get_dimension( wc_format_decimal( $shipment_length ), $dimension_unit, 'cm' );
			update_metadata( 'stc_shipment', $shipment->shipment_id, '_length', $shipment_length );
		}

		$shipment_height = get_metadata( 'stc_shipment', $shipment->shipment_id, '_height', true );

		if ( '' !== $shipment_height ) {
			$shipment_height = wc_get_dimension( wc_format_decimal( $shipment_height ), $dimension_unit, 'cm' );
			update_metadata( 'stc_shipment', $shipment->shipment_id, '_height', $shipment_height );
		}

		$shipment_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$shipment_items_table_name}` WHERE shipment_id = %s", $shipment->shipment_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $shipment_items ) ) {
			foreach ( $shipment_items as $shipment_item ) {
				$item_weight = get_metadata( 'stc_shipment_item', $shipment_item->shipment_item_id, '_weight', true );

				if ( '' !== $item_weight ) {
					$item_weight = wc_get_weight( wc_format_decimal( $item_weight ), $weight_unit, 'kg' );
					update_metadata( 'stc_shipment_item', $shipment_item->shipment_item_id, '_weight', $item_weight );
				}

				$item_width = get_metadata( 'stc_shipment_item', $shipment_item->shipment_item_id, '_width', true );

				if ( '' !== $item_width ) {
					$item_width = wc_get_dimension( wc_format_decimal( $item_width ), $dimension_unit, 'cm' );
					update_metadata( 'stc_shipment_item', $shipment_item->shipment_item_id, '_width', $item_width );
				}

				$item_length = get_metadata( 'stc_shipment_item', $shipment_item->shipment_item_id, '_length', true );

				if ( '' !== $item_length ) {
					$item_length = wc_get_dimension( wc_format_decimal( $item_length ), $dimension_unit, 'cm' );
					update_metadata( 'stc_shipment_item', $shipment_item->shipment_item_id, '_length', $item_length );
				}

				$item_height = get_metadata( 'stc_shipment_item', $shipment_item->shipment_item_id, '_height', true );

				if ( '' !== $item_height ) {
					$item_height = wc_get_dimension( wc_format_decimal( $item_height ), $dimension_unit, 'cm' );
					update_metadata( 'stc_shipment_item', $shipment_item->shipment_item_id, '_height', $item_height );
				}
			}
		}

		update_metadata( 'stc_shipment', $shipment->shipment_id, '_dimension_unit', $dimension_unit );
		update_metadata( 'stc_shipment', $shipment->shipment_id, '_weight_unit', $weight_unit );
	}
}
