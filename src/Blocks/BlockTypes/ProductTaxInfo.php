<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductPrice class.
 */
class ProductTaxInfo extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-tax-info';

	protected function get_label_type() {
		return 'tax_info';
	}

	protected function get_additional_classes( $attributes, $product ) {
		return 'tax-info';
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product ) {
		$html = $product->get_tax_info();

		if ( ! $html && wc_gzd_is_small_business() ) {
			$html = wc_gzd_get_small_business_product_notice();
		}

		if ( false === $html ) {
			$html = '';
		}

		return $html;
	}
}
