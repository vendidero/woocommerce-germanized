<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Product_Attribute_Helper {

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
		add_filter(
			'woocommerce_admin_meta_boxes_prepare_attribute',
			array(
				$this,
				'prepare_attributes_filter',
			),
			10,
			3
		);
		// This is the only nice way to update attributes after Woo has updated product attributes
		add_action( 'woocommerce_product_object_updated_props', array( $this, 'update_attributes' ), 10, 2 );
		// Adjust cart item data to include attributes visible during cart/checkout
		add_filter( 'woocommerce_get_item_data', array( $this, 'cart_item_data_filter' ), 150, 2 );

		if ( is_admin() ) {
			add_action( 'woocommerce_after_product_attribute_settings', array( $this, 'attribute_visibility' ), 10, 2 );
		}
	}

	public function attribute_visibility( $attribute, $i ) {
		global $product_object, $product;

		if ( isset( $product_object ) ) {
			$gzd_product = $product_object;
		} elseif ( isset( $product ) ) {
			$gzd_product = $product;
		} else {
			$gzd_product = null;
		}

		$gzd_product_attribute = ( is_a( $attribute, 'WC_GZD_Product_Attribute' ) ? $attribute : $this->get_attribute( $attribute, $gzd_product ) );
		?>
		<tr>
			<td>
				<label><input type="checkbox" class="checkbox" <?php checked( $gzd_product_attribute->is_checkout_visible(), true ); ?>name="attribute_checkout_visibility[<?php echo esc_attr( $i ); ?>]" value="1"/> <?php esc_html_e( 'Visible during checkout', 'woocommerce-germanized' ); ?></label>
			</td>
		</tr>
		<?php
	}

	public function cart_item_data_filter( $item_data, $cart_item ) {
		$cart_product = $cart_item['data'];

		if ( ! $cart_product ) {
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
					return $this->get_attribute( $attribute, $parent );
				}
			}
		}

		return false;
	}

	public function update_attributes( $product, $updated_props ) {
		$attributes = $product->get_attributes();
		$meta       = get_post_meta( $product->get_id(), '_product_attributes', true );

		if ( $meta && is_array( $meta ) ) {
			foreach ( $meta as $meta_key => $meta_attribute ) {
				if ( isset( $attributes[ $meta_key ] ) ) {
					$attribute = $attributes[ $meta_key ];

					if ( is_a( $attribute, 'WC_GZD_Product_Attribute' ) ) {
						$meta[ $meta_key ]['checkout_visible'] = $attribute->get_checkout_visible() ? 1 : 0;
					}
				}
			}

			update_post_meta( $product->get_id(), '_product_attributes', $meta );
		}
	}

	public function prepare_attributes_filter( $attribute, $data, $i ) {
		$attribute_checkout_visibility = isset( $data['attribute_checkout_visibility'] ) ? $data['attribute_checkout_visibility'] : array();

		$attribute = new WC_GZD_Product_Attribute( $attribute );
		$attribute->set_checkout_visible( isset( $attribute_checkout_visibility[ $i ] ) );

		return $attribute;
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

	public function get_attribute( $attribute, $product_id = false ) {
		$new_attribute   = new WC_GZD_Product_Attribute( $attribute );
		$product_id      = $this->get_product_id( $product_id );
		$meta_attributes = $product_id ? get_post_meta( $product_id, '_product_attributes', true ) : array();
		$meta_key        = sanitize_title( $attribute->get_name() );

		if ( ! empty( $meta_attributes ) && is_array( $meta_attributes ) ) {
			if ( isset( $meta_attributes[ $meta_key ] ) ) {
				$meta_value = array_merge(
					array(
						/** This filter is documented in includes/class-wc-gzd-product-attribute.php */
						'checkout_visible' => apply_filters( 'woocommerce_gzd_product_attribute_checkout_visible_default_value', false ),
					),
					(array) $meta_attributes[ $meta_key ]
				);

				if ( ! is_null( $meta_value['checkout_visible'] ) ) {
					$new_attribute->set_checkout_visible( $meta_value['checkout_visible'] );
				}
			}
		}

		return $new_attribute;
	}
}

WC_GZD_Product_Attribute_Helper::instance();
