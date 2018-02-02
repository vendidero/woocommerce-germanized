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
	protected $child;

	protected $gzd_variation_level_meta = array(
		'unit_price' 		 		=> '',
		'unit_price_regular' 		=> '',
		'unit_price_sale' 	 		=> '',
		'unit_price_auto'	 	   	=> '',
		'mini_desc' 		 		=> '',
		'service'					=> '',
		'gzd_product' 		 		=> NULL,
	);

	protected $gzd_variation_inherited_meta_data = array(
		'unit',
		'unit_base',
		'unit_product',
		'sale_price_label',
		'sale_price_regular_label',
		'free_shipping',
		'differential_taxation'
	);

	/**
	 * Construct new WC_GZD_Product
	 *  
	 * @param WC_Product $product 
	 */
	public function __construct( $product ) {
		
		if ( is_numeric( $product ) )
			$product = WC()->product_factory->get_product_standalone( get_post( $product ) );
		
		$this->child = $product;
	}

	public function get_wc_product() {
		return $this->child;
	}
 
	/**
	 * Redirects __get calls to WC_Product Class.
	 *  
	 * @param  string $key
	 * @return mixed     
	 */
	public function __get( $key ) {

		if ( $this->child->is_type( 'variation' ) && in_array( $key, array_keys( $this->gzd_variation_level_meta ) ) ) {
			
			$value = wc_gzd_get_crud_data( $this->child, $key );

			if ( '' === $value )
				$value = $this->gzd_variation_level_meta[ $key ];
		
		} elseif ( $this->child->is_type( 'variation' ) && in_array( $key, $this->gzd_variation_inherited_meta_data ) ) {
			
			$value = wc_gzd_get_crud_data( $this->child, $key ) ? wc_gzd_get_crud_data( $this->child, $key ) : '';

			// Handle meta data keys which can be empty at variation level to cause inheritance
			if ( ! $value || '' === $value ) {

				$parent = wc_get_product( wc_gzd_get_crud_data( $this->child, 'parent' ) );
				// Check if parent exists
				if ( $parent )
					$value = wc_gzd_get_crud_data( $parent, $key );
			}
		
		} elseif ( $key == 'delivery_time' ) {
			
			$value = $this->get_delivery_time();
		
		} else {
			
			if ( strpos( '_', $key ) !== true )
				$key = '_' . $key;

			$value = wc_gzd_get_crud_data( $this->child, $key );
		}

		return $value;
	}

	/**
	 * Redirect issets to WC_Product Class
	 *  
	 * @param  string  $key 
	 * @return boolean      
	 */
	public function __isset( $key ) {
		if ( $this->child->is_type( 'variation' ) && in_array( $key, array_keys( $this->gzd_variation_level_meta ) ) ) {
			return metadata_exists( 'post', wc_gzd_get_crud_data( $this->child, 'id' ), '_' . $key );
		} elseif ( $this->child->is_type( 'variation' ) && in_array( $key, array_keys( $this->gzd_variation_inherited_meta_data ) ) ) {
			return metadata_exists( 'post', wc_gzd_get_crud_data( $this->child, 'id' ), '_' . $key ) || metadata_exists( 'post', wc_gzd_get_crud_data( $this->child, 'parent' ), '_' . $key );
		} else {
			return metadata_exists( 'post', wc_gzd_get_crud_data( $this->child, 'id' ), '_' . $key );
		}
	}

	public function __call( $method, $args ) {
		if ( method_exists( $this->child, $method ) )
			return call_user_func_array( array( $this->child, $method ), $args );
		return false;
	}

	public function recalculate_unit_price( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'regular_price' => $this->get_regular_price(),
			'sale_price' => $this->get_sale_price(),
			'price' => $this->get_price(),
		) );

		$base = $this->unit_base;
		$product_base = $base;

		if ( empty( $this->unit_product ) ) {
			// Set base multiplicator to 1
			$base = 1;
		} else {
			$product_base = $this->unit_product;
		}

		// Do not recalculate if unit base and/or product is empty
		if ( 0 == $product_base || 0 == $base )
			return;

		$this->unit_price_regular = wc_format_decimal( ( $args[ 'regular_price' ] / $product_base ) * $base, wc_get_price_decimals() );
		$this->unit_price_sale = '';

		if ( ! empty( $args[ 'sale_price' ] ) ) {
			$this->unit_price_sale = wc_format_decimal( ( $args[ 'sale_price' ] / $product_base ) * $base, wc_get_price_decimals() );
		}

		$this->unit_price = wc_format_decimal( ( $args[ 'price' ] / $product_base ) * $base, wc_get_price_decimals() );

		do_action( 'woocommerce_gzd_recalculated_unit_price', $this );
	}

	/**
	 * Get a product's cart description
	 * 
	 * @return boolean|string
	 */
	public function get_mini_desc() {

	    $mini_desc = apply_filters( 'woocommerce_gzd_product_cart_description', $this->mini_desc, $this );

		if ( $mini_desc && ! empty( $mini_desc ) )
			return wpautop( htmlspecialchars_decode( $mini_desc ) );

		return false;
	}

	public function is_service() {
		if ( ! empty( $this->service ) && 'yes' === $this->service )
			return true;

		return false;
	}

	public function is_differential_taxed() {
		if ( ! empty( $this->differential_taxation ) && 'yes' === $this->differential_taxation )
			return true;

		return false;
	}

	/**
	 * Checks whether current product applies for a virtual VAT exception (downloadable or virtual)
	 *  
	 * @return boolean
	 */
	public function is_virtual_vat_exception() {
		return apply_filters( 'woocommerce_gzd_product_virtual_vat_exception', ( ( get_option( 'woocommerce_gzd_enable_virtual_vat' ) === 'yes' ) && ( $this->is_downloadable() || $this->is_virtual() ) ? true : false ), $this );
	}

	public function add_labels_to_price_html( $price_html ) {

	    $org_price_html = $price_html;

		if ( ! $this->child->is_on_sale() )
			return $price_html;

		$sale_label = $this->get_sale_price_label();
		$sale_regular_label = $this->get_sale_price_regular_label();

		// Do not manipulate if there is no label to be added.
		if ( empty( $sale_label ) && empty( $sale_regular_label ) ) {
            return $price_html;
        }
		
		preg_match( "/<del>(.*?)<\\/del>/si", $price_html, $match_regular );
		preg_match( "/<ins>(.*?)<\\/ins>/si", $price_html, $match_sale );
		preg_match( "/<small .*>(.*?)<\\/small>/si", $price_html, $match_suffix );

		if ( empty( $match_sale ) || empty( $match_regular ) ) {
            return $price_html;
        }

		$new_price_regular = $match_regular[0];
		$new_price_sale = $match_sale[0];
		$new_price_suffix = ( empty( $match_suffix ) ? '' : ' ' . $match_suffix[0] );

		if ( ! empty( $sale_label ) && isset( $match_regular[1] ) )
			$new_price_regular = '<span class="wc-gzd-sale-price-label">' . $sale_label . '</span> ' . $match_regular[0];

		if ( ! empty( $sale_regular_label ) && isset( $match_sale[1] ) )
			$new_price_sale = '<span class="wc-gzd-sale-price-label wc-gzd-sale-price-regular-label">' . $sale_regular_label . '</span> ' . $match_sale[0];

		return apply_filters( 'woocommerce_gzd_product_sale_price_with_labels_html', $new_price_regular . ' ' . $new_price_sale . $new_price_suffix, $org_price_html, $this );
	}

	public function get_price_html_from_to( $from, $to, $show_labels = true ) {

		$sale_label = ( $show_labels ? $this->get_sale_price_label() : '' );
		$sale_regular_label = ( $show_labels ? $this->get_sale_price_regular_label() : '' );

		$price = ( ! empty( $sale_label ) ? '<span class="wc-gzd-sale-price-label">' . $sale_label . '</span>' : '' ) . ' <del>' . ( ( is_numeric( $from ) ) ? wc_price( $from ) : $from ) . '</del> ' . ( ! empty( $sale_regular_label ) ? '<span class="wc-gzd-sale-price-label wc-gzd-sale-price-regular-label">' . $sale_regular_label . '</span> ' : '' ) . '<ins>' . ( ( is_numeric( $to ) ) ? wc_price( $to ) : $to ) . '</ins>';

		return apply_filters( 'woocommerce_germanized_get_price_html_from_to', $price, $from, $to, $this );
	}

	/**
	 * Gets a product's tax description (if is taxable)
	 *  
	 * @return mixed string if is taxable else returns false
	 */
	public function get_tax_info() {
		
		$tax_notice = false;
		$is_vat_exempt = ( ! empty( WC()->customer ) ? WC()->customer->is_vat_exempt() : false );

		if ( $this->is_taxable() || $this->is_differential_taxed() ) {
		
			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
			$tax_rates  = WC_Tax::get_rates( $this->get_tax_class() );

			if ( ! empty( $tax_rates ) ) {
		
				$tax_rates = array_values( $tax_rates );

				// If is variable or is virtual vat exception dont show exact tax rate
				if ( $this->is_virtual_vat_exception() || $this->is_type( 'variable' ) || get_option( 'woocommerce_gzd_hide_tax_rate_shop' ) === 'yes' )
					$tax_notice = ( $tax_display_mode == 'incl' && ! $is_vat_exempt ? __( 'incl. VAT', 'woocommerce-germanized' ) : __( 'excl. VAT', 'woocommerce-germanized' ) );
				else
					$tax_notice = ( $tax_display_mode == 'incl' && ! $is_vat_exempt ? sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), ( wc_gzd_format_tax_rate_percentage( $tax_rates[0][ 'rate' ] ) ) ) : sprintf( __( 'excl. %s%% VAT', 'woocommerce-germanized' ), ( wc_gzd_format_tax_rate_percentage( $tax_rates[0][ 'rate' ] ) ) ) );
			}

			if ( $this->is_differential_taxed() ) {
				if ( get_option( 'woocommerce_gzd_differential_taxation_show_notice' ) === 'yes' ) {
					$tax_notice = wc_gzd_get_differential_taxation_notice_text();
				} else {
					$tax_notice = __( 'incl. VAT', 'woocommerce-germanized' );
				}
			}
		}
		
		return apply_filters( 'woocommerce_gzd_product_tax_info', $tax_notice, $this );
	}

	/**
	 * Checks whether current Product has a unit price
	 *  
	 * @return boolean
	 */
	public function has_unit() {
		if ( $this->unit && $this->unit_price_regular && $this->unit_base )
			return true;
		return false;
	}

	/**
	 * Returns unit base html
	 *  
	 * @return string
	 */
	public function get_unit_base() {
		return ( $this->unit_base ) ? ( $this->unit_base != apply_filters( 'woocommerce_gzd_unit_base_hide_amount', 1 ) ? '<span class="unit-base">' . $this->unit_base . '</span>' . apply_filters( 'wc_gzd_unit_price_base_seperator', ' ' ) : '' ) . '<span class="unit">' . $this->get_unit() . '</span>' : '';
	}

	public function get_unit_term() {
		$unit = $this->unit;

		if ( ! empty( $unit ) ) {
			return WC_germanized()->units->get_unit_term( $unit );
		}

		return false;
	}

	/**
	 * Returns unit
	 *  
	 * @return string
	 */
	public function get_unit() {
		$unit = $this->unit;
		return WC_germanized()->units->$unit;
	}

	public function get_sale_price_label_term() {
		$label = $this->sale_price_label;

		if ( ! empty( $label ) ) {
			return WC_germanized()->price_labels->get_label_term( $label );
		}

		return false;
 	}

	/**
	 * Returns sale price label
	 *  
	 * @return string
	 */
	public function get_sale_price_label() {

		$default = get_option( 'woocommerce_gzd_default_sale_price_label', '' );
		$label = ( ! empty( $this->sale_price_label ) ? $this->sale_price_label : $default );

		return ( ! empty( $label ) ? WC_germanized()->price_labels->$label : '' );
	}

	public function get_sale_price_regular_label_term() {
		$label = $this->sale_price_regular_label;

		if ( ! empty( $label ) ) {
			return WC_germanized()->price_labels->get_label_term( $label );
		}

		return false;
	}

	/**
	 * Returns sale price regular label
	 *  
	 * @return string
	 */
	public function get_sale_price_regular_label() {

		$default = get_option( 'woocommerce_gzd_default_sale_price_regular_label', '' );
		$label = ( ! empty( $this->sale_price_regular_label ) ? $this->sale_price_regular_label : $default );

		return ( ! empty( $label ) ? WC_germanized()->price_labels->$label : '' );
	}

	/**
	 * Returns unit regular price
	 *  
	 * @return string the regular price
	 */
	public function get_unit_regular_price() {
		return apply_filters( 'woocommerce_gzd_get_unit_regular_price', $this->unit_price_regular, $this );
	}

	/**
	 * Returns unit sale price
	 *  
	 * @return string the sale price 
	 */
	public function get_unit_sale_price() {
		return apply_filters( 'woocommerce_gzd_get_unit_sale_price', $this->unit_price_sale, $this );
	}

	/**
	 * Returns the unit price (if is sale then return sale price)
	 *  
	 * @param  integer $qty   
	 * @param  string  $price 
	 * @return string  formatted unit price
	 */
	public function get_unit_price( $qty = 1, $price = '' ) {

		do_action( 'woocommerce_gzd_before_get_unit_price', $this, $price, $qty );

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
		$price = ( $price == '' ) ? $this->unit_price : $price;
		return apply_filters( 'woocommerce_gzd_unit_price_including_tax', ( $price == '' ) ? '' : wc_gzd_get_price_including_tax( $this->child, array( 'price' => $price, 'qty' => $qty ) ), $price, $qty, $this );
	}

	/**
	 * Returns unit price excluding tax
	 *  
	 * @param  integer $qty   
	 * @param  string  $price 
	 * @return string  unit price excluding tax
	 */
	public function get_unit_price_excluding_tax( $qty = 1, $price = '' ) {
		$price = ( $price == '' ) ? $this->unit_price : $price;
		return apply_filters( 'woocommerce_gzd_unit_price_excluding_tax', ( $price == '' ) ? '' : wc_gzd_get_price_excluding_tax( $this->child, array( 'price' => $price, 'qty' => $qty ) ), $price, $qty, $this );
	}

	/**
	 * Checks whether unit price is on sale
	 *  
	 * @return boolean 
	 */
	public function is_on_unit_sale() {
		return apply_filters( 'woocommerce_gzd_product_is_on_unit_sale', ( $this->get_unit_sale_price() !== $this->get_unit_regular_price() && $this->get_unit_sale_price() == $this->get_unit_price() ), $this );
	}

	/**
	 * Returns unit price html output
	 *  
	 * @return string 
	 */
	public function get_unit_html( $show_sale = true ) {

		if ( apply_filters( 'woocommerce_gzd_hide_unit_text', false, $this ) )
			return apply_filters( 'woocommerce_germanized_disabled_unit_text', '', $this );

		$html = '';

		if ( $this->has_unit() ) {

			do_action( 'woocommerce_gzd_before_get_unit_price_html', $this );

			$display_price = $this->get_unit_price();
			$display_regular_price = $this->get_unit_price( 1, $this->get_unit_regular_price() );
			$display_sale_price = $this->get_unit_price( 1, $this->get_unit_sale_price() );

			$price_html = ( ( $this->is_on_unit_sale() && $show_sale ) ? $this->get_price_html_from_to( $display_regular_price, $display_sale_price, false ) : wc_price( $display_price ) );
			$text       = get_option( 'woocommerce_gzd_unit_price_text' );

			if ( strpos( $text, '{price}' ) !== false ) {
				$html = str_replace( '{price}', $price_html . apply_filters( 'wc_gzd_unit_price_seperator', ' / ' ) . $this->get_unit_base(), $text );
			} else {
				$html = str_replace( array( '{base_price}', '{unit}', '{base}' ), array(
					$price_html,
					'<span class="unit">' . $this->get_unit() . '</span>',
					( $this->unit_base != apply_filters( 'woocommerce_gzd_unit_base_hide_amount', 1 ) ? '<span class="unit-base">' . $this->unit_base . '</span>' : '' )
				), $text );
			}
		}
		
		return apply_filters( 'woocommerce_gzd_unit_price_html', $html, $this );
	}

	public function is_unit_price_calculated_automatically() {
		return $this->unit_price_auto === 'yes';
	}

	public function get_unit_products() {
		return $this->unit_product;
	}

	public function has_product_units() {
		$products = $this->get_unit_products();
		return ( $products && ! empty( $products ) && $this->get_unit() );
	}

	/**
	 * Formats the amount of product units
	 *  
	 * @return string 
	 */
	public function get_product_units_html() {

		if ( apply_filters( 'woocommerce_gzd_hide_product_units_text', false, $this ) )
			return apply_filters( 'woocommerce_germanized_disabled_product_units_text', '', $this );

		$html = '';
		$text = get_option( 'woocommerce_gzd_product_units_text' );

		if ( $this->has_product_units() )
			$html = str_replace( array( '{product_units}', '{unit}', '{unit_price}' ), array( str_replace( '.', ',', $this->get_unit_products() ), $this->get_unit(), $this->get_unit_html() ), $text );

		return apply_filters( 'woocommerce_gzd_product_units_html', $html, $this );

	}

	/**
	 * Returns the current products delivery time term without falling back to default term
	 *  
	 * @return bool|object false returns false if term does not exist otherwise returns term object
	 */
	public function get_delivery_time() {

		$terms = get_the_terms( wc_gzd_get_crud_data( $this->child, 'id' ), 'product_delivery_time' );
		
		if ( empty( $terms ) && $this->child->is_type( 'variation' ) ) {
			
			$parent_terms = get_the_terms( wc_gzd_get_crud_data( $this->child, 'parent' ), 'product_delivery_time' );

			if ( ! empty( $parent_terms ) && ! is_wp_error( $parent_terms ) )
				$terms = $parent_terms;
		}

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

		if ( empty( $delivery_time ) && get_option( 'woocommerce_gzd_default_delivery_time' ) && ! $this->is_downloadable() ) {
			
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

		$html = '';
		
		if ( apply_filters( 'woocommerce_germanized_hide_delivery_time_text', false, $this ) )
			return apply_filters( 'woocommerce_germanized_disabled_delivery_time_text', '', $this );

		if ( $this->get_delivery_time_term() ) {
			$html = $this->get_delivery_time_term()->name;
		} else {
			$html = apply_filters( 'woocommerce_germanized_empty_delivery_time_text', '', $this );
		}
		
		return ( ! empty( $html ) ? apply_filters( 'woocommerce_germanized_delivery_time_html', str_replace( '{delivery_time}', $html, get_option( 'woocommerce_gzd_delivery_time_text' ) ), $html, $this ) : '' );
	}

	public function has_free_shipping() {
		return ( apply_filters( 'woocommerce_germanized_product_has_free_shipping', ( $this->free_shipping === 'yes' ? true : false ), $this ) );
	}

	/**
	 * Returns the shipping costs notice html output
	 *  
	 * @return string 
	 */
	public function get_shipping_costs_html() {
		
		if ( apply_filters( 'woocommerce_germanized_hide_shipping_costs_text', false, $this ) )
			return apply_filters( 'woocommerce_germanized_disabled_shipping_text', '', $this );
		
		return wc_gzd_get_shipping_costs_text( $this );
	}

}
?>