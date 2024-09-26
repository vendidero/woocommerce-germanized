<?php

namespace Vendidero\Germanized\DHL\Legacy;

defined( 'ABSPATH' ) || exit;

class Helper {

	public static function maybe_define_db_tables() {
		global $wpdb;

		if ( ! isset( $wpdb->gzd_dhl_labels ) ) {
			$tables = array(
				'gzd_dhl_labelmeta' => 'woocommerce_gzd_dhl_labelmeta',
				'gzd_dhl_labels'    => 'woocommerce_gzd_dhl_labels',
			);

			foreach ( $tables as $name => $table ) {
				$wpdb->$name    = $wpdb->prefix . $table;
				$wpdb->tables[] = $table;
			}
		}
	}
}
