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
		'service'					=> '',
		'mini_desc'                 => '',
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
		
		if ( is_numeric( $product ) ) {
			$product = WC()->product_factory->get_product_standalone( get_post( $product ) );
        }
		
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

			if ( '' === $value ) {
				$value = $this->gzd_variation_level_meta[ $key ];
			}
		
		} elseif ( $this->child->is_type( 'variation' ) && in_array( $key, $this->gzd_variation_inherited_meta_data ) ) {

			$value = wc_gzd_get_crud_data( $this->child, $key ) ? wc_gzd_get_crud_data( $this->child, $key ) : '';

			// Handle meta data keys which can be empty at variation level to cause inheritance
			if ( ! $value || '' === $value ) {
				$parent = wc_get_product( wc_gzd_get_crud_data( $this->child, 'parent' ) );

				// Check if parent exists
				if ( $parent ) {
					$value = wc_gzd_get_crud_data( $parent, $key );
				}
			}
		
		} elseif ( $key == 'delivery_time' ) {
			$value = $this->get_delivery_time();
		} else {
			
			if ( strpos( '_', $key ) !== true ) {
				$key = '_' . $key;
			}

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
	    $prices = wc_gzd_recalculate_unit_price( $args, $this );

	    if ( empty( $prices ) ) {
	        return;
        }

        if ( isset( $args['base'] ) && ! empty( $args['base'] ) ) {
            $this->unit_base      = $args['base'];
        }

		$this->unit_price_regular = $prices['regular'];
		$this->unit_price_sale    = $prices['sale'];
		$this->unit_price         = $prices['unit'];

        /**
         * Recalculated unit price.
         *
         * Executes whenever the unit price is recalculated.
         *
         * @since 1.9.1
         *
         * @param WC_GZD_Product $product The product object.
         */
		do_action( 'woocommerce_gzd_recalculated_unit_price', $this );
	}

	/**
	 * Get a product's cart description
	 * 
	 * @return boolean|string
	 */
	public function get_mini_desc() {
        /**
         * Filter that allows adjusting a product's mini cart description.
         *
         * @since 1.0.0
         *
         * @param string         $html The cart description.
         * @param WC_GZD_Product $product The product object.
         */
	    $mini_desc = apply_filters( 'woocommerce_gzd_product_cart_description', $this->mini_desc, $this );

		if ( $mini_desc && ! empty( $mini_desc ) ) {
            return wpautop( htmlspecialchars_decode( $mini_desc ) );
        }

		return false;
	}

	private function attribute_exists( $key, $item_data ) {
        foreach( $item_data as $item_data_key => $data ) {
            if ( isset( $data['key'] ) && $key === $data['key'] ) {
                return true;
            }
        }

        return false;
    }

    protected function attribute_is_checkout_visible( $attribute ) {
	    return ( $attribute && ( 'yes' === get_option( 'woocommerce_gzd_display_checkout_product_attributes' ) || $attribute->is_checkout_visible() ) );
	}

	public function get_checkout_attributes( $item_data = array(), $cart_variation_data = array() ) {
	    $item_data = ! empty( $item_data ) ? $item_data : array();

	    if ( $this->child->is_type( 'variation' ) ) {
            $attributes = ! empty( $cart_variation_data ) ? $cart_variation_data : $this->child->get_variation_attributes();

            foreach( $attributes as $name => $value ) {
                $taxonomy  = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );
                $attribute = WC_GZD_Product_Attribute_Helper::instance()->get_attribute_by_variation( $this->child, $name );

                if ( $this->attribute_is_checkout_visible( $attribute ) ) {
                    if ( taxonomy_exists( $taxonomy ) ) {
                        // If this is a term slug, get the term's nice name.
                        $term = get_term_by( 'slug', $value, $taxonomy );
                        if ( ! is_wp_error( $term ) && $term && $term->name ) {
                            $value = $term->name;
                        }
                        $label = wc_attribute_label( $taxonomy );
                    } else {
                        // If this is a custom option slug, get the options name.
                        $value = apply_filters( 'woocommerce_variation_option_name', $value );
                        $label = wc_attribute_label( str_replace( 'attribute_', '', $name ), $this->child );
                    }

                    if ( '' === $value || $this->attribute_exists( $label, $item_data ) ) {
                        continue;
                    }

                    $item_data[] = array(
                        'key'   => $label,
                        'value' => $value,
                    );
                }
            }
        }

        $product    = $this->child->is_type( 'variation' ) ? wc_get_product( $this->child->get_parent_id() ) : $this->child;
        $attributes = $product->get_attributes();

        foreach( $attributes as $attribute ) {
            $attribute = WC_GZD_Product_Attribute_Helper::instance()->get_attribute( $attribute, $product );

            if ( $this->attribute_is_checkout_visible( $attribute ) ) {
                $values = array();

                // Make sure to exclude variation specific attributes (which were already added by variation data).
                if ( $this->child->is_type( 'variation' ) && $attribute->get_variation() ) {
                    continue;
                }

                if ( $attribute->is_taxonomy() ) {
                    $attribute_taxonomy = $attribute->get_taxonomy_object();
                    $attribute_values   = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );

                    foreach ( $attribute_values as $attribute_value ) {
                        $value_name = esc_html( $attribute_value->name );

                        /**
                         * Filter that might allow making checkout attributes clickable.
                         *
                         * @since 2.0.0
                         *
                         * @param bool $enable Set to `true` to enable clickable checkout attributes.
                         */
                        if ( apply_filters( 'woocommerce_gzd_product_attribute_checkout_clickable', false ) && $attribute_taxonomy->attribute_public ) {
                            $values[] = '<a href="' . esc_url( get_term_link( $attribute_value->term_id, $attribute->get_name() ) ) . '" rel="tag">' . $value_name . '</a>';
                        } else {
                            $values[] = $value_name;
                        }
                    }
                } else {
                    $values = $attribute->get_options();

                    foreach ( $values as &$value ) {
                        $value = make_clickable( esc_html( $value ) );
                    }
                }

                $label = wc_attribute_label( $attribute->get_name() );

                if ( $this->attribute_exists( $label, $item_data ) ) {
                    continue;
                }

                $item_data[] = array(
                    'key'   => $label,
                    'value' => apply_filters( 'woocommerce_attribute', wpautop( wptexturize( implode( ', ', $values ) ) ), $attribute->get_attribute(), $values )
                );
            }
        }

        /**
         * Filter product attributes visible during checkout.
         *
         * @since 2.2.9
         *
         * @param array      $item_data The attribute data.
         * @param WC_Product $product The product object.
         */
        return apply_filters( 'woocommerce_gzd_product_checkout_attributes', $item_data, $this->child );
    }

	public function is_service() {
		if ( ! empty( $this->service ) && 'yes' === $this->service ) {
            return true;
        }

		return false;
	}

	public function is_differential_taxed() {
		if ( ! empty( $this->differential_taxation ) && 'yes' === $this->differential_taxation ) {
            return true;
        }

		return false;
	}

	/**
	 * Checks whether current product applies for a virtual VAT exception (downloadable or virtual)
	 *  
	 * @return boolean
	 */
	public function is_virtual_vat_exception() {
        /**
         * Filter that allows marking a product as virtual vat exception.
         *
         * @since 1.8.5
         *
         * @param bool           $is_exception Whether it is a exception or not.
         * @param WC_GZD_Product $product The product object.
         */
		return apply_filters( 'woocommerce_gzd_product_virtual_vat_exception', ( ( get_option( 'woocommerce_gzd_enable_virtual_vat' ) === 'yes' ) && ( $this->is_downloadable() || $this->is_virtual() ) ? true : false ), $this );
	}

	public function add_labels_to_price_html( $price_html ) {

	    $org_price_html = $price_html;

		if ( ! $this->child->is_on_sale() ) {
            return $price_html;
        }

		$sale_label         = $this->get_sale_price_label();
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
		$new_price_sale    = $match_sale[0];
		$new_price_suffix  = ( empty( $match_suffix ) ? '' : ' ' . $match_suffix[0] );

		if ( ! empty( $sale_label ) && isset( $match_regular[1] ) ) {
			$new_price_regular = '<span class="wc-gzd-sale-price-label">' . $sale_label . '</span> ' . $match_regular[0];
        }

		if ( ! empty( $sale_regular_label ) && isset( $match_sale[1] ) ) {
			$new_price_sale = '<span class="wc-gzd-sale-price-label wc-gzd-sale-price-regular-label">' . $sale_regular_label . '</span> ' . $match_sale[0];
        }

        /**
         * Filters the product sale price containing price labels.
         *
         * @since 1.8.5
         *
         * @param string         $html The new price containing labels.
         * @param string         $old_price The old price.
         * @param WC_GZD_Product $product The product object.
         */
		return apply_filters( 'woocommerce_gzd_product_sale_price_with_labels_html', $new_price_regular . ' ' . $new_price_sale . $new_price_suffix, $org_price_html, $this );
	}

	public function get_price_html_from_to( $from, $to, $show_labels = true ) {

		$sale_label         = ( $show_labels ? $this->get_sale_price_label() : '' );
		$sale_regular_label = ( $show_labels ? $this->get_sale_price_regular_label() : '' );

		$price = ( ! empty( $sale_label ) ? '<span class="wc-gzd-sale-price-label">' . $sale_label . '</span>' : '' ) . ' <del>' . ( ( is_numeric( $from ) ) ? wc_price( $from ) : $from ) . '</del> ' . ( ! empty( $sale_regular_label ) ? '<span class="wc-gzd-sale-price-label wc-gzd-sale-price-regular-label">' . $sale_regular_label . '</span> ' : '' ) . '<ins>' . ( ( is_numeric( $to ) ) ? wc_price( $to ) : $to ) . '</ins>';

        /**
         * Filter to adjust the HTML price range for unit prices.
         *
         * @since 1.0.0
         *
         * @param string         $price The HTML price range.
         * @param string         $from The from price.
         * @param string         $to The to price.
         * @param WC_GZD_Product $product The product object.
         */
		return apply_filters( 'woocommerce_germanized_get_price_html_from_to', $price, $from, $to, $this );
	}

	/**
	 * Gets a product's tax description (if is taxable)
	 *  
	 * @return mixed string if is taxable else returns false
	 */
	public function get_tax_info() {
		$tax_notice    = false;
		$is_vat_exempt = ( ! empty( WC()->customer ) ? WC()->customer->is_vat_exempt() : false );

		if ( $this->is_taxable() || $this->is_differential_taxed() ) {
		
			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
			$tax_rates  = WC_Tax::get_rates( $this->get_tax_class() );

			if ( ! empty( $tax_rates ) ) {
		
				$tax_rates = array_values( $tax_rates );

				// If is variable or is virtual vat exception dont show exact tax rate
				if ( $this->is_virtual_vat_exception() || $this->is_type( 'variable' ) || $this->is_type( 'grouped' ) || get_option( 'woocommerce_gzd_hide_tax_rate_shop' ) === 'yes' ) {
					$tax_notice = ( $tax_display_mode == 'incl' && ! $is_vat_exempt ? __( 'incl. VAT', 'woocommerce-germanized' ) : __( 'excl. VAT', 'woocommerce-germanized' ) );
                } else {
					$tax_notice = ( $tax_display_mode == 'incl' && ! $is_vat_exempt ? sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), ( wc_gzd_format_tax_rate_percentage( $tax_rates[0][ 'rate' ] ) ) ) : sprintf( __( 'excl. %s%% VAT', 'woocommerce-germanized' ), ( wc_gzd_format_tax_rate_percentage( $tax_rates[0]['rate'] ) ) ) );
                }
			}

			if ( $this->is_differential_taxed() ) {
				if ( get_option( 'woocommerce_gzd_differential_taxation_show_notice' ) === 'yes' ) {
					$tax_notice = wc_gzd_get_differential_taxation_notice_text();
				} else {
					$tax_notice = __( 'incl. VAT', 'woocommerce-germanized' );
				}
			}
		}

        /**
         * Filter to adjust the product tax notice.
         *
         * This filter allows you to easily change the tax notice on a per product basis.
         *
         * @since 1.0.0
         *
         * @param string         $tax_notice The tax notice.
         * @param WC_GZD_Product $product The product object.
         */
		return apply_filters( 'woocommerce_gzd_product_tax_info', $tax_notice, $this );
	}

	/**
	 * Checks whether current Product has a unit price
	 *  
	 * @return boolean
	 */
	public function has_unit() {
		if ( $this->unit && $this->unit_price_regular && $this->unit_base ) {
			return true;
        }

		return false;
	}

	/**
	 * Returns unit base html
	 *  
	 * @return string
	 */
	public function get_unit_base() {
        /**
         * Filter that allows changing the amount which is used to determine whether
         * the base for the unit price should be skipped or not. Defaults to 1.
         *
         * @since 1.0.0
         *
         * @param int $amount The amount.
         */
	    $hide_amount = apply_filters( 'woocommerce_gzd_unit_base_hide_amount', 1 );

        /**
         * Filter to adjust the unit price base separator.
         *
         * @since 1.0.0
         *
         * @param string $separator The separator.
         */
	    $separator   = apply_filters( 'wc_gzd_unit_price_base_seperator', ' ' );

		return ( $this->unit_base ) ? ( $this->unit_base != $hide_amount ? '<span class="unit-base">' . $this->unit_base . '</span>' . $separator : '' ) . '<span class="unit">' . $this->get_unit() . '</span>' : '';
	}

	public function get_unit_base_raw() {
		return $this->unit_base;
	}

	public function get_unit_term() {
		$unit = $this->unit;

		if ( ! empty( $unit ) ) {
			return WC_germanized()->units->get_unit_term( $unit );
		}

		return false;
	}

	public function get_unit_raw() {
	    return $this->unit;
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

        /**
         * Filter to adjust a product's regular unit price.
         *
         * @since 1.0.0
         *
         * @param string         $price The regular unit price.
         * @param WC_GZD_Product $product The product object.
         */
		return apply_filters( 'woocommerce_gzd_get_unit_regular_price', $this->unit_price_regular, $this );
	}

	/**
	 * Returns unit sale price
	 *  
	 * @return string the sale price 
	 */
	public function get_unit_sale_price() {

        /**
         * Filter to adjust a product's sale unit price.
         *
         * @since 1.0.0
         *
         * @param string         $price The sale unit price.
         * @param WC_GZD_Product $product The product object.
         */
		return apply_filters( 'woocommerce_gzd_get_unit_sale_price', $this->unit_price_sale, $this );
	}

	/**
	 * Returns unit sale price
	 *
	 * @return string the sale price
	 */
	public function get_unit_price_raw() {

        /**
         * Filter to adjust a product's raw unit price.
         *
         * @since 1.0.0
         *
         * @param string         $price The raw unit price.
         * @param WC_GZD_Product $product The product object.
         */
		return apply_filters( 'woocommerce_gzd_get_unit_price_raw', $this->unit_price, $this );
	}

	/**
	 * Returns the unit price (if is sale then return sale price)
	 *  
	 * @param  integer $qty   
	 * @param  string  $price 
	 * @return string  formatted unit price
	 */
	public function get_unit_price( $qty = 1, $price = '' ) {
        /**
         * Before retrieving unit price.
         *
         * Fires before the product unit price is retrieved.
         *
         * @since 1.0.0
         *
         * @param WC_GZD_Product $this The product object.
         * @param string         $price Optionally pass the price.
         * @param int            $qty The product quantity.
         */
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
		$price = ( $price == '' ) ? $this->get_unit_price_raw() : $price;

        /**
         * Filter to adjust the unit price including tax.
         *
         * @since 1.0.0
         *
         * @param string         $unit_price The calculated unit price.
         * @param string         $price The price passed.
         * @param int            $qty The quantity.
         * @param WC_GZD_Product $product The product object.
         */
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
		$price = ( $price == '' ) ? $this->get_unit_price_raw() : $price;

        /**
         * Filter to adjust the unit price excluding tax.
         *
         * @since 1.0.0
         *
         * @param string         $unit_price The calculated unit price.
         * @param string         $price The price passed.
         * @param int            $qty The quantity.
         * @param WC_GZD_Product $product The product object.
         */
		return apply_filters( 'woocommerce_gzd_unit_price_excluding_tax', ( $price == '' ) ? '' : wc_gzd_get_price_excluding_tax( $this->child, array( 'price' => $price, 'qty' => $qty ) ), $price, $qty, $this );
	}

	/**
	 * Checks whether unit price is on sale
	 *  
	 * @return boolean 
	 */
	public function is_on_unit_sale() {

        /**
         * Filter to decide whether a product is on unit sale or not.
         *
         * @since 1.0.0
         *
         * @param bool           $on_sale Whether the product is on sale or not.
         * @param WC_GZD_Product $product The product object.
         */
		return apply_filters( 'woocommerce_gzd_product_is_on_unit_sale', ( $this->get_unit_sale_price() !== $this->get_unit_regular_price() && $this->get_unit_sale_price() == $this->get_unit_price_raw() ), $this );
	}

	/**
	 * Returns unit price html output
	 *  
	 * @return string 
	 */
	public function get_unit_html( $show_sale = true ) {

        /**
         * Filter that allows disabling the unit price output for a certain product.
         *
         * @since 1.0.0
         *
         * @param bool           $hide Whether to hide the output or not.
         * @param WC_GZD_Product $product The product object.
         */
		if ( apply_filters( 'woocommerce_gzd_hide_unit_text', false, $this ) ) {

            /**
             * Filter to adjust the output of a disabled product unit price.
             *
             * @since 1.0.0
             *
             * @param string         $output The output.
             * @param WC_GZD_Product $product The product object.
             */
			return apply_filters( 'woocommerce_germanized_disabled_unit_text', '', $this );
        }

		$html = '';

		if ( $this->has_unit() ) {

            /**
             * Before retrieving unit price HTML.
             *
             * Fires before the HTML output for the unit price is generated.
             *
             * @since 1.0.0
             *
             * @param WC_GZD_Product $this The product object.
             */
			do_action( 'woocommerce_gzd_before_get_unit_price_html', $this );

			$display_price         = $this->get_unit_price();
			$display_regular_price = $this->get_unit_price( 1, $this->get_unit_regular_price() );
			$display_sale_price    = $this->get_unit_price( 1, $this->get_unit_sale_price() );

			$price_html   = ( ( $this->is_on_unit_sale() && $show_sale ) ? $this->get_price_html_from_to( $display_regular_price, $display_sale_price, false ) : wc_price( $display_price ) );
			$text         = get_option( 'woocommerce_gzd_unit_price_text' );
			$replacements = array();

			if ( strpos( $text, '{price}' ) !== false ) {
			    $replacements = array(
                    /**
                     * Filter to adjust the unit price separator.
                     *
                     * @since 1.0.0
                     *
                     * @param string $separator The separator.
                     */
			        '{price}' => $price_html . apply_filters( 'wc_gzd_unit_price_seperator', ' / ' ) . $this->get_unit_base(),
                );
			} else {
			    $replacements = array(
			        '{base_price}' => $price_html,
                    '{unit}'       => '<span class="unit">' . $this->get_unit() . '</span>',
                    /** This filter is documented in includes/abstracts/abstract-wc-gzd-product.php */
                    '{base}'       => ( $this->unit_base != apply_filters( 'woocommerce_gzd_unit_base_hide_amount', 1 ) ? '<span class="unit-base">' . $this->unit_base . '</span>' : '' )
                );
			}

            $html = wc_gzd_replace_label_shortcodes( $text, $replacements );
		}

        /**
         * Filter to adjust the product's unit price HTML output.
         *
         * @since 1.0.0
         *
         * @param string         $html The unit price as HTML.
         * @param WC_GZD_Product $product The product object.
         */
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

        /**
         * Filter that allows disabling product units output for a specific product.
         *
         * @since 1.0.0
         *
         * @param bool           $disable Whether to disable or not.
         * @param WC_GZD_Product $product The product object.
         */
		if ( apply_filters( 'woocommerce_gzd_hide_product_units_text', false, $this ) ) {

            /**
             * Filter that allows adjusting the disabled product units output.
             *
             * @since 1.0.0
             *
             * @param string         $notice The output.
             * @param WC_GZD_Product $product The product object.
             */
			return apply_filters( 'woocommerce_germanized_disabled_product_units_text', '', $this );
        }

		$html = '';
		$text = get_option( 'woocommerce_gzd_product_units_text' );

		if ( $this->has_product_units() ) {
		    $replacements = array(
		        '{product_units}' => str_replace( '.', ',', $this->get_unit_products() ),
                '{unit}'          => $this->get_unit(),
                '{unit_price}'    => $this->get_unit_html(),
            );

		    $html = wc_gzd_replace_label_shortcodes( $text, $replacements );
        }

        /**
         * Filter to adjust the product units HTML output.
         *
         * @since 1.0.0
         *
         * @param string         $html The HTML output.
         * @param WC_GZD_Product $product The product object.
         */
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

			if ( ! empty( $parent_terms ) && ! is_wp_error( $parent_terms ) ) {
				$terms = $parent_terms;
            }
		}

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return false;
        }
		
		return $terms[0];
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

        /**
         * Filter that allows hiding the delivery time for a specific product.
         *
         * @since 1.0.0
         *
         * @param bool           $hide Whether to hide delivery time or not.
         * @param WC_GZD_Product $product The product object.
         */
		if ( apply_filters( 'woocommerce_germanized_hide_delivery_time_text', false, $this ) ) {

            /**
             * Filter to adjust disabled product delivery time output.
             *
             * @since 1.0.0
             *
             * @param string         $output The output.
             * @param WC_GZD_Product $product The product object.
             */
            return apply_filters( 'woocommerce_germanized_disabled_delivery_time_text', '', $this );
        }

		if ( $this->get_delivery_time_term() ) {
			$html = $this->get_delivery_time_term()->name;
		} else {
            /**
             * Filter to adjust empty delivery time text.
             *
             * @since 1.0.0
             *
             * @param string         $text The delivery time text.
             * @param WC_GZD_Product $product The product object.
             */
			$html = apply_filters( 'woocommerce_germanized_empty_delivery_time_text', '', $this );
		}

		if ( ! empty( $html ) ) {
            $replacements = array(
                '{delivery_time}' => $html,
            );

            /**
             * Filter to adjust product delivery time HTML.
             *
             * @since 1.0.0
             *
             * @param string         $html The notice.
             * @param string         $option The placeholder option.
             * @param string         $html_org The HTML before replacement.
             * @param WC_GZD_Product $product The product object.
             */
		    $html = apply_filters( 'woocommerce_germanized_delivery_time_html',
                wc_gzd_replace_label_shortcodes( get_option( 'woocommerce_gzd_delivery_time_text' ), $replacements ),
                get_option( 'woocommerce_gzd_delivery_time_text' ),
                $html,
                $this
            );
		} else {
		    $html = '';
        }

        // Hide delivery time if product is not in stock
        if ( 'yes' === get_option( 'woocommerce_gzd_delivery_time_disable_not_in_stock' ) && ! $this->is_in_stock() ) {

            /**
             * Filter to adjust product delivery time in case of a product is out of stock.
             *
             * @since 2.0.0
             *
             * @param string         $output The new delivery time text.
             * @param WC_GZD_Product $product The product object.
             * @param string         $html The original HTML output.
             */
            $html = apply_filters( 'woocommerce_germanized_delivery_time_out_of_stock_html', '', $this, $html );
        } elseif ( 'yes' === get_option( 'woocommerce_gzd_delivery_time_disable_backorder' ) && $this->is_on_backorder() ) {

            /**
             * Filter to adjust product delivery time in case of a product is on backorder.
             *
             * @since 2.0.0
             *
             * @param string         $output The new delivery time text.
             * @param WC_GZD_Product $product The product object.
             * @param string         $html The original HTML output.
             */
            $html = apply_filters( 'woocommerce_germanized_delivery_time_backorder_html', '', $this, $html );
        }

        return $html;
	}

	public function has_free_shipping() {

        /**
         * Filter that allows adjusting whether a product has free shipping option or not.
         *
         * @since 1.0.0
         *
         * @param bool           $has_free_shipping Has free shipping or not.
         * @param WC_GZD_Product $product The product object.
         */
		return ( apply_filters( 'woocommerce_germanized_product_has_free_shipping', ( $this->free_shipping === 'yes' ? true : false ), $this ) );
	}

	/**
	 * Returns the shipping costs notice html output
	 *  
	 * @return string 
	 */
	public function get_shipping_costs_html() {

        /**
         * Filter to optionally disable shipping costs info for a certain product.
         *
         * @since 1.0.0
         *
         * @param bool           $disable Whether to disable the shipping costs notice or not.
         * @param WC_GZD_Product $product The product object.
         */
		if ( apply_filters( 'woocommerce_germanized_hide_shipping_costs_text', false, $this ) ) {

            /**
             * Filter to adjust a product's disabled shipping costs notice.
             *
             * @since 1.0.0
             *
             * @param string         $output The output.
             * @param WC_GZD_Product $product The product object.
             */
			return apply_filters( 'woocommerce_germanized_disabled_shipping_text', '', $this );
        }
		
		return wc_gzd_get_shipping_costs_text( $this );
	}

}
?>