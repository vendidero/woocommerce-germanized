<?php
/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class 		WC_GZD_WPML_Helper
 * @category	Class
 * @author 		vendidero
 */
class WC_GZD_WPML_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	public function __construct() {
		
		if ( ! $this->is_activated() ) 
			return;
		
		$this->filter_page_ids();
	}

	public function is_activated() {
		return WC_GZD_Dependencies::instance()->is_wpml_activated();
	}

	public function filter_page_ids() {
		$woo_pages = array(
            'revocation_page_id',
            'data_security_page_id',
            'imprint_page_id',
            'payment_methods_page_id',
            'shipping_costs_page_id'
        );
        foreach ( $woo_pages as $page ) {
        	add_filter( 'woocommerce_get_' . $page, array( $this, 'translate_page' ) );
            add_filter( 'option_woocommerce_' . $page, array( $this, 'translate_page') );
        }
	}

	public function translate_page( $id ) {
        global $pagenow;
        if( is_admin() && $pagenow == 'options-permalink.php' )
            return $id;
        return apply_filters( 'translate_object_id', $id, 'page', true );
    }

}

return WC_GZD_WPML_Helper::instance();