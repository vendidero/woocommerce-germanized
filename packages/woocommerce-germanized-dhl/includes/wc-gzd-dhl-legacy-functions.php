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

/**
 * @param $product
 * @param false|Shipment $shipment
 *
 * @return string[]
 */
function wc_gzd_dhl_get_product_services( $product, $shipment = false ) {
	wc_deprecated_function( 'wc_gzd_dhl_get_product_services', '3.0' );

	return array();
}

/**
 * @param $product
 * @param $service
 * @param false|Shipment $shipment
 *
 * @return bool
 */
function wc_gzd_dhl_product_supports_service( $product, $service, $shipment = false ) {
	wc_deprecated_function( 'wc_gzd_dhl_product_supports_service', '3.0' );

	return false;
}

/**
 * @param $service
 * @param false|Shipment $shipment
 *
 * @return array
 */
function wc_gzd_dhl_get_service_product_attributes( $service, $shipment = false ) {
	wc_deprecated_function( 'wc_gzd_dhl_get_service_product_attributes', '3.0' );

	return array(
		'data-products-supported' => '',
	);
}

function wc_gzd_dhl_get_international_services() {
	wc_deprecated_function( 'wc_gzd_dhl_get_international_services', '3.0' );

	return array(
		'GoGreen',
		'AdditionalInsurance',
		'CDP',
		'Economy',
		'Premium',
		'PDDP',
		'CashOnDelivery',
		'Endorsement',
	);
}

function wc_gzd_get_domestic_services() {
	wc_deprecated_function( 'wc_gzd_get_domestic_services', '3.0' );

	return array_diff( wc_gzd_dhl_get_services(), array( 'PDDP', 'CDP', 'Premium', 'Economy', 'Endorsement' ) );
}

function wc_gzd_dhl_get_services() {
	wc_deprecated_function( 'wc_gzd_dhl_get_services', '3.0' );

	return array(
		'PreferredLocation',
		'PreferredNeighbour',
		'PreferredDay',
		'VisualCheckOfAge',
		'Personally',
		'NoNeighbourDelivery',
		'NamedPersonOnly',
		'Premium',
		'CDP',
		'PDDP',
		'Economy',
		'AdditionalInsurance',
		'BulkyGoods',
		'IdentCheck',
		'CashOnDelivery',
		'ParcelOutletRouting',
		'GoGreen',
		'Endorsement',
		'SignedForByRecipient',
	);
}

function wc_gzd_dhl_get_preferred_services() {
	wc_deprecated_function( 'wc_gzd_dhl_get_preferred_services', '3.0' );

	return array(
		'PreferredTime',
		'PreferredLocation',
		'PreferredNeighbour',
		'PreferredDay',
	);
}

/**
 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
 *
 * @return array
 */
function wc_gzd_dhl_get_deutsche_post_products( $shipment, $parent_only = true ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	$country  = $shipment->get_country();
	$postcode = $shipment->get_postcode();

	if ( 'return' === $shipment->get_type() ) {
		$country  = $shipment->get_sender_country();
		$postcode = $shipment->get_sender_postcode();
	}

	if ( \Vendidero\Germanized\Shipments\Package::is_shipping_domestic( $country, $postcode ) ) {
		return wc_gzd_dhl_get_deutsche_post_products_domestic( $shipment, $parent_only );
	} elseif ( \Vendidero\Germanized\Shipments\Package::is_shipping_inner_eu_country( $country, $postcode ) ) {
		return wc_gzd_dhl_get_deutsche_post_products_eu( $shipment, $parent_only );
	} else {
		return wc_gzd_dhl_get_deutsche_post_products_international( $shipment, $parent_only );
	}
}

/**
 * @param \Vendidero\Germanized\Shipments\Shipment|false $shipment
 *
 * @return array
 */
function wc_gzd_dhl_get_deutsche_post_products_domestic( $shipment = false, $parent_only = true ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	if ( $post = \Vendidero\Germanized\DHL\Package::get_deutsche_post_shipping_provider() ) {
		return $post->get_products(
			array(
				'zone'     => 'dom',
				'shipment' => $shipment,
			)
		)->as_options();
	}

	return array();
}

/**
 * @param \Vendidero\Germanized\Shipments\Shipment|false $shipment
 *
 * @return array
 */
function wc_gzd_dhl_get_deutsche_post_products_eu( $shipment = false, $parent_only = true ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	if ( $post = \Vendidero\Germanized\DHL\Package::get_deutsche_post_shipping_provider() ) {
		return $post->get_products(
			array(
				'zone'     => 'eu',
				'shipment' => $shipment,
			)
		)->as_options();
	}

	return array();
}

/**
 * @param \Vendidero\Germanized\Shipments\Shipment|false $shipment
 *
 * @return array
 */
function wc_gzd_dhl_get_deutsche_post_products_international( $shipment = false, $parent_only = true ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	if ( $post = \Vendidero\Germanized\DHL\Package::get_deutsche_post_shipping_provider() ) {
		return $post->get_products(
			array(
				'zone'     => 'int',
				'shipment' => $shipment,
			)
		)->as_options();
	}

	return array();
}

/**
 * @param \Vendidero\Germanized\Shipments\ShippingProvider\Product[] $products
 * @param $parent_only
 *
 * @return array
 */
function wc_gzd_dhl_im_get_product_list( $products, $parent_only = true ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	$list                       = array();
	$additional_parent_products = array();

	foreach ( $products as $product ) {
		if ( $parent_only && $product->get_parent_id() > 0 ) {
			$additional_parent_products[] = $product->get_parent_id();
			continue;
		}

		$list[ $product->get_id() ] = $product->get_label();
	}

	$additional_parent_products = array_unique( $additional_parent_products );

	if ( ! empty( $additional_parent_products ) ) {
		foreach ( $additional_parent_products as $product_id ) {
			$product = \Vendidero\Germanized\DHL\Package::get_internetmarke_api()->get_product_data( $product_id );

			if ( ! array_key_exists( $product->get_id(), $list ) ) {
				$list[ $product->get_id() ] = $product->get_label();
			}
		}
	}

	return $list;
}

function wc_gzd_dhl_get_im_product_title( $product_name ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	$title = $product_name;

	return $title;
}

function wc_gzd_dhl_get_products_domestic() {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	$country = \Vendidero\Germanized\DHL\Package::get_base_country();

	$germany_dom = array(
		'V01PAK'  => _x( 'DHL Paket', 'dhl', 'woocommerce-germanized' ),
		'V01PRIO' => _x( 'DHL Paket PRIO', 'dhl', 'woocommerce-germanized' ),
		'V06PAK'  => _x( 'DHL Paket Taggleich', 'dhl', 'woocommerce-germanized' ),
		'V62WP'   => _x( 'DHL Warenpost', 'dhl', 'woocommerce-germanized' ),
	);

	$dhl_prod_dom = array();

	switch ( $country ) {
		case 'DE':
			$dhl_prod_dom = $germany_dom;
			break;
		default:
			break;
	}

	return $dhl_prod_dom;
}

function wc_gzd_dhl_get_products_eu() {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	$country = \Vendidero\Germanized\DHL\Package::get_base_country();

	$germany_int = array(
		'V53WPAK' => _x( 'DHL Paket International', 'dhl', 'woocommerce-germanized' ),
		'V55PAK'  => _x( 'DHL Paket Connect', 'dhl', 'woocommerce-germanized' ),
		'V54EPAK' => _x( 'DHL Europaket (B2B)', 'dhl', 'woocommerce-germanized' ),
	);

	if ( wc_gzd_dhl_is_warenpost_international_available() ) {
		$germany_int['V66WPI'] = _x( 'DHL Warenpost International', 'dhl', 'woocommerce-germanized' );
	}

	$dhl_prod_int = array();

	switch ( $country ) {
		case 'DE':
			$dhl_prod_int = $germany_int;
			break;
		default:
			break;
	}

	return $dhl_prod_int;
}

function wc_gzd_dhl_get_products_international() {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	$country = \Vendidero\Germanized\DHL\Package::get_base_country();

	$germany_int = array(
		'V53WPAK' => _x( 'DHL Paket International', 'dhl', 'woocommerce-germanized' ),
	);

	if ( wc_gzd_dhl_is_warenpost_international_available() ) {
		$germany_int['V66WPI'] = _x( 'DHL Warenpost International', 'dhl', 'woocommerce-germanized' );
	}

	$dhl_prod_int = array();

	switch ( $country ) {
		case 'DE':
			$dhl_prod_int = $germany_int;
			break;
		default:
			break;
	}

	return $dhl_prod_int;
}

function wc_gzd_dhl_is_warenpost_international_available() {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return true;
}

function wc_gzd_dhl_get_product_title( $product_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	$products = wc_gzd_dhl_get_products_domestic() + wc_gzd_dhl_get_products_eu() + wc_gzd_dhl_get_products_international();

	return array_key_exists( $product_id, $products ) ? $products[ $product_id ] : $product_id;
}

function wc_gzd_dhl_get_products( $shipping_country, $shipping_postcode = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	if ( \Vendidero\Germanized\DHL\Package::is_shipping_domestic( $shipping_country, $shipping_postcode ) ) {
		return wc_gzd_dhl_get_products_domestic();
	} elseif ( \Vendidero\Germanized\DHL\Package::is_eu_shipment( $shipping_country, $shipping_postcode ) ) {
		return wc_gzd_dhl_get_products_eu();
	} else {
		return wc_gzd_dhl_get_products_international();
	}
}

function wc_gzd_dhl_get_return_products( $shipping_country, $shipping_postcode = '' ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	if ( \Vendidero\Germanized\DHL\Package::is_shipping_domestic( $shipping_country, $shipping_postcode ) ) {
		return wc_gzd_dhl_get_return_products_domestic();
	} else {
		return wc_gzd_dhl_get_return_products_international();
	}
}

function wc_gzd_dhl_get_return_products_international() {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	$retoure = array(
		'retoure_international_a' => _x( 'DHL Retoure International A', 'dhl', 'woocommerce-germanized' ),
		'retoure_international_b' => _x( 'DHL Retoure International B', 'dhl', 'woocommerce-germanized' ),
	);

	return $retoure;
}

function wc_gzd_dhl_get_return_products_domestic() {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	$retoure = array(
		'retoure_online' => _x( 'DHL Retoure Online', 'dhl', 'woocommerce-germanized' ),
	);

	return $retoure;
}

function wc_gzd_dhl_get_inlay_return_products() {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return array(
		'V01PAK',
		'V01PRIO',
		'V86PARCEL',
		'V55PAK',
	);
}

function wc_gzd_dhl_get_default_return_receiver( $country, $method = false ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return \Vendidero\Germanized\DHL\Package::get_return_receiver_by_country( $country );
}

function wc_gzd_dhl_get_default_product( $country, $shipment = false ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	if ( \Vendidero\Germanized\DHL\Package::is_shipping_domestic( $country ) ) {
		return \Vendidero\Germanized\DHL\Package::get_setting( 'label_default_product_dom' );
	} elseif ( \Vendidero\Germanized\DHL\Package::is_eu_shipment( $country ) ) {
		return \Vendidero\Germanized\DHL\Package::get_setting( 'label_default_product_eu' );
	} else {
		return \Vendidero\Germanized\DHL\Package::get_setting( 'label_default_product_int' );
	}
}

function wc_gzd_dhl_round_customs_item_weight( $value, $precision = 0 ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return \Automattic\WooCommerce\Utilities\NumberUtil::round( $value, $precision, 2 );
}

function wc_gzd_dhl_format_preferred_api_time( $time ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return str_replace( array( ':', '-' ), '', $time );
}
