<?php

namespace Vendidero\Germanized\Shipments\Blocks\Integrations;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Vendidero\Germanized\Shipments\Blocks\Assets;
use Vendidero\Germanized\Shipments\Package;

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
		return 'woocommerce-germanized-shipments-pickup-location-select';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->assets = Package::container()->get( Assets::class );

		$this->assets->register_script( 'wc-gzd-shipments-checkout-pickup-location-select-block', $this->assets->get_block_asset_build_path( 'checkout-pickup-location-select' ), array( 'wc-gzd-shipments-blocks' ) );
		$this->assets->register_style( 'wc-gzd-shipments-checkout-pickup-location-select-block', $this->assets->get_block_asset_build_path( 'style-checkout-pickup-location-select', 'css' ) );
		$this->assets->register_style( 'wc-gzd-shipments-checkout', $this->assets->get_block_asset_build_path( 'style-blocksCheckout', 'css' ) );

		$asset_registry = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class );

		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_after',
			function () {
				wp_enqueue_style( 'wc-gzd-shipments-checkout-pickup-location-select-block' );
				wp_enqueue_style( 'wc-gzd-shipments-checkout' );
			}
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'wc-gzd-shipments-checkout-pickup-location-select-block' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'wc-gzd-shipments-checkout-pickup-location-select-block' );
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
