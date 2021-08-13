<?php

namespace Vendidero\OneStopShop;

defined( 'ABSPATH' ) || exit;

class Tax {

	public static function init() {
	    if ( Package::oss_procedure_is_enabled() ) {
		    add_action( 'woocommerce_product_options_tax', array( __CLASS__, 'tax_product_options' ), 10 );
		    add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product_options' ), 10, 1 );

		    add_action( 'woocommerce_variation_options_tax', array( __CLASS__, 'variation_tax_product_options' ), 10, 3 );
		    add_action( 'woocommerce_admin_process_variation_object', array( __CLASS__, 'save_variation_options' ), 10, 2 );

		    add_filter( 'woocommerce_product_get_tax_class', array( __CLASS__, 'filter_tax_class' ), 250, 2 );
		    add_filter( 'woocommerce_product_variation_get_tax_class', array( __CLASS__, 'filter_tax_class' ), 250, 2 );

		    add_filter( 'woocommerce_adjust_non_base_location_prices', array( __CLASS__, 'disable_location_price' ), 250 );
		    add_filter( 'woocommerce_customer_taxable_address', array( __CLASS__, 'vat_exempt_taxable_address' ), 10 );

		    add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'invalidate_shipping_session' ) );
        }
	}

	/**
	 * As prices may change based on the customers address and VAT status (e.g. exempt)
     * it is necessary to make sure that shipping tax is recalculated too in case shipping costs include taxes.
	 */
	public static function invalidate_shipping_session() {
	    if ( apply_filters( 'oss_shipping_costs_include_taxes', false ) ) {
	        if ( WC()->cart ) {
		        foreach( WC()->cart->get_shipping_packages() as $package_key => $package ) {
			        $session_key = "shipping_for_package_{$package_key}";

			        unset( WC()->session->$session_key );
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
	    if ( self::is_vat_exempt() ) {
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
                '78266' // Büsingen am Hochrhein
            ),
            'ES' => array(
                '35*', // Canary Islands
                '38*', // Canary Islands
                '51*', // Ceuta
                '52*' // Melilla
            ),
            'GR' => array(
                '63086', // Mount Athos
                '63087' // Mount Athos
            ),
            'IT' => array(
                '22060', // Livigno, Campione d’Italia
                '23030', // Lake Lugano
            ),
        );

	    if ( empty( $country ) ) {
	        return $exemptions;
        } elseif( array_key_exists( $country, $exemptions ) ) {
	        return $exemptions[ $country ];
        } else {
	        return array();
        }
    }

	public static function disable_location_price() {
	    $fixed_gross_prices = 'yes' === get_option( 'oss_fixed_gross_prices' );

	    if ( $fixed_gross_prices ) {
		    $tax_location = self::get_taxable_location();

		    if ( ! empty( $tax_location[0] ) ) {
			    $country  = $tax_location[0];
			    $postcode = isset( $tax_location[2] ) ? $tax_location[2] : '';

			    /**
			     * By default do not force gross prices for third countries to make sure
                 * net prices are used within cart/checkout.
			     */
			    if ( ! Package::country_supports_eu_vat( $country, $postcode ) && apply_filters( 'oss_disable_static_gross_prices_third_countries', true, $tax_location ) ) {
				    $fixed_gross_prices = false;
			    }
		    }
        }

	    if ( apply_filters( 'oss_force_static_gross_prices', $fixed_gross_prices ) ) {
		    return false;
        }

	    return true;
    }

    protected static function get_taxable_location() {
	    $is_admin_order_request = self::is_admin_order_request();

	    if ( $is_admin_order_request ) {
            $taxable_address = array(
                WC()->countries->get_base_country(),
                WC()->countries->get_base_state(),
                WC()->countries->get_base_postcode(),
                WC()->countries->get_base_city()
            );

            if ( $order = wc_get_order( absint( $_POST['order_id'] ) ) ) {
                $tax_based_on = get_option( 'woocommerce_tax_based_on' );

                if ( 'shipping' === $tax_based_on && ! $order->get_shipping_country() ) {
                    $tax_based_on = 'billing';
                }

	            $is_vat_exempt = apply_filters( 'woocommerce_order_is_vat_exempt', 'yes' === $order->get_meta( 'is_vat_exempt' ), $order );

	            /**
	             * In case the order is a VAT exempt, calculate net prices
                 * based on taxes from base country.
	             */
                if ( $is_vat_exempt ) {
                    $tax_based_on = 'base';
                }

	            $country = 'billing' === $tax_based_on ? $order->get_billing_country() : $order->get_shipping_country();

                if ( 'base' !== $tax_based_on && ! empty( $country ) ) {
                    $taxable_address = array(
                        $country,
                        'billing' === $tax_based_on ? $order->get_billing_state() : $order->get_shipping_state(),
                        'billing' === $tax_based_on ? $order->get_billing_postcode() : $order->get_shipping_postcode(),
                        'billing' === $tax_based_on ? $order->get_billing_city() : $order->get_shipping_city(),
                    );
                }
            }

		    return $taxable_address;
	    } else {
		    return \WC_Tax::get_tax_location();
        }
    }

	/**
	 * @param $tax_class
	 * @param \WC_Product $product
	 */
	public static function filter_tax_class( $tax_class, $product ) {
	    $taxable_address = self::get_taxable_location();

        if ( isset( $taxable_address[0] ) && ! empty( $taxable_address[0] ) && $taxable_address[0] != wc_get_base_location()['country'] ) {
            $county    = $taxable_address[0];
            $postcode  = isset( $taxable_address[2] ) ? $taxable_address[2] : '';
            $tax_class = self::get_product_tax_class_by_country( $product, $county, $postcode, $tax_class );
        }

	    return $tax_class;
    }

    protected static function is_vat_exempt() {
	    $is_admin_order_request = self::is_admin_order_request();
	    $is_vat_exempt          = false;

	    if ( $is_admin_order_request ) {
		    if ( $order = wc_get_order( absint( $_POST['order_id'] ) ) ) {
			    $is_vat_exempt = apply_filters( 'woocommerce_order_is_vat_exempt', 'yes' === $order->get_meta( 'is_vat_exempt' ), $order );
		    }
	    } else {
		    if ( WC()->customer && WC()->customer->is_vat_exempt() ) {
			    $is_vat_exempt = true;
		    }
        }

	    return $is_vat_exempt;
    }

    protected static function is_admin_order_ajax_request() {
	    $order_actions = array( 'woocommerce_calc_line_taxes', 'add_coupon_discount', 'refund_line_items', 'delete_refund' );

	    return isset( $_POST['action'], $_POST['order_id'] ) && ( strstr( $_POST['action'], '_order_' ) || in_array( $_POST['action'], $order_actions ) );
    }

    protected static function is_admin_order_request() {
	    return is_admin() && current_user_can( 'edit_shop_orders' ) && self::is_admin_order_ajax_request();
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

	    $posted        = isset( $_POST['variable_tax_class_by_countries'][ $i ] ) ? wc_clean( (array) $_POST['variable_tax_class_by_countries'][ $i ] ) : array();
	    $new_classes   = isset( $_POST['variable_tax_class_by_countries_new_tax_class'][ $i ] ) ? wc_clean( (array) $_POST['variable_tax_class_by_countries_new_tax_class'][ $i ] ) : array();
	    $new_countries = isset( $_POST['variable_tax_class_by_countries_new_countries'][ $i ] ) ? wc_clean( (array) $_POST['variable_tax_class_by_countries_new_countries'][ $i ] ) : array();

	    foreach( $tax_classes as $country => $tax_class ) {
		    // Maybe delete missing tax classes (e.g. removed by the user)
		    if ( ! isset( $posted[ $country ] ) || 'parent' === $posted[ $country ] ) {
			    unset( $tax_classes[ $country ] );
		    } else {
		        $tax_classes[ $country ] = $posted[ $country ];
            }
	    }

	    foreach( $new_countries as $key => $country ) {
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
	    foreach( $tax_classes as $country => $tax_class ) {
		    if ( $tax_class == $product_tax_class || $country === wc_get_base_location()['country'] ) {
			    unset( $tax_classes[ $country ] );
		    } elseif ( isset( $parent_tax_classes[ $country ] ) && $parent_tax_classes[ $country ] == $tax_class ) {
			    unset( $tax_classes[ $country ] );
            } elseif( 'parent' === $tax_class ) {
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

		$posted        = isset( $_POST['_tax_class_by_countries'] ) ? wc_clean( (array) $_POST['_tax_class_by_countries'] ) : array();
        $new_classes   = isset( $_POST['_tax_class_by_countries_new_tax_class'] ) ? wc_clean( (array) $_POST['_tax_class_by_countries_new_tax_class'] ) : array();
		$new_countries = isset( $_POST['_tax_class_by_countries_new_countries'] ) ? wc_clean( (array) $_POST['_tax_class_by_countries_new_countries'] ) : array();

		foreach( $tax_classes as $country => $tax_class ) {
		    // Maybe delete missing tax classes (e.g. removed by the user)
		    if ( ! isset( $posted[ $country ] ) ) {
		        unset( $tax_classes[ $country ] );
            } else {
			    $tax_classes[ $country ] = $posted[ $country ];
		    }
        }

		foreach( $new_countries as $key => $country ) {
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
		foreach( $tax_classes as $country => $tax_class ) {
		    if ( $tax_class == $product_tax_class || $country === wc_get_base_location()['country'] ) {
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
	    $countries_left = Package::get_non_base_eu_countries( true );

	    if ( ! empty( $tax_classes ) ) {
		    foreach( $tax_classes as $country => $tax_class ) {
			    $countries_left = array_diff( $countries_left, array( $country ) );

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
            <a href="#" class="oss-add-new-tax-class-by-country">+ <?php _ex( 'Add country specific tax class (OSS)', 'oss', 'woocommerce-germanized' ); ?></a>
        </p>

        <div class="oss-add-tax-class-by-country-template">
            <p class="form-field form-row form-row-full oss-add-tax-class-by-country-field">
                <label for="tax_class_countries">
                    <select class="enhanced select oss-tax-class-new-country" name="variable_tax_class_by_countries_new_countries[<?php echo $loop; ?>][]">
                        <option value="" selected="selected"><?php _ex( 'Select country', 'oss', 'woocommerce-germanized' ); ?></option>
					    <?php
					    foreach ( $countries_left as $country_code ) {
						    echo '<option value="' . esc_attr( $country_code ) . '">' . esc_html( WC()->countries->get_countries()[ $country_code ] ) . '</option>';
					    }
					    ?>
                    </select>
                </label>
                <select class="enhanced select short oss-tax-class-new-class" name="variable_tax_class_by_countries_new_tax_class[<?php echo $loop; ?>][]">
				    <?php
				    foreach ( wc_get_product_tax_class_options() as $key => $value ) {
					    echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
				    }
				    ?>
                </select>
                <span class="description">
                    <a href="#" class="dashicons dashicons-no-alt oss-remove-tax-class-by-country"><?php _ex( 'remove', 'oss', 'woocommerce-germanized' ); ?></a>
                </span>
            </p>
        </div>
	    <?php
    }

	public static function tax_product_options() {
		global $product_object;

		$tax_classes    = self::get_product_tax_classes( $product_object );
		$countries_left = Package::get_non_base_eu_countries( true );

		if ( ! empty( $tax_classes ) ) {
			foreach( $tax_classes as $country => $tax_class ) {
				$countries_left = array_diff( $countries_left, array( $country ) );

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
			<a href="#" class="oss-add-new-tax-class-by-country">+ <?php _ex( 'Add country specific tax class (OSS)', 'oss', 'woocommerce-germanized' ); ?></a>
        </p>

        <div class="oss-add-tax-class-by-country-template">
            <p class="form-field">
                <label for="tax_class_countries">
                    <select class="enhanced select" name="_tax_class_by_countries_new_countries[]">
                        <option value="" selected="selected"><?php _ex( 'Select country', 'oss', 'woocommerce-germanized' ); ?></option>
		                <?php
		                foreach ( $countries_left as $country_code ) {
			                echo '<option value="' . esc_attr( $country_code ) . '">' . esc_html( WC()->countries->get_countries()[ $country_code ] ) . '</option>';
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
                    <a href="#" class="dashicons dashicons-no-alt oss-remove-tax-class-by-country"><?php _ex( 'remove', 'oss', 'woocommerce-germanized' ); ?></a>
                </span>
            </p>
        </div>
		<?php
	}

	/**
	 * @param \WC_Product $product
	 */
	public static function get_product_tax_class_by_country( $product, $country, $postcode = '', $default = false ) {
		$tax_classes      = self::get_product_tax_classes( $product );
		$tax_class        = false !== $default ? $default : $product->get_tax_class();
		$postcode         = wc_normalize_postcode( $postcode );
		$filter_tax_class = true;

		/**
		 * Prevent tax class adjustment for GB (except Norther Ireland via postcode detection)
		 */
		if ( 'GB' === $country && ( empty( $postcode ) || 'BT' !== substr( $postcode, 0, 2 ) ) ) {
			$filter_tax_class = false;
		}

		if ( apply_filters( "oss_woocommerce_switch_product_tax_class", $filter_tax_class, $product, $country, $postcode, $default ) && array_key_exists( $country, $tax_classes ) ) {
			$tax_class = $tax_classes[ $country ];
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

                foreach( $tax_classes as $country => $tax_class ) {
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

	public static function import_default_tax_rates() {
	    self::import_tax_rates( false );
	}

	public static function import_tax_rates( $is_oss = true ) {
		$tax_class_slugs = self::get_tax_class_slugs();
		$eu_rates        = self::get_eu_tax_rates();

		foreach( $tax_class_slugs as $tax_class_type => $class ) {
			/**
			 * Maybe create missing tax classes
			 */
			if ( false === $class ) {
				switch( $tax_class_type ) {
					case "reduced":
						/* translators: Do not translate */
						\WC_Tax::create_tax_class( __( 'Reduced rate', 'woocommerce' ) );
						break;
					case "greater-reduced":
						\WC_Tax::create_tax_class( _x( 'Greater reduced rate', 'oss', 'woocommerce-germanized' ) );
						break;
					case "super-reduced":
						\WC_Tax::create_tax_class( _x( 'Super reduced rate', 'oss', 'woocommerce-germanized' ) );
						break;
				}
			}

			$new_rates = array();

			foreach( $eu_rates as $country => $rates_data ) {

				/**
				 * Use base country rates in case OSS is disabled
				 */
				if ( ! $is_oss ) {
					$base_country = wc_get_base_location()['country'];

					if ( isset( $eu_rates[ $base_country ] ) ) {
						$rates_data = $eu_rates[ $base_country ];
					} else {
						continue;
					}
				}

				/**
				 * Each country may contain multiple tax rates
				 */
			    foreach( $rates_data as $rates ) {

			        $rates = wp_parse_args( $rates, array(
                        'name'      => '',
                        'postcodes' => array(),
                        'reduced'   => array(),
                    ) );

				    if ( ! empty( $rates['postcode'] ) ) {
					    foreach( $rates['postcode'] as $postcode ) {
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

			self::import_rates( $new_rates, $class );
		}
	}

	private static function get_single_tax_rate_data( $tax_class_type, $rates, $country, $postcode = '' ) {
		$rates = wp_parse_args( $rates, array(
			'name'    => '',
			'reduced' => array(),
		) );

	    $single_rate = array(
            'name'     => $rates['name'],
            'rate'     => false,
            'country'  => $country,
            'postcode' => $postcode,
        );

		switch( $tax_class_type ) {
			case "greater-reduced":
				if ( sizeof( $rates['reduced'] ) > 1 ) {
					$single_rate['rate'] = $rates['reduced'][1];
				}
				break;
			case "reduced":
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

	public static function import_oss_tax_rates() {
		self::import_tax_rates( true );
	}

	public static function get_tax_class_slugs() {
		$tax_classes               = \WC_Tax::get_tax_class_slugs();
		$reduced_tax_class         = false;
		$greater_reduced_tax_class = false;
		$super_reduced_tax_class   = false;

		/**
		 * Try to determine the reduced tax rate class
		 */
		foreach( $tax_classes as $slug ) {
			if ( strstr( $slug, 'virtual' ) ) {
				continue;
			}

			if ( ! $greater_reduced_tax_class && strstr( $slug, sanitize_title( 'Greater reduced rate' ) ) ) {
				$greater_reduced_tax_class = $slug;
			} elseif ( ! $greater_reduced_tax_class && strstr( $slug, sanitize_title( _x( 'Greater reduced rate', 'oss', 'woocommerce-germanized' ) ) ) ) {
				$greater_reduced_tax_class = $slug;
			} elseif ( ! $super_reduced_tax_class && strstr( $slug, sanitize_title( 'Super reduced rate' ) ) ) {
				$super_reduced_tax_class = $slug;
			} elseif ( ! $super_reduced_tax_class && strstr( $slug, sanitize_title( _x( 'Super reduced rate', 'oss', 'woocommerce-germanized' ) ) ) ) {
				$super_reduced_tax_class = $slug;
			} elseif ( ! $reduced_tax_class && strstr( $slug, sanitize_title( 'Reduced rate' ) ) ) {
				$reduced_tax_class = $slug;
			} elseif ( ! $reduced_tax_class && strstr( $slug, sanitize_title( __( 'Reduced rate', 'woocommerce' ) ) ) ) {
				$reduced_tax_class = $slug;
			} elseif ( ! $reduced_tax_class && strstr( $slug, 'reduced' ) && ! $reduced_tax_class ) {
				$reduced_tax_class = $slug;
			}
		}

		return apply_filters( 'oss_woocommerce_tax_rate_class_slugs', array(
			'reduced'         => $reduced_tax_class,
			'greater-reduced' => $greater_reduced_tax_class,
			'super-reduced'   => $super_reduced_tax_class,
			'standard'        => '',
		) );
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
                    'reduced'  => array( 10, 13 )
                ),
			),
			'BE' => array(
				array(
                    'standard' => 21,
                    'reduced'  => array( 6, 12 )
                ),
			),
			'BG' => array(
				array(
                    'standard' => 20,
                    'reduced'  => array( 9 )
                ),
			),
			'CY' => array(
				array(
                    'standard' => 19,
                    'reduced'  => array( 5, 9 )
                ),
			),
			'CZ' => array(
				array(
                    'standard' => 21,
                    'reduced'  => array( 10, 15 )
                ),
			),
			'DE' => array(
				array(
                    'standard' => 19,
                    'reduced'  => array( 7 )
                ),
			),
			'DK' => array(
				array(
                    'standard' => 25,
                    'reduced'  => array()
                ),
			),
			'EE' => array(
				array(
                    'standard' => 20,
                    'reduced'  => array( 9 )
                ),
			),
			'GR' => array(
				array(
				    'standard' => 24,
				    'reduced'  => array( 6, 13 )
                ),
			),
			'ES' => array(
				array(
                    'standard'      => 21,
                    'reduced'       => array( 10 ),
                    'super-reduced' => 4
                ),
			),
			'FI' => array(
				array(
                    'standard' => 24,
                    'reduced'  => array( 10, 14 )
                ),
			),
			'FR' => array(
				array(
                    'standard'      => 20,
                    'reduced'       => array( 5.5, 10 ),
                    'super-reduced' => 2.1
                ),
			),
			'HR' => array(
				array(
                    'standard' => 25,
                    'reduced'  => array( 5, 13 )
                ),
			),
			'HU' => array(
				array(
                    'standard' => 27,
                    'reduced'  => array( 5, 18 )
                ),
			),
			'IE' => array(
				array(
                    'standard'      => 23,
                    'reduced'       => array( 9, 13.5 ),
                    'super-reduced' => 4.8
                ),
			),
			'IT' => array(
				array(
                    'standard'      => 22,
                    'reduced'       => array( 5, 10 ),
                    'super-reduced' => 4
                ),
			),
			'LT' => array(
				array(
				    'standard' => 21,
				    'reduced'  => array( 5, 9 )
                ),
			),
			'LU' => array(
				array(
                    'standard'      => 17,
                    'reduced'       => array( 8 ),
                    'super-reduced' => 3
                ),
			),
			'LV' => array(
				array(
                    'standard' => 21,
                    'reduced'  => array( 12, 5 )
                ),
			),
			'MC' => array(
				array(
                    'standard'      => 20,
                    'reduced'       => array( 5.5, 10 ),
                    'super-reduced' => 2.1
                ),
			),
			'MT' => array(
				array(
                    'standard' => 18,
                    'reduced'  => array( 5, 7 )
                ),
			),
			'NL' => array(
				array(
                    'standard' => 21,
                    'reduced'  => array( 9 )
                ),
			),
			'PL' => array(
				array(
                    'standard' => 23,
                    'reduced'  => array( 5, 8 )
                ),
			),
			'PT' => array(
				array(
					// Madeira
					'postcode' => array( '90*', '91*', '92*', '93*', '94*' ),
					'standard' => 22,
					'reduced'  => array( 5, 12 ),
					'name'     => _x( 'Madeira', 'oss', 'woocommerce-germanized' )
				),
				array(
					// Acores
					'postcode' => array( '95*', '96*', '97*', '98*' ),
					'standard' => 18,
					'reduced'  => array( 4, 9 ),
					'name'     => _x( 'Acores', 'oss', 'woocommerce-germanized' )
				),
				array(
                    'standard' => 23,
                    'reduced'  => array( 6, 13 )
                ),
			),
			'RO' => array(
				array(
                    'standard' => 19,
                    'reduced'  => array( 5, 9 )
                ),
			),
			'SE' => array(
				array(
                    'standard' => 25,
                    'reduced'  => array( 6, 12 )
                ),
			),
			'SI' => array(
				array(
                    'standard' => 22,
                    'reduced'  => array( 9.5 )
                ),
			),
			'SK' => array(
				array(
                    'standard' => 20,
                    'reduced'  => array( 10 )
                ),
			),
			'GB' => array(
				array(
                    'standard' => 20,
                    'reduced'  => array( 5 ),
                    'postcode' => array( 'BT*' ),
                    'name'     => _x( 'Northern Ireland', 'oss', 'woocommerce-germanized' )
                ),
			),
		);

		foreach( self::get_vat_postcode_exemptions_by_country() as $country => $exempt_postcodes ) {
		    if ( array_key_exists( $country, $rates ) ) {
			    $default_rate = array_values( $rates[ $country ] )[0];

			    $postcode_exempt = array(
				    'postcode' => $exempt_postcodes,
				    'standard' => 0,
				    'reduced'  => sizeof( $default_rate['reduced'] ) > 1 ? array( 0, 0 ) : array( 0 ),
				    'name'     => _x( 'Exempt', 'oss-tax-rate-import', 'woocommerce-germanized' )
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
	        foreach( $rate->postcode as $postcode ) {
	            if ( 'BT' === substr( $postcode, 0, 2 ) ) {
	                return true;
	            }
	        }
	    }

	    return false;
	}

	public static function import_rates( $rates, $tax_class = '' ) {
		global $wpdb;

		$eu_countries = WC()->countries->get_european_union_countries( 'eu_vat' );
		$exemptions   = self::get_vat_postcode_exemptions_by_country();

		/**
		 * Delete EU tax rates and make sure tax rate locations are deleted too
		 */
		foreach( \WC_Tax::get_rates_for_tax_class( $tax_class ) as $rate_id => $rate ) {
		    if ( in_array( $rate->tax_rate_country, $eu_countries ) || self::tax_rate_is_northern_ireland( $rate ) ) {
			    \WC_Tax::_delete_tax_rate( $rate_id );
		    }
		}

		$count = 0;

		foreach ( $rates as $rate ) {
		    $rate = wp_parse_args( $rate, array(
                'rate'     => 0,
                'country'  => '',
                'postcode' => '',
                'name'     => '',
            ) );

		    $iso      = wc_strtoupper( $rate['country'] );
		    $vat_desc = $iso;

		    if ( ! empty( $rate['name'] ) ) {
			    $vat_desc = $vat_desc . ' ' . $rate['name'];
		    }

		    $vat_rate = wc_format_decimal( $rate['rate'], false, true );

		    $tax_rate_name = apply_filters( 'oss_import_tax_rate_name', sprintf( _x( 'VAT %1$s %% %2$s', 'oss-tax-rate-import', 'woocommerce-germanized' ), $vat_rate, $vat_desc ), $rate['rate'], $iso, $tax_class, $rate );

			$_tax_rate = array(
				'tax_rate_country'  => $iso,
				'tax_rate_state'    => '',
				'tax_rate'          => (string) number_format( (double) wc_clean( $rate['rate'] ), 4, '.', '' ),
				'tax_rate_name'     => $tax_rate_name,
				'tax_rate_compound' => 0,
				'tax_rate_priority' => 1,
				'tax_rate_order'    => $count++,
				'tax_rate_shipping' => ( strstr( $tax_class, 'virtual' ) ? 0 : 1 ),
				'tax_rate_class'    => $tax_class
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

		foreach( $taxes as $tax ) {
			if ( $tax->get_rate_id() == $rate_id ) {
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

	protected static function get_tax_rate_percentage( $rate_id ) {
	    $percentage = false;

		if ( is_callable( array( 'WC_Tax', 'get_rate_percent_value' ) ) ) {
			$percentage = \WC_Tax::get_rate_percent_value( $rate_id );
		} elseif ( is_callable( array( 'WC_Tax', 'get_rate_percent' ) ) ) {
			$percentage = filter_var( \WC_Tax::get_rate_percent( $rate_id ), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
		}

		return $percentage;
	}
}
