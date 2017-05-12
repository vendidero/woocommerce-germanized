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
		
		add_filter( 'woocommerce_admin_status_tabs', array( $this, 'set_gzd_status_tab' ) );
		add_action( 'woocommerce_admin_status_content_germanized', array( $this, 'status_tab' ) );

		add_action( 'admin_init', array( $this, 'check_tour_hide' ) );
		add_action( 'admin_init', array( $this, 'check_language_install' ) );
		add_action( 'admin_init', array( $this, 'check_text_options_deletion' ) );
		add_action( 'admin_init', array( $this, 'check_complaints_shortcode_append' ) );
		add_action( 'admin_init', array( $this, 'check_version_cache_deletion' ) );
		
		add_filter( 'woocommerce_addons_section_data', array( $this, 'set_addon' ), 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'set_order_parcel_delivery_opted_in' ), 10, 1 );

    }

	public function status_tab() {
		WC_GZD_Admin_Status::output();
	}

	public function set_gzd_status_tab( $tabs ) {
		$tabs[ 'germanized' ] = __( 'Germanized', 'woocommerce-germanized' );
		return $tabs;
	}

	public function set_order_parcel_delivery_opted_in( $order ) {

		if ( ! wc_gzd_get_crud_data( $order, 'parcel_delivery_opted_in' ) )
			return;

		?>
			<p><strong style="display: block;"><?php _e( 'Parcel Delivery Data Transfer:', 'woocommerce-germanized' ) ?></strong>
				<span><?php echo ( wc_gzd_order_supports_parcel_delivery_reminder( wc_gzd_get_crud_data( $order, 'id' ) ) ? __( 'allowed', 'woocommerce-germanized' ) : __( 'not allowed', 'woocommerce-germanized' ) ); ?></span>
			</p>
			<?php
	}

	public function set_addon( $products, $section_id ) {

		if ( $section_id !== 'featured' )
			return $products;

		array_unshift( $products, (object) array(
			'title' => 'Woo Germanized Pro',
			'excerpt' => 'Upgrade jetzt auf die Pro Version von WooCommerce Germanized und profitiere von weiteren nützliche Funktionen speziell für den deutschen Markt sowie professionellem Support.',
			'link' => 'https://vendidero.de/woocommerce-germanized#buy',
			'price' => '69,95 €',
		) );

		return $products;
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
		wp_register_script( 'scrollto', $admin_script_path . 'scrollTo' . $suffix . '.js', array( 'jquery' ), WC_GERMANIZED_VERSION, true );
		wp_register_script( 'tourbus', $admin_script_path . 'tourbus' . $suffix . '.js', array( 'jquery', 'scrollto' ), WC_GERMANIZED_VERSION, true );
		wp_register_script( 'wc-gzd-admin-tour', $admin_script_path . 'tour' . $suffix . '.js', array( 'jquery', 'woocommerce_settings', 'tourbus' ), WC_GERMANIZED_VERSION, true );
		wp_register_script( 'wc-gzd-admin-product-variations', $admin_script_path . 'product-variations' . $suffix . '.js', array( 'wc-admin-variation-meta-boxes' ), WC_GERMANIZED_VERSION );

		if ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'germanized' ) {
			
			wp_enqueue_script( 'wc-gzd-admin' );

			$section = 'general';

			if ( isset( $_GET[ 'section' ] ) )
				$section = sanitize_text_field( $_GET[ 'section' ] );

			if ( $section === 'trusted_shops' )
				do_action( 'woocommerce_gzd_load_trusted_shops_script' );

			if ( $this->is_tour_enabled( $section ) ) {
				
				wp_enqueue_script( 'scrollto' );
				wp_enqueue_script( 'tourbus' );
				wp_enqueue_script( 'wc-gzd-admin-tour' );
				wp_enqueue_style( 'tourbus' );

			}
		}

		if ( in_array( $screen->id, array( 'product', 'edit-product' ) ) )
			wp_enqueue_script( 'wc-gzd-admin-product-variations' );

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
		
		if ( is_object( $post ) && $post->post_type === 'product' ) {
			$product = wc_get_product( $post );
			if ( ! $product->is_type( 'variable' ) )
				add_meta_box( 'wc-gzd-product-mini-desc', __( 'Optional Mini Description', 'woocommerce-germanized' ), array( $this, 'init_product_mini_desc' ), 'product', 'advanced', 'high' );
		}
	}

	public function save_legal_page_content( $post_id, $post, $update ) {

		if ( $post->post_type != 'page' )
			return;

		if ( isset( $_POST[ '_legal_text' ] ) && ! empty( $_POST[ '_legal_text' ] ) )
			update_post_meta( $post_id, '_legal_text', sanitize_text_field( esc_html( $_POST[ '_legal_text' ] ) ) );
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
		
		} elseif ( isset( $_GET[ 'tour' ] ) && isset( $_GET[ 'enable' ] ) && isset( $_GET[ '_wpnonce' ] ) && check_admin_referer( 'wc-gzd-tour-enable' ) ) {
		
			$setting_sections = array_merge( array( 
				'general' => '', 
				'display' => '', 
				'email' => '' ),
			apply_filters( 'woocommerce_gzd_settings_sections', array() ) );
			
			delete_option( 'woocommerce_gzd_hide_tour' );
			
			foreach ( $setting_sections as $section => $name )
				delete_option( 'woocommerce_gzd_hide_tour_' . $section );
		}
 	}

 	public function check_language_install() {
		
		if ( isset( $_GET[ 'install-language' ] ) && isset( $_GET[ '_wpnonce' ] ) && check_admin_referer( 'wc-gzd-install-language' ) ) {
			
			require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
			$language = sanitize_text_field( $_GET[ 'install-language' ] );

			// Download language pack if possible
			if ( wp_can_install_language_pack() )
				$loaded_language = wp_download_language_pack( $language );

			update_option( 'WPLANG', $language );
			load_default_textdomain( $loaded_language );

			// Redirect to check for updates
			wp_safe_redirect( admin_url( 'update-core.php?force-check=1' ) );

		}

	}

	public function check_text_options_deletion() {

		if ( isset( $_GET[ 'delete-text-options' ] ) && isset( $_GET[ '_wpnonce' ] ) && check_admin_referer( 'wc-gzd-delete-text-options' ) ) {

			global $wpdb;

			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s", 'woocommerce_gzd_%_text' ) );

			$options = array(
				'woocommerce_gzd_checkout_legal_text_digital',
				'woocommerce_gzd_checkout_legal_text_digital_error',
				'woocommerce_gzd_order_confirmation_legal_digital_notice',
				'woocommerce_gzd_checkout_legal_text_error',
			);

			foreach ( $options as $option_name ) {
				delete_option( $option_name );
			}

			// Reinstall options
			WC_GZD_Install::create_options();

			do_action( 'woocommerce_gzd_deleted_text_options' );

			// Redirect to check for updates
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=germanized' ) );

		}

	}

	public function check_version_cache_deletion() {

		if ( isset( $_GET[ 'delete-version-cache' ] ) && isset( $_GET[ '_wpnonce' ] ) && check_admin_referer( 'wc-gzd-delete-version-cache' ) ) {

			wc_gzd_get_dependencies()->delete_cached_plugin_header_data();

            do_action( 'woocommerce_gzd_deleted_cached_plugin_header_data' );
		}
	}

	public function get_complaints_shortcode_pages() {

	    $pages = array(
            'imprint' => wc_get_page_id( 'imprint' ),
        );

	    if ( wc_get_page_id( 'terms' ) && wc_get_page_id( 'terms' ) != -1 ) {
	        $pages[ 'terms' ] = wc_get_page_id( 'terms' );
        }

        return $pages;
    }

	public function check_complaints_shortcode_append() {
 		if ( isset( $_GET[ 'complaints' ] ) && 'add' === $_GET[ 'complaints' ] && isset( $_GET[ '_wpnonce' ] ) && check_admin_referer( 'append-complaints-shortcode' ) ) {

 		    $pages = $this->get_complaints_shortcode_pages();

 		    foreach( $pages as $page_name => $page_id ) {

                if ( $page_id != 1 ) {
                    $this->insert_complaints_shortcode( $page_id );
                }
            }

            // Redirect to check for updates
            wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=germanized' ) );
 		}
 	}

 	public function is_complaints_shortcode_inserted( $page_id ) {
        $post = get_post( $page_id );
        if ( $post )
            return ( strpos( $post->post_content, '[gzd_complaints]' ) !== false ? true : false );
        return false;
 	}

 	public function insert_complaints_shortcode( $page_id ) {
	    if ( $this->is_complaints_shortcode_inserted( $page_id ) )
	        return;

 		$page = get_post( $page_id );
 		wp_update_post(
 			array(
 				'ID' => $page_id,
 				'post_content' => $page->post_content . "\n[gzd_complaints]",
 			)
 		);
 	}

 	public function get_shipping_method_instances() {

	    if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
		    $instances = WC()->shipping->get_shipping_methods();
        } else {
		    $zones = WC_Shipping_Zones::get_zones();
		    $worldwide = new WC_Shipping_Zone( 0 );

		    $instances = $worldwide->get_shipping_methods( true );

		    foreach( $zones as $id => $zone ) {
			    $zone = new WC_Shipping_Zone( $id );
			    $instances = $instances + $zone->get_shipping_methods( true );
		    }
        }

        return $instances;
    }

    public function get_shipping_method_instances_options() {

	    $methods = $this->get_shipping_method_instances();
	    $shipping_methods_options = array();

	    foreach ( $methods as $key => $method ) {

	        if ( method_exists( $method, 'get_rate_id' ) ) {
		        $key = $method->get_rate_id();
            } else {
	            $key = $method->id;
            }

		    $title = $method->get_title();

		    $shipping_methods_options[ $key ] = ( empty( $title ) ? $method->get_method_title() : $title );
	    }

	    return $shipping_methods_options;
    }

}

WC_GZD_Admin::instance();