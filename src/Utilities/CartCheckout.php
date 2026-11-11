<?php
namespace Vendidero\Germanized\Utilities;

class CartCheckout {

	/**
	 * Per-request cache of block detection results.
	 *
	 * @var bool[]
	 */
	protected static $uses_block = array();

	/**
	 * Checks if the default cart page is using the Cart block.
	 *
	 * @return bool true if the WC cart page is using the Cart block.
	 */
	public static function uses_cart_block() {
		return self::page_uses_block( 'cart' );
	}

	/**
	 * Checks if the default checkout page is using the Checkout block.
	 *
	 * @return bool true if the WC checkout page is using the Checkout block.
	 */
	public static function uses_checkout_block() {
		return self::page_uses_block( 'checkout' );
	}

	/**
	 * Resets the per-request block detection cache.
	 *
	 * @return void
	 */
	public static function flush_cache() {
		self::$uses_block = array();
	}

	/**
	 * @param string $page Either cart or checkout.
	 *
	 * @return bool
	 */
	protected static function page_uses_block( $page ) {
		if ( array_key_exists( $page, self::$uses_block ) ) {
			return self::$uses_block[ $page ];
		}

		$block_name = "woocommerce/{$page}";
		$uses_block = false;

		if ( wc_gzd_current_theme_is_fse_theme() && is_callable( array( '\Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils', 'get_block_templates_from_db' ) ) ) {
			$templates_from_db = \Automattic\WooCommerce\Blocks\Utils\BlockTemplateUtils::get_block_templates_from_db( array( $page, "page-{$page}" ), 'wp_template' );

			foreach ( $templates_from_db as $template ) {
				if ( has_block( $block_name, $template->content ) ) {
					$uses_block = true;
					break;
				}
			}
		}

		if ( ! $uses_block ) {
			$page_id    = wc_get_page_id( $page );
			$uses_block = $page_id && has_block( $block_name, $page_id );
		}

		/**
		 * Only cache the result once the theme has been set up, as the FSE theme check depends on it.
		 */
		if ( did_action( 'after_setup_theme' ) ) {
			self::$uses_block[ $page ] = $uses_block;
		}

		return $uses_block;
	}
}
