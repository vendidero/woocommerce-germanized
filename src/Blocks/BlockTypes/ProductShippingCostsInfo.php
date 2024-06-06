<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductPrice class.
 */
class ProductShippingCostsInfo extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-shipping-costs-info';

	protected function get_label_type() {
		return 'shipping_costs_info';
	}

	protected function get_additional_classes( $attributes, $product ) {
		return 'shipping-costs-info';
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product ) {
		return $product->get_shipping_costs_html();
	}
}
