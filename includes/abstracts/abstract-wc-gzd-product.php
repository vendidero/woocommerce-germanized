<?php

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

/**
 * WooCommerce Germanized Abstract Product
 *
 * The WC_GZD_Product Class is used to offer additional functionality for every product type.
 *
 * @class 		WC_GZD_Product
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Product {

	/**
	 * The actual Product object (e.g. simple, variable)
	 * @var object
	 */
	private $child;
	protected static $_instance = null;

	/**
	 * Main Instance
	 *
	 * @static
	 * @return WC_GZD_Product instance
	 */
	public static function instance( $product ) {
		if ( is_null( self::$_instance ) || self::$_instance->product !== $product )
			self::$_instance = new self( $product );
		return self::$_instance;
	}

	/**
	 * Construct new WC_GZD_Product
	 *  
	 * @param WC_Product $product 
	 */
	public function __construct( $product ) {
		$this->child = $product;
	}
 
	/**
	 * Redirects __get calls to WC_Product Class.
	 *  
	 * @param  string $key
	 * @return mixed     
	 */
	public function __get( $key ) {
		if ( $this->child->$key )
			return $this->child->$key;
		else if ( $key == 'delivery_time' )
			return $this->get_delivery_time();
		return false;
	}

	/**
	 * Get a product's cart description
	 * 
	 * @return boolean|string
	 */
	public function get_mini_desc() {
		if ( $this->child->mini_desc )
			return apply_filters( 'the_content', htmlspecialchars_decode( $this->child->mini_desc ) );
		return false;
	}

	/**
	 * Checks whether current product applies for a virtual VAT exception (downloadable or virtual)
	 *  
	 * @return boolean
	 */
	public function is_virtual_vat_exception() {
		return ( ( get_option( 'woocommerce_gzd_enable_virtual_vat' ) == 'yes' ) && ( $this->child->is_downloadable() || $this->child->is_virtual() ) ? true : false );
	}

	/**
	 * Gets a product's tax description (if is taxable)
	 *  
	 * @return mixed string if is taxable else returns false
	 */
	public function get_tax_info() {
		$_tax  = new WC_Tax();
		if ( $this->child->is_taxable() ) {
			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
			$tax_rates  = $_tax->get_rates( $this->child->get_tax_class() );
			if ( ! empty( $tax_rates ) ) {
				$tax_rates = array_values( $tax_rates );
				if ( $this->is_virtual_vat_exception() )
					return ( $tax_display_mode == 'incl' ? __( 'incl. VAT', 'woocommerce-germanized' ) : __( 'excl. VAT', 'woocommerce-germanized' ) );
				return ( $tax_display_mode == 'incl' ? sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), ( wc_gzd_format_tax_rate_percentage( $tax_rates[0][ 'rate' ] ) ) ) : sprintf( __( 'excl. %s%% VAT', 'woocommerce-germanized' ), ( wc_gzd_format_tax_rate_percentage( $tax_rates[0][ 'rate' ] ) ) ) );
			}
		} 
		return false;
	}

	/**
	 * Checks whether current Product has a unit price
	 *  
	 * @return boolean
	 */
	public function has_unit() {
		if ( $this->child->unit && $this->child->unit_price_regular && $this->child->unit_base )
			return true;
		return false;
	}

	/**
	 * Returns unit base html
	 *  
	 * @return string
	 */
	public function get_unit_base() {
		return ( $this->child->unit_base ) ? '<span class="unit-base">' . $this->child->unit_base . '</span>' . apply_filters( 'wc_gzd_unit_price_base_seperator', ' ' ) . '<span class="unit">' . $this->get_unit() . '</span>' : '';
	}

	/**
	 * Returns unit
	 *  
	 * @return string
	 */
	public function get_unit() {
		$unit = $this->child->unit;
		return WC_germanized()->units->$unit;
	}

	/**
	 * Returns unit regular price
	 *  
	 * @return string the regular price
	 */
	public function get_unit_regular_price() {
		return apply_filters( 'woocommerce_gzd_get_unit_regular_price', $this->child->unit_price_regular, $this );
	}

	/**
	 * Returns unit sale price
	 *  
	 * @return string the sale price 
	 */
	public function get_unit_sale_price() {
		return apply_filters( 'woocommerce_gzd_get_unit_sale_price', $this->child->unit_price_sale, $this );
	}

	/**
	 * Returns the unit price (if is sale then return sale price)
	 *  
	 * @param  integer $qty   
	 * @param  string  $price 
	 * @return string  formatted unit price
	 */
	public function get_unit_price( $qty = 1, $price = '' ) {
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		return ( $tax_display_mode == 'incl' ) ? $this->get_unit_price_including_tax( $qty, $price ) : $this->get_unit_price_excluding_tax( $qty, $price );
	}

	/**
	 * Returns unit price including tax
	 *  
	 * @param  integer $qty   
	 * @param  string  $price 
	 * @return string  unit price including tax
	 */
	public function get_unit_price_including_tax( $qty = 1, $price = '' ) {
		$price = ( $price == '' ) ? $this->child->unit_price : $price;
		return $this->child->get_price_including_tax( $qty, $price );
	}

	/**
	 * Returns unit price excluding tax
	 *  
	 * @param  integer $qty   
	 * @param  string  $price 
	 * @return string  unit price excluding tax
	 */
	public function get_unit_price_excluding_tax( $qty = 1, $price = '' ) {
		$price = ( $price == '' ) ? $this->child->unit_price : $price;
		return ( $price == '' ) ? '' : $this->child->get_price_excluding_tax( $qty, $price );
	}

	/**
	 * Checks whether unit price is on sale
	 *  
	 * @return boolean 
	 */
	public function is_on_unit_sale() {
		return ( $this->get_unit_sale_price() ) ? true : false;
	}

	/**
	 * Returns unit price html output
	 *  
	 * @return string 
	 */
	public function get_unit_html() {
		$display_price         = $this->get_unit_price();
		$display_regular_price = $this->get_unit_price( 1, $this->get_unit_regular_price() );
		$display_sale_price    = $this->get_unit_price( 1, $this->get_unit_sale_price() );
		$price_html 		   = ( $this->is_on_unit_sale() ? $this->child->get_price_html_from_to( $display_regular_price, $display_sale_price ) : wc_price( $display_price ) );
		return ( $this->has_unit() ) ? str_replace( '{price}', $price_html . $this->child->get_price_suffix() . apply_filters( 'wc_gzd_unit_price_seperator', ' / ' ) . $this->get_unit_base(), get_option( 'woocommerce_gzd_unit_price_text' ) ) : '';
	}

	/**
	 * Returns the current products delivery time term without falling back to default term
	 *  
	 * @return bool|object false returns false if term does not exist otherwise returns term object
	 */
	public function get_delivery_time() {
		$terms = wp_get_post_terms( $this->id, 'product_delivery_time' );
		if ( is_wp_error( $terms ) || empty( $terms ) )
			return false;
		return $terms[ 0 ];
	}

	/**
	 * Returns current product's delivery time term. If none has been set and a default delivery time has been set, returns that instead.
	 *  
	 * @return object
	 */
	public function get_delivery_time_term() {
		$delivery_time = $this->delivery_time;
		if ( empty( $delivery_time ) && get_option( 'woocommerce_gzd_default_delivery_time' ) && ! $this->child->is_downloadable() ) {
			$delivery_time = array( get_term_by( 'id', get_option( 'woocommerce_gzd_default_delivery_time' ), 'product_delivery_time' ) );
			if ( is_array( $delivery_time ) ) {
				array_values( $delivery_time );
				$delivery_time = $delivery_time[0];
			}
		}
		return ( ! is_wp_error( $delivery_time ) && ! empty( $delivery_time ) ) ? $delivery_time : false;
	}

	/**
	 * Returns the delivery time html output
	 *  
	 * @return string 
	 */
	public function get_delivery_time_html() {
		return ( $this->get_delivery_time_term() ) ? apply_filters( 'woocommerce_germanized_delivery_time_html', str_replace( '{delivery_time}', $this->get_delivery_time_term()->name, get_option( 'woocommerce_gzd_delivery_time_text' ) ), $this->get_delivery_time_term()->name ) : '';
	}

	/**
	 * Returns the shipping costs notice html output
	 *  
	 * @return string 
	 */
	public function get_shipping_costs_html() {
		if ( $this->child->is_virtual() && get_option( 'woocommerce_gzd_display_shipping_costs_virtual' ) != 'yes' )
			return false;
		$find = array(
			'{link}',
			'{/link}'
		);
		$replace = array(
			'<a href="' . esc_url( get_permalink( wc_get_page_id( 'shipping_costs' ) ) ) . '" target="_blank">',
			'</a>'
		);
		return str_replace( $find, $replace, get_option( 'woocommerce_gzd_shipping_costs_text' ) );
	}
}
?>