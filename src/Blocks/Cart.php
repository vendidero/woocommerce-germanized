<?php
namespace Vendidero\Germanized\Blocks;

final class Cart {

	public function __construct() {
		$this->adjust_checkout_block();
		$this->register_integrations();
	}

	private function adjust_checkout_block() {
		add_filter(
			'render_block',
			function ( $content, $block ) {
				if ( 'woocommerce/cart' === $block['blockName'] ) {
					$content = str_replace( '<div data-block-name="woocommerce/cart-totals-block" class="wp-block-woocommerce-cart-totals-block">', '<div data-block-name="woocommerce/cart-totals-block" class="wp-block-woocommerce-cart-totals-block"><div data-block-name="woocommerce-germanized/cart-summary-item" class="wp-block-woocommerce-germanized-cart-summary-item"></div>', $content );
				}

				return $content;
			},
			1000,
			2
		);
	}

	private function register_integrations() {
		add_action(
			'woocommerce_blocks_cart_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( new \Vendidero\Germanized\Blocks\Integrations\Cart() );
			}
		);
	}
}
