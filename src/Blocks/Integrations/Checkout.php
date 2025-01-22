<?php

namespace Vendidero\Germanized\Blocks\Integrations;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Vendidero\Germanized\Blocks\Assets;
use Vendidero\Germanized\Package;

defined( 'ABSPATH' ) || exit;

class Checkout implements IntegrationInterface {

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
		return 'woocommerce-germanized-checkout';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->assets = Package::container()->get( Assets::class );

		$this->assets->register_script( 'wc-gzd-blocks-checkout', $this->assets->get_block_asset_build_path( 'checkout' ), array( 'wc-gzd-blocks' ) );
		$this->assets->register_script( 'wc-gzd-blocks-checkout-frontend', $this->assets->get_block_asset_build_path( 'checkout-frontend' ) );
		$this->assets->register_style( 'wc-gzd-blocks-checkout-frontend', $this->assets->get_block_asset_build_path( 'style-checkout', 'css' ) );

		foreach ( $this->get_chunks() as $chunk ) {
			$handle = 'wc-gzd-blocks-' . $chunk . '-chunk';
			$this->assets->register_script( $handle, $this->assets->get_block_asset_build_path( 'checkout-blocks' . $chunk ), array(), true );

			wp_add_inline_script(
				'wc-gzd-blocks-checkout-frontend',
				wp_scripts()->print_translations( $handle, false ),
				'before'
			);

			wp_deregister_script( $handle );
		}

		$bg_color = ( get_option( 'woocommerce_gzd_display_checkout_table_color' ) ? get_option( 'woocommerce_gzd_display_checkout_table_color' ) : '' );

		if ( ! empty( $bg_color ) ) {
			$custom_css = '.wc-gzd-checkout .wp-block-woocommerce-checkout-order-summary-cart-items-block {padding: 0 !important;} .wc-gzd-checkout .wc-block-components-order-summary, .wc-gzd-checkout .wc-block-components-order-summary.is-large { background-color: ' . esc_attr( $bg_color ) . '; padding: 16px; }';

			if ( wc_gzd_is_small_business() && apply_filters( 'woocommerce_gzd_small_business_show_total_vat_notice', false ) ) {
				$translated  = __( 'incl. VAT', 'woocommerce-germanized' );
				$custom_css .= '.wc-block-components-totals-footer-item .wc-block-components-totals-item__label::after { content: " (' . esc_html( $translated ) . ')"; font-size: .6em; font-weight: normal; }';
			}

			wp_add_inline_style( 'wc-gzd-blocks-checkout-frontend', $custom_css );
		}

		add_action(
			'woocommerce_blocks_enqueue_checkout_block_scripts_after',
			function () {
				wp_enqueue_style( 'wc-gzd-blocks-checkout-frontend' );
			}
		);
	}

	protected function get_chunks() {
		$build_path = Package::get_path( 'build/checkout-blocks' );
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
		return array( 'wc-gzd-blocks-checkout', 'wc-gzd-blocks-checkout-frontend' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'wc-gzd-blocks-checkout' );
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		$this->assets->register_data( 'buyNowButtonText', get_option( 'woocommerce_gzd_order_submit_btn_text', __( 'Buy Now', 'woocommerce-germanized' ) ) );
		$this->assets->register_data( 'isSmallBusiness', wc_gzd_is_small_business() );
		$this->assets->register_data( 'smallBusinessNotice', wc_get_template_html( 'global/small-business-info.php' ) );
		$this->assets->register_data( 'showSmallBusinessVatNotice', apply_filters( 'woocommerce_gzd_small_business_show_total_vat_notice', false ) );

		return array();
	}
}
