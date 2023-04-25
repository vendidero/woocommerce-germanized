<?php

namespace Vendidero\Germanized\DHL;

use Vendidero\Germanized\Shipments\ShippingProvider\Helper;
use Vendidero\Germanized\Shipments\ShippingProvider\Simple;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Install {

	public static function install() {
		$current_version       = get_option( 'woocommerce_gzd_dhl_version', null );
		$needs_settings_update = false;

		self::create_db();

		if ( ! is_null( $current_version ) ) {
			self::update( $current_version );
		}

		/**
		 * Older versions did not support custom versioning
		 */
		if ( is_null( $current_version ) ) {
			add_option( 'woocommerce_gzd_dhl_version', Package::get_version() );
			// Legacy settings -> indicate update necessary
			$needs_settings_update = ( get_option( 'woocommerce_gzd_dhl_enable' ) || get_option( 'woocommerce_gzd_deutsche_post_enable' ) ) && ! get_option( 'woocommerce_gzd_migrated_settings' );
		} else {
			update_option( 'woocommerce_gzd_dhl_version', Package::get_version() );
		}

		if ( $needs_settings_update ) {
			self::migrate_settings();
		}
	}

	private static function update( $current_version ) {
		if ( version_compare( $current_version, '1.5.6', '<' ) ) {
			Helper::instance()->load_shipping_providers();

			$dhl = wc_gzd_get_shipping_provider( 'dhl' );

			if ( ! is_a( $dhl, '\Vendidero\Germanized\DHL\ShippingProvider\DHL' ) ) {
				return;
			}

			$int_product = $dhl->get_setting( 'label_default_product_int' );
			$eu_product  = $dhl->get_setting( 'label_default_product_eu' );

			if ( empty( $eu_product ) ) {
				if ( ! empty( $int_product ) && in_array( $int_product, array_keys( wc_gzd_dhl_get_products_eu() ), true ) ) {
					$dhl->update_setting( 'label_default_product_eu', $int_product );
					$dhl->update_setting( 'label_default_product_int', 'V53WPAK' );
				} elseif ( ! empty( $int_product ) && in_array( $int_product, array_keys( wc_gzd_dhl_get_products_international() ), true ) ) {
					$dhl->update_setting( 'label_default_product_eu', 'V55PAK' );
				}

				$dhl->save();
			}
		}
	}

	private static function migrate_settings() {
		global $wpdb;

		/**
		 * Make sure to reload shipping providers to make sure our classes were registered accordingly as the
		 * install script may be called later than on plugins loaded.
		 */
		Helper::instance()->load_shipping_providers();

		$plugin_options   = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'woocommerce_gzd_dhl_%' OR option_name LIKE 'woocommerce_gzd_deutsche_post_%'" );
		$dhl              = wc_gzd_get_shipping_provider( 'dhl' );
		$deutsche_post    = wc_gzd_get_shipping_provider( 'deutsche_post' );
		$excluded_options = array(
			'woocommerce_gzd_dhl_upload_dir_suffix',
			'woocommerce_gzd_dhl_enable',
			'woocommerce_gzd_dhl_enable_internetmarke',
			'woocommerce_gzd_dhl_internetmarke_enable',
			'woocommerce_gzd_dhl_version',
		);

		/**
		 * Error while retrieving shipping provider instance
		 */
		if ( ! is_a( $dhl, '\Vendidero\Germanized\DHL\ShippingProvider\DHL' ) || ! is_a( $deutsche_post, '\Vendidero\Germanized\DHL\ShippingProvider\DeutschePost' ) ) {
			return false;
		}

		foreach ( $plugin_options as $option ) {
			$option_name = $option->option_name;

			if ( in_array( $option_name, $excluded_options, true ) ) {
				continue;
			}

			$option_value = get_option( $option->option_name, '' );
			$is_dp        = strpos( $option_name, '_im_' ) !== false || strpos( $option_name, '_internetmarke_' ) !== false || strpos( $option_name, '_deutsche_post_' ) !== false;

			if ( ! $is_dp ) {
				$option_name_clean = str_replace( 'woocommerce_gzd_dhl_', '', $option_name );

				if ( strstr( $option_name_clean, 'shipper_' ) || strstr( $option_name_clean, 'return_address_' ) ) {
					continue;
				} elseif ( 'parcel_pickup_map_api_key' === $option_name_clean ) {
					self::update_provider_setting( $dhl, 'parcel_pickup_map_api_password', $option_value );
				} else {
					self::update_provider_setting( $dhl, $option_name_clean, $option_value );
				}
			} else {
				$option_name_clean = str_replace( 'woocommerce_gzd_deutsche_post_', '', $option_name );
				$option_name_clean = str_replace( 'woocommerce_gzd_dhl_', '', $option_name_clean );
				$option_name_clean = str_replace( 'deutsche_post_', '', $option_name_clean );
				$option_name_clean = str_replace( 'im_', '', $option_name_clean );

				self::update_provider_setting( $deutsche_post, $option_name_clean, $option_value );
			}
		}

		$deutsche_post->set_label_default_shipment_weight( get_option( 'woocommerce_gzd_deutsche_post_label_default_shipment_weight' ) );
		$deutsche_post->set_label_minimum_shipment_weight( get_option( 'woocommerce_gzd_deutsche_post_label_minimum_shipment_weight' ) );

		$dhl->save();
		$deutsche_post->save();

		$shipper_name  = self::get_address_name_parts( 'shipper' );
		$base_location = wc_get_base_location();
		$state_suffix  = '';

		if ( version_compare( get_option( 'woocommerce_version' ), '6.3.1', '>=' ) ) {
			$state_suffix = ! empty( $base_location['state'] ) ? ':' . $base_location['state'] : ':DE-BE';
		}

		// Update address data
		$shipper_address = array(
			'first_name' => $shipper_name['first_name'],
			'last_name'  => $shipper_name['last_name'],
			'company'    => get_option( 'woocommerce_gzd_dhl_shipper_company' ),
			'address_1'  => get_option( 'woocommerce_gzd_dhl_shipper_street' ) . ' ' . get_option( 'woocommerce_gzd_dhl_shipper_street_no' ),
			'postcode'   => get_option( 'woocommerce_gzd_dhl_shipper_postcode' ),
			'country'    => get_option( 'woocommerce_gzd_dhl_shipper_country' ),
			'city'       => get_option( 'woocommerce_gzd_dhl_shipper_city' ),
			'phone'      => get_option( 'woocommerce_gzd_dhl_shipper_phone' ),
			'email'      => get_option( 'woocommerce_gzd_dhl_shipper_email' ),
		);

		$shipper_address = array_filter( $shipper_address );

		foreach ( $shipper_address as $key => $value ) {
			if ( 'country' === $key ) {
				$country_data = wc_format_country_state_string( $value );

				/**
				 * Append a state suffix if not exists.
				 */
				if ( ! empty( $state_suffix ) && empty( $country_data['state'] ) ) {
					$value = $value . $state_suffix;
				}
			}

			update_option( 'woocommerce_gzd_shipments_shipper_address_' . $key, $value );
		}

		$return_name = self::get_address_name_parts( 'return_address' );

		$return_address = array(
			'first_name' => $return_name['first_name'],
			'last_name'  => $return_name['last_name'],
			'company'    => get_option( 'woocommerce_gzd_dhl_return_address_company' ),
			'address_1'  => get_option( 'woocommerce_gzd_dhl_return_address_street' ) . ' ' . get_option( 'woocommerce_gzd_dhl_return_address_street_no' ),
			'postcode'   => get_option( 'woocommerce_gzd_dhl_return_address_postcode' ),
			'country'    => get_option( 'woocommerce_gzd_dhl_return_address_country' ),
			'city'       => get_option( 'woocommerce_gzd_dhl_return_address_city' ),
			'phone'      => get_option( 'woocommerce_gzd_dhl_return_address_phone' ),
			'email'      => get_option( 'woocommerce_gzd_dhl_return_address_email' ),
		);

		$return_address = array_filter( $return_address );

		foreach ( $return_address as $key => $value ) {
			if ( 'country' === $key ) {
				$country_data = wc_format_country_state_string( $value );

				/**
				 * Append a state suffix if not exists.
				 */
				if ( ! empty( $state_suffix ) && empty( $country_data['state'] ) ) {
					$value = $value . $state_suffix;
				}
			}

			update_option( 'woocommerce_gzd_shipments_return_address_' . $key, $value );
		}

		update_option( 'woocommerce_gzd_migrated_settings', 'yes' );

		return true;
	}

	protected static function get_address_name_parts( $address_type = 'shipper' ) {
		$sender_name       = explode( ' ', get_option( "woocommerce_gzd_dhl_{$address_type}_name" ) );
		$sender_name_first = $sender_name;
		$sender_first_name = implode( ' ', array_splice( $sender_name_first, 0, ( count( $sender_name ) - 1 ) ) );
		$sender_last_name  = $sender_name[ count( $sender_name ) - 1 ];

		return array(
			'first_name' => $sender_first_name,
			'last_name'  => $sender_last_name,
		);
	}

	/**
	 * @param Simple $provider
	 * @param $key
	 * @param $value
	 */
	protected static function update_provider_setting( $provider, $key, $value ) {
		$provider->update_setting( $key, $value );
	}

	private static function create_db() {
		global $wpdb;
		$wpdb->hide_errors();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( self::get_schema() );
	}

	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_dhl_im_products (
  product_id bigint(20) unsigned NOT NULL auto_increment,
  product_im_id bigint(20) unsigned NOT NULL,
  product_code int(16) NOT NULL,
  product_name varchar(150) NOT NULL DEFAULT '',
  product_slug varchar(150) NOT NULL DEFAULT '',
  product_version int(5) NOT NULL DEFAULT 1,
  product_annotation varchar(500) NOT NULL DEFAULT '',
  product_description varchar(500) NOT NULL DEFAULT '',
  product_information_text text NOT NULL DEFAULT '',
  product_type varchar(50) NOT NULL DEFAULT 'sales',
  product_destination varchar(20) NOT NULL DEFAULT 'national',
  product_price int(8) NOT NULL,
  product_length_min int(8) NULL,
  product_length_max int(8) NULL,
  product_length_unit varchar(8) NULL,
  product_width_min int(8) NULL,
  product_width_max int(8) NULL,
  product_width_unit varchar(8) NULL,
  product_height_min int(8) NULL,
  product_height_max int(8) NULL,
  product_height_unit varchar(8) NULL,
  product_weight_min int(8) NULL,
  product_weight_max int(8) NULL,
  product_weight_unit varchar(8) NULL,
  product_parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
  product_service_count int(3) NOT NULL DEFAULT 0,
  product_is_wp_int int(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (product_id),
  KEY product_im_id (product_im_id),
  KEY product_code (product_code)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_dhl_im_product_services (
  product_service_id bigint(20) unsigned NOT NULL auto_increment,
  product_service_product_id bigint(20) unsigned NOT NULL,
  product_service_product_parent_id bigint(20) unsigned NOT NULL,
  product_service_slug varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY  (product_service_id),
  KEY product_service_product_id (product_service_product_id),
  KEY product_service_product_parent_id (product_service_product_parent_id)
) $collate;";

		return $tables;
	}
}
