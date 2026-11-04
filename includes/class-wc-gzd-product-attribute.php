<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Product_Attribute extends WC_Product_Attribute {

	/**
	 * The original product attribute.
	 *
	 * @var WC_Product_Attribute
	 */
	private $attribute = null;

	/**
	 * Data array.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * WC_GZD_Product_Attribute constructor.
	 *
	 * @param null WC_Product_Attribute $attribute
	 */
	public function __construct( $attribute = null ) {
		if ( ! is_a( $attribute, 'WC_Product_Attribute' ) ) {
			$attribute = new WC_Product_Attribute();
		}

		$this->attribute = $attribute;

		/**
		 * Filter whether a product attribute should be visible within checkout by default.
		 *
		 * @param bool                     $default_visible Set to `true` to enable default checkout visibility.
		 * @param WC_GZD_Product_Attribute $attribute The product attribute
		 *
		 * @since 2.0.0
		 */
		$default_visible = apply_filters( 'woocommerce_gzd_product_attribute_checkout_visible_default_value', false, $this );
		$this->data      = array_merge(
			$attribute->get_data(),
			array(
				'checkout_visible' => $default_visible,
			)
		);

		/**
		 * As of Woo 10.6 additional attribute data is stored as extra data. Make sure to keep
		 * the original extra data and to sync checkout visibility so that the data store
		 * (which persists get_all_extra_data()) saves it.
		 */
		if ( $this->supports_extra_data() ) {
			$this->extra_data = $attribute->get_all_extra_data();
			$checkout_visible = $attribute->get_extra_data( 'checkout_visible' );

			if ( ! is_null( $checkout_visible ) ) {
				$this->data['checkout_visible'] = wc_string_to_bool( $checkout_visible );
			}

			$this->extra_data['checkout_visible'] = $this->data['checkout_visible'];
		}
	}

	/**
	 * Whether the Woo core attribute supports extra data (Woo >= 10.6).
	 *
	 * @return bool
	 */
	protected function supports_extra_data() {
		return method_exists( 'WC_Product_Attribute', 'get_all_extra_data' );
	}

	/**
	 * Returns the original attribute.
	 *
	 * @return WC_Product_Attribute
	 */
	public function get_attribute() {
		return $this->attribute;
	}

	/**
	 * Returns current product attribute data.
	 *
	 * @return array
	 */
	public function get_data() {
		return $this->data;
	}

	/**
	 * Set if visible during cart/checkout.
	 *
	 * @param bool $value If is visible on cart/checkout.
	 */
	public function set_checkout_visible( $value ) {
		$this->data['checkout_visible'] = wc_string_to_bool( $value );

		if ( $this->supports_extra_data() ) {
			$this->extra_data['checkout_visible'] = $this->data['checkout_visible'];
		}
	}

	/**
	 * Get if visible during cart/checkout.
	 *
	 * @return bool
	 */
	public function get_checkout_visible() {
		return $this->data['checkout_visible'];
	}

	/**
	 * Get if visible during cart/checkout.
	 *
	 * @return bool
	 */
	public function is_checkout_visible() {
		return $this->get_checkout_visible();
	}

	/**
	 * OffsetGet.
	 *
	 * @param string $offset Offset.
	 *
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		if ( 'checkout_visible' === $offset ) {
			return $this->get_checkout_visible() ? 1 : 0;
		}

		return $this->attribute[ $offset ];
	}

	/**
	 * OffsetSet.
	 *
	 * @param string $offset Offset.
	 * @param mixed $value Value.
	 */
	public function offsetSet( $offset, $value ) {
		if ( 'is_checkout_visible' === $offset ) {
			$this->set_checkout_visible( $value );
		} else {
			$this->attribute[ $offset ] = $value;
		}
	}

	/**
	 * OffsetUnset.
	 *
	 * @param string $offset Offset.
	 */
	public function offsetUnset( $offset ) {
	}

	/**
	 * OffsetExists.
	 *
	 * @param string $offset Offset.
	 *
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		if ( 'is_checkout_visible' === $offset ) {
			return true;
		}

		return isset( $this->attribute[ $offset ] );
	}
}
