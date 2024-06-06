<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductPrice class.
 */
class ProductDefectDescription extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-defect-description';

	protected function get_label_type() {
		return 'defect_description';
	}

	protected function get_additional_classes( $attributes, $product ) {
		return 'defect-description';
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product ) {
		return $product->get_formatted_defect_description();
	}
}
