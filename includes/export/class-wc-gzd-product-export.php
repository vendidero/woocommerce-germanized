<?php

class WC_GZD_Product_Export {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	protected $additional_columns = array();

	protected $is_exporting_delivery_time = false;

	protected $is_exporting_nutrients = false;

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
		add_filter( 'woocommerce_product_export_product_default_columns', array( $this, 'set_columns' ), 10, 1 );
		add_filter( 'woocommerce_product_export_row_data', array( $this, 'export_delivery_times' ), 10, 2 );
		add_filter( 'woocommerce_product_export_row_data', array( $this, 'export_nutrients' ), 15, 2 );
		add_filter( 'woocommerce_product_export_column_names', array( $this, 'register_additional_columns' ), 500, 2 );
		add_filter( 'woocommerce_product_export_skip_meta_keys', array( $this, 'register_core_meta_data' ), 10, 2 );

		if ( ! did_action( 'init' ) ) {
			add_action( 'init', array( $this, 'register_column_filters' ) );
		} else {
			$this->register_column_filters();
		}

		$this->additional_columns         = array();
		$this->is_exporting_delivery_time = false;
		$this->is_exporting_nutrients     = false;
	}

	public function register_column_filters() {
		foreach ( $this->get_columns() as $key => $column ) {
			add_filter( 'woocommerce_product_export_product_column_' . $key, array( $this, 'export_column' ), 10, 2 );
		}
	}

	public function init() {
		wc_deprecated_function( 'WC_GZD_Product_Export::init', '3.9.0' );
	}

	public function get_columns() {
		/**
		 * Filter to extend Germanized data added to the WooCommerce product export.
		 *
		 * @param array $export_data Product export data.
		 *
		 * @since 1.9.1
		 */
		return apply_filters(
			'woocommerce_gzd_product_export_default_columns',
			array(
				'service'                  => _x( 'Is service?', 'exporter', 'woocommerce-germanized' ),
				'used_good'                => _x( 'Is used good?', 'exporter', 'woocommerce-germanized' ),
				'defective_copy'           => _x( 'Is defective copy?', 'exporter', 'woocommerce-germanized' ),
				'photovoltaic_system'      => _x( 'Is photovoltaic system?', 'exporter', 'woocommerce-germanized' ),
				'warranty_attachment_id'   => _x( 'Warranty attachment id (PDF)', 'exporter', 'woocommerce-germanized' ),
				'gtin'                     => _x( 'GTIN', 'exporter', 'woocommerce-germanized' ),
				'mpn'                      => _x( 'MPN', 'exporter', 'woocommerce-germanized' ),
				'differential_taxation'    => _x( 'Is differential taxed?', 'exporter', 'woocommerce-germanized' ),
				'free_shipping'            => _x( 'Has free shipping?', 'exporter', 'woocommerce-germanized' ),
				'unit_price_regular'       => _x( 'Unit price regular', 'exporter', 'woocommerce-germanized' ),
				'unit_price_sale'          => _x( 'Unit price sale', 'exporter', 'woocommerce-germanized' ),
				'unit_price_auto'          => _x( 'Unit price calculated automatically?', 'exporter', 'woocommerce-germanized' ),
				'unit'                     => _x( 'Unit', 'exporter', 'woocommerce-germanized' ),
				'unit_base'                => _x( 'Unit base', 'exporter', 'woocommerce-germanized' ),
				'unit_product'             => _x( 'Unit product', 'exporter', 'woocommerce-germanized' ),
				'mini_desc'                => _x( 'Cart description', 'exporter', 'woocommerce-germanized' ),
				'min_age'                  => _x( 'Minimum age', 'exporter', 'woocommerce-germanized' ),
				'defect_description'       => _x( 'Defect description', 'exporter', 'woocommerce-germanized' ),
				'delivery_time'            => _x( 'Delivery time', 'exporter', 'woocommerce-germanized' ),
				'sale_price_label'         => _x( 'Strike Price label', 'exporter', 'woocommerce-germanized' ),
				'sale_price_regular_label' => _x( 'Sale Price label', 'exporter', 'woocommerce-germanized' ),
				'is_food'                  => _x( 'Is food?', 'exporter', 'woocommerce-germanized' ),
				'nutrients'                => _x( 'Nutrients', 'exporter', 'woocommerce-germanized' ),
				'allergen_ids'             => _x( 'Allergenic', 'exporter', 'woocommerce-germanized' ),
				'deposit_type'             => _x( 'Deposit Type', 'exporter', 'woocommerce-germanized' ),
				'deposit_quantity'         => _x( 'Deposit Quantity', 'exporter', 'woocommerce-germanized' ),
				'ingredients'              => _x( 'Ingredients', 'exporter', 'woocommerce-germanized' ),
				'nutrient_reference_value' => _x( 'Nutrient reference value slug', 'exporter', 'woocommerce-germanized' ),
				'alcohol_content'          => _x( 'Alcohol content', 'exporter', 'woocommerce-germanized' ),
				'drained_weight'           => _x( 'Drained weight', 'exporter', 'woocommerce-germanized' ),
				'net_filling_quantity'     => _x( 'Net filling quantity', 'exporter', 'woocommerce-germanized' ),
				'nutri_score'              => _x( 'Nutri-Score', 'exporter', 'woocommerce-germanized' ),
				'food_description'         => _x( 'Food Description', 'exporter', 'woocommerce-germanized' ),
				'food_place_of_origin'     => _x( 'Food Place of Origin', 'exporter', 'woocommerce-germanized' ),
				'food_distributor'         => _x( 'Food Distributor', 'exporter', 'woocommerce-germanized' ),
			)
		);
	}

	public function register_core_meta_data( $meta_keys_to_skip, $product ) {
		$meta_keys_to_skip = array_merge(
			$meta_keys_to_skip,
			array(
				'_default_delivery_time',
				'_unit_price',
				'_gzd_version',
			)
		);

		foreach ( $this->get_columns() as $key => $title ) {
			$meta_keys_to_skip[] = "_{$key}";
		}

		return $meta_keys_to_skip;
	}

	public function register_additional_columns( $columns ) {
		$columns = array_replace( $columns, $this->additional_columns );

		return $columns;
	}

	public function set_columns( $columns ) {
		return array_merge( $columns, $this->get_columns() );
	}

	/**
	 * @param $row
	 * @param WC_Product $product
	 */
	public function export_delivery_times( $row, $product ) {
		if ( ! $this->is_exporting_delivery_time ) {
			return $row;
		}

		// Get delivery time without falling back to default
		$gzd_product = wc_gzd_get_product( $product );

		if ( $term = $gzd_product->get_delivery_time( 'edit' ) ) {
			$row['delivery_time'] = $term->name;
		} else {
			$row['delivery_time'] = '';
		}

		foreach ( $gzd_product->get_country_specific_delivery_times( 'edit' ) as $country => $slug ) {
			if ( $term = $gzd_product->get_delivery_time_by_country( $country ) ) {
				$column_key                              = 'delivery_time:' . esc_attr( $country );
				$row[ $column_key ]                      = $term->name;
				$this->additional_columns[ $column_key ] = sprintf( __( 'Delivery Time: %s', 'woocommerce-germanized' ), $country );
			}
		}

		return $row;
	}

	/**
	 * @param $row
	 * @param WC_Product $product
	 */
	public function export_nutrients( $row, $product ) {
		if ( ! $this->is_exporting_nutrients ) {
			return $row;
		}

		$gzd_product      = wc_gzd_get_product( $product );
		$row['nutrients'] = '';

		foreach ( $gzd_product->get_nutrient_ids( 'edit' ) as $nutrient_id => $values ) {
			if ( $nutrient = WC_germanized()->nutrients->get_nutrient_term( $nutrient_id, 'id' ) ) {
				$column_key         = 'nutrients:' . esc_attr( $nutrient->slug );
				$row[ $column_key ] = $values['value'];

				if ( '' !== $values['ref_value'] ) {
					$row[ $column_key ] = $row[ $column_key ] . '|' . $values['ref_value'];
				}

				$this->additional_columns[ $column_key ] = sprintf( __( 'Nutrients: %s', 'woocommerce-germanized' ), $nutrient->name );
			}
		}

		return $row;
	}

	/**
	 * @param $value
	 * @param WC_Product $product
	 *
	 * @return mixed|void|null
	 */
	public function export_column( $value, $product ) {
		$filter        = current_filter();
		$column_name   = str_replace( 'woocommerce_product_export_product_column_', '', $filter );
		$gzd_product   = wc_gzd_get_product( $product );
		$is_html_field = in_array( $column_name, array( 'ingredients', 'food_description', 'food_place_of_origin', 'food_distributor', 'defect_description', 'mini_desc' ), true );

		/**
		 * Delivery time needs special handling
		 */
		if ( 'delivery_time' === $column_name ) {
			$this->is_exporting_delivery_time = true;
			return '';
		} elseif ( 'nutrients' === $column_name ) {
			$this->is_exporting_nutrients = true;
			return '';
		}

		// Filter for 3rd parties.
		if ( has_filter( "woocommerce_gzd_product_export_column_{$column_name}" ) ) {
			/**
			 * Filter that allows adjusting product export data for a certain `$column_name`.
			 *
			 * @param string $data Export data.
			 * @param WC_Product $product Product object.
			 *
			 * @since 1.9.1
			 *
			 */
			$value = apply_filters( "woocommerce_gzd_product_export_column_{$column_name}", '', $product );
		} elseif ( is_callable( array( $this, "get_column_value_{$column_name}" ) ) ) {
			$value = $this->{"get_column_value_{$column_name}"}( $product );
		} else {
			$getter = "get_{$column_name}";
			$value  = '';

			if ( $is_html_field ) {
				$getter = "get_formatted_{$column_name}";
			}

			if ( is_callable( array( $gzd_product, $getter ) ) ) {
				$value = $gzd_product->$getter();
			}
		}

		if ( $is_html_field ) {
			$value = $this->filter_description_field( $value );
		}

		return $value;
	}

	protected function filter_description_field( $description ) {
		$description = str_replace( '\n', "\\\\n", $description );
		$description = str_replace( "\n", '\n', $description );

		return $description;
	}

	/**
	 * Get formatted regular unit price.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @return string
	 */
	protected function get_column_value_unit_price_regular( $product ) {
		return wc_format_localized_price( wc_gzd_get_gzd_product( $product )->get_unit_price_regular() );
	}

	/**
	 * Get formatted sale unit price.
	 *
	 * @param WC_Product $product Product being exported.
	 *
	 * @return string
	 */
	protected function get_column_value_unit_price_sale( $product ) {
		return wc_format_localized_price( wc_gzd_get_gzd_product( $product )->get_unit_price_sale() );
	}

	protected function get_column_value_allergen_ids( $product ) {
		$allergenic_list = array();

		foreach ( wc_gzd_get_gzd_product( $product )->get_allergen_ids() as $id ) {
			if ( $term = WC_germanized()->allergenic->get_allergen_term( $id, 'id' ) ) {
				$allergenic_list[] = $term->name;
			}
		}

		return implode( '|', $allergenic_list );
	}

	protected function get_column_value_sale_price_label( $product ) {
		$term = wc_gzd_get_product( $product )->get_sale_price_label_term();

		if ( is_a( $term, 'WP_Term' ) ) {
			return $term->name;
		}

		return '';
	}

	protected function get_column_value_sale_price_regular_label( $product ) {
		$term = wc_gzd_get_product( $product )->get_sale_price_regular_label_term();

		if ( is_a( $term, 'WP_Term' ) ) {
			return $term->name;
		}

		return '';
	}

	protected function get_column_value_unit( $product ) {
		$term = wc_gzd_get_product( $product )->get_unit_term();

		if ( is_a( $term, 'WP_Term' ) ) {
			return $term->name;
		}

		return '';
	}
}

WC_GZD_Product_Export::instance();
