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
		add_action( "woocommerce_sections_{$this->id}", array( $this, 'header' ), 5 );
	}

	public function get_current_section() {
		$current_section = isset( $_GET['section'] ) && ! empty( $_GET['section'] ) ? wc_clean( $_GET['section'] ) : '';

		return $current_section;
	}

	protected function get_pro_content_html() {
		ob_start();
		?>
		<div class="wc-gzd-premium-overlay notice notice-warning inline">
			<h3><?php _e( 'Get Germanized Pro to unlock', 'woocommerce-germanized' );?></h3>
			<p><?php _e( 'Enjoy even more professional features such as invoices, legal text generators, B2B VAT settings and premium support!', 'woocommerce-germanized' );?></p>
			<p><a class="button button-primary wc-gzd-button" href="https://vendidero.de/woocommerce-germanized" target="_blank"><?php _e( 'Upgrade now', 'woocommerce-germanized' ); ?></a></p>
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

		foreach( $breadcrumb as $breadcrumb_item ) {
			$count++;
			echo '<li class="breadcrumb-item breadcrumb-item-' . esc_attr( $breadcrumb_item['class'] ) . ' ' . ( $count === sizeof( $breadcrumb ) ? 'breadcrumb-item-active' : '' ) . '">' . ( ! empty( $breadcrumb_item['href'] ) ? '<a class="breadcrumb-link" href="' . esc_attr( $breadcrumb_item['href'] ) . '">' . $breadcrumb_item['title'] . '</a>' : $breadcrumb_item['title'] ) . '</li>';
		}

		echo '</ul>';

		$this->output_description();

		if ( $this->is_pro() && ! WC_germanized()->is_pro() ) {
			echo $this->get_pro_content_html();
		}
	}

	public function is_pro() {
		return false;
	}

	protected function output_description() {
		$current_section = $this->get_current_section();

		if( $desc = $this->get_section_description( $current_section ) ) {
			echo '<p class="tab-description tab-section-description">' . $desc . '</p>';
		} elseif( empty( $current_section ) ) {
			echo '<p class="tab-description">' . $this->get_description() . '</p>';
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
			$settings = apply_filters( "woocommerce_gzd_admin_settings_tab_{$this->get_name()}", $settings );
		} else {
			$settings = apply_filters( "woocommerce_gzd_admin_settings_tab_{$this->get_name()}_{$current_section}", $settings );
		}

		return apply_filters( "woocommerce_gzd_admin_settings", $settings, $this->get_name(), $current_section );
	}

	abstract public function get_tab_settings( $current_section = '' );

	public function get_sidebar( $current_section = '' ) {
		return '';
	}

	public function get_pointers() {
	    return array();
    }

	protected function is_saveable() {
	    return ( $this->is_pro() && ! WC_germanized()->is_pro() ? false : true );
    }

	public function output() {
		$current_section  = $this->get_current_section();
		$current_tab      = $this->get_id();
		$current_tab_name = $this->get_name();
		$settings         = $this->get_settings( $this->get_current_section() );
		$sidebar          = $this->get_sidebar( $this->get_current_section() );

		if ( ! $this->is_saveable() ) {
		    $GLOBALS['hide_save_button'] = true;
        }

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

	protected function before_save( $settings, $current_section = '' ) {
	    do_action(  "woocommerce_gzd_admin_settings_before_save_{$this->get_name()}", $settings );

	    if ( ! empty( $current_section ) ) {
		    do_action(  "woocommerce_gzd_admin_settings_before_save_{$this->get_name()}_{$current_section}", $settings );
	    }
    }

    protected function after_save( $settings, $current_section = '' ) {
	    do_action(  "woocommerce_gzd_admin_settings_after_save_{$this->get_name()}", $settings );

	    if ( ! empty( $current_section ) ) {
		    do_action(  "woocommerce_gzd_admin_settings_after_save_{$this->get_name()}_{$current_section}", $settings );
	    }
    }

	public function save() {
	    global $current_section;

		$settings = $this->get_settings( $current_section );

		$this->before_save( $settings, $current_section );
		WC_Admin_Settings::save_fields( $settings );
		$this->after_save( $settings, $current_section );
	}
}