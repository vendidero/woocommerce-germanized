<?php

namespace Vendidero\Germanized\DHL;
use Exception;
use Vendidero\Germanized\Shipments\Shipment;
use WC_Order_Item;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Automation {

	/**
	 * Init the package - load the REST API Server class.
	 */
	public static function init() {
		add_action( 'woocommerce_gzd_shipment_before_status_change', array( __CLASS__, 'set_automation' ), 10, 2 );

		// Watch shipment creations - e.g. default status is set to shipped - needs to trigger label generation
		add_action( 'woocommerce_gzd_new_shipment', array( __CLASS__, 'set_after_create_automation' ), 10, 2 );
		add_action( 'woocommerce_gzd_new_return_shipment', array( __CLASS__, 'set_after_create_automation' ), 10, 2 );

		// After a label has been successfully created - maybe update shipment status
		add_action( 'woocommerce_gzd_shipment_created_dhl_label', array( __CLASS__, 'maybe_adjust_shipment_status' ), 10 );
	}

	/**
	 * @param Shipment $shipment
	 */
	public static function maybe_adjust_shipment_status( $shipment ) {
		if ( 'yes' === Package::get_setting( 'label_auto_shipment_status_shipped' ) ) {
			$shipment->set_status( 'shipped' );
		}
	}

	public static function set_after_create_automation( $shipment_id, $shipment ) {
		self::do_automation( $shipment, false );
	}

	/**
	 * @param Shipment $shipment
	 * @param boolean $is_hook
	 */
	protected static function do_automation( $shipment, $is_hook = true ) {
		$disable = false;

		if ( ! wc_gzd_dhl_shipment_needs_label( $shipment, false ) ) {
			$disable = true;
		}

		/**
		 * Filter that allows to disable automatically creating DHL labels for a certain shipment.
		 *
		 * @param boolean  $disable True if you want to disable automation.
		 * @param Shipment $shipment The shipment object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		$disable = apply_filters( 'woocommerce_gzd_dhl_disable_label_auto_generate', $disable, $shipment );

		if ( $disable ) {
			return;
		}

		$shipping_method = wc_gzd_dhl_get_shipping_method( $shipment->get_shipping_method() );
		$setting         = 'return' === $shipment->get_type() ? 'label_return_auto_enable' : 'label_auto_enable';
		$setting_status  = 'return' === $shipment->get_type() ? 'label_return_auto_shipment_status' : 'label_auto_shipment_status';
		$hook_prefix     = 'woocommerce_gzd_' . ( 'return' === $shipment->get_type() ? 'return_' : '' ) . 'shipment_status_';

		if ( 'yes' === Package::get_setting( $setting, $shipping_method ) ) {
			$status = Package::get_setting( $setting_status, $shipping_method );

			if ( ! empty( $status ) ) {
				$status = str_replace( 'gzd-', '', $status );

				if ( $is_hook ) {
					add_action( $hook_prefix . $status, array( __CLASS__, 'maybe_create_label' ), 10, 2 );
				} elseif( $shipment->has_status( $status ) ) {
					self::maybe_create_label( $shipment->get_id(), $shipment );
				}
			}
		}
	}

	/**
	 * @param $shipment_id
	 * @param Shipment $shipment
	 */
	public static function set_automation( $shipment_id, $shipment ) {
		self::do_automation( $shipment, true );
	}

	public static function create_label( $shipment_id, $shipment = false ) {
		if ( ! $shipment ) {
			$shipment = wc_gzd_get_shipment( $shipment_id );

			if ( ! $shipment ) {
				return;
			}
		}

		if ( ! $shipment->has_label() ) {
			$result = $shipment->create_label();

			if ( ! is_wp_error( $result ) ) {}
		}
	}

	private static function is_admin_edit_order_request() {
		return ( isset( $_POST['action'] ) && 'editpost' === $_POST['action'] && isset( $_POST['post_type'] ) && 'shop_order' === $_POST['post_type'] );
	}

	public static function maybe_create_label( $shipment_id, $shipment = false ) {
		// Make sure that MetaBox is saved before we process automation
		if ( self::is_admin_edit_order_request() ) {
			add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'create_label' ), 70 );
		} else {
			self::create_label( $shipment_id, $shipment );
		}
	}
}
