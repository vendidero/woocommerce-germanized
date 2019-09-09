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
class WC_GZD_Settings_Germanized extends WC_Settings_Page {

    protected $id = 'germanized';

    protected $tabs = null;

	public function __construct() {
		$this->label = __( 'Germanized', 'woocommerce-germanized' );
		$this->get_tabs();

		add_filter( 'admin_body_class', array( $this, 'add_body_classes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		parent::__construct();
	}

	public function get_settings() {
		$settings = array();

		foreach( $this->get_tabs() as $tab ) {
			$sections = $tab->get_sections();

			if ( ! empty( $sections ) ) {
				foreach( $tab->get_sections() as $section_name => $section ) {
					$settings = array_merge( $settings, $tab->get_settings( $section_name ) );
				}
			} else {
				$settings = array_merge( $settings, $tab->get_settings() );
			}
		}

		return $settings;
	}

	public function admin_styles() {
		// Admin styles for WC pages only.
		if ( $this->is_active() ) {
			wp_enqueue_style( 'woocommerce-gzd-admin-settings' );
			do_action( 'woocommerce_gzd_admin_settings_styles' );
		}
	}

	public function admin_scripts() {
		if ( $this->is_active() ) {
			wp_enqueue_script( 'wc-gzd-admin-settings' );
			do_action( 'woocommerce_gzd_admin_settings_scripts' );
		}
	}

	protected function is_active() {
		if ( isset( $_GET['tab'] ) && strpos( $_GET['tab'], 'germanized' ) !== false ) {
			return true;
		}

		return false;
	}

	public function add_body_classes( $classes ) {
		if ( $this->is_active() ) {
			$classes = $classes . ' wc-gzd-settings';
		}

        return $classes;
	}

	public function get_tabs() {

		include_once dirname( __FILE__ ) . '/abstract-wc-gzd-settings-tab.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-general.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-shopmarks.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-emails.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-taxes.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-button-solution.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-checkboxes.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-doi.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-contract.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-invoices.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-multistep-checkout.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-terms-generator.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-revocation-generator.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-dhl.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-shipments.php';

	    $tabs = apply_filters( 'woocommerce_gzd_admin_settings_tabs', array(
		    'general'              => 'WC_GZD_Settings_Tab_General',
            'shopmarks'            => 'WC_GZD_Settings_Tab_Shopmarks',
            'taxes'                => 'WC_GZD_Settings_Tab_Taxes',
		    'contract'             => 'WC_GZD_Settings_Tab_Contract',
            'button_solution'      => 'WC_GZD_Settings_Tab_Button_Solution',
		    'shipments'            => 'WC_GZD_Settings_Tab_Shipments',
		    'dhl'                  => 'WC_GZD_Settings_Tab_DHL',
            'emails'               => 'WC_GZD_Settings_Tab_Emails',
            'checkboxes'           => 'WC_GZD_Settings_Tab_Checkboxes',
            'double_opt_in'        => 'WC_GZD_Settings_Tab_DOI',
		    'invoices'             => 'WC_GZD_Settings_Tab_Invoices',
		    'multistep_checkout'   => 'WC_GZD_Settings_Tab_Multistep_Checkout',
		    'terms_generator'      => 'WC_GZD_Settings_Tab_Terms_Generator',
		    'revocation_generator' => 'WC_GZD_Settings_Tab_Revocation_Generator',
        ) );

	    if ( is_null( $this->tabs ) ) {
	    	$this->tabs = array();

	    	foreach( $tabs as $key => $tab ) {
	    		$this->tabs[ $key ] = new $tab;
		    }
	    }

	    return $this->tabs;
    }

    public function output() {
		$GLOBALS['hide_save_button'] = true;
		$tabs                        = $this->get_tabs();

	    include_once dirname( __FILE__ ) . '/views/html-admin-settings-tabs.php';
    }
}