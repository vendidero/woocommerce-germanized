<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZD_DHL_Parcel_Shops {

	/**
	 * Single instance of WC_GZD_DHL_Parcel_Shops Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public static function instance( $plugin = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $plugin );
		}

		return self::$_instance;
	}

	public function __construct() {

		$this->address_hooks();

		if ( $this->is_enabled() ) {

			// Register fields before WC_GZD_Checkout is loaded
			add_filter( 'woocommerce_gzd_custom_checkout_fields', array( $this, 'init_fields' ), 10, 1 );
			add_filter( 'woocommerce_gzd_custom_checkout_admin_fields', array( $this, 'init_admin_fields' ), 10, 1 );

			add_action( 'woocommerce_gzd_registered_scripts', array( $this, 'load_scripts' ), 10, 3 );
			add_action( 'woocommerce_gzd_localized_scripts', array( $this, 'localize_scripts' ), 10, 1 );

			add_action( 'woocommerce_checkout_process', array( $this, 'manipulate_checkout_fields' ), 10 );
			add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_fields' ), 10, 1 );

			// My Account Edit Address
			add_filter( 'woocommerce_shipping_fields', array( $this, 'manipulate_address_fields' ), 20, 1 );
			add_filter( 'woocommerce_process_myaccount_field_shipping_parcelshop', array( $this, 'validate_address_fields' ), 10, 1 );

			// Customer fields
			add_filter( 'woocommerce_customer_meta_fields', array( $this, 'init_profile_fields' ), 10, 1 );

			add_action( 'woocommerce_before_checkout_form', array( $this, 'maybe_hide_fields_before_rendering' ), 10, 1 );

			if ( $this->is_finder_enabled() ) {
				// Add Markup
				add_filter( 'woocommerce_form_field_checkbox', array( $this, 'add_button_markup' ), 10, 4 );
				add_action( 'wp_footer', array( $this, 'add_overlay_markup' ), 50 );
			}
		}
	}

	public function maybe_hide_fields_before_rendering( $checkout ) {

		$hide_fields = false;
		$chosen_shipping_methods = wc_gzd_get_chosen_shipping_rates();

		foreach ( $chosen_shipping_methods as $rate ) {
			if ( in_array( $rate->id, $this->get_disabled_shipping_methods() ) ) {
				$hide_fields = true;
			}
		}

		if ( apply_filters( 'woocommerce_gzd_dhl_parcel_shops_hide_fields', $hide_fields, $this ) ) {
			add_filter( 'woocommerce_checkout_fields', array( $this, 'remove_fields' ), 10, 1 );
		}
	}

	public function get_disabled_shipping_methods() {

		if ( get_option( 'woocommerce_gzd_display_checkout_shipping_rate_select' ) !== 'yes' )
			return array();

		return get_option( 'woocommerce_gzd_dhl_parcel_shop_disabled_shipping_methods', array() );
	}

	public function remove_fields( $fields ) {

		if ( isset( $fields[ 'shipping' ][ 'shipping_parcelshop' ] ) ) {
			unset( $fields[ 'shipping' ][ 'shipping_parcelshop' ] );
		}

		return $fields;
	}

	public function address_hooks() {
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'set_formatted_shipping_address' ), 20, 2 );
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'set_formatted_billing_address' ), 20, 2 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'set_formatted_address' ), 20, 2 );
		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'set_address_format' ), 20 );
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'set_user_address' ), 10, 3 );
	}

	public function set_address_format( $formats ) {

		foreach( $this->get_supported_countries() as $country ) {

			if ( ! isset( $formats[ $country ] ) )
				continue;

			$format = $formats[ $country ];
			$format = str_replace( "{name}", "{name}\n{parcelshop_post_number}", $format );
			$formats[ $country ] = $format;
		}

		return $formats;
	}

	public function set_formatted_shipping_address( $fields = array(), $order ) {
		$fields[ 'parcelshop_post_number' ] = '';

		if ( wc_gzd_get_crud_data( $order, 'shipping_parcelshop' ) ) {
			$fields[ 'parcelshop_post_number' ] = wc_gzd_get_crud_data( $order, 'shipping_parcelshop_post_number' );
		}

		return $fields;
	}

	public function set_formatted_billing_address( $fields = array(), $order ) {
		$fields[ 'parcelshop_post_number' ] = '';

		return $fields;
	}

	public function set_formatted_address( $placeholder, $args ) {
		if ( isset( $args[ 'parcelshop_post_number' ] ) ) {
			$placeholder[ '{parcelshop_post_number}' ] = $args[ 'parcelshop_post_number' ];
			$placeholder[ '{parcelshop_post_number_upper}' ] = strtoupper( $args[ 'parcelshop_post_number' ] );
		} else {
			$placeholder[ '{parcelshop_post_number}' ] = '';
			$placeholder[ '{parcelshop_post_number_upper}' ] = '';
		}
		return $placeholder;
	}

	public function set_user_address( $address, $customer_id, $name ) {
		if ( 'shipping' === $name ) {
			if ( get_user_meta( $customer_id, $name . '_parcelshop', true ) ) {
				$address[ 'parcelshop_post_number' ] = get_user_meta( $customer_id, $name . '_parcelshop_post_number', true );
			}
		}
		return $address;
	}

	public function init_fields( $fields ) {

		$fields[ 'parcelshop' ] = array(
			'type' 	   => 'checkbox',
			'required' => false,
			'label'    => __( 'Send to DHL Parcel Shop?', 'woocommerce-germanized' ),
			'before'   => 'address_1',
			'group'    => array( 'shipping' ),
			'class'    => array( 'form-row-wide', 'first-check' ),
			'hidden'   => $this->maybe_hide_fields(),
		);

		$fields[ 'parcelshop_post_number' ] = array(
			'type' 	   => 'text',
			'required' => true,
			'label'    => __( 'Postnumber', 'woocommerce-germanized' ),
			'before'   => 'address_1',
			'group'    => array( 'shipping' ),
			'class'    => array( 'form-row-wide' ),
			'hidden'   => $this->maybe_hide_fields(),
		);

		return $fields;
	}

	public function maybe_hide_fields() {
		return apply_filters( 'woocommerce_gzd_dhl_parcel_shops_hide_fields', false, $this );
	}

	public function init_profile_fields( $fields ) {

		$fields[ 'shipping' ][ 'fields' ][ 'shipping_parcelshop' ] = array(
			'label'       => __( 'DHL Parcel Shop?', 'woocommerce-germanized' ),
			'type'		  => 'select',
			'options'     => array( 0 => __( 'No', 'woocommerce-germanized' ), 1 => __( 'Yes', 'woocommerce-germanized' ) ),
			'description' => __( 'Select whether delivery to parcel shop should be enabled.', 'woocommerce-germanized' ),
		);

		$fields[ 'shipping' ][ 'fields' ][ 'shipping_parcelshop_post_number' ] = array(
			'label'       => __( 'Postnumber', 'woocommerce-germanized' ),
			'type'		  => 'text',
			'description' => __( 'In case delivery to parcel shop is enabled please fill in the corresponding DHL post number.', 'woocommerce-germanized' ),
		);

		return $fields;

	}

	public function init_admin_fields( $fields ) {

		$fields[ 'parcelshop_post_number' ] = array(
			'type' 	        => 'text',
			'label'         => __( 'Postnumber', 'woocommerce-germanized' ),
			'before'        => 'address_1',
			'address_type'  => 'shipping',
			'show'          => false,
		);

		return $fields;
	}

	public function is_enabled() {
		return ( get_option( 'woocommerce_gzd_dhl_parcel_shops' ) === 'yes' );
	}

	public function is_finder_enabled() {
		return ( get_option( 'woocommerce_gzd_dhl_parcel_shop_finder' ) === 'yes' );
	}

	public function manipulate_address_fields( $fields ) {
		global $wp;

		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			return $fields;
		}

		if ( empty( $_POST['action'] ) || 'edit_address' !== $_POST['action'] || empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-edit_address' ) ) {
			return $fields;
		}

		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return $fields;
		}

		if ( ! isset( $_POST[ 'shipping_parcelshop' ] ) && isset( $fields[ 'shipping_parcelshop_post_number' ] ) ) {
			$fields[ 'shipping_parcelshop_post_number' ][ 'required' ] = false;
		} else {
			$fields[ 'shipping_address_1' ][ 'label' ] = __( 'Parcel Shop', 'woocommerce-germanized' );
		}

		return $fields;
	}

	public function validate_address_fields( $value ) {
		if ( $value && ! empty( $value ) ) {
			$data = array(
				'shipping_parcelshop_post_number' => '',
				'shipping_parcelshop' => '',
				'shipping_country' => '',
			);

			foreach( $data as $key => $val ) {
				if ( isset( $_POST[ $key ] ) ) {
					$data[ $key ] = sanitize_text_field( $_POST[ $key ] );
				}
			}

			$this->validate_fields( $data );
		}

		return $value;
	}

	public function manipulate_checkout_fields() {
		if ( ! WC()->checkout()->get_value( 'shipping_parcelshop' ) ) {
			$fields = WC()->checkout()->checkout_fields;
			$fields[ 'shipping' ][ 'shipping_parcelshop_post_number' ][ 'required' ] = false;
			WC()->checkout()->checkout_fields = $fields;
		} else {
			$fields = WC()->checkout()->checkout_fields;
			$fields[ 'shipping' ][ 'shipping_address_1' ][ 'label' ] = __( 'Parcel Shop', 'wooocommerce-germanized' );
			WC()->checkout()->checkout_fields = $fields;
		}
	}

	public function validate_fields( $data ) {

		$required = array(
			'shipping_parcelshop_post_number',
			'shipping_parcelshop'
		);

		foreach( $required as $req ) {
			if ( ! isset( $data[ $req ] ) || empty( $data[ $req ] ) )
				return;
		}

		$is_valid_postnumber = (bool) preg_match( '/^([0-9]*)$/', $data[ 'shipping_parcelshop_post_number' ] );

		if ( ! $is_valid_postnumber ) {
			wc_add_notice( __( 'Your PostNumber should contain numbers only', 'woocommerce-germanized' ), 'error' );
		}

		$is_valid_shipping_country = in_array( $data[ 'shipping_country' ], $this->get_supported_countries() );

		if ( ! $is_valid_shipping_country ) {
			wc_add_notice( sprintf( __( 'Parcel Shop Delivery is only supported in: %s.', 'woocommerce-germanized' ), implode( ', ', $this->get_supported_countries( true ) ) ), 'error' );
		}
	}

	public function load_scripts( $suffix, $frontend_script_path, $assets_path ) {

		if ( is_checkout() || is_wc_endpoint_url( 'edit-address' ) ) {

			$deps = array( 'jquery' );

			if ( is_checkout() )
				array_push( $deps, 'wc-checkout' );

			wp_enqueue_script( 'wc-gzd-checkout-dhl-parcel-shops', $frontend_script_path . 'checkout-dhl-parcel-shops' . $suffix . '.js', $deps, WC_GERMANIZED_VERSION, true );

			if ( $this->is_finder_enabled() ) {
				wp_register_style( 'wc-gzd-checkout-dhl-parcel-shops-finder', $assets_path . 'css/woocommerce-gzd-dhl-parcel-shop-finder' . $suffix . '.css', '', WC_GERMANIZED_VERSION, 'all' );
				wp_enqueue_style( 'wc-gzd-checkout-dhl-parcel-shops-finder' );
			}
		}
	}

	public function localize_scripts( $assets_path ) {

		$lang = substr( get_bloginfo( "language" ), 0, 2 );

		if ( wp_script_is( 'wc-gzd-checkout-dhl-parcel-shops' ) ) {
			wp_localize_script( 'wc-gzd-checkout-dhl-parcel-shops', 'wc_gzd_dhl_parcel_shops_params', apply_filters( 'wc_gzd_dhl_parcel_shops_params', array(
				'address_field_title'       => __( 'Parcel Shop', 'woocommerce-germanized' ),
				'address_field_placeholder' => __( 'Parcel Shop', 'woocommerce-germanized' ),
				'supported_countries'       => $this->get_supported_countries(),
				'enable_finder'             => $this->is_finder_enabled(),
				'button_wrapper'            => '#wc-gzd-parcel-shop-finder-button-wrapper',
				'iframe_wrapper'            => '#wc-gzd-parcel-shop-finder-iframe-wrapper',
				'iframe_src'                => '//parcelshopfinder.dhlparcel.com/partnerservice.html?setLng=' . $lang,
				'shipping_country_error'    => sprintf( __( 'Parcel Shop Delivery is only supported in: %s.', 'woocommerce-germanized' ), implode( ', ', $this->get_supported_countries( true ) ) ),
			) ) );
		}
	}

	public function add_overlay_markup() {
		if ( is_checkout() || is_wc_endpoint_url( 'edit-address' ) ) {
			wc_get_template( 'checkout/dhl-parcel-shop-finder.php' );
		}
	}

	public function add_button_markup( $field, $key, $args, $value ) {

		if ( 'shipping_parcelshop' === $key ) {
			if ( substr( $field, -4 ) === "</p>" ) {
				$field = substr( $field, 0, -4 );
				$field .= apply_filters( 'woocommerce_gzd_dhl_parcel_finder_button_html', '<span id="wc-gzd-parcel-shop-finder-button-wrapper"><a class="wc-gzd-parcel-finder-open-button" href="#">' . __( 'Parcel Shop Finder', 'woocommerce-germanized' ) . '</a></span></p><div class="clear"></div>' );
			}
		}

		return $field;
	}

	public function get_supported_countries( $as_names = false ) {

		$codes = apply_filters( 'woocommerce_gzd_dhl_parcel_shops_countries', (array) get_option( 'woocommerce_gzd_dhl_parcel_shop_supported_countries', array( 'DE', 'AT' ) ) );

		if ( $as_names ) {
			$names = WC()->countries->get_countries();
			return array_intersect_key( $names, array_flip( $codes ) );
		} else {
			return $codes;
		}
	}
}

WC_GZD_DHL_Parcel_Shops::instance();