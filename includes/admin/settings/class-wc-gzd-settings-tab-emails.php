<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Email settings.
 *
 * @class        WC_GZD_Settings_Tab_Emails
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_Emails extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Adjust email related settings e.g. attach your legal page content to certain email templates.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Emails', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'emails';
	}

	public function get_sections() {
		return array(
			''            => __( 'General', 'woocommerce-germanized' ),
			'visibility'  => __( 'Visibility', 'woocommerce-germanized' ),
			'attachments' => __( 'PDF Attachments', 'woocommerce-germanized' ),
		);
	}

	protected function section_is_pro( $section_id ) {
		$is_pro = parent::section_is_pro( $section_id );

		if ( 'attachments' === $section_id ) {
			$is_pro = true;
		}

		return $is_pro;
	}

	public function get_pointers() {
		$current  = $this->get_current_section();
		$pointers = array();

		if ( '' === $current ) {
			$pointers = array(
				'pointers' => array(
					'default' => array(
						'target'       => '.ui-sortable td.forminp-multiselect:first .select2-container:nth-of-type(1)',
						'next'         => 'pdf',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Email attachments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html__( 'Choose which of your email templates (e.g. order confirmation) should contain your legal page content e.g. terms and conditions within it\'s footer.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'pdf'     => array(
						'target'       => '.subsubsub li:nth-of-type(3) a',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-checkboxes&tutorial=yes' ),
						'next_trigger' => array(),
						'pro'          => true,
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'PDF Attachments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html__( 'Customers of our pro version may attach PDF files instead of plain text content to emails.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'top',
								'align' => 'left',
							),
						),
					),
				),
			);
		}

		return $pointers;
	}

	public function get_section_description( $section ) {
		if ( '' === $section ) {
			return __( 'Use drag & drop to customize attachment order. Don\'t forget to save your changes.', 'woocommerce-germanized' );
		}

		return '';
	}

	protected function is_saveable() {
		$is_saveable     = parent::is_saveable();
		$current_section = $this->get_current_section();

		if ( in_array( $current_section, array( 'attachments', 'attachments_pdf' ), true ) && ! WC_germanized()->is_pro() ) {
			$is_saveable = false;
		}

		return $is_saveable;
	}

	public function get_tab_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = $this->get_general_settings();
		} elseif ( 'visibility' === $current_section ) {
			$settings = $this->get_visibility_settings();
		} elseif ( 'attachments' === $current_section ) {
			$settings = $this->get_attachment_settings();
		} elseif ( 'attachments_pdf' === $current_section ) {
			$settings = $this->get_attachment_pdf_settings();
		}

		return $settings;
	}

	protected function get_attachment_pdf_settings() {
		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'email_pdf_options',
				'desc'  => '<div class="notice inline notice-warning wc-gzd-premium-overlay"><p>' . sprintf( __( 'Want to attach automatically generated PDF files to emails instead of plain text? %1$sUpgrade to %2$spro%3$s%4$s', 'woocommerce-germanized' ), '<a style="margin-left: 1em" href="https://vendidero.de/woocommerce-germanized" class="button button-primary wc-gzd-button">', '<span class="wc-gzd-pro">', '</span>', '</a>' ) . '</p></div>',
			),

			array(
				'title' => '',
				'id'    => 'woocommerce_gzdp_legal_page_terms_enabled',
				'img'   => WC_Germanized()->plugin_url() . '/assets/images/pro/settings-pdf.png?v=' . WC_germanized()->version,
				'href'  => 'https://vendidero.de/woocommerce-germanized/features#legal',
				'type'  => 'image',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'email_pdf_options',
			),
		);
	}

	protected function get_attachment_settings() {
		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'email_attachment_options',
				'desc'  => '<div class="notice inline notice-warning wc-gzd-premium-overlay"><p>' . sprintf( __( 'Want to attach automatically generated PDF files to emails instead of plain text? %1$sUpgrade to %2$spro%3$s%4$s', 'woocommerce-germanized' ), '<a style="margin-left: 1em" href="https://vendidero.de/woocommerce-germanized" class="button button-primary wc-gzd-button">', '<span class="wc-gzd-pro">', '</span>', '</a>' ) . '</p></div>',
			),

			array(
				'title' => '',
				'id'    => 'woocommerce_gzdp_legal_page_terms_enabled',
				'img'   => WC_Germanized()->plugin_url() . '/assets/images/pro/settings-emails.png?v=' . WC_germanized()->version,
				'href'  => 'https://vendidero.de/woocommerce-germanized/features#legal',
				'type'  => 'image',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'email_attachment_options',
			),
		);
	}

	protected function get_visibility_settings() {
		$payment_gateway_options = WC_GZD_Admin::instance()->get_payment_gateway_options();

		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'desc'  => '',
				'id'    => 'visibility_options',
			),

			array(
				'title'   => __( 'Title', 'woocommerce-germanized' ),
				'desc'    => '<div class="wc-gzd-additional-desc">' . __( 'Adjust the title to be used within emails. Use {first_name}, {last_name} and {title} as placeholders.', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'woocommerce_gzd_email_title_text',
				'default' => __( 'Hi {first_name},', 'woocommerce-germanized' ),
				'type'    => 'text',
			),

			array(
				'title'   => __( 'Hide Username', 'woocommerce-germanized' ),
				'desc'    => __( 'Hide username from email content if password or password reset link is embedded.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . __( 'Trusted Shops advises to not show the username together with an account password or password reset link. This option hides (or masks) the username in those specific cases.', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'woocommerce_gzd_hide_username_with_password',
				'default' => 'yes',
				'type'    => 'gzd_toggle',
			),

			array(
				'title'    => __( 'Pay now Button', 'woocommerce-germanized' ),
				'desc'     => __( 'Add a pay now button to emails and order success page.', 'woocommerce-germanized' ),
				'desc_tip' => __( 'Add a pay now button to order confirmation email and order success page if the order awaits payment (PayPal etc).', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_order_pay_now_button',
				'type'     => 'gzd_toggle',
				'default'  => 'yes',
			),
			array(
				'title'             => __( 'Disabled for', 'woocommerce-germanized' ),
				'desc_tip'          => __( 'You may want to disable the pay now button for certain payment methods.', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_gzd_order_pay_now_button_disabled_methods',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_order_pay_now_button' => '',
				),
				'default'           => array(),
				'class'             => 'wc-enhanced-select',
				'options'           => $payment_gateway_options,
				'type'              => 'multiselect',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'visibility_options',
			),
		);
	}

	protected function get_default_email_ids_by_attachment_type( $type ) {
		$email_ids = array();

		switch ( $type ) {
			case 'revocation':
				$email_ids = array( 'customer_processing_order' );
				break;
			case 'warranties':
				$email_ids = array( 'customer_completed_order' );
				break;
			case 'data_security':
			case 'terms':
				$email_ids = array( 'customer_processing_order', 'customer_new_account', 'customer_new_account_activation' );
				break;
		}

		return $email_ids;
	}

	protected function get_general_settings() {
		$mailer          = WC()->mailer();
		$email_templates = $mailer->get_emails();
		$email_select    = array();

		foreach ( $email_templates as $email ) {
			$customer = false;

			if ( is_callable( array( $email, 'is_customer_email' ) ) ) {
				$customer = $email->is_customer_email();
			}

			$email_select[ $email->id ] = empty( $email->title ) ? ucfirst( $email->id ) : ucfirst( $email->title ) . ' (' . ( $customer ? __( 'Customer', 'woocommerce-germanized' ) : __( 'Admin', 'woocommerce-germanized' ) ) . ')';
		}

		$email_order = wc_gzd_get_email_attachment_order();
		$settings    = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'email_options',
			),

			array(
				'title'   => '',
				'id'      => 'woocommerce_gzd_mail_attach_order',
				'type'    => 'hidden',
				'default' => wc_gzd_get_default_email_attachment_order(),
			),
		);

		foreach ( $email_order as $key => $order ) {
			array_push(
				$settings,
				array(
					'title'    => sprintf( __( 'Attach %s', 'woocommerce-germanized' ), $order ),
					'desc'     => sprintf( __( 'Attach %s to the following email templates', 'woocommerce-germanized' ), $order ),
					'id'       => 'woocommerce_gzd_mail_attach_' . $key,
					'type'     => 'multiselect',
					'class'    => 'wc-enhanced-select',
					'default'  => $this->get_default_email_ids_by_attachment_type( $key ),
					'desc_tip' => true,
					'options'  => $email_select,
				)
			);
		}

		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'email_options',
		);

		return $settings;
	}
}
