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

function wc_gzd_cart_product_differential_taxation_mark( $title, $cart_item, $cart_item_key = '' ) {

	$product = false;
	$product_mark = '';

	if ( isset( $cart_item[ 'data' ] ) ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item[ 'data' ], $cart_item, $cart_item_key );
	} elseif ( isset( $cart_item[ 'product_id' ] ) ) {
		$product = wc_get_product( ! empty( $cart_item[ 'variation_id' ] ) ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] );
	}

	if ( $product ) {
		if ( wc_gzd_get_gzd_product( $product )->is_differential_taxed() )
			$product_mark = apply_filters( 'woocommerce_gzd_differential_taxation_cart_item_mark', ' **' );
	}

	if ( ! empty( $product_mark ) )
		$title .= '<span class="wc-gzd-product-differential-taxation-mark">' . $product_mark . '</span>';

	return $title;
}

/**
 * Appends product item desc live data (while checkout) or order meta to product name
 *  
 * @param  string $title    
 * @param  array $cart_item 
 * @return string
 */
function wc_gzd_cart_product_item_desc( $title, $cart_item, $cart_item_key = '' ) {
	
	$product_desc = "";
	
	if ( isset( $cart_item[ 'data' ] ) ) {
	
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item[ 'data' ], $cart_item, $cart_item_key );
	
		if ( wc_gzd_get_gzd_product( $product )->get_mini_desc() )
			$product_desc = wc_gzd_get_gzd_product( $product )->get_mini_desc();
	
	} elseif ( isset( $cart_item[ 'item_desc' ] ) ) {

		$product_desc = $cart_item[ 'item_desc' ];
	
	} elseif ( isset( $cart_item[ 'product_id' ] ) ) {

		$product = wc_get_product( ! empty( $cart_item[ 'variation_id' ] ) ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] );

		if ( $product && wc_gzd_get_gzd_product( $product )->get_mini_desc() )
			$product_desc = wc_gzd_get_gzd_product( $product )->get_mini_desc();

	}
	
	if ( ! empty( $product_desc ) )
		$title .= '<div class="wc-gzd-item-desc item-desc">' . do_shortcode( $product_desc ) . '</div>';
	
	return $title;
}

/**
 * Appends delivery time live data (while checkout) or order meta to product name
 *  
 * @param  string $title    
 * @param  array $cart_item 
 * @return string
 */
function wc_gzd_cart_product_delivery_time( $title, $cart_item, $cart_item_key = '' ) {
	
	$delivery_time = "";
	
	if ( isset( $cart_item[ 'data' ] ) ) {
	
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item[ 'data' ], $cart_item, $cart_item_key );
	
		if ( wc_gzd_get_gzd_product( $product )->get_delivery_time_term() )
			$delivery_time = wc_gzd_get_gzd_product( $product )->get_delivery_time_html();
	
	} elseif ( isset( $cart_item[ 'delivery_time' ] ) ) {

		$delivery_time = $cart_item[ 'delivery_time' ];
	
	} elseif ( isset( $cart_item[ 'product_id' ] ) ) {

		$product = wc_get_product( ! empty( $cart_item[ 'variation_id' ] ) ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] );

		if ( $product && wc_gzd_get_gzd_product( $product )->get_delivery_time_term() )
			$delivery_time = wc_gzd_get_gzd_product( $product )->get_delivery_time_html();

	}
	 
	if ( ! empty( $delivery_time ) )
		$title .= '<p class="delivery-time-info">' . $delivery_time . '</p>';
	
	return $title;
}

/**
 * Appends unit price to product price live data (while checkout) or order meta to product price
 *  
 * @param  string $price     
 * @param  array $cart_item 
 * @return string            
 */
function wc_gzd_cart_product_unit_price( $price, $cart_item, $cart_item_key = '' ) {
	
	$unit_price = "";

	if ( isset( $cart_item[ 'data' ] ) ) {
	
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item[ 'data' ], $cart_item, $cart_item_key );
	
		if ( wc_gzd_get_gzd_product( $product )->has_unit() ) {
			$unit_price = wc_gzd_get_gzd_product( $product )->get_unit_html( false );
		}
	
	} elseif ( isset( $cart_item[ 'unit_price' ] ) ) {

		$unit_price = $cart_item[ 'unit_price' ];

	
	} elseif ( isset( $cart_item[ 'product_id' ] ) ) {

		$product = wc_get_product( ! empty( $cart_item[ 'variation_id' ] ) ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] );

		if ( $product && wc_gzd_get_gzd_product( $product )->has_unit() )
			$unit_price = wc_gzd_get_gzd_product( $product )->get_unit_html( false );

	}

	if ( ! empty( $unit_price ) )
		$price .= ' <span class="unit-price unit-price-cart">' . $unit_price . '</span>';
	
	return $price;
}

/**
 * Appends product units live data (while checkout) or order meta to product name
 *  
 * @param  string $title    
 * @param  array $cart_item 
 * @return string
 */
function wc_gzd_cart_product_units( $title, $cart_item, $cart_item_key = '' ) {
	
	$units = "";
	
	if ( isset( $cart_item[ 'data' ] ) ) {
	
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item[ 'data' ], $cart_item, $cart_item_key );
	
		if ( wc_gzd_get_gzd_product( $product )->has_product_units() )
			$units = wc_gzd_get_gzd_product( $product )->get_product_units_html();
	
	} elseif ( isset( $cart_item[ 'units' ] ) ) {

		$units = $cart_item[ 'units' ];
	
	} elseif ( isset( $cart_item[ 'product_id' ] ) ) {

		$product = wc_get_product( ! empty( $cart_item[ 'variation_id' ] ) ? $cart_item[ 'variation_id' ] : $cart_item[ 'product_id' ] );

		if ( $product && wc_gzd_get_gzd_product( $product )->has_product_units() )
			$units = wc_gzd_get_gzd_product( $product )->get_product_units_html();

	}
	
	if ( ! empty( $units ) )
		$title .= '<p class="units-info">' . $units . '</p>';
	
	return $title;
}

/**
 * Calculates tax share for shipping/fees
 *  
 * @param  string $type 
 * @return array       
 */
function wc_gzd_get_cart_tax_share( $type = 'shipping' ) {
	
	$cart = WC()->cart->get_cart();
	$tax_shares = array();
	$item_totals = 0;
	
	// Get tax classes and tax amounts
	if ( ! empty( $cart ) ) {
		
		foreach ( $cart as $key => $item ) {
			
			$_product = apply_filters( 'woocommerce_cart_item_product', $item[ 'data' ], $item, $key );
			
			// Dont calculate share if is shipping and product is virtual or vat exception
			if ( $type == 'shipping' && $_product->is_virtual() || ( wc_gzd_get_gzd_product( $_product )->is_virtual_vat_exception() && $type == 'shipping' ) )
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

		$default = ( $item_totals == 0 ? 1 / sizeof( $tax_shares ) : 0 );

		foreach ( $tax_shares as $key => $class )
			$tax_shares[ $key ][ 'share' ] = ( $item_totals > 0 ? $class[ 'total' ] / $item_totals : $default );

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

function wc_gzd_cart_remove_shipping_taxes( $taxes, $cart ) {
    return $cart->taxes;
}

function wc_gzd_get_cart_total_taxes( $include_shipping_taxes = true ) {

	$tax_array = array();

	// If prices are tax inclusive, show taxes here
	if ( get_option( 'woocommerce_calc_taxes' ) === 'yes' && WC()->cart->tax_display_cart === 'incl' ) {

		if ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ) {

		    if ( ! $include_shipping_taxes ) {
			    add_filter( 'woocommerce_cart_get_taxes', 'wc_gzd_cart_remove_shipping_taxes', 10, 2 );
            }

		    $taxes = WC()->cart->get_tax_totals();

			if ( ! $include_shipping_taxes ) {
				remove_filter( 'woocommerce_cart_get_taxes', 'wc_gzd_cart_remove_shipping_taxes', 10, 2 );
			}

		    foreach ( $taxes as $code => $tax ) {

			    $rate = wc_gzd_get_tax_rate( $tax->tax_rate_id );

			    if ( ! $rate ) {
					continue;
				}

				if ( ! empty( $rate ) && isset( $rate->tax_rate ) ) {
					$tax->rate = $rate->tax_rate;
				}

				if ( ! isset( $tax_array[ $tax->rate ] ) ) {
					$tax_array[ $tax->rate ] = array(
					        'tax'      => $tax,
                            'amount'   => $tax->amount,
                            'contains' => array( $tax )
					);
				} else {
					array_push( $tax_array[ $tax->rate ]['contains'], $tax );
					$tax_array[ $tax->rate ]['amount'] += $tax->amount;
				}
			}
		} else {
			$base_rate       = array_values( WC_Tax::get_base_tax_rates() );
			$base_rate       = (object) $base_rate[0];
			$base_rate->rate = $base_rate->rate;
			$tax_array[]     = array( 'tax'      => $base_rate,
			                          'contains' => array( $base_rate ),
			                          'amount'   => WC()->cart->get_taxes_total( true, true )
			);
		}
	}

	return $tax_array;
}

/**
 * Get order total tax html.
 *  
 * @return void
 */
function wc_gzd_cart_totals_order_total_tax_html() {

    foreach ( wc_gzd_get_cart_total_taxes() as $tax ) :

        $label = wc_gzd_get_tax_rate_label( $tax[ 'tax' ]->rate );

    ?>
        <tr class="order-tax">
            <th><?php echo $label; ?></th>
            <td data-title="<?php echo esc_attr( $label ); ?>"><?php echo wc_price( $tax[ 'amount' ] ); ?></td>
        </tr>

    <?php endforeach;
}

function wc_gzd_get_legal_text( $text = '' ) {

    $plain_text = ( $text == '' ? apply_filters( 'woocommerce_gzd_legal_text', get_option( 'woocommerce_gzd_checkout_legal_text' ) ) : $text );

    if ( ! empty( $plain_text ) ) {
		$plain_text = str_replace( 
			array( '{term_link}', '{data_security_link}', '{revocation_link}', '{/term_link}', '{/data_security_link}', '{/revocation_link}' ), 
			array( 
				'<a href="' . esc_url( wc_gzd_get_page_permalink( 'terms' ) ) . '" target="_blank">',
				'<a href="' . esc_url( wc_gzd_get_page_permalink( 'data_security' ) ) . '" target="_blank">', 
				'<a href="' . esc_url( wc_gzd_get_page_permalink( 'revocation' ) ) . '" target="_blank">', 
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
	$text = apply_filters( 'woocommerce_gzd_legal_error_text', get_option( 'woocommerce_gzd_checkout_legal_text_error' ) );

	if ( $text )
		$plain_text = wc_gzd_get_legal_text( $text );

	return $plain_text;
}

function wc_gzd_get_legal_text_digital() {

    $plain_text = '';
    $text = apply_filters( 'woocommerce_gzd_legal_digital_text', get_option( 'woocommerce_gzd_checkout_legal_text_digital', __( 'I want immediate access to the digital content and I acknowledge that thereby I lose my right to cancel once the service has begun.', 'woocommerce-germanized' ) ) );

	if ( $text )
		$plain_text = wc_gzd_get_legal_text( $text );

	return $plain_text;
}

function wc_gzd_get_legal_text_digital_error() {

    $plain_text = '';
	$text = apply_filters( 'woocommerce_gzd_legal_digital_error_text', get_option( 'woocommerce_gzd_checkout_legal_text_digital_error', __( 'To retrieve direct access to digital content you have to agree to the loss of your right of withdrawal.', 'woocommerce-germanized' ) ) );

    if ( $text )
		$plain_text = wc_gzd_get_legal_text( $text );

	return $plain_text;
}

function wc_gzd_get_legal_text_digital_email_notice() {
	$text = apply_filters( 'woocommerce_gzd_legal_digital_email_text', get_option( 'woocommerce_gzd_order_confirmation_legal_digital_notice' ) );
	if ( $text ) {
		$text = str_replace( 
			array( '{link}', '{/link}' ), 
			array( 
				'<a href="' . esc_url( wc_gzd_get_page_permalink( 'revocation' ) ) . '" target="_blank">',
				'</a>'
			),
			$text
		);
	}

	return $text;
}

function wc_gzd_get_legal_text_service() {
	$plain_text = __( 'For services: I demand and acknowledge the immediate performance of the service before the expiration of the withdrawal period. I acknowledge that thereby I lose my right to cancel once the service has begun.', 'woocommerce-germanized' );
	
	if ( get_option( 'woocommerce_gzd_checkout_legal_text_service' ) )
		$plain_text = wc_gzd_get_legal_text( get_option( 'woocommerce_gzd_checkout_legal_text_service' ) );
	
	return apply_filters( 'woocommerce_gzd_legal_service_text', $plain_text );
}

function wc_gzd_get_legal_text_service_error() {

    $plain_text = '';
	$text = apply_filters( 'woocommerce_gzd_legal_service_error_text', get_option( 'woocommerce_gzd_checkout_legal_text_service_error', __( 'To allow the immediate performance of the services you have to agree to the loss of your right of withdrawal.', 'woocommerce-germanized' ) ) );

	if ( get_option( 'woocommerce_gzd_checkout_legal_text_service_error' ) )
		$plain_text = wc_gzd_get_legal_text( $text );
	
	return $plain_text;
}

function wc_gzd_get_legal_text_service_email_notice() {
	$text = apply_filters( 'woocommerce_gzd_legal_service_email_text', get_option( 'woocommerce_gzd_order_confirmation_legal_service_notice' ) );
	
	if ( $text ) {
		$text = str_replace( 
			array( '{link}', '{/link}' ), 
			array( 
				'<a href="' . esc_url( wc_gzd_get_page_permalink( 'revocation' ) ) . '" target="_blank">',
				'</a>'
			),
			$text
		);
	}

	return $text;
}

function wc_gzd_get_chosen_shipping_rates( $args = array() ) {

    $args = wp_parse_args( $args, array(
        'value' => '',
    ) );
	
	$packages = WC()->shipping->get_packages();
	$shipping_methods = (array) WC()->session->get( 'chosen_shipping_methods' );
	$rates = array();

	foreach ( $packages as $i => $package ) {
		if ( isset( $shipping_methods[ $i ] ) && isset( $package['rates'][ $shipping_methods[ $i ] ] ) ) {
		    if ( empty( $args[ 'value' ] ) ) {
			    array_push( $rates, $package['rates'][ $shipping_methods[ $i ] ] );
		    } else {
			    array_push( $rates, $package['rates'][ $shipping_methods[ $i ] ]->{$args['value']} );
		    }
		}
	}

	return $rates;
}

function wc_gzd_get_legal_text_parcel_delivery( $titles = array() ) {
	$plain_text = __( 'Yes, I would like to be reminded via E-mail about parcel delivery ({shipping_method_title}). Your E-mail Address will only be transferred to our parcel service provider for that particular reason.', 'woocommerce-germanized' );

	if ( get_option( 'woocommerce_gzd_checkout_legal_text_parcel_delivery' ) )
		$plain_text = get_option( 'woocommerce_gzd_checkout_legal_text_parcel_delivery' );

	if ( ! empty( $titles ) ) {
		$plain_text = str_replace( '{shipping_method_title}', implode( ', ', $titles ), $plain_text );
	}
	
	return apply_filters( 'woocommerce_gzd_legal_text_parcel_delivery', $plain_text, $titles );
}
