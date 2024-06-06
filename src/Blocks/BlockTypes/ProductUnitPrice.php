<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductPrice class.
 */
class ProductUnitPrice extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-unit-price';

	protected function get_label_type() {
		return 'unit_price';
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product ) {
		return $product->has_unit() ? $product->get_unit_price_html() : '';
	}

	protected function get_additional_classes( $attributes, $product ) {
		return 'price-unit';
	}
}
