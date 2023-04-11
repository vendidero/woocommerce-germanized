<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Registers delivery time taxonomy
 *
 * @class        WC_GZD_Post_Types
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_Post_Types {

	/**
	 * Hook in methods
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 11 );
	}

	/**
	 * Register Delivery Time Taxonomy
	 */
	public static function register_taxonomies() {
		// Delivery time
		register_taxonomy(
			'product_delivery_time',
			/**
			 * Filter post types which are capable of storing delivery times.
			 *
			 * @param array $post_types The post types to support `delivery_time` taxonomy.
			 *
			 * @since 1.0.0
			 *
			 */
			apply_filters( 'woocommerce_germanized_taxonomy_objects_product_delivery_time', array( 'product', 'product_variation' ) ),
			/**
			 * Filter to adjust arguments passed to register the `delivery_time` taxonomy.
			 *
			 * @param array $args Arguments passed to `register_taxonomy`.
			 *
			 * @since 1.0.0
			 *
			 */
			apply_filters(
				'woocommerce_germanized_taxonomy_args_product_delivery_time',
				array(
					'hierarchical'          => false,
					'update_count_callback' => '_wc_term_recount',
					'label'                 => __( 'Product Delivery Times', 'woocommerce-germanized' ),
					'labels'                => array(
						'name'          => __( 'Product Delivery Times', 'woocommerce-germanized' ),
						'singular_name' => __( 'Product Delivery Time', 'woocommerce-germanized' ),
						'menu_name'     => _x( 'Delivery Time', 'Admin menu name', 'woocommerce-germanized' ),
						'search_items'  => __( 'Search Delivery Times', 'woocommerce-germanized' ),
						'all_items'     => __( 'All Product Delivery Times', 'woocommerce-germanized' ),
						'edit_item'     => __( 'Edit Product Delivery Time', 'woocommerce-germanized' ),
						'update_item'   => __( 'Update Product Delivery Time', 'woocommerce-germanized' ),
						'add_new_item'  => __( 'Add New Product Delivery Time', 'woocommerce-germanized' ),
						'new_item_name' => __( 'New Product Delivery Time Name', 'woocommerce-germanized' ),
					),
					'show_ui'               => true,
					'query_var'             => true,
					'public'                => false,
					'capabilities'          => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),
					'rewrite'               => false,
					'meta_box_cb'           => false,
					'show_in_quick_edit'    => false,
				)
			)
		);

		// Units
		register_taxonomy(
			'product_unit',
			/**
			 * Filter post types which are capable of storing units.
			 *
			 * @param array $post_types The post types to support `product_unit` taxonomy.
			 *
			 * @since 1.0.0
			 *
			 */
			apply_filters( 'woocommerce_germanized_taxonomy_objects_product_unit', array( 'product' ) ),
			/**
			 * Filter to adjust arguments passed to register the `product_unit` taxonomy.
			 *
			 * @param array $args Arguments passed to `register_taxonomy`.
			 *
			 * @since 1.0.0
			 *
			 */
			apply_filters(
				'woocommerce_germanized_taxonomy_args_product_unit',
				array(
					'hierarchical'          => false,
					'update_count_callback' => '_wc_term_recount',
					'label'                 => __( 'Units', 'woocommerce-germanized' ),
					'labels'                => array(
						'name'          => __( 'Units', 'woocommerce-germanized' ),
						'singular_name' => __( 'Unit', 'woocommerce-germanized' ),
						'menu_name'     => _x( 'Units', 'Admin menu name', 'woocommerce-germanized' ),
						'search_items'  => __( 'Search Units', 'woocommerce-germanized' ),
						'all_items'     => __( 'All Units', 'woocommerce-germanized' ),
						'edit_item'     => __( 'Edit Unit', 'woocommerce-germanized' ),
						'update_item'   => __( 'Update Unit', 'woocommerce-germanized' ),
						'add_new_item'  => __( 'Add New Unit', 'woocommerce-germanized' ),
						'new_item_name' => __( 'New Unit Name', 'woocommerce-germanized' ),
					),
					'show_ui'               => true,
					'query_var'             => true,
					'public'                => false,
					'capabilities'          => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),
					'rewrite'               => false,
					'meta_box_cb'           => false,
					'show_in_quick_edit'    => false,
				)
			)
		);

		// Price labels
		register_taxonomy(
			'product_price_label',
			/**
			 * Filter post types which are capable of storing price labels.
			 *
			 * @param array $post_types The post types to support `price_label` taxonomy.
			 *
			 * @since 1.0.0
			 *
			 */
			apply_filters( 'woocommerce_germanized_taxonomy_objects_product_price_label', array( 'product' ) ),
			/**
			 * Filter to adjust arguments passed to register the `price_label` taxonomy.
			 *
			 * @param array $args Arguments passed to `register_taxonomy`.
			 *
			 * @since 1.0.0
			 *
			 */
			apply_filters(
				'woocommerce_germanized_taxonomy_args_product_price_label',
				array(
					'hierarchical'          => false,
					'update_count_callback' => '_wc_term_recount',
					'label'                 => __( 'Price Labels', 'woocommerce-germanized' ),
					'labels'                => array(
						'name'          => __( 'Price Labels', 'woocommerce-germanized' ),
						'singular_name' => __( 'Price Label', 'woocommerce-germanized' ),
						'menu_name'     => _x( 'Price Labels', 'Admin menu name', 'woocommerce-germanized' ),
						'search_items'  => __( 'Search Price Labels', 'woocommerce-germanized' ),
						'all_items'     => __( 'All Price Labels', 'woocommerce-germanized' ),
						'edit_item'     => __( 'Edit Price Label', 'woocommerce-germanized' ),
						'update_item'   => __( 'Update Price Label', 'woocommerce-germanized' ),
						'add_new_item'  => __( 'Add New Price Label', 'woocommerce-germanized' ),
						'new_item_name' => __( 'New Price Label Name', 'woocommerce-germanized' ),
					),
					'show_ui'               => true,
					'query_var'             => true,
					'public'                => false,
					'capabilities'          => array(
						'manage_terms' => 'manage_product_terms',
						'edit_terms'   => 'edit_product_terms',
						'delete_terms' => 'delete_product_terms',
						'assign_terms' => 'assign_product_terms',
					),
					'rewrite'               => false,
					'meta_box_cb'           => false,
					'show_in_quick_edit'    => false,
				)
			)
		);

		if ( apply_filters( 'woocommerce_gzd_register_food_taxonomies', ( 'yes' !== get_option( 'woocommerce_gzd_disable_food_options' ) ) ) ) {
			// Deposit classes
			register_taxonomy(
				'product_deposit_type',
				/**
				 * Filter post types which are capable of storing deposit types.
				 *
				 * @param array $post_types The post types to support `product_deposit_type` taxonomy.
				 *
				 * @since 3.9.0
				 */
				apply_filters( 'woocommerce_germanized_taxonomy_objects_product_deposit_type', array( 'product', 'product_variation' ) ),
				/**
				 * Filter to adjust arguments passed to register the `product_deposit_type` taxonomy.
				 *
				 * @param array $args Arguments passed to `register_taxonomy`.
				 *
				 * @since 3.9.0
				 */
				apply_filters(
					'woocommerce_germanized_taxonomy_args_product_deposit_type',
					array(
						'hierarchical'          => false,
						'update_count_callback' => '_wc_term_recount',
						'label'                 => __( 'Product Deposit Types', 'woocommerce-germanized' ),
						'labels'                => array(
							'name'          => __( 'Product Deposit Types', 'woocommerce-germanized' ),
							'singular_name' => __( 'Product Deposit Types', 'woocommerce-germanized' ),
							'menu_name'     => _x( 'Deposit Types', 'Admin menu name', 'woocommerce-germanized' ),
							'search_items'  => __( 'Search Deposit Types', 'woocommerce-germanized' ),
							'all_items'     => __( 'All Deposit Types', 'woocommerce-germanized' ),
							'edit_item'     => __( 'Edit Deposit Types', 'woocommerce-germanized' ),
							'update_item'   => __( 'Update Deposit Type', 'woocommerce-germanized' ),
							'add_new_item'  => __( 'Add New Deposit Type', 'woocommerce-germanized' ),
							'new_item_name' => __( 'New Deposit Type Name', 'woocommerce-germanized' ),
						),
						'show_ui'               => ( WC_germanized()->is_pro() && apply_filters( 'woocommerce_gzd_show_food_ui', true ) ) ? true : false,
						'query_var'             => true,
						'public'                => false,
						'capabilities'          => array(
							'manage_terms' => 'manage_product_terms',
							'edit_terms'   => 'edit_product_terms',
							'delete_terms' => 'delete_product_terms',
							'assign_terms' => 'assign_product_terms',
						),
						'rewrite'               => false,
						'meta_box_cb'           => false,
					)
				)
			);

			// Nutrients
			register_taxonomy(
				'product_nutrient',
				/**
				 * Filter post types which are capable of storing deposit types.
				 *
				 * @param array $post_types The post types to support `product_nutrient` taxonomy.
				 *
				 * @since 3.9.0
				 */
				apply_filters( 'woocommerce_germanized_taxonomy_objects_product_nutrient', array( 'product' ) ),
				/**
				 * Filter to adjust arguments passed to register the `product_nutrient` taxonomy.
				 *
				 * @param array $args Arguments passed to `register_taxonomy`.
				 *
				 * @since 3.9.0
				 */
				apply_filters(
					'woocommerce_germanized_taxonomy_args_product_nutrient',
					array(
						'hierarchical'          => true,
						'update_count_callback' => '_wc_term_recount',
						'label'                 => __( 'Product Nutrients', 'woocommerce-germanized' ),
						'labels'                => array(
							'name'              => __( 'Product Nutrients', 'woocommerce-germanized' ),
							'singular_name'     => __( 'Product Nutrients', 'woocommerce-germanized' ),
							'menu_name'         => _x( 'Nutrients', 'Admin menu name', 'woocommerce-germanized' ),
							'search_items'      => __( 'Search Nutrients', 'woocommerce-germanized' ),
							'all_items'         => __( 'All Nutrients', 'woocommerce-germanized' ),
							'parent_item'       => __( 'Parent nutrient', 'woocommerce-germanized' ),
							'parent_item_colon' => __( 'Parent nutrient:', 'woocommerce-germanized' ),
							'edit_item'         => __( 'Edit Nutrient', 'woocommerce-germanized' ),
							'update_item'       => __( 'Update Nutrient', 'woocommerce-germanized' ),
							'add_new_item'      => __( 'Add New Nutrient', 'woocommerce-germanized' ),
							'new_item_name'     => __( 'New Nutrient Name', 'woocommerce-germanized' ),
						),
						'show_ui'               => ( WC_germanized()->is_pro() && apply_filters( 'woocommerce_gzd_show_food_ui', true ) ) ? true : false,
						'query_var'             => true,
						'public'                => false,
						'capabilities'          => array(
							'manage_terms' => 'manage_product_terms',
							'edit_terms'   => 'edit_product_terms',
							'delete_terms' => 'delete_product_terms',
							'assign_terms' => 'assign_product_terms',
						),
						'rewrite'               => false,
						'meta_box_cb'           => false,
						'show_in_quick_edit'    => false,
					)
				)
			);

			// Allergen
			register_taxonomy(
				'product_allergen',
				/**
				 * Filter post types which are capable of storing allergenic.
				 *
				 * @param array $post_types The post types to support `product_allergen` taxonomy.
				 *
				 * @since 3.9.0
				 */
				apply_filters( 'woocommerce_germanized_taxonomy_objects_product_allergen', array( 'product' ) ),
				/**
				 * Filter to adjust arguments passed to register the `product_allergen` taxonomy.
				 *
				 * @param array $args Arguments passed to `register_taxonomy`.
				 *
				 * @since 3.9.0
				 */
				apply_filters(
					'woocommerce_germanized_taxonomy_args_product_allergen',
					array(
						'hierarchical'          => false,
						'update_count_callback' => '_wc_term_recount',
						'label'                 => __( 'Product Allergenic', 'woocommerce-germanized' ),
						'labels'                => array(
							'name'          => __( 'Product Allergenic', 'woocommerce-germanized' ),
							'singular_name' => __( 'Product Allergen', 'woocommerce-germanized' ),
							'menu_name'     => _x( 'Allergenic', 'Admin menu name', 'woocommerce-germanized' ),
							'search_items'  => __( 'Search Allergenic', 'woocommerce-germanized' ),
							'all_items'     => __( 'All Allergenic', 'woocommerce-germanized' ),
							'edit_item'     => __( 'Edit Allergen', 'woocommerce-germanized' ),
							'update_item'   => __( 'Update Allergen', 'woocommerce-germanized' ),
							'add_new_item'  => __( 'Add New Allergen', 'woocommerce-germanized' ),
							'new_item_name' => __( 'New Allergen Name', 'woocommerce-germanized' ),
						),
						'show_ui'               => ( WC_germanized()->is_pro() && apply_filters( 'woocommerce_gzd_show_food_ui', true ) ) ? true : false,
						'query_var'             => true,
						'public'                => false,
						'capabilities'          => array(
							'manage_terms' => 'manage_product_terms',
							'edit_terms'   => 'edit_product_terms',
							'delete_terms' => 'delete_product_terms',
							'assign_terms' => 'assign_product_terms',
						),
						'rewrite'               => false,
						'meta_box_cb'           => false,
						'show_in_quick_edit'    => false,
					)
				)
			);
		}
	}
}

WC_GZD_Post_types::init();
