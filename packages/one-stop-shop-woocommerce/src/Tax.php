<?php

namespace Vendidero\OneStopShop;

use Vendidero\EUTaxHelper\Helper;

defined( 'ABSPATH' ) || exit;

class Tax {

	public static function init() {
		if ( Helper::oss_procedure_is_enabled() ) {
			add_action( 'woocommerce_product_options_tax', array( __CLASS__, 'tax_product_options' ), 10 );
			add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product_options' ), 10, 1 );

			add_action( 'woocommerce_variation_options_tax', array( __CLASS__, 'variation_tax_product_options' ), 10, 3 );
			add_action( 'woocommerce_admin_process_variation_object', array( __CLASS__, 'save_variation_options' ), 10, 2 );

			add_filter( 'woocommerce_product_get_tax_class', array( __CLASS__, 'filter_tax_class' ), 250, 2 );
			add_filter( 'woocommerce_product_variation_get_tax_class', array( __CLASS__, 'filter_tax_class' ), 250, 2 );

			add_filter( 'woocommerce_adjust_non_base_location_prices', array( __CLASS__, 'disable_location_price' ), 250 );
			add_filter( 'woocommerce_customer_taxable_address', array( __CLASS__, 'vat_exempt_taxable_address' ), 10 );

			add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'invalidate_shipping_session' ), 100 );
		}
	}

	public static function disable_location_price() {
		$fixed_gross_prices = 'yes' === get_option( 'oss_fixed_gross_prices' );

		if ( $fixed_gross_prices ) {
			$tax_location = Helper::get_taxable_location();

			if ( ! empty( $tax_location[0] ) ) {
				$country  = $tax_location[0];
				$postcode = isset( $tax_location[2] ) ? $tax_location[2] : '';

				/**
				 * By default, do not force gross prices for third countries to make sure
				 * net prices are used within cart/checkout.
				 */
				if ( Helper::is_third_country( $country, $postcode ) && apply_filters( 'oss_disable_static_gross_prices_third_countries', ( 'yes' !== get_option( 'oss_fixed_gross_prices_for_third_countries' ) ), $tax_location ) ) {
					$fixed_gross_prices = false;
				}
			}
		}

		if ( apply_filters( 'oss_force_static_gross_prices', $fixed_gross_prices ) ) {
			return false;
		}

		return true;
	}

	protected static function filter_cart_items_available_for_shipping( $item ) {
		$product = $item['data'];

		if ( $product && $product->needs_shipping() ) {
			return true;
		}

		return false;
	}

	protected static function filter_cart_items_calculated_totals( $item ) {
		return isset( $item['line_total'] );
	}

	/**
	 * As prices may change based on the customers address and VAT status (e.g. exempt)
	 * it is necessary to make sure that shipping tax is recalculated too in case shipping costs include taxes.
	 */
	public static function invalidate_shipping_session( $cart ) {
		if ( apply_filters( 'oss_shipping_costs_include_taxes', false ) ) {
			if ( $cart ) {
				$items            = array_values( array_filter( $cart->get_cart(), array( __CLASS__, 'filter_cart_items_available_for_shipping' ) ) );
				$items_calculated = array_values( array_filter( $items, array( __CLASS__, 'filter_cart_items_calculated_totals' ) ) );

				/**
				 * Make sure totals have already been calculated (for all items) to prevent missing array key warnings
				 * while calling WC_Cart::get_shipping_packages()
				 */
				if ( count( $items ) > 0 && $items == $items_calculated ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
					foreach ( $cart->get_shipping_packages() as $package_key => $package ) {
						$session_key = "shipping_for_package_{$package_key}";

						unset( WC()->session->$session_key );
					}
				}
			}
		}
	}

	/**
	 * In case the order/customer is a VAT exempt, use the base address as tax location.
	 *
	 * @param $location
	 *
	 * @return array|mixed
	 */
	public static function vat_exempt_taxable_address( $location ) {
		if ( Helper::current_request_has_vat_exempt() ) {
			$location = array(
				WC()->countries->get_base_country(),
				WC()->countries->get_base_state(),
				WC()->countries->get_base_postcode(),
				WC()->countries->get_base_city(),
			);
		}

		return $location;
	}

	/**
	 * @param $tax_class
	 * @param \WC_Product $product
	 */
	public static function filter_tax_class( $tax_class, $product ) {
		$taxable_address = Helper::get_taxable_location();

		if ( isset( $taxable_address[0] ) && ! empty( $taxable_address[0] ) && WC()->countries->get_base_country() !== $taxable_address[0] ) {

			$address = array(
				'country'  => $taxable_address[0],
				'state'    => isset( $taxable_address[1] ) ? $taxable_address[1] : '',
				'postcode' => isset( $taxable_address[2] ) ? $taxable_address[2] : '',
				'city'     => isset( $taxable_address[3] ) ? $taxable_address[3] : '',
			);

			$tax_class = self::get_product_tax_class_by_country( $product, $address, $tax_class );
		}

		return $tax_class;
	}

	/**
	 * @param \WC_Product_Variation $variation
	 * @param $i
	 */
	public static function save_variation_options( $variation, $i ) {
		$parent             = wc_get_product( $variation->get_parent_id() );
		$tax_classes        = self::get_product_tax_classes( $variation, $parent, 'edit' );
		$parent_tax_classes = self::get_product_tax_classes( $parent );
		$product_tax_class  = $variation->get_tax_class();

		$posted        = isset( $_POST['variable_tax_class_by_countries'][ $i ] ) ? wc_clean( (array) wp_unslash( $_POST['variable_tax_class_by_countries'][ $i ] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$new_classes   = isset( $_POST['variable_tax_class_by_countries_new_tax_class'][ $i ] ) ? wc_clean( (array) wp_unslash( $_POST['variable_tax_class_by_countries_new_tax_class'][ $i ] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$new_countries = isset( $_POST['variable_tax_class_by_countries_new_countries'][ $i ] ) ? wc_clean( (array) wp_unslash( $_POST['variable_tax_class_by_countries_new_countries'][ $i ] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		foreach ( $tax_classes as $country => $tax_class ) {
			// Maybe delete missing tax classes (e.g. removed by the user)
			if ( ! isset( $posted[ $country ] ) || 'parent' === $posted[ $country ] ) {
				unset( $tax_classes[ $country ] );
			} else {
				$tax_classes[ $country ] = $posted[ $country ];
			}
		}

		foreach ( $new_countries as $key => $country ) {
			if ( empty( $country ) ) {
				continue;
			}

			if ( ! array_key_exists( $country, $tax_classes ) && isset( $new_classes[ $key ] ) && 'parent' !== $new_classes[ $key ] ) {
				$tax_classes[ $country ] = $new_classes[ $key ];
			}
		}

		/**
		 * Remove tax classes which match the products main tax class or the base country
		 */
		foreach ( $tax_classes as $country => $tax_class ) {
			if ( $tax_class === $product_tax_class || WC()->countries->get_base_country() === $country ) {
				unset( $tax_classes[ $country ] );
			} elseif ( isset( $parent_tax_classes[ $country ] ) && $parent_tax_classes[ $country ] === $tax_class ) {
				unset( $tax_classes[ $country ] );
			} elseif ( 'parent' === $tax_class ) {
				unset( $tax_classes[ $country ] );
			}
		}

		if ( empty( $tax_classes ) ) {
			$variation->delete_meta_data( '_tax_class_by_countries' );
		} else {
			$variation->update_meta_data( '_tax_class_by_countries', $tax_classes );
		}
	}

	/**
	 * @param \WC_Product $product
	 */
	public static function save_product_options( $product ) {
		$tax_classes       = self::get_product_tax_classes( $product );
		$product_tax_class = $product->get_tax_class();

		$posted        = isset( $_POST['_tax_class_by_countries'] ) ? wc_clean( (array) wp_unslash( $_POST['_tax_class_by_countries'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$new_classes   = isset( $_POST['_tax_class_by_countries_new_tax_class'] ) ? wc_clean( (array) wp_unslash( $_POST['_tax_class_by_countries_new_tax_class'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$new_countries = isset( $_POST['_tax_class_by_countries_new_countries'] ) ? wc_clean( (array) wp_unslash( $_POST['_tax_class_by_countries_new_countries'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		foreach ( $tax_classes as $country => $tax_class ) {
			// Maybe delete missing tax classes (e.g. removed by the user)
			if ( ! isset( $posted[ $country ] ) ) {
				unset( $tax_classes[ $country ] );
			} else {
				$tax_classes[ $country ] = $posted[ $country ];
			}
		}

		foreach ( $new_countries as $key => $country ) {
			if ( empty( $country ) ) {
				continue;
			}

			if ( ! array_key_exists( $country, $tax_classes ) && isset( $new_classes[ $key ] ) ) {
				$tax_classes[ $country ] = $new_classes[ $key ];
			}
		}

		/**
		 * Remove tax classes which match the products main tax class or the base country
		 */
		foreach ( $tax_classes as $country => $tax_class ) {
			if ( $tax_class === $product_tax_class || WC()->countries->get_base_country() === $country ) {
				unset( $tax_classes[ $country ] );
			}
		}

		if ( empty( $tax_classes ) ) {
			$product->delete_meta_data( '_tax_class_by_countries' );
		} else {
			$product->update_meta_data( '_tax_class_by_countries', $tax_classes );
		}
	}

	/**
	 * @param $loop
	 * @param $variation_data
	 * @param \WP_Post $variation
	 */
	public static function variation_tax_product_options( $loop, $variation_data, $variation ) {
		global $product_object;

		if ( ! $variation = wc_get_product( $variation ) ) {
			return;
		}

		$tax_classes    = self::get_product_tax_classes( $variation, $product_object, 'edit' );
		$countries_left = self::get_selectable_countries();

		if ( ! empty( $tax_classes ) ) {
			foreach ( $tax_classes as $country => $tax_class ) {
				$countries_left = array_diff_key( $countries_left, array( $country => '' ) );

				woocommerce_wp_select(
					array(
						'id'            => "variable_tax_class_by_countries{$loop}_{$country}",
						'name'          => "variable_tax_class_by_countries[{$loop}][{$country}]",
						'value'         => $tax_class,
						'label'         => sprintf( _x( 'Tax class (%s)', 'oss', 'woocommerce-germanized' ), $country ),
						'options'       => array( 'parent' => _x( 'Same as parent', 'oss', 'woocommerce-germanized' ) ) + wc_get_product_tax_class_options(),
						'wrapper_class' => 'oss-tax-class-by-country-field form-row form-row-full',
						'description'   => '<a href="#" class="dashicons dashicons-no-alt oss-remove-tax-class-by-country" data-country="' . esc_attr( $country ) . '">' . _x( 'remove', 'oss', 'woocommerce-germanized' ) . '</a>',
					)
				);
			}
		}
		?>
		<div class="oss-new-tax-class-by-country-placeholder"></div>

		<p class="form-field oss-add-tax-class-by-country">
			<label>&nbsp;</label>
			<a href="#" class="oss-add-new-tax-class-by-country">+ <?php echo esc_html_x( 'Add country specific tax class (OSS)', 'oss', 'woocommerce-germanized' ); ?></a>
		</p>

		<div class="oss-add-tax-class-by-country-template">
			<p class="form-field form-row form-row-full oss-add-tax-class-by-country-field">
				<label for="tax_class_countries">
					<select class="enhanced select oss-tax-class-new-country" name="variable_tax_class_by_countries_new_countries[<?php echo esc_attr( $loop ); ?>][]">
						<option value="" selected="selected"><?php echo esc_html_x( 'Select country', 'oss', 'woocommerce-germanized' ); ?></option>
						<?php
						foreach ( $countries_left as $country_code ) {
							echo '<option value="' . esc_attr( $country_code ) . '">' . esc_html( self::get_country_name( $country_code ) ) . '</option>';
						}
						?>
					</select>
				</label>
				<select class="enhanced select short oss-tax-class-new-class" name="variable_tax_class_by_countries_new_tax_class[<?php echo esc_attr( $loop ); ?>][]">
					<?php
					foreach ( wc_get_product_tax_class_options() as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
					}
					?>
				</select>
				<span class="description">
					<a href="#" class="dashicons dashicons-no-alt oss-remove-tax-class-by-country"><?php echo esc_html_x( 'remove', 'oss', 'woocommerce-germanized' ); ?></a>
				</span>
			</p>
		</div>
		<?php
	}

	protected static function get_selectable_countries() {
		$countries = Helper::get_non_base_eu_countries( true );
		$eu        = array( 'EU-wide' => _x( 'EU-wide', 'oss', 'woocommerce-germanized' ) );

		return $eu + $countries;
	}

	protected static function get_country_name( $country_code ) {
		$country_name = $country_code;
		$countries    = WC()->countries ? WC()->countries->get_countries() : array();

		if ( 'EU-wide' === $country_code ) {
			$country_name = _x( 'EU-wide', 'oss', 'woocommerce-germanized' );
		} elseif ( isset( $countries[ $country_code ] ) ) {
			$country_name = $countries[ $country_code ];
		}

		return $country_name;
	}

	public static function tax_product_options() {
		global $product_object;

		$tax_classes    = self::get_product_tax_classes( $product_object );
		$countries_left = self::get_selectable_countries();

		if ( ! empty( $tax_classes ) ) {
			foreach ( $tax_classes as $country => $tax_class ) {
				$countries_left = array_diff_key( $countries_left, array( $country => '' ) );

				woocommerce_wp_select(
					array(
						'id'          => '_tax_class_by_countries_' . $country,
						'name'        => '_tax_class_by_countries[' . $country . ']',
						'value'       => $tax_class,
						'label'       => sprintf( _x( 'Tax class (%s)', 'oss', 'woocommerce-germanized' ), $country ),
						'options'     => wc_get_product_tax_class_options(),
						'description' => '<a href="#" class="dashicons dashicons-no-alt oss-remove-tax-class-by-country" data-country="' . esc_attr( $country ) . '">' . _x( 'remove', 'oss', 'woocommerce-germanized' ) . '</a>',
					)
				);
			}
		}

		?>
		<div class="oss-new-tax-class-by-country-placeholder"></div>

		<p class="form-field oss-add-tax-class-by-country hide_if_grouped hide_if_external">
			<label>&nbsp;</label>
			<a href="#" class="oss-add-new-tax-class-by-country">+ <?php echo esc_html_x( 'Add country specific tax class (OSS)', 'oss', 'woocommerce-germanized' ); ?></a>
		</p>

		<div class="oss-add-tax-class-by-country-template">
			<p class="form-field">
				<label for="tax_class_countries">
					<select class="enhanced select" name="_tax_class_by_countries_new_countries[]">
						<option value="" selected="selected"><?php echo esc_html_x( 'Select country', 'oss', 'woocommerce-germanized' ); ?></option>
						<?php
						foreach ( $countries_left as $country_code ) {
							echo '<option value="' . esc_attr( $country_code ) . '">' . esc_html( self::get_country_name( $country_code ) ) . '</option>';
						}
						?>
					</select>
				</label>
				<select class="enhanced select short" name="_tax_class_by_countries_new_tax_class[]">
					<?php
					foreach ( wc_get_product_tax_class_options() as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
					}
					?>
				</select>
				<span class="description">
					<a href="#" class="dashicons dashicons-no-alt oss-remove-tax-class-by-country"><?php echo esc_html_x( 'remove', 'oss', 'woocommerce-germanized' ); ?></a>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * @param \WC_Product $product
	 */
	public static function get_product_tax_class_by_country( $product, $address = array(), $default = false ) {
		$address = wp_parse_args(
			$address,
			array(
				'country'  => '',
				'state'    => '',
				'postcode' => '',
				'city'     => '',
			)
		);

		$tax_class        = false !== $default ? $default : $product->get_tax_class();
		$postcode         = wc_normalize_postcode( $address['postcode'] );
		$filter_tax_class = true;

		/**
		 * Prevent tax class adjustment for GB (except Norther Ireland via postcode detection)
		 */
		if ( 'GB' === $address['country'] && ( empty( $postcode ) || 'BT' !== substr( $postcode, 0, 2 ) ) ) {
			$filter_tax_class = false;
		}

		if ( apply_filters( 'oss_woocommerce_switch_product_tax_class', $filter_tax_class, $product, $address['country'], $postcode, $default ) ) {
			$cache_suffix      = '_oss_tax_class_' . md5( sprintf( '%s+%s+%s+%s+%s', $address['country'], $address['state'], $address['city'], $postcode, $product->get_id() ) );
			$cache_key         = \WC_Cache_Helper::get_cache_prefix( 'product_' . $product->get_id() ) . $cache_suffix;
			$cache_key_tax     = \WC_Cache_Helper::get_cache_prefix( 'taxes' ) . $cache_suffix;
			$matched_tax_cache = wp_cache_get( $cache_key_tax, 'taxes' );
			$matched_tax_class = false !== $matched_tax_cache ? wp_cache_get( $cache_key, 'products' ) : false;

			if ( false === $matched_tax_class ) {
				$tax_classes     = self::get_product_tax_classes( $product );
				$tax_class_slugs = Helper::get_tax_class_slugs();

				if ( array_key_exists( $address['country'], $tax_classes ) ) {
					$tax_class = $tax_classes[ $address['country'] ];
				} elseif ( isset( $tax_classes['EU-wide'] ) ) {
					$tax_class = $tax_classes['EU-wide'];
				}

				if ( $tax_class_slugs['super-reduced'] === $tax_class ) {
					$tax_rates = \WC_Tax::find_rates(
						array(
							'country'   => $address['country'],
							'state'     => $address['state'],
							'city'      => $address['city'],
							'postcode'  => $postcode,
							'tax_class' => $tax_class,
						)
					);

					/**
					 * Country does not seem to support this tax class - fallback to the reduced tax class
					 */
					if ( empty( $tax_rates ) ) {
						$tax_class = $tax_class_slugs['reduced'];
					}
				}

				if ( $tax_class_slugs['greater-reduced'] === $tax_class ) {
					$tax_rates = \WC_Tax::find_rates(
						array(
							'country'   => $address['country'],
							'state'     => $address['state'],
							'city'      => $address['city'],
							'postcode'  => $postcode,
							'tax_class' => $tax_class,
						)
					);

					/**
					 * Country does not seem to support this tax class - fallback to the reduced tax class
					 */
					if ( empty( $tax_rates ) ) {
						$tax_class = $tax_class_slugs['reduced'];
					}
				}

				if ( $tax_class_slugs['reduced'] === $tax_class ) {
					$tax_rates = \WC_Tax::find_rates(
						array(
							'country'   => $address['country'],
							'state'     => $address['state'],
							'city'      => $address['city'],
							'postcode'  => $postcode,
							'tax_class' => $tax_class,
						)
					);

					/**
					 * Country does not seem to support this tax class - fallback to the standard tax class
					 */
					if ( empty( $tax_rates ) ) {
						$tax_class = $tax_class_slugs['standard'];
					}
				}

				/**
				 * This cache entry depends on both the tax and product data.
				 */
				wp_cache_set( $cache_key_tax, $cache_key, 'taxes' );
				wp_cache_set( $cache_key, $tax_class, 'products' );
			} else {
				$tax_class = $matched_tax_class;
			}
		}

		return $tax_class;
	}

	/**
	 * @param \WC_Product $product
	 */
	public static function get_product_tax_classes( $product, $parent = false, $context = 'view' ) {
		$tax_classes = $product->get_meta( '_tax_class_by_countries', true );
		$tax_classes = ( ! is_array( $tax_classes ) || empty( $tax_classes ) ) ? array() : $tax_classes;

		/**
		 * Merge with parent tax classes
		 */
		if ( is_a( $product, 'WC_Product_Variation' ) ) {
			$parent = $parent ? $parent : wc_get_product( $product->get_parent_id() );

			if ( $parent ) {
				$parent_tax_classes = self::get_product_tax_classes( $parent );
				$tax_classes        = array_replace_recursive( $parent_tax_classes, $tax_classes );

				foreach ( $tax_classes as $country => $tax_class ) {
					$parent_tax_class = isset( $parent_tax_classes[ $country ] ) ? $parent_tax_classes[ $country ] : false;

					if ( 'view' === $context && 'parent' === $tax_class ) {
						if ( $parent_tax_class ) {
							$tax_classes[ $country ] = $parent_tax_class;
						} else {
							unset( $tax_classes[ $country ] );
						}
					} elseif ( 'edit' === $context && $tax_class === $parent_tax_class ) {
						$tax_classes[ $country ] = 'parent';
					}
				}
			}
		}

		return $tax_classes;
	}
}
