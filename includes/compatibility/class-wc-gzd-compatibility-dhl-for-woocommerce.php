<?php
/**
 * PayPal Plus Helper for Inpsyde
 *
 * Specific configuration for Woo PayPal Plus by Inspyde
 *
 * @class 		WC_GZD_Compatibility_Woo_Paypalplus
 * @category	Class
 * @author 		vendidero
 */
class WC_GZD_Compatibility_DHL_For_WooCommerce extends WC_GZD_Compatibility {

	public function __construct() {
		parent::__construct(
			'DHL for WooCommerce',
			'dhl-for-woocommerce/pr-dhl-woocommerce.php'
		);
	}

	public function load() {
		add_action( 'woocommerce_gzd_parcel_delivery_order_opted_in', array( $this, 'parcel_delivery_opted_in' ), 10, 2 );
	}

	public function parcel_delivery_opted_in( $order_id, $opted_in ) {
		$data = get_post_meta( $order_id, '_pr_shipment_dhl_label_items', true );

		if ( empty( $data ) || ! is_array( $data ) ) {
			$data = array();
		}

		$data['pr_dhl_email_notification'] = ( $opted_in ? 'yes' : 'no' );

		update_post_meta( $order_id, '_pr_shipment_dhl_label_items', $data );
	}
}