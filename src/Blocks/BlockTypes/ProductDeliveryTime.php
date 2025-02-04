<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductPrice class.
 */
class ProductDeliveryTime extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-delivery-time';

	protected function get_label_type() {
		return 'delivery_time';
	}

	/**
	 * @param array $attributes
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_additional_classes( $attributes, $product ) {
		return 'delivery-time-info';
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product ) {
		return $product->get_delivery_time_html();
	}
}
