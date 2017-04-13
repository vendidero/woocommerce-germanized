<?php
/**
 * Attaches legal relevant Pages to WooCommerce Emails if has been set by WooCommerce Germanized Options
 *
 * @class 		WC_GZD_Emails
 * @version		1.0.0
 * @author 		Vendidero
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
	 * Adds legal page ids to different options and adds a hook to the email footer
	 */
	public function __construct() {

		$this->set_footer_attachments();

		add_action( 'woocommerce_email', array( $this, 'email_hooks' ), 0, 1 );

        if ( wc_gzd_send_instant_order_confirmation() ) {

            // Send order notice directly after new order is being added - use these filters because order status has to be updated already
            add_filter( 'woocommerce_payment_successful_result', array( $this, 'send_order_confirmation_mails' ), 0, 2 );
            add_filter( 'woocommerce_checkout_no_payment_needed_redirect', array( $this, 'send_order_confirmation_mails' ), 0, 2 );
        }

        // Disable paid order email for certain gateways (e.g. COD or invoice)
		add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_disable_order_paid_email_notification_2_6' ), 0, 1 );
        add_filter( 'woocommerce_allow_send_queued_transactional_email', array( $this, 'maybe_disable_order_paid_email_notification'), 10, 3 );

        // Change email template path if is germanized email template
		add_filter( 'woocommerce_template_directory', array( $this, 'set_woocommerce_template_dir' ), 10, 2 );
		// Map partially refunded order mail template to correct email instance
        add_filter( 'woocommerce_gzd_email_template_id_comparison', array( $this, 'check_for_partial_refund_mail' ), 10, 3 );
        // Hide username if an email contains a password or password reset link (TS advises to do so)
        if ( 'yes' === get_option( 'woocommerce_gzd_hide_username_with_password' ) )
            add_filter( 'woocommerce_before_template_part', array( $this, 'maybe_set_gettext_username_filter' ), 10, 4 );

        if ( is_admin() )
		    $this->admin_hooks();
	}

	public function maybe_set_gettext_username_filter( $template_name, $template_path, $located, $args ) {

		$templates = array(
			'emails/customer-reset-password.php' => 'maybe_hide_username_password_reset',
			'emails/plain/customer-reset-password.php' => 'maybe_hide_username_password_reset',
		);

		// If the password is generated automatically and sent by email, hide the username
		if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) ) {
			$templates = array_merge( $templates, array(
				'emails/customer-new-account.php' => 'maybe_hide_username_new_account',
				'emails/plain/customer-new-account.php' => 'maybe_hide_username_new_account'
			) );
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
				remove_filter( 'gettext', array( $this, 'maybe_hide_username_password_reset' ), 10, 3 );
				return '';
			}
		}

		return $translated;
	}

	public function maybe_hide_username_new_account( $translated, $original, $domain ) {
		if ( 'woocommerce' === $domain && 'Thanks for creating an account on %s. Your username is <strong>%s</strong>' === $original ) {
			remove_filter( 'gettext', array( $this, 'maybe_hide_username_new_account' ), 10, 3 );
			return __( 'Thanks for creating an account on %s.', 'woocommerce-germanized' );
		}
		return $translated;
	}

	public function check_for_partial_refund_mail( $result, $mail_id, $tpl ) {

		if ( $mail_id === 'customer_partially_refunded_order' && $tpl === 'customer_refunded_order' )
			return true;

		return $result;
	}

    private function set_mailer( $mailer = null ) {
	    if ( $mailer )
	        $this->mailer = $mailer;
	    else
            $this->mailer = WC()->mailer();
    }

	private function set_footer_attachments() {

        // Order attachments
        $attachment_order = wc_gzd_get_email_attachment_order();
        $this->footer_attachments = array();

        foreach ( $attachment_order as $key => $order )
            $this->footer_attachments[ 'woocommerce_gzd_mail_attach_' . $key ] = $key;
    }
	
	public function admin_hooks() {
		add_filter( 'woocommerce_resend_order_emails_available', array( $this, 'resend_order_emails' ), 0 );
	}

    public function email_hooks( $mailer ) {

        $this->set_mailer( $mailer );

        if ( wc_gzd_send_instant_order_confirmation() ) {
            $this->prevent_confirmation_email_sending();
        }

        // Hook before WooCommerce Footer is applied
        remove_action( 'woocommerce_email_footer', array( $this->mailer, 'email_footer' ) );

        add_action( 'woocommerce_email_footer', array( $this, 'add_template_footers' ), 0 );
        add_action( 'woocommerce_email_footer', array( $this->mailer, 'email_footer' ), 1 );

        add_filter( 'woocommerce_email_footer_text', array( $this, 'email_footer_plain' ), 0 );
        add_filter( 'woocommerce_email_styles', array( $this, 'styles' ) );

        $mails = $this->mailer->get_emails();

        if ( ! empty( $mails ) ) {

            foreach ( $mails as $mail )
                add_action( 'woocommerce_germanized_email_footer_' . $mail->id, array( $this, 'hook_mail_footer' ), 10, 1 );
        }

        // Set email filters
        add_action( 'woocommerce_email_before_order_table', array( $this, 'set_order_email_filters' ), 10, 4 );

        // Remove them after total has been displayed
        add_action( 'woocommerce_email_after_order_table', array( $this, 'remove_order_email_filters' ), 10, 4 );

        // Pay now button
        add_action( 'woocommerce_email_before_order_table', array( $this, 'email_pay_now_button' ), 0, 1 );
        add_action( 'woocommerce_email_after_order_table', array( $this, 'email_digital_revocation_notice' ), 0, 3 );
    }

	public function get_gateways_disabling_paid_for_order_mail() {
		return apply_filters( 'woocommerce_gzd_disable_gateways_paid_order_email', array( 'cod', 'invoice' ) );
	}

    public function maybe_disable_order_paid_email_notification_2_6( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$method = wc_gzd_get_crud_data( $order, 'payment_method' );
		$current_status = $order->get_status();
		$disable_for_gateways = $this->get_gateways_disabling_paid_for_order_mail();

		if ( in_array( $method, $disable_for_gateways ) ) {
			// Remove action
			if ( WC_germanized()->emails->get_email_instance_by_id( 'customer_paid_for_order' ) ) {
				remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( WC_germanized()->emails->get_email_instance_by_id( 'customer_paid_for_order' ), 'trigger' ), 30 );
			}
		}
    }

    public function maybe_disable_order_paid_email_notification( $send, $filter, $args ) {
		if ( isset( $args[0] ) && is_numeric( $args[0] ) ) {
			$order = wc_get_order( absint( $args[0] ) );

			if ( $order ) {

				$method = wc_gzd_get_crud_data( $order, 'payment_method' );
				$current_status = $order->get_status();
				$disable_for_gateways = $this->get_gateways_disabling_paid_for_order_mail();

				if ( in_array( $method, $disable_for_gateways ) && $filter === 'woocommerce_order_status_pending_to_processing' ) {
					return false;
				}
			}
		}
		return $send;
    }
	
	public function resend_order_emails( $emails ) {
		global $theorder;
		
		if ( is_null( $theorder ) )
			return $emails;
		
		array_push( $emails, 'customer_paid_for_order' );
		
		return $emails;
	}

	public function set_woocommerce_template_dir( $dir, $template ) {
		if ( file_exists( WC_germanized()->plugin_path() . '/templates/' . $template ) )
			return 'woocommerce-germanized';
		return $dir;
	}

    private function get_confirmation_email_transaction_statuses() {
        return array(
            'woocommerce_order_status_pending_to_processing',
            'woocommerce_order_status_pending_to_completed',
            'woocommerce_order_status_pending_to_on-hold',
            'woocommerce_order_status_on-hold_to_processing',
        );
    }

    public function prevent_confirmation_email_sending() {

	    foreach( $this->get_confirmation_email_transaction_statuses() as $status ) {

            remove_action( $status . '_notification', array( $this->get_email_instance_by_id( 'customer_processing_order' ), 'trigger' ) );
            remove_action( $status . '_notification', array( $this->get_email_instance_by_id( 'new_order' ), 'trigger' ) );

            if ( $this->get_email_instance_by_id( 'customer_on_hold_order' ) )
                remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this->get_email_instance_by_id( 'customer_on_hold_order' ), 'trigger' ) );

	    }
    }

    /**
     * Send order confirmation mail directly after order is being sent
     *
     * @param  mixed 	  $return
     * @param  mixed  	  $order
     */
    public function send_order_confirmation_mails( $result, $order ) {

        if ( ! is_object( $order ) )
            $order = wc_get_order( $order );

        if ( ! apply_filters( 'woocommerce_germanized_send_instant_order_confirmation', true, $order ) )
            return $result;

        do_action( 'woocommerce_germanized_before_order_confirmation', wc_gzd_get_crud_data( $order, 'id' ) );

        // Send order processing mail
        if ( apply_filters( 'woocommerce_germanized_order_email_customer_confirmation_sent', false, wc_gzd_get_crud_data( $order, 'id' ) ) === false && $processing = $this->get_email_instance_by_id( 'customer_processing_order' ) )
            $processing->trigger( wc_gzd_get_crud_data( $order, 'id' ) );

        // Send admin mail
        if ( apply_filters( 'woocommerce_germanized_order_email_admin_confirmation_sent', false, wc_gzd_get_crud_data( $order, 'id' ) ) === false && $new_order = $this->get_email_instance_by_id( 'new_order' ) )
            $new_order->trigger( wc_gzd_get_crud_data( $order, 'id' ) );

        // Always clear cart after order success
        if ( get_option( 'woocommerce_gzd_checkout_stop_order_cancellation' ) === 'yes' )
            WC()->cart->empty_cart();

        do_action( 'woocommerce_germanized_order_confirmation_sent', wc_gzd_get_crud_data( $order, 'id' ) );

        return $result;
    }

	public function email_digital_revocation_notice( $order, $sent_to_admin, $plain_text ) {
			
		if ( get_option( 'woocommerce_gzd_checkout_legal_digital_checkbox' ) !== 'yes' )
			return;

		$type = $this->get_current_email_object();
		
		if ( $type && $type->id == 'customer_processing_order' ) {

			// Check if order contains digital products
			$items = $order->get_items();
			$is_downloadable = false;
			$is_service = false;
			
			if ( ! empty( $items ) ) {
				
				foreach ( $items as $item ) {
					
					$_product = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );

					if ( ! $_product )
						continue;
					
					if ( wc_gzd_is_revocation_exempt( $_product ) || apply_filters( 'woocommerce_gzd_product_is_revocation_exception', false, $_product, 'digital' ) )
						$is_downloadable = true;

					if ( wc_gzd_is_revocation_exempt( $_product, 'service' ) || apply_filters( 'woocommerce_gzd_product_is_revocation_exception', false, $_product, 'service' ) )
						$is_service = true;
				}
			}

			if ( $is_downloadable && $text = wc_gzd_get_legal_text_digital_email_notice() )
				echo wpautop( apply_filters( 'woocommerce_gzd_order_confirmation_digital_notice', '<div class="gzd-digital-notice-text">' . $text . '</div>', $order ) );
		
			if ( $is_service && $text = wc_gzd_get_legal_text_service_email_notice() )
				echo wpautop( apply_filters( 'woocommerce_gzd_order_confirmation_service_notice', '<div class="gzd-service-notice-text">' . $text . '</div>', $order ) );
		
		}
	}

	public function email_pay_now_button( $order ) {

		$type = $this->get_current_email_object();

		if ( $type && $type->id == 'customer_processing_order' )
			WC_GZD_Checkout::instance()->add_payment_link( wc_gzd_get_crud_data( $order, 'id' ) );
	}

	public function email_footer_plain( $text ) {

		$type = $this->get_current_email_object();
		
		if ( $type && $type->get_email_type() == 'plain' )
			$this->add_template_footers();
		
		return $text;

	}

	public function get_email_instance_by_id( $id ) {

        if ( ! $this->mailer ) {
            $this->set_mailer();
        }

		$mails = $this->mailer->get_emails();
		
		foreach ( $mails as $mail ) {
			if ( $id === $mail->id )
				return $mail;
		}
		
		return false;
	}
 
	public function set_order_email_filters() {

		$current = $this->get_current_email_object();

		if ( ! $current || empty( $current ) )
			return;

		// Add order item name actions
		add_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_units', wc_gzd_get_hook_priority( 'email_product_units' ), 2 );
		add_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_delivery_time', wc_gzd_get_hook_priority( 'email_product_delivery_time' ), 2 );
		add_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_item_desc', wc_gzd_get_hook_priority( 'email_product_item_desc' ), 2 );
		add_filter( 'woocommerce_order_formatted_line_subtotal', 'wc_gzd_cart_product_unit_price', wc_gzd_get_hook_priority( 'email_product_unit_price' ), 2 );

	}

	public function remove_order_email_filters() {

		// Add order item name actions
		remove_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_units', wc_gzd_get_hook_priority( 'email_product_units' ) );
		remove_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_delivery_time', wc_gzd_get_hook_priority( 'email_product_delivery_time' ) );
		remove_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_item_desc', wc_gzd_get_hook_priority( 'email_product_item_desc' ) );
		remove_filter( 'woocommerce_order_formatted_line_subtotal', 'wc_gzd_cart_product_unit_price', wc_gzd_get_hook_priority( 'email_product_unit_price' ) );

	}

	/**
	 * Add email styles
	 *  
	 * @param  string $css 
	 * @return string      
	 */
	public function styles( $css ) {
		return $css .= '
			.unit-price-cart {
				display: block;
				font-size: 0.9em;
			}
			.gzd-digital-notice-text {
				margin-top: 16px;
			}
		';
	}

	/**
	 * Hook into Email Footer and attach legal page content if necessary
	 *  
	 * @param  object $mail
	 */
	public function hook_mail_footer( $mail ) {
		if ( ! empty( $this->footer_attachments ) ) {
			foreach ( $this->footer_attachments as $option_key => $page_option ) {
				$option = wc_get_page_id ( $page_option );
				if ( $option == -1 || ! get_option( $option_key ) )
					continue;
				if ( in_array( $mail->id, get_option( $option_key ) ) && apply_filters( 'woocommerce_gzd_attach_email_footer', true, $mail, $page_option ) ) {
					$this->attach_page_content( $option, $mail->get_email_type() );
				}
			}
		}
	}

	/**
	 * Add global footer Hooks to Email templates
	 */
	public function add_template_footers() {
		$type = $this->get_current_email_object();
		if ( $type )
			do_action( 'woocommerce_germanized_email_footer_' . $type->id, $type );
	}

	public function get_current_email_object() {

		if ( isset( $GLOBALS[ 'wc_gzd_template_name' ] ) && ! empty( $GLOBALS[ 'wc_gzd_template_name' ] ) ) {
			
			$object = $this->get_email_instance_by_tpl( $GLOBALS[ 'wc_gzd_template_name' ] );
			if ( is_object( $object ) )
				return $object;
		}

		return false;
	}

	/**
	 * Returns Email Object by examining the template file
	 *  
	 * @param  string $tpl 
	 * @return mixed      
	 */
	private function get_email_instance_by_tpl( $tpls = array() ) {

	    if ( ! $this->mailer )
	        $this->set_mailer();

	    $found_mails = array();
		$mails = $this->mailer->get_emails();

	    foreach ( $tpls as $tpl ) {

	        $tpl = apply_filters( 'woocommerce_germanized_email_template_name',  str_replace( array( 'admin-', '-' ), array( '', '_' ), basename( $tpl, '.php' ) ), $tpl );

			if ( ! empty( $mails ) ) {

				foreach ( $mails as $mail ) {

					if ( is_object( $mail ) ) {

						if ( apply_filters( 'woocommerce_gzd_email_template_id_comparison', ( $mail->id === $tpl ), $mail->id, $tpl ) ) {
							array_push( $found_mails, $mail );
						}
					}
				}
			}
		}

		if ( ! empty( $found_mails ) ) {
			return $found_mails[ sizeof( $found_mails ) - 1 ];
		}

		return null;
	}

	/**
	 * Attach page content by ID. Removes revocation_form shortcut to not show the form within the Email footer.
	 *  
	 * @param  integer $page_id 
	 */
	public function attach_page_content( $page_id, $email_type = 'html' ) {
		
		remove_shortcode( 'revocation_form' );
		add_shortcode( 'revocation_form', array( $this, 'revocation_form_replacement' ) );
		
		$template = 'emails/email-footer-attachment.php';
		if ( $email_type == 'plain' )
			$template = 'emails/plain/email-footer-attachment.php';
		
		wc_get_template( $template, array(
			'post_attach'  => get_post( $page_id ),
		) );
		
		add_shortcode( 'revocation_form', 'WC_GZD_Shortcodes::revocation_form' );
	}

	/**
	 * Replaces revocation_form shortcut with a link to the revocation form
	 *  
	 * @param  array $atts 
	 * @return string       
	 */
	public function revocation_form_replacement( $atts ) {
		return '<a href="' . esc_url( wc_gzd_get_page_permalink( 'revocation' ) ) . '">' . _x( 'Forward your Revocation online', 'revocation-form', 'woocommerce-germanized' ) . '</a>';
	}

}
