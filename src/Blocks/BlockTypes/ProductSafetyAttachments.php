<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

class ProductSafetyAttachments extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-safety-attachments';

	protected function get_label_type() {
		return 'product_safety_attachments';
	}

	protected function get_additional_classes( $attributes, $product ) {
		return 'product-safety-attachments';
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product ) {
		return $product->get_product_safety_attachments_html();
	}
}
