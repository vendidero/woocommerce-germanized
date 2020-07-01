<?php

namespace Vendidero\Germanized\DHL\Admin;
use Vendidero\Germanized\DHL\Package;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Importer {

	public static function is_available() {
		$options  = get_option( 'woocommerce_pr_dhl_paket_settings' );
		$imported = get_option( 'woocommerc_gzd_dhl_import_finished' );

		return ( ( ! empty( $options ) && 'yes' !== $imported && Package::base_country_is_supported() ) ? true : false );
	}

	public static function is_plugin_enabled() {
		return class_exists( 'PR_DHL_WC' ) ? true : false;
	}

	public static function import_settings() {
		$old_settings = (array) get_option( 'woocommerce_pr_dhl_paket_settings' );

		$settings_mapping = array(
			'account_num'             => 'account_number',
			'participation_V01PAK'    => 'participation_V01PAK',
			'participation_V01PRIO'   => 'participation_V01PRIO',
			'participation_V06PAK'    => 'participation_V06PAK',
			'participation_V55PAK'    => 'participation_V55PAK',
			'participation_V54EPAK'   => 'participation_V54EPAK',
			'participation_V53WPAK'   => 'participation_V53WPAK',
			'participation_return'    => 'participation_return',
			'api_user'                => 'api_username',
			'api_pwd'                 => 'api_password',
			'default_product_dom'     => 'label_default_product_dom',
			'default_product_int'     => 'label_default_product_int',
			'default_print_codeable'  => 'label_address_codeable_only',
			'shipper_name'            => 'shipper_name',
			'shipper_company'         => 'shipper_company',
			'shipper_address'         => 'shipper_street',
			'shipper_address_no'      => 'shipper_street_no',
			'shipper_address_city'    => 'shipper_city',
			'shipper_address_zip'     => 'shipper_postcode',
			'shipper_phone'           => 'shipper_phone',
			'shipper_email'           => 'shipper_email',
			'return_name'             => 'return_address_name',
			'return_company'          => 'return_address_company',
			'return_address'          => 'return_address_street',
			'return_address_no'       => 'return_address_street_no',
			'return_address_city'     => 'return_address_city',
			'return_address_zip'      => 'return_address_postcode',
			'return_phone'            => 'return_address_phone',
			'return_email'            => 'return_address_email',
			'bank_holder'             => 'bank_holder',
			'bank_name'               => 'bank_name',
			'bank_iban'               => 'bank_iban',
			'bank_bic'                => 'bank_bic',
			'bank_ref'                => 'bank_ref',
			'bank_ref_2'              => 'bank_ref_2',
			'preferred_day'           => 'PreferredDay_enable',
			'preferred_day_cost'      => 'PreferredDay_cost',
			'preferred_day_cutoff'    => 'PreferredDay_cutoff_time',
			'preferred_exclusion_mon' => 'PreferredDay_exclusion_mon',
			'preferred_exclusion_tue' => 'PreferredDay_exclusion_tue',
			'preferred_exclusion_wed' => 'PreferredDay_exclusion_wed',
			'preferred_exclusion_thu' => 'PreferredDay_exclusion_thu',
			'preferred_exclusion_fri' => 'PreferredDay_exclusion_fri',
			'preferred_exclusion_sat' => 'PreferredDay_exclusion_sat',
			'preferred_location'      => 'PreferredLocation_enable',
			'preferred_neighbour'     => 'PreferredNeighbour_enable',
			'payment_gateway'         => 'preferred_payment_gateways_excluded',
			'display_packstation'     => 'parcel_pickup_packstation_enable',
			'display_parcelshop'      => 'parcel_pickup_parcelshop_enable',
			'display_post_office'     => 'parcel_pickup_postoffice_enable',
			'parcel_limit'            => 'parcel_pickup_map_max_results',
			'google_maps_api_key'     => 'parcel_pickup_map_api_key',
		);

		// Bulk update settings
		foreach( $settings_mapping as $setting_old_key => $setting_new_key ) {
			if ( isset( $old_settings[ 'dhl_' . $setting_old_key ] ) ) {
				update_option( 'woocommerce_gzd_dhl_' . $setting_new_key, $old_settings[ 'dhl_' . $setting_old_key ] );
			}
		}

		// Enable maps if API key exists
		if ( isset( $settings['dhl_google_maps_api_key'] ) && ! empty( $settings['dhl_google_maps_api_key'] ) ) {
			update_option( 'woocommerce_gzd_parcel_pickup_map_enable', 'yes' );
		}

		// Shipper state to country ISO mapping
		$countries       = WC()->countries;
		$shipper_country = ( isset( $old_settings['dhl_shipper_address_state'] ) && ! empty( $old_settings['dhl_shipper_address_state'] ) ) ? $old_settings['dhl_shipper_address_state'] : '';
		$return_country  = ( isset( $old_settings['dhl_return_address_state'] ) && ! empty( $old_settings['dhl_return_address_state'] ) ) ? $old_settings['dhl_return_address_state'] : '';
		$isos            = ( $countries ) ? $countries->get_countries() : array();

		if ( ! empty( $shipper_country ) && ! empty( $isos ) ) {
			if ( ( $key = array_search( $shipper_country, $isos ) ) !== false ) {
				update_option( 'woocommerce_gzd_dhl_shipper_country', $key );
			}
		}

		if ( ! empty( $return_country ) && ! empty( $isos ) ) {
			if ( ( $key = array_search( $return_country, $isos ) ) !== false ) {
				update_option( 'woocommerce_gzd_dhl_return_address_country', $key );
			}
		}
	}

	public static function import_order_data( $limit = 10, $offset = 0 ) {

		$orders = wc_get_orders( array(
			'limit'   => $limit,
			'offset'  => $offset,
			'orderby' => 'date',
			'order'   => 'DESC',
			'type'    => 'shop_order'
		) );

		if ( ! empty( $orders ) ) {
			foreach( $orders as $order ) {

				if ( ! $order->get_meta( '_shipping_address_type' ) ) {

					// Update order pickup type from official DHL plugin
					if ( self::order_has_pickup( $order ) ) {

						$order->update_meta_data( '_shipping_address_type', 'dhl' );
						$order->update_meta_data( '_shipping_dhl_postnumber', $order->get_meta( '_shipping_dhl_postnum' ) );

						// Remove data to make sure we do not show data twice
						$order->delete_meta_data( '_shipping_dhl_address_type' );
						$order->delete_meta_data( '_shipping_dhl_postnum' );

						$order->save();
					}
				}
			}
		}
	}

	protected static function order_has_pickup( $order ) {
		$pos_ps = stripos( $order->get_shipping_address_1(), 'Packstation' );
		$pos_fl = stripos( $order->get_shipping_address_1(), 'Postfiliale' );

		if ( $pos_ps !== false || $pos_fl !== false ) {
			return true;
		}

		return false;
	}
}
