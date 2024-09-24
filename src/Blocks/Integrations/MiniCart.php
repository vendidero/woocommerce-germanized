<?php

namespace Vendidero\Germanized\Blocks\Integrations;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Vendidero\Germanized\Blocks\Assets;
use Vendidero\Germanized\Package;

defined( 'ABSPATH' ) || exit;

class MiniCart implements IntegrationInterface {

	/**
	 * @var Assets
	 */
	private $assets;

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'woocommerce-germanized-mini-cart';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->assets = Package::container()->get( Assets::class );

		$this->assets->register_script( 'wc-gzd-blocks-mini-cart-frontend', $this->assets->get_block_asset_build_path( 'mini-cart-frontend' ) );
		$this->assets->register_style( 'wc-gzd-blocks-mini-cart-frontend', $this->assets->get_block_asset_build_path( 'style-mini-cart', 'css' ) );

		foreach ( $this->get_chunks() as $chunk ) {
			$handle = 'wc-gzd-blocks-' . $chunk . '-chunk';
			$this->assets->register_script( $handle, $this->assets->get_block_asset_build_path( 'mini-cart-blocks' . $chunk ), array(), true );

			wp_add_inline_script(
				'wc-gzd-blocks-mini-cart-frontend',
				wp_scripts()->print_translations( $handle, false ),
				'before'
			);

			wp_deregister_script( $handle );
		}

		add_filter(
			'render_block',
			function ( $content, $block ) {
				if ( ! empty( $block['blockName'] ) && 'woocommerce/mini-cart' === $block['blockName'] ) {
					wp_enqueue_style( 'wc-gzd-blocks-mini-cart-frontend' );
				}

				return $content;
			},
			5,
			2
		);
	}

	protected function get_chunks() {
		$build_path = Package::get_path( 'build/mini-cart-blocks' );
		$blocks     = array();

		if ( ! is_dir( $build_path ) ) {
			return array();
		}
		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $build_path ) ) as $block_name ) {
			/**
			 * Skip additional auto-generated style js files.
			 */
			if ( '-style.js' === substr( $block_name, -9 ) ) {
				continue;
			}

			$blocks[] = str_replace( $build_path, '', $block_name );
		}

		$chunks = preg_filter( '/.js/', '', $blocks );
		return $chunks;
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'wc-gzd-blocks-mini-cart-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		$is_small_business = wc_gzd_is_small_business();

		$this->assets->register_data( 'isSmallBusiness', $is_small_business );
		$this->assets->register_data( 'smallBusinessNotice', wp_strip_all_tags( wc_get_template_html( 'global/small-business-info.php' ) ) );
		$this->assets->register_data( 'showMiniCartShippingCostsNotice', apply_filters( 'woocommerce_gzd_show_mini_cart_totals_shipping_costs_notice', true ) );
		$this->assets->register_data( 'showMiniCartTaxNotice', apply_filters( 'woocommerce_gzd_show_mini_cart_totals_tax_notice', ! $is_small_business ) );

		return array();
	}
}
