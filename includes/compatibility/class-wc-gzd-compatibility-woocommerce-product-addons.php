<?php

defined( 'ABSPATH' ) || exit;

/**
 * Product Addons Helper
 *
 * @class    WC_GZD_Compatibility_WooCommerce_Product_Addons
 * @category Class
 * @author   vendidero
 */
class WC_GZD_Compatibility_WooCommerce_Product_Addons extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'WooCommerce Product Addons';
	}

	public static function get_path() {
		return 'woocommerce-product-addons/woocommerce-product-addons.php';
	}

	public function load() {
		add_action( 'woocommerce_product_addons_end', array( $this, 'shopmarks' ), 11, 1 );
	}

	public function shopmarks( $post_id ) {
		if ( ! $product = wc_get_product( $post_id ) ) {
			return;
		}

		ob_start();
		foreach ( wc_gzd_get_single_product_shopmarks() as $shopmark ) {
			$callback = $shopmark->get_callback();

			if ( function_exists( $callback ) && $shopmark->is_enabled() ) {
				call_user_func( $callback );
			}
		}
		$html = trim( ob_get_clean() );

		if ( ! empty( $html ) ) {
			?>
			<script type="text/javascript">
				jQuery( function( $ ) {
					$( 'form.variations_form' ).on( 'updated_addons', function() {
						if ( $( this ).find( '.product-addon-totals:visible' ).length > 0 ) {
							$( this ).find( '.wc-gzd-product-addons-shopmarks' ).show();
						} else {
							$( this ).find( '.wc-gzd-product-addons-shopmarks' ).hide();
						}
					} );
				});
			</script>
			<style>div.product-addon-totals { border-bottom: none; padding-bottom: 0; } .wc-gzd-product-addons-shopmarks { margin-top: -40px; margin-bottom: 40px; border-bottom: 1px solid #eee; padding-bottom: 20px; font-size: .9em; text-align: right; }</style>
			<div class="wc-gzd-product-addons-shopmarks" style="<?php echo ( $product->is_type( 'variable' ) ? 'display: none' : '' ); ?>"><?php echo wp_kses_post( $html ); ?></div>
			<?php
		}
	}
}
