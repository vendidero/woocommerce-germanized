<?php
namespace Vendidero\Germanized\Blocks;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema;
use Vendidero\Germanized\Blocks\Integrations\ProductElements;
use Vendidero\Germanized\Package;

final class Products {

	public function __construct() {
		$this->register_integrations();
		$this->register_endpoint_data();
	}

	private function register_integrations() {
		add_action(
			'woocommerce_blocks_all-products_block_registration',
			function( $integration_registry ) {
				$integration_registry->register( Package::container()->get( ProductElements::class ) );
			}
		);
	}

	protected function get_tax_display_mode( $tax_display_mode = '' ) {
		return in_array( $tax_display_mode, array( 'incl', 'excl' ), true ) ? $tax_display_mode : get_option( 'woocommerce_tax_display_shop' );
	}

	/**
	 * WooCommerce can return prices including or excluding tax; choose the correct method based on tax display mode.
	 *
	 * @param string $tax_display_mode If returned prices are incl or excl of tax.
	 * @return string Function name.
	 */
	protected function get_price_function_from_tax_display_mode( $tax_display_mode ) {
		return 'incl' === $tax_display_mode ? 'wc_get_price_including_tax' : 'wc_get_price_excluding_tax';
	}

	/**
	 * Convert monetary values from WooCommerce to string based integers, using
	 * the smallest unit of a currency.
	 *
	 * @param string|float $amount Monetary amount with decimals.
	 * @param int          $decimals Number of decimals the amount is formatted with.
	 * @param int          $rounding_mode Defaults to the PHP_ROUND_HALF_UP constant.
	 * @return string      The new amount.
	 */
	protected function prepare_money_response( $amount, $decimals = 2, $rounding_mode = PHP_ROUND_HALF_UP ) {
		$money_formatter = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\StoreApi\StoreApi::class )->container()->get( ExtendSchema::class )->get_formatter( 'money' );

		return $money_formatter->format(
			$amount,
			array(
				'decimals'      => $decimals,
				'rounding_mode' => $rounding_mode,
			)
		);
	}

	/**
	 * @param \WC_GZD_Product $product
	 * @param string $tax_display_mode
	 *
	 * @return array
	 */
	private function get_unit_prices( $product, $tax_display_mode = '' ) {
		$prices           = array();
		$tax_display_mode = $this->get_tax_display_mode( $tax_display_mode );
		$price_function   = $this->get_price_function_from_tax_display_mode( $tax_display_mode );

		// If we have a variable product, get the price from the variations (this will use the min value).
		if ( $product->is_type( 'variable' ) ) {
			$price         = $product->get_variation_unit_price();
			$regular_price = $product->get_variation_unit_regular_price();
			$sale_price    = $product->get_variation_unit_sale_price();
		} else {
			$price         = $product->get_unit_price();
			$regular_price = $product->get_unit_price_regular();
			$sale_price    = $product->get_unit_price_sale();
		}

		$prices['price']         = $this->prepare_money_response( empty( $price ) ? 0 : $price_function( $product->get_wc_product(), array( 'price' => $price ) ), wc_get_price_decimals() );
		$prices['regular_price'] = $this->prepare_money_response( empty( $regular_price ) ? 0 : $price_function( $product->get_wc_product(), array( 'price' => $regular_price ) ), wc_get_price_decimals() );
		$prices['sale_price']    = $this->prepare_money_response( empty( $sale_price ) ? 0 : $price_function( $product->get_wc_product(), array( 'price' => $sale_price ) ), wc_get_price_decimals() );
		$prices['price_range']   = $this->get_price_range( $product, $tax_display_mode );

		return $prices;
	}

	/**
	 * Get price range from certain product types.
	 *
	 * @param \WC_GZD_Product $product Product instance.
	 * @param string      $tax_display_mode If returned prices are incl or excl of tax.
	 * @return object|null
	 */
	protected function get_price_range( $product, $tax_display_mode = '' ) {
		$tax_display_mode = $this->get_tax_display_mode( $tax_display_mode );

		if ( $product->is_type( 'variable' ) ) {
			$prices = $product->get_variation_unit_prices( true );

			if ( ! empty( $prices['price'] ) && ( min( $prices['price'] ) !== max( $prices['price'] ) ) ) {
				return (object) array(
					'min_amount' => $this->prepare_money_response( min( $prices['price'] ), wc_get_price_decimals() ),
					'max_amount' => $this->prepare_money_response( max( $prices['price'] ), wc_get_price_decimals() ),
				);
			}
		}

		if ( $product->is_type( 'grouped' ) ) {
			$children       = array_filter( array_map( 'wc_get_product', $product->get_children() ), 'wc_products_array_filter_visible_grouped' );
			$price_function = $this->get_price_function_from_tax_display_mode( $tax_display_mode );

			foreach ( $children as $child ) {
				$gzd_child  = wc_gzd_get_product( $child );
				$unit_price = $gzd_child->get_unit_price();

				if ( '' !== $unit_price ) {
					$child_prices[] = $price_function( $child, array( 'price' => $unit_price ) );
				}
			}

			if ( ! empty( $child_prices ) ) {
				return (object) array(
					'min_amount' => $this->prepare_money_response( min( $child_prices ), wc_get_price_decimals() ),
					'max_amount' => $this->prepare_money_response( max( $child_prices ), wc_get_price_decimals() ),
				);
			}
		}

		return null;
	}

	private function register_endpoint_data() {
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => ProductSchema::IDENTIFIER,
				'namespace'       => 'woocommerce-germanized',
				'data_callback'   => function( $product ) {
					$gzd_product    = wc_gzd_get_product( $product );
					$html_formatter = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\StoreApi\StoreApi::class )->container()->get( ExtendSchema::class )->get_formatter( 'html' );

					return array(
						'unit_price_html'    => $html_formatter->format( $gzd_product->get_unit_price_html() ),
						'unit_prices'        => (object) $this->get_unit_prices( $gzd_product ),
						'unit'               => $gzd_product->get_unit(),
						'unit_base'          => $gzd_product->get_unit_base(),
						'unit_product'       => $gzd_product->get_unit_product(),
						'delivery_time_html' => $html_formatter->format( $gzd_product->get_delivery_time_html() ),
						'tax_info_html'      => $html_formatter->format( $gzd_product->get_tax_info() ),
					);
				},
				'schema_callback' => function () {
					return array(
						'unit'               => array(
							'description' => __( 'The unit for the unit price.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'unit_base'          => array(
							'description' => __( 'The unit base.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'unit_product'       => array(
							'description' => __( 'The unit product.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'unit_price_html'    => array(
							'description' => __( 'Unit price string formatted as HTML.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'unit_prices'        => array(
							'description' => __( 'Unit price data provided using the smallest unit of the currency.', 'woocommerce-germanized' ),
							'type'        => 'object',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'price'         => array(
									'description' => __( 'Current product unit price.', 'woocommerce-germanized' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'regular_price' => array(
									'description' => __( 'Regular product unit price.', 'woocommerce-germanized' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'sale_price'    => array(
									'description' => __( 'Sale product unit price, if applicable.', 'woocommerce-germanized' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'price_range'   => array(
									'description' => __( 'Price range, if applicable.', 'woocommerce-germanized' ),
									'type'        => array( 'object', 'null' ),
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
									'properties'  => array(
										'min_amount' => array(
											'description' => __( 'Price amount.', 'woocommerce-germanized' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
										'max_amount' => array(
											'description' => __( 'Price amount.', 'woocommerce-germanized' ),
											'type'        => 'string',
											'context'     => array( 'view', 'edit' ),
											'readonly'    => true,
										),
									),
								),
							),
						),
						'delivery_time_html' => array(
							'description' => __( 'Delivery time formatted as HTML.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'tax_info_html'      => array(
							'description' => __( 'Tax notice formatted as HTML.', 'woocommerce-germanized' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					);
				},
			)
		);
	}
}
