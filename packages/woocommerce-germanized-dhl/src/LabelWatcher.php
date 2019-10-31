<?php

namespace Vendidero\Germanized\DHL;
use Exception;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ShipmentItem;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class LabelWatcher {

	/**
	 * Init the package - load the REST API Server class.
	 */
	public static function init() {

		// Create labels if they do not yet exist
		add_action( 'woocommerce_gzd_dhl_before_create_label', array( __CLASS__, 'create_label' ), 10, 1 );
		add_action( 'woocommerce_gzd_dhl_before_update_label', array( __CLASS__, 'update_label' ), 10, 1 );

		// Create labels if they do not yet exist
		add_action( 'woocommerce_gzd_dhl_before_create_return_label', array( __CLASS__, 'create_return_label' ), 10, 1 );
		add_action( 'woocommerce_gzd_dhl_before_update_reutrn_label', array( __CLASS__, 'update_return_label' ), 10, 1 );

		// Delete label
		add_action( 'woocommerce_gzd_dhl_label_deleted', array( __CLASS__, 'delete_label' ), 10, 2 );

		// Delete the label if parent shipment has been deleted
		add_action( 'woocommerce_gzd_shipment_deleted', array( __CLASS__, 'deleted_shipment' ), 10, 2 );
		add_action( 'woocommerce_gzd_return_shipment_deleted', array( __CLASS__, 'deleted_shipment' ), 10, 2 );

		// Sync shipment items
		add_action( 'woocommerce_gzd_shipment_item_synced', array( __CLASS__, 'sync_item_meta' ), 10, 3 );

		// Add shipment tracking url
		add_filter( 'woocommerce_gzd_shipment_get_tracking_url', array( __CLASS__, 'add_tracking_url' ), 10, 2 );
		add_filter( 'woocommerce_gzd_return_shipment_get_tracking_url', array( __CLASS__, 'add_tracking_url' ), 10, 2 );

		add_action( 'woocommerce_gzd_shipment_synced', array( __CLASS__, 'maybe_set_shipping_provider' ), 10, 3 );
	}

	/**
	 * @param Shipment $shipment
	 * @param \Vendidero\Germanized\Shipments\Order $order_shipment
	 * @param array $args
	 */
	public static function maybe_set_shipping_provider( $shipment, $order_shipment, $args ) {
		if ( $shipping_method = $shipment->get_shipping_method() ) {

			if ( $dhl_shipping_method = wc_gzd_dhl_get_shipping_method( $shipping_method ) ) {

				if ( $dhl_shipping_method->is_dhl_enabled() ) {
					$shipment->set_shipping_provider( 'dhl' );
				}
			}
		}
	}

	/**
	 * @param string $url
	 * @param Shipment $shipment
	 *
	 * @return string
	 */
	public static function add_tracking_url( $url, $shipment ) {

		if ( $label = wc_gzd_dhl_get_shipment_label( $shipment ) ) {
			return $label->get_tracking_url();
		}

		return $url;
	}

	/**
	 * @param ShipmentItem $item
	 * @param WC_Order_Item $order_item
	 * @param $args
	 */
	public static function sync_item_meta( $item, $order_item, $args ) {
		if ( $product = $item->get_product() ) {
			$dhl_product = wc_gzd_dhl_get_product( $product );

			$item->update_meta_data( '_dhl_hs_code', $dhl_product->get_hs_code() );
			$item->update_meta_data( '_dhl_manufacture_country', $dhl_product->get_manufacture_country() );
		}
	}

	public static function create_label( $label ) {
		try {
			Package::get_api()->get_label( $label );
			self::maybe_update_shipment_tracking( $label );
		} catch( Exception $e ) {
			throw new Exception( nl2br( $e->getMessage() ) );
		}
	}

	public static function create_return_label( $label ) {
		try {
			Package::get_api()->get_return_label( $label );
			self::maybe_update_shipment_tracking( $label );
		} catch( Exception $e ) {
			throw new Exception( nl2br( $e->getMessage() ) );
		}
	}

	protected static function maybe_update_shipment_tracking( $label ) {
		// Add tracking id to shipment
		if ( ( $shipment = $label->get_shipment() ) && $label->get_number() ) {
			$shipment->set_tracking_id( $label->get_number() );
			$shipment->save();
		}
	}

	public static function update_label( $label ) {
		try {
			Package::get_api()->get_label( $label );
			self::maybe_update_shipment_tracking( $label );
		} catch( Exception $e ) {
			throw new Exception( nl2br( $e->getMessage() ) );
		}
	}

	public static function update_return_label( $label ) {
		try {
			Package::get_api()->get_return_label( $label );
			self::maybe_update_shipment_tracking( $label );
		} catch( Exception $e ) {
			throw new Exception( nl2br( $e->getMessage() ) );
		}
	}

	public static function delete_label( $label_id, $label ) {
		try {
			Package::get_api()->delete_label( $label );
			self::delete_shipment_tracking( $label );
		} catch( Exception $e ) {}
	}

	protected static function delete_shipment_tracking( $label ) {
		// Remove shipment data
		if ( ( $shipment = $label->get_shipment() ) ) {
			$shipment->set_tracking_id( '' );
			$shipment->save();
		}
	}

	public static function deleted_shipment( $shipment_id, $shipment ) {
		if ( $label = wc_gzd_dhl_get_shipment_label( $shipment_id ) ) {
			$label->delete( true );
		}
	}
}
