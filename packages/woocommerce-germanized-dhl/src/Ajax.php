<?php

namespace Vendidero\Germanized\DHL;

/**
 * WC_Ajax class.
 */
class Ajax {

	/**
	 * Hook in ajax handlers.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	public static function add_ajax_events() {

		$ajax_events = array(
			'refresh_deutsche_post_label_preview',
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_woocommerce_gzd_dhl_' . $ajax_event, array( __CLASS__, 'suppress_errors' ), 5 );
			add_action( 'wp_ajax_woocommerce_gzd_dhl_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}

	public static function suppress_errors() {
		if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
			@ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON.
		}

		$GLOBALS['wpdb']->hide_errors();
	}

	/**
	 *
	 */
	public static function refresh_deutsche_post_label_preview() {
		check_ajax_referer( 'wc-gzd-dhl-refresh-deutsche-post-label-preview', 'security' );

		if ( ! current_user_can( 'edit_shop_orders' ) || ! isset( $_POST['product_id'] ) ) {
			wp_die( -1 );
		}

		$selected_services   = isset( $_POST['selected_services'] ) ? wc_clean( $_POST['selected_services'] ) : array();
		$im_product_id       = absint( $_POST['product_id'] );
		$product_id          = 0;
		$is_wp_int           = false;
		$response            = array(
			'success'      => true,
			'preview_url'  => '',
			'preview_data' => array(),
			'fragments'    => array(),
		);

		if ( ! empty( $im_product_id ) ) {
			$product_id = Package::get_internetmarke_api()->get_product_id( $im_product_id );

			/**
			 * Refresh im product id by selected services.
			 */
			$im_product_id = Package::get_internetmarke_api()->get_product_code( $im_product_id, $selected_services );
			$preview_url   = Package::get_internetmarke_api()->preview_stamp( $im_product_id );
			$preview_data  = Package::get_internetmarke_api()->get_product_preview_data( $im_product_id );
			$is_wp_int     = Package::get_internetmarke_api()->is_warenpost_international( $im_product_id );

			if ( $preview_url ) {
				$response['preview_url']  = $preview_url;
				$response['preview_data'] = $preview_data;
			}
		}

		ob_start();
		include( Package::get_path() . '/includes/admin/views/html-deutsche-post-additional-services.php' );
		$html = ob_get_clean();

		$response['is_wp_int'] = $is_wp_int;
		$response['fragments']['.wc-gzd-shipment-im-additional-services'] = '<div class="wc-gzd-shipment-im-additional-services">' . $html . '</div>';

		wp_send_json( $response );
	}
}
