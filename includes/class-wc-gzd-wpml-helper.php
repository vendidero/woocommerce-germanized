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
	public $locale = false;

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	public function __construct() {
		
		if ( ! $this->is_activated() ) 
			return;

		add_action( 'init', array( $this, 'init' ), 5 );
		
		$this->filter_page_ids();
	}

	public function is_activated() {
		return WC_GZD_Dependencies::instance()->is_wpml_activated();
	}

	public function init() {
		// Observe order update and trigger hook
		add_action( 'post_updated', array( $this, 'observe_order_update' ), 0, 3 );
	}

	public function observe_order_update( $post_id, $post_after, $post_before ) {

		if ( 'shop_order' === $post_after->post_type ) {

			do_action( 'woocommerce_gzdp_before_order_post_status', $post_id );

			$order = wc_get_order( $post_id );
			$lang = null;

			// Reset GZD Locale
			if ( $lang = get_post_meta( $post_id, 'wpml_language', true ) ) {
				$this->set_language( $lang );
			}
		}

	}

	public function set_language( $lang ) {

		global $sitepress, $woocommerce;

		$sitepress->switch_lang( $lang, true );
        $this->locale = $sitepress->get_locale( $lang );
       	
       	add_filter( 'plugin_locale', array( $this, 'set_locale' ), 10, 2 );

        unload_textdomain( 'woocommerce' );
        unload_textdomain( 'woocommerce-germanized' );
        unload_textdomain( 'woocommerce-germanized-pro' );
        unload_textdomain( 'default' );
        
        $woocommerce->load_plugin_textdomain();
        WC_germanized()->load_plugin_textdomain();

        do_action( 'woocommerce_gzd_wpml_lang_changed', $lang );
        
        load_default_textdomain();
        global $wp_locale;
        $wp_locale = new WP_Locale();

	}

	public function set_locale( $locale, $domain ) {

		if( in_array( $domain, array( 'woocommerce', 'woocommerce-germanized', 'woocommerce-germanized-pro' ) ) && $this->locale ) {
            $locale = $this->locale;
        }

        return $locale;
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