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
	 * contains options and page ids
	 * @var array
	 */
	private $footer_attachments = array();

	/**
	 * Adds legal page ids to different options and adds a hook to the email footer
	 */
	public function __construct() {

		// Order attachments
		$attachment_order = wc_gzd_get_email_attachment_order();
		$this->footer_attachments = array();

		foreach ( $attachment_order as $key => $order )
			$this->footer_attachments[ 'woocommerce_gzd_mail_attach_' . $key ] = $key;

		add_action( 'woocommerce_email', array( $this, 'email_hooks' ), 0, 1 );
		// Change email template path if is germanized email template
		add_filter( 'woocommerce_template_directory', array( $this, 'set_woocommerce_template_dir' ), 10, 2 );
	}

	public function set_woocommerce_template_dir( $dir, $template ) {
		if ( file_exists( WC_germanized()->plugin_path() . '/templates/' . $template ) )
			return 'woocommerce-germanized';
		return $dir;
	}

	public function email_hooks( $mailer ) {
		// Hook before WooCommerce Footer is applied
		remove_action( 'woocommerce_email_footer', array( $mailer, 'email_footer' ) );
		add_action( 'woocommerce_email_footer', array( $this, 'add_template_footers' ), 0 );
		add_action( 'woocommerce_email_footer', array( $mailer, 'email_footer' ), 1 );

		add_filter( 'woocommerce_email_footer_text', array( $this, 'email_footer_plain' ), 0 );

		add_filter( 'woocommerce_email_styles', array( $this, 'styles' ) );

		$mails = $mailer->get_emails();

		if ( ! empty( $mails ) ) {

			foreach ( $mails as $mail )
				add_action( 'woocommerce_germanized_email_footer_' . $mail->id, array( $this, 'hook_mail_footer' ), 10, 1 );
		}

		// Set email filters
		add_filter( 'woocommerce_order_item_product', array( $this, 'set_order_email_filters' ), 0, 1 );
		// Remove them after total has been displayed
		add_action( 'woocommerce_email_after_order_table', array( $this, 'remove_order_email_filters' ), 10, 1 );

		// Pay now button
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_pay_now_button' ), 0, 1 );

		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_digital_revocation_notice' ), 0, 3 );

	}

	public function email_digital_revocation_notice( $order, $sent_to_admin, $plain_text ) {
			
		if ( get_option( 'woocommerce_gzd_checkout_legal_digital_checkbox' ) !== 'yes' )
			return;

		$type = $this->get_current_email_object();
		
		if ( $type && $type->id == 'customer_processing_order' ) {

			// Check if order contains digital products
			$items = $order->get_items();
			$is_downloadable = false;
			
			if ( ! empty( $items ) ) {
				foreach ( $items as $item ) {
					$_product = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
					if ( $_product->is_downloadable() || apply_filters( 'woocommerce_gzd_product_is_revocation_exception', false, $_product ) )
						$is_downloadable = true;
				}
			}

			if ( $is_downloadable && $text = wc_gzd_get_legal_text_digital_email_notice() )
				echo wpautop( apply_filters( 'woocommerce_gzd_order_confirmation_digital_notice', '<div class="gzd-digital-notice-text">' . $text . '</div>', $order ) );
		}
	}

	public function email_pay_now_button( $order ) {

		$type = $this->get_current_email_object();

		if ( $type && $type->id == 'customer_processing_order' )
			WC_GZD_Checkout::instance()->add_payment_link( $order->id );
	}

	public function email_footer_plain( $text ) {

		$type = $this->get_current_email_object();
		
		if ( $type && $type->get_email_type() == 'plain' )
			$this->add_template_footers();
		
		return $text;

	}

	public function get_email_instance_by_id( $id ) {
		
		$mailer = WC()->mailer();
		$mails = $mailer->get_emails();
		
		foreach ( $mails as $mail ) {
			if ( $id === $mail->id )
				return $mail;
		}
		
		return false;
	}
 
	public function set_order_email_filters( $product ) {

		$current = $this->get_current_email_object();

		if ( ! $current || empty( $current ) )
			return $product;

		// Add order item name actions
		add_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_units', wc_gzd_get_hook_priority( 'email_product_units' ), 2 );
		add_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_delivery_time', wc_gzd_get_hook_priority( 'email_product_delivery_time' ), 2 );
		add_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_item_desc', wc_gzd_get_hook_priority( 'email_product_item_desc' ), 2 );
		add_filter( 'woocommerce_order_formatted_line_subtotal', 'wc_gzd_cart_product_unit_price', wc_gzd_get_hook_priority( 'email_product_unit_price' ), 2 );
		
		return $product;
	}

	public function remove_order_email_filters() {

		$current = $this->get_current_email_object();

		if ( ! $current || empty( $current ) )
			return;

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
				$option = woocommerce_get_page_id ( $page_option );
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
		$found_mails = array();
		foreach ( $tpls as $tpl ) {
			$tpl = apply_filters( 'woocommerce_germanized_email_template_name',  str_replace( array( 'admin-', '-' ), array( '', '_' ), basename( $tpl, '.php' ) ), $tpl );
			$mails = WC()->mailer()->get_emails();
			if ( ! empty( $mails ) ) {
				foreach ( $mails as $mail ) {
					if ( $mail->id == $tpl )
						array_push( $found_mails, $mail );
				}
			}
		}
		if ( ! empty( $found_mails ) )
			return $found_mails[ sizeof( $found_mails ) - 1 ];
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
		return '<a href="' . esc_url( get_permalink( wc_get_page_id( 'revocation' ) ) ) . '">' . _x( 'Forward your Revocation online', 'revocation-form', 'woocommerce-germanized' ) . '</a>';
	}

}
