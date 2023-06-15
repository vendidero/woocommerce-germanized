<?php

namespace Vendidero\EUTaxHelper;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Vendidero\EUTaxHelper\Helper' ) ) {
	return;
}

class Helper {

	/**
	 * Version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.8';

	public static function get_version() {
		return self::VERSION;
	}

	public static function oss_procedure_is_enabled() {
		return apply_filters( 'woocommerce_eu_tax_helper_oss_procedure_is_enabled', false );
	}

	public static function get_eu_countries() {
		if ( ! WC()->countries ) {
			return array();
		}

		$countries = WC()->countries->get_european_union_countries();

		return $countries;
	}

	public static function get_eu_vat_countries() {
		$vat_countries = WC()->countries ? WC()->countries->get_european_union_countries( 'eu_vat' ) : array();

		return apply_filters( 'woocommerce_eu_tax_helper_eu_vat_countries', $vat_countries );
	}

	public static function is_northern_ireland( $country, $postcode = '' ) {
		if ( 'GB' === $country && 'BT' === strtoupper( substr( trim( $postcode ), 0, 2 ) ) ) {
			return true;
		} elseif ( 'IX' === $country ) {
			return true;
		}

		return false;
	}

	public static function is_eu_vat_country( $country, $postcode = '' ) {
		$country           = wc_strtoupper( $country );
		$postcode          = wc_normalize_postcode( $postcode );
		$is_eu_vat_country = in_array( $country, self::get_eu_vat_countries(), true );

		if ( self::is_northern_ireland( $country, $postcode ) ) {
			$is_eu_vat_country = true;
		} elseif ( self::is_eu_vat_postcode_exemption( $country, $postcode ) ) {
			$is_eu_vat_country = false;
		}

		return apply_filters( 'woocommerce_eu_tax_helper_is_eu_vat_country', $is_eu_vat_country, $country, $postcode );
	}

	public static function is_third_country( $country, $postcode = '' ) {
		$is_third_country = true;

		/**
		 * In case the base country is within EU consider all non-EU VAT countries as third countries.
		 * In any other case consider every non-base-country as third country.
		 */
		if ( in_array( self::get_base_country(), self::get_eu_vat_countries(), true ) ) {
			$is_third_country = ! self::is_eu_vat_country( $country, $postcode );
		} else {
			$is_third_country = self::get_base_country() !== $country;
		}

		return apply_filters( 'woocommerce_eu_tax_helper_is_third_country', $is_third_country, $country, $postcode );
	}

	public static function is_eu_country( $country ) {
		return in_array( $country, self::get_eu_countries(), true );
	}

	public static function is_eu_vat_postcode_exemption( $country, $postcode = '' ) {
		$country    = wc_strtoupper( $country );
		$postcode   = wc_normalize_postcode( $postcode );
		$exemptions = self::get_vat_postcode_exemptions_by_country();
		$is_exempt  = false;

		if ( ! empty( $postcode ) && in_array( $country, self::get_eu_vat_countries(), true ) ) {
			if ( array_key_exists( $country, $exemptions ) ) {
				$wildcards = wc_get_wildcard_postcodes( $postcode, $country );

				foreach ( $exemptions[ $country ] as $exempt_postcode ) {
					if ( in_array( $exempt_postcode, $wildcards, true ) ) {
						$is_exempt = true;
						break;
					}
				}
			}
		}

		return $is_exempt;
	}

	/**
	 * Get VAT exemptions (of EU countries) for certain postcodes (e.g. canary islands)
	 *
	 * @see https://www.hk24.de/produktmarken/beratung-service/recht-und-steuern/steuerrecht/umsatzsteuer-mehrwertsteuer/umsatzsteuer-mehrwertsteuer-international/verfahrensrecht/territoriale-besonderheiten-umsatzsteuer-zollrecht-1167674
	 * @see https://github.com/woocommerce/woocommerce/issues/5143
	 * @see https://ec.europa.eu/taxation_customs/business/vat/eu-vat-rules-topic/territorial-status-eu-countries-certain-territories_en
	 *
	 * @return \string[][]
	 */
	public static function get_vat_postcode_exemptions_by_country( $country = '' ) {
		$country = wc_strtoupper( $country );

		$exemptions = array(
			'DE' => array(
				'27498', // Helgoland
				'78266', // Büsingen am Hochrhein
			),
			'ES' => array(
				'35*', // Canary Islands
				'38*', // Canary Islands
				'51*', // Ceuta
				'52*', // Melilla
			),
			'GR' => array(
				'63086', // Mount Athos
				'63087', // Mount Athos
			),
			'FR' => array(
				'971*', // Guadeloupe
				'972*', // Martinique
				'973*', // French Guiana
				'974*', // Réunion
				'976*', // Mayotte
			),
			'IT' => array(
				'22060', // Livigno, Campione d’Italia
				'23030', // Lake Lugano
			),
			'FI' => array(
				'22*', // Aland islands
			),
		);

		if ( empty( $country ) ) {
			return $exemptions;
		} elseif ( array_key_exists( $country, $exemptions ) ) {
			return $exemptions[ $country ];
		} else {
			return array();
		}
	}

	/**
	 * @param integer|\WC_Order $order
	 *
	 * @return array
	 */
	public static function get_order_taxable_location( $order ) {
		$order = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order );

		$taxable_address = array(
			WC()->countries->get_base_country(),
			WC()->countries->get_base_state(),
			WC()->countries->get_base_postcode(),
			WC()->countries->get_base_city(),
		);

		if ( ! $order ) {
			return $taxable_address;
		}

		$tax_based_on = get_option( 'woocommerce_tax_based_on' );

		if ( is_a( $order, 'WC_Order_Refund' ) ) {
			$order = wc_get_order( $order->get_parent_id() );

			if ( ! $order ) {
				return $taxable_address;
			}
		}

		/**
		 * Shipping address data does not exist
		 */
		if ( 'shipping' === $tax_based_on && ! $order->get_shipping_country() ) {
			$tax_based_on = 'billing';
		}

		$is_vat_exempt = apply_filters( 'woocommerce_order_is_vat_exempt', 'yes' === $order->get_meta( 'is_vat_exempt' ), $order );

		/**
		 * In case the order is a VAT exempt, calculate net prices based on taxes from base country.
		 */
		if ( $is_vat_exempt ) {
			$tax_based_on = 'base';
		}

		$country = 'shipping' === $tax_based_on ? $order->get_shipping_country() : $order->get_billing_country();

		if ( 'base' !== $tax_based_on && ! empty( $country ) ) {
			$taxable_address = array(
				$country,
				'billing' === $tax_based_on ? $order->get_billing_state() : $order->get_shipping_state(),
				'billing' === $tax_based_on ? $order->get_billing_postcode() : $order->get_shipping_postcode(),
				'billing' === $tax_based_on ? $order->get_billing_city() : $order->get_shipping_city(),
			);
		}

		return $taxable_address;
	}

	public static function get_taxable_location() {
		$is_admin_order_request = self::is_admin_order_request();

		if ( $is_admin_order_request ) {
			$taxable_address = array(
				WC()->countries->get_base_country(),
				WC()->countries->get_base_state(),
				WC()->countries->get_base_postcode(),
				WC()->countries->get_base_city(),
			);

			if ( isset( $_POST['order_id'] ) && ( $order = wc_get_order( absint( $_POST['order_id'] ) ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$taxable_address = self::get_order_taxable_location( $order );
			}

			return $taxable_address;
		} else {
			return \WC_Tax::get_tax_location();
		}
	}

	public static function is_admin_order_ajax_request() {
		$order_actions = array( 'woocommerce_calc_line_taxes', 'woocommerce_save_order_items', 'add_coupon_discount', 'refund_line_items', 'delete_refund' );

		return isset( $_POST['action'], $_POST['order_id'] ) && ( strstr( wc_clean( wp_unslash( $_POST['action'] ) ), '_order_' ) || in_array( wc_clean( wp_unslash( $_POST['action'] ) ), $order_actions, true ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	public static function is_admin_order_request() {
		return is_admin() && current_user_can( 'edit_shop_orders' ) && self::is_admin_order_ajax_request();
	}

	public static function current_request_has_vat_exempt() {
		$is_admin_order_request = self::is_admin_order_request();
		$is_vat_exempt          = false;

		if ( $is_admin_order_request ) {
			if ( $order = wc_get_order( absint( $_POST['order_id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotValidated
				$is_vat_exempt = apply_filters( 'woocommerce_order_is_vat_exempt', 'yes' === $order->get_meta( 'is_vat_exempt' ), $order );
			}
		} else {
			if ( WC()->customer && WC()->customer->is_vat_exempt() ) {
				$is_vat_exempt = true;
			}
		}

		return $is_vat_exempt;
	}

	public static function get_base_country() {
		if ( WC()->countries ) {
			return WC()->countries->get_base_country();
		} else {
			return wc_get_base_location()['country'];
		}
	}

	/**
	 * Returns a list of EU countries except base country.
	 *
	 * @return string[]
	 */
	public static function get_non_base_eu_countries( $include_gb = false ) {
		$countries = self::get_eu_vat_countries();

		/**
		 * Include GB to allow Northern Ireland
		 */
		if ( $include_gb && ! in_array( 'GB', $countries, true ) ) {
			$countries = array_merge( $countries, array( 'GB' ) );
		}

		$base_country = self::get_base_country();
		$countries    = array_diff( $countries, array( $base_country ) );

		return $countries;
	}

	public static function country_supports_eu_vat( $country, $postcode = '' ) {
		return self::is_eu_vat_country( $country, $postcode );
	}

	public static function import_oss_tax_rates( $tax_class_slug_names = array() ) {
		self::import_tax_rates_internal( true, $tax_class_slug_names );
	}

	public static function import_default_tax_rates( $tax_class_slug_names = array() ) {
		self::import_tax_rates_internal( false, $tax_class_slug_names );
	}

	public static function import_tax_rates( $tax_class_slug_names = array() ) {
		self::import_tax_rates_internal( self::oss_procedure_is_enabled(), $tax_class_slug_names );
	}

	protected static function parse_tax_class_slug_names( $tax_class_slug_names = array() ) {
		return wp_parse_args(
			$tax_class_slug_names,
			array(
				'reduced'         => apply_filters( 'woocommerce_eu_tax_helper_tax_class_reduced_name', __( 'Reduced rate', 'woocommerce' ) ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
				'greater-reduced' => apply_filters( 'woocommerce_eu_tax_helper_tax_class_greater_reduced_name', _x( 'Greater reduced rate', 'tax-helper-tax-class-name', 'woocommerce-germanized' ) ),
				'super-reduced'   => apply_filters( 'woocommerce_eu_tax_helper_tax_class_super_reduced_name', _x( 'Super reduced rate', 'tax-helper-tax-class-name', 'woocommerce-germanized' ) ),
				'zero'            => apply_filters( 'woocommerce_eu_tax_helper_tax_class_zero_name', __( 'Zero rate', 'woocommerce' ) ), // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			)
		);
	}

	protected static function import_tax_rates_internal( $is_oss = true, $tax_class_slug_names = array() ) {
		self::clear_cache();

		$tax_class_slugs      = self::get_tax_class_slugs( $tax_class_slug_names );
		$tax_class_slug_names = self::parse_tax_class_slug_names( $tax_class_slug_names );
		$eu_rates             = self::get_eu_tax_rates();

		foreach ( $tax_class_slugs as $tax_class_type => $class ) {
			/**
			 * Maybe create missing tax classes
			 */
			if ( false === $class ) {
				switch ( $tax_class_type ) {
					case 'reduced':
						/* translators: Do not translate */
						\WC_Tax::create_tax_class( $tax_class_slug_names['reduced'] );
						break;
					case 'greater-reduced':
						\WC_Tax::create_tax_class( $tax_class_slug_names['greater-reduced'] );
						break;
					case 'super-reduced':
						\WC_Tax::create_tax_class( $tax_class_slug_names['super-reduced'] );
						break;
					case 'zero':
						\WC_Tax::create_tax_class( $tax_class_slug_names['zero'] );
						break;
				}
			}

			$new_rates = array();

			if ( 'zero' === $tax_class_type ) {
				$new_rates = array(
					array(
						'country' => '*',
						'rate'    => 0.0,
						'name'    => '',
					),
				);
			} else {
				foreach ( $eu_rates as $country => $rates_data ) {
					/**
					 * Use base country rates in case OSS is disabled
					 */
					if ( ! $is_oss ) {
						$base_country = self::get_base_country();

						if ( isset( $eu_rates[ $base_country ] ) ) {
							/**
							 * In case the country includes multiple rules (e.g. postcode exempts) by default
							 * do only use the last rule (which does not include exempts) to construct non-base country tax rules.
							 */
							if ( $base_country !== $country ) {
								$base_country_base_rate = array_values( array_slice( $eu_rates[ $base_country ], -1 ) )[0];

								foreach ( $rates_data as $key => $rate_data ) {
									$rates_data[ $key ] = array_replace_recursive( $rate_data, $base_country_base_rate );

									foreach ( $tax_class_slugs as $tmp_class_type => $class_data ) {
										/**
										 * Do not include tax classes which are not supported by the base country.
										 */
										if ( isset( $rates_data[ $key ][ $tmp_class_type ] ) && ! isset( $base_country_base_rate[ $tmp_class_type ] ) ) {
											unset( $rates_data[ $key ][ $tmp_class_type ] );
										} elseif ( isset( $rates_data[ $key ][ $tmp_class_type ] ) ) {
											/**
											 * Replace tax class data with base data to make sure that reduced
											 * classes have the same dimensions
											 */
											$rates_data[ $key ][ $tmp_class_type ] = $base_country_base_rate[ $tmp_class_type ];

											/**
											 * In case this is an exempt make sure to replace with zero tax rates
											 */
											if ( isset( $rate_data['is_exempt'] ) && $rate_data['is_exempt'] ) {
												if ( is_array( $rates_data[ $key ][ $tmp_class_type ] ) ) {
													foreach ( $rates_data[ $key ][ $tmp_class_type ] as $k => $rate ) {
														$rates_data[ $key ][ $tmp_class_type ][ $k ] = 0;
													}
												} else {
													$rates_data[ $key ][ $tmp_class_type ] = 0;
												}
											}
										}
									}
								}
							}
						} else {
							continue;
						}
					}

					/**
					 * Each country may contain multiple tax rates
					 */
					foreach ( $rates_data as $rates ) {
						$rates = wp_parse_args(
							$rates,
							array(
								'name'      => '',
								'postcodes' => array(),
								'reduced'   => array(),
							)
						);

						if ( ! empty( $rates['postcode'] ) ) {
							foreach ( $rates['postcode'] as $postcode ) {
								$tax_rate = self::get_single_tax_rate_data( $tax_class_type, $rates, $country, $postcode );

								if ( false !== $tax_rate ) {
									$new_rates[] = $tax_rate;
								}
							}
						} else {
							$tax_rate = self::get_single_tax_rate_data( $tax_class_type, $rates, $country );

							if ( false !== $tax_rate ) {
								$new_rates[] = $tax_rate;
							}
						}
					}
				}
			}

			self::import_rates( $new_rates, $class, $tax_class_type );
		}
	}

	private static function get_single_tax_rate_data( $tax_class_type, $rates, $country, $postcode = '' ) {
		$rates = wp_parse_args(
			$rates,
			array(
				'name'    => '',
				'reduced' => array(),
			)
		);

		$single_rate = array(
			'name'     => $rates['name'],
			'rate'     => false,
			'country'  => $country,
			'postcode' => $postcode,
		);

		switch ( $tax_class_type ) {
			case 'greater-reduced':
				if ( count( $rates['reduced'] ) > 1 ) {
					$single_rate['rate'] = $rates['reduced'][1];
				}
				break;
			case 'reduced':
				if ( ! empty( $rates['reduced'] ) ) {
					$single_rate['rate'] = $rates['reduced'][0];
				}
				break;
			default:
				if ( isset( $rates[ $tax_class_type ] ) ) {
					$single_rate['rate'] = $rates[ $tax_class_type ];
				}
				break;
		}

		if ( false === $single_rate['rate'] ) {
			return false;
		}

		return $single_rate;
	}

	protected static function clear_cache() {
		$cache_key = \WC_Cache_Helper::get_cache_prefix( 'taxes' ) . 'eu_tax_helper_tax_class_slugs';

		wp_cache_delete( $cache_key, 'taxes' );
	}

	public static function get_tax_class_slugs( $tax_class_slug_names = array() ) {
		$tax_class_slug_names = self::parse_tax_class_slug_names( $tax_class_slug_names );
		$cache_key            = \WC_Cache_Helper::get_cache_prefix( 'taxes' ) . 'eu_tax_helper_tax_class_slugs';
		$slugs                = wp_cache_get( $cache_key, 'taxes' );

		if ( false === $slugs ) {
			$reduced_tax_class         = false;
			$greater_reduced_tax_class = false;
			$super_reduced_tax_class   = false;
			$zero_tax_class            = false;
			$tax_classes               = \WC_Tax::get_tax_class_slugs();

			/**
			 * Try to determine the reduced tax rate class
			 */
			foreach ( $tax_classes as $slug ) {
				if ( strstr( $slug, 'virtual' ) ) {
					continue;
				}

				if ( ! $greater_reduced_tax_class && strstr( $slug, sanitize_title( 'Greater reduced rate' ) ) ) {
					$greater_reduced_tax_class = $slug;
				} elseif ( ! $greater_reduced_tax_class && strstr( $slug, sanitize_title( $tax_class_slug_names['greater-reduced'] ) ) ) {
					$greater_reduced_tax_class = $slug;
				} elseif ( ! $super_reduced_tax_class && strstr( $slug, sanitize_title( 'Super reduced rate' ) ) ) {
					$super_reduced_tax_class = $slug;
				} elseif ( ! $super_reduced_tax_class && strstr( $slug, sanitize_title( $tax_class_slug_names['super-reduced'] ) ) ) {
					$super_reduced_tax_class = $slug;
				} elseif ( ! $reduced_tax_class && strstr( $slug, sanitize_title( 'Reduced rate' ) ) ) {
					$reduced_tax_class = $slug;
				} elseif ( ! $reduced_tax_class && strstr( $slug, sanitize_title( $tax_class_slug_names['reduced'] ) ) ) { // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
					$reduced_tax_class = $slug;
				} elseif ( ! $reduced_tax_class && strstr( $slug, 'reduced' ) ) {
					$reduced_tax_class = $slug;
				} elseif ( ! $zero_tax_class && strstr( $slug, sanitize_title( $tax_class_slug_names['zero'] ) ) ) {
					$zero_tax_class = $slug;
				} elseif ( ! $zero_tax_class && strstr( $slug, 'zero' ) ) {
					$zero_tax_class = $slug;
				}
			}

			$slugs = array(
				'reduced'         => $reduced_tax_class,
				'greater-reduced' => $greater_reduced_tax_class,
				'super-reduced'   => $super_reduced_tax_class,
				'standard'        => '',
				'zero'            => $zero_tax_class,
			);

			wp_cache_set( $cache_key, $slugs, 'taxes' );
		}

		return apply_filters( 'woocommerce_eu_tax_helper_tax_rate_class_slugs', $slugs );
	}

	public static function get_tax_type_by_country_rate( $rate_percentage, $country ) {
		$country = strtoupper( $country );

		/**
		 * Map northern ireland to GB
		 */
		if ( 'XI' === $country ) {
			$country = 'GB';
		}

		$eu_rates = self::get_eu_tax_rates();
		$tax_type = 'standard';

		if ( array_key_exists( $country, $eu_rates ) ) {
			$rates = $eu_rates[ $country ];

			foreach ( $rates as $rate ) {
				foreach ( $rate as $tax_rate_type => $tax_rate_percent ) {
					if ( ( is_array( $tax_rate_percent ) && in_array( $rate_percentage, $tax_rate_percent, true ) ) || (float) $tax_rate_percent === (float) $rate_percentage ) {
						$tax_type = $tax_rate_type;
						break;
					}
				}
			}
		}

		return apply_filters( 'woocommerce_eu_tax_helper_country_rate_tax_type', $tax_type, $country, $rate_percentage );
	}

	public static function get_eu_tax_rates() {
		/**
		 * @see https://europa.eu/youreurope/business/taxation/vat/vat-rules-rates/index_en.htm
		 *
		 * Include Great Britain to allow including Norther Ireland
		 */
		$rates = array(
			'AT' => array(
				array(
					'standard' => 20,
					'reduced'  => array( 10, 13 ),
				),
			),
			'BE' => array(
				array(
					'standard' => 21,
					'reduced'  => array( 6, 12 ),
				),
			),
			'BG' => array(
				array(
					'standard' => 20,
					'reduced'  => array( 9 ),
				),
			),
			'CY' => array(
				array(
					'standard' => 19,
					'reduced'  => array( 5, 9 ),
				),
			),
			'CZ' => array(
				array(
					'standard' => 21,
					'reduced'  => array( 10, 15 ),
				),
			),
			'DE' => array(
				array(
					'standard' => 19,
					'reduced'  => array( 7 ),
				),
			),
			'DK' => array(
				array(
					'standard' => 25,
					'reduced'  => array(),
				),
			),
			'EE' => array(
				array(
					'standard' => 20,
					'reduced'  => array( 9 ),
				),
			),
			'GR' => array(
				array(
					'standard' => 24,
					'reduced'  => array( 6, 13 ),
				),
			),
			'ES' => array(
				array(
					'standard'      => 21,
					'reduced'       => array( 10 ),
					'super-reduced' => 4,
				),
			),
			'FI' => array(
				array(
					'standard' => 24,
					'reduced'  => array( 10, 14 ),
				),
			),
			'FR' => array(
				array(
					'standard'      => 20,
					'reduced'       => array( 5.5, 10 ),
					'super-reduced' => 2.1,
				),
			),
			'HR' => array(
				array(
					'standard' => 25,
					'reduced'  => array( 5, 13 ),
				),
			),
			'HU' => array(
				array(
					'standard' => 27,
					'reduced'  => array( 5, 18 ),
				),
			),
			'IE' => array(
				array(
					'standard'      => 23,
					'reduced'       => array( 9, 13.5 ),
					'super-reduced' => 4.8,
				),
			),
			'IT' => array(
				array(
					'standard'      => 22,
					'reduced'       => array( 5, 10 ),
					'super-reduced' => 4,
				),
			),
			'LT' => array(
				array(
					'standard' => 21,
					'reduced'  => array( 5, 9 ),
				),
			),
			'LU' => array(
				array(
					'standard'      => 16,
					'reduced'       => array( 7 ),
					'super-reduced' => 3,
				),
			),
			'LV' => array(
				array(
					'standard' => 21,
					'reduced'  => array( 12, 5 ),
				),
			),
			'MC' => array(
				array(
					'standard'      => 20,
					'reduced'       => array( 5.5, 10 ),
					'super-reduced' => 2.1,
				),
			),
			'MT' => array(
				array(
					'standard' => 18,
					'reduced'  => array( 5, 7 ),
				),
			),
			'NL' => array(
				array(
					'standard' => 21,
					'reduced'  => array( 9 ),
				),
			),
			'PL' => array(
				array(
					'standard' => 23,
					'reduced'  => array( 5, 8 ),
				),
			),
			'PT' => array(
				array(
					// Madeira
					'postcode' => array( '90*', '91*', '92*', '93*', '94*' ),
					'standard' => 22,
					'reduced'  => array( 5, 12 ),
					'name'     => _x( 'Madeira', 'tax-helper', 'woocommerce-germanized' ),
				),
				array(
					// Acores
					'postcode' => array( '95*', '96*', '97*', '98*', '99*' ),
					'standard' => 18,
					'reduced'  => array( 4, 9 ),
					'name'     => _x( 'Acores', 'tax-helper', 'woocommerce-germanized' ),
				),
				array(
					'standard' => 23,
					'reduced'  => array( 6, 13 ),
				),
			),
			'RO' => array(
				array(
					'standard' => 19,
					'reduced'  => array( 5, 9 ),
				),
			),
			'SE' => array(
				array(
					'standard' => 25,
					'reduced'  => array( 6, 12 ),
				),
			),
			'SI' => array(
				array(
					'standard' => 22,
					'reduced'  => array( 9.5 ),
				),
			),
			'SK' => array(
				array(
					'standard' => 20,
					'reduced'  => array( 10 ),
				),
			),
			'GB' => array(
				array(
					'standard' => 20,
					'reduced'  => array( 5 ),
					'postcode' => array( 'BT*' ),
					'name'     => _x( 'Northern Ireland', 'tax-helper', 'woocommerce-germanized' ),
				),
			),
		);

		foreach ( self::get_vat_postcode_exemptions_by_country() as $country => $exempt_postcodes ) {
			if ( array_key_exists( $country, $rates ) ) {
				$default_rate = array_values( $rates[ $country ] )[0];

				$postcode_exempt = array(
					'postcode'  => $exempt_postcodes,
					'standard'  => 0,
					'reduced'   => count( $default_rate['reduced'] ) > 1 ? array( 0, 0 ) : array( 0 ),
					'name'      => _x( 'Exempt', 'tax-helper-rate-import', 'woocommerce-germanized' ),
					'is_exempt' => true,
				);

				if ( array_key_exists( 'super-reduced', $default_rate ) ) {
					$postcode_exempt['super-reduced'] = 0;
				}

				// Prepend before other tax rates
				$rates[ $country ] = array_merge( array( $postcode_exempt ), $rates[ $country ] );
			}
		}

		return $rates;
	}

	/**
	 * @param \stdClass $rate
	 *
	 * @return bool
	 */
	public static function tax_rate_is_northern_ireland( $rate ) {
		if ( 'GB' === $rate->tax_rate_country && isset( $rate->postcode ) && ! empty( $rate->postcode ) ) {
			foreach ( $rate->postcode as $postcode ) {
				if ( self::is_northern_ireland( $rate->tax_rate_country, $postcode ) ) {
					return true;
				}
			}
		}

		return false;
	}

	public static function import_rates( $rates, $tax_class = '', $tax_class_type = '' ) {
		global $wpdb;

		$eu_countries = self::get_eu_vat_countries();

		/**
		 * Delete EU tax rates and make sure tax rate locations are deleted too
		 */
		foreach ( \WC_Tax::get_rates_for_tax_class( $tax_class ) as $rate_id => $rate ) {
			if ( in_array( $rate->tax_rate_country, $eu_countries, true ) || self::tax_rate_is_northern_ireland( $rate ) || ( 'GB' === $rate->tax_rate_country && 'GB' !== self::get_base_country() ) ) {
				\WC_Tax::_delete_tax_rate( $rate_id );
			} elseif ( 'zero' === $tax_class_type && empty( $rate->tax_rate_country ) ) {
				\WC_Tax::_delete_tax_rate( $rate_id );
			}
		}

		$count = 0;

		foreach ( $rates as $rate ) {
			$rate = wp_parse_args(
				$rate,
				array(
					'rate'     => 0,
					'country'  => '',
					'postcode' => '',
					'name'     => '',
				)
			);

			$iso      = wc_strtoupper( $rate['country'] );
			$vat_desc = '*' !== $iso ? $iso : '';

			if ( ! empty( $rate['name'] ) ) {
				$vat_desc = ( ! empty( $vat_desc ) ? $vat_desc . ' ' : '' ) . $rate['name'];
			}

			$vat_rate = wc_format_decimal( $rate['rate'], false, true );

			$tax_rate_name = apply_filters( 'woocommerce_eu_tax_helper_import_tax_rate_name', sprintf( _x( 'VAT %1$s %% %2$s', 'tax-helper-rate-import', 'woocommerce-germanized' ), $vat_rate, $vat_desc ), $rate['rate'], $iso, $tax_class, $rate );

			$_tax_rate = array(
				'tax_rate_country'  => $iso,
				'tax_rate_state'    => '',
				'tax_rate'          => (string) number_format( (float) wc_clean( $rate['rate'] ), 4, '.', '' ),
				'tax_rate_name'     => $tax_rate_name,
				'tax_rate_compound' => 0,
				'tax_rate_priority' => 1,
				'tax_rate_order'    => $count++,
				'tax_rate_shipping' => ( strstr( $tax_class, 'virtual' ) ? 0 : 1 ),
				'tax_rate_class'    => $tax_class,
			);

			$new_tax_rate_id = \WC_Tax::_insert_tax_rate( $_tax_rate );

			if ( ! empty( $rate['postcode'] ) ) {
				\WC_Tax::_update_tax_rate_postcodes( $new_tax_rate_id, $rate['postcode'] );
			}
		}
	}

	/**
	 * @param $rate_id
	 * @param \WC_Order $order
	 */
	public static function get_tax_rate_percent( $rate_id, $order ) {
		$taxes      = $order->get_taxes();
		$percentage = null;

		foreach ( $taxes as $tax ) {
			if ( (int) $tax->get_rate_id() === (int) $rate_id ) {
				if ( is_callable( array( $tax, 'get_rate_percent' ) ) ) {
					$percentage = $tax->get_rate_percent();
				}
			}
		}

		/**
		 * WC_Order_Item_Tax::get_rate_percent returns null by default.
		 * Fallback to global tax rates (DB) in case the percentage is not available within order data.
		 */
		if ( is_null( $percentage ) || '' === $percentage ) {
			$rate_percentage = self::get_tax_rate_percentage( $rate_id );

			if ( false !== $rate_percentage ) {
				$percentage = $rate_percentage;
			}
		}

		if ( ! is_numeric( $percentage ) ) {
			$percentage = 0;
		}

		return $percentage;
	}

	public static function get_tax_rate_percentage( $rate_id ) {
		$percentage = false;

		if ( is_callable( array( 'WC_Tax', 'get_rate_percent_value' ) ) ) {
			$percentage = \WC_Tax::get_rate_percent_value( $rate_id );
		} elseif ( is_callable( array( 'WC_Tax', 'get_rate_percent' ) ) ) {
			$percentage = filter_var( \WC_Tax::get_rate_percent( $rate_id ), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		}

		return $percentage;
	}
}
