<?php

class WC_GZD_Checkout {

	public $custom_fields = array();
	public $custom_fields_admin = array();

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

		if ( get_option( 'woocommerce_gzd_checkout_address_field' ) == 'yes' ) {

			$this->custom_fields[ 'title' ] = array(
				'type' 	   => 'select',
				'required' => 1,
				'label'    => __( 'Title', 'woocommerce-germanized' ),
				'options'  => array( 1 => __( 'Sir', 'woocommerce-germanized' ), 2 => __( 'Madam', 'woocommerce-germanized' ) ),
				'before'   => 'first_name',
				'group'    => array( 'billing', 'shipping' ),
			);

			$this->custom_fields_admin[ 'title' ] = array(
				'before'   => 'first_name',
				'type'     => 'select',
				'options'  => array( 1 => __( 'Sir', 'woocommerce-germanized' ), 2 => __( 'Madam', 'woocommerce-germanized' ) ),
				'show'     => false,
				'label'    => __( 'Title', 'woocommerce-germanized' ),
			);

		}

		if ( get_option( 'woocommerce_gzd_checkout_phone_required' ) == 'no' ) {

			$this->custom_fields[ 'phone' ] = array(
				'before'   => '',
				'override' => true,
				'required' => false,
				'group'    => array( 'billing' )
			);

		}

		add_filter( 'woocommerce_billing_fields', array( $this, 'set_custom_fields' ), 0, 1 );
		add_filter( 'woocommerce_shipping_fields', array( $this, 'set_custom_fields_shipping' ), 0, 1 );
		// Add Fields to Order Edit Page
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'set_custom_fields_admin' ), 0, 1 );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'set_custom_fields_admin' ), 0, 1 );
		// Save Fields on order
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_fields' ) );
		// Add Title to billing address format
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'set_formatted_billing_address' ), 0, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'set_formatted_shipping_address' ), 0, 2 );
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'set_formatted_address' ), 0, 2 );
		// Add item desc to order
		add_action( 'woocommerce_order_add_product', array( $this, 'set_item_desc_order_meta' ), 0, 5 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'set_item_desc_order_meta_hidden' ), 0 );
	}

	public function set_item_desc_order_meta( $order_id, $item_id, $product, $qty, $args ) {
		wc_add_order_item_meta( $item_id, '_product_desc', $product->get_mini_desc() );
	}

	public function set_item_desc_order_meta_hidden( $metas ) {
		array_push( $metas, '_product_desc' );
		return $metas;
	}

	public function set_formatted_billing_address( $fields = array(), $order ) {
		if ( $order->billing_title )
			$fields[ 'title' ] = $this->get_customer_title( $order->billing_title );
		return $fields;
	}

	public function set_formatted_shipping_address( $fields = array(), $order ) {
		if ( $order->shipping_title )
			$fields[ 'title' ] = $this->get_customer_title( $order->shipping_title );
		return $fields;
	}

	public function get_customer_title( $option = 1 ) {
		return ( isset( $this->custom_fields[ 'title' ][ 'options' ][ $option ] ) ? $this->custom_fields[ 'title' ][ 'options' ][ $option ] : false );
	}

	public function set_formatted_address( $placeholder, $args ) {
		if ( isset( $args[ 'title' ] ) ) {
			$placeholder[ '{title}' ] = $args[ 'title' ];
			$placeholder[ '{title_upper}' ] = strtoupper( $args[ 'title' ] );
			$placeholder[ '{name}' ] = $placeholder[ '{title}' ] . ' ' . $placeholder[ '{name}' ];
			$placeholder[ '{name_upper}' ] = $placeholder[ '{title_upper}' ] . ' ' . $placeholder[ '{name_upper}' ];
		}
		return $placeholder;
	}

	public function set_custom_fields( $fields = array(), $type = 'billing' ) {
		$new = array();
		if ( ! empty( $this->custom_fields ) ) {
			foreach ( $this->custom_fields as $key => $custom_field ) {
				if ( in_array( $type, $custom_field[ 'group' ] ) ) {
					if ( ! empty( $fields ) ) {
						foreach ( $fields as $name => $field ) {
							if ( $name == $type . '_' . $custom_field[ 'before' ] && ! isset( $custom_field[ 'override' ] ) )
								$new[ $type . '_' . $key ] = $custom_field;
							$new[ $name ] = $field;
							if ( $name == $type . '_' . $key && isset( $custom_field[ 'override' ] ) )
								$new[ $name ] = array_merge( $field, $custom_field );
						}
					}
				}
			}
		}
		return ( ! empty( $new ) ? $new : $fields );
	}

	public function set_custom_fields_shipping( $fields ) {
		return $this->set_custom_fields( $fields, 'shipping' );
	}

	public function set_custom_fields_admin( $fields = array() ) {
		$new = array();
		if ( ! empty( $this->custom_fields_admin ) ) {
			foreach ( $this->custom_fields_admin as $key => $custom_field ) {
				if ( ! empty( $fields ) ) {
					foreach ( $fields as $name => $field ) {
						if ( $name == $custom_field[ 'before' ] && ! isset( $custom_field[ 'override' ] ) )
							$new[ $key ] = $custom_field;
						$new[ $name ] = $field;
					}
				}
			}
		}
		return ( ! empty( $new ) ? $new : $fields );
	}

	public function save_fields( $order_id ) {
		if ( ! empty( $this->custom_fields ) ) {
			foreach ( $this->custom_fields as $key => $custom_field ) {
				if ( ! empty( $custom_field[ 'group' ] ) && ! isset( $custom_field[ 'override' ] ) ) {
					foreach ( $custom_field[ 'group' ] as $group ) {
						if ( ! empty( $_POST[ $group . '_' . $key ] ) )
							update_post_meta( $order_id, '_' . $group . '_' . $key, sanitize_text_field( $_POST[ $group . '_' . $key ] ) );
					}
				}
			}
		}
	}

}

WC_GZD_Checkout::instance();