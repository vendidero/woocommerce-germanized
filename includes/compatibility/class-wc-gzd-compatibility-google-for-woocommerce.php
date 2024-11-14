<?php

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\MPN;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductUnitPricingBaseMeasure;
use Automattic\WooCommerce\GoogleListingsAndAds\Vendor\Google\Service\ShoppingContent\ProductUnitPricingMeasure;

/**
 * Compatibility script for https://wordpress.org/plugins/google-listings-and-ads/
 *
 * @class        WC_GZD_Compatibility_Google_For_WooCommerce
 * @category     Class
 * @author       vendidero
 */
class WC_GZD_Compatibility_Google_For_WooCommerce extends WC_GZD_Compatibility {

	protected const VALUE_KEY = 'woocommerce_germanized';

	public static function get_name() {
		return 'Google for WooCommerce';
	}

	public static function get_path() {
		return 'google-listings-and-ads/google-listings-and-ads.php';
	}

	public static function is_applicable() {
		$is_applicable = parent::is_applicable();

		return $is_applicable && class_exists( 'Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\GTIN' );
	}

	public function load() {
		add_filter( 'woocommerce_gla_product_attribute_value_options_mpn', array( $this, 'register_option_group' ) );
		add_filter( 'woocommerce_gla_product_attribute_value_options_gtin', array( $this, 'register_option_group' ) );
		add_filter( 'woocommerce_gla_product_attribute_value_mpn', array( $this, 'get_mpn' ), 10, 2 );
		add_filter( 'woocommerce_gla_product_attribute_value_gtin', array( $this, 'get_gtin' ), 10, 2 );
		add_filter( 'woocommerce_gla_attribute_mapping_sources', array( $this, 'register_mapping_source' ), 10, 2 );

		add_filter( 'woocommerce_gla_product_attribute_values', array( $this, 'inject_unit_price' ), 10, 3 );
	}

	/**
	 * @param $attributes
	 * @param WC_Product $product
	 * @param $adapter
	 *
	 * @return void
	 */
	public function inject_unit_price( $attributes, $product, $adapter ) {
		if ( $gzd_product = wc_gzd_get_gzd_product( $product ) ) {
			if ( $gzd_product->has_unit() && apply_filters( 'woocommerce_gzd_google_for_woocommerce_inject_unit_price', true, $product ) ) {
				$base_measure = new ProductUnitPricingBaseMeasure();
				$base_measure->setUnit( $gzd_product->get_unit() );
				$base_measure->setValue( $gzd_product->get_unit_base() );

				$measure = new ProductUnitPricingMeasure();
				$measure->setUnit( $gzd_product->get_unit() );
				$measure->setValue( $gzd_product->get_unit_product() );

				$attributes['unitPricingMeasure']     = $measure;
				$attributes['unitPricingBaseMeasure'] = $base_measure;
			}
		}

		return $attributes;
	}

	public function register_mapping_source( $sources, $attribute_id ) {
		if ( GTIN::get_id() === $attribute_id ) {
			return array_merge( $this->get_mapping_gtin_sources(), $sources );
		}

		if ( MPN::get_id() === $attribute_id ) {
			return array_merge( $this->get_mapping_mpn_sources(), $sources );
		}

		return $sources;
	}

	/**
	 * @param $value
	 * @param WC_Product $product
	 *
	 * @return mixed|null
	 */
	public function get_mpn( $value, $product ) {
		if ( strpos( $value, self::VALUE_KEY ) === 0 ) {
			if ( $gzd_product = wc_gzd_get_gzd_product( $product ) ) {
				if ( $mpn = $gzd_product->get_mpn() ) {
					$value = $mpn;
				} else {
					$value = null;
				}
			}
		}

		return ! empty( $value ) ? $value : null;
	}

	/**
	 * @param $value
	 * @param WC_Product $product
	 *
	 * @return mixed|null
	 */
	public function get_gtin( $value, $product ) {
		if ( strpos( $value, self::VALUE_KEY ) === 0 ) {
			if ( $gzd_product = wc_gzd_get_gzd_product( $product ) ) {
				if ( $gtin = $gzd_product->get_gtin() ) {
					$value = $gtin;
				} else {
					$value = null;
				}
			}
		}

		return ! empty( $value ) ? $value : null;
	}

	public function register_option_group( $options ) {
		$options[ self::VALUE_KEY ] = __( 'Germanized for WooCommerce', 'woocommerce-germanized' );

		return $options;
	}

	/**
	 * Load the GTIN Fields for Attribute mapping YOAST SEO
	 *
	 * @return array The GTIN sources
	 */
	protected function get_mapping_gtin_sources() {
		return array_merge( self::get_mapping_group_source(), array( self::VALUE_KEY . ':gtin' => __( 'GTIN', 'woocommerce-germanized' ) ) );
	}

	/**
	 * Load the MPN Fields for Attribute mapping YOAST SEO
	 *
	 * @return array The MPN sources
	 */
	protected function get_mapping_mpn_sources() {
		return array_merge( self::get_mapping_group_source(), array( self::VALUE_KEY . ':mpn' => __( 'MPN', 'woocommerce-germanized' ) ) );
	}

	/**
	 * Load the group disabled option for Attribute mapping YOAST SEO
	 *
	 * @return array The disabled group option
	 */
	protected function get_mapping_group_source() {
		return array( 'disabled:' . self::VALUE_KEY => __( 'Germanized for WooCommerce', 'woocommerce-germanized' ) );
	}
}
