<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZD_Admin_Importer {

	/**
	 * Single instance of WC_GZD_Importer
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public $enabled = false;

	public $taxonomies = array(
		'product_delivery_time' => 'product_delivery_times',
		'product_unit' => 'pa_masseinheit',
	);

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized-pro' ), '1.0' );
	}

	public function __construct() {

		// Check for previous installs
		if ( ! $this->is_available() )
			return;

		add_action( 'admin_init', array( $this, 'init' ) );

	}

	public function is_available() {
		return ( get_option( 'wgm_upgrade_24' ) && ! get_option( '_wc_gzd_import_finished' ) ? true : false );
	}

	public function init() {

		if ( isset( $_GET[ 'import' ] ) && check_admin_referer( 'wc-gzd-import', 'nonce' ) && current_user_can( 'edit_products' ) ) {
			$this->import();
		} elseif ( isset( $_GET[ 'skip-import' ] ) && check_admin_referer( 'wc-gzd-skip-import', 'nonce' ) ) {
			delete_option( '_wc_gzd_import_available' );
			update_option( '_wc_gzd_import_finished', 1 );
			wp_safe_redirect( remove_query_arg( array( 'nonce', 'skip-import' ) ) );
		}

	}

	private function import() {
		
		// Import legal pages
		$this->import_pages();
		
		// Import some settings
		$this->import_settings();

		// Import delivery time
		$this->import_product_data();
		
		// Finished
		delete_option( '_wc_gzd_import_available' );
		update_option( '_wc_gzd_import_finished', 1 );

		// Save redirect
		wp_safe_redirect( remove_query_arg( array( 'nonce', 'import' ) ) );
	}

	private function import_settings() {

		$settings = array(
			'woocommerce_widerrufsadressdaten' => 'woocommerce_gzd_revocation_address',
		);

		$settings_on_off = array(
			'woocommerce_de_show_delivery_time_overview' => 'woocommerce_gzd_display_listings_delivery_time',
			'woocommerce_de_show_price_per_unit' => 'woocommerce_gzd_display_listings_unit_price',
			'wgm_use_split_tax' => 'woocommerce_gzd_shipping_tax',
			'wgm_use_split_tax' => 'woocommerce_gzd_fee_tax',
		);

		// Small Business
		if ( 'on' === get_option( 'woocommerce_de_kleinunternehmerregelung' ) ) {
			update_option( 'woocommerce_gzd_small_enterprise', 'yes' );
			update_option( 'woocommerce_gzd_display_product_detail_small_enterprise', 'yes' );
		}

		// Update 1:1
		foreach ( $settings as $old => $new ) {
			if ( get_option( $old ) )
				update_option( $new, get_option( $old ) );
		}

		// Update on off
		foreach ( $settings_on_off as $old => $new ) {
			update_option( $new, ( 'on' === get_option( $old ) ? 'yes' : 'no' ) );
		}
 
	}

	private function import_pages() {
		
		$pages = array(
			'woocommerce_widerruf_page_id' => 'woocommerce_revocation_page_id',
			'woocommerce_impressum_page_id' => 'woocommerce_imprint_page_id',
			'woocommerce_datenschutz_page_id' => 'woocommerce_data_security_page_id',
			'woocommerce_zahlungsarten_page_id' => 'woocommerce_payment_methods_page_id',
			'woocommerce_versandkosten__lieferung_page_id' => 'woocommerce_shipping_costs_page_id',
		);

		foreach ( $pages as $old => $new ) {
			
			if ( get_option( $old ) )
				update_option( $new, get_option( $old ) );

		}

	}

	private function import_single_product_data( $product ) {

		$save = array(
			'product-type' => $product->get_type(),
			'_unit_price_sale' => '',
			'_unit_price_regular' => '',
		);

		// Price per unit
		if ( get_post_meta( $product->id, '_regular_price_per_unit', true ) ) {

			$regular = get_post_meta( $product->id, '_regular_price_per_unit', true );
			$base = get_post_meta( $product->id, '_unit_regular_price_per_unit_mult', true );
			$sale = get_post_meta( $product->id, '_sale_price_per_unit', true );
			$unit = get_post_meta( $product->id, '_unit_regular_price_per_unit', true );

			if ( $unit ) {

				$unit_term = get_term_by( 'slug', $unit, $this->taxonomies[ 'product_unit' ] );
				
				if ( $unit_term && ! is_wp_error( $unit_term ) ) {
					
					$gzd_term = false;

					if ( ! $gzd_term = get_term_by( 'slug', $unit, 'product_unit' ) )
						$gzd_term = wp_insert_term( $unit_term->name, 'product_unit', array( 'description' => $unit_term->description ) );

					if ( $gzd_term && ! is_wp_error( $gzd_term ) ) {

						$save['_unit'] = $gzd_term->slug;
						$save['_unit_base'] = $base;
						$save['_unit_price_regular'] = $regular;
						$save['_unit_price_sale'] = $sale;
						$save['_sale_price'] = $product->get_sale_price();
						$save['_sale_price_dates_from'] = ( get_post_meta( $product->id, '_sale_price_dates_from', true ) ? get_post_meta( $product->id, '_sale_price_dates_from', true ) : '' );
						$save['_sale_price_dates_to'] = ( get_post_meta( $product->id, '_sale_price_dates_to', true ) ? get_post_meta( $product->id, '_sale_price_dates_to', true ) : '' );

					}

				}

			}

		}

		// Delivery time
		if ( $delivery_time = get_post_meta( $product->id, '_lieferzeit', true ) ) {

			$term = get_term_by( 'id', $delivery_time, $this->taxonomies[ 'product_delivery_time' ] );

			if ( $term && ! is_wp_error( $term ) )
				$save['delivery_time'] = $term->name;
		
		}

		// Free shipping
		if ( 'on' === get_post_meta( $product->id, '_suppress_shipping_notice', true ) && ! $product->is_type( 'variation' ) ) {
			$save['_free_shipping'] = 'yes';
		}

		// Save
		if ( sizeof( $save ) > 3 ) {
			WC_Germanized_Meta_Box_Product_Data::save_product_data( $product->id, $save, ( $product->is_type( 'variation' ) ) );
		}

	}

	private function import_product_data() {

		// Temporarily add taxonomy if doesnt exist
		foreach ( $this->taxonomies as $taxonomy ) {

			if ( ! taxonomy_exists( $taxonomy ) )
				register_taxonomy( $taxonomy, array( 'product' ) );

		}

		// First get products
		$posts = get_posts( array( 
			'post_type' => 'product', 
			'posts_per_page' => -1, 
			'fields' => 'ids',
			'post_status' => array( 'publish', 'draft', 'private' ),
		) );

		if ( ! empty( $posts ) ) {

			foreach ( $posts as $post_id ) {

				$product = wc_get_product( $post_id );

				$this->import_single_product_data( $product );

				if ( $product->is_type( 'variable' ) ) {

					$variations = $product->get_children();
					
					if ( ! empty( $variations ) ) {

						foreach ( $variations as $variation_id ) {

							$variation = wc_get_product( $variation_id );
							$this->import_single_product_data( $variation );

						}

					}

				}

			}

		}

	}

}

return WC_GZD_Admin_Importer::instance();

?>