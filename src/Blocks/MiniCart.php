<?php
namespace Vendidero\Germanized\Blocks;

final class MiniCart {

	public function __construct() {
		$this->register_integrations();
		$this->adjust_markup();
	}

	private function adjust_markup() {
		add_filter(
			'render_block',
			function ( $content, $block ) {
				/**
				 * Whether to disable the (structural) adjustments applied to the WooCommerce mini cart block.
				 *
				 * @param boolean Whether to disable the mini cart adjustments or not.
				 *
				 * @since 3.15.4
				 */
				if ( 'woocommerce/mini-cart' === $block['blockName'] && ! apply_filters( 'woocommerce_gzd_disable_mini_cart_block_adjustments', false ) ) {
					$blocks = '<div data-block-name="woocommerce-germanized/mini-cart-notices" class="wp-block-woocommerce-gzd-mini-cart-notices-block"></div>';

					$content = preg_replace( '/(<div data-block-name="woocommerce\/mini-cart-footer-block" class="wp-block-woocommerce-mini-cart-footer-block[\w\s-]*">)(.*)/', '$1' . $blocks . '$2', $content );
				}

				return $content;
			},
			1000,
			2
		);
	}

	private function register_integrations() {
		add_action(
			'woocommerce_blocks_mini-cart_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( new \Vendidero\Germanized\Blocks\Integrations\MiniCart() );
			}
		);
	}
}
