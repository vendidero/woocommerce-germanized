<?php
/**
 * Cart Functions
 *
 * Functions for cart specific things.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wc_gzd_get_tax_rate( $tax_rate_id ) {
	global $wpdb;
	$rate = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %d LIMIT 1", $tax_rate_id ) );
	if ( ! empty( $rate ) )
		return $rate[0];
	return false; 
}

/**
 * Appends product item desc live data (while checkout) or order meta to product name
 *  
 * @param  string $title    
 * @param  array $cart_item 
 * @return string
 */
function wc_gzd_product_item_desc( $title, $cart_item ) {
	$product_desc = "";
	if ( isset( $cart_item[ 'data' ] ) ) {
		$product = $cart_item[ 'data' ];
		if ( $product->gzd_product->get_mini_desc() )
			$product_desc = $product->gzd_product->get_mini_desc();
	} else if ( isset( $cart_item[ 'item_desc' ] ) )
		$product_desc = $cart_item[ 'item_desc' ];
	if ( ! empty( $product_desc ) )
		$title .= '<div class="wc-gzd-item-desc item-desc">' . $product_desc . '</div>';
	return $title;
}

function wc_gzd_get_cart_tax_share( $type = 'shipping' ) {
	$cart = WC()->cart->get_cart();
	$tax_shares = array();
	$item_totals = 0;
	// Get tax classes and tax amounts
	if ( ! empty( $cart ) ) {
		foreach ( $cart as $key => $item ) {
			$_product = $item['data'];
			// Dont calculate share if is shipping and product is virtual or vat exception
			if ( $type == 'shipping' && $_product->is_virtual() || ( $_product->gzd_product->is_virtual_vat_exception() && $type == 'shipping' ) )
				continue;
			$class = $_product->get_tax_class();
			if ( ! isset( $tax_shares[ $class ] ) ) {
				$tax_shares[ $class ] = array();
				$tax_shares[ $class ][ 'total' ] = 0;
				$tax_shares[ $class ][ 'key' ] = '';
			}
			$tax_shares[ $class ][ 'total' ] += ( $item[ 'line_total' ] + $item[ 'line_tax' ] ); 
			$tax_shares[ $class ][ 'key' ] = key( $item[ 'line_tax_data' ][ 'total' ] );
			$item_totals += ( $item[ 'line_total' ] + $item[ 'line_tax' ] ); 
		}
	}
	if ( ! empty( $tax_shares ) ) {
		foreach ( $tax_shares as $key => $class )
			$tax_shares[ $key ][ 'share' ] = ( $item_totals > 0 ? $class[ 'total' ] / $item_totals : 0 );
	}
	return $tax_shares;
}

/**
 * Get order total html
 *
 * @return void
 */
function wc_gzd_cart_totals_order_total_html() {
	echo '<td><strong>' . WC()->cart->get_total() . '</strong></td>';
}

/**
 * Get order total tax html.
 *  
 * @return void
 */
function wc_gzd_cart_totals_order_total_tax_html() {
	$_tax = new WC_Tax();
	// If prices are tax inclusive, show taxes here
	if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' && WC()->cart->tax_display_cart == 'incl' ) {
		$tax_array = array();
		if ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ) {
			foreach ( WC()->cart->get_tax_totals() as $code => $tax ) {
				$rate = wc_gzd_get_tax_rate( $tax->tax_rate_id );
				if ( ! $rate )
					continue;
				if ( ! empty( $rate ) && isset( $rate->tax_rate ) )
					$tax->rate = $rate->tax_rate;
				if ( ! isset( $tax_array[ $tax->rate ] ) )
					$tax_array[ $tax->rate ] = array( 'tax' => $tax, 'amount' => $tax->amount, 'contains' => array( $tax ) );
				else {
					array_push( $tax_array[ $tax->rate ][ 'contains' ], $tax );
					$tax_array[ $tax->rate ][ 'amount' ] += $tax->amount;
				}
			}
		} else {
			$base_rate = array_values( WC_Tax::get_shop_base_rate() );
			$base_rate = (object) $base_rate[0];
			$base_rate->rate = $base_rate->rate;
			$tax_array[] = array( 'tax' => $base_rate, 'contains' => array( $base_rate ), 'amount' => WC()->cart->get_taxes_total( true, true ) );
		}
		if ( ! empty( $tax_array ) ) {	
			foreach ( $tax_array as $tax ) {
				echo '
					<tr class="order-tax">
						<th>' . ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ? sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), wc_gzd_format_tax_rate_percentage( $tax[ 'tax' ]->rate ) ) : __( 'incl. VAT', 'woocommerce-germanized' ) ) . '</th> 
						<td>' . wc_price( $tax[ 'amount' ] ) . '</td>
					</tr>';
			}
		}
	}
}

function wc_gzd_get_legal_text( $text = '' ) {
	$plain_text = ( $text == '' ? get_option( 'woocommerce_gzd_checkout_legal_text' ) : $text );
	if ( ! empty( $plain_text ) ) {
		$plain_text = str_replace( 
			array( '{term_link}', '{data_security_link}', '{revocation_link}', '{/term_link}', '{/data_security_link}', '{/revocation_link}' ), 
			array( 
				'<a href="' . esc_url( get_permalink( wc_get_page_id( 'terms' ) ) ) . '" target="_blank">',
				'<a href="' . esc_url( get_permalink( wc_get_page_id( 'data_security' ) ) ) . '" target="_blank">', 
				'<a href="' . esc_url( get_permalink( wc_get_page_id( 'revocation' ) ) ) . '" target="_blank">', 
				'</a>',
				'</a>',
				'</a>', 
			), 
			$plain_text 
		);
	}
	return  $plain_text;
}

function wc_gzd_get_legal_text_error() {
	$plain_text = '';
	if ( get_option( 'woocommerce_gzd_checkout_legal_text_error' ) )
		$plain_text = wc_gzd_get_legal_text( get_option( 'woocommerce_gzd_checkout_legal_text_error' ) );
	return $plain_text;
}

function wc_gzd_get_legal_text_digital() {
	$plain_text = __( 'I want immediate access to the digital content and I acknowledge that thereby I lose my right to cancel once the service has begun.', 'woocommerce-germanized' );
	if ( get_option( 'woocommerce_gzd_checkout_legal_text_digital' ) )
		$plain_text = wc_gzd_get_legal_text( get_option( 'woocommerce_gzd_checkout_legal_text_digital' ) );
	return $plain_text;
}
