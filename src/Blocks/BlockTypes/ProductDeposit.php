<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

/**
 * ProductPrice class.
 */
class ProductDeposit extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-deposit';

	protected function get_label_type() {
		return 'deposit';
	}

	protected function get_additional_classes( $attributes, $product ) {
		return 'deposit-amount';
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product ) {
		return $product->get_deposit_amount_html();
	}
}
