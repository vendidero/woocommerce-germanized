<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductPrice class.
 */
class ProductDepositPackagingType extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-deposit-packaging-type';

	protected function get_label_type() {
		return 'deposit_packaging_type';
	}

	protected function get_additional_classes( $attributes, $product ) {
		return 'deposit-packaging-type';
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product ) {
		return $product->get_deposit_packaging_type_title();
	}
}
