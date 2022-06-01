<?php

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZD_Admin_Note_Theme_Supported extends WC_GZD_Admin_Note {

	protected $current_theme = null;

	public function is_disabled() {

		if ( ! WC_GZD_Admin_Notices::instance()->is_theme_supported_by_pro() ) {
			return true;
		}

		return parent::is_disabled();
	}

	protected function get_current_theme() {
		if ( is_null( $this->current_theme ) ) {
			$this->current_theme = wp_get_theme();
		}

		return $this->current_theme;
	}

	public function get_name() {
		return 'theme_supported';
	}

	public function is_pro() {
		return true;
	}

	public function get_title() {
		$current_theme = $this->get_current_theme();

		return sprintf( __( 'Enable full %s support', 'woocommerce-germanized' ), $current_theme->get( 'Name' ) );
	}

	public function get_content() {
		$current_theme = $this->get_current_theme();

		return sprintf( __( 'Your current theme %1$s needs some adaptions to seamlessly integrate with Germanized. Our Pro Version will <strong>enable support for %2$s</strong> and makes sure Germanized settings are shown and styled within frontend for a better user experience. A better user experience will help you selling more products.', 'woocommerce-germanized' ), $current_theme->get( 'Name' ), $current_theme->get( 'Name' ) );
	}

	public function get_actions() {
		$current_theme = $this->get_current_theme();

		return array(
			array(
				'url'        => 'https://vendidero.de/woocommerce-germanized#upgrade',
				'title'      => sprintf( __( 'Enable support for %s', 'woocommerce-germanized' ), $current_theme->get( 'Name' ) ),
				'target'     => '_blank',
				'is_primary' => true,
			),
		);
	}
}
