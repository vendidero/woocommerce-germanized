<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Product_Attribute_Helper {

	protected static $_instance = null;

	public static function instance() {
        if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '10.6.0', '<' ) ) {
            return WC_GZD_Deprecated_Product_Attribute_Helper::instance();
        }

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
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheating huh?', 'woocommerce-germanized' ), '1.0' );
	}

	public function __construct() {
		// Make sure Woo uses our implementation when updating the attributes via AJAX
        add_filter( 'woocommerce_product_read_attribute', array( $this, 'read' ), 10, 2 );
        add_filter( 'woocommerce_product_importer_read_attribute', array( $this, 'read' ), 10, 2 );

		// Save additional attribute data
		add_filter( 'woocommerce_admin_meta_boxes_prepare_attribute', array( $this, 'save' ), 10, 3 );

		// Adjust cart item data to include attributes visible during cart/checkout
		add_filter( 'woocommerce_get_item_data', array( $this, 'cart_item_data_filter' ), 150, 2 );

		if ( is_admin() ) {
			add_action( 'woocommerce_after_product_attribute_settings', array( $this, 'edit' ), 10, 2 );
		}
	}

	public function read( WC_Product_Attribute $attribute, $data ) {
        $default_checkout_visible = apply_filters( 'woocommerce_gzd_product_attribute_checkout_visible_default_value', false );

		$attribute->set_extra_data( 'checkout_visible', isset( $data['checkout_visible'] ) ? $data['checkout_visible'] : $default_checkout_visible);

		return $attribute;
	}

	public function save( WC_Product_Attribute $attribute, $data, $i ) {
		$attribute_checkout_visibility = isset( $data['attribute_checkout_visibility'][$i] ) && $data['attribute_checkout_visibility'][$i];

		$attribute->set_extra_data( 'checkout_visible', $attribute_checkout_visibility );

		return $attribute;
	}

	public function edit( $attribute, $i ) {
		?>
		<tr>
			<td>
				<label><input type="checkbox" class="checkbox" <?php checked( $attribute->get_extra_data( 'checkout_visible' ), true ); ?>name="attribute_checkout_visibility[<?php echo esc_attr( $i ); ?>]" value="1"/> <?php esc_html_e( 'Visible during checkout', 'woocommerce-germanized' ); ?></label>
			</td>
		</tr>
		<?php
	}

	public function cart_item_data_filter( $item_data, $cart_item ) {
		$cart_product = $cart_item['data'];

		if ( ! $cart_product || null === $item_data ) {
			return $item_data;
		}

		$item_data_product = wc_gzd_get_gzd_product( $cart_product )->get_checkout_attributes( $item_data, isset( $cart_item['variation'] ) ? $cart_item['variation'] : array() );

		if ( $item_data !== $item_data_product ) {
			$item_data = array_replace_recursive( $item_data, $item_data_product );
		}

		return $item_data;
	}

	public function get_attribute_by_variation( $product, $name ) {
		$name = str_replace( 'attribute_', '', $name );

		if ( $parent = wc_get_product( $product->get_parent_id() ) ) {
			foreach ( $parent->get_attributes() as $key => $attribute ) {
				if ( $attribute->get_name() === $name ) {
					return $attribute;
				}
			}
		}

		return false;
	}

	protected function get_product_id( $maybe_product_id ) {
		$product_id = false;

		if ( is_numeric( $maybe_product_id ) ) {
			$product_id = $maybe_product_id;
		} elseif ( is_a( $maybe_product_id, 'WC_Product' ) || is_a( $maybe_product_id, 'WC_GZD_Product' ) ) {
			$product_id = $maybe_product_id->get_id();
		}

		return $product_id;
	}
}

WC_GZD_Product_Attribute_Helper::instance();
