<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Checkboxes settings.
 *
 * @class 		WC_GZD_Settings_Tab_Checkboxes
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Tab_Checkboxes extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Legal checkboxes are being used to ask the customer for a certain permission or action (e.g. to accept terms & conditions) before the checkout or another form may be completed.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Legal Checkboxes', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'checkboxes';
	}

	public function get_current_section() {
		return $this->get_current_checkbox_id();
	}

	public function get_current_checkbox_id() {
		$checkbox_id  = isset( $_GET['checkbox_id'] ) && ! empty( $_GET['checkbox_id'] ) ? wc_clean( $_GET['checkbox_id'] ) : false;

		return $checkbox_id;
	}

	public function get_tab_settings( $current_section = '' ) {
		return array();
	}

	protected function get_section_description( $checkbox_id ) {
		$manager = WC_GZD_Legal_Checkbox_Manager::instance();

		if ( $checkbox = $manager->get_checkbox( $checkbox_id ) ) {
			return $checkbox->get_admin_desc();
		}

		return '';
	}

	public function get_section_title( $checkbox_id = '' ) {
		if ( ! empty( $checkbox_id ) ) {
			$manager  = WC_GZD_Legal_Checkbox_Manager::instance();
			$checkbox = $manager->get_checkbox( $checkbox_id );

			if ( $checkbox ) {
				return $checkbox->get_admin_name();
			}
		}

		return '';
	}

	protected function get_breadcrumb() {
		$breadcrumb          = parent::get_breadcrumb();
		$checkbox_id         = $this->get_current_checkbox_id();
		$new_checkbox_link   = apply_filters( 'woocommerce_gzd_admin_new_legal_checkbox_link', 'https://vendidero.de/woocommerce-germanized' );
		$new_checkbox_button = ' <a class="page-title-action" href="' . $new_checkbox_link . '" target="' . ( ! WC_germanized()->is_pro() ? '_blank' : '_self' ) . '">' . esc_html__( 'Add checkbox', 'woocommerce-germanized' ) . ' ' . ( ! WC_germanized()->is_pro() ? '<span class="wc-gzd-premium-section-tab">pro</span>' : '' ) . '</a>';

		if ( empty( $checkbox_id ) ) {
			$breadcrumb[ sizeof( $breadcrumb ) - 1 ]['title'] = $breadcrumb[ sizeof( $breadcrumb ) - 1 ]['title'] . $new_checkbox_button;
		}

		return $breadcrumb;
	}

	/**
	 * Handles output of the shipping zones page in admin.
	 */
	public function output() {
		global $hide_save_button;

		if ( isset( $_REQUEST['checkbox_id'] ) ) { // WPCS: input var ok, CSRF ok.
			$this->edit_screen( wc_clean( wp_unslash( $_REQUEST['checkbox_id'] ) ) ); // WPCS: input var ok, CSRF ok.
		} else {
			$hide_save_button = true;
			$this->screen();
		}
	}

	protected function edit_screen( $checkbox_id ) {
		$manager = WC_GZD_Legal_Checkbox_Manager::instance();
		$manager->do_register_action();

		$checkbox = $manager->get_checkbox( $checkbox_id );

		/**
		 * Adjust the checkbox within admin edit view.
		 *
		 * @since 2.0.0
		 *
		 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
		 * @param int                   $checkbox_id The checkbox id.
		 */
		$checkbox = apply_filters( 'woocommerce_gzd_admin_legal_checkbox', $checkbox, $checkbox_id );

		if ( ! empty( $_POST['save'] ) ) { // WPCS: input var ok, sanitization ok.

			if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'woocommerce-settings' ) ) { // WPCS: input var ok, sanitization ok.
				echo '<div class="updated error"><p>' . esc_html__( 'Edit failed. Please try again.', 'woocommerce-germanized' ) . '</p></div>';
			}

			/**
			 * Before saving a legal checkbox.
			 *
			 * This hook fires before a certain legal checkbox saves it's settings.
			 *
			 * @since 2.0.0
			 *
			 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox to be saved.
			 */
			do_action( 'woocommerce_gzd_before_save_legal_checkbox', $checkbox );

			if ( $checkbox ) {
				$checkbox->save_fields();

				/**
				 * After saving a legal checkbox
				 *
				 * This hook fires after a certain legal checkbox saves it's settings.
				 *
				 * @since 2.0.0
				 *
				 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox containing the new settings.
				 */
				do_action( 'woocommerce_gzd_after_save_legal_checkbox', $checkbox );
			}
		}

		if ( ! $checkbox && ! WC_germanized()->is_pro() ) {
			wp_die( __( 'Sorry, but this checkbox does not exist.', 'woocommerce-germanized' ) );
		}

		include_once dirname( __FILE__ ) . '/views/html-admin-page-checkbox.php';
	}

	protected function screen() {

		/**
		 * Before outputting admin checkboxes.
		 *
		 * This hook fires before legal checkboxes admin list view is being output.
		 *
		 * @since 2.0.0
		 */
		do_action( 'woocommerce_gzd_before_admin_legal_checkboxes' );

		$manager = WC_GZD_Legal_Checkbox_Manager::instance();
		$manager->do_register_action();

		$checkboxes = $manager->get_checkboxes( array(), 'json' );

		wp_localize_script(
			'wc-gzd-admin-legal-checkboxes', 'wc_gzd_legal_checkboxes_params', array(
				'checkboxes'              => $checkboxes,
				'checkboxes_nonce'        => wp_create_nonce( 'wc_gzd_legal_checkbox_nonce' ),
				'strings'                 => array(
					'unload_confirmation_msg'     => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce-germanized' ),
					'delete_confirmation_msg'     => __( 'Are you sure you want to delete this checkbox? This action cannot be undone.', 'woocommerce-germanized' ),
					'save_failed'                 => __( 'Your changes were not saved. Please retry.', 'woocommerce-germanized' ),
				),
			)
		);

		wp_enqueue_script( 'wc-gzd-admin-legal-checkboxes' );

		include_once dirname( __FILE__ ) . '/views/html-admin-page-checkboxes.php';
	}
}