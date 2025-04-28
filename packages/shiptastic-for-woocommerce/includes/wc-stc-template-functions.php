<?php
/**
 * WooCommerce Template
 *
 * Functions for the templating system.
 *
 * @package  WooCommerce\Functions
 * @version  2.6.0
 */

use Vendidero\Shiptastic\Shipment;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_stc_get_email_shipment_items' ) ) {
	/**
	 * Get HTML for the order items to be shown in emails.
	 *
	 * @param Shipment $shipment Shipment object.
	 * @param array           $args Arguments.
	 *
	 * @return string
	 */
	function wc_stc_get_email_shipment_items( $shipment, $args = array() ) {
		ob_start();

		$defaults = array(
			'show_sku'      => false,
			'show_image'    => false,
			'image_size'    => array( 32, 32 ),
			'plain_text'    => false,
			'sent_to_admin' => false,
		);

		$args     = wp_parse_args( $args, $defaults );
		$template = $args['plain_text'] ? 'emails/plain/email-shipment-items.php' : 'emails/email-shipment-items.php';

		wc_get_template(
			$template,
			/**
			 * Filter to adjust the arguments passed to retrieving ShipmentItems for display in an Email.
			 *
			 * @param array $args Array containing the arguments passed.
			 *
			 * @package Vendidero/Shiptastic
			 */
			apply_filters(
				'woocommerce_shiptastic_email_shipment_items_args',
				array(
					'shipment'      => $shipment,
					'items'         => $shipment->get_items( 'customer' ),
					'show_sku'      => $args['show_sku'],
					'show_image'    => $args['show_image'],
					'image_size'    => $args['image_size'],
					'plain_text'    => $args['plain_text'],
					'sent_to_admin' => $args['sent_to_admin'],
				)
			)
		);

		/**
		 * Filter that allows adjusting the HTML output of the shipment email item table.
		 *
		 * @param string   $html The HTML output.
		 * @param Shipment $shipment The shipment instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return apply_filters( 'woocommerce_shiptastic_email_shipment_items_table', ob_get_clean(), $shipment );
	}
}

if ( ! function_exists( 'woocommerce_shiptastic_template_view_shipments' ) ) {

	function woocommerce_shiptastic_template_view_shipments( $order_id ) {
		if ( ! ( $order = wc_get_order( $order_id ) ) ) {
			echo '<div class="woocommerce-error">' . esc_html_x( 'Invalid order.', 'shipments', 'woocommerce-germanized' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="wc-forward">' . esc_html_x( 'My account', 'shipments', 'woocommerce-germanized' ) . '</a></div>';
			return;
		}

		$shipments = wc_stc_get_shipments(
			array(
				'order_id' => $order_id,
				'type'     => 'simple',
				'status'   => wc_stc_get_shipment_customer_visible_statuses(),
			)
		);

		$returns = wc_stc_get_shipments(
			array(
				'order_id' => $order_id,
				'type'     => 'return',
				'status'   => wc_stc_get_shipment_customer_visible_statuses( 'return' ),
			)
		);

		wc_get_template(
			'myaccount/order-shipments.php',
			array(
				'order_id'  => $order_id,
				'order'     => $order,
				'shipments' => $shipments,
				'returns'   => $returns,
			)
		);
	}
}

if ( ! function_exists( 'woocommerce_shiptastic_template_view_endpoint_shipments' ) ) {

	function woocommerce_shiptastic_template_view_endpoint_shipments( $order_id ) {
		if ( ! current_user_can( 'view_order', $order_id ) ) {
			echo '<div class="woocommerce-error">' . esc_html_x( 'Invalid order.', 'shipments', 'woocommerce-germanized' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="wc-forward">' . esc_html_x( 'My account', 'shipments', 'woocommerce-germanized' ) . '</a></div>';
			return;
		}

		woocommerce_shiptastic_template_view_shipments( $order_id );
	}
}

if ( ! function_exists( 'woocommerce_shiptastic_template_view_shipment' ) ) {

	function woocommerce_shiptastic_template_view_shipment( $shipment_id ) {

		if ( ( ! ( $shipment = wc_stc_get_shipment( $shipment_id ) ) ) || ( ! current_user_can( 'view_order', $shipment->get_order_id() ) ) ) {
			echo '<div class="woocommerce-error">' . esc_html_x( 'Invalid shipment.', 'shipments', 'woocommerce-germanized' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="wc-forward">' . esc_html_x( 'My account', 'shipments', 'woocommerce-germanized' ) . '</a></div>';

			return;
		}

		wc_get_template(
			'myaccount/view-shipment.php',
			array(
				'shipment'    => $shipment,
				'shipment_id' => $shipment_id,
			)
		);
	}
}

if ( ! function_exists( 'woocommerce_shiptastic_template_add_return_shipment' ) ) {

	function woocommerce_shiptastic_template_add_return_shipment( $order_id ) {
		if ( ( ! ( $order = wc_get_order( $order_id ) ) ) || ( ! wc_stc_customer_can_add_return_shipment( $order_id ) ) ) {
			echo '<div class="woocommerce-error">' . esc_html_x( 'Invalid order.', 'shipments', 'woocommerce-germanized' ) . ' <a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="wc-forward">' . esc_html_x( 'My account', 'shipments', 'woocommerce-germanized' ) . '</a></div>';
			return;
		}

		if ( ! wc_stc_order_is_customer_returnable( $order_id ) ) {
			echo '<div class="woocommerce-error">' . esc_html_x( 'Currently you cannot add new return requests to that order. If you have questions regarding the return of that order please contact us for further information.', 'shipments', 'woocommerce-germanized' ) . ( is_user_logged_in() ? ' <a href="' . esc_url( $order->get_view_order_url() ) . '" class="wc-forward">' . esc_html_x( 'View order', 'shipments', 'woocommerce-germanized' ) . '</a>' : '' ) . '</div>';
			return;
		}

		$notices = function_exists( 'wc_print_notices' ) ? wc_print_notices( true ) : '';

		// Output notices in case notices have not been outputted yet.
		if ( ! empty( $notices ) ) {
			echo '<div class="woocommerce">' . wc_kses_notice( $notices ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		wc_get_template(
			'myaccount/add-return-shipment.php',
			array(
				'order'          => $order,
				'order_id'       => $order_id,
				'shipment_order' => wc_stc_get_shipment_order( $order ),
			)
		);
	}
}

if ( ! function_exists( 'woocommerce_stc_shipment_details_table' ) ) {

	function woocommerce_stc_shipment_details_table( $shipment_id ) {
		if ( ! $shipment_id ) {
			return;
		}

		wc_get_template(
			'shipment/shipment-details.php',
			array(
				'shipment_id' => $shipment_id,
			)
		);
	}
}

if ( ! function_exists( 'woocommerce_stc_return_shipments_template_instructions' ) ) {

	function woocommerce_stc_return_shipments_template_instructions( $shipment_id ) {
		if ( $shipment = wc_stc_get_shipment( $shipment_id ) ) {

			if ( 'return' !== $shipment->get_type() ) {
				return;
			}

			if ( ! $shipment->has_status( array( 'processing', 'sent' ) ) ) {
				return;
			}

			wc_get_template(
				'shipment/shipment-return-instructions.php',
				array(
					'shipment'    => $shipment,
					'shipment_id' => $shipment_id,
				)
			);
		}
	}
}

if ( ! function_exists( 'woocommerce_shiptastic_template_non_returnable_items_note' ) ) {

	function woocommerce_shiptastic_template_non_returnable_items_note( $order ) {
		if ( $shipment_order = wc_stc_get_shipment_order( $order ) ) {
			$non_returnable_items = $shipment_order->get_non_returnable_items();

			if ( ! empty( $non_returnable_items ) ) {
				$product_names = array();

				foreach ( $non_returnable_items as $non_returnable_item ) {
					$product_names[] = $non_returnable_item['name'];
				}

				if ( ! empty( $product_names ) ) {
					echo '<p class="woocommerce-add-return-non-returnable-list"><small>' . sprintf( esc_html_x( 'The following products cannot be returned due to their nature: %1$s', 'shipments', 'woocommerce-germanized' ), esc_html( implode( ', ', $product_names ) ) ) . '</small></p>';
				}
			}
		}
	}
}
