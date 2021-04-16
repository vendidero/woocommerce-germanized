<?php
/**
 * Cart Functions
 *
 * Functions for cart specific things.
 *
 * @author        Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @param bool|WC_Cart $cart
 *
 * @return bool|string
 */
function wc_gzd_get_cart_tax_display_mode( $cart = false ) {
    if ( ! $cart ) {
        $cart = WC()->cart;
    }

    if ( ! $cart ) {
        return 'incl';
    }

    if ( is_callable( array( $cart, 'get_tax_price_display_mode' ) ) ) {
        return $cart->get_tax_price_display_mode();
    }

    return $cart->tax_display_cart;
}

function wc_gzd_get_tax_rate( $tax_rate_id ) {
	global $wpdb;

	$rate = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %d LIMIT 1", $tax_rate_id ) );

	if ( ! empty( $rate ) ) {
		return $rate[0];
	}

	return false;
}

function wc_gzd_cart_product_differential_taxation_mark( $title, $cart_item, $cart_item_key = '' ) {
	$product      = false;
	$product_mark = '';

	if ( is_a( $cart_item, 'WC_Order_Item_Product' ) ) {
		$product = $cart_item->get_product();
	} elseif ( isset( $cart_item['data'] ) ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
	}

	if ( $product ) {
		if ( wc_gzd_get_product( $product )->is_differential_taxed() ) {
			/**
			 * Differential taxation mark.
			 *
			 * Adjust the default differential taxation mark.
			 *
			 * @param string $html The differential mark e.g. `*`.
			 *
			 * @since 1.9.1
			 */
			$product_mark = apply_filters( 'woocommerce_gzd_differential_taxation_cart_item_mark', wc_gzd_get_differential_taxation_mark() );
		}
	}

	if ( ! empty( $product_mark ) ) {
		$title .= '<span class="wc-gzd-product-differential-taxation-mark">' . $product_mark . '</span>';
	}

	return $title;
}

function wc_gzd_cart_contains_differential_taxed_product() {

	// Might gets called from Shopmarks before init - return false to prevent cart errors
	if ( ! did_action( 'before_woocommerce_init' ) || doing_action( 'before_woocommerce_init' ) ) {
		return false;
	}

	$cart                           = WC()->cart;
	$contains_differentail_taxation = false;

	if ( ! $cart ) {
	    return false;
    }

	foreach ( $cart->get_cart() as $cart_item_key => $values ) {
		$_product = $values['data'];

		if ( wc_gzd_get_product( $_product )->is_differential_taxed() ) {
			$contains_differentail_taxation = true;
			break;
		}
	}

	return $contains_differentail_taxation;
}

/**
 * Appends product item desc live data (while checkout) or order meta to product name
 *
 * @param string $title
 * @param array|WC_Order_Item_Product $cart_item
 *
 * @return string
 */
function wc_gzd_cart_product_item_desc( $title, $cart_item, $cart_item_key = '' ) {
	$product_desc = "";
	$echo         = false;

	if ( is_array( $title ) && isset( $title['data'] ) ) {
		$cart_item     = $title;
		$cart_item_key = $cart_item;
		$title         = "";
		$echo          = true;
	}

	if ( is_a( $cart_item, 'WC_Order_Item_Product' ) ) {
		if ( $gzd_item = wc_gzd_get_order_item( $cart_item ) ) {
			$product_desc = $gzd_item->get_cart_description();
		} elseif( ( $product = $cart_item->get_product() ) && wc_gzd_get_gzd_product( $product )->get_mini_desc() ) {
			$product_desc = wc_gzd_get_gzd_product( $product )->get_formatted_cart_description();
		}
	} elseif ( isset( $cart_item['data'] ) ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

		if ( is_a( $product, 'WC_Product' ) && wc_gzd_get_product( $product )->get_cart_description() ) {
			$product_desc = wc_gzd_get_product( $product )->get_formatted_cart_description();
		}
	} elseif ( isset( $cart_item['item_desc'] ) ) {
		$product_desc = $cart_item['item_desc'];
	}

	if ( ! empty( $product_desc ) ) {
		$title .= '<div class="wc-gzd-cart-info wc-gzd-item-desc item-desc">' . do_shortcode( $product_desc ) . '</div>';
	}

	if ( $echo ) {
		echo $title;
	}

	return $title;
}

function wc_gzd_cart_product_attributes( $title, $cart_item, $cart_item_key = '' ) {
	$item_data = array();

	if ( is_a( $cart_item, 'WC_Order_Item_Product' ) ) {
		if ( $product = $cart_item->get_product() ) {
			$item_data = wc_gzd_get_product( $product )->get_checkout_attributes();
		}
	} elseif ( isset( $cart_item['data'] ) ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

		if ( is_a( $product, 'WC_Product' ) ) {
			$item_data = wc_gzd_get_gzd_product( $product )->get_checkout_attributes( array(), isset( $cart_item['variation'] ) ? $cart_item['variation'] : array() );
		}
	}

	// Format item data ready to display.
	foreach ( $item_data as $key => $data ) {
		// Set hidden to true to not display meta on cart.
		if ( ! empty( $data['hidden'] ) ) {
			unset( $item_data[ $key ] );
			continue;
		}
		$item_data[ $key ]['key']     = ! empty( $data['key'] ) ? $data['key'] : $data['name'];
		$item_data[ $key ]['display'] = ! empty( $data['display'] ) ? $data['display'] : $data['value'];
	}

	$item_data_html = '';

	// Output flat or in list format.
	if ( count( $item_data ) > 0 ) {
		ob_start();
		foreach ( $item_data as $data ) {
			echo esc_html( $data['key'] ) . ': ' . strip_tags( $data['display'] ) . "\n" . "<br/>";
		}
		$item_data_html = ob_get_clean();
	}

	if ( ! empty( $item_data_html ) ) {
		$title .= '<div class="wc-gzd-item-attributes item-attributes">' . $item_data_html . '</div>';
	}

	return $title;
}

/**
 * Appends delivery time live data (while checkout) or order meta to product name
 *
 * @param string $title
 * @param array|WC_Order_Item_Product $cart_item
 *
 * @return string
 */
function wc_gzd_cart_product_delivery_time( $title, $cart_item, $cart_item_key = '' ) {
	$delivery_time = "";
	$echo          = false;

	if ( is_array( $title ) && isset( $title['data'] ) ) {
		$cart_item     = $title;
		$cart_item_key = $cart_item;
		$title         = "";
		$echo          = true;
	}

	if ( is_a( $cart_item, 'WC_Order_Item_Product' ) ) {
		if ( $gzd_item = wc_gzd_get_order_item( $cart_item ) ) {
			$delivery_time = $gzd_item->get_delivery_time();
		} elseif( ( $product = $cart_item->get_product() ) && wc_gzd_get_product( $product )->get_delivery_time_term() ) {
			$delivery_time = wc_gzd_get_product( $product )->get_delivery_time_html();
		}
	} elseif ( isset( $cart_item['data'] ) ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

		if ( is_a( $product, 'WC_Product' ) && wc_gzd_get_product( $product )->get_delivery_time_term() ) {
			$delivery_time = wc_gzd_get_product( $product )->get_delivery_time_html();
		}

	} elseif ( isset( $cart_item['delivery_time'] ) ) {
		$delivery_time = $cart_item['delivery_time'];
	}

	if ( ! empty( $delivery_time ) ) {
		$title .= '<p class="wc-gzd-cart-info delivery-time-info">' . $delivery_time . '</p>';
	}

	if ( $echo ) {
		echo $title;
	}

	return $title;
}

/**
 * Appends unit price to product price live data (while checkout) or order meta to product price
 *
 * @param string $price
 * @param array $cart_item
 *
 * @return string
 */
function wc_gzd_cart_product_unit_price( $price, $cart_item, $cart_item_key = '' ) {
	$unit_price = "";
	$echo       = false;

	if ( is_array( $price ) && isset( $price['data'] ) ) {
		$cart_item     = $price;
		$cart_item_key = $cart_item;
		$price         = "";
		$echo          = true;
	}

	$tax_display = get_option( 'woocommerce_tax_display_cart' );

	if ( is_a( $cart_item, 'WC_Order_Item_Product' ) ) {
        if ( $gzd_item = wc_gzd_get_order_item( $cart_item ) ) {
	        $unit_price = $gzd_item->get_formatted_unit_price( 'incl' === $tax_display ? true : false );
        } elseif( ( $product = $cart_item->get_product() ) && wc_gzd_get_product( $product )->has_unit() ) {
		    $unit_price = wc_gzd_get_product( $product )->get_unit_price_html( false, $tax_display );
        }
	} elseif ( isset( $cart_item['data'] ) ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

		if ( is_a( $product, 'WC_Product' ) && wc_gzd_get_product( $product )->has_unit() ) {
		    $unit_price = wc_gzd_get_product( $product )->get_unit_price_html( false, $tax_display );
		}
	} elseif ( isset( $cart_item['unit_price'] ) ) {
		$unit_price = $cart_item['unit_price'];
	}

	if ( ! empty( $unit_price ) ) {
		$price .= ' <span class="wc-gzd-cart-info unit-price unit-price-cart">' . $unit_price . '</span>';
	}

	if ( $echo ) {
		echo $price;
	}

	return $price;
}

/**
 * Appends product units live data (while checkout) or order meta to product name
 *
 * @param string $title
 * @param array $cart_item
 *
 * @return string
 */
function wc_gzd_cart_product_units( $title, $cart_item, $cart_item_key = '' ) {
	$units = "";
	$echo  = false;

	if ( is_array( $title ) && isset( $title['data'] ) ) {
		$cart_item     = $title;
		$cart_item_key = $cart_item;
		$title         = "";
		$echo          = true;
	}

	if ( is_a( $cart_item, 'WC_Order_Item_Product' ) ) {
		if ( $gzd_item = wc_gzd_get_order_item( $cart_item ) ) {
		    $units = $gzd_item->get_formatted_product_units();
		} elseif( ( $product = $cart_item->get_product() ) && wc_gzd_get_product( $product )->has_unit_product() ) {
			$units = wc_gzd_get_product( $product )->get_unit_product_html();
		}
	} elseif ( isset( $cart_item['data'] ) ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

		if ( is_a( $product, 'WC_Product' ) && wc_gzd_get_product( $product )->has_unit_product() ) {
			$units = wc_gzd_get_gzd_product( $product )->get_unit_product_html();
		}
	} elseif ( isset( $cart_item['units'] ) ) {
		$units = $cart_item['units'];
	}

	if ( ! empty( $units ) ) {
		$title .= '<p class="wc-gzd-cart-info units-info">' . $units . '</p>';
	}

	if ( $echo ) {
		echo $title;
	}

	return $title;
}

function wc_gzd_cart_needs_age_verification( $items = false ) {
	$items                  = $items ? (array) $items : WC()->cart->get_cart();
	$is_cart                = true;
	$needs_age_verification = false;

	if ( ! empty( $items ) ) {
		foreach ( $items as $cart_item_key => $values ) {
			if ( is_a( $values, 'WC_Order_Item_Product' ) ) {
			    if ( $gzd_item = wc_gzd_get_order_item( $values ) ) {
					$needs_age_verification = $gzd_item->needs_age_verification();
				}

				$is_cart  = false;
			} elseif ( isset( $values['data'] ) ) {
				$_product = apply_filters( 'woocommerce_cart_item_product', $values['data'], $values, $cart_item_key );

				if ( is_a( $_product, 'WC_Product' ) && wc_gzd_needs_age_verification( $_product ) ) {
					$needs_age_verification = true;
				}
			}

			if ( $needs_age_verification ) {
			    break;
            }
		}
	}

	if ( ! $is_cart ) {
		/**
		 * Determines whether order items need age verification or not.
		 *
		 * This filter might adjust whether order items need age verification or not.
		 *
		 * @param bool $needs_age_verification Whether items need age verification or not.
		 * @param array $items The order items.
		 *
		 * @since 2.3.5
		 *
		 */
		return apply_filters( 'woocommerce_gzd_order_needs_age_verification', $needs_age_verification, $items );
	} else {
		/**
		 * Determines whether a cart needs age verification or not.
		 *
		 * This filter might adjust whether cart items need age verification or not.
		 *
		 * @param bool $needs_age_verification Whether items need age verification or not.
		 * @param array $items The cart items.
		 *
		 * @since 2.3.5
		 *
		 */
		return apply_filters( 'woocommerce_gzd_cart_needs_age_verification', $needs_age_verification, $items );
	}
}

function wc_gzd_cart_get_age_verification_min_age( $items = false ) {
	$items   = $items ? (array) $items : WC()->cart->get_cart();
	$min_age = false;
	$is_cart = true;

	if ( ! empty( $items ) ) {

		foreach ( $items as $cart_item_key => $values ) {
			$item_min_age = false;

			if ( is_a( $values, 'WC_Order_Item_Product' ) ) {
				if ( $gzd_item = wc_gzd_get_order_item( $values ) ) {
				    if ( $gzd_item->needs_age_verification() ) {
					    $item_min_age = (int) $gzd_item->get_min_age();
                    }
				}

				$is_cart  = false;
			} elseif ( isset( $values['data'] ) ) {
				$_product = apply_filters( 'woocommerce_cart_item_product', $values['data'], $values, $cart_item_key );

				if ( is_a( $_product, 'WC_Product' ) ) {
					$_gzd_product = wc_gzd_get_gzd_product( $_product );

					if ( wc_gzd_needs_age_verification( $_product ) ) {
						$item_min_age = (int) $_gzd_product->get_min_age();
					}
				}
			}

			if ( false !== $item_min_age ) {
				if ( false === $min_age ) {
					$min_age = $item_min_age;
				} elseif ( $item_min_age > $min_age ) {
					$min_age = $item_min_age;
				}
            }
		}
	}

	if ( ! $is_cart ) {
		/**
		 * Returns the minimum age for certain order items.
		 *
		 * This filter might be used to adjust the minimum age for a certain order used for
		 * the age verification.
		 *
		 * @param integer $min_age The minimum age required to buy.
		 * @param array $items The order items.
		 *
		 * @since 3.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_order_age_verification_min_age', $min_age, $items );
	} else {
		/**
		 * Returns the minimum age for a cart.
		 *
		 * This filter might be used to adjust the minimum age for a certain cart used for
		 * the age verification.
		 *
		 * @param integer $min_age The minimum age required to checkout.
		 * @param array $items The cart items.
		 *
		 * @since 2.3.5
		 *
		 */
		return apply_filters( 'woocommerce_gzd_cart_age_verification_min_age', $min_age, $items );
	}
}

function wc_gzd_item_is_tax_share_exempt( $item, $type = 'shipping', $key = false ) {
	$exempt     = false;
	$_product   = false;
	$is_cart    = false;
	$tax_class  = '';
	$tax_status = '';

	if ( is_a( $item, 'WC_Order_Item' ) ) {
		$_product   = $item->get_product();
		$tax_class  = $item->get_tax_class();
		$tax_status = $item->get_tax_status();
	} elseif ( isset( $item['data'] ) ) {
		$_product = apply_filters( 'woocommerce_cart_item_product', $item['data'], $item, $key );
		$is_cart  = true;

		if ( is_a( $_product, 'WC_Product' ) ) {
			$tax_status = $_product->get_tax_status();
			$tax_class  = $_product->get_tax_class();
        }
	}

	if ( is_a( $_product, 'WC_Product' ) ) {
	    if ( 'shipping' === $type ) {
		    if ( $_product->is_virtual() || wc_gzd_get_product( $_product )->is_virtual_vat_exception() ) {
			    $exempt = true;
		    }
        }
    }

	if ( 'none' === $tax_status || 'zero-rate' === $tax_class ) {
		$exempt = true;
	}

	if ( $is_cart ) {
		/**
		 * Filter whether cart item supports tax share calculation or not.
		 *
		 * @param bool   $exempt True if it is an exempt. False if not.
		 * @param array  $item The cart item.
		 * @param string $key The cart item hash if existent.
		 * @param string $type The tax calculation type e.g. shipping or fees.
		 *
		 * @since 1.7.5
		 */
		$exempt = apply_filters( 'woocommerce_gzd_cart_item_not_supporting_tax_share', $exempt, $item, $key, $type );
    } else {
		/**
		 * Filter whether order item supports tax share calculation or not.
		 *
		 * @param bool          $exempt True if it is an exempt. False if not.
		 * @param WC_Order_Item $item The order item.
		 * @param string        $type The tax calculation type e.g. shipping or fees.
		 *
		 * @since 3.1.2
		 */
		$exempt = apply_filters( 'woocommerce_gzd_order_item_tax_share_exempt', $exempt, $item, $type );
	}

	return $exempt;
}

/**
 * Calculates tax share for shipping/fees
 *
 * @param string $type
 *
 * @return array
 */
function wc_gzd_get_cart_tax_share( $type = 'shipping', $cart_contents = array() ) {
	$cart        = empty( $cart_contents ) ? WC()->cart->cart_contents : $cart_contents;
	$tax_shares  = array();
	$item_totals = 0;
	$is_cart     = true;

	// Get tax classes and tax amounts
	if ( ! empty( $cart ) ) {
		foreach ( $cart as $key => $item ) {

			if ( is_a( $item, 'WC_Order_Item' ) ) {
				$class      = $item->get_tax_class();
				$line_total = $item->get_total();
				$taxes      = $item->get_taxes();
				$tax_rate   = key( $taxes['total'] );

				// Search for the first non-empty tax rate
				foreach( $taxes['total'] as $rate_id => $tax ) {
				    if ( ! empty( $tax ) ) {
				        $tax_rate = $rate_id;
				        break;
                    }
                }
			} elseif ( isset( $item['data'] ) ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $item['data'], $item, $key );
				$class      = $_product->get_tax_class();
				$line_total = $item['line_total'];
				$tax_rate   = key( $item['line_tax_data']['total'] );
			}

			if ( wc_gzd_item_is_tax_share_exempt( $item, $type, $key ) ) {
			    continue;
            }

			if ( ! isset( $tax_shares[ $class ] ) ) {
				$tax_shares[ $class ]          = array();
				$tax_shares[ $class ]['total'] = 0;
				$tax_shares[ $class ]['key']   = '';
			}

			// Does not contain pricing data in case of recurring Subscriptions
			$tax_shares[ $class ]['total'] += $line_total;
			$tax_shares[ $class ]['key']   = $tax_rate;

			$item_totals += $line_total;
		}
	}

	if ( ! empty( $tax_shares ) ) {
		$default = ( $item_totals == 0 ? 1 / sizeof( $tax_shares ) : 0 );

		foreach ( $tax_shares as $key => $class ) {
			$tax_shares[ $key ]['share'] = ( $item_totals > 0 ? $class['total'] / floatval( $item_totals ) : $default );
		}
	}

	return $tax_shares;
}

function wc_gzd_cart_remove_shipping_taxes( $taxes, $cart ) {
	return is_callable( array( $cart, 'set_cart_contents_taxes' ) ) ? $cart->get_cart_contents_taxes() : $cart->taxes;
}

function wc_gzd_get_cart_taxes( $cart, $include_shipping_taxes = true ) {
	$tax_array = array();

	// If prices are tax inclusive, show taxes here
	if ( 'yes' === get_option( 'woocommerce_calc_taxes' ) && 'incl' === wc_gzd_get_cart_tax_display_mode() ) {

		if ( 'itemized' === get_option( 'woocommerce_tax_total_display' )  ) {
			if ( ! $include_shipping_taxes ) {
				add_filter( 'woocommerce_cart_get_taxes', 'wc_gzd_cart_remove_shipping_taxes', 10, 2 );
			}

			$taxes = $cart->get_tax_totals();

			if ( ! $include_shipping_taxes ) {
				remove_filter( 'woocommerce_cart_get_taxes', 'wc_gzd_cart_remove_shipping_taxes', 10 );
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
			$base_rate = array_values( WC_Tax::get_base_tax_rates() );

			if ( ! empty( $base_rate ) ) {
				$base_rate       = (object) $base_rate[0];
				$tax_array[]     = array(
					'tax'      => $base_rate,
					'contains' => array( $base_rate ),
					'amount'   => WC()->cart->get_taxes_total( true, true )
				);
            }
		}
	}

	/**
	 * Filter to adjust the cart tax items.
	 *
	 * @param array   $tax_array The array containing tax amounts.
     * @param WC_Cart $cart The cart instance.
     * @param bool    $include_shipping_taxes Whether to include shipping taxes or not.
	 *
	 * @since 3.1.8
	 */
	return apply_filters( 'woocommerce_gzd_cart_taxes', $tax_array, $cart, $include_shipping_taxes );
}

function wc_gzd_get_cart_total_taxes( $include_shipping_taxes = true ) {
	return wc_gzd_get_cart_taxes( WC()->cart, $include_shipping_taxes );
}

/**
 * Get order total tax html.
 *
 * @return void
 */
function wc_gzd_cart_totals_order_total_tax_html() {

	foreach ( wc_gzd_get_cart_total_taxes() as $tax ) :
		$label = wc_gzd_get_tax_rate_label( $tax['tax']->rate );
		?>
        <tr class="order-tax">
            <th><?php echo $label; ?></th>
            <td data-title="<?php echo esc_attr( $label ); ?>"><?php echo wc_price( $tax['amount'] ); ?></td>
        </tr>
	<?php endforeach;
}


function wc_gzd_get_legal_text( $plain_text ) {
	if ( ! empty( $plain_text ) ) {

		$replacements = array(
			'{term_link}'           => '<a href="' . esc_url( wc_gzd_get_page_permalink( 'terms' ) ) . '" target="_blank">',
			'{data_security_link}'  => '<a href="' . esc_url( wc_gzd_get_page_permalink( 'data_security' ) ) . '" target="_blank">',
			'{revocation_link}'     => '<a href="' . esc_url( wc_gzd_get_page_permalink( 'revocation' ) ) . '" target="_blank">',
			'{/term_link}'          => '</a>',
			'{/data_security_link}' => '</a>',
			'{/revocation_link}'    => '</a>',
		);

		$plain_text = wc_gzd_replace_label_shortcodes( $plain_text, $replacements );
	}

	return $plain_text;
}

function wc_gzd_get_legal_text_error() {
	wc_deprecated_function( __FUNCTION__, '2.0' );
}

function wc_gzd_get_legal_text_digital() {
	wc_deprecated_function( __FUNCTION__, '2.0' );
}

function wc_gzd_get_legal_text_digital_error() {
	wc_deprecated_function( __FUNCTION__, '2.0' );
}

function wc_gzd_get_legal_text_digital_email_notice() {
	$text = '';

	if ( $checkbox = wc_gzd_get_legal_checkbox( 'download' ) ) {
		$text = $checkbox->confirmation;

		if ( $text ) {
			$replacements = array(
				'{link}'  => '<a href="' . esc_url( wc_gzd_get_page_permalink( 'revocation' ) ) . '" target="_blank">',
				'{/link}' => '</a>',
			);

			$text = wc_gzd_replace_label_shortcodes( $text, $replacements );
		}
	}

	/**
	 * Filter to adjust the legal email text for digital products.
	 *
	 * @param string $text The HTML output.
	 *
	 * @since 2.0.2
	 */
	return apply_filters( 'woocommerce_gzd_legal_digital_email_text', $text );
}

function wc_gzd_get_legal_text_service() {
	wc_deprecated_function( __FUNCTION__, '2.0' );
}

function wc_gzd_get_legal_text_service_error() {
	wc_deprecated_function( __FUNCTION__, '2.0' );
}

function wc_gzd_get_legal_text_service_email_notice() {
	$text = '';

	if ( $checkbox = wc_gzd_get_legal_checkbox( 'service' ) ) {
		$text = $checkbox->confirmation;

		if ( $text ) {
			$replacements = array(
				'{link}'  => '<a href="' . esc_url( wc_gzd_get_page_permalink( 'revocation' ) ) . '" target="_blank">',
				'{/link}' => '</a>',
			);

			$text = wc_gzd_replace_label_shortcodes( $text, $replacements );
		}
	}

	/**
	 * Filter to adjust the legal email text for service products.
	 *
	 * @param string $text The HTML output.
	 *
	 * @since 2.0.2
	 *
	 */
	return apply_filters( 'woocommerce_gzd_legal_service_email_text', $text );
}

function wc_gzd_get_chosen_shipping_rates( $args = array() ) {

	$args = wp_parse_args( $args, array(
		'value' => '',
	) );

	$packages         = WC()->shipping->get_packages();
	$shipping_methods = (array) WC()->session->get( 'chosen_shipping_methods' );
	$rates            = array();

	foreach ( $packages as $i => $package ) {
		if ( isset( $shipping_methods[ $i ] ) && isset( $package['rates'][ $shipping_methods[ $i ] ] ) ) {
			if ( empty( $args['value'] ) ) {
				array_push( $rates, $package['rates'][ $shipping_methods[ $i ] ] );
			} else {
				array_push( $rates, $package['rates'][ $shipping_methods[ $i ] ]->{$args['value']} );
			}
		}
	}

	return $rates;
}

function wc_gzd_get_legal_text_parcel_delivery( $titles = array() ) {
	wc_deprecated_function( __FUNCTION__, '2.0' );
}
