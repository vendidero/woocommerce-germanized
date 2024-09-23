<?php

/**
 * Class WC_Helper_Product.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_GZD_Helper_Product {

	public static function create_deposit_type() {
		$term = get_term_by( 'slug', 'Can', 'product_deposit_type' );

		if ( is_wp_error( $term ) || ! $term ) {
			$term = wp_insert_term( 'Can', 'product_deposit_type' );

			if ( ! is_wp_error( $term ) ) {
				update_term_meta( $term['term_id'], 'deposit', wc_format_decimal( 0.25, '' ) );
				update_term_meta( $term['term_id'], 'deposit_packaging_type', 'disposable' );

				$term = get_term_by( 'id', $term['term_id'], 'product_deposit_type' );
			}
		}

		return $term;
	}

	public static function create_manufacturer() {
		$term = get_term_by( 'slug', 'vendidero', 'product_manufacturer' );

		if ( is_wp_error( $term ) || ! $term ) {
			$term = wp_insert_term( 'vendidero', 'product_manufacturer' );

			if ( ! is_wp_error( $term ) ) {
				update_term_meta( $term['term_id'], 'formatted_address', "vendidero Gmbh\nMusterstr. 36\n12207 Berlin" );
				update_term_meta( $term['term_id'], 'formatted_eu_address', "vendidero Gmbh\nMusterstr. 36\n12207 Berlin" );

				$term = get_term_by( 'id', $term['term_id'], 'product_manufacturer' );
			}
		}

		return $term;
	}

	public static function create_nutrient() {
		$term = get_term_by( 'slug', 'energy', 'product_nutrient' );

		if ( is_wp_error( $term ) || ! $term ) {
			$term = wp_insert_term( 'Energy', 'product_nutrient' );

			if ( ! is_wp_error( $term ) ) {
				$term = get_term_by( 'id', $term['term_id'], 'product_nutrient' );
			}
		}

		return $term;
	}

	public static function create_allergen() {
		$term = get_term_by( 'slug', 'hazelnut', 'product_allergen' );

		if ( is_wp_error( $term ) || ! $term ) {
			$term = wp_insert_term( 'Hazelnut', 'product_allergen' );

			if ( ! is_wp_error( $term ) ) {
				$term = get_term_by( 'id', $term['term_id'], 'product_allergen' );
			}
		}

		return $term;
	}

	public static function create_attachment() {
		$attachment = get_page_by_path( 'woocommerce-placeholder', 'OBJECT', 'attachment' );

		if ( ! is_a( $attachment, 'WP_Post' ) ) {
			$upload_dir = wp_upload_dir();
			$source     = WC()->plugin_path() . '/assets/images/placeholder-attachment.png';
			$filename   = $upload_dir['basedir'] . '/woocommerce-placeholder.png';

			if ( ! file_exists( $filename ) ) {
				copy( $source, $filename ); // @codingStandardsIgnoreLine.
			}

			$filetype   = wp_check_filetype( basename( $filename ), null );
			$attachment = array(
				'guid'           => $upload_dir['url'] . '/' . basename( $filename ),
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$attachment_id = wp_insert_attachment( $attachment, $filename );

			if ( ! is_wp_error( $attachment_id ) ) {
				$attachment = get_post( $attachment_id );
			}
		}

		return is_a( $attachment, 'WP_Post' ) ? $attachment->ID : 0;
	}

	public static function create_simple_product() {
		$deposit_type = self::create_deposit_type();
		$allergen     = self::create_allergen();
		$nutrient     = self::create_nutrient();
		$manufacturer = self::create_manufacturer();
		$attachment   = self::create_attachment();

		$product = WC_Helper_Product::create_simple_product();

		$product->set_regular_price( 10 );
		$product->set_sale_price( 9 );

		$data = array(
			'_unit'                     => 'g',
			'_unit_base'                => '10',
			'_unit_product'             => '1',
			'_unit_price_regular'       => '100.0',
			'_unit_price_sale'          => '90.0',
			'_unit_price'               => '90.0',
			'_unit_price_auto'          => false,
			'_sale_price_label'         => 'new-price',
			'_sale_price_regular_label' => 'old-price',
			'_mini_desc'                => 'This is a test',
			'_defect_description'       => 'This is a defect desc',
			'_ts_gtin'                  => 'gtin',
			'_ts_mpn'                   => 'mpn',
			'_free_shipping'            => 'yes',
			'_service'                  => 'yes',
			'_used_good'                => 'yes',
			'_defective_copy'           => 'yes',
			'_differential_taxation'    => 'yes',
			'_default_delivery_time'    => '2-3-days',
			'_gzd_version'              => WC_GERMANIZED_VERSION,
			'_delivery_time_countries'  => array(
				'BG' => '3-4-days',
				'AT' => '4-5-days'
			),
			'_is_food'      => 'yes',
			'_manufacturer_slug' => $manufacturer->slug,
			'_deposit_type' => $deposit_type->slug,
			'_deposit_quantity' => 5,
			'_ingredients' => '<strong>Hazelnut</strong>, Fish',
			'_food_description' => 'A sample food description',
			'_food_place_of_origin' => 'Germany, Berlin',
			'_food_distributor' => 'John Doe Ltd.',
			'_nutrient_reference_value' => '100ml',
			'_drained_weight' => 25.31,
			'_net_filling_quantity' => 30.22,
			'_alcohol_content' => 15.1,
			'_nutri_score' => 'b',
			'_nutrient_ids' => array(
				$nutrient->term_id => array(
					'value'     => 20.31,
					'ref_value' => 22.1,
				),
			),
			'_safety_attachment_ids' => array(
				$attachment
			),
			'_warranty_attachment_id' => $attachment,
			'_allergen_ids' => array(
				$allergen->term_id,
			),
		);

		foreach ( $data as $key => $value ) {
			$product->update_meta_data( $key, $value );
		}

		$product->save();

		wp_set_object_terms( $product->get_id(), array( '2-3 Days', '3-4 Days', '4-5 Days' ), 'product_delivery_time' );
		wp_set_object_terms( $product->get_id(), array( $deposit_type->slug ), 'product_deposit_type' );
		wp_set_object_terms( $product->get_id(), array( $manufacturer->slug ), 'product_manufacturer' );

		return wc_gzd_get_gzd_product( $product );
	}

	public static function create_variation_product() {
		$product      = WC_Helper_Product::create_variation_product();
		$children     = $product->get_children();
		$variation_id = $children[0];

		$deposit_type = self::create_deposit_type();
		$allergen     = self::create_allergen();
		$nutrient     = self::create_nutrient();
		$manufacturer = self::create_manufacturer();
		$attachment   = self::create_attachment();

		$data = array(
			'_unit'                     => 'g',
			'_unit_base'                => '10',
			'_unit_product'             => '1',
			'_sale_price_label'         => 'new-price',
			'_sale_price_regular_label' => 'old-price',
			'_free_shipping'            => 'yes',
			'_service'                  => 'yes',
			'_used_good'                => 'yes',
			'_defective_copy'           => 'yes',
			'_differential_taxation'    => 'yes',
			'_default_delivery_time'    => '2-3-days',
			'_gzd_version'              => WC_GERMANIZED_VERSION,
			'_delivery_time_countries'  => array(
				'BG' => '3-4-days',
				'AT' => '4-5-days'
			),
			'_ts_gtin'                  => 'child_gtin',
			'_ts_mpn'                   => 'child_mpn',
			'_is_food'      => 'yes',
			'_deposit_type' => $deposit_type->slug,
			'_manufacturer_slug' => $manufacturer->slug,
			'_deposit_quantity' => 5,
			'_ingredients' => '<strong>Hazelnut</strong>, Fish',
			'_food_description' => 'A sample food description',
			'_food_place_of_origin' => 'Germany, Berlin',
			'_food_distributor' => 'John Doe Ltd.',
			'_nutrient_reference_value' => '100ml',
			'_drained_weight' => 25.31,
			'_net_filling_quantity' => 30.22,
			'_alcohol_content' => 15.1,
			'_nutri_score' => 'b',
			'_nutrient_ids' => array(
				$nutrient->term_id => array(
					'value'     => 20.31,
					'ref_value' => 22.1,
				),
			),
			'_allergen_ids' => array(
				$allergen->term_id,
			),
		);

		foreach ( $data as $key => $value ) {
			$product->update_meta_data( $key, $value );
		}

		$product->save();

		wp_set_object_terms( $product->get_id(), array( '2-3 Days', '3-4 Days', '4-5 Days' ), 'product_delivery_time' );

		return wc_gzd_get_gzd_product( $product );
	}
}