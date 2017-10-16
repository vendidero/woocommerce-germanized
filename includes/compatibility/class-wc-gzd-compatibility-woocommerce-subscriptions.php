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

		/**
         * Direct Debit Gateway
         */
		add_filter( 'woocommerce_gzd_direct_debit_mandate_type_order_text', array( $this, 'direct_debit_mandate_type_order_text' ), 10, 2 );
		add_filter( 'woocommerce_gzd_direct_debit_mandate_type_text', array( $this, 'direct_debit_mandate_type_cart_text' ), 10, 1 );
		add_filter( 'wcs_renewal_order_meta', array( $this, 'direct_debit_subscription_order_meta' ), 10, 3 );
		add_filter( 'wcs_subscription_meta', array( $this, 'direct_debit_subscription_order_meta' ), 10, 3 );
		add_action( 'woocommerce_gzd_direct_debit_order_data_updated', array( $this, 'update_direct_debit_order' ), 10, 3 );
		add_action( 'woocommerce_scheduled_subscription_payment_direct-debit', array( $this, 'direct_debit_subscription_payment' ), 10, 2 );
		add_filter( 'woocommerce_gzd_direct_debit_export_query_args', array( $this, 'exporter_query' ), 10, 2 );
		add_action( 'woocommerce_process_shop_subscription_meta', array( $this, 'save_direct_debit_data' ), 10, 2 );
	}

	public function save_direct_debit_data( $post_id, $post ) {
	    $order = wc_get_order( $post_id );

	    if ( wc_gzd_get_crud_data( $order, 'payment_method' ) !== 'direct-debit' )
	        return;

		$gateways = WC()->payment_gateways()->payment_gateways();

		if ( ! empty( $gateways[ 'direct-debit' ] ) ) {
		    $gateways[ 'direct-debit' ]->save_debit_fields( $order );

		    if ( wc_gzd_get_dependencies()->woocommerce_version_supports_crud() ) {
			    $order->save();
		    }
        }
    }

	public function exporter_query( $query_args, $args ) {

		$query_args[ 'post_type' ] = array( 'shop_order', 'shop_subscription' );
		$query_args[ 'post_status' ] = array_merge( $query_args[ 'post_status' ], array( 'wc-active' ) );

	    if ( isset( $query_args[ 'p' ] ) ) {
	        $id = $query_args[ 'p' ];

	        if ( wcs_order_contains_subscription( $id, array( 'parent' ) ) ) {

	            $order_ids = array();
		        $subscriptions = wcs_get_subscriptions_for_order( $id );

		        if ( ! empty( $subscriptions ) ) {
			        foreach( $subscriptions as $subscription ) {
			            array_push( $order_ids, wc_gzd_get_crud_data( $subscription, 'id' ) );

			            $orders = $subscription->get_related_orders();
			            foreach( $orders as $order_id ) {
				            array_push( $order_ids, $order_id );
                        }
                    }
		        }

		        unset( $query_args[ 'p' ] );
		        $query_args[ 'post__in' ] = $order_ids;
            }
        }

        return $query_args;
    }

	public function direct_debit_subscription_payment( $total, $order ) {
        if ( $mail = WC_germanized()->emails->get_email_instance_by_id( 'customer_sepa_direct_debit_mandate' ) )
            $mail->trigger( $order );

        WC_Subscriptions_Manager::process_subscription_payments_on_order( $order );
    }

	public function direct_debit_subscription_order_meta( $meta, $to_order, $from_order ) {
	    foreach( $meta as $key => $meta_data ) {
	        if ( '_direct_debit_mandate_type' === $meta_data[ 'meta_key' ] ) {
		        $meta[ $key ][ 'meta_value' ] = Digitick\Sepa\PaymentInformation::S_RECURRING;
            }
        }
        return $meta;
    }

	public function direct_debit_mandate_type_order_text( $text, $order ) {
	    if ( wcs_order_contains_subscription( wc_gzd_get_crud_data( $order, 'id' ), array( 'parent', 'renewal' ) ) ) {
	        return __( 'recurring payments', 'woocommerce-germanized' );
        }
        return $text;
    }

	public function direct_debit_mandate_type_cart_text( $text ) {
		if ( WC_Subscriptions_Cart::cart_contains_subscription() ) {
		    return __( 'recurring payments', 'woocommerce-germanized' );
        }
		return $text;
	}

	public function update_direct_debit_order( $order, $user_id, $gateway ) {
	    if ( WC_Subscriptions_Cart::cart_contains_subscription() ) {
		    $order = wc_gzd_set_crud_data( $order, '_direct_debit_mandate_type', Digitick\Sepa\PaymentInformation::S_FIRST );
        }
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
				$base_rate = array_values( WC_Tax::get_base_tax_rates() );
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