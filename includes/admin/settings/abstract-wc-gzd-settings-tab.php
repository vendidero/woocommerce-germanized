<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Adds Settings Interface to WooCommerce Settings Tabs
 *
 * @class 		WC_GZD_Settings_Germanized
 * @version		1.0.0
 * @author 		Vendidero
 */
abstract class WC_GZD_Settings_Tab extends WC_Settings_Page {

	public function __construct() {
		$this->id = 'germanized-' . $this->get_name();

		parent::__construct();

		remove_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( "woocommerce_sections_{$this->id}", array( $this, 'header' ), 15 );
	}

	public function get_current_section() {
		$current_section = isset( $_GET['section'] ) && ! empty( $_GET['section'] ) ? wc_clean( $_GET['section'] ) : '';

		return $current_section;
	}

	public function get_section_title( $section = '' ) {
		$sections      = $this->get_sections();
		$section_label = isset( $sections[ $section ] ) ? $sections[ $section ] : '';

		return $section_label;
	}

	public function header() {
		$breadcrumb = $this->get_breadcrumb();

		echo '<ul class="wc-gzd-settings-breadcrumb">';
		$count = 0;

		foreach( $breadcrumb as $breadcrumb_item ) {
			$count++;
			echo '<li class="breadcrumb-item breadcrumb-item-' . esc_attr( $breadcrumb_item['class'] ) . ' ' . ( $count === sizeof( $breadcrumb ) ? 'breadcrumb-item-active' : '' ) . '">' . ( ! empty( $breadcrumb_item['href'] ) ? '<a class="breadcrumb-link" href="' . esc_attr( $breadcrumb_item['href'] ) . '">' . esc_attr( $breadcrumb_item['title'] ) . '</a>' : $breadcrumb_item['title'] ) . '</li>';
		}

		echo '</ul>';

		$this->output_description();
	}

	protected function output_description() {
		$current_section = $this->get_current_section();

		if ( empty( $current_section ) ) {
			echo '<p class="tab-description">' . $this->get_description() . '</p>';
		} elseif( $desc = $this->get_section_description( $current_section ) ) {
			echo '<p class="tab-description tab-section-description">' . $desc . '</p>';
		}
	}

	protected function get_breadcrumb() {
		$sections        = $this->get_sections();
		$current_section = $this->get_current_section();
		$section_label   = $this->get_section_title( $current_section );

		$breadcrumb      = array( array(
			'class' => 'main',
			'href'  => admin_url( 'admin.php?page=wc-settings&tab=germanized' ),
			'title' => __( 'Germanized', 'woocommerce-germanized' )
		) );

		$breadcrumb[] = array(
			'class' => 'tab',
			'href'  => ! empty( $current_section ) ? $this->get_link() : '',
			'title' => $this->get_label()
		);

		if ( ! empty( $current_section ) ) {
			$breadcrumb[] = array(
				'class' => 'section',
				'href'  => '',
				'title' => $section_label,
			);
		}

		return apply_filters( "woocommerce_gzd_admin_settings_tab_{$this->id}_breadcrumb", $breadcrumb );
	}

	public function get_description() {}

	protected function get_section_description( $section ) {
		return '';
	}

	public function supports_disabling() {
		return false;
	}

	public function get_settings( $current_section = '' ) {
		$settings = $this->get_tab_settings( $current_section );

		if ( ! empty( $current_section ) ) {
			$settings = apply_filters( "woocommerce_gzd_admin_settings_tab_{$this->get_id()}", $settings );
		} else {
			$settings = apply_filters( "woocommerce_gzd_admin_settings_tab_{$this->get_id()}_{$current_section}", $settings );
		}

		return apply_filters( "woocommerce_gzd_admin_settings", $settings, $this->get_id(), $current_section );
	}

	abstract public function get_tab_settings( $current_section = '' );

	public function get_sidebar( $current_section = '' ) {
		return '';
	}

	public function output() {
		$current_section = $this->get_current_section();
		$current_tab     = $this->get_id();
		$settings        = $this->get_settings( $this->get_current_section() );
		$sidebar         = $this->get_sidebar( $this->get_current_section() );

		do_action( "woocommerce_gzd_admin_settings_before_{$this->get_id()}", $current_section );

		include_once 'views/html-admin-settings-section.php';
	}

	public function is_enabled() {
		if ( $this->supports_disabling() ) {
			if ( ! empty( $this->get_enable_option_name() ) ) {
				return 'yes' === get_option( $this->get_enable_option_name() );
			}

			return false;
		}

		return true;
	}

	public function disable() {
		if ( $this->supports_disabling() && ! empty( $this->get_enable_option_name() ) ) {
			update_option( $this->get_enable_option_name(), 'no' );
		}
	}

	public function enable() {
		if ( $this->supports_disabling() && ! empty( $this->get_enable_option_name() ) ) {
			update_option( $this->get_enable_option_name(), 'yes' );
		}
	}

	protected function get_enable_option_name() {}

	public function get_link() {
		return admin_url( 'admin.php?page=wc-settings&tab=' . sanitize_title( $this->get_id() ) );
	}

	abstract public function get_name();
}