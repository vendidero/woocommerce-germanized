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
 * Get order total html
 *
 * @return void
 */
function wc_gzd_cart_totals_order_total_html() {
	echo '<td><strong>' . WC()->cart->get_total() . '</strong></td>';
}

/**
 * Get order total tax html
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
				if ( ! empty( $rate ) && isset( $rate->tax_rate ) )
					$tax->rate = (int) $rate->tax_rate; 
				$tax_array[] = array( 'tax' => $tax, 'amount' => $tax->formatted_amount );
			}
		} else {
			$base_rate = array_values( $_tax::get_shop_base_rate() );
			$base_rate = (object) $base_rate[0];
			$base_rate->rate = $base_rate->rate;
			$tax_array[] = array( 'tax' => $base_rate, 'amount' => wc_price( WC()->cart->get_taxes_total( true, true ) ) );
		}
		if ( ! empty( $tax_array ) ) {	
			foreach ( $tax_array as $tax ) {
				echo '
					<tr class="small tax">
						<th>' . sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), $tax[ 'tax' ]->rate ) . '</th> 
						<td>' . $tax[ 'amount' ] . '</td>
					</tr>';
			}
		}
	}
}