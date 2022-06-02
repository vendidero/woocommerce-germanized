<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Checkboxes settings.
 *
 * @class        WC_GZD_Settings_Tab_Checkboxes
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_Checkboxes extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Ask your customers for a certain permission or action before a form may be submitted.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Legal Checkboxes', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'checkboxes';
	}

	public function get_pointers() {
		$current  = $this->get_current_section();
		$pointers = array();

		if ( false === $current ) {
			$pointers = array(
				'pointers' => array(
					'default' => array(
						'target'       => '.wc-gzd-legal-checkbox-rows tr:first td.wc-gzd-legal-checkbox-name a:first',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-checkboxes&checkbox_id=terms&tutorial=yes' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Edit checkbox', 'woocommerce-germanized' ) . '</h3><p>' . esc_html__( 'Legal checkboxes help you obtain consent from your customers. You might edit a checkbox\' label and other options by clicking on the link.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'top',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'terms' === $current ) {
			$pointers = array(
				'pointers' => array(
					'default' => array(
						'target'       => '#woocommerce_gzd_checkboxes_terms_label',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized' ),
						'last_step'    => true,
						'pro'          => true,
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Label', 'woocommerce-germanized' ) . '</h3><p>' . esc_html__( 'Adjust the label of your checkbox which will be shown within your shop (e.g. checkout). Use placeholders to add links to your legal pages.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'bottom',
								'align' => 'left',
							),
						),
					),
				),
			);
		}

		return $pointers;
	}

	public function get_current_section() {
		return $this->get_current_checkbox_id();
	}

	public function get_current_checkbox_id() {
		$checkbox_id = isset( $_GET['checkbox_id'] ) && ! empty( $_GET['checkbox_id'] ) ? wc_clean( wp_unslash( $_GET['checkbox_id'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

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

		if ( 'new' === $checkbox_id ) {
			return __( 'New checkbox', 'woocommerce-germanized' );
		} elseif ( ! empty( $checkbox_id ) ) {
			$manager  = WC_GZD_Legal_Checkbox_Manager::instance();
			$checkbox = $manager->get_checkbox( $checkbox_id );

			if ( $checkbox ) {
				return $checkbox->get_admin_name();
			}
		}

		return '';
	}

	protected function get_breadcrumb() {
		$breadcrumb  = parent::get_breadcrumb();
		$checkbox_id = $this->get_current_checkbox_id();

		/**
		 * Filter to adjust new legal checkbox link for free version.
		 *
		 * @param string $link Link to vendidero website.
		 *
		 * @since 3.0.0
		 *
		 */
		$new_checkbox_link   = apply_filters( 'woocommerce_gzd_admin_new_legal_checkbox_link', 'https://vendidero.de/woocommerce-germanized' );
		$new_checkbox_button = ' <a class="page-title-action" href="' . $new_checkbox_link . '" target="' . ( ! WC_germanized()->is_pro() ? '_blank' : '_self' ) . '">' . esc_html__( 'Add checkbox', 'woocommerce-germanized' ) . ' ' . ( ! WC_germanized()->is_pro() ? '<span class="wc-gzd-pro">pro</span>' : '' ) . '</a>';

		if ( empty( $checkbox_id ) ) {
			$breadcrumb[ count( $breadcrumb ) - 1 ]['title'] = $breadcrumb[ count( $breadcrumb ) - 1 ]['title'] . $new_checkbox_button;
		}

		return $breadcrumb;
	}

	/**
	 * Handles output of the shipping zones page in admin.
	 */
	public function output() {
		global $hide_save_button;

		if ( isset( $_REQUEST['checkbox_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->edit_screen( wc_clean( wp_unslash( $_REQUEST['checkbox_id'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
		 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox instance.
		 * @param int $checkbox_id The checkbox id.
		 *
		 * @since 2.0.0
		 *
		 */
		$checkbox = apply_filters( 'woocommerce_gzd_admin_legal_checkbox', $checkbox, $checkbox_id );

		if ( ! empty( $_POST['save'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'woocommerce-settings' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				echo '<div class="updated error"><p>' . esc_html__( 'Edit failed. Please try again.', 'woocommerce-germanized' ) . '</p></div>';
			}

			/**
			 * Before saving a legal checkbox.
			 *
			 * This hook fires before a certain legal checkbox saves it's settings.
			 *
			 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox to be saved.
			 *
			 * @since 2.0.0
			 *
			 */
			do_action( 'woocommerce_gzd_before_save_legal_checkbox', $checkbox );

			if ( $checkbox ) {
				$checkbox->save_fields();

				/**
				 * After saving a legal checkbox
				 *
				 * This hook fires after a certain legal checkbox saves it's settings.
				 *
				 * @param WC_GZD_Legal_Checkbox $checkbox The checkbox containing the new settings.
				 *
				 * @since 2.0.0
				 *
				 */
				do_action( 'woocommerce_gzd_after_save_legal_checkbox', $checkbox );
			}
		}

		if ( ! $checkbox && ! WC_germanized()->is_pro() ) {
			wp_die( esc_html__( 'Sorry, but this checkbox does not exist.', 'woocommerce-germanized' ) );
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
			'wc-gzd-admin-legal-checkboxes',
			'wc_gzd_legal_checkboxes_params',
			array(
				'checkboxes'       => $checkboxes,
				'checkboxes_nonce' => wp_create_nonce( 'wc_gzd_legal_checkbox_nonce' ),
				'strings'          => array(
					'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'woocommerce-germanized' ),
					'delete_confirmation_msg' => __( 'Are you sure you want to delete this checkbox? This action cannot be undone.', 'woocommerce-germanized' ),
					'save_failed'             => __( 'Your changes were not saved. Please retry.', 'woocommerce-germanized' ),
				),
			)
		);

		wp_enqueue_script( 'wc-gzd-admin-legal-checkboxes' );

		include_once dirname( __FILE__ ) . '/views/html-admin-page-checkboxes.php';
	}
}
