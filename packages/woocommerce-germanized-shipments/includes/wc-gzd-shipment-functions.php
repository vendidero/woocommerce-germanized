<?php
/**
 * WooCommerce Germanized DHL Shipment Functions
 *
 * Functions for shipment specific things.
 *
 * @package WooCommerce_Germanized/DHL/Functions
 * @version 3.4.0
 */

use Vendidero\Germanized\Shipments\Order;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\AddressSplitter;
use Vendidero\Germanized\Shipments\ShipmentFactory;
use Vendidero\Germanized\Shipments\ShipmentItem;
use Vendidero\Germanized\Shipments\SimpleShipment;
use Vendidero\Germanized\Shipments\ReturnShipment;
use Vendidero\Germanized\Shipments\Package;

defined( 'ABSPATH' ) || exit;

function wc_gzd_get_shipment_order( $order ) {
    if ( is_numeric( $order ) ) {
        $order = wc_get_order( $order);
    }

    if ( is_a( $order, 'WC_Order' ) ) {
        try {
            return new Vendidero\Germanized\Shipments\Order( $order );
        } catch ( Exception $e ) {
            wc_caught_exception( $e, __FUNCTION__, func_get_args() );
            return false;
        }
    }

    return false;
}

function wc_gzd_get_shipment_label( $type, $plural = false ) {
	$type_data = wc_gzd_get_shipment_type_data( $type );

	return ( ! $plural ? $type_data['labels']['singular'] : $type_data['labels']['plural'] );
}

function wc_gzd_get_shipment_types() {
	return array_keys( wc_gzd_get_shipment_type_data( false ) );
}

/**
 * Get shipment type data by type.
 *
 * @param  string $type type name.
 * @return bool|array Details about the shipment type.
 *
 * @package Vendidero/Germanized/Shipments
 */
function wc_gzd_get_shipment_type_data( $type = false ) {
	$types = apply_filters( 'woocommerce_gzd_shipment_type_data', array(
		'simple' => array(
			'class_name' => '\Vendidero\Germanized\Shipments\SimpleShipment',
			'labels'     => array(
				'singular' => _x( 'Shipment', 'shipments', 'woocommerce-germanized' ),
				'plural'   => _x( 'Shipments', 'shipments', 'woocommerce-germanized' ),
			),
		),
		'return' => array(
			'class_name' => '\Vendidero\Germanized\Shipments\ReturnShipment',
			'labels'     => array(
				'singular' => _x( 'Return', 'shipments', 'woocommerce-germanized' ),
				'plural'   => _x( 'Returns', 'shipments', 'woocommerce-germanized' ),
			),
		),
	) );

	if ( $type && array_key_exists( $type, $types ) ) {
		return $types[ $type ];
	} elseif ( false === $type ) {
		return $types;
	} else {
		return $types['simple'];
	}
}

function wc_gzd_get_shipments_by_order( $order ) {
	$shipments = array();

	if ( $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
		$shipments = $order_shipment->get_shipments();
	}

	return $shipments;
}

function wc_gzd_get_shipment_order_shipping_statuses() {
    $shipment_statuses = array(
        'gzd-not-shipped'       => _x( 'Not shipped', 'shipments', 'woocommerce-germanized' ),
        'gzd-partially-shipped' => _x( 'Partially shipped', 'shipments', 'woocommerce-germanized' ),
        'gzd-shipped'           => _x( 'Shipped', 'shipments', 'woocommerce-germanized' ),
    );

	/**
	 * Filter to adjust or add order shipping statuses.
	 * An order might retrieve a shipping status e.g. not shipped.
	 *
	 * @param array $shipment_statuses Available order shipping statuses.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
    return apply_filters( 'woocommerce_gzd_order_shipping_statuses', $shipment_statuses );
}

function wc_gzd_get_shipment_order_shipping_status_name( $status ) {
    if ( 'gzd-' !== substr( $status, 0, 4 ) ) {
        $status = 'gzd-' . $status;
    }

    $status_name = '';
    $statuses    = wc_gzd_get_shipment_order_shipping_statuses();

    if ( array_key_exists( $status, $statuses ) ) {
        $status_name = $statuses[ $status ];
    }

	/**
	 * Filter to adjust the status name for a certain order shipping status.
	 *
	 * @see wc_gzd_get_shipment_order_shipping_statuses()
	 *
	 * @param string $status_name The status name.
	 * @param string $status The shipping status.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
    return apply_filters( 'woocommerce_gzd_order_shipping_status_name', $status_name, $status );
}

/**
 * Standard way of retrieving shipments based on certain parameters.
 *
 * @param  array $args Array of args (above).
 *
 * @return Shipment[] The shipments found.
 *@since  3.0.0
 */
function wc_gzd_get_shipments( $args ) {
    $query = new Vendidero\Germanized\Shipments\ShipmentQuery( $args );

    return $query->get_shipments();
}

/**
 * Main function for returning shipments.
 *
 * @param  mixed $the_shipment Object or shipment id.
 *
 * @return bool|SimpleShipment|Shipment
 */
function wc_gzd_get_shipment( $the_shipment ) {
    return ShipmentFactory::get_shipment( $the_shipment );
}

/**
 * Get all shipment statuses.
 *
 * @return array
 */
function wc_gzd_get_shipment_statuses() {
    $shipment_statuses = array(
        'gzd-draft'      => _x( 'Draft', 'shipments', 'woocommerce-germanized' ),
        'gzd-processing' => _x( 'Processing', 'shipments', 'woocommerce-germanized' ),
        'gzd-shipped'    => _x( 'Shipped', 'shipments', 'woocommerce-germanized' ),
        'gzd-delivered'  => _x( 'Delivered', 'shipments', 'woocommerce-germanized' ),
        'gzd-returned'   => _x( 'Returned', 'shipments', 'woocommerce-germanized' ),
    );

	/**
	 * Add or adjust available Shipment statuses.
	 *
	 * @param array $shipment_statuses The available shipment statuses.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
    return apply_filters( 'woocommerce_gzd_shipment_statuses', $shipment_statuses );
}

function wc_gzd_get_shipment_selectable_statuses( $type ) {
	$shipment_statuses = wc_gzd_get_shipment_statuses();

	if ( isset( $shipment_statuses['gzd-returned'] ) ) {
		unset( $shipment_statuses['gzd-returned'] );
	}

	/**
	 * Add or remove selectable Shipment statuses for a certain type.
	 *
	 * @param array $shipment_statuses The available shipment statuses.
	 * @param string $type The shipment type e.g. return.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipment_selectable_statuses', $shipment_statuses, $type );
}

/**
 * @param SimpleShipment $parent_shipment
 * @param array $args
 *
 * @return ReturnShipment|WP_Error
 */
function wc_gzd_create_return_shipment( $parent_shipment, $args = array() ) {
	try {

		if ( ! $parent_shipment || ! is_a( $parent_shipment, 'Vendidero\Germanized\Shipments\Shipment' ) ) {
			throw new Exception( _x( 'Invalid shipment.', 'shipments', 'woocommerce-germanized' ) );
		}

		if ( $parent_shipment->has_complete_return() ) {
			throw new Exception( _x( 'This shipment is already fully returned.', 'shipments', 'woocommerce-germanized' ) );
		}

		$args = wp_parse_args( $args, array(
			'items' => array(),
			'props' => array(),
		) );

		$shipment = ShipmentFactory::get_shipment( false, 'return' );

		if ( ! $shipment ) {
			throw new Exception( _x( 'Error while creating the shipment instance', 'shipments', 'woocommerce-germanized' ) );
		}

		// Make sure shipment knows its parent
		$shipment->set_parent_id( $parent_shipment->get_id() );
		$shipment->set_parent( $parent_shipment );

		$shipment->sync( $args['props'] );
		$shipment->sync_items( $args );
		$shipment->save();

	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}

	return $shipment;
}

/**
 * @param Order $order_shipment
 * @param array $args
 *
 * @return Shipment|WP_Error
 */
function wc_gzd_create_shipment( $order_shipment, $args = array() ) {
    try {

        if ( ! $order_shipment || ! is_a( $order_shipment, 'Vendidero\Germanized\Shipments\Order' ) ) {
            throw new Exception( _x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized' ) );
        }

        if ( ! $order = $order_shipment->get_order() ) {
            throw new Exception( _x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized' ) );
        }

        $args = wp_parse_args( $args, array(
            'items' => array(),
	        'props' => array(),
        ) );

        $shipment = ShipmentFactory::get_shipment( false, 'simple' );

        if ( ! $shipment ) {
	        throw new Exception( _x( 'Error while creating the shipment instance', 'shipments', 'woocommerce-germanized' ) );
        }

        $shipment->set_order_shipment( $order_shipment );
        $shipment->sync( $args['props'] );
        $shipment->sync_items( $args );
        $shipment->save();

    } catch ( Exception $e ) {
        return new WP_Error( 'error', $e->getMessage() );
    }

    return $shipment;
}

function wc_gzd_create_shipment_item( $shipment, $order_item, $args = array() ) {
    try {

        if ( ! $order_item || ! is_a( $order_item, 'WC_Order_Item' ) ) {
            throw new Exception( _x( 'Invalid order item', 'shipments', 'woocommerce-germanized' ) );
        }

        $item = new Vendidero\Germanized\Shipments\ShipmentItem();

        $item->set_order_item_id( $order_item->get_id() );
        $item->set_shipment( $shipment );
        $item->sync( $args );
        $item->save();

    } catch ( Exception $e ) {
        return new WP_Error( 'error', $e->getMessage() );
    }

    return $item;
}

function wc_gzd_create_return_shipment_item( $shipment, $parent_item, $args = array() ) {
	try {

		if ( ! $parent_item || ! is_a( $parent_item, '\Vendidero\Germanized\Shipments\ShipmentItem' ) ) {
			throw new Exception( _x( 'Invalid shipment item', 'shipments', 'woocommerce-germanized' ) );
		}

		$item = new Vendidero\Germanized\Shipments\ShipmentItem();
		$item->set_parent_id( $parent_item->get_id() );
		$item->set_shipment( $shipment );
		$item->sync( $args );
		$item->save();

	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}

	return $item;
}

function wc_gzd_get_shipment_editable_statuses() {
	/**
	 * Filter that allows to adjust Shipment statuses which decide upon whether
	 * a Shipment is editable or not.
	 *
	 * @param array $statuses Statuses which should be considered as editable.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
    return apply_filters( 'woocommerce_gzd_shipment_editable_statuses', array( 'draft', 'processing' ) );
}

function wc_gzd_split_shipment_street( $streetStr ) {
	$return = array(
		'street' => $streetStr,
		'number' => '',
	);

	try {
		$split = AddressSplitter::splitAddress( $streetStr );

		$return['street'] = $split['streetName'];
		$return['number'] = $split['houseNumber'];
		
	} catch( Exception $e ) {}

	return $return;
}

function wc_gzd_get_shipping_providers() {
	/**
	 * Filter that allows third-parties to add custom shipping providers (e.g. DHL) to Shipments.
	 *
	 * @param array $providers Array containing key => value pairs of providers and their title or description.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipping_providers', array() );
}

function wc_gzd_get_shipping_provider_title( $slug ) {
	$providers = wc_gzd_get_shipping_providers();

	if ( array_key_exists( $slug, $providers ) ) {
		$title = $providers[ $slug ];
	} else {
		$title = $slug;
	}

	/**
	 * Filter to adjust the title of a certain shipping provider e.g. DHL.
	 *
	 * @param string  $title The shipping provider title.
	 * @param string  $slug The shipping provider slug.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipping_provider_title', $title, $slug );
}

function wc_gzd_get_shipping_provider_slug( $provider ) {
	$providers = wc_gzd_get_shipping_providers();

	if ( in_array( $provider, $providers ) ) {
		$slug = array_search( $provider, $providers );
	} elseif( array_key_exists( $provider, $providers ) ) {
		$slug = $provider;
	} else {
		$slug = sanitize_key( $provider );
	}

	return $slug;
}

/**
 * @param SimpleShipment $parent_shipment
 *
 * @return array
 */
function wc_gzd_get_shipment_return_address( $parent_shipment ) {
	$country_state = wc_format_country_state_string( Package::get_setting( 'return_address_country' ) );

	$address = array(
		'first_name' => Package::get_setting( 'return_address_first_name' ),
		'last_name'  => Package::get_setting( 'return_address_last_name' ),
		'company'    => Package::get_setting( 'return_address_company' ),
		'address_1'  => Package::get_setting( 'return_address_address_1' ),
		'address_2'  => Package::get_setting( 'return_address_address_2' ),
		'city'       => Package::get_setting( 'return_address_city' ),
		'country'    => $country_state['country'],
		'state'      => $country_state['state'],
		'postcode'   => Package::get_setting( 'return_address_postcode' ),
 	);

	$address['email'] = get_option( 'admin_email' );

	return $address;
}

/**
 * @param WC_Order $order
 */
function wc_gzd_get_shipment_order_shipping_method_id( $order ) {
	$methods = $order->get_shipping_methods();
	$id      = '';

	if ( ! empty( $methods ) ) {
		$method_vals = array_values( $methods );
		$method      = array_shift( $method_vals );

		if ( $method ) {
			$id = $method->get_method_id() . ':' . $method->get_instance_id();
		}
	}

	/**
	 * Allows adjusting the shipping method id for a certain Order.
	 *
	 * @param string   $id The shipping method id.
	 * @param WC_Order $order The order object.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipment_order_shipping_method_id', $id, $order );
}

function wc_gzd_render_shipment_action_buttons( $actions ) {
	$actions_html = '';

	foreach ( $actions as $action ) {
		if ( isset( $action['group'] ) ) {
			$actions_html .= '<div class="wc-gzd-shipment-action-button-group"><label>' . $action['group'] . '</label> <span class="wc-gzd-shipment-action-button-group__items">' . wc_gzd_render_shipment_action_buttons( $action['actions'] ) . '</span></div>';
		} elseif ( isset( $action['action'], $action['url'], $action['name'] ) ) {
			$target = isset( $action['target'] ) ? $action['target'] : '_self';

			$actions_html .= sprintf( '<a class="button wc-gzd-shipment-action-button wc-gzd-shipment-action-button-%1$s %1$s" href="%2$s" aria-label="%3$s" title="%3$s" target="%4$s">%5$s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( isset( $action['title'] ) ? $action['title'] : $action['name'] ), $target, esc_html( $action['name'] ) );
		}
	}

	return $actions_html;
}

function wc_gzd_get_shipment_status_name( $status ) {
    if ( 'gzd-' !== substr( $status, 0, 4 ) ) {
        $status = 'gzd-' . $status;
    }

    $status_name = '';
    $statuses    = wc_gzd_get_shipment_statuses();

    if ( array_key_exists( $status, $statuses ) ) {
        $status_name = $statuses[ $status ];
    }

	/**
	 * Filter to adjust the shipment status name or title.
	 *
	 * @param string  $status_name The status name or title.
	 * @param integer $status The status slug.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
    return apply_filters( 'woocommerce_gzd_shipment_status_name', $status_name, $status );
}

function wc_gzd_get_shipment_sent_statuses() {
	/**
	 * Filter to adjust which Shipment statuses should be considered as sent.
	 *
	 * @param array $statuses An array of statuses considered as shipped,
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
    return apply_filters( 'woocommerce_gzd_shipment_sent_statuses', array(
        'shipped',
        'delivered',
        'returned'
    ) );
}

function wc_gzd_get_shipment_counts( $type = '' ) {
    $counts = array();

    foreach( array_keys( wc_gzd_get_shipment_statuses() ) as $status ) {
        $counts[ $status ] = wc_gzd_get_shipment_count( $status, $type );
    }

    return $counts;
}

function wc_gzd_get_shipment_count( $status, $type = '' ) {
    $count             = 0;
    $status            = ( substr( $status, 0, 4 ) ) === 'gzd-' ? $status : 'gzd-' . $status;
    $shipment_statuses = array_keys( wc_gzd_get_shipment_statuses() );

    if ( ! in_array( $status, $shipment_statuses, true ) ) {
        return 0;
    }

    $cache_key    = WC_Cache_Helper::get_cache_prefix( 'shipments' ) . $status . $type;
    $cached_count = wp_cache_get( $cache_key, 'counts' );

    if ( false !== $cached_count ) {
        return $cached_count;
    }

    $data_store = WC_Data_Store::load( 'shipment' );

    if ( $data_store ) {
        $count += $data_store->get_shipment_count( $status, $type );
    }

    wp_cache_set( $cache_key, $count, 'counts' );

    return $count;
}

/**
 * See if a string is a shipment status.
 *
 * @param  string $maybe_status Status, including any gzd- prefix.
 * @return bool
 */
function wc_gzd_is_shipment_status( $maybe_status ) {
    $shipment_statuses = wc_gzd_get_shipment_statuses();

    return isset( $shipment_statuses[ $maybe_status ] );
}

/**
 * Main function for returning shipment items.
 *
 * @since  2.2
 *
 * @param  mixed $the_shipment Object or shipment item id.
 *
 * @return bool|WC_GZD_Shipment_Item
 */
function wc_gzd_get_shipment_item( $the_item = false ) {
    $item_id = wc_gzd_get_shipment_item_id( $the_item );

    if ( ! $item_id ) {
        return false;
    }

	/**
	 * Filter to adjust the classname used to construct a ShipmentItem.
	 *
	 * @param string  $classname The classname to be used.
	 * @param integer $item_id The shipment item id.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
    $classname = apply_filters( 'woocommerce_gzd_shipment_item_class', 'Vendidero\Germanized\Shipments\ShipmentItem', $item_id );

    if ( ! class_exists( $classname ) ) {
        return false;
    }

    try {
        return new $classname( $item_id );
    } catch ( Exception $e ) {
        wc_caught_exception( $e, __FUNCTION__, func_get_args() );
        return false;
    }
}

/**
 * Get the shipment item ID depending on what was passed.
 *
 * @since 3.0.0
 * @param  mixed $item Item data to convert to an ID.
 * @return int|bool false on failure
 */
function wc_gzd_get_shipment_item_id( $item ) {
    if ( is_numeric( $item ) ) {
        return $item;
    } elseif ( $item instanceof Vendidero\Germanized\Shipments\ShipmentItem ) {
        return $item->get_id();
    } elseif ( ! empty( $item->shipment_item_id ) ) {
        return $item->shipment_item_id;
    } else {
        return false;
    }
}

/**
 * Format dimensions for display.
 *
 * @since  3.0.0
 * @param  array $dimensions Array of dimensions.
 * @return string
 */
function wc_gzd_format_shipment_dimensions( $dimensions ) {
    $dimension_string = implode( ' &times; ', array_filter( array_map( 'wc_format_localized_decimal', $dimensions ) ) );

    if ( ! empty( $dimension_string ) ) {
        $dimension_string .= ' ' . 'cm';
    } else {
        $dimension_string = _x( 'N/A', 'shipments', 'woocommerce-germanized' );
    }

	/**
	 * Filter to adjust the format of Shipment dimensions e.g. LxBxH.
	 *
	 * @param string  $dimension_string The dimension string.
	 * @param array   $dimensions Array containing the dimensions.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
    return apply_filters( 'woocommerce_gzd_format_shipment_dimensions', $dimension_string, $dimensions );
}

/**
 * Format a weight for display.
 *
 * @since  3.0.0
 * @param  float $weight Weight.
 * @return string
 */
function wc_gzd_format_shipment_weight( $weight ) {
    $weight_string = wc_format_localized_decimal( $weight );

    if ( ! empty( $weight_string ) ) {
        $weight_string .= ' ' . 'kg';
    } else {
        $weight_string = _x( 'N/A', 'shipments', 'woocommerce-germanized' );
    }

	/**
	 * Filter to adjust the format of Shipment weight.
	 *
	 * @param string  $weight_string The weight string.
	 * @param string  $weight The Shipment weight.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
    return apply_filters( 'woocommerce_gzd_format_shipment_weight', $weight_string, $weight );
}

/**
 * Get My Account > Shipments columns.
 *
 * @since 3.0.0
 * @return array
 */
function wc_gzd_get_account_shipments_columns() {
	/**
	 * Filter to adjust columns being used to display shipments in a table view on the customer
	 * account page.
	 *
	 * @param string[] $columns The columns in key => value pairs.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	$columns = apply_filters(
		'woocommerce_gzd_account_shipments_columns',
		array(
			'shipment-number'   => _x( 'Shipment', 'shipments', 'woocommerce-germanized' ),
			'shipment-date'     => _x( 'Date', 'shipments', 'woocommerce-germanized' ),
			'shipment-status'   => _x( 'Status', 'shipments', 'woocommerce-germanized' ),
			'shipment-tracking' => _x( 'Tracking', 'shipments', 'woocommerce-germanized' ),
			'shipment-actions'  => _x( 'Actions', 'shipments', 'woocommerce-germanized' ),
		)
	);

	return $columns;
}

/**
 * Get account shipments actions.
 *
 * @since  3.2.0
 * @param  int|Shipment $shipment Shipment instance or ID.
 * @return array
 */
function wc_gzd_get_account_shipments_actions( $shipment ) {

	if ( ! is_object( $shipment ) ) {
		$shipment_id = absint( $shipment );
		$shipment    = wc_gzd_get_shipment( $shipment_id );
	}

	$actions = array(
		'view'   => array(
			'url'  => $shipment->get_view_shipment_url(),
			'name' => _x( 'View', 'shipments', 'woocommerce-germanized' ),
		),
	);

	/**
	 * Filter to adjust available actions in the shipments table view on the customer account page
	 * for a specific shipment.
	 *
	 * @param string[] $actions Available actions containing an id as key and a URL and name.
	 * @param Shipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_account_shipments_actions', $actions, $shipment );
}
