<?php

namespace Vendidero\Shiptastic\DHL\Blocks\Integrations;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Vendidero\Shiptastic\DHL\Blocks\Assets;
use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\DHL\ParcelServices;

defined( 'ABSPATH' ) || exit;

class PreferredServices implements IntegrationInterface {

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
		return 'dhl-for-shiptastic-preferred-services';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->assets = Package::container()->get( Assets::class );

		$this->assets->register_script( 'wc-stc-shipments-blocks-dhl-checkout', $this->assets->get_block_asset_build_path( 'checkout' ), array( 'wc-stc-shipments-blocks-dhl' ) );
		$this->assets->register_style( 'wc-stc-shipments-blocks-dhl-checkout', $this->assets->get_block_asset_build_path( 'style-checkout', 'css' ) );

		$asset_registry = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class );
		$asset_registry->add( 'dhlCdpCountries', ParcelServices::get_cdp_countries() );
		$asset_registry->add( 'dhlExcludedPaymentGateways', ParcelServices::get_excluded_payment_gateways() );

		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_after',
			function () {
				wp_enqueue_style( 'wc-stc-shipments-blocks-dhl-checkout' );
			}
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'wc-stc-shipments-blocks-dhl-checkout' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'wc-stc-shipments-blocks-dhl-checkout' );
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
