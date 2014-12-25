<?php

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

class WC_GZD_Admin {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-germanized' ), '1.0' );
	}
	
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_legal_page_metabox' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_product_mini_desc' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'settings_page_scroll_top' ) );
	}

	public function settings_page_scroll_top() {
		$assets_path = str_replace( array( 'http:', 'https:' ), '', WC_germanized()->plugin_url() ) . '/assets/';
		$admin_script_path = $assets_path . 'js/admin/';
		if ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'germanized' )
			wp_enqueue_script( 'wc-gzd-admin', $admin_script_path . 'settings.js', array( 'jquery', 'woocommerce_settings' ), WC_GERMANIZED_VERSION, true );
	}

	public function add_legal_page_metabox() {
		add_meta_box( 'wc-gzd-legal-page-email-content', __( 'Optional Email Content', 'woocommerce-germanized' ), array( $this, 'init_legal_page_metabox' ), 'page' );
	}

	public function init_legal_page_metabox( $post ) {
		$legal_pages = array( wc_get_page_id( 'revocation' ), wc_get_page_id( 'data_security' ), wc_get_page_id( 'imprint' ), wc_get_page_id( 'terms' ) );
		if ( ! in_array( $post->ID, $legal_pages ) ) {
			echo '<style type="text/css">#wc-gzd-legal-page-email-content { display: none; }</style>';
			return;
		}
		echo '<p class="small">' . __( 'Add content which will be replacing default page content within emails.', 'woocommerce-germanized' ) . '</p>';
		wp_editor( get_the_excerpt(), 'legal_page_email_content', array( 'textarea_name' => 'excerpt', 'textarea_rows' => 5 ) );
	}

	public function add_product_mini_desc() {
		global $post;
		if ( isset( $post ) ) {
			$product = wc_get_product( $post );
			if ( ! $product->is_type( 'variable' ) )
				add_meta_box( 'wc-gzd-product-mini-desc', __( 'Optional Mini Description', 'woocommerce-germanized' ), array( $this, 'init_product_mini_desc' ), 'product', 'advanced', 'high' );
		}
	}

	public function init_product_mini_desc( $post ) {
		echo '<p class="small">' . __( 'This content will be shown as short product description within checkout and emails.', 'woocommerce-germanized' ) . '</p>';
		wp_editor( get_post_meta( $post->ID, '_mini_desc', true ), 'wc_gzd_product_mini_desc', array( 'textarea_name' => '_mini_desc', 'textarea_rows' => 5, 'media_buttons' => false ) );
	}

}

WC_GZD_Admin::instance();