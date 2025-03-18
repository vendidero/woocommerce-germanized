<?php

namespace Vendidero\Shiptastic\Blocks\Integrations;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Vendidero\Shiptastic\Blocks\Assets;
use Vendidero\Shiptastic\Package;

defined( 'ABSPATH' ) || exit;

class CheckoutPickupLocationSelect implements IntegrationInterface {

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
		return 'wc-shiptastic-pickup-location-select';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->assets = Package::container()->get( Assets::class );

		$this->assets->register_script( 'wc-shiptastic-checkout-pickup-location-select-block', $this->assets->get_block_asset_build_path( 'checkout-pickup-location-select' ), array( 'wc-shiptastic-blocks' ) );
		$this->assets->register_style( 'wc-shiptastic-checkout-pickup-location-select-block', $this->assets->get_block_asset_build_path( 'style-checkout-pickup-location-select', 'css' ) );
		$this->assets->register_style( 'wc-shiptastic-checkout', $this->assets->get_block_asset_build_path( 'style-blocksCheckout', 'css' ) );

		$asset_registry = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class );

		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_after',
			function () {
				wp_enqueue_style( 'wc-shiptastic-checkout-pickup-location-select-block' );
				wp_enqueue_style( 'wc-shiptastic-checkout' );
			}
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'wc-shiptastic-checkout-pickup-location-select-block' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'wc-shiptastic-checkout-pickup-location-select-block' );
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
