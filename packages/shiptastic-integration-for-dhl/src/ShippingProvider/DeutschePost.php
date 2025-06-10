<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Shiptastic\DHL\ShippingProvider;

use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShipmentError;
use Vendidero\Shiptastic\ShippingProvider\Auto;
use Vendidero\Shiptastic\ShippingProvider\Product;
use Vendidero\Shiptastic\ShippingProvider\ProductList;
use Vendidero\Shiptastic\ShippingProvider\ServiceList;

defined( 'ABSPATH' ) || exit;

class DeutschePost extends Auto {

	use PickupDeliveryTrait;

	protected function get_default_label_default_print_format() {
		return 1;
	}

	public function supports_customer_return_requests() {
		return true;
	}

	public function get_help_link() {
		return 'https://vendidero.de/doc/woocommerce-germanized/internetmarke-integration-einrichten';
	}

	public function get_signup_link() {
		return 'https://portokasse.deutschepost.de/portokasse/#!/register/';
	}

	public function get_label_classname( $type ) {
		if ( 'return' === $type ) {
			return '\Vendidero\Shiptastic\DHL\Label\DeutschePostReturn';
		} else {
			return '\Vendidero\Shiptastic\DHL\Label\DeutschePost';
		}
	}

	/**
	 * @param false|\WC_Order $order
	 *
	 * @return bool
	 */
	public function supports_customer_returns( $order = false ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		/**
		 * Return labels are only supported for DE
		 */
		if ( $order && 'DE' !== $order->get_shipping_country() ) {
			return false;
		}

		return parent::supports_customer_returns( $order );
	}

	public function supports_labels( $label_type, $shipment = false ) {
		$label_types = array( 'simple', 'return' );

		/**
		 * Return labels are only supported for DE
		 */
		if ( 'return' === $label_type && $shipment && 'return' === $shipment->get_type() && 'DE' !== $shipment->get_sender_country() ) {
			return false;
		}

		return in_array( $label_type, $label_types, true );
	}

	public function get_title( $context = 'view' ) {
		return _x( 'Deutsche Post', 'dhl', 'woocommerce-germanized' );
	}

	public function get_name( $context = 'view' ) {
		return 'deutsche_post';
	}

	public function get_description( $context = 'view' ) {
		return _x( 'Integration for products of the Deutsche Post through Internetmarke.', 'dhl', 'woocommerce-germanized' );
	}

	public function get_default_tracking_url_placeholder() {
		return 'https://deutschepost.de/de/s/sendungsverfolgung.html?piececode={tracking_id}';
	}

	public function get_api_username( $context = 'view' ) {
		return $this->get_meta( 'api_username', true, $context );
	}

	public function set_api_username( $username ) {
		$this->update_meta_data( 'api_username', strtolower( $username ) );
	}

	protected function get_available_base_countries() {
		return Package::get_available_countries();
	}

	protected function get_printing_settings() {
		$settings     = parent::get_printing_settings();
		$settings_url = $this->get_edit_link( '' );

		$settings = array_merge(
			array(
				array(
					'title' => _x( 'Printing', 'dhl', 'woocommerce-germanized' ),
					'type'  => 'title',
					'id'    => 'shipping_provider_label_printing_options',
					'desc'  => '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Choose a print format which will be selected by default when creating labels. Manually <a href="%s">refresh</a> available print formats to make sure the list is up-to-date.', 'dhl', 'woocommerce-germanized' ), esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wc-stc-dhl-im-page-formats-refresh' ), $settings_url ), 'wc-stc-dhl-refresh-im-page-formats' ) ) ) . '</div>',
				),
				array(
					'title'             => _x( 'Print X-axis column', 'dhl', 'woocommerce-germanized' ),
					'id'                => 'label_position_x',
					'desc_tip'          => _x( 'Adjust the print X-axis start column for the label.', 'dhl', 'woocommerce-germanized' ),
					'type'              => 'number',
					'value'             => $this->get_setting( 'label_position_x', 1 ),
					'custom_attributes' => array(
						'min'  => 0,
						'step' => 1,
					),
					'css'               => 'max-width: 100px;',
					'default'           => 1,
				),
				array(
					'title'             => _x( 'Print Y-axis column', 'dhl', 'woocommerce-germanized' ),
					'id'                => 'label_position_y',
					'desc_tip'          => _x( 'Adjust the print Y-axis start column for the label.', 'dhl', 'woocommerce-germanized' ),
					'type'              => 'number',
					'value'             => $this->get_setting( 'label_position_y', 1 ),
					'custom_attributes' => array(
						'min'  => 0,
						'step' => 1,
					),
					'css'               => 'max-width: 100px;',
					'default'           => 1,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'shipping_provider_label_format_options',
				),
			),
			$settings
		);

		return $settings;
	}

	public function test_connection() {
		if ( $im = Package::get_internetmarke_api() ) {
			if ( $im->is_configured() && $im->auth() ) {
				$preview = $im->preview_stamp( 31 );

				return $preview ? true : false;
			}
		}

		return false;
	}

	protected function get_general_settings() {
		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'deutsche_post_general_options',
			),

			array(
				'title'             => _x( 'Username', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => '<div class="wc-shiptastic-additional-desc ">' . sprintf( _x( 'Your credentials to the <a href="%s" target="_blank">Portokasse</a>. Please test your credentials before connecting.', 'dhl', 'woocommerce-germanized' ), 'https://portokasse.deutschepost.de/portokasse/#!/' ) . '</div>',
				'id'                => 'api_username',
				'default'           => '',
				'value'             => $this->get_setting( 'api_username', '' ),
				'custom_attributes' => array( 'autocomplete' => 'new-password' ),
			),

			array(
				'title'             => _x( 'Password', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'password',
				'id'                => 'api_password',
				'default'           => '',
				'value'             => $this->get_setting( 'api_password', '' ),
				'custom_attributes' => array( 'autocomplete' => 'new-password' ),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'deutsche_post_general_options',
			),
		);

		if ( $im = Package::get_internetmarke_api() ) {
			$im->reload_products();

			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

			if ( is_admin() && $screen && in_array( $screen->id, array( 'woocommerce_page_wc-settings' ), true ) ) {
				if ( $im->is_configured() && $im->auth() ) {
					if ( isset( $_GET['provider'] ) && 'deutsche_post' === $_GET['provider'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						$balance = $im->get_balance( true );

						$settings = array_merge(
							$settings,
							array(
								array(
									'title' => _x( 'Portokasse', 'dhl', 'woocommerce-germanized' ),
									'type'  => 'title',
									'id'    => 'deutsche_post_portokasse_options',
								),
								array(
									'title' => _x( 'Balance', 'dhl', 'woocommerce-germanized' ),
									'type'  => 'html',
									'id'    => 'im_balance',
									'html'  => wc_price( Package::cents_to_eur( $balance ), array( 'currency' => 'EUR' ) ),
								),

								array(
									'title' => _x( 'Charge (€)', 'dhl', 'woocommerce-germanized' ),
									'type'  => 'dp_charge',
								),

								array(
									'type' => 'sectionend',
									'id'   => 'deutsche_post_portokasse_options',
								),
							)
						);
					}
				}

				if ( $im->is_configured() ) {
					$settings_url = $this->get_edit_link( '' );

					$settings = array_merge(
						$settings,
						array(
							array(
								'title' => _x( 'Products', 'dhl', 'woocommerce-germanized' ),
								'type'  => 'title',
								'id'    => 'deutsche_post_product_refresh_options',
								'desc'  => '<a class="button button-secondary" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wc-stc-dhl-im-product-refresh' ), $settings_url ), 'wc-stc-dhl-refresh-im-products' ) ) . '">' . esc_html_x( 'Refresh available products', 'dhl', 'woocommerce-germanized' ) . '</a>',
							),
							array(
								'type' => 'sectionend',
								'id'   => 'deutsche_post_product_refresh_options',
							),
						)
					);
				}
			}
		}

		$settings = array_merge(
			$settings,
			array(
				array(
					'title' => _x( 'Tracking', 'dhl', 'woocommerce-germanized' ),
					'type'  => 'title',
					'id'    => 'tracking_options',
				),
			)
		);

		$general_settings = parent::get_general_settings();

		return array_merge( $settings, $general_settings );
	}

	protected function register_services() {
		foreach ( Package::get_internetmarke_api()->get_product_list()->get_additional_services() as $service => $label ) {
			$this->register_service(
				$service,
				array(
					'label'              => $label,
					'shipment_types'     => array( 'return', 'simple' ),
					'excluded_locations' => wc_stc_get_shipping_provider_service_locations(),
				)
			);
		}
	}

	protected function register_print_formats() {
		if ( $im = Package::get_internetmarke_api() ) {
			$print_list = $im->get_page_format_list();

			asort( $print_list );

			foreach ( $print_list as $page_format_id => $page_format ) {
				$this->register_print_format(
					$page_format_id,
					array(
						'label' => $page_format,
					)
				);
			}
		}
	}

	protected function register_products() {
		global $wpdb;

		if ( ! get_transient( 'wc_stc_dhl_im_products_expire' ) ) {
			if ( Package::get_internetmarke_api()->is_configured() ) {
				$result = Package::get_internetmarke_api()->get_product_list()->update();

				if ( is_wp_error( $result ) ) {
					Package::log( 'Error while refreshing Internetmarke product data: ' . $result->get_error_message() );
				}
			}

			/**
			 * Refresh product data once per day.
			 */
			set_transient( 'wc_stc_dhl_im_products_expire', 'yes', DAY_IN_SECONDS );
		}

		$products = wp_cache_get( 'im_products', 'shiptastic-dhl' );

		if ( false === $products ) {
			$products = $wpdb->get_results( "SELECT * FROM {$wpdb->stc_dhl_im_products}" );

			wp_cache_set( 'im_products', $products, 'shiptastic-dhl' );
		}

		foreach ( (array) $products as $product ) {
			$this->register_product(
				$product->product_code,
				array(
					'id'             => $product->product_code,
					'label'          => $product->product_name,
					'description'    => $product->product_description,
					'shipment_types' => array( 'simple', 'return' ),
					'internal_id'    => $product->product_id,
					'parent_id'      => $product->product_parent_id,
					'zones'          => 'national' === $product->product_destination ? array( 'dom' ) : array( 'eu', 'int' ),
					'price'          => $product->product_price,
					'length'         => array(
						'min' => $product->product_length_min,
						'max' => $product->product_length_max,
					),
					'width'          => array(
						'min' => $product->product_width_min,
						'max' => $product->product_width_max,
					),
					'height'         => array(
						'min' => $product->product_height_min,
						'max' => $product->product_height_max,
					),
					'weight'         => array(
						'min' => $product->product_weight_min,
						'max' => $product->product_weight_max,
					),
					'weight_unit'    => 'g',
					'dimension_unit' => 'mm',
					'meta'           => array(
						'information_text' => $product->product_information_text,
						'annotation'       => $product->product_annotation,
						'destination'      => $product->product_destination,
					),
				)
			);
		}
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 */
	public function get_label_fields( $shipment ) {
		if ( ! Package::get_internetmarke_api()->auth() ) {
			return new ShipmentError( 500, sprintf( _x( 'Your Portokasse doesn\'t seem to be configured. Please check your <a href="%s">settings</a>.', 'dhl', 'woocommerce-germanized' ), Package::get_deutsche_post_shipping_provider()->get_edit_link() ) );
		}

		return parent::get_label_fields( $shipment );
	}

	public function get_label_fields_html( $shipment ) {
		$html  = parent::get_label_fields_html( $shipment );
		$html .= '
			<div class="columns preview-columns wc-stc-dhl-im-product-data">
		        <div class="column col-4">
		            <p class="wc-stc-dhl-im-product-price wc-price data-placeholder hide-default" data-replace="price_formatted"></p>
		        </div>
		        <div class="column col-3 col-dimensions">
		            <p class="wc-stc-dhl-im-product-dimensions data-placeholder hide-default" data-replace="dimensions_formatted"></p>
		        </div>
		        <div class="column col-5 col-preview">
		            <div class="image-preview"></div>
		        </div>
		        <div class="column col-12">
		            <p class="wc-stc-dhl-im-product-description data-placeholder hide-default" data-replace="description_formatted"></p>
		            <p class="wc-stc-dhl-im-product-information-text data-placeholder hide-default" data-replace="information_text_formatted"></p>
		        </div>
		    </div>
		';

		return $html;
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_return_label_fields( $shipment ) {
		return $this->get_simple_label_fields( $shipment );
	}

	/**
	 * @param \Vendidero\Shiptastic\Shipment $shipment
	 *
	 * @return array|\WP_Error
	 */
	protected function get_simple_label_fields( $shipment ) {
		$props    = $this->get_default_label_props( $shipment );
		$products = $this->get_products(
			array(
				'shipment'  => $shipment,
				'parent_id' => 0,
			)
		);
		$settings = parent::get_simple_label_fields( $shipment );

		/**
		 * When retrieving the label fields make sure to only include parent products
		 * in case the parent product exists (e.g. the Maxibrief Integral + Zusatzentgelt may be available,
		 * although it's parent Maxibrief is not available).
		 */
		foreach ( $settings[0]['options'] as $product_id => $label ) {
			if ( $product = $this->get_product( $product_id ) ) {
				if ( $product->get_parent_id() > 0 ) {
					$parent_code = Package::get_internetmarke_api()->get_product_parent_code( $product->get_id() );

					if ( array_key_exists( $parent_code, $settings[0]['options'] ) ) {
						unset( $settings[0]['options'][ $product_id ] );
					}
				}
			}
		}

		if ( $products->empty() ) {
			return new \WP_Error( 'dp-label-missing-products', sprintf( _x( 'Sorry but none of your selected <a href="%s">Deutsche Post Products</a> is available for this shipment. Please verify your shipment data (e.g. weight) and try again.', 'dhl', 'woocommerce-germanized' ), esc_url( $this->get_edit_link( 'label' ) ) ) );
		}

		$settings = array_merge( $settings, $this->get_available_additional_services( $props['product_id'], $props['services'] ) );
		$settings = array_merge(
			$settings,
			array(
				array(
					'id'   => '',
					'type' => 'columns',
				),
				array(
					'id'                => 'position_x',
					'label'             => _x( 'Print X-Position', 'dhl', 'woocommerce-germanized' ),
					'description'       => '',
					'type'              => 'number',
					'wrapper_class'     => 'column col-6',
					'style'             => 'width: 100%;',
					'custom_attributes' => array(
						'min'  => 0,
						'step' => 1,
					),
					'value'             => isset( $default_args['position_x'] ) ? $default_args['position_x'] : 1,
				),
				array(
					'id'                => 'position_y',
					'label'             => _x( 'Print Y-Position', 'dhl', 'woocommerce-germanized' ),
					'description'       => '',
					'type'              => 'number',
					'wrapper_class'     => 'column col-6',
					'style'             => 'width: 100%;',
					'custom_attributes' => array(
						'min'  => 0,
						'step' => 1,
					),
					'value'             => isset( $default_args['position_y'] ) ? $default_args['position_y'] : 1,
				),
			)
		);

		return $settings;
	}

	public function get_available_additional_services( $product_id, $selected_services = array() ) {
		$im_product_id = $this->get_product( $product_id )->get_internal_id();
		$services      = \Vendidero\Shiptastic\DHL\Package::get_internetmarke_api()->get_product_list()->get_services_for_product( $im_product_id, $selected_services );
		$settings      = array(
			array(
				'id'   => 'additional-services',
				'type' => 'wrapper',
			),
		);

		foreach ( $services as $service ) {
			$settings[] = array(
				'id'            => 'service_' . $service,
				'wrapper_class' => 'form-field-checkbox',
				'type'          => 'checkbox',
				'label'         => \Vendidero\Shiptastic\DHL\Package::get_internetmarke_api()->get_product_list()->get_additional_service_title( $service ),
				'value'         => in_array( $service, $selected_services, true ) ? 'yes' : 'no',
			);
		}

		$settings[] = array(
			'type' => 'wrapper_end',
		);

		return $settings;
	}

	protected function get_default_label_props( $shipment ) {
		$dp_defaults = $this->get_default_simple_label_props( $shipment );
		$defaults    = parent::get_default_label_props( $shipment );
		$available   = $this->get_available_label_products( $shipment );
		$defaults    = array_replace_recursive( $defaults, $dp_defaults );

		if ( ! empty( $defaults['product_id'] ) ) {
			if ( $product = $this->get_product( $defaults['product_id'] ) ) {
				$defaults['stamp_total'] = Package::get_internetmarke_api()->get_product_total( $defaults['product_id'] );

				if ( $product->get_parent_id() > 0 ) {
					$parent_code = Package::get_internetmarke_api()->get_product_parent_code( $product->get_id() );

					if ( array_key_exists( $parent_code, $available ) ) {
						$defaults['services']   = Package::get_internetmarke_api()->get_product_services( $product->get_id() );
						$defaults['product_id'] = Package::get_internetmarke_api()->get_product_parent_code( $product->get_id() );
					}
				} else {
					/**
					 * Get current services from the selected product.
					 */
					$defaults['services'] = Package::get_internetmarke_api()->get_product_services( $defaults['product_id'] );
				}
			}
		}

		return $defaults;
	}

	protected function get_default_simple_label_props( $shipment ) {
		$defaults = array(
			'position_x'  => $this->get_setting( 'label_position_x' ),
			'position_y'  => $this->get_setting( 'label_position_y' ),
			'stamp_total' => 0,
			'services'    => array(),
		);

		return $defaults;
	}

	/**
	 * @param Shipment $shipment
	 * @param $props
	 *
	 * @return \WP_Error|mixed
	 */
	protected function validate_label_request( $shipment, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'product_id' => '',
				'services'   => array(),
			)
		);

		$error = new \WP_Error();

		if ( ! empty( $args['services'] ) ) {
			/**
			 * Additional services are requested. Let's check whether the actual product exists and
			 * refresh the product code (to the child product code).
			 */
			$im_product_code = Package::get_internetmarke_api()->get_product_code( $args['product_id'], $args['services'] );

			if ( false === $im_product_code ) {
				$error->add( 500, _x( 'The services chosen are not available for the current product.', 'dhl', 'woocommerce-germanized' ) );
			} else {
				$args['product_id'] = $im_product_code;
			}
		}

		$available_products = $this->get_products( array( 'shipment' => $shipment ) );

		/**
		 * Check whether the product might not be available for the current shipment
		 */
		if ( ! $available_products->get( $args['product_id'] ) ) {
			/**
			 * In case no other products are available or this is a manual request - return error
			 */
			if ( empty( $available_products ) || ( is_admin() && current_user_can( 'manage_woocommerce' ) ) ) {
				$error->add( 500, sprintf( _x( 'Sorry but none of your selected <a href="%s">Deutsche Post Products</a> is available for this shipment. Please verify your shipment data (e.g. weight) and try again.', 'dhl', 'woocommerce-germanized' ), esc_url( $this->get_edit_link( 'label' ) ) ) );
			} else {
				/**
				 * In case the chosen product is not available - use the first product available instead
				 * to prevent errors during automation (connected with the default product option which might not fit).
				 */
				$im_product_code = Package::get_internetmarke_api()->get_product_parent_code( $available_products->get_by_index( 0 )->get_id() );

				if ( ! empty( $args['services'] ) ) {
					$im_product_code_additional = Package::get_internetmarke_api()->get_product_code( $im_product_code, $args['services'] );

					if ( false !== $im_product_code_additional ) {
						$im_product_code = $im_product_code_additional;
					}
				}

				$args['product_id'] = $im_product_code;
			}
		}

		/**
		 * Refresh stamp total based on actual product.
		 */
		if ( ! empty( $args['product_id'] ) ) {
			$args['stamp_total'] = Package::get_internetmarke_api()->get_product_total( $args['product_id'] );
		} else {
			$error->add( 500, sprintf( _x( 'Deutsche Post product is missing for %s.', 'dhl', 'woocommerce-germanized' ), $shipment->get_id() ) );
		}

		if ( wc_stc_dhl_wp_error_has_errors( $error ) ) {
			return $error;
		}

		return $args;
	}

	public function get_setting_sections() {
		$sections = parent::get_setting_sections();

		return $sections;
	}

	protected function get_pickup_locations_settings() {
		$settings = parent::get_pickup_locations_settings();

		$settings = array_merge(
			$settings,
			array(
				array(
					'title' => '',
					'type'  => 'title',
					'id'    => 'deutsche_post_pickup_options',
				),
				array(
					'title'             => _x( 'Packstation', 'dhl', 'woocommerce-germanized' ),
					'desc'              => _x( 'Enable delivery to Packstation.', 'dhl', 'woocommerce-germanized' ),
					'desc_tip'          => _x( 'Let customers choose a Packstation as delivery address.', 'dhl', 'woocommerce-germanized' ),
					'id'                => 'parcel_pickup_packstation_enable',
					'value'             => wc_bool_to_string( $this->get_setting( 'parcel_pickup_packstation_enable' ) ),
					'default'           => 'yes',
					'type'              => 'shiptastic_toggle',
					'custom_attributes' => array( 'data-show_if_pickup_locations_enable' => '' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'deutsche_post_pickup_options',
				),
			)
		);

		return $settings;
	}
}
