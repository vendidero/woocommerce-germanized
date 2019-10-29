<?php

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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}

	public function __construct() {
		add_action( 'admin_init', array( $this, 'init' ), 30 );
	}

	public function get_columns() {
		return WC_GZD_Product_Export::instance()->get_columns();
	}

	public function init() {
		add_filter( 'woocommerce_csv_product_import_mapping_options', array( $this, 'set_columns' ) );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns', array( $this, 'set_mappings' ) );
		add_filter( 'woocommerce_product_import_pre_insert_product_object', array( $this, 'import' ), 10, 2 );
		add_filter( 'woocommerce_product_importer_parsed_data', array( $this, 'parse_data' ), 10, 1 );
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
		return apply_filters( 'woocommerce_gzd_product_import_formatting_callbacks', array(
			'mini_desc'                => 'wp_filter_post_kses',
			'unit_price_regular'       => 'wc_format_decimal',
			'unit_price_sale'          => 'wc_format_decimal',
			'unit_base'                => 'wc_format_decimal',
			'unit_product'             => 'wc_format_decimal',
			'unit_price_auto'          => array( $this, 'parse_bool_str' ),
			'service'                  => array( $this, 'parse_bool_str' ),
			'differential_taxation'    => array( $this, 'parse_bool_str' ),
			'free_shipping'            => array( $this, 'parse_bool_str' ),
			'delivery_time'            => array( $this, 'parse_delivery_time' ),
			'sale_price_label'         => array( $this, 'parse_sale_price_label' ),
			'sale_price_regular_label' => array( $this, 'parse_sale_price_label' ),
			'unit'                     => array( $this, 'parse_unit' ),
		) );
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

		return $data;
	}

	public function set_columns( $columns ) {
		return array_merge( $columns, $this->get_columns() );
	}

	public function set_mappings( $columns ) {
		return array_merge( $columns, array_flip( $this->get_columns() ) );
	}

	/**
	 * @param WC_Product $product
	 * @param $data
	 *
	 * @return mixed|void
	 */
	public function import( $product, $data ) {
		$formattings = $this->get_formatting_callbacks();
		$gzd_product = wc_gzd_get_product( $product );

		foreach ( $this->get_columns() as $column_name => $column ) {
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

	public function parse_term( $name, $taxonomy, $output = 'term_id' ) {
		$term = get_term_by( 'name', $name, $taxonomy );

		if ( ! $term || is_wp_error( $term ) ) {
			$term = (object) wp_insert_term( $name, $taxonomy );
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
		if ( ! empty( $value ) ) {
			$product->update_meta_data( "_product_delivery_time", $value );
		} else {
			$product->update_meta_data( "_delete_product_delivery_time", "yes" );
		}

		return $product;
	}
}

WC_GZD_Product_Import::instance();