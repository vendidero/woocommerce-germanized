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

					$legal_guarantee_locations = wc_gzd_get_legal_guarantee_global_locations();

					if ( in_array( 'cart', $legal_guarantee_locations, true ) && ! strstr( $content, 'woocommerce-germanized/checkout-legal-guarantee' ) && class_exists( 'WP_Block_Type_Registry' ) ) {
						$legal_guarantee_block = \WP_Block_Type_Registry::get_instance()->get_registered( 'woocommerce-germanized/checkout-legal-guarantee' );

						if ( $legal_guarantee_block ) {
							$legal_guarantee_block_html = '<div data-block-name="woocommerce-germanized/checkout-legal-guarantee">' . $legal_guarantee_block->render(
								array(
									'variant'   => wc_gzd_get_legal_guarantee_variant( '', 'cart' ),
									'className' => '',
								)
							) . '</div>';

							$content = str_replace( 'class="wp-block-woocommerce-cart-line-items-block"></div>', 'class="wp-block-woocommerce-cart-line-items-block"></div>' . $legal_guarantee_block_html, $content );
						}
					}
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
