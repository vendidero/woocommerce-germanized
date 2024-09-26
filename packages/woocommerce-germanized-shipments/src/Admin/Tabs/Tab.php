<?php

namespace Vendidero\Germanized\Shipments\Admin\Tabs;

use Vendidero\Germanized\Shipments\Admin\Settings;
use Vendidero\Germanized\Shipments\Package;

/**
 * Settings Interface
 */
abstract class Tab extends \WC_Settings_Page {

	public function __construct() {
		$this->id = 'shipments-' . $this->get_name();

		parent::__construct();

		remove_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( "woocommerce_sections_{$this->id}", array( $this, 'header' ), 5 );
	}

	public function get_current_section() {
		$current_section = isset( $_GET['section'] ) && ! empty( $_GET['section'] ) ? wc_clean( wp_unslash( $_GET['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return $current_section;
	}

	public function needs_install() {
		if ( $extension_name = $this->get_extension_name() ) {
			return ! \Vendidero\Germanized\PluginsHelper::is_plugin_active( $extension_name );
		}

		return false;
	}

	public function get_extension_name() {
		return '';
	}

	public function get_section_title( $section = '' ) {
		$sections      = $this->get_sections();
		$section_label = isset( $sections[ $section ] ) ? $sections[ $section ] : '';

		return $section_label;
	}

	public function header() {
		$breadcrumb = $this->get_breadcrumb();

		echo '<ul class="wc-gzd-shipments-settings-breadcrumb">';
		$count = 0;

		foreach ( $breadcrumb as $breadcrumb_item ) {
			++$count;
			echo '<li class="breadcrumb-item breadcrumb-item-' . esc_attr( $breadcrumb_item['class'] ) . ' ' . ( count( $breadcrumb ) === $count ? 'breadcrumb-item-active' : '' ) . '">' . ( ! empty( $breadcrumb_item['href'] ) ? '<a class="breadcrumb-link" href="' . esc_attr( $breadcrumb_item['href'] ) . '">' . wp_kses_post( $breadcrumb_item['title'] ) . '</a>' : wp_kses_post( $breadcrumb_item['title'] ) ) . '</li>';
		}

		echo '</ul>';

		$this->output_description();
	}

	public function get_help_link() {
		return '';
	}

	public function has_help_link() {
		$help_link = $this->get_help_link();

		return ( ! empty( $help_link ) ? true : false );
	}

	public function is_pro( $section = '' ) {
		return false;
	}

	protected function output_description() {
		$current_section = $this->get_current_section();

		if ( $desc = $this->get_section_description( $current_section ) ) {
			echo '<p class="tab-description tab-section-description">' . wp_kses_post( $desc ) . '</p>';
		} elseif ( empty( $current_section ) ) {
			echo '<p class="tab-description">' . wp_kses_post( $this->get_description() ) . '</p>';
		}
	}

	protected function get_additional_breadcrumb_items( $breadcrumb ) {
		return $breadcrumb;
	}

	/**
	 * Output sections.
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 1 === count( $sections ) ) {
			return;
		}

		echo '<ul class="subsubsub">';

		$array_keys = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			$class     = ( $current_section === $id ? 'current' : '' );
			$separator = ( end( $array_keys ) === $id ? '' : '|' );
			$text      = esc_html( $label ) . ( $this->is_pro( $id ) && ! Package::is_pro() ? '<span class="wc-gzd-shipments-pro wc-gzd-shipments-pro-outlined">pro</span>' : '' );
			echo "<li><a href='" . esc_url( $this->get_section_url( $id ) ) . "' class='" . esc_attr( $class ) . "'>" . wp_kses_post( $text ) . '</a> ' . esc_html( $separator ) . '</li>';
		}

		echo '</ul><br class="clear" />';
	}

	protected function get_section_url( $section_id ) {
		return $this->get_url( $section_id );
	}

	protected function get_breadcrumb() {
		$sections        = $this->get_sections();
		$current_section = $this->get_current_section();
		$section_label   = $this->get_section_title( $current_section );
		$breadcrumb      = Settings::get_main_breadcrumb();

		$breadcrumb[] = array(
			'class' => 'tab',
			'href'  => ! empty( $current_section ) ? $this->get_url() : '',
			'title' => empty( $current_section ) ? $this->get_breadcrumb_label( $this->get_label() ) : $this->get_label(),
		);

		if ( ! empty( $current_section ) ) {
			$breadcrumb[] = array(
				'class' => 'section',
				'href'  => '',
				'title' => $this->get_breadcrumb_label( $section_label ),
			);
		}

		$breadcrumb = $this->get_additional_breadcrumb_items( $breadcrumb );

		return apply_filters( "woocommerce_gzd_shipments_admin_settings_tab_{$this->get_name()}_breadcrumb", $breadcrumb );
	}

	public function get_description() {
		return '';
	}

	protected function get_breadcrumb_label( $label ) {
		$section = $this->get_current_section();

		if ( empty( $section ) && $this->has_help_link() ) {
			$label = $label . '<a class="page-title-action" href="' . esc_url( $this->get_help_link() ) . '" target="_blank">' . _x( 'Learn more', 'shipments', 'woocommerce-germanized' ) . '</a>';
		} elseif ( ! empty( $section ) && ! Package::is_pro() && $this->is_pro( $section ) ) {
			$label = $label . '<span class="wc-gzd-shipments-pro wc-gzd-shipments-pro-outlined">pro</span>';
		}

		return $label;
	}

	protected function get_section_description( $section ) {
		return '';
	}

	public function supports_disabling() {
		return false;
	}

	private function get_settings_internal( $section_id ) {
		$settings = $this->get_tab_settings( $section_id );

		if ( empty( $section_id ) ) {
			/**
			 * Filter to adjust the settings for a certain settings tab.
			 *
			 * The dynamic portion of the hook name, `$this->get_name()` refers to the tab name e.g. checkboxes.
			 *
			 * @param array $settings Array containing settings data.
			 *
			 * @since 3.0.0
			 */
			$settings = apply_filters( "woocommerce_gzd_shipments_admin_settings_tab_{$this->get_name()}", $settings );
		} else {
			/**
			 * Filter to adjust the settings for a certain section of a settings tab.
			 *
			 * The dynamic portion of the hook name, `$this->get_name()` refers to the tab name e.g. checkboxes.
			 * `$current_section` refers to the current section e.g. product_widget.
			 *
			 * @param array $settings Array containing settings data.
			 *
			 * @since 3.0.0
			 */
			$settings = apply_filters( "woocommerce_gzd_shipments_admin_settings_tab_{$this->get_name()}_{$section_id}", $settings );
		}

		/**
		 * General filter to adjust the settings for setting tabs.
		 *
		 * @param array $settings Array containing settings data.
		 * @param string $tab_name The name of the tab e.g. checkboxes
		 * @param string $section_id The section name e.g. product_widgets. Might be empty too.
		 *
		 * @since 3.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_shipments_admin_settings', $settings, $this->get_name(), $section_id );
	}

	public function get_settings_for_section_core( $section_id ) {
		return $this->get_settings_internal( $section_id );
	}

	public function get_settings( $section_id = '' ) {
		return $this->get_settings_internal( $section_id );
	}

	abstract public function get_tab_settings( $current_section = '' );

	public function has_tutorial() {
		$pointers = $this->get_pointers();

		return ! empty( $pointers ) ? true : false;
	}

	public function get_pointers() {
		return array();
	}

	protected function is_saveable() {
		return ( $this->is_pro() && ! Package::is_pro() ? false : true );
	}

	public function hide_from_main_panel() {
		return false;
	}

	public function output() {
		$current_section  = $this->get_current_section();
		$current_tab      = $this->get_id();
		$current_tab_name = $this->get_name();
		$settings         = $this->get_settings_for_section_core( $this->get_current_section() );

		if ( ! $this->is_saveable() ) {
			$GLOBALS['hide_save_button'] = true;
		}

		/**
		 * Fires before settings for a certain tab are rendered.
		 *
		 * The dynamic portion of the hook name, $this->get_name(),
		 * refers to the current tab name e.g. checkboxes.
		 *
		 * @param string $current_section The current sub section of the tab.
		 *
		 * @since 3.0.0
		 */
		do_action( "woocommerce_gzd_shipments_admin_settings_before_wrapper_{$this->get_name()}", $current_section );

		include Package::get_path( 'includes/admin/views/tabs/html-admin-settings-section.php' );
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

	protected function get_enable_option_name() {
		return '';
	}

	public function get_url( $section = '' ) {
		$link = admin_url( 'admin.php?page=wc-settings&tab=' . sanitize_title( $this->get_id() ) . ( ! empty( $section ) ? '&section=' . sanitize_title( $section ) : '' ) );

		return $link;
	}

	abstract public function get_name();

	protected function before_save( $settings, $current_section = '' ) {
		/**
		 * Fires before settings for a certain tab are saved.
		 *
		 * The dynamic portion of the hook name, `$this->get_name()`,
		 * refers to the current tab id e.g. checkboxes.
		 *
		 * @param array  $settings Array containing the settings to be saved.
		 * @param string $current_section The current section.
		 *
		 * @since 3.0.0
		 */
		do_action( "woocommerce_gzd_shipments_admin_settings_before_save_{$this->get_name()}", $settings, $current_section );

		if ( ! empty( $current_section ) ) {

			/**
			 * Fires before settings for a certain section of a tab are saved.
			 *
			 * The dynamic portion of the hook name, `$this->get_name()` and `$current_section`,
			 * refer to the current tab id e.g. checkboxes and the current section name e.g. product_widgets.
			 *
			 * @param array $settings Array containing the settings to be saved.
			 *
			 * @since 3.0.0
			 */
			do_action( "woocommerce_gzd_shipments_admin_settings_before_save_{$this->get_name()}_{$current_section}", $settings );
		}
	}

	protected function after_save( $settings, $current_section = '' ) {
		/**
		 * Fires after settings for a certain tab have been saved.
		 *
		 * The dynamic portion of the hook name, `$this->get_name()`,
		 * refers to the current tab id e.g. checkboxes.
		 *
		 * @param array  $settings Array containing the settings to be saved.
		 * @param string $current_section The current section.
		 *
		 * @since 3.0.0
		 */
		do_action( "woocommerce_gzd_shipments_admin_settings_after_save_{$this->get_name()}", $settings, $current_section );

		if ( ! empty( $current_section ) ) {

			/**
			 * Fires after settings for a certain section of a tab are saved.
			 *
			 * The dynamic portion of the hook name, `$this->get_name()` and `$current_section`,
			 * refer to the current tab id e.g. checkboxes and the current section name e.g. product_widgets.
			 *
			 * @param array $settings Array containing the settings to be saved.
			 *
			 * @since 3.0.0
			 *
			 */
			do_action( "woocommerce_gzd_shipments_admin_settings_after_save_{$this->get_name()}_{$current_section}", $settings );
		}
	}

	public function save() {
		global $current_section;

		$settings = $this->get_settings_for_section_core( $current_section );

		$this->before_save( $settings, $current_section );
		\WC_Admin_Settings::save_fields( $settings );
		$this->after_save( $settings, $current_section );
	}
}
