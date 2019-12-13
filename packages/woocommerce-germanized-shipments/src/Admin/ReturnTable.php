<?php

namespace Vendidero\Germanized\Shipments\Admin;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ReturnShipment;
use WP_List_Table;
use Vendidero\Germanized\Shipments\ShipmentQuery;

defined( 'ABSPATH' ) || exit;

/**
 * Class Table
 * @package Vendidero/Germanized/Shipments\Admin
 */
class ReturnTable extends Table {

	protected function get_custom_columns() {
		$columns = array();

		$columns['cb']         = '<input type="checkbox" />';
		$columns['title']      = _x( 'Title', 'shipments', 'woocommerce-germanized' );
		$columns['date']       = _x( 'Date', 'shipments', 'woocommerce-germanized' );
		$columns['status']     = _x( 'Status', 'shipments', 'woocommerce-germanized' );
		$columns['items']      = _x( 'Items', 'shipments', 'woocommerce-germanized' );
		$columns['sender']     = _x( 'Sender', 'shipments', 'woocommerce-germanized' );
		$columns['shipment']   = _x( 'Shipment', 'shipments', 'woocommerce-germanized' );
		$columns['actions']    = _x( 'Actions', 'shipments', 'woocommerce-germanized' );

		return $columns;
	}

	/**
	 * @param ReturnShipment $shipment
	 * @param $actions
	 *
	 * @return mixed
	 */
	protected function get_custom_actions( $shipment, $actions ) {

		if ( isset( $actions['shipped'] ) ) {
			unset( $actions['shipped'] );
		}

		if ( ! $shipment->has_status( 'delivered' ) ) {
			$actions['received'] = array(
				'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_gzd_update_shipment_status&status=delivered&shipment_id=' . $shipment->get_id() ), 'update-shipment-status' ),
				'name'   => _x( 'Delivered', 'shipments', 'woocommerce-germanized' ),
				'action' => 'delivered',
			);
		}

		if ( $shipment->supports_label() ) {

			if ( $shipment->has_label() ) {
				$actions['email_label'] = array(
					'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_gzd_send_shipment_return_label_email&shipment_id=' . $shipment->get_id() ), 'send-shipment-return-label' ),
					'name'   => _x( 'Send label to customer', 'shipments', 'woocommerce-germanized' ),
					'action' => 'send-label email',
				);
			}
		}

		return $actions;
	}

	public function get_main_page() {
		return 'admin.php?page=wc-gzd-return-shipments';
	}

	protected function get_custom_bulk_actions( $actions ) {
		return $actions;
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param ReturnShipment $shipment The current shipment object.
	 */
	public function column_sender( $shipment ) {
		$address = $shipment->get_formatted_sender_address();

		if ( $address ) {
			echo '<a target="_blank" href="' . esc_url( $shipment->get_address_map_url( $shipment->get_sender_address() ) ) . '">' . esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) ) . '</a>';
		} else {
			echo '&ndash;';
		}
	}

	/**
	 * Handles the post author column output.
	 *
	 * @since 4.3.0
	 *
	 * @param ReturnShipment $shipment The current shipment object.
	 */
	public function column_shipment( $shipment ) {
		if ( ( $parent = $shipment->get_parent() ) ) {
			echo '<a href="' . $parent->get_edit_shipment_url() . '">' . $parent->get_shipment_number() . '</a>';
		} else {
			echo '&ndash;';
		}
	}
}
