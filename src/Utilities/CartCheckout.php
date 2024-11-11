<?php
namespace Vendidero\Germanized\Utilities;

class CartCheckout {

	/**
	 * Checks if the default cart page is using the Cart block.
	 *
	 * @return bool true if the WC cart page is using the Cart block.
	 */
	public static function uses_cart_block() {
		if ( function_exists( 'wc_current_theme_is_fse_theme' ) && wc_current_theme_is_fse_theme() && is_callable( array( '\Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils', 'get_block_templates_from_db' ) ) ) {
			$templates_from_db = \Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils::get_block_templates_from_db( array( 'cart', 'page-cart' ), 'wp_template' );
			foreach ( $templates_from_db as $template ) {
				if ( has_block( 'woocommerce/cart', $template->content ) ) {
					return true;
				}
			}
		}
		$cart_page_id = wc_get_page_id( 'cart' );

		return $cart_page_id && has_block( 'woocommerce/cart', $cart_page_id );
	}

	/**
	 * Checks if the default checkout page is using the Checkout block.
	 *
	 * @return bool true if the WC checkout page is using the Checkout block.
	 */
	public static function uses_checkout_block() {
		if ( function_exists( 'wc_current_theme_is_fse_theme' ) && wc_current_theme_is_fse_theme() && is_callable( array( '\Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils', 'get_block_templates_from_db' ) ) ) {
			$templates_from_db = \Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils::get_block_templates_from_db( array( 'checkout', 'page-checkout' ), 'wp_template' );
			foreach ( $templates_from_db as $template ) {
				if ( has_block( 'woocommerce/checkout', $template->content ) ) {
					return true;
				}
			}
		}
		$checkout_page_id = wc_get_page_id( 'checkout' );

		return $checkout_page_id && has_block( 'woocommerce/checkout', $checkout_page_id );
	}
}
