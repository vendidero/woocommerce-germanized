<?php

namespace Vendidero\Germanized\Blocks\Integrations;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Vendidero\Germanized\Blocks\Assets;
use Vendidero\Germanized\Package;

defined( 'ABSPATH' ) || exit;

class ProductElements implements IntegrationInterface {

	/**
	 * @var Assets
	 */
	private $assets = null;

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wc-gzd-product-elements';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->assets = Package::container()->get( Assets::class );
		$this->assets->register_script( 'woocommerce-gzd-blocks-product-elements', $this->assets->get_block_asset_build_path( 'wc-gzd-blocks-product-elements' ) );
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'woocommerce-gzd-blocks-product-elements' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'woocommerce-gzd-blocks-product-elements' );
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
