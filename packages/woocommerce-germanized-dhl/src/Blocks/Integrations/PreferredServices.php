<?php

namespace Vendidero\Germanized\DHL\Blocks\Integrations;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Vendidero\Germanized\DHL\Blocks\Assets;
use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\ParcelServices;

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
		return 'woocommerce-germanized-dhl-preferred-services';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->assets = Package::container()->get( Assets::class );

		$this->assets->register_script( 'wc-gzd-shipments-blocks-dhl-checkout', $this->assets->get_block_asset_build_path( 'checkout' ), array( 'wc-gzd-shipments-blocks-dhl' ) );
		$this->assets->register_style( 'wc-gzd-shipments-blocks-dhl-checkout', $this->assets->get_block_asset_build_path( 'style-checkout', 'css' ) );

		$asset_registry = \Automattic\WooCommerce\Blocks\Package::container()->get( \Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class );
		$asset_registry->add( 'dhlCdpCountries', ParcelServices::get_cdp_countries() );
		$asset_registry->add( 'dhlExcludedPaymentGateways', ParcelServices::get_excluded_payment_gateways() );

		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_after',
			function () {
				wp_enqueue_style( 'wc-gzd-shipments-blocks-dhl-checkout' );
			}
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'wc-gzd-shipments-blocks-dhl-checkout' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'wc-gzd-shipments-blocks-dhl-checkout' );
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
