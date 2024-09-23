<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

class ProductManufacturer extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-manufacturer';

	protected function get_label_type() {
		return 'manufacturer';
	}

	protected function get_additional_classes( $attributes, $product ) {
		return 'manufacturer';
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product ) {
		return $product->get_manufacturer() ? $product->get_manufacturer()->get_html() : '';
	}
}
