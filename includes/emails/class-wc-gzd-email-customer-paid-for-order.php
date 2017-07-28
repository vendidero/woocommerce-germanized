<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_GZD_Email_Customer_Paid_For_Order' ) ) :

/**
 * eKomi Review Reminder Email
 *
 * This Email is being sent after the order has been marked as completed to transfer the eKomi Rating Link to the customer.
 *
 * @class 		WC_GZD_Email_Customer_Ekomi
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Email_Customer_Paid_For_Order extends WC_Email {

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->id 				= 'customer_paid_for_order';
		$this->customer_email   = true;
		$this->title 			= __( 'Paid for order', 'woocommerce-germanized' );
		$this->description		= __( 'This E-Mail is being sent to a customer after the order has been paid.', 'woocommerce-germanized' );

		$this->template_html 	= 'emails/customer-paid-for-order.php';
		$this->template_plain  	= 'emails/plain/customer-paid-for-order.php';

		$this->heading 			= __( 'Payment received', 'woocommerce-germanized' );
		$this->subject      	= __( 'Payment received for order {order_number}', 'woocommerce-germanized' );

		// Triggers for this email
		add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ), 30 );

		// Call parent constuctor
		parent::__construct();
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	public function trigger( $order_id ) {

		if ( $order_id ) {
			$this->object       = wc_get_order( $order_id );
			$this->recipient    = wc_gzd_get_crud_data( $this->object, 'billing_email' );

			$this->find['order-number']    = '{order_number}';
			$this->replace['order-number'] = $this->object->get_order_number();
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get content html.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'			=> $this
		) );
	}

	/**
	 * Get content plain.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this
		) );
	}

}

endif;

return new WC_GZD_Email_Customer_Paid_For_Order();