<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * WooCommerce Germanized Abstract Product
 *
 * The WC_GZD_Product Class is used to offer additional functionality for every product type.
 *
 * @class        WC_GZD_Product
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_Product {

	/**
	 * The actual Product object (e.g. simple, variable)
	 * @var WC_Product
	 */
	protected $child;

	/**
	 * Construct new WC_GZD_Product
	 *
	 * @param WC_Product $product
	 */
	public function __construct( $product ) {
		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		$this->child = $product;
	}

	public function get_wc_product() {
		return $this->child;
	}

	protected function get_prop( $prop, $context = 'view' ) {
		$meta_key = substr( $prop, 0, 1 ) !== '_' ? '_' . $prop : $prop;
		$value    = $this->child->get_meta( $meta_key, true, $context );

		/**
		 * Filter to adjust a certain product property e.g. unit_price.
		 *
		 * The dynamic portion of the hook name, `$prop` refers to the product property e.g. unit_price.
		 *
		 * @param mixed $value The property value.
		 * @param WC_GZD_Product $gzd_product The GZD product instance.
		 * @param WC_Product $product The product instance.
		 *
		 * @since 3.0.0
		 *
		 */
		return apply_filters( "woocommerce_gzd_get_product_{$prop}", $value, $this, $this->child );
	}

	protected function set_prop( $prop, $value ) {
		$meta_key = substr( $prop, 0, 1 ) !== '_' ? '_' . $prop : $prop;

		$this->child->update_meta_data( $meta_key, $value );
	}

	public function __call( $method, $args ) {
		if ( method_exists( $this->child, $method ) ) {
			return call_user_func_array( array( $this->child, $method ), $args );
		}

		return false;
	}

	public function get_unit( $context = 'view' ) {
		return $this->get_prop( 'unit', $context );
	}

	public function get_unit_base( $context = 'view' ) {
		return $this->get_prop( 'unit_base', $context );
	}

	public function get_unit_product( $context = 'view' ) {
		return $this->get_prop( 'unit_product', $context );
	}

	public function get_unit_price_regular( $context = 'view' ) {
		return $this->get_prop( 'unit_price_regular', $context );
	}

	public function get_unit_price( $context = 'view' ) {
		return $this->get_prop( 'unit_price', $context );
	}

	public function get_unit_price_sale( $context = 'view' ) {
		return $this->get_prop( 'unit_price_sale', $context );
	}

	public function get_unit_price_auto( $context = 'view' ) {
		return wc_string_to_bool( $this->get_prop( 'unit_price_auto', $context ) );
	}

	public function is_unit_price_auto( $context = 'view' ) {
		return $this->get_unit_price_auto( $context ) === true;
	}

	public function get_sale_price_label( $context = 'view' ) {
		$label = $this->get_prop( 'sale_price_label', $context );

		if ( 'view' === $context && empty( $label ) ) {
			$label = get_option( 'woocommerce_gzd_default_sale_price_label', '' );
		}

		return $label;
	}

	public function get_sale_price_regular_label( $context = 'view' ) {
		$label = $this->get_prop( 'sale_price_regular_label', $context );

		if ( 'view' === $context && empty( $label ) ) {
			$label = get_option( 'woocommerce_gzd_default_sale_price_regular_label', '' );
		}

		return $label;
	}

	public function get_mini_desc( $context = 'view' ) {
		return $this->get_prop( 'mini_desc', $context );
	}

	public function get_cart_description( $context = 'view' ) {
		return $this->get_mini_desc();
	}

	public function has_cart_description() {
		$desc = $this->get_cart_description();

		return ( ! empty( $desc ) ) ? true : false;
	}

	public function get_formatted_cart_description() {
		$desc = $this->get_cart_description();

		if ( ! empty( $desc ) ) {
			return wpautop( htmlspecialchars_decode( $desc ) );
		} else {
			return '';
		}
	}

	public function get_service( $context = 'view' ) {
		return wc_string_to_bool( $this->get_prop( 'service', $context ) );
	}

	public function is_service( $context = 'view' ) {
		return $this->get_service() === true;
	}

	public function get_free_shipping( $context = 'view' ) {
		return wc_string_to_bool( $this->get_prop( 'free_shipping', $context ) );
	}

	public function has_free_shipping( $context = 'view' ) {
		return $this->get_free_shipping() === true;
	}

	public function get_differential_taxation( $context = 'view' ) {
		return wc_string_to_bool( $this->get_prop( 'differential_taxation', $context ) );
	}

	public function is_differential_taxed( $context = 'view' ) {
		return $this->get_differential_taxation() === true;
	}

	public function set_unit_price( $price ) {
		$this->set_prop( 'unit_price', wc_format_decimal( $price ) );
	}

	public function set_unit_price_regular( $price ) {
		$this->set_prop( 'unit_price_regular', wc_format_decimal( $price ) );
	}

	public function set_unit_price_sale( $price ) {
		$this->set_prop( 'unit_price_sale', wc_format_decimal( $price ) );
	}

	public function set_unit( $unit ) {
		$this->set_prop( 'unit', $unit );
	}

	public function set_unit_base( $base ) {
		$this->set_prop( 'unit_base', '' === $base ? '' : wc_format_decimal( $base ) );
	}

	public function set_unit_product( $product ) {
		$this->set_prop( 'unit_product', '' === $product ? '' : wc_format_decimal( $product ) );
	}

	public function set_unit_price_auto( $auto ) {
		$this->set_prop( 'unit_price_auto', wc_bool_to_string( $auto ) );
	}

	public function set_service( $service ) {
		$this->set_prop( 'service', wc_bool_to_string( $service ) );
	}

	public function set_free_shipping( $shipping ) {
		$this->set_prop( 'free_shipping', wc_bool_to_string( $shipping ) );
	}

	public function set_differential_taxation( $taxation ) {
		$this->set_prop( 'differential_taxation', wc_bool_to_string( $taxation ) );
	}

	public function set_sale_price_label( $label ) {
		$this->set_prop( 'sale_price_label', $label );
	}

	public function set_sale_price_regular_label( $label ) {
		$this->set_prop( 'sale_price_regular_label', $label );
	}

	public function set_mini_desc( $desc ) {
		$this->set_prop( 'mini_desc', $desc );
	}

	public function set_cart_description( $desc ) {
		$this->set_mini_desc( $desc );
	}

	public function recalculate_unit_price( $args = array() ) {
		$prices = wc_gzd_recalculate_unit_price( $args, $this );

		if ( empty( $prices ) ) {
			return;
		}

		if ( isset( $args['base'] ) && ! empty( $args['base'] ) ) {
			$this->set_unit_base( $args['base'] );
		}

		$this->set_unit_price_regular( $prices['regular'] );
		$this->set_unit_price_sale( $prices['sale'] );
		$this->set_unit_price( $prices['unit'] );

		/**
		 * Recalculated unit price.
		 *
		 * Executes whenever the unit price is recalculated.
		 *
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.9.1
		 *
		 */
		do_action( 'woocommerce_gzd_recalculated_unit_price', $this );
	}

	public function needs_age_verification() {
		$min_age = $this->get_min_age();

		return ! empty( $min_age ) ? true : false;
	}

	public function has_min_age() {
		return $this->needs_age_verification();
	}

	public function get_min_age( $context = 'view' ) {
		$product_min_age = $this->get_prop( 'min_age', $context );

		if ( 'view' === $context ) {
			$categories = wc_get_product_cat_ids( $this->get_id() );

			// Use product category age as fallback
			if ( empty( $product_min_age ) ) {

				foreach ( $categories as $category ) {
					if ( $category_age = get_term_meta( $category, 'age_verification', true ) ) {
						$category_age = absint( $category_age );

						if ( ! empty( $category_age ) ) {
							$product_min_age = $category_age;
						}
					}
				}
			}

			// Use global age as fallback
			if ( empty( $product_min_age ) ) {
				if ( $checkbox = wc_gzd_get_legal_checkbox( 'age_verification' ) ) {

					if ( $checkbox->is_enabled() && $checkbox->get_option( 'min_age' ) ) {
						$product_min_age = $checkbox->get_option( 'min_age' );

						// Fix -1 option
						if ( ! is_numeric( $product_min_age ) || '-1' === $product_min_age ) {
							$product_min_age = '';
						}
					}
				}
			}

			/**
			 * Filter that allows adjusting a product's age verification min age.
			 *
			 * @param string $min_age The minimum age.
			 * @param WC_GZD_Product $product The product object.
			 *
			 * @since 1.0.0
			 *
			 */
			return apply_filters( 'woocommerce_gzd_product_age_verification_min_age', $product_min_age, $this, $context );
		}

		return $product_min_age;
	}

	public function set_min_age( $min_age ) {
		$this->set_prop( 'min_age', is_numeric( $min_age ) ? absint( $min_age ) : '' );
	}

	private function attribute_exists( $key, $item_data ) {
		foreach ( $item_data as $item_data_key => $data ) {
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

			foreach ( $attributes as $name => $value ) {
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

		foreach ( $attributes as $attribute ) {
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
						 * @param bool $enable Set to `true` to enable clickable checkout attributes.
						 *
						 * @since 2.0.0
						 *
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
		 * @param array $item_data The attribute data.
		 * @param WC_Product $product The product object.
		 *
		 * @since 2.2.9
		 *
		 */
		return apply_filters( 'woocommerce_gzd_product_checkout_attributes', $item_data, $this->child );
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
		 * @param bool $is_exception Whether it is a exception or not.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.8.5
		 *
		 */
		return apply_filters( 'woocommerce_gzd_product_virtual_vat_exception', ( ( get_option( 'woocommerce_gzd_enable_virtual_vat' ) === 'yes' ) && ( $this->is_downloadable() || $this->is_virtual() ) ? true : false ), $this );
	}

	public function add_labels_to_price_html( $price_html ) {
		$org_price_html = $price_html;

		if ( ! $this->child->is_on_sale() ) {
			return $price_html;
		}

		$sale_label         = $this->get_sale_price_label_name();
		$sale_regular_label = $this->get_sale_price_regular_label_name();

		// Do not manipulate if there is no label to be added.
		if ( empty( $sale_label ) && empty( $sale_regular_label ) ) {
			return $price_html;
		}

		preg_match( "/<del.*>(.*?)<\\/del>/si", $price_html, $match_regular );
		preg_match( "/<ins.*>(.*?)<\\/ins>/si", $price_html, $match_sale );
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
		 * @param string $html The new price containing labels.
		 * @param string $old_price The old price.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.8.5
		 *
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
		 * @param string $price The HTML price range.
		 * @param string $from The from price.
		 * @param string $to The to price.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_germanized_get_price_html_from_to', $price, $from, $to, $this );
	}

	public function hide_shopmarks_due_to_missing_price() {
		$has_empty_price = apply_filters( 'woocommerce_gzd_product_misses_price', ( '' === $this->get_price() && '' === $this->child->get_price_html() ), $this );

		return apply_filters( 'woocommerce_gzd_product_hide_shopmarks_empty_price', true, $this ) && $has_empty_price;
	}

	/**
	 * Gets a product's tax description (if is taxable)
	 *
	 * @return mixed string if is taxable else returns false
	 */
	public function get_tax_info() {
		$tax_notice    = false;
		$is_vat_exempt = ( ! empty( WC()->customer ) ? WC()->customer->is_vat_exempt() : false );

		if ( $this->hide_shopmarks_due_to_missing_price() ) {
			return false;
		}

		if ( $this->child->is_taxable() || $this->is_differential_taxed() ) {

			$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
			$tax_rates        = WC_Tax::get_rates( $this->child->get_tax_class() );

			if ( ! empty( $tax_rates ) ) {

				$tax_rates = array_values( $tax_rates );

				// If is variable or is virtual vat exception dont show exact tax rate
				if ( $this->is_virtual_vat_exception() || $this->child->is_type( 'variable' ) || $this->child->is_type( 'grouped' ) || get_option( 'woocommerce_gzd_hide_tax_rate_shop' ) === 'yes' ) {
					$tax_notice = ( $tax_display_mode == 'incl' && ! $is_vat_exempt ? __( 'incl. VAT', 'woocommerce-germanized' ) : __( 'excl. VAT', 'woocommerce-germanized' ) );
				} else {
					$tax_notice = ( $tax_display_mode == 'incl' && ! $is_vat_exempt ? sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), ( wc_gzd_format_tax_rate_percentage( $tax_rates[0]['rate'] ) ) ) : sprintf( __( 'excl. %s%% VAT', 'woocommerce-germanized' ), ( wc_gzd_format_tax_rate_percentage( $tax_rates[0]['rate'] ) ) ) );
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
		 * @param string $tax_notice The tax notice.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_product_tax_info', $tax_notice, $this );
	}

	/**
	 * Checks whether current Product has a unit price
	 *
	 * @return boolean
	 */
	public function has_unit() {

		if ( $this->get_unit() !== '' && $this->get_unit_price_regular() > 0 && $this->get_unit_base() !== '' ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns unit base html
	 *
	 * @return string
	 */
	public function get_unit_base_html() {
		return wc_gzd_format_unit_base( $this->get_unit_base() );
	}

	public function get_unit_html() {
		return wc_gzd_format_unit( $this->get_unit_name() );
	}

	public function get_unit_term( $context = 'view' ) {
		$unit = $this->get_unit( $context );

		if ( ! empty( $unit ) ) {
			return WC_germanized()->units->get_unit_term( $unit );
		}

		return false;
	}

	public function get_unit_name( $context = 'view' ) {
		if ( $term = $this->get_unit_term( $context ) ) {
			return $term->name;
		}

		return '';
	}

	public function get_sale_price_label_term( $context = 'view' ) {
		$label = $this->get_sale_price_label( $context );

		if ( ! empty( $label ) ) {
			return WC_germanized()->price_labels->get_label_term( $label );
		}

		return false;
	}

	public function get_sale_price_label_name( $context = 'view' ) {
		if ( $term = $this->get_sale_price_label_term( $context ) ) {
			return $term->name;
		}

		return '';
	}

	public function get_sale_price_regular_label_term( $context = 'view' ) {
		$label = $this->get_sale_price_regular_label( $context );

		if ( ! empty( $label ) ) {
			return WC_germanized()->price_labels->get_label_term( $label );
		}

		return false;
	}

	public function get_sale_price_regular_label_name( $context = 'view' ) {
		if ( $term = $this->get_sale_price_regular_label_term( $context ) ) {
			return $term->name;
		}

		return '';
	}

	/**
	 * Returns the unit price (if is sale then return sale price)
	 *
	 * @param integer $qty
	 * @param string $price
	 *
	 * @return string  formatted unit price
	 */
	public function get_formatted_unit_price( $qty = 1, $price = '', $tax_display = '' ) {
		/**
		 * Before retrieving unit price.
		 *
		 * Fires before the product unit price is retrieved.
		 *
		 * @param WC_GZD_Product $this The product object.
		 * @param string $price Optionally pass the price.
		 * @param int $qty The product quantity.
		 *
		 * @since 1.0.0
		 *
		 */
		do_action( 'woocommerce_gzd_before_get_unit_price', $this, $price, $qty );

		$tax_display_mode = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_shop' );

		return ( 'incl' === $tax_display_mode ) ? $this->get_unit_price_including_tax( $qty, $price ) : $this->get_unit_price_excluding_tax( $qty, $price );
	}

	/**
	 * Returns unit price including tax
	 *
	 * @param integer $qty
	 * @param string $price
	 *
	 * @return string  unit price including tax
	 */
	public function get_unit_price_including_tax( $qty = 1, $price = '' ) {
		$price = ( $price == '' ) ? $this->get_unit_price() : $price;

		/**
		 * Filter to adjust the unit price including tax.
		 *
		 * @param string $unit_price The calculated unit price.
		 * @param string $price The price passed.
		 * @param int $qty The quantity.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_unit_price_including_tax', ( empty( $price ) ) ? '' : wc_get_price_including_tax( $this->child, array(
			'price' => $price,
			'qty'   => $qty
		) ), $price, $qty, $this );
	}

	/**
	 * Returns unit price excluding tax
	 *
	 * @param integer $qty
	 * @param string $price
	 *
	 * @return string  unit price excluding tax
	 */
	public function get_unit_price_excluding_tax( $qty = 1, $price = '' ) {
		$price = ( $price == '' ) ? $this->get_unit_price() : $price;

		/**
		 * Filter to adjust the unit price excluding tax.
		 *
		 * @param string $unit_price The calculated unit price.
		 * @param string $price The price passed.
		 * @param int $qty The quantity.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_unit_price_excluding_tax', ( empty( $price ) ) ? '' : wc_get_price_excluding_tax( $this->child, array(
			'price' => $price,
			'qty'   => $qty
		) ), $price, $qty, $this );
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
		 * @param bool $on_sale Whether the product is on sale or not.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_product_is_on_unit_sale', ( $this->get_unit_price_sale() !== $this->get_unit_price_regular() && $this->get_unit_price_sale() === $this->get_unit_price() ), $this );
	}

	/**
	 * Returns unit price html output
	 *
	 * @return string
	 */
	public function get_unit_price_html( $show_sale = true, $tax_display = '' ) {
		/**
		 * Filter that allows disabling the unit price output for a certain product.
		 *
		 * @param bool $hide Whether to hide the output or not.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 *
		 */
		if ( apply_filters( 'woocommerce_gzd_hide_unit_text', false, $this ) ) {

			/**
			 * Filter to adjust the output of a disabled product unit price.
			 *
			 * @param string $output The output.
			 * @param WC_GZD_Product $product The product object.
			 *
			 * @since 1.0.0
			 *
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
			 * @param WC_GZD_Product $this The product object.
			 *
			 * @since 1.0.0
			 *
			 */
			do_action( 'woocommerce_gzd_before_get_unit_price_html', $this );

			$display_price         = $this->get_formatted_unit_price( 1, '', $tax_display );
			$display_regular_price = $this->get_formatted_unit_price( 1, $this->get_unit_price_regular(), $tax_display );
			$display_sale_price    = $this->get_formatted_unit_price( 1, $this->get_unit_price_sale(), $tax_display );

			$price_html = ( ( $this->is_on_unit_sale() && $show_sale ) ? $this->get_price_html_from_to( $display_regular_price, $display_sale_price, false ) : wc_price( $display_price ) );
			$html       = wc_gzd_format_unit_price( $price_html, $this->get_unit_html(), $this->get_unit_base_html() );
		}

		/**
		 * Filter to adjust the product's unit price HTML output.
		 *
		 * @param string $html The unit price as HTML.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_unit_price_html', $html, $this );
	}

	public function is_unit_price_calculated_automatically() {
		return $this->is_unit_price_auto();
	}

	public function has_unit_product() {
		$products = $this->get_unit_product();

		return ( $products && ! empty( $products ) && $this->get_unit() );
	}

	/**
	 * Formats the amount of product units
	 *
	 * @return string
	 */
	public function get_unit_product_html() {

		/**
		 * Filter that allows disabling product units output for a specific product.
		 *
		 * @param bool $disable Whether to disable or not.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 *
		 */
		if ( apply_filters( 'woocommerce_gzd_hide_product_units_text', false, $this ) ) {

			/**
			 * Filter that allows adjusting the disabled product units output.
			 *
			 * @param string $notice The output.
			 * @param WC_GZD_Product $product The product object.
			 *
			 * @since 1.0.0
			 *
			 */
			return apply_filters( 'woocommerce_germanized_disabled_product_units_text', '', $this );
		}

		$html = '';
		$text = get_option( 'woocommerce_gzd_product_units_text' );

		if ( $this->has_unit_product() ) {
			$replacements = array(
				'{product_units}' => str_replace( '.', ',', $this->get_unit_product() ),
				'{unit}'          => $this->get_unit_html(),
				'{unit_price}'    => $this->get_unit_price_html(),
			);

			$html = wc_gzd_replace_label_shortcodes( $text, $replacements );
		}

		/**
		 * Filter to adjust the product units HTML output.
		 *
		 * @param string $html The HTML output.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_product_units_html', $html, $this );
	}

	/**
	 * Returns the current products delivery time term without falling back to default term
	 *
	 * @return bool|object false returns false if term does not exist otherwise returns term object
	 */
	public function get_delivery_time( $context = 'view' ) {
		$terms = get_the_terms( $this->child->get_id(), 'product_delivery_time' );

		if ( 'view' === $context && ( empty( $terms ) && $this->child->is_type( 'variation' ) ) ) {
			$parent_terms = get_the_terms( $this->child->get_parent_id(), 'product_delivery_time' );

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
	 * @return WP_Term|false
	 */
	public function get_delivery_time_term( $context = 'view' ) {
		$delivery_time = $this->get_delivery_time();

		if ( 'view' === $context && ( empty( $delivery_time ) && get_option( 'woocommerce_gzd_default_delivery_time' ) && ! $this->is_downloadable() ) ) {

			$delivery_time = array( get_term_by( 'id', get_option( 'woocommerce_gzd_default_delivery_time' ), 'product_delivery_time' ) );

			if ( is_array( $delivery_time ) ) {
				array_values( $delivery_time );
				$delivery_time = $delivery_time[0];
			}
		}

		return ( ! is_wp_error( $delivery_time ) && ! empty( $delivery_time ) ) ? $delivery_time : false;
	}

	public function get_delivery_time_name( $context = 'view' ) {
		if ( $term = $this->get_delivery_time_term( $context ) ) {
			return $term->name;
		}

		return '';
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
		 * @param bool $hide Whether to hide delivery time or not.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 *
		 */
		if ( apply_filters( 'woocommerce_germanized_hide_delivery_time_text', false, $this ) ) {

			/**
			 * Filter to adjust disabled product delivery time output.
			 *
			 * @param string $output The output.
			 * @param WC_GZD_Product $product The product object.
			 *
			 * @since 1.0.0
			 *
			 */
			return apply_filters( 'woocommerce_germanized_disabled_delivery_time_text', '', $this );
		} elseif ( $this->hide_shopmarks_due_to_missing_price() ) {
			return '';
		}

		if ( $this->get_delivery_time_term() ) {
			$html = $this->get_delivery_time_name();
		} else {
			/**
			 * Filter to adjust empty delivery time text.
			 *
			 * @param string $text The delivery time text.
			 * @param WC_GZD_Product $product The product object.
			 *
			 * @since 1.0.0
			 *
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
			 * @param string $html The notice.
			 * @param string $option The placeholder option.
			 * @param string $html_org The HTML before replacement.
			 * @param WC_GZD_Product $product The product object.
			 *
			 * @since 1.0.0
			 *
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
		if ( 'yes' === get_option( 'woocommerce_gzd_delivery_time_disable_not_in_stock' ) && ! $this->child->is_in_stock() ) {

			/**
			 * Filter to adjust product delivery time in case of a product is out of stock.
			 *
			 * @param string $output The new delivery time text.
			 * @param WC_GZD_Product $product The product object.
			 * @param string $html The original HTML output.
			 *
			 * @since 2.0.0
			 *
			 */
			$html = apply_filters( 'woocommerce_germanized_delivery_time_out_of_stock_html', '', $this, $html );
		} elseif ( 'yes' === get_option( 'woocommerce_gzd_delivery_time_disable_backorder' ) && $this->child->is_on_backorder() ) {

			/**
			 * Filter to adjust product delivery time in case of a product is on backorder.
			 *
			 * @param string $output The new delivery time text.
			 * @param WC_GZD_Product $product The product object.
			 * @param string $html The original HTML output.
			 *
			 * @since 2.0.0
			 *
			 */
			$html = apply_filters( 'woocommerce_germanized_delivery_time_backorder_html', '', $this, $html );
		}

		/**
		 * Filter to adjust product delivery time html output.
		 *
		 * @param string $html The delivery time html.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 3.1.12
		 */
		return apply_filters( 'woocommerce_gzd_product_delivery_time_html', $html, $this );
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
		 * @param bool $disable Whether to disable the shipping costs notice or not.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.0.0
		 *
		 */
		if ( apply_filters( 'woocommerce_germanized_hide_shipping_costs_text', false, $this ) ) {

			/**
			 * Filter to adjust a product's disabled shipping costs notice.
			 *
			 * @param string $output The output.
			 * @param WC_GZD_Product $product The product object.
			 *
			 * @since 1.0.0
			 *
			 */
			return apply_filters( 'woocommerce_germanized_disabled_shipping_text', '', $this );
		}

		if ( $this->hide_shopmarks_due_to_missing_price() ) {
			return '';
		}

		return wc_gzd_get_shipping_costs_text( $this );
	}
}

?>