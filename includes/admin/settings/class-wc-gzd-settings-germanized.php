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

        parent::__construct();
	}

	public function get_tabs() {
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-shopmarks.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-emails.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-taxes.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-button-solution.php';
		include_once dirname( __FILE__ ) . '/class-wc-gzd-settings-tab-checkboxes.php';

	    $tabs = apply_filters( 'woocommerce_gzd_admin_settings_tabs', array(
            'shopmarks'       => 'WC_GZD_Settings_Tab_Shopmarks',
            'taxes'           => 'WC_GZD_Settings_Tab_Taxes',
            'button_solution' => 'WC_GZD_Settings_Tab_Button_Solution',
            'emails'          => 'WC_GZD_Settings_Tab_Emails',
            'checkboxes'      => 'WC_GZD_Settings_Tab_Checkboxes'
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