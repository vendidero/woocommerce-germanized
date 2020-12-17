<?php

namespace Vendidero\Germanized\DHL;

defined( 'ABSPATH' ) || exit;

/**
 * Main package class.
 */
class Install {

    public static function install() {
    	self::create_upload_dir();
		self::create_db();
    }

    private static function create_db() {
	    global $wpdb;
	    $wpdb->hide_errors();
	    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	    dbDelta( self::get_schema() );
    }

    private static function create_upload_dir() {
    	Package::maybe_set_upload_dir();

	    $dir = Package::get_upload_dir();

	    if ( ! @is_dir( $dir['basedir'] ) ) {
	    	@mkdir( $dir['basedir'] );
	    }

	    if ( ! file_exists( trailingslashit( $dir['basedir'] ) . '.htaccess' ) ) {
		    @file_put_contents( trailingslashit( $dir['basedir'] ) . '.htaccess', 'deny from all' );
	    }

	    if ( ! file_exists( trailingslashit( $dir['basedir'] ) . 'index.php' ) ) {
		    @touch( trailingslashit( $dir['basedir'] ) . 'index.php' );
	    }
    }

    private static function get_schema() {
        global $wpdb;

        $collate = '';

        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }

        $tables = "
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_dhl_labels (
  label_id BIGINT UNSIGNED NOT NULL auto_increment,
  label_date_created datetime NOT NULL default '0000-00-00 00:00:00',
  label_date_created_gmt datetime NOT NULL default '0000-00-00 00:00:00',
  label_shipment_id BIGINT UNSIGNED NOT NULL,
  label_parent_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  label_number varchar(200) NOT NULL DEFAULT '',
  label_dhl_product varchar(200) NOT NULL DEFAULT '',
  label_path varchar(200) NOT NULL DEFAULT '',
  label_default_path varchar(200) NOT NULL DEFAULT '',
  label_export_path varchar(200) NOT NULL DEFAULT '',
  label_type varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY  (label_id),
  KEY label_shipment_id (label_shipment_id),
  KEY label_parent_id (label_parent_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_dhl_im_products (
  product_id BIGINT UNSIGNED NOT NULL auto_increment,
  product_im_id BIGINT UNSIGNED NOT NULL,
  product_code INT(16) NOT NULL,
  product_name varchar(150) NOT NULL DEFAULT '',
  product_slug varchar(150) NOT NULL DEFAULT '',
  product_version INT(5) NOT NULL DEFAULT 1,
  product_annotation varchar(500) NOT NULL DEFAULT '',
  product_description varchar(500) NOT NULL DEFAULT '',
  product_information_text TEXT NOT NULL DEFAULT '',
  product_type varchar(50) NOT NULL DEFAULT 'sales',
  product_destination varchar(20) NOT NULL DEFAULT 'national',
  product_price INT(8) NOT NULL,
  product_length_min INT(8) NULL,
  product_length_max INT(8) NULL,
  product_length_unit VARCHAR(8) NULL,
  product_width_min INT(8) NULL,
  product_width_max INT(8) NULL,
  product_width_unit VARCHAR(8) NULL,
  product_height_min INT(8) NULL,
  product_height_max INT(8) NULL,
  product_height_unit VARCHAR(8) NULL,
  product_weight_min INT(8) NULL,
  product_weight_max INT(8) NULL,
  product_weight_unit VARCHAR(8) NULL,
  product_parent_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
  product_service_count INT(3) NOT NULL DEFAULT 0,
  product_is_wp_int INT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (product_id),
  KEY product_im_id (product_im_id),
  KEY product_code (product_code)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_dhl_im_product_services (
  product_service_id BIGINT UNSIGNED NOT NULL auto_increment,
  product_service_product_id BIGINT UNSIGNED NOT NULL,
  product_service_product_parent_id BIGINT UNSIGNED NOT NULL,
  product_service_slug VARCHAR(20) NOT NULL DEFAULT '',
  PRIMARY KEY  (product_service_id),
  KEY product_service_product_id (product_service_product_id),
  KEY product_service_product_parent_id (product_service_product_parent_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_gzd_dhl_labelmeta (
  meta_id BIGINT UNSIGNED NOT NULL auto_increment,
  gzd_dhl_label_id BIGINT UNSIGNED NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY gzd_dhl_label_id (gzd_dhl_label_id),
  KEY meta_key (meta_key(32))
) $collate;";

        return $tables;
    }
}
