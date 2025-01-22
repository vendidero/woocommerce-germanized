<?php
namespace Vendidero\Germanized\Blocks\BlockTypes;

use Automattic\WooCommerce\Blocks\Utils\StyleAttributesUtils;

/**
 * ProductPrice class.
 */
abstract class AbstractProductElementBlock extends AbstractBlock {
	/**
	 * Block namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'woocommerce-germanized';

	/**
	 * API version name.
	 *
	 * @var string
	 */
	protected $api_version = '2';

	/**
	 * @return string
	 */
	abstract protected function get_label_type();

	/**
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	abstract protected function get_label_content( $product );

	protected function get_label_type_class() {
		$label_type = $this->get_label_type();

		return str_replace( '_', '-', $label_type );
	}

	/**
	 * Get block supports. Shared with the frontend.
	 * IMPORTANT: If you change anything here, make sure to update the JS file too.
	 *
	 * @return array
	 */
	protected function get_block_type_supports() {
		return array(
			'color'                  =>
				array(
					'text'       => true,
					'background' => true,
					'link'       => false,
				),
			'typography'             =>
				array(
					'fontSize'                 => true,
					'__experimentalFontWeight' => true,
					'__experimentalFontStyle'  => true,
				),
			'__experimentalSelector' => '.wp-block-woocommerce-gzd-product-' . $this->get_label_type_class() . ' .wc-gzd-block-components-product-' . $this->get_label_type_class(),
		);
	}

	/**
	 * Get the frontend style handle for this block type.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Overwrite parent method to prevent script registration.
	 *
	 * It is necessary to register and enqueues assets during the render
	 * phase because we want to load assets only if the block has the content.
	 */
	protected function register_block_type_assets() {
		return null;
	}

	/**
	 * Register the context.
	 */
	protected function get_block_type_uses_context() {
		return array( 'query', 'queryId', 'postId' );
	}

	/**
	 * @param array $attributes
	 * @param \WC_GZD_Product $product
	 *
	 * @return string
	 */
	protected function get_additional_classes( $attributes, $product ) {
		return '';
	}

	/**
	 * Include and render the block.
	 *
	 * @param array     $attributes Block attributes. Default empty array.
	 * @param string    $content    Block content. Default empty string.
	 * @param \WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		if ( ! empty( $content ) ) {
			parent::register_block_type_assets();
			$this->register_chunk_translations( array( $this->block_name ) );
			return $content;
		}

		$post_id = isset( $block->context['postId'] ) ? $block->context['postId'] : '';
		$product = wc_gzd_get_product( $post_id );

		if ( $product ) {
			$styles_and_classes            = StyleAttributesUtils::get_classes_and_styles_by_attributes( $attributes );
			$text_align_styles_and_classes = StyleAttributesUtils::get_text_align_class_and_style( $attributes );
			$margin_styles_and_classes     = StyleAttributesUtils::get_margin_class_and_style( $attributes );
			$inner_classes                 = sprintf( 'wc-gzd-block-components-product-%1$s wc-gzd-block-grid__product-%1$s', $this->get_label_type_class() ) . ' ' . ( isset( $text_align_styles_and_classes['class'] ) ? $text_align_styles_and_classes['class'] : '' ) . ' ' . $styles_and_classes['classes'];
			$inner_classes                .= ' ' . $this->get_additional_classes( $attributes, $product );
			$html                          = $this->get_label_content( $product );
			$block_classes                 = 'wp-block-woocommerce-gzd-product-price-label wp-block-woocommerce-gzd-product-' . $this->get_label_type_class() . ' ' . ( isset( $margin_styles_and_classes['class'] ) ? $margin_styles_and_classes['class'] : '' );

			if ( ! $html && $product->is_type( 'variable' ) ) {
				$inner_classes .= ' wc-gzd-additional-info-placeholder';
			}

			if ( ! $html ) {
				$block_classes .= ' wp-block-woocommerce-gzd-product-is-empty';
			}

			return sprintf(
				'<div class="%1$s" style="%2$s"><div class="%3$s" style="%4$s">%5$s</div></div>',
				esc_attr( trim( $block_classes ) ),
				esc_attr( isset( $margin_styles_and_classes['style'] ) ? $margin_styles_and_classes['style'] : '' ),
				esc_attr( trim( $inner_classes ) ),
				esc_attr( isset( $styles_and_classes['styles'] ) ? $styles_and_classes['styles'] : '' ),
				wc_gzd_kses_post_svg( $html )
			);
		}
	}
}
