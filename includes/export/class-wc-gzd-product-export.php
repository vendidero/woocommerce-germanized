<?php

class WC_GZD_Product_Export {

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
		add_action( 'admin_init', array( $this, 'init' ), 20 );
	}

	public function init() {

		/**
		 * Filter to extend Germanized data added to the WooCommerce product export.
		 *
		 * @param array $export_data Product export data.
		 *
		 * @since 1.9.1
		 *
		 */
		$this->columns = apply_filters( 'woocommerce_gzd_product_export_default_columns', array(
			'service'                  => _x( 'Is service?', 'exporter', 'woocommerce-germanized' ),
			'differential_taxation'    => _x( 'Is differential taxed?', 'exporter', 'woocommerce-germanized' ),
			'free_shipping'            => _x( 'Has free shipping?', 'exporter', 'woocommerce-germanized' ),
			'unit_price_regular'       => _x( 'Unit price regular', 'exporter', 'woocommerce-germanized' ),
			'unit_price_sale'          => _x( 'Unit price sale', 'exporter', 'woocommerce-germanized' ),
			'unit_price_auto'          => _x( 'Unit price calculated automatically?', 'exporter', 'woocommerce-germanized' ),
			'unit'                     => _x( 'Unit', 'exporter', 'woocommerce-germanized' ),
			'unit_base'                => _x( 'Unit base', 'exporter', 'woocommerce-germanized' ),
			'unit_product'             => _x( 'Unit product', 'exporter', 'woocommerce-germanized' ),
			'mini_desc'                => _x( 'Cart description', 'exporter', 'woocommerce-germanized' ),
			'delivery_time'            => _x( 'Delivery time', 'exporter', 'woocommerce-germanized' ),
			'sale_price_label'         => _x( 'Sale price label', 'exporter', 'woocommerce-germanized' ),
			'sale_price_regular_label' => _x( 'Sale price regular label', 'exporter', 'woocommerce-germanized' ),
		) );

		add_filter( 'woocommerce_product_export_product_default_columns', array( $this, 'set_columns' ), 10, 1 );

		foreach ( $this->columns as $key => $column ) {
			add_filter( 'woocommerce_product_export_product_column_' . $key, array( $this, 'export_column' ), 10, 2 );
		}
	}

	public function get_columns() {
		return $this->columns;
	}

	public function set_columns( $columns ) {
		return array_merge( $columns, $this->columns );
	}

	/**
	 * @param $value
	 * @param WC_Product $product
	 *
	 * @return mixed|void|null
	 */
	public function export_column( $value, $product ) {
		$filter      = current_filter();
		$column_name = str_replace( 'woocommerce_product_export_product_column_', '', $filter );
		$gzd_product = wc_gzd_get_product( $product );

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
		} else if ( is_callable( array( $this, "get_column_value_{$column_name}" ) ) ) {
			$value = $this->{"get_column_value_{$column_name}"}( $product );
		} else {
			$getter = "get_{$column_name}";
			$value  = '';

			if ( is_callable( array( $gzd_product, $getter ) ) ) {
				$value = $gzd_product->$getter();
			}
		}

		return $value;
	}

	public function get_column_value_delivery_time( $product ) {

		// Get delivery time without falling back to default
		$term = wc_gzd_get_product( $product )->get_delivery_time();

		if ( ! empty( $term ) ) {
			return $term->name;
		}

		return '';
	}

	public function get_column_value_sale_price_label( $product ) {

		$term = wc_gzd_get_product( $product )->get_sale_price_label_term();

		if ( is_a( $term, 'WP_Term' ) ) {
			return $term->name;
		}

		return '';
	}

	public function get_column_value_sale_price_regular_label( $product ) {

		$term = wc_gzd_get_product( $product )->get_sale_price_regular_label_term();

		if ( is_a( $term, 'WP_Term' ) ) {
			return $term->name;
		}

		return '';
	}

	public function get_column_value_unit( $product ) {

		$term = wc_gzd_get_product( $product )->get_unit_term();

		if ( is_a( $term, 'WP_Term' ) ) {
			return $term->name;
		}

		return '';
	}
}

WC_GZD_Product_Export::instance();