<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Adds Germanized Shortcodes
 *
 * @class 		WC_GZD_Shortcodes
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Shortcodes {
	
	/**
	 * Initializes Shortcodes
	 */
	public static function init() {
		
		// Define shortcodes
		$shortcodes = array(
			'revocation_form'            => __CLASS__ . '::revocation_form',
			'payment_methods_info'		 => __CLASS__ . '::payment_methods_info',
			'trusted_shops_rich_snippets'=> __CLASS__ . '::trusted_shops_rich_snippets',
			'trusted_shops_reviews'		 => __CLASS__ . '::trusted_shops_reviews',
			'trusted_shops_badge'		 => __CLASS__ . '::trusted_shops_badge',
			'ekomi_badge'				 => __CLASS__ . '::ekomi_badge',
			'ekomi_widget'				 => __CLASS__ . '::ekomi_widget',
			'gzd_feature'				 => __CLASS__ . '::gzd_feature',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}

	}

	/**
	 * Returns revocation_form template html
	 *  
	 * @param  array $atts 
	 * @return string revocation form html       
	 */
	public static function revocation_form( $atts ) {
		
		ob_start();
		woocommerce_get_template( 'forms/revocation-form.php' );
		$return = '<div class="woocommerce woocommerce-gzd">' . ob_get_clean() . '</div>';
		return $return;
	
	}

	/**
	 * Returns payment methods info html
	 *  
	 * @param  array $atts
	 * @return string
	 */
	public static function payment_methods_info( $atts ) {
		
		ob_start();
		woocommerce_get_template( 'global/payment-methods.php' );
		$return = '<div class="woocommerce woocommerce-gzd">' . ob_get_clean() . '</div>';
		return $return;
	
	}

	/**
	 * Returns Trusted Shops rich snippet review html
	 *  
	 * @param  array $atts 
	 * @return string       
	 */
	public static function trusted_shops_rich_snippets( $atts ) {
		
		ob_start();
		woocommerce_get_template( 'trusted-shops/rich-snippets.php' );
		$html = ob_get_clean();
		return WC_germanized()->trusted_shops->is_enabled() ? '<div class="woocommerce woocommerce-gzd">' . $html . '</div>' : '';
	
	}

	/**
	 * Returns Trusted Shops reviews graphic
	 *  
	 * @param  array $atts 
	 * @return string       
	 */
	public static function trusted_shops_reviews( $atts ) {
		
		ob_start();
		woocommerce_get_template( 'trusted-shops/reviews.php' );
		$html = ob_get_clean();
		return WC_germanized()->trusted_shops->is_enabled() ? '<div class="woocommerce woocommerce-gzd">' . $html . '</div>' : '';
	
	}

	/**
	 * Returns Trusted Shops Badge html
	 *  
	 * @param  array $atts 
	 * @return string       
	 */
	public static function trusted_shops_badge( $atts ) {

		extract( shortcode_atts( array('width' => ''), $atts ) );
		return WC_germanized()->trusted_shops->is_enabled() ? '<a class="trusted-shops-badge" style="' . ( $width ? 'background-size:' . ( $width - 1 ) . 'px auto; width: ' . $width . 'px; height: ' . $width . 'px;' : '' ) . '" href="' . WC_germanized()->trusted_shops->get_certificate_link() . '" target="_blank"></a>' : '';
	
	}

	/**
	 * Returns eKomi Badge html
	 *  
	 * @param  array $atts 
	 * @return string     
	 */
	public static function ekomi_badge( $atts ) {

		return WC_germanized()->ekomi->get_badge( $atts );
	
	}

	/**
	 * Returns eKomi Widget html
	 *  
	 * @param  array $atts 
	 * @return string       
	 */
	public static function ekomi_widget( $atts ) {

		return WC_germanized()->ekomi->get_widget( $atts );
	
	}

	/**
	 * Returns header feature shortcode
	 *  
	 * @param  array $atts    
	 * @param  string $content 
	 * @return string          
	 */
	public static function gzd_feature( $atts, $content = '' ) {

		extract( shortcode_atts( array('icon' => ''), $atts ) );
		return ( !empty( $icon ) ? '<i class="fa fa-' . $icon . '"></i> ' : '' ) . $content;
	
	}

}