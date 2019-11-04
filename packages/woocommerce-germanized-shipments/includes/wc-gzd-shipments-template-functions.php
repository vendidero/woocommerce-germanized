<?php
/**
 * WooCommerce Template
 *
 * Functions for the templating system.
 *
 * @package  WooCommerce\Functions
 * @version  2.5.0
 */

use Vendidero\Germanized\Shipments\Shipment;

defined('ABSPATH') || exit;

if ( ! function_exists( 'wc_gzd_get_email_shipment_items' ) ) {
    /**
     * Get HTML for the order items to be shown in emails.
     *
     * @param Shipment $shipment Shipment object.
     * @param array           $args Arguments.
     *
     * @since 3.0.0
     * @return string
     */
    function wc_gzd_get_email_shipment_items( $shipment, $args = array() ) {
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
	         * @since 3.0.0
	         * @package Vendidero/Germanized/Shipments
	         */
            apply_filters(
                'woocommerce_gzd_email_shipment_items_args',
                array(
                    'shipment'            => $shipment,
                    'items'               => $shipment->get_items(),
                    'show_sku'            => $args['show_sku'],
                    'show_image'          => $args['show_image'],
                    'image_size'          => $args['image_size'],
                    'plain_text'          => $args['plain_text'],
                    'sent_to_admin'       => $args['sent_to_admin'],
                )
            )
        );

	    /**
	     * Filter that allows adjusting the HTML output of the shipment email item table.
	     *
	     * @param string   $html The HTML output.
	     * @param Shipment $shipment The shipment instance.
	     *
	     * @since 3.0.0
	     * @package Vendidero/Germanized/Shipments
	     */
        return apply_filters( 'woocommerce_gzd_email_shipment_items_table', ob_get_clean(), $shipment );
    }
}

if ( ! function_exists( 'woocommerce_gzd_shipments_template_customer_order' ) ) {

	function woocommerce_gzd_shipments_template_customer_order( $order_id ) {
		$shipments_order = wc_gzd_get_shipment_order( $order_id );
		$order           = wc_get_order( $order_id );
		$shipments       = $shipments_order->get_simple_shipments();

		wc_get_template( 'myaccount/order-shipments.php', array(
			'has_shipments' => ( ! empty( $shipments ) ? true : false ),
			'shipments'     => $shipments,
			'order'         => $order,
		) );
	}
}

if ( ! function_exists( 'woocommerce_gzd_shipments_template_view_shipment' ) ) {

	function woocommerce_gzd_shipments_template_view_shipment( $shipment_id ) {

		if ( ( ! ( $shipment = wc_gzd_get_shipment( $shipment_id ) ) ) || ( ! current_user_can( 'view_order', $shipment->get_order_id() ) ) ) {
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

if ( ! function_exists( 'woocommerce_gzd_shipment_details_table' ) ) {

	function woocommerce_gzd_shipment_details_table( $shipment_id ) {
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
