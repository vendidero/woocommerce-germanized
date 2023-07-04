<?php
/**
 * WooCommerce Germanized DHL Shipment Functions
 *
 * Functions for shipment specific things.
 *
 * @package WooCommerce_Germanized/DHL/Functions
 * @version 3.4.0
 */

use Vendidero\Germanized\DHL\Legacy\LabelQuery;

/**
 * Standard way of retrieving shipments based on certain parameters.
 *
 * @since  2.6.0
 * @param  array $args Array of args (above).
 * @return \Vendidero\Germanized\DHL\Label\Label[]
 */
function wc_gzd_dhl_get_labels( $args ) {
	$query = new LabelQuery( $args );

	return $query->get_labels();
}

function wc_gzd_legacy_dhl_get_label_types() {
	return array(
		'simple',
		'return',
		'deutsche_post',
		'deutsche_post_return',
	);
}

function wc_gzd_dhl_get_return_label_by_shipment( $the_shipment ) {
	return wc_gzd_dhl_get_shipment_label( $the_shipment, 'return' );
}

/**
 * Main function for returning label.
 *
 * @param  mixed $the_label Object or label id.
 *
 * @return bool|\Vendidero\Germanized\DHL\Label\Label
 *
 */
function wc_gzd_dhl_get_label( $the_label = false ) {
	return wc_gzd_get_shipment_label( $the_label );
}

function wc_gzd_dhl_get_shipment_label( $the_shipment, $type = '' ) {
	$shipment_id = \Vendidero\Germanized\Shipments\ShipmentFactory::get_shipment_id( $the_shipment );

	if ( $shipment_id && \Vendidero\Germanized\DHL\Package::legacy_label_table_exists() ) {
		$args = array(
			'shipment_id' => $shipment_id,
		);

		if ( ! empty( $type ) ) {
			$args['type'] = $type;
		}

		$labels = wc_gzd_dhl_get_labels( $args );

		if ( ! empty( $labels ) ) {
			return $labels[0];
		}
	}

	return false;
}

add_filter( 'woocommerce_gzd_shipping_provider_dhl_get_label', '_wc_gzd_dhl_legacy_shipment_label_dhl', 10, 3 );
add_filter( 'woocommerce_gzd_shipping_provider_deutsche_post_get_label', '_wc_gzd_dhl_legacy_shipment_label_deutsche_post', 10, 3 );

/**
 * @param $label
 * @param \Vendidero\Germanized\Shipments\Shipment $the_shipment
 * @param \Vendidero\Germanized\Shipments\Interfaces\ShippingProvider $provider
 *
 * @return false|\Vendidero\Germanized\DHL\Label\Label
 */
function _wc_gzd_dhl_legacy_shipment_label_dhl( $label, $the_shipment, $provider ) {
	if ( ! $label && '' === $the_shipment->get_version() ) {
		$label_type = $the_shipment->get_type();

		return wc_gzd_dhl_get_shipment_label( $the_shipment, $label_type );
	}

	return $label;
}

function _wc_gzd_dhl_legacy_shipment_label_deutsche_post( $label, $the_shipment, $provider ) {
	if ( ! $label && '' === $the_shipment->get_version() ) {
		$label_type = $the_shipment->get_type();
		$label_type = 'return' === $label_type ? 'deutsche_post_return' : 'deutsche_post';

		return wc_gzd_dhl_get_shipment_label( $the_shipment, $label_type );
	}

	return $label;
}

add_filter( 'woocommerce_gzd_shipment_label', '_wc_gzd_dhl_legacy_label', 10, 4 );

function _wc_gzd_dhl_legacy_label( $label, $the_label, $shipping_provider, $type ) {
	if ( ! $label ) {
		$label_id = \Vendidero\Germanized\Shipments\Labels\Factory::get_label_id( $the_label );

		if ( $label_id ) {
			$type = WC_Data_Store::load( 'dhl-legacy-label' )->get_label_type( $label_id );

			if ( $type ) {
				$mappings = array(
					'simple'               => '\Vendidero\Germanized\DHL\Label\DHL',
					'return'               => '\Vendidero\Germanized\DHL\Label\DHLReturn',
					'deutsche_post'        => '\Vendidero\Germanized\DHL\Label\DeutschePost',
					'deutsche_post_return' => '\Vendidero\Germanized\DHL\Label\DeutschePostReturn',
				);

				$classname = isset( $mappings[ $type ] ) ? $mappings[ $type ] : '\Vendidero\Germanized\DHL\Label\DHL';

				try {
					$label = new $classname( $label_id, true );
				} catch ( Exception $e ) {
					wc_caught_exception( $e, __FUNCTION__, array( $label, $the_label, $shipping_provider, $type ) );
					$label = false;
				}
			}
		}
	}

	return $label;
}

function wc_gzd_dhl_get_inlay_return_label_default_args( $parent_label ) {
	wc_deprecated_function( 'wc_gzd_dhl_get_inlay_return_label_default_args', '1.5' );

	return array();
}

function wc_gzd_dhl_validate_return_label_args( $shipment, $args = array() ) {
	wc_deprecated_function( 'wc_gzd_dhl_validate_return_label_args', '1.5' );

	return $args;
}

function wc_gzd_dhl_validate_inlay_return_label_args( $parent_label, $args = array() ) {
	wc_deprecated_function( 'wc_gzd_dhl_validate_inlay_return_label_args', '1.5' );

	return $args;
}

function wc_gzd_dhl_validate_label_args( $shipment, $args = array() ) {
	wc_deprecated_function( 'wc_gzd_dhl_validate_label_args', '1.5' );

	return $args;
}

/**
 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
 */
function wc_gzd_dhl_shipment_needs_label( $shipment, $check_status = true ) {
	wc_deprecated_function( 'wc_gzd_dhl_shipment_needs_label', '1.5' );

	/**
	 * Filter to determine whether a shipment needs a DHL label or not.
	 *
	 * @param boolean  $needs_label Whether the shipment needs a DHL label or not.
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment The shipment object.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/DHL
	 */
	return apply_filters( 'woocommerce_gzd_dhl_shipment_needs_label', $shipment->needs_label( $check_status ), $shipment );
}

/**
 * @param \Vendidero\Germanized\DHL\Label\DHL $parent_label
 * @param array $args
 *
 * @return bool|\Vendidero\Germanized\DHL\Label\DHLInlayReturn|WP_Error
 */
function wc_gzd_dhl_create_inlay_return_label( $parent_label, $args = array() ) {
	wc_deprecated_function( 'wc_gzd_dhl_create_inlay_return_label', '1.5' );

	$label = \Vendidero\Germanized\Shipments\Labels\Factory::get_label( 0, 'dhl', 'inlay_return' );
	$label->set_props( $args );
	$label->set_parent_id( $parent_label->get_id() );
	$label->set_shipment_id( $parent_label->get_shipment_id() );

	return $label;
}

function wc_gzd_dhl_update_label( $label, $args = array() ) {
	wc_deprecated_function( 'wc_gzd_dhl_update_label', '1.5' );

	return $label;
}

/**
 * @param \Vendidero\Germanized\Shipments\Shipment $shipment the shipment
 * @param array $args
 */
function wc_gzd_dhl_create_label( $shipment, $args = false ) {
	wc_deprecated_function( 'wc_gzd_dhl_create_label', '1.5' );

	try {
		$label = $shipment->create_label( $args );

		if ( is_wp_error( $label ) ) {
			return $label;
		}

		/**
		 * Action fires after creating a DHL label.
		 *
		 * The dynamic portion of this hook, `$hook_prefix` refers to the label type e.g. return.
		 *
		 * Example hook name: woocommerce_gzd_dhl_after_create_return_label
		 *
		 * @param \Vendidero\Germanized\DHL\Label\Label $label The label object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		do_action( 'woocommerce_gzd_dhl_after_create_label', $label );

	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}

	return $label;
}

/**
 * @param \Vendidero\Germanized\DHL\Order $dhl_order
 * @param \Vendidero\Germanized\DHL\Label\ReturnLabel $shipment
 */
function wc_gzd_dhl_get_return_label_default_args( $dhl_order, $shipment ) {
	wc_deprecated_function( 'wc_gzd_dhl_get_return_label_default_args', '1.5' );

	return array();
}

/**
 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
 * @param string $unit
 *
 * @return float
 */
function wc_gzd_dhl_get_shipment_weight( $shipment, $unit = 'kg', $net_weight = false ) {
	wc_deprecated_function( 'wc_gzd_dhl_get_shipment_weight', '1.5' );

	return wc_gzd_get_shipment_label_weight( $shipment, $net_weight, $unit );
}

/**
 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
 * @param string $dimension
 * @param string $unit
 */
function wc_gzd_dhl_get_shipment_dimensions( $shipment, $unit = 'cm' ) {
	wc_deprecated_function( 'wc_gzd_dhl_get_shipment_dimensions', '1.5' );

	return wc_gzd_get_shipment_label_dimensions( $shipment, $unit );
}

function wc_gzd_dhl_get_label_default_args( $dhl_order, $shipment ) {
	wc_deprecated_function( 'wc_gzd_dhl_get_label_default_args', '1.5' );

	return array();
}

function wc_gzd_dhl_get_label_id( $label ) {
	wc_deprecated_function( 'wc_gzd_dhl_get_label_id', '1.5' );

	if ( is_numeric( $label ) ) {
		return $label;
	} elseif ( $label instanceof Vendidero\Germanized\DHL\Label\Label ) {
		return $label->get_id();
	} elseif ( ! empty( $label->label_id ) ) {
		return $label->label_id;
	} else {
		return false;
	}
}

function wc_gzd_dhl_upload_data( $filename, $bits, $relative = true ) {
	wc_deprecated_function( 'wc_gzd_dhl_upload_data', '1.5' );

	return wc_gzd_shipments_upload_data( $filename, $bits, $relative );
}

function wc_gzd_dhl_get_return_label_by_parent( $label_parent_id ) {
	wc_deprecated_function( 'wc_gzd_dhl_get_return_label_by_parent', '1.5' );

	$labels = wc_gzd_dhl_get_labels(
		array(
			'parent_id' => $label_parent_id,
			'type'      => 'return',
		)
	);

	if ( ! empty( $labels ) ) {
		return $labels[0];
	}

	return false;
}

function wc_gzd_dhl_generate_label_filename( $label, $prefix = 'label' ) {
	wc_deprecated_function( 'wc_gzd_dhl_generate_label_filename', '1.5' );

	$filename = 'dhl-' . $prefix . '-' . $label->get_shipment_id() . '.pdf';

	return $filename;
}

function wc_gzd_dhl_get_deutsche_post_selected_default_product( $shipment, $dhl_order = false ) {
	wc_deprecated_function( 'wc_gzd_dhl_get_deutsche_post_selected_default_product', '1.5' );

	return array();
}

function wc_gzd_dhl_get_deutsche_post_label_default_args( $dhl_order, $shipment ) {
	wc_deprecated_function( 'wc_gzd_dhl_get_deutsche_post_label_default_args', '1.5' );

	return array();
}

function wc_gzd_dhl_validate_deutsche_post_label_args( $shipment, $args = array() ) {
	wc_deprecated_function( 'wc_gzd_dhl_validate_deutsche_post_label_args', '1.5' );

	return $args;
}

function wc_gzd_dhl_get_deutsche_post_default_product( $shipment ) {
	wc_deprecated_function( 'wc_gzd_dhl_get_deutsche_post_default_product', '1.5' );

	return false;
}
