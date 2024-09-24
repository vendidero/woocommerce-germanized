<?php

namespace Vendidero\Germanized\Blocks\Integrations;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Vendidero\Germanized\Blocks\Assets;
use Vendidero\Germanized\Package;

defined( 'ABSPATH' ) || exit;

class Cart implements IntegrationInterface {

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
		return 'woocommerce-germanized-cart';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->assets = Package::container()->get( Assets::class );

		$this->assets->register_script( 'wc-gzd-blocks-cart', $this->assets->get_block_asset_build_path( 'cart' ), array( 'wc-gzd-blocks' ) );
		$this->assets->register_script( 'wc-gzd-blocks-cart-frontend', $this->assets->get_block_asset_build_path( 'cart-frontend' ) );
		$this->assets->register_style( 'wc-gzd-blocks-cart-frontend', $this->assets->get_block_asset_build_path( 'style-cart', 'css' ) );

		foreach ( $this->get_chunks() as $chunk ) {
			$handle = 'wc-gzd-blocks-' . $chunk . '-chunk';
			$this->assets->register_script( $handle, $this->assets->get_block_asset_build_path( $chunk ), array(), true );

			wp_add_inline_script(
				'wc-gzd-blocks-cart-frontend',
				wp_scripts()->print_translations( $handle, false ),
				'before'
			);

			wp_deregister_script( $handle );
		}

		add_action(
			'woocommerce_blocks_enqueue_cart_block_scripts_after',
			function () {
				wp_enqueue_style( 'wc-gzd-blocks-cart-frontend' );
			}
		);
	}

	protected function get_chunks() {
		$build_path = Package::get_path( 'build/cart-blocks' );
		$blocks     = array();

		if ( ! is_dir( $build_path ) ) {
			return array();
		}
		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $build_path ) ) as $block_name ) {
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
		return array( 'wc-gzd-blocks-cart', 'wc-gzd-blocks-cart-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'wc-gzd-blocks-cart' );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return array();
	}
}
