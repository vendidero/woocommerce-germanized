<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Admin_Note_Shiptastic_Migration extends WC_GZD_Admin_Note {

	public function is_disabled() {
		$is_disabled = true;

		if ( function_exists( 'wc_stc_get_shipments' ) && 'yes' === get_option( 'woocommerce_gzd_shiptastic_migration_has_errors' ) ) {
			$is_disabled = false;
		}

		if ( ! $is_disabled ) {
			return parent::is_disabled();
		} else {
			return true;
		}
	}

	public function get_name() {
		return 'shiptastic_migration';
	}

	public function get_title() {
		return __( 'Errors while migrating to Shiptastic', 'woocommerce-germanized' );
	}

	public function get_content() {
		return __( 'There was an error while migrating your Germanized Shipments integration to Shiptastic. Please check the status page to see what went wrong.', 'woocommerce-germanized' );
	}

	public function get_actions() {
		$buttons = array(
			array(
				'url'        => 'https://vendidero.de/doc/woocommerce-germanized/shipments-zu-shiptastic-migration',
				'title'      => __( 'Learn more', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => true,
			),
			array(
				'url'        => admin_url( 'admin.php?page=wc-status&tab=germanized' ),
				'title'      => __( 'View Status', 'woocommerce-germanized' ),
				'target'     => '_self',
				'is_primary' => false,
			),
		);

		return $buttons;
	}
}
