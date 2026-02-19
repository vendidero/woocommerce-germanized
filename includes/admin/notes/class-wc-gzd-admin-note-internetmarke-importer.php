<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZD_Admin_Note_Internetmarke_Importer extends WC_GZD_Admin_Note {

	protected function is_im_plugin_installed() {
		return defined( 'WCDPI_PLUGIN_FILE' ) ? true : false;
	}

	public function is_disabled() {
		$is_disabled = true;

		if ( $this->is_im_plugin_installed() ) {
			$is_disabled = false;
		}

		if ( ! $is_disabled ) {
			return parent::is_disabled();
		} else {
			return true;
		}
	}

	public function get_name() {
		return 'internetmarke_importer';
	}

	public function get_title() {
		return _x( 'Internetmarke built-in Integration', 'dhl', 'woocommerce-germanized' );
	}

	public function get_content() {
		$content = '<p>' . _x( 'It seems like you are currently using the Deutsche Post Internetmarke plugin. Our plugin Shiptastic for WooCommerce does now fully integrate Internetmarke and switching is as simple as can be. Check your advantages by using the Internetmarke integration in Shiptastic and let Shiptastic import your current settings for you.', 'dhl', 'woocommerce-germanized' ) . '</p>
		    <ul>
		        <li>' . _x( 'Perfectly integrated with Germanized any many other plugins', 'dhl', 'woocommerce-germanized' ) . '</li>
		        <li>' . sprintf( _x( 'Many improved features such as automation, services per shipping method and %s', 'dhl', 'woocommerce-germanized' ), '<a href="https://vendidero.de/doc/woocommerce-germanized/internetmarke-integration-einrichten" target="_blank">' . _x( 'many more', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</li>
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
				'title'        => __( 'Install Shiptastic & Internetmarke Integration now', 'woocommerce-germanized' ),
				'target'       => '_self',
				'is_primary'   => true,
				'nonce_action' => 'wc-gzd-check-install_shiptastic',
			),
			array(
				'url'        => 'https://vendidero.de/doc/woocommerce-germanized/internetmarke-integration-einrichten',
				'title'      => _x( 'Learn more', 'dhl', 'woocommerce-germanized' ),
				'target'     => '_blank',
				'is_primary' => false,
			),
		);
	}
}
