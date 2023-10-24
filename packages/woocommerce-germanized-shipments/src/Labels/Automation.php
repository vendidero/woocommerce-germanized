<?php

namespace Vendidero\Germanized\Shipments\Labels;

use Exception;
use Vendidero\Germanized\Shipments\Package;
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
		add_action( 'woocommerce_gzd_shipment_created_label', array( __CLASS__, 'maybe_adjust_shipment_status' ), 10 );
	}

	/**
	 * @param Shipment $shipment
	 */
	public static function maybe_adjust_shipment_status( $shipment ) {
		if ( $provider = $shipment->get_shipping_provider_instance() ) {
			if ( $provider->automatically_set_shipment_status_shipped( $shipment ) ) {
				$shipment->set_status( 'shipped' );
			}
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

		if ( ! $shipment->needs_label( false ) ) {
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
		$disable = apply_filters( 'woocommerce_gzd_shipments_disable_label_auto_generate', $disable, $shipment );

		if ( $disable ) {
			return;
		}

		if ( $provider = $shipment->get_shipping_provider_instance() ) {
			$hook_prefix = 'woocommerce_gzd_' . ( 'return' === $shipment->get_type() ? 'return_' : '' ) . 'shipment_status_';
			$auto_status = $provider->get_label_automation_shipment_status( $shipment );

			if ( $provider->automatically_generate_label( $shipment ) && ! empty( $auto_status ) ) {
				$status = str_replace( 'gzd-', '', $auto_status );

				if ( $is_hook ) {
					add_action( $hook_prefix . $status, array( __CLASS__, 'maybe_create_label' ), 5, 2 );
				} elseif ( $shipment->has_status( $status ) ) {
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

			if ( is_wp_error( $result ) ) {
				$result = wc_gzd_get_shipment_error( $result );
			}

			if ( is_wp_error( $result ) ) {
				if ( $result->is_soft_error() ) {
					Package::log( sprintf( 'Info while automatically creating label for %1$s: %2$s', $shipment->get_shipment_number(), wc_print_r( $result->get_error_messages(), true ) ) );
				} else {
					Package::log( sprintf( 'Error while automatically creating label for %1$s: %2$s', $shipment->get_shipment_number(), wc_print_r( $result->get_error_messages(), true ) ) );
				}
			}
		}
	}

	private static function is_admin_edit_order_request() {
		return ( isset( $_POST['action'] ) && 'editpost' === $_POST['action'] && isset( $_POST['post_type'] ) && 'shop_order' === $_POST['post_type'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	public static function maybe_create_label( $shipment_id, $shipment = false ) {
		// Make sure that MetaBox is saved before we process automation
		if ( self::is_admin_edit_order_request() && ! did_action( 'woocommerce_process_shop_order_meta' ) ) {
			add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'create_label' ), 70 );
		} else {
			self::create_label( $shipment_id, $shipment );
		}
	}
}
