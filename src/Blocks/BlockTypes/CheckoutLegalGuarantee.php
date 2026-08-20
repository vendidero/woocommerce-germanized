<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

/**
 * CheckoutOrderSummaryCouponFormBlock class.
 */
class CheckoutLegalGuarantee extends AbstractInnerBlock {
	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'checkout-legal-guarantee';

	public function render( $attributes, $content, $block ) {
		$classes = array(
			$attributes['className'],
		);

		if ( ! empty( $attributes['align'] ) ) {
			$classes[] = 'has-text-align-' . $attributes['align'];
		}

		$classes = array_filter( $classes );
		$classes = implode( ' ', $classes );

		return '<div class="' . esc_attr( $classes ) . '">' . wc_gzd_kses_post_svg( wc_gzd_get_legal_guarantee_html( $attributes['variant'], '', 'checkout' ) ) . '</div>';
	}

	protected function get_block_type_attributes() {
		return array(
			'variant' => array(
				'type'    => 'string',
				'enum'    => array_keys( wc_gzd_get_legal_guarantee_variants() ),
				'default' => wc_gzd_get_legal_guarantee_variant( '', 'checkout' ),
			),
		);
	}
}
