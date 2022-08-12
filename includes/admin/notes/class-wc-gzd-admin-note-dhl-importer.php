<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZD_Admin_Note_DHL_Importer extends WC_GZD_Admin_Note {

	public function is_disabled() {
		$is_disabled = true;

		if ( class_exists( 'Vendidero\Germanized\DHL\Admin\Importer\DHL' ) && Vendidero\Germanized\DHL\Admin\Importer\DHL::is_plugin_enabled() ) {
			$is_disabled = false;
		}

		if ( ! $is_disabled ) {
			return parent::is_disabled();
		} else {
			return true;
		}
	}

	public function get_name() {
		return 'dhl_importer';
	}

	public function get_title() {
		return _x( 'DHL built-in Integration', 'dhl', 'woocommerce-germanized' );
	}

	public function get_content() {
		$content = '<p>' . _x( 'It seems like you are currently using the DHL for WooCommerce plugin. Germanized does now fully integrate DHL services and switching is as simple as can be. Check your advantages by using the DHL integration in Germanized and let Germanized import your current settings for you.', 'dhl', 'woocommerce-germanized' ) . '</p>
		    <ul>
		        <li>' . _x( 'No need to use an external plugin which might lead to incompatibilities', 'dhl', 'woocommerce-germanized' ) . '</li>
		        <li>' . sprintf( _x( 'Many improved features such as automation, services per shipping method and %s', 'dhl', 'woocommerce-germanized' ), '<a href="https://vendidero.de/dokument/dhl-labels-zu-sendungen-erstellen" target="_blank">' . _x( 'many more', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</li>
		        <li>' . _x( 'Perfectly integrated in Germanized &ndash; easily create labels for shipments', 'dhl', 'woocommerce-germanized' ) . '</li>
		    </ul>';

		return $content;
	}

	protected function has_nonce_action() {
		return true;
	}

	public function get_actions() {
		return array(
			array(
				'url'          => add_query_arg( 'wc-gzd-dhl-import', 'yes', admin_url( 'admin.php?page=wc-settings&tab=germanized-shipping_provider&provider=dhl' ) ),
				'title'        => Vendidero\Germanized\DHL\Admin\Importer\DHL::is_available() ? _x( 'Import settings and activate', 'dhl', 'woocommerce-germanized' ) : _x( 'Use integration', 'dhl', 'woocommerce-germanized' ),
				'target'       => '_self',
				'is_primary'   => true,
				'nonce_action' => 'woocommerce_gzd_dhl_import_nonce',
			),
			array(
				'url'        => 'https://vendidero.de/dokument/dhl-labels-zu-sendungen-erstellen',
				'title'      => _x( 'Learn more', 'dhl', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => false,
			),
		);
	}
}
