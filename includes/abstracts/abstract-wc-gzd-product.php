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
class WC_GZD_Product extends WC_Product {

	/**
	 * The actual Product object (e.g. simple, variable)
	 * @var object
	 */
	private $child;

	/**
	 * Sets the actual product implementation and constructs the abstract WC_Product class.
	 *  
	 * @param object $product
	 */
	public function __construct( $product ) {
		// Post types
		$this->child = $product;
		parent::__construct( $product );
	}

	/**
	 * Redirects __get calls to WC_Product Class.
	 *  
	 * @param  string $key
	 * @return mixed     
	 */
	public function __get( $key ) {
		$value = parent::__get( $key );
		if ( $key == 'delivery_time' ) {
			$value = get_the_terms( $this->post->ID, 'product_delivery_time' );
			if ( $this->child->product_type == 'variation' )
				$value = $this->child->$key;
		}
		return $value;
	}

	public function get_mini_desc() {
		if ( $this->mini_desc )
			return apply_filters( 'the_content', $this->mini_desc );
		return false;
	}

	/**
	 * Gets a product's tax description (if is taxable)
	 *  
	 * @return mixed string if is taxable else returns false
	 */
	public function get_tax_info() {
		$_tax  = new WC_Tax();
		if ( $this->is_taxable() ) {
			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
			$tax_rates  = $_tax->get_rates( $this->get_tax_class() );
			if ( !empty( $tax_rates ) ) {
				$tax_rates = array_values( $tax_rates );
				return ( $tax_display_mode == 'incl' ? sprintf( __( 'incl. %s VAT', 'woocommerce-germanized' ), ( (int) $tax_rates[0][ 'rate' ] ) . '%' ) : sprintf( __( 'excl. %s VAT', 'woocommerce-germanized' ), ( (int) $tax_rates[0][ 'rate' ] ) . '%' ) );
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
		return $this->get_price_including_tax( $qty, $price );
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
		return ( $price == '' ) ? '' : $this->get_price_excluding_tax( $qty, $price );
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
		$price_html 		   = ( $this->is_on_unit_sale() ? $this->get_price_html_from_to( $display_regular_price, $display_sale_price ) : wc_price( $display_price ) );
		return ( $this->has_unit() ) ? $price_html . $this->get_price_suffix() . apply_filters( 'wc_gzd_unit_price_seperator', ' / ' ) . $this->get_unit_base() : '';
	}

	/**
	 * Returns current product's delivery time term. If none has been set and a default delivery time has been set, returns that instead.
	 *  
	 * @return object
	 */
	public function get_delivery_time_term() {
		$delivery_time = $this->delivery_time;
		if ( empty( $delivery_time ) && get_option( 'woocommerce_gzd_default_delivery_time' ) )
			$delivery_time = array( get_term_by( 'id', get_option( 'woocommerce_gzd_default_delivery_time' ), 'product_delivery_time' ) );
		else {
			array_values( $delivery_time );
			$delivery_time = $delivery_time[0];
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