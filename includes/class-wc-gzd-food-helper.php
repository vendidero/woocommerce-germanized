<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Food_Helper {

	public static function init() {

	}

	public static function get_nutrient_reference_values() {
		return apply_filters(
			'woocommerce_gzd_nutrient_reference_values',
			array(
				'100g'  => _x( 'per 100 g', 'nutrient-reference-value', 'woocommerce-germanized' ),
				'100ml' => _x( 'per 100 ml', 'nutrient-reference-value', 'woocommerce-germanized' ),
			)
		);
	}

	public static function get_nutri_score_values() {
		return apply_filters(
			'woocommerce_gzd_nutri_score_values',
			array(
				'a' => _x( 'A', 'nutri-score', 'woocommerce-germanized' ),
				'b' => _x( 'B', 'nutri-score', 'woocommerce-germanized' ),
				'c' => _x( 'C', 'nutri-score', 'woocommerce-germanized' ),
				'd' => _x( 'D', 'nutri-score', 'woocommerce-germanized' ),
				'e' => _x( 'E', 'nutri-score', 'woocommerce-germanized' ),
			)
		);
	}

	public static function get_food_attribute_types() {
		return array(
			'ingredients'          => __( 'Ingredients', 'woocommerce-germanized' ),
			'nutrients'            => __( 'Nutrients', 'woocommerce-germanized' ),
			'allergenic'           => __( 'Allergenic', 'woocommerce-germanized' ),
			'food_description'     => _x( 'Description', 'food', 'woocommerce-germanized' ),
			'food_distributor'     => _x( 'Distributor', 'food', 'woocommerce-germanized' ),
			'food_place_of_origin' => _x( 'Place of origin', 'food', 'woocommerce-germanized' ),
			'alcohol_content'      => __( 'Alcohol content', 'woocommerce-germanized' ),
			'drained_weight'       => __( 'Drained weight', 'woocommerce-germanized' ),
			'net_filling_quantity' => __( 'Net filling quantity', 'woocommerce-germanized' ),
			'nutri_score'          => __( 'Nutri-Score', 'woocommerce-germanized' ),
		);
	}

	public static function get_nutrient_types() {
		return apply_filters(
			'woocommerce_gzd_nutrient_types',
			array(
				'numeric'  => _x( 'Numeric', 'food-type', 'woocommerce-germanized' ),
				'vitamins' => _x( 'Vitamins & Minerals', 'food-type', 'woocommerce-germanized' ),
				'title'    => _x( 'Title', 'food-type', 'woocommerce-germanized' ),
			)
		);
	}

	public static function get_nutrient_rounding_rules() {
		return apply_filters(
			'woocommerce_gzd_nutrient_rounding_rules',
			array(
				'energy'             => array(
					'title' => __( 'Energy', 'woocommerce-germanized' ),
					'rules' => array(
						array(
							'min'      => 0,
							'max'      => -1,
							'decimals' => 0,
						),
					),
				),
				'proteins_sugar_fat' => array(
					'title'       => __( 'Proteins, Sugar & Fat', 'woocommerce-germanized' ),
					'description' => __( 'Fat, Carbohydrates, Sugar, Protein, Dietary fiber, Polyols, Starch', 'woocommerce-germanized' ),
					'rules'       => array(
						array(
							'min'      => 10,
							'max'      => -1,
							'decimals' => 0,
						),
						array(
							'min'      => 0.51,
							'max'      => 10,
							'decimals' => 1,
						),
						array(
							'min'      => 0,
							'max'      => 0.5,
							'decimals' => 1,
							'prefix'   => '<',
						),
					),
				),
				'fatty_acids'        => array(
					'title' => __( 'Fatty Acids', 'woocommerce-germanized' ),
					'rules' => array(
						array(
							'min'      => 10,
							'max'      => -1,
							'decimals' => 0,
						),
						array(
							'min'      => 0.1001,
							'max'      => 10,
							'decimals' => 1,
						),
						array(
							'min'      => 0,
							'max'      => 0.1,
							'decimals' => 1,
							'prefix'   => '<',
						),
					),
				),
				'natrium'            => array(
					'title' => __( 'Natrium', 'woocommerce-germanized' ),
					'rules' => array(
						array(
							'min'      => 1,
							'max'      => -1,
							'decimals' => 1,
						),
						array(
							'min'      => 0.006,
							'max'      => 1,
							'decimals' => 2,
						),
						array(
							'min'      => 0,
							'max'      => 0.005,
							'decimals' => 2,
							'prefix'   => '<',
						),
					),
				),
				'salt'               => array(
					'title' => __( 'Salt', 'woocommerce-germanized' ),
					'rules' => array(
						array(
							'min'      => 1,
							'max'      => -1,
							'decimals' => 1,
						),
						array(
							'min'      => 0.0125,
							'max'      => 1,
							'decimals' => 2,
						),
						array(
							'min'      => 0,
							'max'      => 0.0125,
							'decimals' => 2,
							'prefix'   => '<',
						),
					),
				),
				'vitamin_3'          => array(
					'title'       => __( 'Vitamin A, Chlorid etc', 'woocommerce-germanized' ),
					'description' => __( 'Vitamin A, folic acid, chloride, calcium, phosphorus, magnesium, iodine, potassium', 'woocommerce-germanized' ),
					'rules'       => array(
						array(
							'min'      => 0,
							'max'      => -1,
							'decimals' => 3,
						),
					),
				),
				'vitamin_2'          => array(
					'title' => __( 'Other Vitamins & Minerals', 'woocommerce-germanized' ),
					'rules' => array(
						array(
							'min'      => 0,
							'max'      => -1,
							'decimals' => 2,
						),
					),
				),
			)
		);
	}
}

WC_GZD_Food_Helper::init();
