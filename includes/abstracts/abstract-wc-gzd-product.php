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
	 * @var null|WP_Term[]
	 */
	protected $delivery_times = null;

	protected $delivery_times_need_update = false;

	protected $warranty_attachment = false;

	protected $allergenic = null;

	protected $nutrients = null;

	protected $deposit_type = null;

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
		 * @param string $context
		 *
		 * @since 3.0.0
		 *
		 */
		return apply_filters( "woocommerce_gzd_get_product_{$prop}", $value, $this, $this->child, $context );
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

	public function get_warranty_attachment_id( $context = 'view' ) {
		return $this->get_prop( 'warranty_attachment_id', $context );
	}

	public function get_gtin( $context = 'view' ) {
		return $this->get_prop( 'ts_gtin', $context );
	}

	public function get_mpn( $context = 'view' ) {
		return $this->get_prop( 'ts_mpn', $context );
	}

	public function get_nutrient_ids( $context = 'view' ) {
		$nutrients = $this->get_prop( 'nutrient_ids', $context );

		return (array) $nutrients;
	}

	public function get_nutrients( $context = 'view' ) {
		if ( is_null( $this->nutrients ) ) {
			$this->nutrients = apply_filters( 'woocommerce_gzd_get_product_nutrients', array(), $this, $context );
		}

		return $this->nutrients;
	}

	public function has_nutrients() {
		return ! empty( $this->get_nutrients() );
	}

	public function get_nutrients_html( $context = 'view' ) {
		return apply_filters( 'woocommerce_gzd_get_product_nutrients_html', '', $this, $context );
	}

	public function get_allergen_ids( $context = 'view' ) {
		$nutrients = $this->get_prop( 'allergen_ids', $context );

		return array_filter( (array) $nutrients );
	}

	public function has_allergenic() {
		return ! empty( $this->get_allergenic() );
	}

	public function get_allergenic( $context = 'view' ) {
		if ( is_null( $this->allergenic ) ) {
			$this->allergenic = apply_filters( 'woocommerce_gzd_get_product_allergenic', array(), $this, $context );
		}

		return $this->allergenic;
	}

	public function get_formatted_allergenic( $context = 'view' ) {
		$allergenic = '';

		if ( $this->has_allergenic() ) {
			$allergenic_list = implode( ', ', $this->get_allergenic( $context ) );
			$allergenic      = sprintf( __( 'Contains: %1$s', 'woocommerce-germanized' ), $allergenic_list );
		}

		return apply_filters( 'woocommerce_gzd_get_product_formatted_allergenic', $allergenic, $this, $context );
	}

	public function get_ingredients( $context = 'view' ) {
		return $this->get_prop( 'ingredients', $context );
	}

	/**
	 * @return string
	 */
	public function get_formatted_ingredients( $context = 'view' ) {
		if ( $ingredients = $this->get_ingredients( $context ) ) {
			return wpautop( do_shortcode( wp_kses_post( htmlspecialchars_decode( $ingredients ) ) ) );
		}

		return '';
	}

	public function get_nutrient_reference_value( $context = 'view' ) {
		return $this->get_prop( 'nutrient_reference_value', $context );
	}

	public function get_nutri_score( $context = 'view' ) {
		return $this->get_prop( 'nutri_score', $context );
	}

	public function get_formatted_nutri_score() {
		$nutri_score = $this->get_nutri_score();

		if ( '' !== $nutri_score ) {
			$nutri_score = '<span title="' . sprintf( esc_html__( 'Nutri-Score %1$s', 'woocommerce-germanized' ), strtoupper( $nutri_score ) ) . '" aria-label="' . sprintf( esc_html__( 'Nutri-Score %1$s', 'woocommerce-germanized' ), strtoupper( $nutri_score ) ) . '" class="wc-gzd-nutri-score-value wc-gzd-nutri-score-value-' . esc_attr( $nutri_score ) . '">' . esc_html( strtoupper( $nutri_score ) ) . '</span>';
		}

		return apply_filters( 'woocommerce_gzd_product_formatted_nutri_score', $nutri_score, $this );
	}

	public function get_drained_weight( $context = 'view' ) {
		return $this->get_prop( 'drained_weight', $context );
	}

	public function get_net_filling_quantity( $context = 'view' ) {
		return $this->get_prop( 'net_filling_quantity', $context );
	}

	public function get_formatted_net_filling_quantity() {
		$quantity = $this->get_net_filling_quantity();

		if ( '' === $quantity ) {
			$quantity = $this->get_unit_product();
		}

		if ( '' !== $quantity ) {
			$unit     = apply_filters( 'woocommerce_gzd_product_net_filling_quantity_unit', $this->get_unit(), $this );
			$quantity = sprintf( '%1$s %2$s', wc_gzd_format_food_attribute_value( $quantity, array( 'attribute_type' => 'net_filling_quantity' ) ), $unit );
		}

		return apply_filters( 'woocommerce_gzd_product_formatted_net_filling_quantity', $quantity, $this );
	}

	public function get_formatted_drain_weight() {
		$weight = '';

		if ( '' !== $this->get_drained_weight() ) {
			$drain_weight_unit = apply_filters( 'woocommerce_gzd_drain_weight_unit', 'g' );
			$weight_in_g       = wc_get_weight( (float) $this->get_drained_weight(), $drain_weight_unit, get_option( 'woocommerce_weight_unit' ) );
			$weight            = sprintf( '%1$s %2$s', wc_gzd_format_food_attribute_value( $weight_in_g, array( 'attribute_type' => 'drained_weight' ) ), $drain_weight_unit );
		}

		return apply_filters( 'woocommerce_gzd_product_formatted_drain_weight', $weight, $this );
	}

	public function get_alcohol_content( $context = 'view' ) {
		$alcohol_content = $this->get_prop( 'alcohol_content', $context );

		if ( empty( $alcohol_content ) && 'view' === $alcohol_content ) {
			$alcohol_content = 0;
		}

		return $alcohol_content;
	}

	public function get_formatted_alcohol_content( $context = 'view' ) {
		return wc_gzd_format_alcohol_content( $this->get_alcohol_content( $context ) );
	}

	public function includes_alcohol( $context = 'view' ) {
		return apply_filters( 'woocommerce_gzd_product_includes_alcohol', ( (float) $this->get_alcohol_content( $context ) > 0 ), $this, $context );
	}

	public function get_food_distributor( $context = 'view' ) {
		return $this->get_prop( 'food_distributor', $context );
	}

	public function get_formatted_food_distributor( $context = 'view' ) {
		if ( $distributor = $this->get_food_distributor( $context ) ) {
			return wpautop( do_shortcode( wp_kses_post( htmlspecialchars_decode( $distributor ) ) ) );
		}

		return '';
	}

	public function get_food_place_of_origin( $context = 'view' ) {
		return $this->get_prop( 'food_place_of_origin', $context );
	}

	public function get_formatted_food_place_of_origin( $context = 'view' ) {
		if ( $origin = $this->get_food_place_of_origin( $context ) ) {
			return wpautop( do_shortcode( wp_kses_post( htmlspecialchars_decode( $origin ) ) ) );
		}

		return '';
	}

	public function get_food_description( $context = 'view' ) {
		return $this->get_prop( 'food_description', $context );
	}

	public function get_formatted_food_description( $context = 'view' ) {
		if ( $description = $this->get_food_description( $context ) ) {
			return wpautop( do_shortcode( wp_kses_post( htmlspecialchars_decode( $description ) ) ) );
		}

		return '';
	}

	public function get_deposit_type_term( $context = 'view' ) {
		if ( is_null( $this->deposit_type ) ) {
			$this->deposit_type = false;

			$type = $this->get_deposit_type( $context );

			if ( ! empty( $type ) ) {
				$this->deposit_type = WC_germanized()->deposit_types->get_deposit_type_term( $type );
			}
		}

		return $this->deposit_type;
	}

	public function get_deposit_type( $context = 'view' ) {
		return $this->get_prop( 'deposit_type', $context );
	}

	public function get_deposit_packaging_type( $context = 'view' ) {
		$returnable_type = false;

		if ( $this->has_deposit( $context ) && ( $term = $this->get_deposit_type_term( $context ) ) ) {
			$returnable_type = WC_germanized()->deposit_types->get_packaging_type( $term );
		}

		return apply_filters( 'woocommerce_gzd_product_deposit_packaging_type', $returnable_type );
	}

	public function get_deposit_packaging_type_title( $context = 'view' ) {
		$returnable_type_title = '';

		if ( $returnable_type = $this->get_deposit_packaging_type( $context ) ) {
			$returnable_type_title = WC_germanized()->deposit_types->get_packaging_type_title( $returnable_type );
		}

		return apply_filters( 'woocommerce_gzd_product_deposit_packaging_type_title', $returnable_type_title );
	}

	/**
	 * Returns the total deposit amount.
	 *
	 * @param string $tax_display
	 * @param string $context
	 *
	 * @return string formatted deposit amount
	 */
	public function get_deposit_amount( $context = 'view', $tax_display = '' ) {
		$tax_display_mode = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_shop' );
		$quantity         = 1;

		// Use the raw deposit amount and calculate taxes for the total deposit amount, not per unit
		$price = $this->get_deposit_amount_per_unit( 'edit', $tax_display );

		if ( $this->get_deposit_quantity() > 1 ) {
			$quantity = $this->get_deposit_quantity();
		}

		$amount = (float) $price * (float) $quantity;

		// Calculate taxes
		if ( 'view' === $context && $amount > 0 ) {
			$amount           = ( 'incl' === $tax_display_mode ) ? $this->get_deposit_amount_including_tax( 1, $amount ) : $this->get_deposit_amount_excluding_tax( 1, $amount );
			$shipping_country = $this->get_current_customer_shipping_country();

			if ( apply_filters( 'woocommerce_gzd_shipping_country_skips_deposit', false, $shipping_country ) ) {
				$amount = 0;
			}
		}

		return apply_filters( 'woocommerce_gzd_product_deposit_amount', $amount, $quantity, $this, $context, $tax_display );
	}

	/**
	 * Returns unit price including tax
	 *
	 * @param integer $qty
	 * @param string $price
	 *
	 * @return string  unit price including tax
	 */
	public function get_deposit_amount_including_tax( $qty = 1, $price = '' ) {
		$price = ( '' === $price ) ? $this->get_deposit_amount_per_unit( 'view', 'incl' ) : $price;

		/**
		 * Filter to adjust the deposit amount including tax.
		 *
		 * @param string $deposit_amount The calculated deposit amount.
		 * @param string $price The price passed.
		 * @param int $qty The quantity.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 3.9.0
		 */
		return apply_filters(
			'woocommerce_gzd_deposit_amount_including_tax',
			( empty( $price ) ) ? '' : wc_get_price_including_tax(
				$this->child,
				array(
					'price' => $price,
					'qty'   => $qty,
				)
			),
			$price,
			$qty,
			$this
		);
	}

	/**
	 * Returns deposit amount excluding tax
	 *
	 * @param integer $qty
	 * @param string $price
	 *
	 * @return string deposit amount excluding tax
	 */
	public function get_deposit_amount_excluding_tax( $qty = 1, $price = '' ) {
		$price = ( '' === $price ) ? $this->get_deposit_amount_per_unit( 'view', 'excl' ) : $price;

		/**
		 * Filter to adjust the deposit amount excluding tax.
		 *
		 * @param string $deposit_amount The calculated deposit amount.
		 * @param string $price The price passed.
		 * @param int $qty The quantity.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 3.9,0
		 *
		 */
		return apply_filters(
			'woocommerce_gzd_deposit_amount_excluding_tax',
			( empty( $price ) ) ? '' : wc_get_price_excluding_tax(
				$this->child,
				array(
					'price' => $price,
					'qty'   => $qty,
				)
			),
			$price,
			$qty,
			$this
		);
	}

	public function get_deposit_amount_per_unit( $context = 'view', $tax_display = '' ) {
		$amount = wc_format_decimal( 0 );

		if ( $term = $this->get_deposit_type_term( $context ) ) {
			$amount = WC_germanized()->deposit_types->get_deposit( $term );
		}

		$tax_display_mode = $tax_display ? $tax_display : get_option( 'woocommerce_tax_display_shop' );

		if ( 'view' === $context && $amount > 0 ) {
			$amount = ( 'incl' === $tax_display_mode ) ? $this->get_deposit_amount_including_tax( 1, $amount ) : $this->get_deposit_amount_excluding_tax( 1, $amount );
		}

		return apply_filters( 'woocommerce_gzd_product_deposit_amount_per_unit', $amount, $this, $context, $tax_display_mode );
	}

	public function get_deposit_quantity( $context = 'view' ) {
		$quantity = $this->get_prop( 'deposit_quantity', $context );

		if ( 'view' === $context && empty( $quantity ) ) {
			$quantity = 1;
		}

		return $quantity;
	}

	public function has_deposit( $context = 'view' ) {
		$has_deposit = $this->get_deposit_amount_per_unit() > 0;

		if ( 'view' === $context && ! $this->is_food() ) {
			$has_deposit = false;
		}

		return apply_filters( 'woocommerce_gzd_product_has_deposit', $has_deposit, $this, $context );
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

	public function get_defect_description( $context = 'view' ) {
		return $this->get_prop( 'defect_description', $context );
	}

	public function get_cart_description( $context = 'view' ) {
		return $this->get_mini_desc();
	}

	public function get_warranty_attachment( $context = 'view' ) {
		$warranty_attachment_id = $this->get_warranty_attachment_id( $context );

		if ( ! empty( $warranty_attachment_id ) ) {
			if ( $post = get_post( $warranty_attachment_id ) ) {
				$this->warranty_attachment = $post;

				return $this->warranty_attachment;
			}
		}

		return false;
	}

	public function get_warranty_file( $context = 'view' ) {
		if ( $attachment = $this->get_warranty_attachment( $context ) ) {
			return get_attached_file( $attachment->ID );
		}

		return false;
	}

	public function get_warranty_url( $context = 'view' ) {
		if ( $this->has_warranty( $context ) ) {
			return wp_get_attachment_url( $this->get_warranty_attachment_id() );
		}

		return false;
	}

	public function get_warranty_filename( $context = 'view' ) {
		if ( $file = $this->get_warranty_file( $context ) ) {
			return basename( $file );
		}

		return false;
	}

	public function has_warranty( $context = 'view' ) {
		return $this->get_warranty_attachment_id( $context ) && $this->get_warranty_attachment( $context ) ? true : false;
	}

	public function has_cart_description() {
		$desc = $this->get_cart_description();

		return ( ! empty( $desc ) ) ? true : false;
	}

	public function get_formatted_mini_desc( $context = 'view' ) {
		return $this->get_formatted_cart_description( $context );
	}

	public function get_formatted_cart_description( $context = 'view' ) {
		$desc = $this->get_cart_description( $context );

		if ( ! empty( $desc ) ) {
			return wpautop( do_shortcode( wp_kses_post( htmlspecialchars_decode( $desc ) ) ) );
		} else {
			return '';
		}
	}

	public function get_service( $context = 'view' ) {
		return wc_string_to_bool( $this->get_prop( 'service', $context ) );
	}

	public function is_service( $context = 'view' ) {
		return true === $this->get_service( $context );
	}

	/**
	 * This method refers to other services in terms of their VAT treatment.
	 * Services/virtual products may be treated differently.
	 *
	 * @see https://www.smartsteuer.de/online/lexikon/s/sonstige-leistung/
	 *
	 * @param $context
	 *
	 * @return boolean
	 */
	public function is_other_service( $context = 'view' ) {
		return apply_filters( 'woocommerce_gzd_product_is_other_service', ( $this->is_service() || $this->get_wc_product()->is_virtual() ) );
	}

	public function get_photovoltaic_system( $context = 'view' ) {
		return wc_string_to_bool( $this->get_prop( 'photovoltaic_system', $context ) );
	}

	public function is_photovoltaic_system( $context = 'view' ) {
		return true === $this->get_photovoltaic_system( $context );
	}

	public function get_used_good( $context = 'view' ) {
		$is_used_good = wc_string_to_bool( $this->get_prop( 'used_good', $context ) );

		if ( 'view' === $context && $this->is_differential_taxed( $context ) ) {
			$is_used_good = apply_filters( 'woocommerce_gzd_product_differential_taxed_is_used_good', true, $this );
		}

		return $is_used_good;
	}

	public function is_food( $context = 'view' ) {
		return $this->get_is_food( $context ) === true;
	}

	public function get_is_food( $context = 'view' ) {
		$is_food = wc_string_to_bool( $this->get_prop( 'is_food', $context ) );

		return $is_food;
	}

	public function is_used_good( $context = 'view' ) {
		return $this->get_used_good( $context ) === true;
	}

	public function get_defective_copy( $context = 'view' ) {
		return wc_string_to_bool( $this->get_prop( 'defective_copy', $context ) );
	}

	public function is_defective_copy( $context = 'view' ) {
		return $this->get_defective_copy( $context ) === true;
	}

	public function get_free_shipping( $context = 'view' ) {
		return wc_string_to_bool( $this->get_prop( 'free_shipping', $context ) );
	}

	public function has_free_shipping( $context = 'view' ) {
		return $this->get_free_shipping( $context ) === true;
	}

	public function get_differential_taxation( $context = 'view' ) {
		return wc_string_to_bool( $this->get_prop( 'differential_taxation', $context ) );
	}

	public function is_differential_taxed( $context = 'view' ) {
		return $this->get_differential_taxation( $context ) === true;
	}

	public function set_deposit_type( $deposit_type ) {
		$this->set_prop( 'deposit_type', $deposit_type );
		$this->deposit_type = null;
	}

	public function set_deposit_quantity( $quantity ) {
		$this->set_prop( 'deposit_quantity', empty( $quantity ) ? '' : absint( $quantity ) );
	}

	public function set_warranty_attachment_id( $id ) {
		$this->set_prop( 'warranty_attachment_id', ! empty( $id ) ? absint( $id ) : '' );
		$this->warranty_attachment = false;
	}

	public function set_gtin( $gtin ) {
		$this->set_prop( 'ts_gtin', $gtin );
	}

	public function set_mpn( $mpn ) {
		$this->set_prop( 'ts_mpn', $mpn );
	}

	public function set_nutrient_ids( $ids ) {
		$ids = (array) $ids;

		foreach ( $ids as $k => $value ) {
			if ( is_array( $value ) ) {
				$value = wp_parse_args(
					$value,
					array(
						'value'     => 0,
						'ref_value' => '',
					)
				);

				if ( '' === $value['value'] ) {
					unset( $ids[ $k ] );
				} else {
					$value['value']     = wc_format_decimal( $value['value'] );
					$value['ref_value'] = is_numeric( $value['ref_value'] ) ? wc_format_decimal( $value['ref_value'] ) : '';

					$ids[ $k ] = $value;
				}
			} elseif ( '' === $value ) {
				unset( $ids[ $k ] );
			} else {
				$ids[ $k ] = array(
					'value'     => wc_format_decimal( $value ),
					'ref_value' => '',
				);
			}
		}

		$this->set_prop( 'nutrient_ids', $ids );
	}

	public function set_nutrient_reference_value( $value ) {
		$this->set_prop( 'nutrient_reference_value', $value );
	}

	public function set_allergen_ids( $ids ) {
		$this->set_prop( 'allergen_ids', array_map( 'absint', array_filter( (array) $ids ) ) );

		$this->allergenic = null;
	}

	public function set_ingredients( $ingredients ) {
		$this->set_prop( 'ingredients', $ingredients );
	}

	public function set_nutri_score( $score ) {
		$this->set_prop( 'nutri_score', $score );
	}

	public function set_drained_weight( $weight ) {
		$this->set_prop( 'drained_weight', wc_format_decimal( $weight ) );
	}

	public function set_net_filling_quantity( $quantity ) {
		$this->set_prop( 'net_filling_quantity', wc_format_decimal( $quantity ) );
	}

	public function set_alcohol_content( $content ) {
		$this->set_prop( 'alcohol_content', wc_format_decimal( $content ) );
	}

	public function set_food_distributor( $distributor ) {
		$this->set_prop( 'food_distributor', $distributor );
	}

	public function set_food_place_of_origin( $place_of_origin ) {
		$this->set_prop( 'food_place_of_origin', $place_of_origin );
	}

	public function set_food_description( $description ) {
		$this->set_prop( 'food_description', $description );
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

	public function set_photovoltaic_system( $service ) {
		$this->set_prop( 'photovoltaic_system', wc_bool_to_string( $service ) );
	}

	public function set_defective_copy( $is_defective_copy ) {
		$this->set_prop( 'defective_copy', wc_bool_to_string( $is_defective_copy ) );
	}

	public function set_used_good( $is_used_good ) {
		$this->set_prop( 'used_good', wc_bool_to_string( $is_used_good ) );
	}

	public function set_is_food( $is_food ) {
		$this->set_prop( 'is_food', wc_bool_to_string( $is_food ) );
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

	public function set_defect_description( $desc ) {
		$this->set_prop( 'defect_description', $desc );
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
		 * @param array $args Arguments passed to the recalculation method.
		 *
		 * @since 1.9.1
		 *
		 */
		do_action( 'woocommerce_gzd_recalculated_unit_price', $this, $args );
	}

	public function needs_age_verification() {
		$min_age = $this->get_min_age();

		return ! empty( $min_age ) ? true : false;
	}

	public function has_min_age() {
		return $this->needs_age_verification();
	}

	public function has_nutrient( $id, $context = 'view' ) {
		$nutrients = $this->get_nutrient_ids( $context );

		return array_key_exists( $id, $nutrients );
	}

	public function get_nutrient_value( $id, $context = 'view' ) {
		$nutrient_value = '';

		if ( $nutrient = $this->get_nutrient( $id, $context ) ) {
			$nutrient_value = (float) $nutrient['value'];
		}

		$nutrient_value = apply_filters( 'woocommerce_gzd_product_nutrient_value', $nutrient_value, $id, $this, $context );

		if ( 'view' === $context ) {
			$nutrient_value = wc_gzd_format_food_attribute_value( $nutrient_value );
		}

		return $nutrient_value;
	}

	public function get_nutrient_reference( $id, $context = 'view' ) {
		$ref_value = '';

		if ( $nutrient = $this->get_nutrient( $id, $context ) ) {
			$ref_value = (float) $nutrient['ref_value'];
		}

		$ref_value = apply_filters( 'woocommerce_gzd_product_nutrient_reference', $ref_value, $id, $this, $context );

		if ( 'view' === $context ) {
			$ref_value = wc_gzd_format_food_attribute_value( $ref_value, array( 'attribute_type' => 'nutrient_reference' ) );
		}

		return $ref_value;
	}

	public function get_nutrient( $id, $context = 'view' ) {
		$id        = apply_filters( 'woocommerce_gzd_product_nutrient_value_term_id', $id, $this, $context );
		$nutrients = $this->get_nutrient_ids( $context );
		$nutrient  = false;

		if ( array_key_exists( $id, $nutrients ) && is_array( $nutrients[ $id ] ) ) {
			$nutrient = wp_parse_args(
				$nutrients[ $id ],
				array(
					'value'     => 0,
					'ref_value' => 0,
				)
			);

			$nutrient['value']     = (float) $nutrient['value'];
			$nutrient['ref_value'] = (float) $nutrient['ref_value'];
		}

		return apply_filters( 'woocommerce_gzd_product_nutrient', $nutrient, $id, $this, $context );
	}

	public function get_min_age( $context = 'view' ) {
		$product_min_age = $this->get_prop( 'min_age', $context );

		if ( 'view' === $context ) {
			// Force using parent product categories in case of variations
			$categories = wc_get_product_cat_ids( $this->child->get_parent_id() > 0 ? $this->child->get_parent_id() : $this->get_id() );

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
					'value' => apply_filters( 'woocommerce_attribute', wpautop( wptexturize( implode( ', ', $values ) ) ), $attribute->get_attribute(), $values ),
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
		 * @param bool $is_exception Whether it is an exception or not.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 1.8.5
		 */
		return apply_filters( 'woocommerce_gzd_product_virtual_vat_exception', ( ( 'yes' === get_option( 'woocommerce_gzd_enable_virtual_vat' ) || \Vendidero\EUTaxHelper\Helper::oss_procedure_is_enabled() ) && ( $this->is_downloadable() || $this->is_virtual() ) ? true : false ), $this );
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

		preg_match( '/<del.*>(.*?)<\\/del>/si', $price_html, $match_regular );
		preg_match( '/<ins.*>(.*?)<\\/ins>/si', $price_html, $match_sale );
		preg_match( '/<small .*>(.*?)<\\/small>/si', $price_html, $match_suffix );

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

	protected function is_doing_price_html_action() {
		return apply_filters( 'woocommerce_gzd_product_is_doing_price_html_action', doing_action( 'woocommerce_get_price_html' ), $this );
	}

	public function hide_shopmarks_due_to_missing_price() {
		$price_html_checked = true;

		/**
		 * Prevent infinite loops in case the shopmark is added via the price_html filter.
		 * Calling get_price_html during cart/checkout may cause side-effects (e.g. subtotal calculation in Measurement Plugin)
		 * within shopmarks - prevent calls here too.
		 */
		if ( ! $this->is_doing_price_html_action() && ! is_cart() && ! is_checkout() && apply_filters( 'woocommerce_gzd_shopmarks_empty_price_html_check_enabled', true, $this ) ) {
			$price_html_checked = ( '' === $this->child->get_price_html() );
		}

		$has_empty_price = apply_filters( 'woocommerce_gzd_product_misses_price', ( '' === $this->get_price() && $price_html_checked ), $this );

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
					$tax_notice = ( 'incl' === $tax_display_mode && ! $is_vat_exempt ? __( 'incl. VAT', 'woocommerce-germanized' ) : __( 'excl. VAT', 'woocommerce-germanized' ) );
				} else {
					$tax_notice = ( 'incl' === $tax_display_mode && ! $is_vat_exempt ? sprintf( __( 'incl. %s%% VAT', 'woocommerce-germanized' ), ( wc_gzd_format_tax_rate_percentage( $tax_rates[0]['rate'] ) ) ) : sprintf( __( 'excl. %s%% VAT', 'woocommerce-germanized' ), ( wc_gzd_format_tax_rate_percentage( $tax_rates[0]['rate'] ) ) ) );
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
		$price = ( '' === $price ) ? $this->get_unit_price() : $price;

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
		return apply_filters(
			'woocommerce_gzd_unit_price_including_tax',
			( empty( $price ) ) ? '' : wc_get_price_including_tax(
				$this->child,
				array(
					'price' => $price,
					'qty'   => $qty,
				)
			),
			$price,
			$qty,
			$this
		);
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
		$price = ( '' === $price ) ? $this->get_unit_price() : $price;

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
		return apply_filters(
			'woocommerce_gzd_unit_price_excluding_tax',
			( empty( $price ) ) ? '' : wc_get_price_excluding_tax(
				$this->child,
				array(
					'price' => $price,
					'qty'   => $qty,
				)
			),
			$price,
			$qty,
			$this
		);
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
	 * Returns the deposit time html output
	 *
	 * @return string
	 */
	public function get_deposit_amount_html( $context = 'view', $tax_display = '' ) {
		/**
		 * Filter that allows disabling the deposit text output for a certain product.
		 *
		 * @param bool $hide Whether to hide the output or not.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 3.9.0
		 *
		 */
		if ( apply_filters( 'woocommerce_gzd_hide_deposit_amount_text', false, $this ) ) {

			/**
			 * Filter to adjust the output of a disabled product deposit text.
			 *
			 * @param string $output The output.
			 * @param WC_GZD_Product $product The product object.
			 *
			 * @since 3.9.0
			 *
			 */
			return apply_filters( 'woocommerce_gzd_disabled_deposit_amount_text', '', $this );
		}

		$html = '';

		if ( $this->has_deposit() ) {
			$price_html = wc_price( $this->get_deposit_amount( 'view', $tax_display ) );
			$html       = wc_gzd_format_deposit_amount(
				$price_html,
				array(
					'type'            => $this->get_deposit_type( $context ),
					'quantity'        => $this->get_deposit_quantity( $context ),
					'packaging_type'  => $this->get_deposit_packaging_type( $context ),
					'amount_per_unit' => wc_price( $this->get_deposit_amount_per_unit( $context, $tax_display ) ),
				)
			);
		}

		/**
		 * Filter to adjust the product's deposit HTML output.
		 *
		 * @param string $html The deposit as HTML.
		 * @param WC_GZD_Product $product The product object.
		 *
		 * @since 3.9.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_deposit_amount_html', $html, $this, $tax_display );
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
			$html       = wc_gzd_format_unit_price( $price_html, $this->get_unit_html(), $this->get_unit_base_html(), wc_gzd_format_product_units_decimal( $this->get_unit_product() ) );
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
		return apply_filters( 'woocommerce_gzd_unit_price_html', $html, $this, $tax_display );
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
				'{product_units}' => wc_gzd_format_product_units_decimal( $this->get_unit_product() ),
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
	 * @return WP_Term[]
	 */
	public function get_delivery_times( $context = 'view' ) {
		if ( is_null( $this->delivery_times ) ) {
			$slugs        = $this->get_delivery_time_slugs( $context );
			$cached_terms = array();

			foreach ( $slugs as $slug ) {
				$term = WC_germanized()->delivery_times->get_delivery_time_term( $slug );

				if ( ! $term ) {
					continue;
				}

				$cached_terms[ $term->slug ] = $term;
			}

			$this->delivery_times = apply_filters( 'woocommerce_gzd_product_delivery_times', $cached_terms, $this, $this->child, $context );
		}

		return $this->delivery_times;
	}

	public function get_delivery_time_slugs( $context = 'view' ) {
		/**
		 * Normally (view context) we are using the term relationship model to retrieve
		 * the delivery times mapped to the product. While saving we are using the props model
		 * to enable saving the current object state.
		 */
		if ( 'save' === $context || $this->delivery_times_need_update() ) {
			$slugs = false;

			if ( $this->delivery_times_need_update() ) {
				$slugs            = array();
				$default_slug     = $this->get_default_delivery_time_slug( 'save' );
				$country_specific = array_values( array_unique( $this->get_country_specific_delivery_times( 'save' ) ) );

				if ( ! empty( $default_slug ) ) {
					$slugs = array_merge( array( $default_slug ), $slugs );
				}

				if ( ! empty( $country_specific ) ) {
					$slugs = array_merge( $country_specific, $slugs );
				}

				$slugs = array_unique( $slugs );
			}

			return $slugs;
		} else {
			$object_id = $this->get_wc_product()->get_id();
			$terms     = get_the_terms( $object_id, 'product_delivery_time' );

			if ( false === $terms || is_wp_error( $terms ) ) {
				return array();
			}

			return wp_list_pluck( $terms, 'slug' );
		}
	}

	protected function set_delivery_time_slugs( $slugs ) {
		$slugs = wc_gzd_get_valid_product_delivery_time_slugs( $slugs );

		$this->set_prop( 'delivery_time_slugs', array_unique( array_map( 'sanitize_title', $slugs ) ) );
		$this->delivery_times = null;
	}

	public function delivery_times_need_update() {
		return $this->delivery_times_need_update;
	}

	public function set_delivery_times_need_update( $need_update = true ) {
		$this->delivery_times_need_update = $need_update;
	}

	public function set_default_delivery_time_slug( $slug ) {
		$slug    = wc_gzd_get_valid_product_delivery_time_slugs( $slug );
		$current = $this->get_default_delivery_time_slug();

		$this->set_prop( 'default_delivery_time', $slug );

		if ( $current !== $slug ) {
			$this->set_delivery_times_need_update();
		}
	}

	protected function get_current_customer_shipping_country() {
		$country = false;

		if ( ( is_cart() || is_checkout() ) && WC()->cart && WC()->cart->get_customer() ) {
			$country = '' === WC()->cart->get_customer()->get_shipping_country() ? WC()->cart->get_customer()->get_billing_country() : WC()->cart->get_customer()->get_shipping_country();
		} elseif ( wc_gzd_is_admin_order_request() ) {
			if ( isset( $_POST['order_id'] ) && ( $order = wc_get_order( absint( $_POST['order_id'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( is_callable( array( $order, 'get_shipping_country' ) ) ) {
					$country = '' === $order->get_shipping_country() ? $order->get_billing_country() : $order->get_shipping_country();
				}
			}
		} elseif ( WC()->customer ) {
			$country = '' === WC()->customer->get_shipping_country() ? WC()->customer->get_billing_country() : WC()->customer->get_shipping_country();
		} elseif ( 'base' === get_option( 'woocommerce_default_customer_address' ) ) {
			$country = wc_gzd_get_base_country();
		}

		return empty( $country ) ? false : $country;
	}

	/**
	 * Returns the current products delivery time term without falling back to default term
	 *
	 * @return false|WP_Term false returns false if term does not exist otherwise returns term object
	 */
	public function get_delivery_time( $context = 'view' ) {
		/**
		 * Use the edit context to disable global setting fallback
		 */
		$delivery_time = $this->get_default_delivery_time( $context );

		if ( 'view' === $context ) {
			if ( $country = $this->get_current_customer_shipping_country() ) {
				$delivery_time_country = $this->get_delivery_time_by_country( $country );

				if ( $delivery_time_country ) {
					$delivery_time = $delivery_time_country;
				}
			}
		}

		return $delivery_time;
	}

	public function get_default_delivery_time_slug( $context = 'view' ) {
		return $this->get_prop( 'default_delivery_time', $context );
	}

	public function get_gzd_version( $context = 'view' ) {
		return $this->get_prop( 'gzd_version', $context );
	}

	/**
	 * @param string $context
	 *
	 * @return false|WP_Term
	 */
	public function get_default_delivery_time( $context = 'view' ) {
		$default_slug  = $this->get_default_delivery_time_slug( $context );
		$times         = $this->get_delivery_times( $context );
		$delivery_time = false;

		/**
		 * In case of older Germanized version which did not support multiple delivery times per product (e.g. per country)
		 * the default delivery time matches the first (only) delivery time set for the product.
		 *
		 * Newer versions include a separate meta field (_default_delivery_time) to indicate the default delivery time.
		 */
		if ( ! empty( $default_slug ) && array_key_exists( $default_slug, $times ) ) {
			$delivery_time = $times[ $default_slug ];
		} elseif ( ( empty( $this->get_gzd_version() ) || version_compare( $this->get_gzd_version(), '3.7.0', '<' ) ) && ! empty( $times ) ) {
			$delivery_time = array_values( $times )[0];
		}

		/**
		 * Use a global default delivery time from settings as a fallback in case no default delivery time was selected for this product.
		 */
		if ( 'view' === $context && ( empty( $delivery_time ) && ! $this->is_downloadable() ) ) {
			$eu_countries   = WC()->countries->get_european_union_countries();
			$base_country   = wc_gzd_get_base_country();
			$delivery_time  = false;
			$default_option = false;

			if ( ( $country = $this->get_current_customer_shipping_country() ) && $base_country !== $country ) {
				if ( in_array( $country, $eu_countries, true ) ) {
					$default_option = get_option( 'woocommerce_gzd_default_delivery_time_eu' );
				} elseif ( ! in_array( $country, $eu_countries, true ) ) {
					$default_option = get_option( 'woocommerce_gzd_default_delivery_time_third_countries' );
				}

				if ( $default_option ) {
					$delivery_time = WC_germanized()->delivery_times->get_delivery_time_term( $default_option, 'slug_fallback' );
				}
			}

			if ( ! $delivery_time && get_option( 'woocommerce_gzd_default_delivery_time' ) ) {
				$default_option = get_option( 'woocommerce_gzd_default_delivery_time' );
				$delivery_time  = WC_germanized()->delivery_times->get_delivery_time_term( $default_option, 'slug_fallback' );
			}
		}

		return $delivery_time;
	}

	public function get_country_specific_delivery_times( $context = 'view' ) {
		$countries = $this->get_prop( 'delivery_time_countries', $context );
		$countries = ( ! is_array( $countries ) || empty( $countries ) ) ? array() : $countries;

		ksort( $countries );

		return $countries;
	}

	public function set_gzd_version( $version ) {
		$this->set_prop( 'gzd_version', $version );
	}

	protected function is_valid_country_specific_delivery_time( $slug, $country ) {
		$default_slug = $this->get_default_delivery_time_slug( 'edit' );

		if ( $slug === $default_slug || wc_gzd_get_base_country() === $country ) {
			return false;
		}

		return true;
	}

	public function set_country_specific_delivery_times( $terms ) {
		$current = $this->get_country_specific_delivery_times();
		$terms   = wc_gzd_get_valid_product_delivery_time_slugs( $terms );

		foreach ( $terms as $country => $slug ) {
			if ( ! $this->is_valid_country_specific_delivery_time( $slug, $country ) ) {
				unset( $terms[ $country ] );
			}
		}

		ksort( $terms );

		$this->set_prop( 'delivery_time_countries', $terms );
		$this->delivery_times = null;

		if ( $current !== $terms ) {
			$this->set_delivery_times_need_update();
		}
	}

	public function get_delivery_time_by_country( $country = '', $context = 'view' ) {
		$countries          = $this->get_country_specific_delivery_times( $context );
		$times              = $this->get_delivery_times( $context );
		$delivery_time      = false;
		$eu_countries       = WC()->countries->get_european_union_countries();
		$base_country       = wc_gzd_get_base_country();
		$delivery_time_slug = false;

		/**
		 * EU-wide delivery times in case target country does not match base country
		 */
		if ( in_array( $country, $eu_countries, true ) && $base_country !== $country && array_key_exists( 'EU-wide', $countries ) ) {
			$delivery_time_slug = $countries['EU-wide'];
		}

		/**
		 * Non-EU-wide delivery times in case target country does not match base country
		 */
		if ( ! in_array( $country, $eu_countries, true ) && $base_country !== $country && array_key_exists( 'Non-EU-wide', $countries ) ) {
			$delivery_time_slug = $countries['Non-EU-wide'];
		}

		/**
		 * Allow overriding by custom country rules
		 */
		if ( array_key_exists( $country, $countries ) ) {
			$delivery_time_slug = $countries[ $country ];
		}

		/**
		 * Make sure delivery time is related to product
		 */
		if ( $delivery_time_slug && array_key_exists( $delivery_time_slug, $times ) ) {
			$delivery_time = $times[ $delivery_time_slug ];
		}

		if ( 'view' === $context && ! $delivery_time ) {
			$delivery_time = $this->get_default_delivery_time( $context );
		}

		return $delivery_time;
	}

	/**
	 * Returns current product's delivery time term. If none has been set and a default delivery time has been set, returns that instead.
	 *
	 * @return WP_Term|false
	 */
	public function get_delivery_time_term( $context = 'view' ) {
		$delivery_time = $this->get_delivery_time( $context );

		return ( ! is_wp_error( $delivery_time ) && ! empty( $delivery_time ) ) ? $delivery_time : false;
	}

	public function get_delivery_time_name( $context = 'view' ) {
		if ( $term = $this->get_delivery_time( $context ) ) {
			return $term->name;
		}

		return '';
	}

	/**
	 * Returns the delivery time html output
	 *
	 * @return string
	 */
	public function get_delivery_time_html( $context = 'view' ) {
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

		if ( $this->get_delivery_time( $context ) ) {
			$html = $this->get_delivery_time_name( $context );
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
			$delivery_time_str = get_option( 'woocommerce_gzd_delivery_time_text' );

			$replacements = array(
				'{delivery_time}' => $html,
			);

			if ( strstr( $delivery_time_str, '{stock_status}' ) ) {
				$replacements['{stock_status}'] = str_replace( array( '<p ', '</p>' ), array( '<span ', '</span>' ), wc_get_stock_html( $this->child ) );
			}

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
			$html = apply_filters(
				'woocommerce_germanized_delivery_time_html',
				wc_gzd_replace_label_shortcodes( $delivery_time_str, $replacements ),
				$delivery_time_str,
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
	 * Returns the defect description html output
	 *
	 * @return string
	 */
	public function get_formatted_defect_description( $context = 'view' ) {
		if ( $this->is_defective_copy( $context ) ) {
			return apply_filters( 'woocommerce_gzd_defect_description', wpautop( do_shortcode( wp_kses_post( htmlspecialchars_decode( $this->get_defect_description( $context ) ) ) ) ) );
		}

		return '';
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

	public function save() {
		/**
		 * Update delivery time term slugs if they have been explicitly set during the
		 * save request.
		 */
		$slugs = $this->get_delivery_time_slugs( 'save' );
		$id    = false;

		if ( false !== $slugs ) {
			$this->set_delivery_times_need_update( false );

			$id = $this->child->save();
		}

		if ( false !== $slugs && $id ) {
			$slugs = array_unique( array_map( 'sanitize_title', $slugs ) );

			if ( empty( $slugs ) ) {
				wp_delete_object_term_relationships( $id, 'product_delivery_time' );
			} else {
				wp_set_post_terms( $id, $slugs, 'product_delivery_time', false );
			}

			$this->delivery_times = null;
		}

		/**
		 * Update deposit type term relationships
		 */
		if ( $deposit_type = $this->get_deposit_type_term( 'edit' ) ) {
			wp_set_post_terms( $this->get_wc_product()->get_id(), array( $deposit_type->slug ), 'product_deposit_type', false );
		} else {
			wp_delete_object_term_relationships( $this->get_wc_product()->get_id(), 'product_deposit_type' );
		}
	}
}


