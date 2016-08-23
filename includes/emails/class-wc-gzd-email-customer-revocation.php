<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_GZD_Email_Customer_Revocation' ) ) :

/**
 * Revocation conformation Email
 *
 * Email is being sent if a customer fills out the revocation form (insert via [revocation_form] shortcut)
 *
 * @class 		WC_GZD_Email_Customer_Revocation
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Email_Customer_Revocation extends WC_Email {

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

		$this->id     		 	= 'customer_revocation';
		$this->title    	 	= __( 'Revocation', 'woocommerce-germanized' );
		$this->description   	= __( 'Email being sent if a customer fills out the revocation form.', 'woocommerce-germanized' );

		$this->template_html  	= 'emails/customer-revocation.php';
		$this->template_plain  	= 'emails/plain/customer-revocation.php';

		$this->subject    		= __( 'Your Revocation', 'woocommerce-germanized' );
		$this->heading       	= __( 'Your Revocation', 'woocommerce-germanized' );

		// Call parent constuctor
		parent::__construct();

		$this->customer_email = true;
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	function trigger( $user_data = array() ) {

		if ( !empty( $user_data['address_mail'] ) ) {
			$this->object      	  = $user_data;
			$this->user_email     = $user_data['address_mail'];
			if ( !empty( $user_data['mail'] ) )
				$this->user_email = $user_data['mail'];
			$this->recipient      = $this->user_email;
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() )
			return;

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		ob_start();
		wc_get_template( $this->template_html, array(
			'user'      	 => $this->object,
			'email_heading'  => $this->get_heading(),
			'blogname'       => $this->get_blogname(),
			'sent_to_admin'  => false,
			'plain_text'     => false,
			'email'			=> $this
		) );
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'user'      	 => $this->object,
			'email_heading'  => $this->get_heading(),
			'blogname'       => $this->get_blogname(),
			'sent_to_admin'  => false,
			'plain_text'     => true,
			'email'			=> $this
		) );
		return ob_get_clean();
	}
}

endif;

return new WC_GZD_Email_Customer_Revocation();
