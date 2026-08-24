<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

/**
 * ProductPrice class.
 */
class ProductLegalGuarantee extends AbstractProductElementBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'product-legal-guarantee';

	protected function get_label_type() {
		return 'legal_guarantee';
	}

	protected function get_additional_classes( $attributes, $product ) {
		return 'wc-gzd-legal-guarantee';
	}

	protected function get_block_type_attributes() {
		return array(
			'variant' => array(
				'type'    => 'string',
				'enum'    => array_keys( wc_gzd_get_legal_guarantee_variants() ),
				'default' => wc_gzd_get_legal_guarantee_variant( '', 'product' ),
			),
		);
	}

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_label_content( $product, $attributes = array() ) {
		return $product->get_legal_guarantee_html( $attributes['variant'] );
	}
}
