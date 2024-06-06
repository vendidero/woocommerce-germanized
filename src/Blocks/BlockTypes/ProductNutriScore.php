<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductPrice class.
 */
class ProductNutriScore extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-nutri-score';

	protected function get_label_type() {
		return 'nutri_score';
	}

	protected function get_additional_classes( $attributes, $product ) {
		return 'wc-gzd-nutri-score';
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product ) {
		return $product->get_formatted_nutri_score();
	}
}
