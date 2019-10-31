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
