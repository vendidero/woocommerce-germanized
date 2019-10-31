<?php

namespace Vendidero\Germanized\Shipments;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Install {

	public static function install() {
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
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipment_items (
  shipment_item_id BIGINT UNSIGNED NOT NULL auto_increment,
  shipment_id BIGINT UNSIGNED NOT NULL,
  shipment_item_name TEXT NOT NULL,
  shipment_item_order_item_id BIGINT UNSIGNED NOT NULL,
  shipment_item_product_id BIGINT UNSIGNED NOT NULL,
  shipment_item_parent_id BIGINT UNSIGNED NOT NULL,
  shipment_item_quantity SMALLINT UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY  (shipment_item_id),
  KEY shipment_id (shipment_id),
  KEY shipment_item_order_item_id (shipment_item_order_item_id),
  KEY shipment_item_product_id (shipment_item_product_id),
  KEY shipment_item_parent_id (shipment_item_parent_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipment_itemmeta (
  meta_id BIGINT UNSIGNED NOT NULL auto_increment,
  gzd_shipment_item_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY gzd_shipment_item_id (gzd_shipment_item_id),
  KEY meta_key (meta_key(32))
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipments (
  shipment_id BIGINT UNSIGNED NOT NULL auto_increment,
  shipment_date_created datetime NOT NULL default '0000-00-00 00:00:00',
  shipment_date_created_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  shipment_date_sent datetime default NULL,
  shipment_date_sent_gmt datetime default NULL,
  shipment_est_delivery_date datetime default NULL,
  shipment_est_delivery_date_gmt datetime default NULL,
  shipment_status varchar(20) NOT NULL default 'gzd-draft',
  shipment_order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  shipment_parent_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  shipment_country varchar(2) NOT NULL DEFAULT '',
  shipment_tracking_id varchar(200) NOT NULL DEFAULT '',
  shipment_type varchar(200) NOT NULL DEFAULT '',
  shipment_shipping_provider varchar(200) NOT NULL DEFAULT '',
  shipment_shipping_method varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (shipment_id),
  KEY shipment_order_id (shipment_order_id),
  KEY shipment_parent_id (shipment_parent_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_shipmentmeta (
  meta_id BIGINT UNSIGNED NOT NULL auto_increment,
  gzd_shipment_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY gzd_shipment_id (gzd_shipment_id),
  KEY meta_key (meta_key(32))
) $collate;";

		return $tables;
	}
}
