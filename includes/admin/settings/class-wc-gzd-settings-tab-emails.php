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

	public function get_help_link() {
		return 'https://vendidero.de/doc/allgemein/e-mail-einstellungen';
	}

	public function get_section_help_link( $section ) {
		$links = array(
			'attachments' => 'https://vendidero.de/doc/allgemein/rechtstexte-hinterlegen-und-an-e-mails-anhaengen',
		);

		return array_key_exists( $section, $links ) ? $links[ $section ] : parent::get_section_help_link( $section );
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
		return $this->get_attachment_settings();
	}

	protected function get_attachment_settings() {
		return $this->get_pro_preview_settings(
			array(
				'id'           => 'email_attachments_pro_preview',
				'title'        => __( 'PDF email attachments with Germanized Pro', 'woocommerce-germanized' ),
				'desc'         => __( 'Attach automatically generated PDF files of your legal pages to emails instead of plain text.', 'woocommerce-germanized' ),
				'features'     => array(
					__( 'Generate PDF files from your legal pages automatically', 'woocommerce-germanized' ),
					__( 'Or upload your own, custom PDF files', 'woocommerce-germanized' ),
					__( 'Customize the layout of generated PDF files', 'woocommerce-germanized' ),
				),
				'href'         => 'https://vendidero.de/woocommerce-germanized/features#legal',
				'placeholders' => array(
					array(
						'title' => 'Terms & Conditions',
						'type'  => 'title',
					),
					array(
						'title' => 'Enable',
						'type'  => 'toggle',
						'value' => 'yes',
						'desc'  => 'Attach PDF instead of plain text.',
					),
					array(
						'title' => 'PDF',
						'type'  => 'select',
						'value' => 'Generate automatically',
					),
					array(
						'title' => 'Cancellation Policy',
						'type'  => 'title',
					),
					array(
						'title' => 'Enable',
						'type'  => 'toggle',
						'value' => 'yes',
						'desc'  => 'Attach PDF instead of plain text.',
					),
					array(
						'title' => 'PDF',
						'type'  => 'select',
						'value' => 'Generate automatically',
					),
					array(
						'title' => 'Privacy Policy',
						'type'  => 'title',
					),
					array(
						'title' => 'Enable',
						'type'  => 'toggle',
						'value' => 'no',
						'desc'  => 'Attach PDF instead of plain text.',
					),
					array(
						'title' => 'PDF',
						'type'  => 'select',
						'value' => 'Custom upload',
					),
				),
			)
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
				$email_ids = array( 'customer_processing_order' );
				break;
			case 'eu_guarantees':
				$email_ids = array( 'customer_processing_order' );
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
