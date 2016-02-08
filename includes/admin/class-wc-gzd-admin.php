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
		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_action( 'save_post', array( $this, 'save_legal_page_content' ), 10, 3 );
		add_action( 'admin_menu', array( $this, 'remove_status_page_hooks' ), 0 );
		add_action( 'admin_menu', array( $this, 'set_status_page' ), 1 );
		add_action( 'admin_init', array( $this, 'check_tour_hide' ) );
		add_action( 'admin_init', array( $this, 'check_complaints_shortcode_append' ) );
	}

	/**
	 * Manually remove hook (class WC_Admin_Menus is noch callable)
	 */
	public function remove_status_page_hooks() {
		global $wp_filter;
		if ( isset( $wp_filter[ 'admin_menu' ][60] ) ) {
			foreach ( $wp_filter[ 'admin_menu' ][60] as $k => $f ) {
				if ( isset( $f[ 'function' ][1] ) && $f[ 'function' ][1] == 'status_menu' )
					unset( $wp_filter[ 'admin_menu' ][60][$k] );
			}
		}
	}

	public function set_status_page() {
		if ( ! is_ajax() ) {
			include_once( 'class-wc-gzd-admin-status.php' );
			add_action( 'admin_menu', array( $this, 'status_menu' ), 60 );
		}
	}

	public function status_menu() {
		add_submenu_page( 'woocommerce', __( 'WooCommerce Status', 'woocommerce-germanized' ),  __( 'System Status', 'woocommerce-germanized' ) , 'manage_woocommerce', 'wc-status', array( $this, 'status_page' ) );
		register_setting( 'woocommerce_status_settings_fields', 'woocommerce_status_options' );
	}

	public function status_page() {
		WC_GZD_Admin_Status::output();
	}

	public function add_scripts() {
		
		$screen = get_current_screen();
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path = WC_germanized()->plugin_url() . '/assets/';
		$admin_script_path = $assets_path . 'js/admin/';

		wp_register_style( 'woocommerce-gzd-admin', $assets_path . 'css/woocommerce-gzd-admin' . $suffix . '.css', false, WC_GERMANIZED_VERSION );
		wp_enqueue_style( 'woocommerce-gzd-admin' );

		wp_register_style( 'tourbus', $assets_path . 'css/tourbus' . $suffix . '.css', false, WC_GERMANIZED_VERSION );

		wp_register_script( 'wc-gzd-admin', $admin_script_path . 'settings' . $suffix . '.js', array( 'jquery', 'woocommerce_settings' ), WC_GERMANIZED_VERSION, true );
		wp_register_script( 'wc-gzd-admin-emails', $admin_script_path . 'emails' . $suffix . '.js', array( 'jquery', 'woocommerce_settings' ), WC_GERMANIZED_VERSION, true );
		wp_register_script( 'scrollto', $admin_script_path . 'scrollTo' . $suffix . '.js', array( 'jquery' ), WC_GERMANIZED_VERSION, true );
		wp_register_script( 'tourbus', $admin_script_path . 'tourbus' . $suffix . '.js', array( 'jquery' ), WC_GERMANIZED_VERSION, true );
		wp_register_script( 'wc-gzd-admin-tour', $admin_script_path . 'tour' . $suffix . '.js', array( 'jquery', 'woocommerce_settings', 'tourbus' ), WC_GERMANIZED_VERSION, true );

		if ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'germanized' ) {
			wp_enqueue_script( 'wc-gzd-admin' );

			$section = 'general';

			if ( isset( $_GET[ 'section' ] ) )
				$section = sanitize_text_field( $_GET[ 'section' ] );

			if ( $this->is_tour_enabled( $section ) ) {
				
				wp_enqueue_script( 'scrollto' );
				wp_enqueue_script( 'tourbus' );
				wp_enqueue_script( 'wc-gzd-admin-tour' );
				wp_enqueue_style( 'tourbus' );

			}
		}

		if ( isset( $_GET[ 'section' ] ) && ! empty( $_GET[ 'section' ] ) && strpos( $_GET[ 'section' ], 'gzd_' ) !== false ) {
			wp_enqueue_script( 'wc-gzd-admin-emails' );
		}

		// Hide delivery time and unit tagsdiv
		if ( version_compare( WC()->version, '2.3', '>=' ) )
			wp_add_inline_style( 'woocommerce-gzd-admin', '#tagsdiv-product_delivery_time, #tagsdiv-product_unit {display: none}' );
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
		wp_editor( htmlspecialchars_decode( get_post_meta( $post->ID, '_legal_text', true ) ), 'legal_page_email_content', array( 'textarea_name' => '_legal_text', 'textarea_rows' => 5 ) );
	}

	public function add_product_mini_desc() {
		global $post;
		if ( isset( $post ) ) {
			$product = wc_get_product( $post );
			if ( ! $product->is_type( 'variable' ) )
				add_meta_box( 'wc-gzd-product-mini-desc', __( 'Optional Mini Description', 'woocommerce-germanized' ), array( $this, 'init_product_mini_desc' ), 'product', 'advanced', 'high' );
		}
	}

	public function save_legal_page_content( $post_id, $post, $update ) {

		if ( $post->post_type != 'page' )
			return;

		if ( isset( $_POST[ '_legal_text' ] ) && ! empty( $_POST[ '_legal_text' ] ) )
			update_post_meta( $post_id, '_legal_text', esc_html( $_POST[ '_legal_text' ] ) );
		else
			delete_post_meta( $post_id, '_legal_text' );
		
	}

	public function init_product_mini_desc( $post ) {
		echo '<p class="small">' . __( 'This content will be shown as short product description within checkout and emails.', 'woocommerce-germanized' ) . '</p>';
		wp_editor( htmlspecialchars_decode( get_post_meta( $post->ID, '_mini_desc', true ) ), 'wc_gzd_product_mini_desc', array( 'textarea_name' => '_mini_desc', 'textarea_rows' => 5, 'media_buttons' => false ) );
	}

	public function disable_tour_link( $type ) {
		return wp_nonce_url( add_query_arg( array( 'tour' => $type, 'hide' => true ) ), 'wc-gzd-tour-hide' );
	}

	public function is_tour_enabled( $type = '' ) {
		return ( ! get_option( 'woocommerce_gzd_hide_tour' ) && ! get_option( 'woocommerce_gzd_hide_tour_' . $type ) );
	}

	public function check_tour_hide() {
		if ( isset( $_GET[ 'tour' ] ) && isset( $_GET[ 'hide' ] ) && isset( $_GET[ '_wpnonce' ] ) && check_admin_referer( 'wc-gzd-tour-hide' ) ) {
			if ( ! empty( $_GET[ 'tour' ] ) )
				update_option( 'woocommerce_gzd_hide_tour_' . sanitize_text_field( $_GET[ 'tour' ] ), true );
			else 
				update_option( 'woocommerce_gzd_hide_tour', true );
			wp_safe_redirect( remove_query_arg( array( 'hide', 'tour', '_wpnonce' ) ) );
		} else if ( isset( $_GET[ 'tour' ] ) && isset( $_GET[ 'enable' ] ) && isset( $_GET[ '_wpnonce' ] ) && check_admin_referer( 'wc-gzd-tour-enable' ) ) {
			$setting_sections = array_merge( array( 
				'general' => '', 
				'display' => '', 
				'emails' => '' ), 
			apply_filters( 'woocommerce_gzd_settings_sections', array() ) );
			
			delete_option( 'woocommerce_gzd_hide_tour' );
			
			foreach ( $setting_sections as $section => $name )
				delete_option( 'woocommerce_gzd_hide_tour_' . $section );
		}
 	}

 	public function is_complaints_shortcode_inserted() {
 		$imprint = wc_get_page_id( 'imprint' );
 		if ( $imprint != -1 ) {
 			$post = get_post( $imprint );
 			return ( strpos( $post->post_content, '[gzd_complaints]' ) !== false ? true : false );
 		}
 		return false;
 	}

 	public function check_complaints_shortcode_append() {
 		if ( isset( $_GET[ 'complaints' ] ) && 'add' === $_GET[ 'complaints' ] && isset( $_GET[ '_wpnonce' ] ) && check_admin_referer( 'append-complaints-shortcode' ) ) {
 			if ( wc_get_page_id( 'imprint' ) != 1 ) {
 				$page_id = wc_get_page_id( 'imprint' );
 				$this->insert_complaints_shortcode( $page_id );
 				wp_safe_redirect( admin_url( 'post.php?post=' . $page_id . '&action=edit' ) );
 			}
 		}
 	}

 	public function insert_complaints_shortcode( $page_id ) {
 		$page = get_post( $page_id );
 		wp_update_post(
 			array(
 				'ID' => $page_id,
 				'post_content' => $page->post_content . "\n<h3>" . __( 'Complaints Procedure', 'woocommerce-germanized' ) . "</h3>\n[gzd_complaints]",
 			)
 		);
 	}

}

WC_GZD_Admin::instance();