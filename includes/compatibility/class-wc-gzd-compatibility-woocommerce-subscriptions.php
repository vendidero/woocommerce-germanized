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
class WC_GZD_Compatibility_Woocommerce_Subscriptions extends WC_GZD_Compatibility {

	public function __construct() {
		
		parent::__construct( 
			'WooCommerce Subscriptions', 
			'woocommerce-subscriptions/woocommerce-subscriptions.php', 
			array(
				'version' => get_option( 'woocommerce_subscriptions_active_version', '1.0.0' ),
				'requires_at_least' => '2.0',
			)
		);
		
	}

	public function load() {
		add_filter( 'wcs_cart_totals_order_total_html', array( $this, 'set_tax_notice' ), 50, 2 );
	}

	public function set_tax_notice( $price, $cart ) {
		// Tax for inclusive prices
		if ( 'yes' == get_option( 'woocommerce_calc_taxes' ) && 'incl' == $cart->tax_display_cart ) {

			ob_start();
			
			$tax_array = array();
			
			if ( 'itemized' == get_option( 'woocommerce_tax_total_display' ) ) {
				foreach ( $cart->get_tax_totals() as $code => $tax ) {
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
				$tax_array[] = array( 'tax' => $base_rate, 'contains' => array( $base_rate ), 'amount' => $cart->get_taxes_total( true, true ) );
			}

			?>

			<?php echo $price; ?>
			</td></tr>

			<?php

			if ( ! empty( $tax_array ) ) {
				
				$count = 0;
				foreach ( $tax_array as $tax ) {

					$count++;
					$label = ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ? sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), wc_gzd_format_tax_rate_percentage( $tax[ 'tax' ]->rate ) ) : __( 'incl. VAT', 'woocommerce-germanized' ) );
					?>

					<tr class="order-tax">
						<th rowspan="1"><?php echo $label; ?></th>
						<td data-title="<?php echo esc_attr( $label ); ?>"><?php echo wc_price( $tax[ 'amount' ] ); ?>
					
					<?php if ( sizeof( $tax_array ) != $count ) : ?>
						</td></tr>
					<?php endif; ?>

					<?php
				}
			}

			return ob_get_clean();
		
		} else {
			return $price;
		}
	}

}