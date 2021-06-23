<?php

use Vendidero\Germanized\DHL\Admin\Importer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class WC_GZD_Admin {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	protected $wizard = null;

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
		add_action( 'admin_menu', array( $this, 'hide_metaboxes' ), 10 );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_action( 'save_post', array( $this, 'save_legal_page_content' ), 10, 3 );

		add_filter( 'woocommerce_admin_status_tabs', array( $this, 'set_gzd_status_tab' ) );
		add_action( 'woocommerce_admin_status_content_germanized', array( $this, 'status_tab' ) );

		add_action( 'admin_init', array( $this, 'check_language_install' ) );
		add_action( 'admin_init', array( $this, 'check_text_options_deletion' ) );
		add_action( 'admin_init', array( $this, 'check_complaints_shortcode_append' ) );
		add_action( 'admin_init', array( $this, 'check_insert_vat_rates' ) );
		add_action( 'admin_init', array( $this, 'check_resend_activation_email' ) );
		add_action( 'admin_init', array( $this, 'check_notices' ) );
		add_action( 'admin_init', array( $this, 'check_dhl_import' ) );
		add_action( 'admin_init', array( $this, 'check_internetmarke_import' ) );
		add_action( 'admin_init', array( $this, 'check_encryption_key_insert' ) );

		add_filter( 'woocommerce_addons_section_data', array( $this, 'set_addon' ), 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array(
			$this,
			'set_order_parcel_delivery_opted_in'
		), 10, 1 );

		add_filter( 'woocommerce_order_actions', array( $this, 'order_actions' ), 10, 1 );
		add_action( 'woocommerce_order_action_order_confirmation', array( $this, 'resend_order_confirmation' ), 10, 1 );
		add_action( 'woocommerce_order_action_paid_for_order_notification', array(
			$this,
			'send_paid_for_order_notification'
		), 10, 1 );

		add_filter( 'pre_update_option_wp_page_for_privacy_policy', array(
			$this,
			'pre_update_wp_privacy_option_page'
		), 10, 2 );
		add_filter( 'pre_update_option_woocommerce_data_security_page_id', array(
			$this,
			'pre_update_gzd_privacy_option_page'
		), 10, 2 );

		add_action( 'woocommerce_admin_field_gzd_toggle', array( $this, 'toggle_input_field' ), 10 );
		add_action( 'woocommerce_admin_field_image', array( $this, 'image_field' ), 10, 1 );
		add_action( 'woocommerce_admin_field_html', array( $this, 'html_field' ), 10, 1 );
		add_action( 'woocommerce_admin_field_hidden', array( $this, 'hidden_field' ), 10, 1 );

		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'save_toggle_input_field' ), 0, 3 );

		add_action( 'woocommerce_oss_enabled_oss_procedure', array( $this, 'oss_enable_hide_tax_percentage' ), 10 );

		$this->wizward = require 'class-wc-gzd-admin-setup-wizard.php';
	}

	public function oss_enable_hide_tax_percentage() {
	    update_option( 'woocommerce_gzd_hide_tax_rate_shop', 'yes' );
    }

	public function hide_metaboxes() {
		remove_meta_box( 'tagsdiv-product_unit', 'product', 'side' );
		remove_meta_box( 'tagsdiv-product_delivery_time', 'product', 'side' );
		remove_meta_box( 'tagsdiv-product_price_label', 'product', 'side' );
	}

	public function check_dhl_import() {

		if ( ! class_exists( '\Vendidero\Germanized\DHL\Admin\Importer\DHL' ) ) {
			return;
		}

		if ( Importer\DHL::is_available() ) {
			if ( isset( $_GET['wc-gzd-dhl-import'] ) && isset( $_GET['_wpnonce'] ) ) { // WPCS: input var ok, CSRF ok.

				if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'woocommerce_gzd_dhl_import_nonce' ) ) { // WPCS: input var ok, CSRF ok.
					wp_die( esc_html_x( 'Action failed. Please refresh the page and retry.', 'dhl', 'woocommerce-germanized' ) );
				}

				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_die( esc_html_x( 'You don\'t have permission to do this.', 'dhl', 'woocommerce-germanized' ) );
				}

				$this->import_dhl_settings();

				wp_safe_redirect( wc_gzd_get_shipping_provider( 'dhl' )->get_edit_link() );
			}
		}
	}

	public function import_dhl_settings() {
		Importer\DHL::import_order_data( 50 );
		Importer\DHL::import_settings();

		deactivate_plugins( 'dhl-for-woocommerce/pr-dhl-woocommerce.php' );

		update_option( 'woocommerce_gzd_dhl_enable', 'yes' );
		update_option( 'woocommerc_gzd_dhl_import_finished', 'yes' );
	}

	public function check_internetmarke_import() {

		if ( ! class_exists( '\Vendidero\Germanized\DHL\Admin\Importer\Internetmarke' ) ) {
			return;
		}

		if ( Importer\Internetmarke::is_available() ) {
			if ( isset( $_GET['wc-gzd-internetmarke-import'] ) && isset( $_GET['_wpnonce'] ) ) { // WPCS: input var ok, CSRF ok.

				if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'woocommerce_gzd_internetmarke_import_nonce' ) ) { // WPCS: input var ok, CSRF ok.
					wp_die( esc_html_x( 'Action failed. Please refresh the page and retry.', 'dhl', 'woocommerce-germanized' ) );
				}

				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_die( esc_html_x( 'You don\'t have permission to do this.', 'dhl', 'woocommerce-germanized' ) );
				}

				$this->import_internetmarke_settings();

				wp_safe_redirect( wc_gzd_get_shipping_provider( 'deutsche_post' )->get_edit_link() );
			}
		}
	}

	public function import_internetmarke_settings() {
		Importer\DHL::import_settings();

		deactivate_plugins( 'woo-dp-internetmarke/woo-dp-internetmarke.php' );

		update_option( 'woocommerce_gzd_dhl_internetmarke_enable', 'yes' );
		update_option( 'woocommerce_gzd_internetmarke_import_finished', 'yes' );
	}

	public function save_toggle_input_field( $value, $option, $raw_value ) {
		if ( 'gzd_toggle' === $option['type'] ) {
			$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
		}

		return $value;
	}

	public function image_field( $value ) {
		?>
        <tr valign="top">
            <th class="forminp forminp-image" colspan="2" id="<?php echo esc_attr( $value['id'] ); ?>">
                <a href="<?php echo esc_attr( $value['href'] ); ?>" target="_blank"><img
                            src="<?php echo $value['img']; ?>"/></a>
            </th>
        </tr>
		<?php
	}

	public function html_field( $value ) {
		?>
        <tr valign="top">
            <th class="forminp forminp-html" id="<?php echo esc_attr( $value['id'] ); ?>">
                <label><?php echo esc_attr( $value['title'] ); ?><?php echo( isset( $value['desc_tip'] ) && ! empty( $value['desc_tip'] ) ? wc_help_tip( $value['desc_tip'] ) : '' ); // WPCS: XSS ok. ?></label>
            </th>
            <td class="forminp"><?php echo $value['html']; ?></td>
        </tr>
		<?php
	}

	public function hidden_field( $value ) {
		$option_value = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		?>
        <tr valign="top" style="display: none">
            <th class="forminp forminp-image">
                <input type="hidden" id="<?php echo esc_attr( $value['id'] ); ?>"
                       value="<?php echo esc_attr( $option_value ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>"/>
            </th>
        </tr>
		<?php
	}

	/**
	 * Show a notice highlighting bad template files.
	 */
	public function get_template_version_check_result() {

		/**
		 * Filter to include certain packages or plugins while checking for outdated templates.
		 *
		 * @param array $template_data Template data in key => value pairs.
		 *
		 * @since 3.0.0
		 *
		 */
		$template_data = apply_filters( 'woocommerce_gzd_template_check', array(
			'germanized' => array(
				'title'             => __( 'Germanized for WooCommerce', 'woocommerce-germanized' ),
				'path'              => WC_germanized()->plugin_path() . '/templates',
				'template_path'     => WC_germanized()->template_path(),
				'outdated_help_url' => 'https://vendidero.de/dokument/veraltete-germanized-templates-aktualisieren',
				'files'             => array(),
				'has_outdated'      => false,
			)
		) );

		foreach ( $template_data as $plugin => $path_data ) {

			$path_data = wp_parse_args( $path_data, array(
				'title'             => '',
				'path'              => '',
				'template_path'     => '',
				'outdated_help_url' => 'https://vendidero.de/dokument/veraltete-germanized-templates-aktualisieren',
				'files'             => array(),
				'has_outdated'      => false,
			) );

			$template_data[ $plugin ] = $path_data;

			$core_templates = WC_Admin_Status::scan_template_files( $path_data['path'] );
			$template_path  = $path_data['template_path'];

			foreach ( $core_templates as $file ) {

				if ( '.DS_Store' === $file ) {
					continue;
				}

				$theme_file = false;

				if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
					$theme_file = get_stylesheet_directory() . '/' . $file;
				} elseif ( file_exists( get_stylesheet_directory() . '/' . $template_path . $file ) ) {
					$theme_file = get_stylesheet_directory() . '/' . $template_path . $file;
				} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
					$theme_file = get_template_directory() . '/' . $file;
				} elseif ( file_exists( get_template_directory() . '/' . $template_path . $file ) ) {
					$theme_file = get_template_directory() . '/' . $template_path . $file;
				}

				if ( false !== $theme_file ) {
					$core_version  = WC_Admin_Status::get_file_version( $path_data['path'] . '/' . $file );
					$theme_version = WC_Admin_Status::get_file_version( $theme_file );

					if ( ! $theme_version ) {
						$theme_version = '1.0';
					}

					$file_data = array(
						'template'      => $file,
						'theme_file'    => $theme_file,
						'theme_version' => $theme_version,
						'core_version'  => $core_version,
						'outdated'      => false,
					);

					if ( $core_version && $theme_version && version_compare( $theme_version, $core_version, '<' ) ) {
						$file_data['outdated']                    = true;
						$template_data[ $plugin ]['has_outdated'] = true;
					}

					$template_data[ $plugin ]['files'][] = $file_data;
				}
			}
		}

		return $template_data;
	}

	public function toggle_input_field( $value ) {
		// Custom attribute handling.
		$custom_attributes = array();

		if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
			foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}
		}

		// Description handling.
		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];

		if ( ! isset( $value['value'] ) ) {
			$value['value'] = WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}

		$option_value      = $value['value'];

		if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) {
			?>
            <tr valign="top">
            <th scope="row" class="titledesc">
                <span class="wc-gzd-label-wrap"><?php echo esc_html( $value['title'] ); ?><?php echo $tooltip_html; // WPCS: XSS ok. ?></span>
            </th>
            <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
            <fieldset>
			<?php
		} else {
			?>
            <fieldset>
			<?php
		}
		?>
        <a href="#" class="woocommerce-gzd-input-toggle-trigger">
            <span id="<?php echo esc_attr( $value['id'] ); ?>-toggle"
                  class="woocommerce-gzd-input-toggle woocommerce-input-toggle woocommerce-input-toggle--<?php echo( 'yes' === $option_value ? 'enabled' : 'disabled' ); ?>"><?php echo( 'yes' === $option_value ? __( 'Yes', 'woocommerce-germanized' ) : __( 'No', 'woocommerce-germanized' ) ); ?></span>
        </a>
        <input
        name="<?php echo esc_attr( $value['id'] ); ?>"
        id="<?php echo esc_attr( $value['id'] ); ?>"
        type="checkbox"
        style="display: none; <?php echo esc_attr( $value['css'] ); ?>"
        value="1"
        class="<?php echo esc_attr( $value['class'] ); ?>"
		<?php checked( $option_value, 'yes' ); ?>
		<?php echo implode( ' ', $custom_attributes ); // WPCS: XSS ok. ?>
        /><?php echo esc_html( $value['suffix'] ); ?><?php echo $description; // WPCS: XSS ok. ?>

        </fieldset>
		<?php if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) {
			?>
            </td>
            </tr>
			<?php
		}
	}

	public function pre_update_gzd_privacy_option_page( $new_value, $old_value ) {
		/**
		 * Filter to disable syncing WP privacy page option with Germanized
		 * privacy page option.
		 *
		 * @param bool $enabled Set to false to disable syncing.
		 *
		 * @since 2.0.0
		 *
		 */
		if ( apply_filters( 'woocommerce_gzd_sync_wp_privacy_page', true ) ) {
			remove_filter( 'pre_update_option_wp_page_for_privacy_policy', array(
				$this,
				'pre_update_wp_privacy_option_page'
			), 10 );
			update_option( 'wp_page_for_privacy_policy', $new_value );
		}

		return $new_value;
	}

	/**
	 * Updates Germanized privacy page option as soon as WP option changes to keep the pages in sync.
	 *
	 * @param $new_value
	 * @param $old_value
	 */
	public function pre_update_wp_privacy_option_page( $new_value, $old_value ) {

		/** This filter is documented in includes/admin/class-wc-gzd-admin.php */
		if ( apply_filters( 'woocommerce_gzd_sync_wp_privacy_page', true ) ) {
			remove_filter( 'pre_update_option_woocommerce_data_security_page_id', array(
				$this,
				'pre_update_gzd_privacy_option_page'
			), 10 );
			update_option( 'woocommerce_data_security_page_id', $new_value );
		}

		return $new_value;
	}

	public function send_paid_for_order_notification( $order ) {
		do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_paid_for_order' );

		$mail = WC_germanized()->emails->get_email_instance_by_id( 'customer_paid_for_order' );

		if ( $mail ) {
			$mail->trigger( $order );

			// Note the event.
			$order->add_order_note( __( 'Paid for order notification manually sent to customer.', 'woocommerce-germanized' ), false, true );
		}

		do_action( 'woocommerce_after_resend_order_email', $order, 'customer_paid_for_order' );
	}

	public function resend_order_confirmation( $order ) {
		do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_processing_order' );

		// Send the customer invoice email.
		WC()->payment_gateways();
		WC()->shipping();

		$mail_id = 'customer_processing_order';
		$mail    = WC_germanized()->emails->get_email_instance_by_id( $mail_id );

		if ( $mail ) {
			$mail->trigger( $order );

			// Note the event.
			$order->add_order_note( __( 'Order confirmation manually sent to customer.', 'woocommerce-germanized' ), false, true );

			/**
			 * Admin manual resend order confirmation email.
			 *
			 * This hook fires after a manual resend of the order confirmation email has been triggered.
			 *
			 * @param WC_Order $order The order for which the confirmation email is sent.
			 * @param string $mail_id The email id (customer_processing_order).
			 *
			 * @since 1.0.0
			 *
			 */
			do_action( 'woocommerce_gzd_after_resend_order_confirmation_email', $order, $mail_id );
		}

		do_action( 'woocommerce_after_resend_order_email', $order, 'customer_processing_order' );
	}

	public function order_actions( $actions ) {
		$actions['order_confirmation']          = __( 'Resend order confirmation', 'woocommerce-germanized' );
		$actions['paid_for_order_notification'] = __( 'Send paid for order notification', 'woocommerce-germanized' );

		return $actions;
	}

	public function status_tab() {
		WC_GZD_Admin_Status::output();
	}

	public function set_gzd_status_tab( $tabs ) {
		$tabs['germanized'] = __( 'Germanized', 'woocommerce-germanized' );

		return $tabs;
	}

	/**
	 * @param WC_Order $order
	 */
	public function set_order_parcel_delivery_opted_in( $order ) {
		if ( ! $order->get_meta( '_parcel_delivery_opted_in' ) ) {
			return;
		}
		?>
        <p class="parcel-delivery-checkbox-status"><strong
                    style="display: block;"><?php _e( 'Parcel Delivery Data Transfer:', 'woocommerce-germanized' ) ?></strong>
            <span><?php echo( wc_gzd_order_supports_parcel_delivery_reminder( $order->get_id() ) ? __( 'allowed', 'woocommerce-germanized' ) : __( 'not allowed', 'woocommerce-germanized' ) ); ?></span>
        </p>
		<?php
	}

	public function set_addon( $products, $section_id ) {
		if ( $section_id !== 'featured' ) {
			return $products;
		}

		array_unshift( $products, (object) array(
			'title'   => 'Germanized für WooCommerce Pro',
			'excerpt' => 'Upgrade jetzt auf die Pro Version von Germanized und profitiere von weiteren nützliche Funktionen speziell für den deutschen Markt sowie professionellem Support.',
			'link'    => 'https://vendidero.de/woocommerce-germanized#upgrade',
			'price'   => '69 €',
		) );

		return $products;
	}

	public function status_page() {
		WC_GZD_Admin_Status::output();
	}

	public function add_scripts() {

		$screen            = get_current_screen();
		$suffix            = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path       = WC_germanized()->plugin_url() . '/assets/';
		$admin_script_path = $assets_path . 'js/admin/';

		wp_register_style( 'woocommerce-gzd-admin', $assets_path . 'css/admin' . $suffix . '.css', false, WC_GERMANIZED_VERSION );
		wp_enqueue_style( 'woocommerce-gzd-admin' );

		wp_register_style( 'woocommerce-gzd-admin-settings', $assets_path . 'css/admin-settings' . $suffix . '.css', array(
			'woocommerce_admin_styles',
			'woocommerce-gzd-admin'
		), WC_GERMANIZED_VERSION );

		wp_register_script( 'wc-gzd-admin-product-variations', $admin_script_path . 'product-variations' . $suffix . '.js', array( 'wc-admin-variation-meta-boxes' ), WC_GERMANIZED_VERSION );
		wp_register_script( 'wc-gzd-admin-legal-checkboxes', $admin_script_path . 'legal-checkboxes' . $suffix . '.js', array(
			'jquery',
			'wp-util',
			'underscore',
			'backbone',
			'jquery-ui-sortable',
			'wc-enhanced-select'
		), WC_GERMANIZED_VERSION );

		wp_register_script( 'wc-gzd-admin-settings', $assets_path . 'js/admin/settings' . $suffix . '.js', array(
			'jquery',
			'woocommerce_admin'
		), WC_GERMANIZED_VERSION, true );

		wp_localize_script(
			'wc-gzd-admin-settings',
			'wc_gzd_admin_settings_params',
			array(
				'tab_toggle_nonce' => wp_create_nonce( 'wc_gzd_tab_toggle_nonce' ),
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
			)
		);

		if ( in_array( $screen->id, array( 'product', 'edit-product' ) ) ) {
			wp_enqueue_script( 'wc-gzd-admin-product-variations' );
		}

		/**
		 * After admin assets.
		 *
		 * This hook fires after Germanized has loaded and enqueued it's admin assets.
		 *
		 * @param WC_GZD_Admin $this The admin class.
		 * @param string $admin_script_path The absolute URL to the plugins admin js scripts.
		 * @param string $suffix The assets suffix e.g. .min in non-debugging-mode.
		 *
		 * @since 1.0.0
		 *
		 */
		do_action( 'woocommerce_gzd_admin_assets', $this, $admin_script_path, $suffix );
	}

	public function add_legal_page_metabox() {
		add_meta_box( 'wc-gzd-legal-page-email-content', __( 'Optional Email Content', 'woocommerce-germanized' ), array(
			$this,
			'init_legal_page_metabox'
		), 'page' );
	}

	public function init_legal_page_metabox( $post ) {
		$legal_pages = array(
			wc_get_page_id( 'revocation' ),
			wc_get_page_id( 'data_security' ),
			wc_get_page_id( 'imprint' ),
			wc_get_page_id( 'terms' )
		);
		if ( ! in_array( $post->ID, $legal_pages ) ) {
			echo '<style type="text/css">#wc-gzd-legal-page-email-content { display: none; }</style>';

			return;
		}
		echo '<p class="small">' . __( 'Add content which will be replacing default page content within emails.', 'woocommerce-germanized' ) . '</p>';
		wp_editor( htmlspecialchars_decode( get_post_meta( $post->ID, '_legal_text', true ) ), 'legal_page_email_content', array(
			'textarea_name' => '_legal_text',
			'textarea_rows' => 5
		) );
	}

	public function add_product_mini_desc() {
		global $post;

		if ( is_object( $post ) && $post->post_type === 'product' ) {
			$product = wc_get_product( $post );

			if ( $product && ( ! $product->is_type( 'variable' ) ) ) {
				add_meta_box( 'wc-gzd-product-mini-desc', __( 'Optional Mini Description', 'woocommerce-germanized' ), array(
					$this,
					'init_product_mini_desc'
				), 'product', 'advanced', 'high' );
			}
		}
	}

	public function save_legal_page_content( $post_id, $post, $update ) {

		if ( $post->post_type != 'page' ) {
			return;
		}

		if ( isset( $_POST['_legal_text'] ) && ! empty( $_POST['_legal_text'] ) ) {
			update_post_meta( $post_id, '_legal_text', wc_gzd_sanitize_html_text_field( $_POST['_legal_text'] ) );
		} else {
			delete_post_meta( $post_id, '_legal_text' );
		}
	}

	public function init_product_mini_desc( $post ) {
		echo '<p class="small">' . __( 'This content will be shown as short product description within checkout and emails.', 'woocommerce-germanized' ) . '</p>';

		wp_editor( htmlspecialchars_decode( get_post_meta( $post->ID, '_mini_desc', true ) ), 'wc_gzd_product_mini_desc', array(
			'textarea_name' => '_mini_desc',
			'textarea_rows' => 5,
			'media_buttons' => false
		) );
	}

	public function check_language_install() {
		if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['install-language'] ) && isset( $_GET['_wpnonce'] ) && check_admin_referer( 'wc-gzd-install-language' ) ) {

			require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
			$language = sanitize_text_field( $_GET['install-language'] );

			// Download language pack if possible
			if ( wp_can_install_language_pack() ) {
				$loaded_language = wp_download_language_pack( $language );
			}

			update_option( 'WPLANG', $language );
			load_default_textdomain( $loaded_language );

			// Redirect to check for updates
			wp_safe_redirect( admin_url( 'update-core.php?force-check=1' ) );

		}

	}

	public function check_text_options_deletion() {
		if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['delete-text-options'] ) && isset( $_GET['_wpnonce'] ) && check_admin_referer( 'wc-gzd-delete-text-options' ) ) {

			global $wpdb;

			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->options WHERE option_name LIKE %s", 'woocommerce_gzd_%_text' ) );

			$manager = WC_GZD_Legal_Checkbox_Manager::instance();
			$manager->do_register_action();
			$options = $manager->get_options();

			$checkboxes   = $manager->get_checkboxes();
			$text_options = array(
				'label',
				'error_message',
				'confirmation',
				'admin_desc',
				'admin_name',
			);

			foreach ( $checkboxes as $checkbox ) {
				if ( ! $checkbox->is_core() ) {
					continue;
				}
				foreach ( $text_options as $text_option ) {
					if ( isset( $options[ $checkbox->get_id() ][ $text_option ] ) ) {
						unset( $options[ $checkbox->get_id() ][ $text_option ] );
					}
				}
			}

			/**
			 * Clear options cache before calling add_option again
			 */
			wp_cache_delete( 'notoptions', 'options' );
			wp_cache_delete( 'alloptions', 'options' );

			$manager->update_options( $options );

			// Reinstall options
			WC_GZD_Install::create_options();

			/**
			 * After text options deletion.
			 *
			 * This hook fires after Germanized has deleted and re-installed it's text options.
			 *
			 * @since 1.6.0
			 */
			do_action( 'woocommerce_gzd_deleted_text_options' );

			// Redirect to check for updates
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=germanized' ) );
		}
	}

	public function get_complaints_shortcode_pages() {

		$pages = array(
			'imprint' => wc_get_page_id( 'imprint' ),
		);

		if ( wc_get_page_id( 'terms' ) && wc_get_page_id( 'terms' ) != - 1 ) {
			$pages['terms'] = wc_get_page_id( 'terms' );
		}

		return $pages;
	}

	public function check_resend_activation_email() {
		if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['user_id'] ) && isset( $_GET['gzd-resend-activation'] ) && 'yes' === $_GET['gzd-resend-activation'] && isset( $_GET['_wpnonce'] ) && check_admin_referer( 'resend-activation-link' ) ) {
			$user_id = absint( $_GET['user_id'] );

			if ( ! empty( $user_id ) && ! wc_gzd_is_customer_activated( $user_id ) ) {
				$helper              = WC_GZD_Customer_Helper::instance();
				$user_activation     = $helper->get_customer_activation_meta( $user_id, true );
				$user_activation_url = $helper->get_customer_activation_url( $user_activation );

				if ( $email = WC_germanized()->emails->get_email_instance_by_id( 'customer_new_account_activation' ) ) {
					$email->trigger( $user_id, $user_activation, $user_activation_url );
				}
			}

			// Redirect to check for updates
			wp_safe_redirect( admin_url( sprintf( 'user-edit.php?user_id=%d&gzd-sent=yes', $user_id ) ) );
		}
	}

	public function check_complaints_shortcode_append() {
		if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['complaints'] ) && 'add' === $_GET['complaints'] && isset( $_GET['_wpnonce'] ) && check_admin_referer( 'append-complaints-shortcode' ) ) {

			$pages = $this->get_complaints_shortcode_pages();

			foreach ( $pages as $page_name => $page_id ) {

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
		if ( $post ) {
			return ( strpos( $post->post_content, '[gzd_complaints' ) !== false ? true : false );
		}

		return false;
	}

	public function insert_complaints_shortcode( $page_id ) {
		if ( $this->is_complaints_shortcode_inserted( $page_id ) ) {
			return;
		}

		$page = get_post( $page_id );
		wp_update_post(
			array(
				'ID'           => $page_id,
				'post_content' => $page->post_content . "\n[gzd_complaints]",
			)
		);
	}

	public function check_encryption_key_insert() {
		if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['insert-encryption-key'] ) && isset( $_GET['_wpnonce'] ) && check_admin_referer( 'wc-gzd-insert-encryption-key' ) ) {
			$result = false;

		    if ( class_exists( 'WC_GZD_Secret_Box_Helper' ) ) {
				if ( ! WC_GZD_Secret_Box_Helper::has_valid_encryption_key() ) {
					$result = WC_GZD_Secret_Box_Helper::maybe_insert_missing_key();
				}
			}

			// Redirect to check for updates
			wp_safe_redirect( add_query_arg( array( 'added-encryption-key' => wc_bool_to_string( $result ) ), wp_get_referer() ) );
		}
	}

	public function check_notices() {
		if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['check-notices'] ) && isset( $_GET['_wpnonce'] ) && check_admin_referer( 'wc-gzd-notices' ) ) {

			if ( get_option( 'woocommerce_gzd_disable_notices' ) ) {
				delete_option( 'woocommerce_gzd_disable_notices' );
			} else {
				update_option( 'woocommerce_gzd_disable_notices', 'yes' );
			}

			// Redirect to check for updates
			wp_safe_redirect( admin_url( 'admin.php?page=wc-status&tab=germanized' ) );
		}
	}

	public function disable_small_business_options() {
		// Update woocommerce options to show tax
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
	}

	public function enable_small_business_options() {
		// Update woocommerce options to not show tax
		update_option( 'woocommerce_calc_taxes', 'no' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );
		update_option( 'woocommerce_tax_display_cart', 'incl' );
		update_option( 'woocommerce_price_display_suffix', '' );

		update_option( 'woocommerce_gzd_shipping_tax', 'no' );
		update_option( 'woocommerce_gzd_enable_virtual_vat', 'no' );
	}

	public function check_insert_vat_rates() {
		if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['insert-vat-rates'] ) && isset( $_GET['_wpnonce'] ) && check_admin_referer( 'wc-gzd-insert-vat-rates' ) ) {

			WC_GZD_Install::create_tax_rates();

			// Redirect to check for updates
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=tax&section=standard' ) );
		}
	}

	public function get_shipping_method_instances() {

		// Make sure we are not firing before init because otherwise some Woo errors might occur
		if ( ! did_action( 'init' ) ) {
			return array();
		}

		// WC_Shipping_Zone will try to call WC()->countries. Make sure that the object already exists.
		if ( ! isset( WC()->countries ) || ! is_a( WC()->countries, 'WC_Countries' ) ) {
			return array();
		}

		if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
			$instances = WC()->shipping->get_shipping_methods();
		} else {
			$zones     = WC_Shipping_Zones::get_zones();
			$worldwide = new WC_Shipping_Zone( 0 );

			$instances = $worldwide->get_shipping_methods( true );

			foreach ( $zones as $id => $zone ) {
				$zone      = new WC_Shipping_Zone( $id );
				$instances = $instances + $zone->get_shipping_methods( true );
			}
		}

		return $instances;
	}

	public function get_shipping_method_instances_options() {
		$methods                  = $this->get_shipping_method_instances();
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

	public function get_payment_gateway_options() {
		$gateways = WC()->payment_gateways->payment_gateways();
		$options  = array();

		if ( ! empty( $gateways ) ) {
			foreach ( $gateways as $gateway ) {
				$options[ $gateway->id ] = $gateway->title;
			}
		}

		return $options;
	}

	private function get_setting_key_by_id( $settings, $id, $type = '' ) {
		if ( ! empty( $settings ) ) {

			foreach ( $settings as $key => $value ) {

				if ( isset( $value['id'] ) && $value['id'] == $id ) {

					if ( ! empty( $type ) && $type !== $value['type'] ) {
						continue;
					}

					return $key;
				}
			}
		}

		return false;
	}

	public function remove_setting( $settings, $id ) {

		foreach ( $settings as $key => $value ) {
			if ( isset( $value['id'] ) && $id === $value['id'] ) {
				unset( $settings[ $key ] );
			}
		}

		return array_filter( $settings );

	}

	public function insert_setting_after( $settings, $id, $insert = array(), $type = '' ) {
		$key = $this->get_setting_key_by_id( $settings, $id, $type );
		if ( is_numeric( $key ) ) {
			$key ++;
			$settings = array_merge( array_merge( array_slice( $settings, 0, $key, true ), $insert ), array_slice( $settings, $key, count( $settings ) - 1, true ) );
		} else {
			$settings += $insert;
		}

		return $settings;
	}

}

WC_GZD_Admin::instance();