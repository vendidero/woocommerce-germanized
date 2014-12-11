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

		// Hook before WooCommerce Footer is applied
		add_action( 'woocommerce_email_footer', array( $this, 'add_template_footers' ), -1 );
		add_action( 'woocommerce_order_item_name', 'wc_gzd_product_item_desc', 0, 2 );

		$mails = WC()->mailer()->get_emails();

		if ( !empty( $mails ) ) {
			foreach ( $mails as $mail ) {
				add_action( 'woocommerce_germanized_email_footer_' . $mail->id, array( $this, 'hook_mail_footer' ), 10, 1 );
			}
		}
	}

	public function order_item_desc( $item_name, $item ) {
		return wc_gzd_product_item_desc( $item_name, $item );
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
				if ( in_array( $mail->id, get_option( $option_key ) ) )
					$this->attach_page_content( $option );
			}
		}
	}

	/**
	 * Add global footer Hooks to Email templates
	 */
	public function add_template_footers() {
		$type = ( !empty( $GLOBALS['template_name'] ) ) ? $this->get_email_instance_by_tpl( $GLOBALS['template_name'][0] ) : '';
		if ( !empty( $type ) )
			do_action( 'woocommerce_germanized_email_footer_' . $type->id, $type );
	}

	/**
	 * Returns Email Object by examining the template file
	 *  
	 * @param  string $tpl 
	 * @return mixed      
	 */
	private function get_email_instance_by_tpl( $tpl ) {
		$tpl = str_replace( array( 'admin-', '-' ), array( '', '_' ), basename( $tpl, '.php' ) );
		$mails = WC()->mailer()->get_emails();
		if ( !empty( $mails ) ) {
			foreach ( $mails as $mail ) {
				if ( $mail->id == $tpl )
					return $mail;
			}
		}
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
