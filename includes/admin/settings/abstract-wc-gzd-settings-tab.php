<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Settings Interface to WooCommerce Settings Tabs
 *
 * @class        WC_GZD_Settings_Germanized
 * @version        1.0.0
 * @author        Vendidero
 */
abstract class WC_GZD_Settings_Tab extends WC_Settings_Page {

	public function __construct() {
		$this->id = 'germanized-' . $this->get_name();

		parent::__construct();

		remove_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( "woocommerce_sections_{$this->id}", array( $this, 'header' ), 5 );
	}

	public function notice_on_activate() {
	    return false;
    }

	public function get_current_section() {
		$current_section = isset( $_GET['section'] ) && ! empty( $_GET['section'] ) ? wc_clean( $_GET['section'] ) : '';

		return $current_section;
	}

	protected function get_pro_content_html() {
		ob_start();
		?>
        <div class="wc-gzd-premium-overlay notice notice-warning inline">
            <h3><?php _e( 'Get Germanized Pro to unlock', 'woocommerce-germanized' ); ?></h3>
            <p><?php _e( 'Enjoy even more professional features such as invoices, legal text generators, B2B VAT settings and premium support!', 'woocommerce-germanized' ); ?></p>
            <p><a class="button button-primary wc-gzd-button" href="https://vendidero.de/woocommerce-germanized"
                  target="_blank"><?php _e( 'Upgrade now', 'woocommerce-germanized' ); ?></a></p>
        </div>
		<?php
		$html = ob_get_clean();

		return $html;
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

		foreach ( $breadcrumb as $breadcrumb_item ) {
			$count ++;
			echo '<li class="breadcrumb-item breadcrumb-item-' . esc_attr( $breadcrumb_item['class'] ) . ' ' . ( $count === sizeof( $breadcrumb ) ? 'breadcrumb-item-active' : '' ) . '">' . ( ! empty( $breadcrumb_item['href'] ) ? '<a class="breadcrumb-link" href="' . esc_attr( $breadcrumb_item['href'] ) . '">' . $breadcrumb_item['title'] . '</a>' : $breadcrumb_item['title'] ) . '</li>';
		}

		echo '</ul>';

		$this->output_description();

		if ( $this->is_pro() && ! WC_germanized()->is_pro() ) {
			echo $this->get_pro_content_html();
		}
	}

	public function get_help_link() {
		return '';
	}

	public function has_help_link() {
		$help_link = $this->get_help_link();

		return ( ! empty( $help_link ) ? true : false );
	}

	public function is_pro() {
		return false;
	}

	protected function output_description() {
		$current_section = $this->get_current_section();

		if ( $desc = $this->get_section_description( $current_section ) ) {
			echo '<p class="tab-description tab-section-description">' . $desc . '</p>';
		} elseif ( empty( $current_section ) ) {
			echo '<p class="tab-description">' . $this->get_description() . '</p>';
		}
	}

	protected function get_additional_breadcrumb_items( $breadcrumb ) {
	    return $breadcrumb;
    }

	/**
	 * Output sections.
	 */
	public function output_sections() {
		parent::output_sections();
	}

	protected function get_breadcrumb() {
		$sections        = $this->get_sections();
		$current_section = $this->get_current_section();
		$section_label   = $this->get_section_title( $current_section );

		$breadcrumb = array(
			array(
				'class' => 'main',
				'href'  => admin_url( 'admin.php?page=wc-settings&tab=germanized' ),
				'title' => __( 'Germanized', 'woocommerce-germanized' )
			)
		);

		$breadcrumb[] = array(
			'class' => 'tab',
			'href'  => ! empty( $current_section ) ? $this->get_link() : '',
			'title' => empty( $current_section ) ? $this->get_breadcrumb_label( $this->get_label() ) : $this->get_label()
		);

		if ( ! empty( $current_section ) ) {
			$breadcrumb[] = array(
				'class' => 'section',
				'href'  => '',
				'title' => $this->get_breadcrumb_label( $section_label ),
			);
		}

		$breadcrumb = $this->get_additional_breadcrumb_items( $breadcrumb );

		/**
		 * Filter to adjust the breadcrumb items for a certain settings tab.
		 *
		 * The dynamic portion of the hook name, `$this->get_name()` refers to the tab name e.g. checkboxes.
		 *
		 * @param array $breadcrumb Array containing breadcrumb data.
		 *
		 * @since 3.0.0
		 *
		 */
		return apply_filters( "woocommerce_gzd_admin_settings_tab_{$this->get_name()}_breadcrumb", $breadcrumb );
	}

	public function get_description() {
	}

	protected function get_breadcrumb_label( $label ) {
		if ( empty( $this->get_current_section() ) && $this->has_help_link() ) {
			$label = $label . '<a class="page-title-action" href="' . esc_url( $this->get_help_link() ) . '" target="_blank">' . __( 'Learn more', 'woocommerce-germanized' ) . '</a>';
		}

		return $label;
	}

	protected function get_section_description( $section ) {
		return '';
	}

	public function supports_disabling() {
		return false;
	}

	private function _get_settings( $section_id ) {
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
			$settings = apply_filters( "woocommerce_gzd_admin_settings_tab_{$this->get_name()}", $settings );
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
			$settings = apply_filters( "woocommerce_gzd_admin_settings_tab_{$this->get_name()}_{$section_id}", $settings );
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
		return apply_filters( "woocommerce_gzd_admin_settings", $settings, $this->get_name(), $section_id );
	}

	public function get_settings_for_section_core( $section_id ) {
		return $this->_get_settings( $section_id );
	}

	public function get_settings( $section_id = '' ) {
		return $this->_get_settings( $section_id );
	}

	abstract public function get_tab_settings( $current_section = '' );

	public function get_sidebar( $current_section = '' ) {
		return '';
	}

	public function has_tutorial() {
		$pointers = $this->get_pointers();

		return ! empty( $pointers ) ? true : false;
	}

	public function get_pointers() {
		return array();
	}

	protected function is_saveable() {
		return ( $this->is_pro() && ! WC_germanized()->is_pro() ? false : true );
	}

	public function hide_from_main_panel() {
	    return false;
	}

	public function output() {
		$current_section  = $this->get_current_section();
		$current_tab      = $this->get_id();
		$current_tab_name = $this->get_name();
		$settings         = $this->get_settings_for_section_core( $this->get_current_section() );
		$sidebar          = $this->get_sidebar( $this->get_current_section() );

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
		 *
		 */
		do_action( "woocommerce_gzd_admin_settings_before_wrapper_{$this->get_name()}", $current_section );

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

	protected function get_enable_option_name() {
	    return '';
	}

	public function get_link() {
		return admin_url( 'admin.php?page=wc-settings&tab=' . sanitize_title( $this->get_id() ) );
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
		do_action( "woocommerce_gzd_admin_settings_before_save_{$this->get_name()}", $settings, $current_section );

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
			do_action( "woocommerce_gzd_admin_settings_before_save_{$this->get_name()}_{$current_section}", $settings );
		}

		if ( $this->notice_on_activate() && $this->supports_disabling() && ! empty( $this->get_enable_option_name() ) ) {

		    // Option seems to be activated
		    if ( 'yes' !== get_option( $this->get_enable_option_name() ) && ! empty( $_POST[ $this->get_enable_option_name() ] ) ) {
			    WC_Admin_Settings::add_error( $this->notice_on_activate() );
            }
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
		do_action( "woocommerce_gzd_admin_settings_after_save_{$this->get_name()}", $settings, $current_section );

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
			do_action( "woocommerce_gzd_admin_settings_after_save_{$this->get_name()}_{$current_section}", $settings );
		}
	}

	public function save() {
		global $current_section;

		$settings = $this->get_settings_for_section_core( $current_section );

		$this->before_save( $settings, $current_section );
		WC_Admin_Settings::save_fields( $settings );
		$this->after_save( $settings, $current_section );
	}
}