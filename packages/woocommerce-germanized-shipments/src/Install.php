<?php

namespace Vendidero\Germanized\Shipments;

use Vendidero\Germanized\Shipments\ShippingProvider\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Install {

	public static function install() {
		$current_version = get_option( 'woocommerce_gzd_shipments_version', null );

		self::create_upload_dir();
		self::create_tables();
		self::maybe_create_return_reasons();
		self::maybe_create_packaging();
		self::update_providers();

		update_option( 'woocommerce_gzd_shipments_version', Package::get_version() );
		update_option( 'woocommerce_gzd_shipments_db_version', Package::get_version() );

		do_action( 'woocommerce_flush_rewrite_rules' );
	}

	private static function update_providers() {
		$providers = Helper::instance()->get_shipping_providers();

		foreach ( $providers as $provider ) {
			if ( ! $provider->is_activated() ) {
				continue;
			}

			$provider->update_settings_with_defaults();
			$provider->save();
		}
	}

	private static function maybe_create_return_reasons() {
		$reasons = get_option( 'woocommerce_gzd_shipments_return_reasons', null );

		if ( is_null( $reasons ) ) {
			$default_reasons = array(
				array(
					'order'  => 1,
					'code'   => 'wrong-product',
					'reason' => _x( 'Wrong product or size ordered', 'shipments', 'woocommerce-germanized' ),
				),
				array(
					'order'  => 2,
					'code'   => 'not-needed',
					'reason' => _x( 'Product no longer needed', 'shipments', 'woocommerce-germanized' ),
				),
				array(
					'order'  => 3,
					'code'   => 'look',
					'reason' => _x( 'Don\'t like the look', 'shipments', 'woocommerce-germanized' ),
				),
			);

			update_option( 'woocommerce_gzd_shipments_return_reasons', $default_reasons );
		}
	}

	private static function get_db_version() {
		return get_option( 'woocommerce_gzd_shipments_db_version', null );
	}

	private static function maybe_create_packaging() {
		$packaging  = wc_gzd_get_packaging_list();
		$db_version = self::get_db_version();

		if ( empty( $packaging ) && is_null( $db_version ) ) {
			$defaults = array(
				array(
					'description'        => _x( 'Cardboard S', 'shipments', 'woocommerce-germanized' ),
					'length'             => 25,
					'width'              => 17.5,
					'height'             => 10,
					'weight'             => 0.14,
					'max_content_weight' => 30,
					'type'               => 'cardboard',
				),
				array(
					'description'        => _x( 'Cardboard M', 'shipments', 'woocommerce-germanized' ),
					'length'             => 37.5,
					'width'              => 30,
					'height'             => 13.5,
					'weight'             => 0.23,
					'max_content_weight' => 30,
					'type'               => 'cardboard',
				),
				array(
					'description'        => _x( 'Cardboard L', 'shipments', 'woocommerce-germanized' ),
					'length'             => 45,
					'width'              => 35,
					'height'             => 20,
					'weight'             => 0.3,
					'max_content_weight' => 30,
					'type'               => 'cardboard',
				),
				array(
					'description'        => _x( 'Letter C5/6', 'shipments', 'woocommerce-germanized' ),
					'length'             => 22,
					'width'              => 11,
					'height'             => 1,
					'weight'             => 0,
					'max_content_weight' => 0.05,
					'type'               => 'letter',
				),
				array(
					'description'        => _x( 'Letter C4', 'shipments', 'woocommerce-germanized' ),
					'length'             => 22.9,
					'width'              => 32.4,
					'height'             => 2,
					'weight'             => 0.01,
					'max_content_weight' => 1,
					'type'               => 'letter',
				),
			);

			foreach ( $defaults as $default ) {
				$packaging = new Packaging();
				$packaging->set_props( $default );
				$packaging->save();
			}
		}
	}

	private static function create_tables() {
		global $wpdb;

		$current_version    = get_option( 'woocommerce_gzd_shipments_version', null );
		$current_db_version = self::get_db_version();

		/**
		 * Make possible duplicate names unique.
		 */
		if ( null !== $current_version && isset( $wpdb->gzd_shipping_provider ) ) {
			$providers          = $wpdb->get_results( "SELECT * FROM $wpdb->gzd_shipping_provider" );
			$shipping_providers = array();

			foreach ( $providers as $provider ) {
				if ( in_array( $provider->shipping_provider_name, $shipping_providers, true ) ) {
					$unique_provider_name = sanitize_title( $provider->shipping_provider_name . '_' . wp_generate_password( 4, false, false ) );

					$wpdb->update(
						$wpdb->gzd_shipping_provider,
						array(
							'shipping_provider_name' => $unique_provider_name,
						),
						array( 'shipping_provider_id' => $provider->shipping_provider_id )
					);
				} else {
					$shipping_providers[] = $provider->shipping_provider_name;
				}
			}
		}

		$wpdb->hide_errors();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$db_delta_result = dbDelta( self::get_schema() );

		/**
		 * Update MySQL datetime default to NULL for MySQL 8 compatibility.
		 */
		if ( ! empty( $current_db_version ) && version_compare( $current_db_version, '2.3.0', '<' ) ) {
			$date_fields = array(
				"{$wpdb->prefix}woocommerce_gzd_shipments" => array(
					'shipment_date_created',
					'shipment_date_created_gmt',
					'shipment_date_sent',
					'shipment_date_sent_gmt',
					'shipment_est_delivery_date',
					'shipment_est_delivery_date_gmt',
				),
				"{$wpdb->prefix}woocommerce_gzd_shipment_labels" => array(
					'label_date_created',
					'label_date_created_gmt',
				),
				"{$wpdb->prefix}woocommerce_gzd_packaging" => array(
					'packaging_date_created',
					'packaging_date_created_gmt',
				),
			);

			foreach ( $date_fields as $table => $columns ) {
				foreach ( $columns as $column ) {
					$result = $wpdb->query( "ALTER TABLE `$table` CHANGE $column $column datetime DEFAULT NULL;" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

					if ( true !== $result ) {
						$db_delta_result = false;
						Package::log( sprintf( 'Error while updating datetime field in %s for column %s', $table, $column ), 'error' );
					}
				}
			}
		}

		return $db_delta_result;
	}

	private static function create_upload_dir() {
		Package::maybe_set_upload_dir();

		$dir = Package::get_upload_dir();

		if ( ! @is_dir( $dir['basedir'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@mkdir( $dir['basedir'] ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		if ( ! file_exists( trailingslashit( $dir['basedir'] ) . '.htaccess' ) ) {
			@file_put_contents( trailingslashit( $dir['basedir'] ) . '.htaccess', 'deny from all' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		}

		if ( ! file_exists( trailingslashit( $dir['basedir'] ) . 'index.php' ) ) {
			@touch( trailingslashit( $dir['basedir'] ) . 'index.php' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		/**
		 * Use a varchar(191) for shipping_provider_name as the key length might overflow max key length for older MySQL (< 5.7).
		 * @see https://stackoverflow.com/a/31474509
		 */
		$tables = "
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipment_items (
  shipment_item_id bigint(20) unsigned NOT NULL auto_increment,
  shipment_id bigint(20) unsigned NOT NULL,
  shipment_item_name text NOT NULL,
  shipment_item_order_item_id bigint(20) unsigned NOT NULL,
  shipment_item_product_id bigint(20) unsigned NOT NULL,
  shipment_item_parent_id bigint(20) unsigned NOT NULL,
  shipment_item_quantity smallint(4) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY  (shipment_item_id),
  KEY shipment_id (shipment_id),
  KEY shipment_item_order_item_id (shipment_item_order_item_id),
  KEY shipment_item_product_id (shipment_item_product_id),
  KEY shipment_item_parent_id (shipment_item_parent_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipment_itemmeta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  gzd_shipment_item_id bigint(20) unsigned NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY gzd_shipment_item_id (gzd_shipment_item_id),
  KEY meta_key (meta_key(32))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipments (
  shipment_id bigint(20) unsigned NOT NULL auto_increment,
  shipment_date_created datetime default NULL,
  shipment_date_created_gmt datetime default NULL,
  shipment_date_sent datetime default NULL,
  shipment_date_sent_gmt datetime default NULL,
  shipment_est_delivery_date datetime default NULL,
  shipment_est_delivery_date_gmt datetime default NULL,
  shipment_status varchar(20) NOT NULL default 'gzd-draft',
  shipment_order_id bigint(20) unsigned NOT NULL DEFAULT 0,
  shipment_packaging_id bigint(20) unsigned NOT NULL DEFAULT 0,
  shipment_parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
  shipment_country varchar(2) NOT NULL DEFAULT '',
  shipment_tracking_id varchar(200) NOT NULL DEFAULT '',
  shipment_type varchar(200) NOT NULL DEFAULT '',
  shipment_version varchar(200) NOT NULL DEFAULT '',
  shipment_search_index longtext NOT NULL DEFAULT '',
  shipment_shipping_provider varchar(200) NOT NULL DEFAULT '',
  shipment_shipping_method varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (shipment_id),
  KEY shipment_order_id (shipment_order_id),
  KEY shipment_packaging_id (shipment_packaging_id),
  KEY shipment_parent_id (shipment_parent_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipment_labels (
  label_id bigint(20) unsigned NOT NULL auto_increment,
  label_date_created datetime default NULL,
  label_date_created_gmt datetime default NULL,
  label_shipment_id bigint(20) unsigned NOT NULL,
  label_parent_id bigint(20) unsigned NOT NULL DEFAULT 0,
  label_number varchar(200) NOT NULL DEFAULT '',
  label_product_id varchar(200) NOT NULL DEFAULT '',
  label_shipping_provider varchar(200) NOT NULL DEFAULT '',
  label_path varchar(200) NOT NULL DEFAULT '',
  label_type varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (label_id),
  KEY label_shipment_id (label_shipment_id),
  KEY label_parent_id (label_parent_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipment_labelmeta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  gzd_shipment_label_id bigint(20) unsigned NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY gzd_shipment_label_id (gzd_shipment_label_id),
  KEY meta_key (meta_key(32))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipmentmeta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  gzd_shipment_id bigint(20) unsigned NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY gzd_shipment_id (gzd_shipment_id),
  KEY meta_key (meta_key(32))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_packaging (
  packaging_id bigint(20) unsigned NOT NULL auto_increment,
  packaging_date_created datetime default NULL,
  packaging_date_created_gmt datetime default NULL,
  packaging_type varchar(200) NOT NULL DEFAULT '',
  packaging_description tinytext NOT NULL DEFAULT '',
  packaging_weight decimal(6,2) unsigned NOT NULL DEFAULT 0,
  packaging_order bigint(20) unsigned NOT NULL DEFAULT 0,
  packaging_max_content_weight decimal(6,2) unsigned NOT NULL DEFAULT 0,
  packaging_length decimal(6,2) unsigned NOT NULL DEFAULT 0,
  packaging_width decimal(6,2) unsigned NOT NULL DEFAULT 0,
  packaging_height decimal(6,2) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (packaging_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_packagingmeta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  gzd_packaging_id bigint(20) unsigned NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY gzd_packaging_id (gzd_packaging_id),
  KEY meta_key (meta_key(32))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipping_provider (
  shipping_provider_id bigint(20) unsigned NOT NULL auto_increment,
  shipping_provider_activated tinyint(1) NOT NULL default 1,
  shipping_provider_order smallint(10) NOT NULL DEFAULT 0,
  shipping_provider_title varchar(200) NOT NULL DEFAULT '',
  shipping_provider_name varchar(191) NOT NULL DEFAULT '',
  PRIMARY KEY  (shipping_provider_id),
  UNIQUE KEY shipping_provider_name (shipping_provider_name)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipping_providermeta (
  meta_id bigint(20) unsigned NOT NULL auto_increment,
  gzd_shipping_provider_id bigint(20) unsigned NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY gzd_shipping_provider_id (gzd_shipping_provider_id),
  KEY meta_key (meta_key(32))
) $collate;";

		return $tables;
	}
}
