<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * AJAX Handler
 *
 * @class        WC_GZD_AJAX
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_AJAX {

	/**
	 * Hook in methods
	 */
	public static function init() {
		$ajax_events = array(
			'gzd_revocation'                    => true,
			'gzd_refresh_unit_price'            => true,
			'gzd_refresh_cart_vouchers'         => true,
			'gzd_json_search_delivery_time'     => false,
			'gzd_json_search_manufacturer'      => false,
			'gzd_legal_checkboxes_save_changes' => false,
			'gzd_toggle_tab_enabled'            => false,
			'gzd_install_extension'             => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_woocommerce_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				// WC AJAX can be used for frontend ajax requests.
				add_action( 'wc_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	public static function gzd_install_extension() {
		check_ajax_referer( 'wc_gzd_install_extension_nonce', 'security' );

		if ( ! current_user_can( 'install_plugins' ) || ! isset( $_POST['extension'] ) ) {
			wp_die( - 1 );
		}

		$extension = wc_clean( wp_unslash( $_POST['extension'] ) );

		if ( ! empty( $extension ) && \Vendidero\Germanized\PluginsHelper::is_plugin_whitelisted( $extension ) ) {
			$result = \Vendidero\Germanized\PluginsHelper::install_or_activate_extension( $extension );

			if ( \Vendidero\Germanized\PluginsHelper::is_plugin_active( $extension ) ) {
				wp_send_json(
					array(
						'success' => true,
					)
				);
			} else {
				$plugin_name = \Vendidero\Germanized\PluginsHelper::get_plugin_name( $extension );
				$message     = sprintf( __( 'There was an error while automatically installing %1$s. %2$s', 'woocommerce-germanized' ), esc_html( $plugin_name ), \Vendidero\Germanized\PluginsHelper::get_plugin_manual_install_message( $extension ) );

				wp_send_json(
					array(
						'message' => $message,
					)
				);
			}
		}
	}

	public static function gzd_toggle_tab_enabled() {
		check_ajax_referer( 'wc_gzd_tab_toggle_nonce', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['tab'] ) || ! isset( $_POST['enable'] ) ) {
			wp_die( - 1 );
		}

		$tab_id = wc_clean( wp_unslash( $_POST['tab'] ) );
		$enable = wc_string_to_bool( wc_clean( wp_unslash( $_POST['enable'] ) ) );

		$pages = WC_Admin_Settings::get_settings_pages();

		foreach ( $pages as $page ) {

			if ( is_a( $page, 'WC_GZD_Settings_Germanized' ) ) {
				if ( $tab = $page->get_tab_by_name( $tab_id ) ) {
					if ( $enable ) {
						$tab->enable();
						$data = array(
							'data'    => true,
							'message' => '',
						);

						if ( $tab->notice_on_activate() ) {
							$data['message'] = $tab->notice_on_activate();
						}

						wp_send_json( $data );
					} else {
						$tab->disable();

						wp_send_json(
							array(
								'data' => false,
							)
						);
					}
				}
			}
		}

		wp_send_json(
			array(
				'data' => false,
			)
		);
	}

	public static function gzd_legal_checkboxes_save_changes() {
		if ( ! isset( $_POST['wc_gzd_legal_checkbox_nonce'], $_POST['changes'] ) ) {
			wp_send_json_error( 'missing_fields' );
			wp_die();
		}

		if ( ! wp_verify_nonce( wp_unslash( $_POST['wc_gzd_legal_checkbox_nonce'] ), 'wc_gzd_legal_checkbox_nonce' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_send_json_error( 'bad_nonce' );
			wp_die();
		}

		// Check User Caps
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( 'missing_capabilities' );
			wp_die();
		}

		$changes = wc_clean( wp_unslash( $_POST['changes'] ) );
		$manager = WC_GZD_Legal_Checkbox_Manager::instance();
		$options = $manager->get_options();

		foreach ( $changes as $id => $data ) {

			$checkbox = $manager->get_checkbox( $id );

			if ( isset( $data['deleted'] ) ) {
				if ( isset( $data['newRow'] ) ) {
					// So the user added and deleted a new row.
					// That's fine, it's not in the database anyways. NEXT!
					continue;
				}
				// Delete
				if ( isset( $options[ $id ] ) ) {
					// Do not allow to delete core entries
					if ( $checkbox && $checkbox->is_core() ) {
						continue;
					}
					$manager->delete( $id );
					unset( $options[ $id ] );
				}
				continue;
			}

			/**
			 * Filters legal checkbox default option keys.
			 *
			 * @param array $args Option keys.
			 *
			 * @since 2.0.0
			 *
			 */
			$keys = apply_filters(
				'woocommerce_gzd_legal_checkboxes_option_keys',
				array(
					'id'       => '',
					'priority' => 1,
				)
			);

			$checkbox_data = array_intersect_key( $data, $keys );

			if ( ! isset( $options[ $id ] ) || ! is_array( $options[ $id ] ) ) {
				$options[ $id ] = array();
			}

			$options[ $id ] = array_replace_recursive( $options[ $id ], $checkbox_data );
		}

		$manager->update_options( $options );
		$manager->do_register_action();

		$checkboxes = $manager->get_checkboxes( array(), 'json' );

		wp_send_json_success(
			array(
				'checkboxes' => $checkboxes,
			)
		);
	}

	public static function gzd_json_search_delivery_time() {
		ob_start();

		check_ajax_referer( 'search-products', 'security' );
		$term  = isset( $_GET['term'] ) ? (string) wc_clean( wp_unslash( $_GET['term'] ) ) : '';
		$terms = array();

		if ( empty( $term ) ) {
			die();
		}

		$args = array(
			'hide_empty' => false,
			'fields'     => 'id=>name',
		);

		if ( is_numeric( $term ) ) {
			$args['include'] = array( absint( $term ) );
		} else {
			$args['name__like'] = (string) $term;
		}

		$query = WC_germanized()->delivery_times->get_delivery_times( $args );

		if ( ! empty( $query ) ) {
			foreach ( $query as $term_id => $term_name ) {
				$terms[ $term_id ] = rawurldecode( $term_name );
			}
		} else {
			$terms[ rawurldecode( $term ) ] = rawurldecode( sprintf( __( '%s [new]', 'woocommerce-germanized' ), $term ) );
		}
		wp_send_json( $terms );
	}

	public static function gzd_json_search_manufacturer() {
		ob_start();

		check_ajax_referer( 'search-products', 'security' );
		$term  = isset( $_GET['term'] ) ? (string) wc_clean( wp_unslash( $_GET['term'] ) ) : '';
		$terms = array();

		if ( empty( $term ) ) {
			die();
		}

		$args = array(
			'hide_empty' => false,
			'fields'     => 'id=>name',
		);

		if ( is_numeric( $term ) ) {
			$args['include'] = array( absint( $term ) );
		} else {
			$args['name__like'] = (string) $term;
		}

		$query = WC_germanized()->manufacturers->get_manufacturers( $args );

		if ( ! empty( $query ) ) {
			foreach ( $query as $term_id => $term_name ) {
				$terms[ $term_id ] = rawurldecode( $term_name );
			}
		} else {
			$terms[ rawurldecode( $term ) ] = rawurldecode( sprintf( __( '%s [new]', 'woocommerce-germanized' ), $term ) );
		}
		wp_send_json( $terms );
	}

	/**
	 * @param $price
	 * @param WC_Product $product
	 */
	protected static function get_price_excluding_tax( $price, $product ) {
		$tax_rates    = WC_Tax::get_rates( $product->get_tax_class() );
		$remove_taxes = WC_Tax::calc_tax( $price, $tax_rates, true );
		$price        = $price - array_sum( $remove_taxes ); // Unrounded since we're dealing with tax inclusive prices. Matches logic in cart-totals class. @see adjust_non_base_location_price.

		return \Vendidero\Germanized\Utilities\NumberUtil::round( $price, wc_get_price_decimals() );
	}

	protected static function get_price_including_tax( $price, $product ) {
		// If prices are shown excl. tax, add taxes to match the prices stored in the DB.
		$tax_rates = WC_Tax::get_rates( $product->get_tax_class() );
		$taxes     = WC_Tax::calc_tax( $price, $tax_rates, false );
		$price     = $price + array_sum( $taxes );

		return \Vendidero\Germanized\Utilities\NumberUtil::round( $price, wc_get_price_decimals() );
	}

	public static function gzd_refresh_cart_vouchers() {
		check_ajax_referer( 'wc-gzd-refresh-cart-vouchers', 'security' );

		$return = array(
			'vouchers' => array(),
			'result'   => 'success',
		);

		if ( WC()->cart ) {
			// Make sure vouchers are already registered as fees.
			WC()->cart->calculate_totals();

			$return['vouchers'] = WC_GZD_Coupon_Helper::instance()->get_voucher_data_from_cart();
		}

		wp_send_json( $return );
	}

	public static function gzd_refresh_unit_price() {
		check_ajax_referer( 'wc-gzd-refresh-unit-price', 'security' );

		if ( ! isset( $_POST['products'] ) ) {
			wp_send_json( array( 'result' => 'failure' ) );
		}

		$products = (array) wc_clean( wp_unslash( $_POST['products'] ) );
		$response = array();

		foreach ( $products as $product_data ) {
			if ( ! isset( $product_data['product_id'], $product_data['price'], $product_data['key'] ) ) {
				continue;
			}

			$product_id = absint( $product_data['product_id'] );
			$key        = wc_clean( wp_unslash( $product_data['key'] ) );

			if ( ! $product = wc_gzd_get_product( $product_id ) ) {
				continue;
			}

			$args = self::get_db_prices_by_display_prices(
				$product,
				array(
					'price'      => (float) wc_clean( wp_unslash( $product_data['price'] ) ),
					'sale_price' => isset( $product_data['price_sale'] ) && '' !== wc_clean( wp_unslash( $product_data['price_sale'] ) ) ? (float) wc_clean( wp_unslash( $product_data['price_sale'] ) ) : '',
				)
			);

			$product->recalculate_unit_price( $args );

			$response[ $key ] = array(
				'unit_price_html' => $product->get_unit_price_html(),
				'product_id'      => $product_id,
			);
		}

		wp_send_json(
			array(
				'result'   => 'success',
				'products' => $response,
			)
		);
	}

	/**
	 * @param WC_GZD_Product|WC_Product $product
	 * @param $prices
	 *
	 * @return array
	 */
	public static function get_db_prices_by_display_prices( $product, $prices ) {
		$prices = wp_parse_args(
			$prices,
			array(
				'price'      => 0,
				'sale_price' => '',
			)
		);

		if ( is_a( $product, 'WC_GZD_Product' ) ) {
			$product = $product->get_wc_product();
		}

		$price            = (float) $prices['price'];
		$sale_price       = '' === $prices['sale_price'] ? '' : (float) $prices['sale_price'];
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

		/**
		 * The issue with the price passed to the unit price recalculation is that the price
		 * reflects the actual display price, e.g. a price which already includes taxes.
		 *
		 * We do need to restore the actual price stored within the DB (based on whether
		 * prices are stored including tax or not) to make sure that taxes are not added/subtracted twice.
		 */
		if ( wc_tax_enabled() && $product->is_taxable() ) {
			$price_before = $prices['price'];

			if ( 'incl' === $tax_display_mode ) {
				$price_after = wc_get_price_including_tax(
					$product,
					array(
						'price' => $price,
						'qty'   => 1,
					)
				);
			} else {
				$price_after = wc_get_price_excluding_tax(
					$product,
					array(
						'price' => $price,
						'qty'   => 1,
					)
				);
			}

			$tax_diff = abs( (float) $price_before - (float) $price_after );

			if ( $tax_diff > 0 ) {
				$sale_price_incl_taxes = '';

				/**
				 * Ugly tweaks to make sure we do not need to replicate
				 * the whole tax calculation behaviour included in wc_get_price_including_tax().
				 *
				 * The whole idea of this is to revert the tax calculation done to the display price
				 * and then add/subtract the difference to the price passed to this script.
				 *
				 * @see wc_get_price_including_tax()
				 */
				add_filter( 'wc_get_price_decimals', array( __CLASS__, 'tmp_increase_price_decimals' ), 9999 );
				add_filter( 'woocommerce_calc_tax', array( __CLASS__, 'tmp_manipulate_tax_calculation' ), 9999, 4 );

				if ( 'incl' === $tax_display_mode ) {
					$price_incl_taxes = wc_get_price_including_tax(
						$product,
						array(
							'price' => $price,
							'qty'   => 1,
						)
					);

					if ( '' !== $sale_price ) {
						$sale_price_incl_taxes = wc_get_price_including_tax(
							$product,
							array(
								'price' => $sale_price,
								'qty'   => 1,
							)
						);
					}
				} else {
					$price_incl_taxes = wc_get_price_excluding_tax(
						$product,
						array(
							'price' => $price,
							'qty'   => 1,
						)
					);

					if ( '' !== $sale_price ) {
						$sale_price_incl_taxes = wc_get_price_excluding_tax(
							$product,
							array(
								'price' => $sale_price,
								'qty'   => 1,
							)
						);
					}
				}

				remove_filter( 'wc_get_price_decimals', array( __CLASS__, 'tmp_increase_price_decimals' ), 9999 );
				remove_filter( 'woocommerce_calc_tax', array( __CLASS__, 'tmp_manipulate_tax_calculation' ), 9999 );

				if ( wc_prices_include_tax() ) {
					$price = \Vendidero\Germanized\Utilities\NumberUtil::round( $price + abs( $price - $price_incl_taxes ), wc_get_price_decimals() );

					if ( '' !== $sale_price_incl_taxes ) {
						$sale_price = \Vendidero\Germanized\Utilities\NumberUtil::round( $sale_price + abs( $sale_price - $sale_price_incl_taxes ), wc_get_price_decimals() );
					}
				} else {
					$price = \Vendidero\Germanized\Utilities\NumberUtil::round( $price - abs( $price - $price_incl_taxes ), wc_get_price_decimals() );

					if ( '' !== $sale_price_incl_taxes ) {
						$sale_price = \Vendidero\Germanized\Utilities\NumberUtil::round( $sale_price - abs( $sale_price - $sale_price_incl_taxes ), wc_get_price_decimals() );
					}
				}
			}
		}

		return apply_filters(
			'woocommerce_gzd_db_prices_by_display_prices',
			array(
				'regular_price' => $price,
				'sale_price'    => '' !== $sale_price ? $sale_price : $price,
				'price'         => '' !== $sale_price ? $sale_price : $price,
			),
			$prices,
			$product
		);
	}

	public static function tmp_increase_price_decimals() {
		return absint( WC_ROUNDING_PRECISION );
	}

	public static function tmp_manipulate_tax_calculation( $taxes, $price, $rates, $price_includes_tax ) {
		if ( true === $price_includes_tax ) {
			$taxes = WC_Tax::calc_exclusive_tax( $price, $rates );
		} else {
			$taxes = WC_Tax::calc_inclusive_tax( $price, $rates );
		}

		return $taxes;
	}

	/**
	 * Checks revocation form and sends Email to customer and Admin
	 */
	public static function gzd_revocation() {
		check_ajax_referer( 'woocommerce-revocation', 'security' );

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'woocommerce-revocation' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			wp_send_json( array( 'result' => 'failure' ) );
		}

		$data   = array();
		$fields = WC_GZD_Revocation::get_fields();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $key => $field ) {
				if ( 'sep' !== $key ) {
					if ( isset( $field['required'] ) && true === $field['required'] ) {
						if ( 'address_mail' === $key ) {
							if ( ! isset( $_POST[ $key ] ) || ! is_email( wp_unslash( $_POST[ $key ] ) ) ) {
								wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . _x( 'is not a valid email address.', 'revocation-form', 'woocommerce-germanized' ), 'error' );
							}
						} elseif ( 'address_postal' === $key ) {
							if ( ! isset( $_POST['address_country'] ) || ! isset( $_POST[ $key ] ) || ! WC_Validation::is_postcode( wc_clean( wp_unslash( $_POST[ $key ] ) ), wc_clean( wp_unslash( $_POST['address_country'] ) ) ) || empty( $_POST[ $key ] ) ) {
								wc_add_notice( _x( 'Please enter a valid postcode/ZIP', 'revocation-form', 'woocommerce-germanized' ), 'error' );
							}
						} elseif ( 'privacy_checkbox' === $key ) {
							if ( isset( $field['required'] ) && empty( $_POST[ $key ] ) ) {
								wc_add_notice( '<strong>' . $field['label'] . '</strong>', 'error' );
							}
						} elseif ( isset( $field['required'] ) && empty( $_POST[ $key ] ) ) {
								wc_add_notice( '<strong>' . $field['label'] . '</strong> ' . _x( 'is not valid.', 'revocation-form', 'woocommerce-germanized' ), 'error' );
						}
					}

					if ( isset( $_POST[ $key ] ) && ! empty( $_POST[ $key ] ) ) {
						if ( 'country' === $field['type'] ) {
							$countries    = WC()->countries->get_countries();
							$country      = wc_clean( wp_unslash( $_POST[ $key ] ) );
							$data[ $key ] = ( isset( $countries[ $country ] ) ? $countries[ $country ] : '' );
						} else {
							$data[ $key ] = wc_clean( wp_unslash( $_POST[ $key ] ) );
						}
					}
				}
			}
		}

		$error = false;

		if ( 0 === wc_notice_count( 'error' ) ) {
			wc_add_notice( _x( 'Thank you. We have received your Revocation Request. You will receive a conformation email within a few minutes.', 'revocation-form', 'woocommerce-germanized' ), 'success' );

			// Send Mail
			if ( $mail = WC_germanized()->emails->get_email_instance_by_id( 'customer_revocation' ) ) {
				// Send to customer
				$mail->trigger( $data );

				// Send to Admin
				$data['send_to_admin'] = true;
				$mail->trigger( $data );
			}
		} else {
			$error = true;
		}

		ob_start();
		wc_print_notices();
		$messages = ob_get_clean();

		$data = array(
			'messages' => isset( $messages ) ? $messages : '',
			'result'   => ( $error ? 'failure' : 'success' ),
		);

		wp_send_json( $data );
	}
}

WC_GZD_AJAX::init();
