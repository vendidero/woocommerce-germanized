<?php

defined( 'ABSPATH' ) || exit;

/**
 * WPML Helper
 *
 * Specific configuration for WPML
 *
 * @class        WC_GZD_WPML_Helper
 * @category    Class
 * @author        vendidero
 */
class WC_GZD_Compatibility_WooCommerce_Product_Bundles extends WC_GZD_Compatibility {

	public static $variable_has_filtered = false;

	public static function get_name() {
		return 'WooCommerce Product Bundles';
	}

	public static function get_path() {
		return 'woocommerce-product-bundles/woocommerce-product-bundles.php';
	}

	public function load() {
		add_filter(
			'woocommerce_gzd_cart_item_tax_share_product',
			array(
				$this,
				'switch_bundle_tax_share_product',
			),
			10,
			4
		);

		add_filter(
			'woocommerce_gzd_product_types_supporting_unit_prices',
			array(
				$this,
				'enable_unit_prices',
			),
			10,
			1
		);

		/**
		 * Add tax, unit price shopmarks to bundled item prices
		 */
		add_filter( 'woocommerce_bundled_item_details', array( $this, 'register_item_price_filters' ), 0, 2 );
		add_filter( 'woocommerce_bundled_item_details', array( $this, 'unregister_item_price_filters' ), 500, 2 );

		/**
		 * Add single product shopmarks to the bundle total price
		 */
		add_action( 'woocommerce_after_bundle_price', array( $this, 'output_bundle_shopmarks' ), 10 );

		add_filter( 'woocommerce_gzd_product_is_doing_price_html_action', array( $this, 'is_doing_price_html_action' ), 10 );

		add_action( 'woocommerce_bundled_single_variation', array( $this, 'bundled_variation' ), 10, 2 );
		add_action( 'woocommerce_gzd_before_add_variation_options', array( $this, 'before_bundled_variation_options' ), 10, 3 );

		add_action( 'woocommerce_gzd_registered_scripts', array( $this, 'register_script' ), 10, 3 );
		add_filter( 'woocommerce_gzd_templates_requiring_variation_script', array( $this, 'register_template' ) );
	}

	/**
	 * Make sure to load the Germanized variation script in case a variable bundled item is included.
	 *
	 * @param $templates
	 *
	 * @return mixed
	 */
	public function register_template( $templates ) {
		$templates[] = 'single-product/add-to-cart/bundle.php';

		return $templates;
	}

	public function register_script( $suffix, $frontend_script_path, $assets_path ) {
		wp_register_script(
			'wc-gzd-unit-price-observer-bundle',
			$frontend_script_path . 'unit-price-observer-bundle' . $suffix . '.js',
			array(
				'jquery',
				'wc-gzd-unit-price-observer',
				'wc-add-to-cart-bundle',
			),
			WC_GERMANIZED_VERSION,
			true
		);

		if ( is_product() ) {
			if ( apply_filters( 'woocommerce_gzd_refresh_unit_price_on_price_change', true ) ) {
				wp_enqueue_script( 'wc-gzd-unit-price-observer-bundle' );
			}
		}
	}

	public function before_bundled_variation_options( $options, $gzd_product, $parent ) {
		global $product;

		/**
		 * The global product is a bundle - this variations seems to be part of a bundle.
		 */
		if ( is_a( $product, 'WC_Product_Bundle' ) && isset( $options['is_bundled'] ) ) {
			$bundled_items          = $product->get_bundled_items();
			$is_priced_individually = false;

			foreach ( $bundled_items as $bundled_item ) {
				if ( $bundled_item->get_product()->get_id() === $parent->get_id() ) {
					if ( is_callable( array( $bundled_item, 'is_priced_individually' ) ) && $bundled_item->is_priced_individually() ) {
						$is_priced_individually = $bundled_item->is_priced_individually();
						break;
					}
				}
			}

			if ( $is_priced_individually ) {
				/**
				 * Force recalculating the unit price based on current options data
				 */
				$gzd_product->recalculate_unit_price(
					array(
						'regular_price' => wc_format_decimal( $options['display_regular_price'], '' ),
						'price'         => wc_format_decimal( $options['display_price'], '' ),
						'sale_price'    => wc_format_decimal( $options['display_price'], '' ),
					)
				);
			}
		}
	}

	public function bundled_variation( $product_id, $bundled_item ) {
		if ( $bundled_product = $bundled_item->get_product() ) {
			if ( is_callable( array( $bundled_item, 'is_priced_individually' ) ) && $bundled_item->is_priced_individually() ) {
				foreach ( wc_gzd_get_single_product_shopmarks() as $shopmark ) {
					$callback = $shopmark->get_callback();

					if ( function_exists( $callback ) && $shopmark->is_enabled() && in_array( $shopmark->get_type(), array( 'unit_price', 'legal', 'tax', 'shipping_costs' ), true ) ) {
						switch ( $shopmark->get_type() ) {
							case 'unit_price':
								echo '<p class="price price-unit smaller wc-gzd-additional-info"></p>';
								break;
							case 'tax':
								echo '<p class="wc-gzd-additional-info tax-info"></p>';
								break;
							case 'shipping_costs':
								echo '<p class="wc-gzd-additional-info shipping-costs-info"></p>';
								break;
							case 'legal':
								echo '<div class="legal-price-info"><p class="wc-gzd-additional-info"><span class="wc-gzd-additional-info tax-info"></span>&nbsp;<span class="wc-gzd-additional-info shipping-costs-info"></span></p></div>';
								break;
						}
					}
				}
			}
		}
	}

	/**
	 * Prevent bundle price HTML infinite loops
	 *
	 * @see WC_GZD_Product::is_doing_price_html_action()
	 *
	 * @param $is_doing_action
	 *
	 * @return bool|mixed
	 */
	public function is_doing_price_html_action( $is_doing_action ) {
		if ( ! $is_doing_action && doing_action( 'woocommerce_bundled_item_price_html' ) ) {
			$is_doing_action = true;
		}

		return $is_doing_action;
	}

	public function output_bundle_shopmarks() {
		foreach ( wc_gzd_get_single_product_shopmarks() as $shopmark ) {
			$callback = $shopmark->get_callback();

			if ( function_exists( $callback ) && $shopmark->is_enabled() && in_array( $shopmark->get_type(), array( 'unit_price', 'legal', 'tax', 'shipping_costs' ), true ) ) {
				call_user_func( $callback );
			}
		}
	}

	/**
	 * @param \WC_Bundled_Item    $bundled_item
	 * @param \WC_Product_Bundle  $bundle
	 */
	public function register_item_price_filters( $bundled_item, $bundle ) {
		if ( $bundled_item->get_product()->is_type( 'variable' ) ) {
			self::$variable_has_filtered = false;
		}

		add_filter( 'woocommerce_bundled_item_price_html', array( $this, 'add_price_suffixes' ), 10, 3 );
	}

	protected function replace_p_tags( $html ) {
		return str_replace( array( '<p', '</p>' ), array( '<span', '</span>' ), $html );
	}

	public function add_price_suffixes( $price, $org_price, $org_product ) {
		global $product;

		// Store global $product variable in tmp variable
		$original_product = $product;

		if ( $product = $org_product->get_product() ) {
			$add_suffixes = true;

			if ( $product->is_type( 'variable' ) && self::$variable_has_filtered ) {
				$add_suffixes = false;
			}

			/**
			 * Recalculate the unit price in case the bundle is individually priced (e.g. may have discounts)
			 */
			if ( ! $product->is_type( 'variable' ) && is_callable( array( $org_product, 'is_priced_individually' ) ) && $org_product->is_priced_individually() ) {
				if ( is_callable( array( $org_product, 'get_raw_price' ) ) && is_callable( array( $org_product, 'get_raw_regular_price' ) ) ) {
					wc_gzd_get_gzd_product( $product )->recalculate_unit_price(
						array(
							'regular_price' => wc_format_decimal( $org_product->get_raw_regular_price(), '' ),
							'price'         => wc_format_decimal( $org_product->get_raw_price(), '' ),
							'sale_price'    => wc_format_decimal( $org_product->get_raw_price(), '' ),
						)
					);
				}
			}

			if ( $add_suffixes ) {
				ob_start();
				woocommerce_gzd_template_single_tax_info();
				$legal = ob_get_clean();

				/**
				 * Do not show the unit price for variable products which might contain (dynamically calculated) price ranges.
				 */
				if ( ! $product->is_type( 'variable' ) ) {
					ob_start();
					woocommerce_gzd_template_single_price_unit();
					$unit = ob_get_clean();
				} else {
					$unit = '';
					// Make sure the tax-info class is not being replaced by variation data.
					$legal = str_replace( 'tax-info', 'tax-info-static', $legal );
				}

				$price = $price . '<span class="wc-gzd-legal-price-info">' . $this->replace_p_tags( $unit ) . $this->replace_p_tags( $legal ) . '</span>';

				/**
				 * Do only add these suffixes to the variable parent product and not to every single variation which
				 * retrieves shopmarks via JS.
				 */
				if ( $product->is_type( 'variable' ) ) {
					self::$variable_has_filtered = true;
				}
			}
		}

		// Restore global $product variable
		$product = $original_product;

		return $price;
	}

	/**
	 * @param \WC_Bundled_Item    $bundled_item
	 * @param \WC_Product_Bundle  $bundle
	 */
	public function unregister_item_price_filters( $bundled_item, $bundle ) {
		remove_filter( 'woocommerce_bundled_item_price_html', array( $this, 'add_price_suffixes' ), 10 );
	}

	public function enable_unit_prices( $types ) {
		$types[] = 'bundle';

		return $types;
	}

	/**
	 * If the bundled product is priced individually WC Product Bundles marks the item as virtual.
	 * In that case we are returning the original product to be matched agains Germanized tax share check.
	 *
	 * @param $product
	 * @param $item
	 * @param $item_key
	 * @param $type
	 *
	 * @return mixed
	 */
	public function switch_bundle_tax_share_product( $product, $item, $item_key, $type ) {
		if ( ! function_exists( 'wc_pb_get_bundled_item' ) ) {
			return $product;
		}

		if ( 'shipping' === $type ) {
			if ( isset( $item['bundled_item_id'] ) && ! empty( $item['bundled_item_id'] ) ) {
				if ( $bundled_item = wc_pb_get_bundled_item( $item['bundled_item_id'] ) ) {
					if ( $bundled_item->is_priced_individually() ) {
						return $bundled_item->product;
					}
				}
			}
		}

		return $product;
	}
}
