<?php

namespace Vendidero\Germanized\DHL\Admin\Importer;

use Vendidero\Germanized\DHL\Package;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class DHL {

	public static function is_available() {
		$options  = get_option( 'woocommerce_pr_dhl_paket_settings' );
		$imported = get_option( 'woocommerc_gzd_dhl_import_finished' );
		$user     = '';

		if ( $dhl = Package::get_dhl_shipping_provider() ) {
			$user = $dhl->get_customer_number();
		}

		return ( ( ! empty( $options ) && empty( $user ) && 'yes' !== $imported && Package::base_country_is_supported() ) ? true : false );
	}

	public static function is_plugin_enabled() {
		return class_exists( 'PR_DHL_WC' ) ? true : false;
	}

	public static function import_settings() {
		$old_settings = (array) get_option( 'woocommerce_pr_dhl_paket_settings' );
		$dhl          = Package::get_dhl_shipping_provider();

		if ( ! $dhl ) {
			return false;
		}

		$settings_mapping = array(
			'account_num'             => 'account_number',
			'participation_V01PAK'    => 'participation_V01PAK',
			'participation_V01PRIO'   => 'participation_V01PRIO',
			'participation_V06PAK'    => 'participation_V06PAK',
			'participation_V55PAK'    => 'participation_V55PAK',
			'participation_V54EPAK'   => 'participation_V54EPAK',
			'participation_V53WPAK'   => 'participation_V53WPAK',
			'participation_V62WP'     => 'participation_V62WP',
			'participation_V66WPI'    => 'participation_V66WPI',
			'participation_return'    => 'participation_return',
			'api_user'                => 'api_username',
			'api_pwd'                 => 'api_password',
			'default_product_dom'     => 'label_default_product_dom',
			'default_product_int'     => 'label_default_product_int',
			'default_print_codeable'  => 'label_address_codeable_only',
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
			'google_maps_api_key'     => 'parcel_pickup_map_api_password',
		);

		// Bulk update settings
		foreach ( $settings_mapping as $setting_old_key => $setting_new_key ) {
			if ( isset( $old_settings[ 'dhl_' . $setting_old_key ] ) && ! empty( $old_settings[ 'dhl_' . $setting_old_key ] ) ) {
				$dhl->update_setting( $setting_new_key, $old_settings[ 'dhl_' . $setting_old_key ] );
			}
		}

		/**
		 * Default address update
		 */
		foreach ( array( 'shipper', 'return' ) as $address_type ) {
			$plain_address = array(
				'company'      => 'company',
				'address_city' => 'city',
				'address_zip'  => 'postcode',
				'phone'        => 'phone',
				'email'        => 'email',
			);

			foreach ( $plain_address as $prop => $new_prop ) {
				$prop_name = $address_type . '_' . $prop;

				if ( ! empty( $old_settings[ 'dhl_' . $prop_name ] ) ) {
					update_option( "woocommerce_gzd_shipments_{$address_type}_address_{$new_prop}", $old_settings[ 'dhl_' . $prop_name ] );
				}
			}

			if ( ! empty( $old_settings[ "dhl_{$address_type}_address" ] ) ) {
				$address_1 = $old_settings[ "dhl_{$address_type}_address" ] . ' ' . ( isset( $old_settings[ "dhl_{$address_type}_address_no" ] ) ? $old_settings[ "dhl_{$address_type}_address_no" ] : '' );

				update_option( "woocommerce_gzd_shipments_{$address_type}_address_address_1", $address_1 );
			}

			if ( ! empty( $old_settings[ "dhl_{$address_type}_name" ] ) ) {
				$name       = explode( ' ', $old_settings[ "dhl_{$address_type}_name" ] );
				$name_first = $name;
				$first_name = implode( ' ', array_splice( $name_first, 0, ( count( $name ) - 1 ) ) );
				$last_name  = $name[ count( $name ) - 1 ];

				update_option( "woocommerce_gzd_shipments_{$address_type}_address_first_name", $first_name );
				update_option( "woocommerce_gzd_shipments_{$address_type}_address_last_name", $last_name );
			}
		}

		// Enable maps if API key exists
		if ( isset( $settings['dhl_google_maps_api_key'] ) && ! empty( $settings['dhl_google_maps_api_key'] ) ) {
			$dhl->update_setting( 'parcel_pickup_map_enable', 'yes' );
		}

		// Shipper state to country ISO mapping
		$countries       = WC()->countries;
		$shipper_country = ( isset( $old_settings['dhl_shipper_address_state'] ) && ! empty( $old_settings['dhl_shipper_address_state'] ) ) ? $old_settings['dhl_shipper_address_state'] : '';
		$return_country  = ( isset( $old_settings['dhl_return_address_state'] ) && ! empty( $old_settings['dhl_return_address_state'] ) ) ? $old_settings['dhl_return_address_state'] : '';
		$isos            = ( $countries ) ? $countries->get_countries() : array();

		if ( ! empty( $shipper_country ) && ! empty( $isos ) ) {
			if ( ( $key = array_search( $shipper_country, $isos, true ) ) !== false ) {
				update_option( 'woocommerce_gzd_shipments_shipper_address_country', $key );
			}
		}

		if ( ! empty( $return_country ) && ! empty( $isos ) ) {
			if ( ( $key = array_search( $return_country, $isos, true ) ) !== false ) {
				update_option( 'woocommerce_gzd_shipments_return_address_country', $key );
			}
		}

		$dhl->save();

		return true;
	}

	public static function import_order_data( $limit = 10, $offset = 0 ) {

		$orders = wc_get_orders(
			array(
				'limit'   => $limit,
				'offset'  => $offset,
				'orderby' => 'date',
				'order'   => 'DESC',
				'type'    => 'shop_order',
			)
		);

		if ( ! empty( $orders ) ) {
			foreach ( $orders as $order ) {

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

		if ( false !== $pos_ps || false !== $pos_fl ) {
			return true;
		}

		return false;
	}
}
