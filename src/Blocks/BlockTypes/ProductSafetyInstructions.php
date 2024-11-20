<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

class ProductSafetyInstructions extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-safety-instructions';

	protected function get_label_type() {
		return 'safety_instructions';
	}

	protected function get_additional_classes( $attributes, $product ) {
		return 'safety-instructions';
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product ) {
		return $product->get_formatted_safety_instructions();
	}
}
