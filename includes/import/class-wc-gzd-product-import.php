<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Product_Import {

	public $columns = array();

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized' ), '1.0' );
	}

	public function __construct() {
		add_filter( 'woocommerce_csv_product_import_mapping_options', array( $this, 'set_columns' ), 10, 2 );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns', array( $this, 'set_mappings' ), 10 );
		add_filter( 'woocommerce_csv_product_import_mapping_special_columns', array( $this, 'set_special_columns' ), 10 );
		add_filter( 'woocommerce_product_import_pre_insert_product_object', array( $this, 'import' ), 10, 2 );
		add_filter( 'woocommerce_product_importer_parsed_data', array( $this, 'parse_data' ), 10, 1 );
	}

	public function get_columns() {
		return WC_GZD_Product_Export::instance()->get_columns();
	}

	public function set_special_columns( $columns ) {
		$columns[ __( 'Delivery Time: %s', 'woocommerce-germanized' ) ] = 'delivery_time:';
		$columns[ __( 'Nutrients: %s', 'woocommerce-germanized' ) ]     = 'nutrients:';

		return $columns;
	}

	public function get_formatting_callbacks() {

		/**
		 * Filter that allows adjusting product import formatting callbacks
		 * for Germanized product data.
		 *
		 * @param array $callbacks Key => value array containing meta keys and callback functions.
		 *
		 * @since 1.8.5
		 *
		 */
		return apply_filters(
			'woocommerce_gzd_product_import_formatting_callbacks',
			array(
				'mini_desc'                => array( $this, 'parse_html_field' ),
				'defect_description'       => array( $this, 'parse_html_field' ),
				'unit_price_regular'       => 'wc_format_decimal',
				'unit_price_sale'          => 'wc_format_decimal',
				'unit_base'                => 'wc_format_decimal',
				'unit_product'             => 'wc_format_decimal',
				'unit_price_auto'          => array( $this, 'parse_bool_str' ),
				'service'                  => array( $this, 'parse_bool_str' ),
				'used_good'                => array( $this, 'parse_bool_str' ),
				'defective_copy'           => array( $this, 'parse_bool_str' ),
				'photovoltaic_system'      => array( $this, 'parse_bool_str' ),
				'differential_taxation'    => array( $this, 'parse_bool_str' ),
				'free_shipping'            => array( $this, 'parse_bool_str' ),
				'delivery_time'            => array( $this, 'parse_delivery_time' ),
				'min_age'                  => array( $this, 'parse_min_age' ),
				'sale_price_label'         => array( $this, 'parse_sale_price_label' ),
				'sale_price_regular_label' => array( $this, 'parse_sale_price_label' ),
				'unit'                     => array( $this, 'parse_unit' ),
				'warranty_attachment_id'   => 'absint',
				'gtin'                     => 'wc_clean',
				'mpn'                      => 'wc_clean',
				'is_food'                  => array( $this, 'parse_bool_str' ),
				'alcohol_content'          => 'wc_format_decimal',
				'drained_weight'           => 'wc_format_decimal',
				'net_filling_quantity'     => 'wc_format_decimal',
				'deposit_quantity'         => 'absint',
				'deposit_type'             => array( $this, 'parse_deposit_type' ),
				'allergen_ids'             => array( $this, 'parse_allergenic' ),
				'nutri_score'              => array( $this, 'parse_nutri_score' ),
				'ingredients'              => array( $this, 'parse_html_field' ),
				'food_description'         => array( $this, 'parse_html_field' ),
				'food_place_of_origin'     => array( $this, 'parse_html_field' ),
				'food_distributor'         => array( $this, 'parse_html_field' ),
				'nutrient_reference_value' => array( $this, 'parse_nutrient_reference_value' ),
			)
		);
	}

	public function parse_data( $data ) {
		$formattings = $this->get_formatting_callbacks();

		foreach ( $this->get_columns() as $column_name => $column ) {
			// Skip import for columns which do not exist in import file
			if ( ! isset( $data[ $column_name ] ) ) {
				continue;
			}

			if ( isset( $formattings[ $column_name ] ) ) {
				$data[ $column_name ] = call_user_func( $formattings[ $column_name ], $data[ $column_name ] );
			} else {
				$data[ $column_name ] = wc_clean( $data[ $column_name ] );
			}
		}

		$country_specific_delivery_times = array();
		$nutrients                       = array();

		foreach ( $data as $key => $value ) {
			if ( $this->starts_with( $key, 'delivery_time:' ) ) {
				$country                                     = str_replace( 'delivery_time:', '', $key );
				$country_specific_delivery_times[ $country ] = $this->parse_delivery_time( $value );

				unset( $data[ $key ] );
			} elseif ( $this->starts_with( $key, 'nutrients:' ) ) {
				$nutrient = str_replace( 'nutrients:', '', $key );

				if ( $nutrient_id = $this->parse_term( $nutrient, 'product_nutrient' ) ) {
					$nutrients[ $nutrient_id ] = $this->parse_nutrient( $value );
				}

				unset( $data[ $key ] );
			}
		}

		if ( ! empty( $country_specific_delivery_times ) ) {
			$data['country_specific_delivery_times'] = $country_specific_delivery_times;
		}

		if ( ! empty( $nutrients ) ) {
			$data['nutrient_ids'] = $nutrients;
		} else {
			$data['nutrient_ids'] = array();
		}

		return $data;
	}

	/**
	 * Parse a description value field
	 *
	 * @param string $description field value.
	 *
	 * @return string
	 */
	public function parse_html_field( $description ) {
		$parts = explode( "\\\\n", $description );
		foreach ( $parts as $key => $part ) {
			$parts[ $key ] = str_replace( '\n', "\n", $part );
		}

		return wc_gzd_sanitize_html_text_field( implode( '\\\n', $parts ) );
	}

	protected function starts_with( $haystack, $needle ) {
		return substr( $haystack, 0, strlen( $needle ) ) === $needle;
	}

	public function set_columns( $columns, $item ) {
		$columns  = array_merge( $columns, $this->get_columns() );
		$country  = str_replace( 'delivery_time:', '', $item );
		$nutrient = str_replace( 'nutrients:', '', $item );

		$columns[ "delivery_time:{$country}" ] = __( 'Country specific delivery times', 'woocommerce-germanized' );
		$columns[ "nutrients:{$nutrient}" ]    = __( 'Nutrients', 'woocommerce-germanized' );

		return $columns;
	}

	public function set_mappings( $columns ) {
		$columns = array_merge( $columns, array_flip( $this->get_columns() ) );

		return $columns;
	}

	/**
	 * @param WC_Product $product
	 * @param $data
	 *
	 * @return mixed|void
	 */
	public function import( $product, $data ) {
		$formattings  = $this->get_formatting_callbacks();
		$gzd_product  = wc_gzd_get_product( $product );
		$column_names = array_merge(
			$this->get_columns(),
			array(
				'country_specific_delivery_times' => '',
				'nutrient_ids'                    => '',
			)
		);

		foreach ( array_keys( $column_names ) as $column_name ) {
			if ( isset( $data[ $column_name ] ) ) {
				$value = $data[ $column_name ];

				if ( has_filter( "woocommerce_gzd_product_import_column_{$column_name}" ) ) {

					/**
					 * Filter that allows adjusting product import data for a certain `$column_name`.
					 *
					 * @param WC_Product $product The product object
					 * @param mixed $value The import value.
					 *
					 * @since 1.8.5
					 *
					 */
					$product = apply_filters( "woocommerce_gzd_product_import_column_{$column_name}", $product, $value );
				} elseif ( is_callable( array( $this, "set_column_value_{$column_name}" ) ) ) {
					$product = $this->{"set_column_value_{$column_name}"}( $product, $value );
				} else {
					$unprefixed = substr( $column_name, 0, 1 ) === '_' ? substr( $column_name, 1 ) : $column_name;
					$setter     = "set_{$unprefixed}";

					if ( is_callable( array( $gzd_product, $setter ) ) ) {
						$gzd_product->$setter( $value );
					}
				}
			}
		}

		return $product;
	}

	public function parse_bool_str( $value ) {
		$value = wc_string_to_bool( $value );

		return ( $value ? 'yes' : '' );
	}

	public function parse_allergenic( $allergenic ) {
		$allergenic   = array_filter( array_map( 'trim', explode( '|', $allergenic ) ) );
		$allergen_ids = array();

		foreach ( $allergenic as $allergen ) {
			if ( $term_id = $this->parse_term( $allergen, 'product_allergen' ) ) {
				$allergen_ids[] = $term_id;
			}
		}

		return $allergen_ids;
	}

	public function parse_nutrient_reference_value( $ref_value ) {
		if ( array_key_exists( $ref_value, WC_GZD_Food_Helper::get_nutrient_reference_values() ) ) {
			return $ref_value;
		} else {
			return '';
		}
	}

	public function parse_nutri_score( $nutri_score ) {
		if ( array_key_exists( $nutri_score, WC_GZD_Food_Helper::get_nutri_score_values() ) ) {
			return $nutri_score;
		} else {
			return '';
		}
	}

	public function parse_deposit_type( $name ) {
		if ( empty( $name ) ) {
			return 0;
		}

		return $this->parse_term( $name, 'product_deposit_type', 'slug' );
	}

	public function parse_unit( $name ) {
		if ( empty( $name ) ) {
			return 0;
		}

		return $this->parse_term( $name, 'product_unit', 'slug' );
	}

	public function parse_sale_price_label( $name ) {
		if ( empty( $name ) ) {
			return '';
		}

		return $this->parse_term( $name, 'product_price_label', 'slug' );
	}

	public function parse_delivery_time( $name ) {
		if ( empty( $name ) ) {
			return 0;
		}

		return $this->parse_term( $name, 'product_delivery_time' );
	}

	public function parse_nutrient( $nutrient_data ) {
		$nutrient_data = explode( '|', $nutrient_data );
		$return_data   = array(
			'value'     => 0,
			'ref_value' => '',
		);

		if ( ! empty( $nutrient_data ) ) {
			$return_data['value'] = wc_format_decimal( $nutrient_data[0] );

			if ( count( $nutrient_data ) > 1 ) {
				$return_data['ref_value'] = wc_format_decimal( $nutrient_data[1] );
			}
		}

		return $return_data;
	}

	public function parse_min_age( $min_age ) {
		if ( array_key_exists( (int) $min_age, wc_gzd_get_age_verification_min_ages() ) ) {
			return (int) $min_age;
		}

		return '';
	}

	public function parse_term( $name, $taxonomy, $output = 'term_id' ) {
		if ( empty( $name ) ) {
			return false;
		}

		if ( is_numeric( $name ) ) {
			$term = get_term_by( 'id', $name, $taxonomy );
		} else {
			$term = get_term_by( 'name', $name, $taxonomy );

			if ( ! $term || is_wp_error( $term ) ) {
				$term = get_term_by( 'slug', $name, $taxonomy );
			}
		}

		/**
		 * If the term does not exist, try to insert
		 */
		if ( ! is_a( $term, 'WP_Term' ) ) {
			$term_data = wp_insert_term( $name, $taxonomy );

			if ( ! is_wp_error( $term_data ) ) {
				$term = get_term_by( 'id', $term_data['term_id'], $taxonomy );
			}
		}

		if ( ! is_a( $term, 'WP_Term' ) ) {
			return '';
		}

		return $term->{$output};
	}

	/**
	 * @param WC_Product $product
	 * @param $value
	 *
	 * @return mixed
	 */
	public function set_column_value_delivery_time( $product, $value ) {
		wc_gzd_get_gzd_product( $product )->set_default_delivery_time_slug( $value );

		return $product;
	}

	/**
	 * @param WC_Product $product
	 * @param $value
	 *
	 * @return mixed
	 */
	public function set_column_value_country_specific_delivery_times( $product, $value ) {
		wc_gzd_get_gzd_product( $product )->set_country_specific_delivery_times( $value );

		return $product;
	}
}

WC_GZD_Product_Import::instance();
