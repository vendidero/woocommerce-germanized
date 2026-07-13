<?php
namespace Vendidero\Germanized\Blocks;

use Vendidero\Germanized\Package;

final class MiniCart {

	public function __construct() {
		$this->register_style();
		$this->register_integrations();
		$this->adjust_markup();
	}

	private function register_style() {
		add_action(
			'wp_enqueue_scripts',
			function () {
				$assets = Package::container()->get( Assets::class );
				$assets->register_style( 'wc-gzd-blocks-mini-cart-frontend', $assets->get_block_asset_build_path( 'style-mini-cart', 'css' ) );
			}
		);

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

	private function adjust_markup() {
		add_filter(
			'render_block',
			function ( $content, $block ) {
				/**
				 * Whether to disable the (structural) adjustments applied to the WooCommerce (react) mini cart block.
				 *
				 * @param boolean Whether to disable the mini cart adjustments or not.
				 *
				 * @since 3.15.4
				 */
				if ( 'woocommerce/mini-cart' === $block['blockName'] && ! apply_filters( 'woocommerce_gzd_disable_mini_cart_block_adjustments', false ) ) {
					$blocks = '<div data-block-name="woocommerce-germanized/mini-cart-notices" class="wp-block-woocommerce-gzd-mini-cart-notices-block"></div>';

					$content = preg_replace( '/(<div data-block-name="woocommerce\/mini-cart-footer-block" class="wp-block-woocommerce-mini-cart-footer-block[\w\s-]*">)(.*)/', '$1' . $blocks . '$2', $content );
				}

				/**
				 * Support new iapi mini cart block
				 */
				if ( 'woocommerce/mini-cart-footer-block' === $block['blockName'] ) {
					$is_small_business          = wc_gzd_is_small_business();
					$show_mini_cart_tax_notice  = apply_filters( 'woocommerce_gzd_show_mini_cart_totals_tax_notice', ! $is_small_business );
					$show_shipping_costs_notice = woocommerce_gzd_mini_cart_show_shipping_costs_notice();
					$notice_html                = '';
					$shipping_cost_notice       = wc_gzd_get_shipping_costs_text();
					$tax_notice                 = wc()->cart ? ( wc()->cart->display_prices_including_tax() ? __( 'incl. VAT', 'woocommerce-germanized' ) : __( 'excl. VAT', 'woocommerce-germanized' ) ) : __( 'incl. VAT', 'woocommerce-germanized' );

					if ( wc_gzd_is_small_business() ) {
						$small_business_info = wc_get_template_html( 'global/small-business-info.php' );

						if ( ! empty( $small_business_info ) ) {
							$notice_html .= '<div class="wc-gzd-block-mini-cart-notices__notice wc-gzd-block-mini-cart-notices__small-business-notice">' . wp_kses_post( wp_strip_all_tags( $small_business_info ) ) . '</div>';
						}

						if ( apply_filters( 'woocommerce_gzd_small_business_show_total_vat_notice', false ) ) {
							$show_mini_cart_tax_notice = true;
							$tax_notice                = __( 'incl. VAT', 'woocommerce-germanized' );
						}
					}

					$notice_html .= '<div class="wc-gzd-block-mini-cart-notices__notice-wrap">';

					if ( $show_mini_cart_tax_notice ) {
						$notice_html .= '<div class="wc-gzd-block-mini-cart-notices__notice wc-gzd-block-mini-cart-notices__tax-notice">' . wp_kses_post( $tax_notice ) . '</div>';
					}

					if ( $show_shipping_costs_notice && ! empty( $shipping_cost_notice ) ) {
						$notice_html .= '<div class="wc-gzd-block-mini-cart-notices__notice wc-gzd-block-mini-cart-notices__shipping-notice">' . wp_kses_post( $shipping_cost_notice ) . '</div>';
					}

					$notice_html .= '</div>';

					$content = preg_replace( '/<div class="wc-block-mini-cart__footer-actions">/', '<div class="wc-block-mini-cart__footer-actions"><div class="wc-gzd-block-mini-cart-notices">' . $notice_html . '</div>', $content );
				}

				return $content;
			},
			1000,
			2
		);
	}

	private function register_integrations() {
		add_action(
			'woocommerce_blocks_mini-cart_block_registration',
			function ( $integration_registry ) {
				$has_iapi_mini_cart = false;

				if ( class_exists( 'Automattic\WooCommerce\Admin\Features\Features' ) ) {
					$has_iapi_mini_cart = \Automattic\WooCommerce\Admin\Features\Features::is_enabled( 'experimental-iapi-mini-cart' );
				}

				if ( ! $has_iapi_mini_cart ) {
					$integration_registry->register( new \Vendidero\Germanized\Blocks\Integrations\MiniCart() );
				}
			}
		);
	}
}
