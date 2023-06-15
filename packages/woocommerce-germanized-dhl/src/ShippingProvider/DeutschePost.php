<?php
/**
 * ShippingProvider impl.
 *
 * @package WooCommerce/Blocks
 */
namespace Vendidero\Germanized\DHL\ShippingProvider;

use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ShippingProvider\Auto;

defined( 'ABSPATH' ) || exit;

class DeutschePost extends Auto {

	protected function get_default_label_minimum_shipment_weight() {
		return 0.01;
	}

	protected function get_default_label_default_shipment_weight() {
		return 0.5;
	}

	public function supports_customer_return_requests() {
		return true;
	}

	public function get_help_link() {
		return 'https://vendidero.de/dokument/internetmarke-integration-einrichten';
	}

	public function get_signup_link() {
		return 'https://portokasse.deutschepost.de/portokasse/#!/register/';
	}

	public function get_label_classname( $type ) {
		if ( 'return' === $type ) {
			return '\Vendidero\Germanized\DHL\Label\DeutschePostReturn';
		} else {
			return '\Vendidero\Germanized\DHL\Label\DeutschePost';
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
		return 'https://www.deutschepost.de/sendung/simpleQueryResult.html?form.sendungsnummer={tracking_id}&form.einlieferungsdatum_tag={label_date_day}&form.einlieferungsdatum_monat={label_date_month}&form.einlieferungsdatum_jahr={label_date_year}';
	}

	public function get_api_username( $context = 'view' ) {
		return $this->get_meta( 'api_username', true, $context );
	}

	public function set_api_username( $username ) {
		$this->update_meta_data( 'api_username', strtolower( $username ) );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_available_label_products( $shipment ) {
		return wc_gzd_dhl_get_deutsche_post_products( $shipment );
	}

	protected function get_available_base_countries() {
		return Package::get_available_countries();
	}

	protected function get_general_settings( $for_shipping_method = false ) {
		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'deutsche_post_general_options',
			),

			array(
				'title'             => _x( 'Username', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Your credentials to the <a href="%s" target="_blank">Portokasse</a>. Please test your credentials before connecting.', 'dhl', 'woocommerce-germanized' ), 'https://portokasse.deutschepost.de/portokasse/#!/' ) . '</div>',
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

		$settings = array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'dhl_product_options',
				),

				array(
					'title' => _x( 'Tracking', 'dhl', 'woocommerce-germanized' ),
					'type'  => 'title',
					'id'    => 'tracking_options',
				),
			)
		);

		$general_settings = parent::get_general_settings( $for_shipping_method );

		return array_merge( $settings, $general_settings );
	}

	private function is_save_settings_request() {
		$is_settings_save             = ( isset( $_POST['available_products'] ) && isset( $_GET['provider'] ) && 'deutsche_post' === wc_clean( wp_unslash( $_GET['provider'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended
		$is_ajax_shipping_method_save = wp_doing_ajax() && isset( $_GET['action'] ) && 'woocommerce_shipping_zone_methods_save_settings' === wc_clean( wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended

		return $is_ajax_shipping_method_save || $is_settings_save;
	}

	protected function get_label_settings( $for_shipping_method = false ) {
		$im                  = Package::get_internetmarke_api();
		$settings            = parent::get_label_settings( $for_shipping_method );
		$settings_url        = $this->get_edit_link( 'label' );
		$screen              = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		$page_format_options = array();
		$product_options     = array(
			'available'         => array(),
			'default_available' => $im ? $im->get_default_available_products() : array(),
			'dom'               => array(),
			'eu'                => array(),
			'int'               => array(),
		);

		/**
		 * Do only allow calling IM API during admin setting (save) requests.
		 */
		if ( is_admin() && ( ( $screen && 'woocommerce_page_wc-settings' === $screen->id ) || $this->is_save_settings_request() ) ) {
			if ( $im && $im->is_configured() && $im->auth() && $im->is_available() ) {
				$im->reload_products();

				$page_format_options = $im->get_page_format_list();

				$product_options = array(
					'available'         => $this->get_product_select_options(),
					'default_available' => $im ? $im->get_default_available_products() : array(),
					'dom'               => wc_gzd_dhl_get_deutsche_post_products_domestic( false, false ),
					'eu'                => wc_gzd_dhl_get_deutsche_post_products_eu( false, false ),
					'int'               => wc_gzd_dhl_get_deutsche_post_products_international( false, false ),
				);

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
			} elseif ( $im && $im->has_errors() ) {
				$settings = array_merge(
					$settings,
					array(
						array(
							'title' => _x( 'API Error', 'dhl', 'woocommerce-germanized' ),
							'type'  => 'title',
							'id'    => 'deutsche_post_api_error',
							'desc'  => '<div class="notice inline notice-error"><p>' . implode( ', ', $im->get_errors()->get_error_messages() ) . '</p></div>',
						),
						array(
							'type' => 'sectionend',
							'id'   => 'deutsche_post_api_error',
						),
					)
				);

				return $settings;
			}
		}

		if ( $im && $im->is_configured() ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'title'          => _x( 'Products', 'dhl', 'woocommerce-germanized' ),
						'type'           => 'title',
						'id'             => 'deutsche_post_product_options',
						'allow_override' => true,
					),

					array(
						'title'          => _x( 'Available Products', 'dhl', 'woocommerce-germanized' ),
						'id'             => 'available_products',
						'class'          => 'wc-enhanced-select',
						'desc'           => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Choose the products you want to be available for your shipments from the list above. Manually <a href="%s">refresh</a> the product list to make sure it is up-to-date.', 'dhl', 'woocommerce-germanized' ), esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wc-gzd-dhl-im-product-refresh' ), $settings_url ), 'wc-gzd-dhl-refresh-im-products' ) ) ) . '</div>',
						'type'           => 'multiselect',
						'value'          => $this->get_setting( 'available_products', $product_options['default_available'] ),
						'options'        => $product_options['available'],
						'default'        => $product_options['default_available'],
						'allow_override' => false,
					),

					array(
						'title'   => _x( 'Domestic Default Service', 'dhl', 'woocommerce-germanized' ),
						'type'    => 'select',
						'default' => '',
						'value'   => $this->get_setting( 'label_default_product_dom', '' ),
						'id'      => 'label_default_product_dom',
						'desc'    => '<div class="wc-gzd-additional-desc">' . _x( 'Please select your default shipping service for domestic shipments that you want to offer to your customers (you can always change this within each individual shipment afterwards).', 'dhl', 'woocommerce-germanized' ) . '</div>',
						'options' => $product_options['dom'],
						'class'   => 'wc-enhanced-select',
					),

					array(
						'title'   => _x( 'EU Default Service', 'dhl', 'woocommerce-germanized' ),
						'type'    => 'select',
						'default' => '',
						'value'   => $this->get_setting( 'label_default_product_eu', '' ),
						'id'      => 'label_default_product_eu',
						'desc'    => '<div class="wc-gzd-additional-desc">' . _x( 'Please select your default shipping service for EU shipments that you want to offer to your customers.', 'dhl', 'woocommerce-germanized' ) . '</div>',
						'options' => $product_options['eu'],
						'class'   => 'wc-enhanced-select',
					),

					array(
						'title'   => _x( 'Int. Default Service', 'dhl', 'woocommerce-germanized' ),
						'type'    => 'select',
						'default' => '',
						'value'   => $this->get_setting( 'label_default_product_int', '' ),
						'id'      => 'label_default_product_int',
						'desc'    => '<div class="wc-gzd-additional-desc">' . _x( 'Please select your default shipping service for cross-border shipments that you want to offer to your customers.', 'dhl', 'woocommerce-germanized' ) . '</div>',
						'options' => $product_options['int'],
						'class'   => 'wc-enhanced-select',
					),

					array(
						'type' => 'sectionend',
						'id'   => 'deutsche_post_product_options',
					),
				)
			);

			$settings = array_merge(
				$settings,
				array(
					array(
						'title' => _x( 'Printing', 'dhl', 'woocommerce-germanized' ),
						'type'  => 'title',
						'id'    => 'deutsche_post_print_options',
					),

					array(
						'title'   => _x( 'Default Format', 'dhl', 'woocommerce-germanized' ),
						'id'      => 'label_default_page_format',
						'class'   => 'wc-enhanced-select',
						'desc'    => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Choose a print format which will be selected by default when creating labels. Manually <a href="%s">refresh</a> available print formats to make sure the list is up-to-date.', 'dhl', 'woocommerce-germanized' ), esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'wc-gzd-dhl-im-page-formats-refresh' ), $settings_url ), 'wc-gzd-dhl-refresh-im-page-formats' ) ) ) . '</div>',
						'type'    => 'select',
						'value'   => $this->get_setting( 'label_default_page_format', 1 ),
						'options' => $page_format_options,
						'default' => 1,
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
						'id'   => 'deutsche_post_print_options',
					),
				)
			);
		}

		return $settings;
	}

	protected function get_product_select_options() {
		$products = Package::get_internetmarke_api()->get_products();
		$options  = wc_gzd_dhl_im_get_product_list( $products, false );

		return $options;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_label_fields( $shipment ) {
		if ( ! Package::get_internetmarke_api()->is_available() ) {
			return Package::get_internetmarke_api()->get_errors();
		}

		return parent::get_label_fields( $shipment );
	}

	protected function get_portokasse_charge_button() {
		if ( ! Package::get_internetmarke_api()->get_user() ) {
			return '';
		}

		$balance      = Package::get_internetmarke_api()->get_balance();
		$user_token   = Package::get_internetmarke_api()->get_user()->getUserToken();
		$settings_url = $this->get_edit_link();

		$html = '
			<input type="text" placeholder="10.00" style="max-width: 150px; margin-right: 10px;" class="wc-input-price short" name="woocommerce_gzd_dhl_im_portokasse_charge_amount" id="woocommerce_gzd_dhl_im_portokasse_charge_amount" />
			<a id="woocommerce_gzd_dhl_im_portokasse_charge" class="button button-secondary" data-url="https://portokasse.deutschepost.de/portokasse/marketplace/enter-app-payment" data-success_url="' . esc_url( add_query_arg( array( 'wallet-charge-success' => 'yes' ), $settings_url ) ) . '" data-cancel_url="' . esc_url( add_query_arg( array( 'wallet-charge-success' => 'no' ), $settings_url ) ) . '" data-partner_id="' . esc_attr( Package::get_internetmarke_partner_id() ) . '" data-key_phase="' . esc_attr( Package::get_internetmarke_key_phase() ) . '" data-user_token="' . esc_attr( $user_token ) . '" data-schluessel_dpwn_partner="' . esc_attr( Package::get_internetmarke_token() ) . '" data-wallet="' . esc_attr( $balance ) . '">' . _x( 'Charge Portokasse', 'dhl', 'woocommerce-germanized' ) . '</a>
			<p class="description">' . sprintf( _x( 'The minimum amount is %s', 'dhl', 'woocommerce-germanized' ), wc_price( 10, array( 'currency' => 'EUR' ) ) ) . '</p>
		';

		return $html;
	}

	public function get_label_fields_html( $shipment ) {
		$html  = parent::get_label_fields_html( $shipment );
		$html .= '
			<div class="columns preview-columns wc-gzd-dhl-im-product-data">
		        <div class="column col-4">
		            <p class="wc-gzd-dhl-im-product-price wc-price data-placeholder hide-default" data-replace="price_formatted"></p>
		        </div>
		        <div class="column col-3 col-dimensions">
		            <p class="wc-gzd-dhl-im-product-dimensions data-placeholder hide-default" data-replace="dimensions_formatted"></p>
		        </div>
		        <div class="column col-5 col-preview">
		            <div class="image-preview"></div>
		        </div>
		        <div class="column col-12">
		            <p class="wc-gzd-dhl-im-product-description data-placeholder hide-default" data-replace="description_formatted"></p>
		            <p class="wc-gzd-dhl-im-product-information-text data-placeholder hide-default" data-replace="information_text_formatted"></p>
		        </div>
		    </div>
		';

		return $html;
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_return_label_fields( $shipment ) {
		return $this->get_simple_label_fields( $shipment );
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 *
	 * @return array|\WP_Error
	 */
	protected function get_simple_label_fields( $shipment ) {
		$settings     = parent::get_simple_label_fields( $shipment );
		$default_args = $this->get_default_available_label_args( $shipment );
		$products     = $this->get_available_label_products( $shipment );
		$is_wp_int    = false;

		/**
		 * Replace the product id (which might contain services) with the default parent id.
		 * Otherwise the correct (parent only) product would not be selected from the available products lists.
		 */
		if ( ! empty( $default_args['product_id'] ) ) {
			foreach ( $settings as $key => $setting ) {
				if ( 'product_id' === $setting['id'] ) {
					$settings[ $key ]['value'] = $default_args['product_id'];
				}
			}

			$is_wp_int = Package::get_internetmarke_api()->is_warenpost_international( $default_args['product_id'] );
		}

		if ( empty( $products ) ) {
			return new \WP_Error( 'dp-label-missing-products', sprintf( _x( 'Sorry but none of your selected <a href="%s">Deutsche Post Products</a> is available for this shipment. Please verify your shipment data (e.g. weight) and try again.', 'dhl', 'woocommerce-germanized' ), esc_url( $this->get_edit_link( 'label' ) ) ) );
		}

		$settings = array_merge( $settings, $this->get_available_additional_services( $default_args['product_id'], $default_args['services'] ) );

		if ( ! $is_wp_int ) {
			$settings = array_merge(
				$settings,
				array(
					array(
						'id'          => 'page_format',
						'label'       => _x( 'Page Format', 'dhl', 'woocommerce-germanized' ),
						'description' => '',
						'type'        => 'select',
						'options'     => Package::get_internetmarke_api()->get_page_format_list(),
						'value'       => isset( $default_args['page_format'] ) ? $default_args['page_format'] : '',
					),
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
		}

		return $settings;
	}

	public function get_available_additional_services( $product_id, $selected_services = array() ) {
		$im_product_id = Package::get_internetmarke_api()->get_product_id( $product_id );
		$services      = \Vendidero\Germanized\DHL\Package::get_internetmarke_api()->get_product_list()->get_services_for_product( $im_product_id, $selected_services );
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
				'label'         => \Vendidero\Germanized\DHL\Package::get_internetmarke_api()->get_product_list()->get_additional_service_title( $service ),
				'value'         => in_array( $service, $selected_services, true ) ? 'yes' : 'no',
			);
		}

		$settings[] = array(
			'type' => 'wrapper_end',
		);

		return $settings;
	}

	protected function get_default_available_label_args( $shipment, $default_args = array() ) {
		if ( empty( $default_args ) ) {
			$default_args = $this->get_default_label_props( $shipment );
		}

		$im_all_products   = wc_gzd_dhl_get_deutsche_post_products( $shipment, false );
		$default_product   = isset( $default_args['product_id'] ) ? $default_args['product_id'] : array_keys( $im_all_products )[0];
		$selected_product  = isset( $im_all_products[ $default_product ] ) ? $default_product : array_keys( $im_all_products )[0];
		$selected_services = isset( $default_args['services'] ) ? $default_args['services'] : array();

		if ( ! empty( $selected_product ) ) {
			/**
			 * Do only override services in case the product is a child product and force parent code.
			 */
			if ( ! Package::get_internetmarke_api()->product_code_is_parent( $selected_product ) ) {
				$selected_services = Package::get_internetmarke_api()->get_product_services( $selected_product );
				$selected_product  = Package::get_internetmarke_api()->get_product_parent_code( $selected_product );
			}
		}

		return array_replace_recursive(
			$default_args,
			array(
				'services'    => $selected_services,
				'product_id'  => $selected_product,
				'page_format' => $default_args['page_format'],
			)
		);
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_default_label_product( $shipment ) {
		$country  = $shipment->get_country();
		$postcode = $shipment->get_postcode();

		if ( 'return' === $shipment->get_type() ) {
			$country  = $shipment->get_sender_country();
			$postcode = $shipment->get_sender_postcode();
		}

		if ( Package::is_shipping_domestic( $country, $postcode ) ) {
			return $this->get_shipment_setting( $shipment, 'label_default_product_dom' );
		} elseif ( Package::is_eu_shipment( $country, $postcode ) ) {
			return $this->get_shipment_setting( $shipment, 'label_default_product_eu' );
		} else {
			return $this->get_shipment_setting( $shipment, 'label_default_product_int' );
		}
	}

	/**
	 * @param \Vendidero\Germanized\Shipments\Shipment $shipment
	 */
	public function get_available_label_services( $shipment ) {
		$services = array();

		if ( $im = Package::get_internetmarke_api()->get_product_list() ) {
			$services = array_keys( $im->get_additional_services() );
		}

		return $services;
	}

	protected function get_default_label_props( $shipment ) {
		$dp_defaults = $this->get_default_simple_label_props( $shipment );
		$defaults    = parent::get_default_label_props( $shipment );
		$defaults    = array_replace_recursive( $defaults, $dp_defaults );

		if ( ! empty( $defaults['product_id'] ) ) {
			/**
			 * Get current services from the selected product.
			 */
			$defaults['services'] = Package::get_internetmarke_api()->get_product_services( $defaults['product_id'] );

			/**
			 * Force parent product by default to allow manually selecting services.
			 */
			$defaults['product_id'] = Package::get_internetmarke_api()->get_product_parent_code( $defaults['product_id'] );
		}

		if ( ! empty( $defaults['product_id'] ) ) {
			$defaults['stamp_total'] = Package::get_internetmarke_api()->get_product_total( $defaults['product_id'] );
		}

		return $defaults;
	}

	protected function get_default_simple_label_props( $shipment ) {
		$defaults = array(
			'page_format' => $this->get_shipment_setting( $shipment, 'label_default_page_format' ),
			'position_x'  => $this->get_shipment_setting( $shipment, 'label_position_x' ),
			'position_y'  => $this->get_shipment_setting( $shipment, 'label_position_y' ),
			'stamp_total' => 0,
			'services'    => array(),
		);

		return $defaults;
	}

	/**
	 * @param Shipment $shipment
	 *
	 * @return array
	 */
	protected function get_default_return_label_props( $shipment ) {
		$defaults                   = $this->get_default_simple_label_props( $shipment );
		$defaults['sender_address'] = $shipment->get_address();

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
				'page_format' => '',
				'product_id'  => '',
			)
		);

		$error = new \WP_Error();

		if ( ! empty( $args['services'] ) ) {
			/**
			 * Additional services are requested. Lets check whether the actual product exists and
			 * refresh the product code (to the child product code).
			 */
			$im_product_code = Package::get_internetmarke_api()->get_product_code( $args['product_id'], $args['services'] );

			if ( false === $im_product_code ) {
				$error->add( 500, _x( 'The services chosen are not available for the current product.', 'dhl', 'woocommerce-germanized' ) );
			} else {
				$args['product_id'] = $im_product_code;
			}
		}

		$available_products = wc_gzd_dhl_get_deutsche_post_products( $shipment, true );

		/**
		 * Force the product to check to parent id because some services might not be explicitly added as
		 * available products.
		 */
		$im_parent_code = Package::get_internetmarke_api()->get_product_parent_code( $args['product_id'] );

		/**
		 * Check whether the product might not be available for the current shipment
		 */
		if ( ! array_key_exists( $im_parent_code, $available_products ) ) {
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
				reset( $available_products );
				$im_product_code = Package::get_internetmarke_api()->get_product_parent_code( key( $available_products ) );

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

		if ( wc_gzd_dhl_wp_error_has_errors( $error ) ) {
			return $error;
		}

		return $args;
	}
}
