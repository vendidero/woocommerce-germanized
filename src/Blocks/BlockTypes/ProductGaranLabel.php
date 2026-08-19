<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

/**
 * ProductPrice class.
 */
class ProductGaranLabel extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-garan-label';

	protected function get_label_type() {
		return 'garan_label';
	}

	protected function get_additional_classes( $attributes, $product ) {
		return 'wc-gzd-garan-label';
	}

	protected function get_block_type_attributes() {
		return array(
			'variant' => array(
				'type'    => 'string',
				'enum'    => array_keys( wc_gzd_get_garan_label_variants() ),
				'default' => wc_gzd_get_garan_label_default_variant(),
			),
		);
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product, $attributes = array() ) {
		return $product->get_garan_label_html( $attributes['variant'] );
	}
}
