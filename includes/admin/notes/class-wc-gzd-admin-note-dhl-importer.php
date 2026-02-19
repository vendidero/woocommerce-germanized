<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZD_Admin_Note_DHL_Importer extends WC_GZD_Admin_Note {

	protected function is_dhl_plugin_enabled() {
		return class_exists( 'PR_DHL_WC' ) ? true : false;
	}

	public function is_disabled() {
		$is_disabled = true;

		if ( $this->is_dhl_plugin_enabled() ) {
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
		$content = '<p>' . _x( 'It seems like you are currently using the DHL for WooCommerce plugin. Our plugin Shiptastic for WooCommerce does now fully integrate DHL services and switching is as simple as can be. Check your advantages by using the DHL integration in Shiptastic and let Shiptastic import your current settings for you.', 'dhl', 'woocommerce-germanized' ) . '</p>
		    <ul>
		        <li>' . _x( 'Perfectly integrated with Germanized any many other plugins', 'dhl', 'woocommerce-germanized' ) . '</li>
		        <li>' . sprintf( _x( 'Many improved features such as automation, services per shipping method and %s', 'dhl', 'woocommerce-germanized' ), '<a href="https://vendidero.de/doc/woocommerce-germanized/dhl-labels-zu-sendungen-erstellen" target="_blank">' . _x( 'many more', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</li>
		        <li>' . _x( 'Allow your customers to select Packstation or Postfiliale delivery conveniently from within your checkout', 'dhl', 'woocommerce-germanized' ) . '</li>
		    </ul>';

		return $content;
	}

	protected function has_nonce_action() {
		return true;
	}

	public function get_actions() {
		return array(
			array(
				'url'          => add_query_arg(
					array(
						'wc-gzd-check-install_shiptastic' => true,
						'install-dhl'                     => true,
					),
					admin_url( 'admin.php?page=wc-settings&tab=germanized' )
				),
				'title'        => __( 'Install Shiptastic & DHL Integration now', 'woocommerce-germanized' ),
				'target'       => '_self',
				'is_primary'   => true,
				'nonce_action' => 'wc-gzd-check-install_shiptastic',
			),
			array(
				'url'        => 'https://vendidero.de/doc/woocommerce-germanized/dhl-labels-zu-sendungen-erstellen',
				'title'      => _x( 'Learn more', 'dhl', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => false,
			),
		);
	}
}
