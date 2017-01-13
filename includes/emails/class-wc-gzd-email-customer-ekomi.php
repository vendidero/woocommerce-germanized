<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_GZD_Email_Customer_Ekomi' ) ) :

/**
 * eKomi Review Reminder Email
 *
 * This Email is being sent after the order has been marked as completed to transfer the eKomi Rating Link to the customer.
 *
 * @class 		WC_GZD_Email_Customer_Ekomi
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Email_Customer_Ekomi extends WC_Email {

	/**
	 * Constructor
	 */
	function __construct() {

		$this->id 				= 'customer_ekomi';
		$this->title 			= _x( 'eKomi Review Reminder', 'ekomi', 'woocommerce-germanized' );
		$this->description		= _x( 'This E-Mail is being sent to a customer to transfer eKomi order review link to a customer.', 'ekomi', 'woocommerce-germanized' );

		$this->heading 			= _x( 'Please rate your Order', 'ekomi', 'woocommerce-germanized' );
		$this->subject      	= _x( 'Please rate your {site_title} order from {order_date}', 'ekomi', 'woocommerce-germanized' );

		$this->template_html 	= 'emails/customer-ekomi.php';
		$this->template_plain 	= 'emails/plain/customer-ekomi.php';

		// Triggers for this email
		add_action( 'woocommerce_germanized_ekomi_review_notification', array( $this, 'trigger' ) );

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
	function trigger( $order_id ) {

		if ( $order_id ) {
			$this->object 		= wc_get_order( $order_id );
			$this->recipient	= wc_gzd_get_crud_data( $this->object, 'billing_email' );

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';
			
			$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( wc_gzd_get_crud_data( $this->object, 'order_date' ) ) );
			$this->replace['order-number'] = $this->object->get_order_number();
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

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
			'order' 		=> $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false,
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
			'order' 		=> $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this
		) );
		return ob_get_clean();
	}

}

endif;

return new WC_GZD_Email_Customer_Ekomi();