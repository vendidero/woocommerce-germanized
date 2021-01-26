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
		add_action( 'woocommerce_gzd_dhl_before_update_return_label', array( __CLASS__, 'update_return_label' ), 10, 1 );

		// Create post labels if they do not yet exist
		add_action( 'woocommerce_gzd_dhl_before_create_deutsche_post_label', array( __CLASS__, 'create_post_label' ), 10, 1 );
		add_action( 'woocommerce_gzd_dhl_before_update_deutsche_post_label', array( __CLASS__, 'update_post_label' ), 10, 1 );

		// Create post return labels if they do not yet exist
		add_action( 'woocommerce_gzd_dhl_before_create_deutsche_post_return_label', array( __CLASS__, 'create_post_label' ), 10, 1 );
		add_action( 'woocommerce_gzd_dhl_before_update_deutsche_post_return_label', array( __CLASS__, 'update_post_label' ), 10, 1 );

		// Delete label
		add_action( 'woocommerce_gzd_dhl_label_deleted', array( __CLASS__, 'delete_label' ), 10, 2 );
		add_action( 'woocommerce_gzd_dhl_deutsche_post_label_deleted', array( __CLASS__, 'delete_post_label' ), 10, 2 );
		add_action( 'woocommerce_gzd_dhl_deutsche_post_return_label_deleted', array( __CLASS__, 'delete_post_label' ), 10, 2 );

		// Sync shipment items
		add_action( 'woocommerce_gzd_shipment_item_synced', array( __CLASS__, 'sync_item_meta' ), 10, 3 );
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
		} catch( Exception $e ) {
			throw new Exception( nl2br( $e->getMessage() ) );
		}
	}

	public static function create_return_label( $label ) {
		try {
			Package::get_api()->get_return_label( $label );
		} catch( Exception $e ) {
			throw new Exception( nl2br( $e->getMessage() ) );
		}
	}

	/**
	 * @param DeutschePostLabel $label
	 *
	 * @throws Exception
	 */
	public static function create_post_label( $label ) {
		try {
			Package::get_internetmarke_api()->get_label( $label );
		} catch( Exception $e ) {
			throw new Exception( nl2br( $e->getMessage() ) );
		}
	}

	public static function update_label( $label ) {
		try {
			Package::get_api()->get_label( $label );
		} catch( Exception $e ) {
			throw new Exception( nl2br( $e->getMessage() ) );
		}
	}

	public static function update_return_label( $label ) {
		try {
			Package::get_api()->get_return_label( $label );
		} catch( Exception $e ) {
			throw new Exception( nl2br( $e->getMessage() ) );
		}
	}

	public static function update_post_label( $label ) {
		try {
			Package::get_internetmarke_api()->get_label( $label );
		} catch( Exception $e ) {
			throw new Exception( nl2br( $e->getMessage() ) );
		}
	}

	public static function delete_post_label( $label_id, $label ) {
		try {
			Package::get_internetmarke_api()->delete_label( $label );
		} catch( Exception $e ) {}
	}

	public static function delete_label( $label_id, $label ) {
		try {
			Package::get_api()->delete_label( $label );
		} catch( Exception $e ) {}
	}
}
