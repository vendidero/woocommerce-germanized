<?php

defined( 'ABSPATH' ) || exit;

/**
 * Attaches legal relevant Pages to WooCommerce Emails if has been set by WooCommerce Germanized Options
 *
 * @class        WC_GZD_Emails
 * @version        1.0.0
 * @author        Vendidero
 */
class WC_GZD_Emails {

	/**
	 * Contains options and page ids
	 * @var array
	 */
	private $footer_attachments = array();

	/**
	 * Contains WC_Emails instance after init
	 * @var WC_Emails
	 */
	private $mailer = null;

	/**
	 * @var bool|WC_Email
	 */
	private $current_email_instance = false;

	/**
	 * Adds legal page ids to different options and adds a hook to the email footer
	 */
	public function __construct() {
		$this->set_footer_attachments();

		/**
		 * Late-removal of email notification hooks based on order data to allow conditionally sending or not sending
		 * the instant order confirmation.
		 */
		add_action( 'woocommerce_before_order_object_save', array( $this, 'maybe_prevent_confirmation_email_sending' ), 1 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'maybe_restore_confirmation_email_sending' ), 1000, 4 );
		add_filter( 'woocommerce_allow_send_queued_transactional_email', array( $this, 'maybe_prevent_queued_confirmation_email_sending' ), 10, 3 );

		add_action( 'woocommerce_email', array( $this, 'email_hooks' ), 0, 1 );
		add_filter( 'wc_get_template', array( $this, 'maybe_set_current_email_instance' ), 1000, 3 );
		add_filter( 'woocommerce_email_actions', array( $this, 'register_custom_email_actions' ), 10 );

		if ( wc_gzd_send_instant_order_confirmation() ) {
			/**
			 * Support WooCommerce Gutenberg checkout block
			 */
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '6.4.0', '>=' ) ) {
				add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'confirm_order' ) );
			} elseif ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '6.0.0', '>=' ) ) {
				add_action( 'woocommerce_blocks_checkout_order_processed', array( $this, 'confirm_order' ) );
			} else {
				add_action( '__experimental_woocommerce_blocks_checkout_order_processed', array( $this, 'confirm_order' ) );
			}

			/**
			 * Register a custom shutdown handler to make sure the order is being confirmed even though the
			 * woocommerce_payment_successful_result or woocommerce_checkout_no_payment_needed_redirect hooks
			 * are not executed (e.g. custom gateway logic).
			 */
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'register_confirmation_fallback' ), 30 );

			// Send order notice directly after new order is being added - use these filters because order status has to be updated already
			add_filter(
				'woocommerce_payment_successful_result',
				array(
					$this,
					'send_order_confirmation_mails',
				),
				0,
				2
			);
			add_filter(
				'woocommerce_checkout_no_payment_needed_redirect',
				array(
					$this,
					'send_order_confirmation_mails',
				),
				0,
				2
			);
		}

		// Register the woocommerce_gzd_order_confirmation action which will be used as a notification to send the confirmation
		add_filter(
			'woocommerce_email_actions',
			array(
				$this,
				'register_order_confirmation_email_action',
			),
			10,
			1
		);

		// Make sure order confirmation is being sent as soon as the notification fires
		add_action(
			'woocommerce_gzd_order_confirmation_notification',
			array(
				$this,
				'trigger_order_confirmation_emails',
			),
			10,
			1
		);

		// Disable paid order email for certain gateways (e.g. COD or invoice)
		add_filter(
			'woocommerce_allow_send_queued_transactional_email',
			array(
				$this,
				'maybe_disable_order_paid_email_notification_queued',
			),
			10,
			3
		);

		add_action(
			'woocommerce_order_status_processing',
			array(
				$this,
				'maybe_disable_order_paid_email_notification',
			),
			5,
			2
		);

		// Change email template path if is germanized email template
		add_filter( 'woocommerce_template_directory', array( $this, 'set_woocommerce_template_dir' ), 10, 2 );

		// Map partially refunded order mail template to correct email instance
		add_filter(
			'woocommerce_gzd_email_template_id_comparison',
			array(
				$this,
				'check_for_partial_refund_mail',
			),
			10,
			3
		);

		// Filter customer-processing-order Woo 3.5 payment text
		add_filter( 'woocommerce_before_template_part', array( $this, 'maybe_set_gettext_email_filter' ), 10, 4 );

		// Make sure confirmation emails are not being resent on order-pay
		add_action( 'woocommerce_before_pay_action', array( $this, 'disable_pay_order_confirmation' ), 10, 1 );

		// Hide username if an email contains a password or password reset link (TS advises to do so)
		if ( 'yes' === get_option( 'woocommerce_gzd_hide_username_with_password' ) ) {
			add_filter(
				'woocommerce_before_template_part',
				array(
					$this,
					'maybe_set_gettext_username_filter',
				),
				10,
				4
			);
		}

		add_action( 'woocommerce_email', array( $this, 'init_bcc_field' ), 10, 1 );
		add_filter( 'woocommerce_email_headers', array( $this, 'add_bcc_email_headers' ), 10, 4 );
		add_filter( 'woocommerce_email_footer_text', array( $this, 'reset_email_instance' ), 1000 );

		add_filter( 'woocommerce_email_attachments', array( $this, 'attach_product_warranties' ), 100, 3 );

		add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'send_manual_order_confirmation' ), 10 );
		add_action( 'woocommerce_order_status_pending_to_completed_notification', array( $this, 'send_manual_order_confirmation' ), 10 );
		add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this, 'send_manual_order_confirmation' ), 10 );

		if ( is_admin() ) {
			$this->admin_hooks();
		}
	}

	public function register_confirmation_fallback( $order_id ) {
		register_shutdown_function( array( $this, 'after_order_shutdown' ), $order_id );
	}

	public function after_order_shutdown( $order_id ) {
		if ( ! did_action( 'woocommerce_germanized_order_confirmation_sent' ) ) {
			$this->confirm_order( $order_id );
		}
	}

	public function get_email_plain_content( $content_post ) {
		$shortcodes_allowed = apply_filters( 'woocommerce_gzd_email_attachment_content_shortcodes_allowed', array( 'revocation_form', 'gzd_complaints', 'payment_methods_info' ) );
		$content            = '';
		$post               = is_numeric( $content_post ) ? get_post( $content_post ) : $content_post;

		if ( is_a( $post, 'WP_Post' ) ) {
			remove_shortcode( 'revocation_form' );
			add_shortcode( 'revocation_form', array( $this, 'revocation_form_replacement' ) );

			if ( ! get_post_meta( $post->ID, '_legal_text', true ) ) {
				$content = wc_gzd_get_post_plain_content( $post, $shortcodes_allowed );
			} else {
				/**
				 * Hook that executes before retrieving legal page content from the optional
				 * meta field `_legal_text` that may be used to use a differing text as legal attachment.
				 *
				 * @param WP_Post $post
				 *
				 * @since 3.12.0
				 */
				do_action( 'woocommerce_gzd_before_get_email_meta_plain_content', $post );

				/**
				 * Filter that allows disabling the `the_content` filter for optional legal page content.
				 *
				 * @param bool $enable Enable or disable the `the_content` filter.
				 * @param string $content The content.
				 */
				$apply_content_filters = apply_filters( 'woocommerce_gzd_apply_optional_content_filter_email_attachment', true, $content, $post );
				$plain_content         = htmlspecialchars_decode( get_post_meta( $post->ID, '_legal_text', true ) );
				$content               = $apply_content_filters ? apply_filters( 'the_content', $plain_content ) : $plain_content;
				$content               = str_replace( ']]>', ']]&gt;', $content );

				/**
				 * Hook that executes after retrieving legal page content from the optional
				 * meta field `_legal_text` that may be used to use a differing text as legal attachment.
				 *
				 * @param WP_Post $post
				 *
				 * @since 3.12.0
				 */
				do_action( 'woocommerce_gzd_before_get_email_meta_plain_content', $post );
			}

			add_shortcode( 'revocation_form', 'WC_GZD_Shortcodes::revocation_form' );
		}

		return apply_filters( 'woocommerce_gzd_email_plain_content', $content );
	}

	public function attach_product_warranties( $attachments, $mail_id, $object = false ) {
		$warranty_email_ids = array_filter( (array) get_option( 'woocommerce_gzd_mail_attach_warranties', array() ) );

		if ( $object && in_array( $mail_id, $warranty_email_ids, true ) ) {
			$product_ids = array();

			if ( is_a( $object, 'WC_Order' ) ) {
				foreach ( $object->get_items( 'line_item' ) as $item ) {
					$product_ids[] = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
				}
			} elseif ( is_a( $object, '\Vendidero\Germanized\Shipments\Shipment' ) ) {
				foreach ( $object->get_items() as $item ) {
					$product_ids[] = $item->get_product_id();
				}
			}

			$product_ids = apply_filters( 'woocommerce_gzd_product_warranties_email_product_ids', $product_ids, $object, $mail_id );
			$product_ids = array_filter( array_unique( $product_ids ) );

			if ( ! empty( $product_ids ) ) {
				foreach ( $product_ids as $product_id ) {
					if ( $gzd_product = wc_gzd_get_gzd_product( $product_id ) ) {
						if ( $gzd_product->has_warranty() ) {
							if ( ! in_array( $gzd_product->get_warranty_file(), $attachments, true ) ) {
								$attachments[] = $gzd_product->get_warranty_file();
							}
						}
					}
				}
			}
		}

		return $attachments;
	}

	public function register_custom_email_actions( $actions ) {
		$actions = array_merge(
			$actions,
			array(
				'woocommerce_order_status_pending_to_cancelled',
			)
		);

		return $actions;
	}

	public function prevent_html_url_auto_link( $url ) {
		$url = str_replace( array( '.', ':', 'http' ), array( '<span>.</span>', '<span>:</span>', 'ht<span>tp</span>' ), $url );

		return $url;
	}

	public function reset_email_instance( $text ) {
		$this->current_email_instance = false;

		do_action( 'woocommerce_gzd_reset_email_instance', $this );

		return $text;
	}

	/**
	 * On including a template (e.g. emails/customer-processing-order.php)
	 * Woo adds arguments to the template. In this method we do check whether the arguments
	 * contain an email (which indicates that an email template is included and send). We will then
	 * set the global email instance to the included email instance and use that to determine whether to add certain
	 * legal texts to it or not.
	 *
	 * @param $template
	 * @param $template_name
	 * @param $args
	 *
	 * @return mixed
	 */
	public function maybe_set_current_email_instance( $template, $template_name, $args ) {
		if ( isset( $args['email'] ) && is_a( $args['email'], 'WC_Email' ) ) {
			$this->current_email_instance = $args['email'];
		}

		return $template;
	}

	/**
	 * Returns the current email instance (if available).
	 *
	 * @return bool|WC_Email
	 */
	public function get_current_email_instance() {
		/**
		 * Filters the current email instance (e.g. while sending an email) determined by Germanized.
		 * This instance is being used to check whether to attach legal texts to this email or not.
		 *
		 * @param bool|WC_Email $email The current email instance.
		 * @param WC_GZD_Emails $email_helper The email helper instance.
		 *
		 * @since 3.2.2
		 */
		return apply_filters( 'woocommerce_gzd_current_email_instance', $this->current_email_instance, $this );
	}

	/**
	 * Add BCC headers depending on the current email template.
	 * Make the last parameter option to potentially support Woo < 3.9.
	 *
	 * @param $headers
	 * @param $id
	 * @param $object
	 * @param null $email
	 *
	 * @return string
	 */
	public function add_bcc_email_headers( $headers, $id, $object, $email = null ) {
		if ( $email && is_a( $email, 'WC_Email' ) ) {
			$recipients = $email->get_option( 'bcc' );

			if ( $recipients && ! empty( $recipients ) ) {
				$recipients = array_map( 'trim', explode( ',', $recipients ) );
				$recipients = array_filter( $recipients, 'is_email' );

				if ( ! empty( $recipients ) ) {
					$headers .= 'Bcc: ' . trim( implode( ',', $recipients ) ) . "\r\n";
				}
			}
		}

		return $headers;
	}

	public function init_bcc_field( $mailer ) {
		$mails = $mailer->get_emails();

		foreach ( $mails as $mail ) {
			$mail->form_fields['bcc'] = array(
				'title'       => __( 'BCC recipients', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'Enter blind-copy recipients (comma separated) for this email.', 'woocommerce-germanized' ),
				'placeholder' => '',
				'default'     => '',
			);
		}
	}

	public function pay_for_order_request_needs_confirmation( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return false;
		}

		$needs_confirmation = true;

		/**
		 * If the order has been created by the customer (e.g. via checkout) or new order notification has been sent disable another order confirmation.
		 */
		if ( 'checkout' === $order->get_created_via() || $order->get_meta( '_new_order_email_sent' ) ) {
			$needs_confirmation = false;
		}

		/**
		 * Filter to decide whether a pay for order request needs email confirmation or not.
		 *
		 * @param boolean  $needs_confirmation Needs confirmation or not.
		 * @param WC_Order $order The order instance
		 *
		 * @since 3.5.3
		 */
		return apply_filters( 'woocommerce_gzd_pay_for_order_request_needs_confirmation', $needs_confirmation, $order );
	}

	/**
	 * @param WC_Order $order
	 */
	public function disable_pay_order_confirmation( $order ) {
		/**
		 * If the order has been created by the customer (e.g. via checkout) disable another order confirmation.
		 */
		if ( ! $this->pay_for_order_request_needs_confirmation( $order ) ) {
			remove_filter( 'woocommerce_payment_successful_result', array( $this, 'send_order_confirmation_mails' ), 0 );
			remove_filter( 'woocommerce_checkout_no_payment_needed_redirect', array( $this, 'send_order_confirmation_mails' ), 0 );
		}
	}

	public function save_confirmation_text_option() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( isset( $_POST['woocommerce_gzd_email_order_confirmation_text'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_option( 'woocommerce_gzd_email_order_confirmation_text', sanitize_textarea_field( wp_unslash( $_POST['woocommerce_gzd_email_order_confirmation_text'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	public function confirmation_text_option( $object ) {
		if ( 'customer_processing_order' === $object->id ) {

			/**
			 * Filter order confirmation text option field.
			 *
			 * @param array $args Text option arguments.
			 *
			 * @since 1.0.0
			 *
			 */
			$args = apply_filters(
				'woocommerce_gzd_admin_email_order_confirmation_text_option',
				array(
					'id'                => 'woocommerce_gzd_email_order_confirmation_text',
					'label'             => __( 'Confirmation text', 'woocommerce-germanized' ),
					'placeholder'       => __( 'Your order has been received and is now being processed. Your order details are shown below for your reference:', 'woocommerce-germanized' ),
					'desc'              => __( 'This text will be inserted within the order confirmation email. Use {order_number}, {site_title} or {order_date} as placeholder.', 'woocommerce-germanized' ),
					'custom_attributes' => array(),
					'value'             => get_option( 'woocommerce_gzd_email_order_confirmation_text' ),
				)
			);

			include_once WC_GERMANIZED_ABSPATH . 'includes/admin/views/html-admin-email-text-option.php';
		}
	}

	public function register_order_confirmation_email_action( $actions ) {
		$actions[] = 'woocommerce_gzd_order_confirmation';

		return $actions;
	}

	public function maybe_set_gettext_email_filter( $template_name, $template_path, $located, $args ) {

		if ( 'emails/customer-processing-order.php' === $template_name || 'emails/plain/customer-processing-order.php' === $template_name ) {
			if ( isset( $args['order'] ) ) {
				$GLOBALS['wc_gzd_processing_order'] = $args['order'];

				add_filter( 'gettext', array( $this, 'replace_processing_email_text' ), 9999, 3 );
			}
		}

		/**
		 * By hooking into this filter you might prevent Germanized from replacing titles (e.g. Dear Dennis)
		 * in WooCommerce email templates.
		 *
		 * ```php
		 * add_filter( 'woocommerce_gzd_replace_email_titles', '__return_false', 10 );
		 * ```
		 *
		 * @param bool $disable Whether to disable email title replacement or not.
		 *
		 * @since 3.0.0
		 *
		 */
		if ( strpos( $template_name, 'emails/' ) !== false && isset( $args['order'] ) && get_option( 'woocommerce_gzd_email_title_text' ) && apply_filters( 'woocommerce_gzd_replace_email_titles', true ) ) {
			$GLOBALS['wc_gzd_email_order'] = $args['order'];

			add_filter( 'gettext', array( $this, 'replace_title_email_text' ), 9999, 3 );
		}
	}

	public function replace_processing_email_text( $translated, $original, $domain ) {
		if ( 'woocommerce' === $domain ) {
			$search = array(
				'Just to let you know &mdash; we\'ve received your order #%s, and it is now being processed:',
				'Just to let you know &mdash; your payment has been confirmed, and order #%s is now being processed:',
				'Your order has been received and is now being processed. Your order details are shown below for your reference:',
			);

			if ( in_array( $original, $search, true ) ) {
				if ( isset( $GLOBALS['wc_gzd_processing_order'] ) ) {
					$order = $GLOBALS['wc_gzd_processing_order'];

					return $this->get_processing_email_text( $order );
				}
			}
		}

		return $translated;
	}

	public function replace_title_email_text( $translated, $original, $domain ) {
		if ( 'woocommerce' === $domain ) {
			if ( 'Hi %s,' === $original ) {
				if ( isset( $GLOBALS['wc_gzd_email_order'] ) ) {
					$order         = $GLOBALS['wc_gzd_email_order'];
					$title_text    = get_option( 'woocommerce_gzd_email_title_text' );
					$title_options = array(
						'{first_name}' => $order->get_billing_first_name(),
						'{last_name}'  => $order->get_billing_last_name(),
						'{title}'      => wc_gzd_get_order_customer_title( $order, 'billing' ),
					);

					$title_text = str_replace( array_keys( $title_options ), array_values( $title_options ), $title_text );

					/**
					 * Filter the email title option used to address the customer in emails.
					 *
					 * @param string $title The title.
					 * @param WC_Order $order The order object.
					 *
					 * @since 2.0.0
					 *
					 */
					return apply_filters( 'woocommerce_gzd_email_title', esc_html( $title_text ), $order );
				}
			}
		}

		return $translated;
	}

	protected function get_processing_email_text( $order_id ) {
		$order = is_numeric( $order_id ) ? wc_get_order( $order_id ) : $order_id;

		/**
		 * Filters the plain order confirmation email text.
		 *
		 * @param string $text The plain text.
		 *
		 * @since 1.0.0
		 *
		 */
		$plain = apply_filters( 'woocommerce_gzd_order_confirmation_email_plain_text', get_option( 'woocommerce_gzd_email_order_confirmation_text' ) );

		if ( ! $plain || '' === $plain ) {

			/**
			 * Filter the fallback order confirmation email text.
			 *
			 * @param string $text The default text.
			 *
			 * @since 1.0.0
			 *
			 */
			$plain = apply_filters( 'woocommerce_gzd_order_confirmation_email_default_text', __( 'Your order has been received and is now being processed. Your order details are shown below for your reference.', 'woocommerce-germanized' ) );
		}

		$placeholders = array(
			'{site_title}'   => wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ),
			'{order_number}' => $order->get_order_number(),
			'{order_date}'   => wc_gzd_get_order_date( $order ),
		);

		$plain = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $plain );

		/**
		 * Filter the order confirmation introduction text.
		 *
		 * @param string $plain The text.
		 * @param WC_Order $order The order object.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_order_confirmation_email_text', $plain, $order );
	}

	public function maybe_set_gettext_username_filter( $template_name, $template_path, $located, $args ) {

		$templates = array(
			'emails/customer-reset-password.php'       => 'maybe_hide_username_password_reset',
			'emails/plain/customer-reset-password.php' => 'maybe_hide_username_password_reset',
		);

		// If the password is generated automatically and sent by email, hide the username
		if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) ) {
			$templates = array_merge(
				$templates,
				array(
					'emails/customer-new-account.php' => 'maybe_hide_username_new_account',
					'emails/plain/customer-new-account.php' => 'maybe_hide_username_new_account',
				)
			);
		}

		if ( isset( $templates[ $template_name ] ) ) {
			add_filter( 'gettext', array( $this, $templates[ $template_name ] ), 10, 3 );
		}
	}

	public function maybe_hide_username_password_reset( $translated, $original, $domain ) {
		if ( 'woocommerce' === $domain ) {
			if ( 'Someone requested that the password be reset for the following account:' === $original ) {
				return __( 'Someone requested a password reset for your account.', 'woocommerce-germanized' );
			} elseif ( 'Username: %s' === $original ) {
				remove_filter( 'gettext', array( $this, 'maybe_hide_username_password_reset' ), 10 );

				return '';
			}
		}

		return $translated;
	}

	public function maybe_hide_username_new_account( $translated, $original, $domain ) {
		if ( 'woocommerce' === $domain && 'Thanks for creating an account on %s. Your username is <strong>%s</strong>' === $original ) {
			remove_filter( 'gettext', array( $this, 'maybe_hide_username_new_account' ), 10 );

			return __( 'Thanks for creating an account on %s.', 'woocommerce-germanized' );
		}

		return $translated;
	}

	public function check_for_partial_refund_mail( $result, $mail_id, $tpl ) {
		if ( 'customer_partially_refunded_order' === $mail_id && 'customer_refunded_order' === $tpl ) {
			return true;
		}

		return $result;
	}

	private function set_mailer( $mailer = null ) {
		if ( $mailer ) {
			$this->mailer = $mailer;
		} else {
			$this->mailer = WC()->mailer();
		}
	}

	private function set_footer_attachments() {
		// Order attachments
		$attachment_order         = wc_gzd_get_email_attachment_order( true );
		$this->footer_attachments = array();

		foreach ( $attachment_order as $key => $order ) {
			$this->footer_attachments[ 'woocommerce_gzd_mail_attach_' . $key ] = $key;
		}
	}

	public function admin_hooks() {
		add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'resend_order_emails' ), 0 );
		add_action( 'woocommerce_email_settings_after', array( $this, 'confirmation_text_option' ), 10, 1 );
		add_action(
			'woocommerce_update_options_email_customer_processing_order',
			array(
				$this,
				'save_confirmation_text_option',
			)
		);
	}

	public function send_manual_order_confirmation( $order_id ) {
		if ( ! wc_gzd_send_instant_order_confirmation( $order_id ) ) {
			return;
		}

		if ( $order = wc_get_order( $order_id ) ) {
			if ( 'admin' === $order->get_created_via() && apply_filters( 'woocommerce_gzd_send_order_confirmation_for_manual_order', true, $order_id ) ) {
				$this->confirm_order( $order );
			} elseif ( 'rest-api' === $order->get_created_via() && apply_filters( 'woocommerce_gzd_send_order_confirmation_for_rest_api_orders', true, $order_id ) ) {
				$this->confirm_order( $order );
			}
		}
	}

	public function email_hooks( $mailer ) {
		$this->set_mailer( $mailer );

		/**
		 * Use 5 as a priority to hook before global WooCommerce email footer (10)
		 */
		add_action( 'woocommerce_email_footer', array( $this, 'add_template_footers' ), 5 );

		/**
		 * The plain email templates do only include a filter to display a footer text
		 */
		add_filter( 'woocommerce_email_footer_text', array( $this, 'email_footer_plain' ), 0 );
		add_filter( 'woocommerce_email_styles', array( $this, 'styles' ) );

		add_action( 'woocommerce_email_order_meta', array( $this, 'small_business_notice' ), 1 );

		$mails = $this->mailer->get_emails();

		if ( ! empty( $mails ) ) {
			foreach ( $mails as $mail ) {
				add_action(
					'woocommerce_germanized_email_footer_' . $mail->id,
					array(
						$this,
						'hook_mail_footer',
					),
					10,
					1
				);
			}
		}

		// Set email filters
		add_action( 'woocommerce_email_order_details', array( $this, 'set_order_email_filters' ), 5, 4 );

		// Remove them after total has been displayed
		add_action( 'woocommerce_email_after_order_table', array( $this, 'remove_order_email_filters' ), 10 );

		// Pay now button
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_pay_now_button' ), 0, 1 );

		// Email notices right beneath order table
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_notices' ), 5, 3 );
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return void
	 */
	public function maybe_prevent_confirmation_email_sending( $order ) {
		if ( wc_gzd_send_instant_order_confirmation( $order ) ) {
			$this->prevent_confirmation_email_sending();
		}
	}

	/**
	 * @param integer $order_id
	 *
	 * @return void
	 */
	public function maybe_restore_confirmation_email_sending( $order_id, $from, $to, $order ) {
		if ( wc_gzd_send_instant_order_confirmation( $order ) ) {
			$this->restore_confirmation_email_sending();
		}
	}

	/**
	 * Add small business global Email Footer
	 */
	public function small_business_notice() {
		if ( wc_gzd_is_small_business() ) {
			wc_get_template( 'global/small-business-info.php' );
		}
	}

	public function get_gateways_disabling_paid_for_order_mail() {
		/**
		 * Filters disabled gateway for the paid for order notification.
		 * By adjusting the filter you may deactivate the paid for order notification for certain gateways.
		 *
		 * @param array $gateways Array of gateway ids.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_disable_gateways_paid_order_email', array( 'cod', 'invoice' ) );
	}

	/**
	 * Tweak to prevent Woo from sending confirmation emails for queued email tasks.
	 * We are using this filter to allow late-checking whether the instant order confirmation is enabled
	 * for this order.
	 *
	 * @param boolean $send
	 * @param $filter
	 * @param $args
	 *
	 * @return boolean
	 */
	public function maybe_prevent_queued_confirmation_email_sending( $send, $filter, $args ) {
		if ( isset( $args[0] ) && is_numeric( $args[0] ) ) {
			if ( $order = wc_get_order( absint( $args[0] ) ) ) {
				if ( wc_gzd_send_instant_order_confirmation( $order ) ) {
					$this->prevent_confirmation_email_sending();
				}
			}
		}

		return $send;
	}

	public function maybe_disable_order_paid_email_notification_queued( $send, $filter, $args ) {
		if ( isset( $args[0] ) && is_numeric( $args[0] ) ) {
			if ( $order = wc_get_order( absint( $args[0] ) ) ) {
				$this->maybe_prevent_order_paid_email_notification( $args[0] );
			}
		}

		return $send;
	}

	public function maybe_disable_order_paid_email_notification( $order_id, $order = false ) {
		$this->maybe_prevent_order_paid_email_notification( $order_id );
	}

	protected function maybe_prevent_order_paid_email_notification( $order_id ) {
		if ( $order = wc_get_order( $order_id ) ) {
			if ( is_callable( array( $order, 'get_payment_method' ) ) ) {
				$method               = $order->get_payment_method();
				$disable_for_gateways = $this->get_gateways_disabling_paid_for_order_mail();
				$disable_notification = ( in_array( $method, $disable_for_gateways, true ) || $order->get_total() <= 0 ) ? true : false;

				/**
				 * Filter to adjust whether to disable the paid for order notification based on order data.
				 *
				 * @param bool    $disable Whether to disable notification or not.
				 * @param integer $order_id The order id.
				 *
				 * @since 3.2.3
				 */
				if ( apply_filters( 'woocommerce_gzd_disable_paid_for_order_notification', $disable_notification, $order_id ) ) {
					$emails = WC()->mailer()->emails;

					if ( isset( $emails['WC_GZD_Email_Customer_Paid_For_Order'] ) ) {
						// Remove notification
						remove_action(
							'woocommerce_order_status_pending_to_processing_notification',
							array(
								$emails['WC_GZD_Email_Customer_Paid_For_Order'],
								'trigger',
							),
							30
						);
					}
				}
			}
		}
	}

	public function resend_order_emails( $emails ) {
		global $theorder;

		if ( is_null( $theorder ) ) {
			return $emails;
		}

		array_push( $emails, 'customer_paid_for_order' );

		return $emails;
	}

	public function set_woocommerce_template_dir( $dir, $template ) {
		if ( file_exists( WC_germanized()->plugin_path() . '/templates/' . $template ) ) {
			return 'woocommerce-germanized';
		}

		return $dir;
	}

	private function get_confirmation_email_transaction_statuses() {
		return array(
			'woocommerce_order_status_pending_to_processing',
			'woocommerce_order_status_pending_to_completed',
			'woocommerce_order_status_pending_to_on-hold',
			'woocommerce_order_status_on-hold_to_processing',
			'woocommerce_order_status_failed_to_processing',
			'woocommerce_order_status_cancelled_to_processing',
			'woocommerce_order_status_cancelled_to_on-hold',
			'woocommerce_order_status_failed_to_on-hold',
		);
	}

	public function prevent_confirmation_email_sending() {
		foreach ( $this->get_confirmation_email_transaction_statuses() as $status ) {
			remove_action(
				$status . '_notification',
				array(
					$this->get_email_instance_by_id( 'customer_processing_order' ),
					'trigger',
				)
			);

			remove_action(
				$status . '_notification',
				array(
					$this->get_email_instance_by_id( 'new_order' ),
					'trigger',
				)
			);

			if ( $this->get_email_instance_by_id( 'customer_on_hold_order' ) ) {
				remove_action(
					$status . '_notification',
					array(
						$this->get_email_instance_by_id( 'customer_on_hold_order' ),
						'trigger',
					)
				);
			}
		}
	}

	public function restore_confirmation_email_sending() {
		foreach ( array(
			'woocommerce_order_status_cancelled_to_processing',
			'woocommerce_order_status_failed_to_processing',
			'woocommerce_order_status_on-hold_to_processing',
			'woocommerce_order_status_pending_to_processing',
		) as $status ) {
			if ( ! has_action( $status . '_notification', array( $this->get_email_instance_by_id( 'customer_processing_order' ), 'trigger' ) ) ) {
				add_action(
					$status . '_notification',
					array(
						$this->get_email_instance_by_id( 'customer_processing_order' ),
						'trigger',
					)
				);
			}
		}

		foreach ( array(
			'woocommerce_order_status_pending_to_on-hold',
			'woocommerce_order_status_failed_to_on-hold',
			'woocommerce_order_status_cancelled_to_on-hold',
		) as $status ) {
			if ( $this->get_email_instance_by_id( 'customer_on_hold_order' ) ) {
				if ( ! has_action( $status . '_notification', array( $this->get_email_instance_by_id( 'customer_on_hold_order' ), 'trigger' ) ) ) {
					add_action(
						$status . '_notification',
						array(
							$this->get_email_instance_by_id( 'customer_on_hold_order' ),
							'trigger',
						)
					);
				}
			}
		}

		foreach ( array(
			'woocommerce_order_status_pending_to_processing',
			'woocommerce_order_status_pending_to_completed',
			'woocommerce_order_status_pending_to_on-hold',
			'woocommerce_order_status_failed_to_processing',
			'woocommerce_order_status_failed_to_completed',
			'woocommerce_order_status_failed_to_on-hold',
			'woocommerce_order_status_cancelled_to_processing',
			'woocommerce_order_status_cancelled_to_completed',
			'woocommerce_order_status_cancelled_to_on-hold',
		) as $status ) {
			if ( ! has_action( $status . '_notification', array( $this->get_email_instance_by_id( 'new_order' ), 'trigger' ) ) ) {
				add_action(
					$status . '_notification',
					array(
						$this->get_email_instance_by_id( 'new_order' ),
						'trigger',
					)
				);
			}
		}
	}

	/**
	 * @param WC_Order $order
	 */
	public function confirm_order( $order ) {
		if ( ! is_object( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return;
		}

		/**
		 * Triggers after WooCommerce has processed the order via checkout and payment gateway has been processed.
		 *
		 * This hook may be used to find a uniform way to process orders after the payment method has been triggered.
		 *
		 * @param WC_Order $order The order object.
		 *
		 * @since 3.1.6
		 */
		do_action( 'woocommerce_gzd_checkout_order_before_confirmation', $order );

		/**
		 * Last chance to force disabling the order confirmation for a certain order object.
		 *
		 * @param bool $disable Whether to disable notification or not.
		 * @param WC_Order $order The order object.
		 *
		 * @since 1.0.0
		 */
		if ( ! apply_filters( 'woocommerce_germanized_send_instant_order_confirmation', wc_gzd_send_instant_order_confirmation( $order ), $order ) ) {
			return;
		}

		/**
		 * Trigger the order confirmation email.
		 *
		 * This action triggers the order confirmation email notification.
		 *
		 * @param WC_Order $order The order object.
		 *
		 * @since 1.9.10
		 *
		 */
		do_action( 'woocommerce_gzd_order_confirmation', $order );

		if ( get_option( 'woocommerce_gzd_checkout_stop_order_cancellation' ) === 'yes' && WC()->cart ) {

			/**
			 * Decide whether to clear the cart after sending the order confirmation email or not.
			 * By default the cart is not cleared to prevent compatibility issues with payment providers
			 * like Stripe or Klarna which depend on cart data.
			 *
			 * @param boolean  $clear Whether to clear cart or not.
			 * @param WC_Order $order_id The order.
			 *
			 * @since 3.1.2
			 */
			if ( apply_filters( 'woocommerce_gzd_clear_cart_after_order_confirmation', false, $order ) ) {
				WC()->cart->empty_cart();
			}
		}
	}

	/**
	 * Send order confirmation mail directly after order is being sent
	 *
	 * @param mixed $return
	 * @param mixed $order
	 */
	public function send_order_confirmation_mails( $result, $order ) {
		$this->confirm_order( $order );

		return $result;
	}

	public function trigger_order_confirmation_emails( $order ) {
		$order_id = $order->get_id();

		/**
		 * Before order confirmation emails.
		 *
		 * Fires before the order confirmation emails are being triggered (admin and user).
		 *
		 * @param integer $order_id The order id.
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_germanized_before_order_confirmation', $order_id );

		/**
		 * Filters whether the order confirmation email has already been sent or not.
		 *
		 * @param bool $sent Whether the email has been sent or not.
		 * @param int $order_id The order id.
		 *
		 * @since 1.0.0
		 */
		if ( apply_filters( 'woocommerce_germanized_order_email_customer_confirmation_sent', false, $order_id ) === false && $processing = $this->get_email_instance_by_id( 'customer_processing_order' ) ) {
			$processing->trigger( $order_id );
		}

		/**
		 * Filters whether the order confirmation admin email has already been sent or not.
		 *
		 * @param bool $sent Whether the email has been sent or not.
		 * @param int $order_id The order id.
		 *
		 * @since 1.0.0
		 */
		if ( apply_filters( 'woocommerce_germanized_order_email_admin_confirmation_sent', false, $order_id ) === false && $new_order = $this->get_email_instance_by_id( 'new_order' ) ) {
			$new_order->trigger( $order_id );
		}

		/**
		 * After order confirmation emails.
		 *
		 * Fires after the order confirmation emails are being triggered (admin and user).
		 *
		 * @param integer $order_id The order id.
		 *
		 * @since 1.0.0
		 *
		 */
		do_action( 'woocommerce_germanized_order_confirmation_sent', $order_id );
	}

	/**
	 * @param WC_Order $order
	 * @param $sent_to_admin
	 * @param $plain_text
	 */
	public function email_notices( $order, $sent_to_admin, $plain_text ) {
		$type = $this->get_current_email_object();

		if ( $type ) {

			// Check if order contains digital products
			$items = $order->get_items();

			$is_downloadable       = false;
			$is_service            = false;
			$is_differential_taxed = false;

			if ( ! empty( $items ) ) {
				foreach ( $items as $item ) {
					$_product = $item->get_product();

					if ( ! $_product ) {
						continue;
					}

					if ( wc_gzd_is_revocation_exempt( $_product ) || apply_filters( 'woocommerce_gzd_product_is_revocation_exception', false, $_product, 'digital' ) ) {
						$is_downloadable = true;
					}

					if ( wc_gzd_is_revocation_exempt( $_product, 'service' ) || apply_filters( 'woocommerce_gzd_product_is_revocation_exception', false, $_product, 'service' ) ) {
						$is_service = true;
					}

					if ( wc_gzd_get_gzd_product( $_product )->is_differential_taxed() ) {
						$is_differential_taxed = true;
					}
				}
			}

			if ( get_option( 'woocommerce_gzd_differential_taxation_checkout_notices' ) === 'yes' && $is_differential_taxed && apply_filters( 'woocommerce_gzd_show_differential_taxation_in_emails', true, $type ) ) {

				$mark = wc_gzd_get_differential_taxation_mark();

				/**
				 * Filters the differential taxation notice text for emails.
				 *
				 * @param string $html The notice output.
				 *
				 * @since 1.5.0
				 *
				 */
				$notice = apply_filters( 'woocommerce_gzd_differential_taxation_notice_text_email', $mark . wc_gzd_get_differential_taxation_notice_text() );

				echo '<div class="gzd-differential-taxation-notice-email">' . wp_kses_post( wpautop( $notice ) ) . '</div>';
			}

			if ( $this->is_order_confirmation_email( $type->id ) ) {

				if ( $is_downloadable && $text = wc_gzd_get_legal_text_digital_email_notice() ) {

					/**
					 * Filters the order confirmation digital notice text.
					 *
					 * @param string $html The notice HTML.
					 * @param WC_Order $order The order object.
					 *
					 * @since 1.0.0
					 *
					 */
					echo wp_kses_post( apply_filters( 'woocommerce_gzd_order_confirmation_digital_notice', '<div class="gzd-digital-notice-text">' . wpautop( $text ) . '</div>', $order ) );
				}

				if ( $is_service && $text = wc_gzd_get_legal_text_service_email_notice() ) {

					/**
					 * Filters the order confirmation service notice text.
					 *
					 * @param string $html The notice HTML.
					 * @param WC_Order $order The order object.
					 *
					 * @since 1.0.0
					 *
					 */
					echo wp_kses_post( apply_filters( 'woocommerce_gzd_order_confirmation_service_notice', '<div class="gzd-service-notice-text">' . wpautop( $text ) . '</div>', $order ) );
				}
			}
		}
	}

	public function is_order_confirmation_email( $id ) {

		/**
		 * Filters whether a certain email id equals the order confirmation email.
		 *
		 * @param bool $is_confirmation Whether the `$id` matches the order confirmation or not.
		 * @param string $id The email id.
		 *
		 * @since 1.0.0
		 *
		 */
		return apply_filters( 'woocommerce_gzd_is_order_confirmation_email', ( 'customer_processing_order' === $id ), $id );
	}

	public function email_pay_now_button( $order ) {
		$type = $this->get_current_email_object();

		if ( $type && $this->is_order_confirmation_email( $type->id ) ) {
			WC_GZD_Checkout::instance()->add_payment_link( $order->get_id() );
		}
	}

	public function email_footer_plain( $text ) {
		$type = $this->get_current_email_object();

		if ( $type && 'plain' === $type->get_email_type() ) {
			$this->add_template_footers();
		}

		return $text;

	}

	public function get_email_instance_by_id( $id ) {

		if ( ! $this->mailer ) {
			$this->set_mailer();
		}

		$mails = $this->mailer->get_emails();

		foreach ( $mails as $mail ) {
			if ( $id === $mail->id ) {
				return $mail;
			}
		}

		return false;
	}

	public function set_order_email_filters( $order, $sent_to_admin, $plain_text, $current = null ) {

		if ( ! $current ) {
			$current = $this->get_current_email_object();
		}

		if ( ! $current || empty( $current ) ) {
			return;
		}

		$this->remove_order_email_filters();

		/**
		 * Before place email cart item filters.
		 *
		 * This hook fires before Germanized places certain cart item filters to make sure
		 * that product-related info (e.g. delivery time, unit price etc.) is shown within email tables.
		 *
		 * @param WC_GZD_Emails $this The email helper class.
		 * @param WC_Email $current The current email object.
		 * @param boolean $sent_to_admin Whether this mail is being sent to admin or not.
		 *
		 * @since 1.9.1
		 *
		 */
		do_action( 'woocommerce_gzd_before_set_email_cart_item_filters', $this, $current, $sent_to_admin );

		// Add order item name actions
		add_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_differential_taxation_mark', wc_gzd_get_hook_priority( 'email_product_differential_taxation' ), 2 );

		if ( 'yes' === get_option( 'woocommerce_gzd_display_emails_product_units' ) ) {
			add_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_units', wc_gzd_get_hook_priority( 'email_product_units' ), 2 );
		}

		if ( 'yes' === get_option( 'woocommerce_gzd_display_emails_delivery_time' ) ) {
			add_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_delivery_time', wc_gzd_get_hook_priority( 'email_product_delivery_time' ), 2 );
		}

		if ( 'yes' === get_option( 'woocommerce_gzd_display_emails_product_item_desc' ) ) {
			add_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_item_desc', wc_gzd_get_hook_priority( 'email_product_item_desc' ), 2 );
		}

		if ( 'yes' === get_option( 'woocommerce_gzd_display_emails_product_defect_description' ) ) {
			add_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_defect_description', wc_gzd_get_hook_priority( 'email_product_defect_description' ), 2 );
		}

		if ( 'yes' === get_option( 'woocommerce_gzd_display_emails_product_attributes' ) ) {
			add_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_attributes', wc_gzd_get_hook_priority( 'email_product_attributes' ), 2 );
		}

		if ( 'yes' === get_option( 'woocommerce_gzd_display_emails_unit_price' ) ) {
			add_filter( 'woocommerce_order_formatted_line_subtotal', 'wc_gzd_cart_product_unit_price', wc_gzd_get_hook_priority( 'email_product_unit_price' ), 2 );
		}

		if ( 'yes' === get_option( 'woocommerce_gzd_display_emails_deposit_packaging_type' ) ) {
			add_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_deposit_packaging_type', wc_gzd_get_hook_priority( 'email_deposit_packaging_type' ), 2 );
		}

		if ( 'yes' === get_option( 'woocommerce_gzd_display_emails_deposit' ) ) {
			add_filter( 'woocommerce_order_formatted_line_subtotal', 'wc_gzd_cart_product_deposit_amount', wc_gzd_get_hook_priority( 'email_product_deposit_amount' ), 2 );
		}

		/**
		 * After place email cart item filters.
		 *
		 * This hook fires after Germanized placed certain cart item filters.
		 *
		 * @param WC_GZD_Emails $this The email helper class.
		 * @param WC_Email $current The current email object.
		 * @param boolean $sent_to_admin Whether this mail is being sent to admin or not.
		 *
		 * @since 1.9.1
		 *
		 */
		do_action( 'woocommerce_gzd_after_set_email_cart_item_filters', $this, $current, $sent_to_admin );
	}

	public function remove_order_email_filters() {
		/**
		 * Remove order shopmarks
		 */
		foreach ( wc_gzd_get_order_shopmarks() as $shopmark ) {
			$shopmark->remove();
		}

		remove_action( 'woocommerce_thankyou', 'woocommerce_gzd_template_order_item_hooks', 0 );
		remove_action( 'before_woocommerce_pay', 'woocommerce_gzd_template_order_item_hooks', 10 );

		// Remove order email filters
		remove_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_differential_taxation_mark', wc_gzd_get_hook_priority( 'email_product_differential_taxation' ) );
		remove_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_units', wc_gzd_get_hook_priority( 'email_product_units' ) );
		remove_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_delivery_time', wc_gzd_get_hook_priority( 'email_product_delivery_time' ) );
		remove_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_item_desc', wc_gzd_get_hook_priority( 'email_product_item_desc' ) );
		remove_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_defect_description', wc_gzd_get_hook_priority( 'email_product_defect_description' ) );
		remove_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_attributes', wc_gzd_get_hook_priority( 'email_product_attributes' ) );
		remove_filter( 'woocommerce_order_item_name', 'wc_gzd_cart_product_deposit_packaging_type', wc_gzd_get_hook_priority( 'email_deposit_packaging_type' ) );

		remove_filter( 'woocommerce_order_formatted_line_subtotal', 'wc_gzd_cart_product_deposit_amount', wc_gzd_get_hook_priority( 'email_product_deposit_amount' ) );
		remove_filter( 'woocommerce_order_formatted_line_subtotal', 'wc_gzd_cart_product_unit_price', wc_gzd_get_hook_priority( 'email_product_unit_price' ) );
	}

	/**
	 * Add email styles
	 *
	 * @param string $css
	 *
	 * @return string
	 */
	public function styles( $css ) {
		return $css . '
			.unit-price-cart {
				display: block;
				font-size: 0.9em;
			}
			.gzd-digital-notice-text, .gzd-differential-taxation-notice-email, .gzd-service-notice-text {
				margin-top: 16px;
			}
			.deposit-packaging-type {
				text-transform: uppercase;
			}
		';
	}

	/**
	 * Hook into Email Footer and attach legal page content if necessary
	 *
	 * @param object $mail
	 */
	public function hook_mail_footer( $mail ) {
		if ( ! empty( $this->footer_attachments ) ) {

			foreach ( $this->footer_attachments as $option_key => $page_option ) {
				$option = wc_get_page_id( $page_option );

				if ( -1 === $option || ! get_option( $option_key ) ) {
					continue;
				}

				/**
				 * Filters whether to attach a certain page to the email footer or not.
				 *
				 * @param bool $attach Whether to attach or not.
				 * @param WC_Email $mail The mail instance.
				 * @param string $page_option The legal page option identifier e.g. terms.
				 *
				 * @since 1.0.0
				 *
				 */
				if ( in_array( $mail->id, get_option( $option_key ), true ) && apply_filters( 'woocommerce_gzd_attach_email_footer', true, $mail, $page_option ) ) {
					$this->attach_page_content( $option, $mail, $mail->get_email_type() );
				}
			}
		}
	}

	/**
	 * Add global footer Hooks to Email templates
	 */
	public function add_template_footers( $email = false ) {
		if ( ! $email ) {
			$email = $this->get_current_email_instance();
		}

		if ( is_a( $email, 'WC_Email' ) ) {
			$email_id = $email->id;

			/**
			 * Global email footer (after content) hook.
			 *
			 * This hook serves as entry point for legal attachment texts within emails.
			 * `$email_id` contains the actual Woo email template id e.g. "wc_email_new_order".
			 *
			 * @param WC_Email $type The email instance.
			 *
			 * @since 1.0.0
			 *
			 */
			do_action( 'woocommerce_germanized_email_footer_' . $email_id, $email );
		} else {
			/**
			 * Global email footer (after content) hook.
			 *
			 * This hook serves as entry point for legal attachment texts within emails in case
			 * not email instance (e.g. sent without an instance) could be detected.
			 *
			 * @since 3.7.3
			 *
			 */
			do_action( 'woocommerce_germanized_email_footer_fallback' );
		}
	}

	public function get_current_email_object() {
		return $this->get_current_email_instance();
	}

	/**
	 * Attach page content by ID. Removes revocation_form shortcut to not show the form within the Email footer.
	 *
	 * @param integer $page_id
	 */
	public function attach_page_content( $page_id, $mail, $email_type = 'html' ) {
		global $post;
		$reset_post = $post;

		/**
		 * Attach email footer.
		 *
		 * Fires before attaching legal page content to certain email templates.
		 *
		 * @param int $page_id The page id related to the legal content.
		 * @param string $email_type Equals `html` if HTML output is allowed.
		 *
		 * @since 1.0.0
		 *
		 */
		do_action( 'woocommerce_germanized_attach_email_footer', $page_id, $email_type );

		/**
		 * Filters the page id to be attached to the email footer.
		 *
		 * @param int $page_id The page id to be attached.
		 * @param WC_Email $mail The email instance.
		 *
		 * @since 1.0.0
		 *
		 */
		$page_id = apply_filters( 'woocommerce_germanized_attach_email_footer_page_id', $page_id, $mail );

		$template = 'emails/email-footer-attachment.php';

		if ( 'plain' === $email_type ) {
			$template = 'emails/plain/email-footer-attachment.php';
		}

		$content = $this->get_email_plain_content( $page_id );

		if ( ! empty( $content ) ) {
			$post = get_post( $page_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			if ( is_a( $post, 'WP_Post' ) ) {
				setup_postdata( $post );

				if ( ! empty( $content ) ) {
					wc_get_template(
						$template,
						array(
							'print_title'  => ( substr( trim( $content ), 0, 2 ) === '<h' ) ? false : true,
							'post_content' => $content,
							'post_id'      => $page_id,
							'post_attach'  => $post,
						)
					);
				}
			}

			/**
			 * Reset post data to keep global loop valid.
			 */
			if ( $reset_post ) {
				$post = $reset_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

				setup_postdata( $reset_post );
			}
		}
	}

	/**
	 * Replaces revocation_form shortcut with a link to the revocation form
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public function revocation_form_replacement( $atts ) {
		return '<a href="' . esc_url( wc_gzd_get_page_permalink( 'revocation' ) ) . '">' . _x( 'Forward your withdrawal online', 'revocation-form', 'woocommerce-germanized' ) . '</a>';
	}
}
