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
	private $footer_attachments;

	/**
	 * Adds legal page ids to different options and adds a hook to the email footer
	 */
	public function __construct() {

		$this->footer_attachments = array(
			'woocommerce_gzd_mail_attach_revocation' => woocommerce_get_page_id ( 'revocation' ),
			'woocommerce_gzd_mail_attach_terms' => woocommerce_get_page_id ( 'terms' ),
			'woocommerce_gzd_mail_attach_data_security' => woocommerce_get_page_id ( 'data_security' ),
			'woocommerce_gzd_mail_attach_imprint' => woocommerce_get_page_id ( 'imprint' ),
		);

		// Add new customer activation
		if ( get_option( 'woocommerce_gzd_customer_activation' ) == 'yes' ) {
			remove_action( 'woocommerce_created_customer_notification', array( WC()->mailer(), 'customer_new_account' ), 10 );
			add_action( 'woocommerce_created_customer_notification', array( $this, 'customer_new_account_activation' ), 9, 3 );
		}

		// Hook before WooCommerce Footer is applied
		remove_action( 'woocommerce_email_footer', array( WC()->mailer(), 'email_footer' ) );
		add_action( 'woocommerce_email_footer', array( $this, 'add_template_footers' ), 0 );
		add_action( 'woocommerce_email_footer', array( WC()->mailer(), 'email_footer' ), 1 );

		$mails = WC()->mailer()->get_emails();

		if ( ! empty( $mails ) ) {
			foreach ( $mails as $mail ) {
				add_action( 'woocommerce_germanized_email_footer_' . $mail->id, array( $this, 'hook_mail_footer' ), 10, 1 );
			}
		}

		// Add email order item name filter (if frontend filters are not loaded)
		if ( is_admin() ) {
			add_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_delivery_time', 0, 2 );
			add_action( 'woocommerce_order_item_name', 'wc_gzd_cart_product_item_desc', 0, 2 );
		}

	}

	/**
	 * Customer new account activation email.
	 *
	 * @param int $customer_id
	 * @param array $new_customer_data
	 */
	public function customer_new_account_activation( $customer_id, $new_customer_data = array(), $password_generated = false ) {
		global $wp_hasher;

		if ( ! $customer_id )
			return;

		$user_pass = ! empty( $new_customer_data['user_pass'] ) ? $new_customer_data['user_pass'] : '';
		
		if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$wp_hasher = new PasswordHash( 8, true );
		}
		$user_activation = $wp_hasher->HashPassword( wp_generate_password( 20 ) );
		$user_activation_url = apply_filters( 'woocommerce_gzd_customer_activation_url', add_query_arg( 'activate', $user_activation, get_permalink( wc_get_page_id( 'myaccount' ) ) ) ); 
		add_user_meta( $customer_id, '_woocommerce_activation', $user_activation );

		$email = WC()->mailer()->emails['WC_GZD_Email_Customer_New_Account_Activation'];
		$email->trigger( $customer_id, $user_activation, $user_activation_url, $user_pass, $password_generated );
	}

	/**
	 * Adds product description to order item if available
	 *  
	 * @param  string $item_name product name
	 * @param  array $item     
	 * @return string the item name containing product description if available
	 */
	public function order_item_desc( $item_name, $item ) {
		if ( isset( $item[ 'product_desc' ] ) )
			$item_name .= '<div class="wc-gzd-item-desc item-desc">' . $item[ 'product_desc' ] . '</div>';
		return $item_name;
	}
	/**
	 * Hook into Email Footer and attach legal page content if necessary
	 *  
	 * @param  object $mail
	 */
	public function hook_mail_footer( $mail ) {
		if ( ! empty( $this->footer_attachments ) ) {
			foreach ( $this->footer_attachments as $option_key => $option ) {
				if ( $option == -1 || ! get_option( $option_key ) )
					continue;
				if ( in_array( $mail->id, get_option( $option_key ) ) ) {
					$this->attach_page_content( $option );
				}
			}
		}
	}

	/**
	 * Add global footer Hooks to Email templates
	 */
	public function add_template_footers() {
		$type = ( ! empty( $GLOBALS[ 'wc_gzd_template_name' ] ) ) ? $this->get_email_instance_by_tpl( $GLOBALS[ 'wc_gzd_template_name' ] ) : '';
		if ( ! empty( $type ) )
			do_action( 'woocommerce_germanized_email_footer_' . $type->id, $type );
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
			if ( !empty( $mails ) ) {
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
	public function attach_page_content( $page_id ) {
		remove_shortcode( 'revocation_form' );
		add_shortcode( 'revocation_form', array( $this, 'revocation_form_replacement' ) );
		wc_get_template( 'emails/email-footer-attachment.php', array(
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
