<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Direct Debit Payment Gateway
 *
 * Provides a Direct Debit Payment Gateway.
 *
 * @class 		WC_GZD_Gateway_Direct_Debit
 * @extends		WC_Payment_Gateway
 * @version		2.1.0
 * @author 		Vendidero
 */
class WC_GZD_Gateway_Direct_Debit extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     */
	public function __construct() {
		$this->id                 = 'direct-debit';
		$this->icon               = apply_filters( 'woocommerce_gzd_direct_debit_icon', '' );
		$this->has_fields         = true;
		$this->method_title       = __( 'Direct Debit', 'woocommerce-germanized' );
		$this->method_description =  sprintf( __( 'Allows you to offer direct debit as a payment method to your customers. Adds SEPA fields to checkout. %s', 'woocommerce-germanized' ), '<a class="button button-secondary" href="' . admin_url( 'export.php' ) . '">' . __( 'SEPA XML Bulk Export', 'woocommerce-germanized' ) . '</a>' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->enabled          				= $this->get_option( 'enabled' );
		$this->title        					= $this->get_option( 'title' );
		$this->description  					= $this->get_option( 'description' );
		$this->instructions 					= $this->get_option( 'instructions', $this->description );
		$this->enable_checkbox					= $this->get_option( 'enable_checkbox', 'yes' );
		$this->generate_mandate_id 				= $this->get_option( 'generate_mandate_id', 'yes' );
		$this->mandate_id_format 				= $this->get_option( 'mandate_id_format', 'MANDAT{id}' );
		$this->company_info 					= $this->get_option( 'company_info' );
		$this->company_identification_number 	= $this->get_option( 'company_identification_number' );
		$this->company_account_holder     		= $this->get_option( 'company_account_holder' );
		$this->company_account_iban     		= $this->get_option( 'company_account_iban' );
		$this->company_account_bic     			= $this->get_option( 'company_account_bic' );
		$this->pain_format     					= $this->get_option( 'pain_format', 'pain.008.002.02' );
		$this->checkbox_label					= $this->get_option( 'checkbox_label' );
		$this->remember							= $this->get_option( 'remember', 'no' );
		$this->mask 							= $this->get_option( 'mask', 'yes' );
		$this->mandate_text	   					= $this->get_option( 'mandate_text', __( '[company_info]
debtee identification number: [company_identification_number]
mandat reference number: [mandate_id].

<h3>SEPA Direct Debit Mandate</h3>

I hereby authorize the payee to automatically draft from my savings account listed below for the specified amount. I further authorize my bank to accept the direct debit from this account.

Notice: I may request a full refund within eight weeks starting with the initial debiting date. Responsibilities agreed with my credit institute apply for a refund.

<strong>Debtor:</strong>
Account holder: [account_holder]
Street: [street]
Postcode: [postcode]
City: [city]
Country: [country]
IBAN: [account_iban]
BIC: [account_swift]

[city], [date], [account_holder]

This letter is done automatically and is valid without signature.

<hr/>

Please notice: Period for pre-information of the SEPA direct debit is shortened to one day.', 'woocommerce-germanized' ) );

		$this->supports = array(
			'products',
		);

		if ( $this->get_option( 'enabled' ) === 'yes' && ! $this->supports_encryption() ) {

			ob_start();
			include_once( 'views/html-encryption-notice.php' );
			$notice = ob_get_clean();

			$this->method_description .= $notice;

		}

		// Force disabling remember account data if encryption is not supported
		if ( ! $this->supports_encryption() ) {
			$this->remember = 'no';
		}

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    	add_action( 'woocommerce_thankyou_direct-debit', array( $this, 'thankyou_page' ) );
    	add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
    	add_action( 'woocommerce_review_order_after_payment', array( $this, 'checkbox' ), wc_gzd_get_hook_priority( 'checkout_direct_debit' ) );
    	add_action( 'wp_ajax_show_direct_debit', array( $this, 'generate_mandate' ) );
		add_action( 'wp_ajax_nopriv_show_direct_debit', array( $this, 'generate_mandate' ) );
		add_filter( 'woocommerce_email_classes', array( $this, 'add_email_template' ) );

		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkbox' ) );

		// Pay for Order
		add_action( 'woocommerce_pay_order_before_submit', array( $this, 'checkbox' ) );

		// Order Meta
    	add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'set_order_meta' ), 10, 2 );

    	// Customer Emails
    	add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
    	add_action( 'woocommerce_germanized_order_confirmation_sent', array( $this, 'send_mail' ) );
    	add_action( 'woocommerce_email_customer_details', array( $this, 'email_sepa' ), 15, 3 );

    	// Order admin
    	add_filter( 'woocommerce_admin_billing_fields', array( $this, 'set_debit_fields' ) );
    	// Admin order table download actions
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'order_actions' ), 0, 2 );

    	// Export filters
		add_action( 'export_filters', array( $this, 'export_view' ) );
		add_action( 'export_wp', array( $this, 'export' ), 0, 1 );
		add_filter( 'export_args', array( $this, 'export_args' ), 0, 1 );

    }

    public function order_actions( $actions, $order ) {

    	if ( wc_gzd_get_crud_data( $order, 'payment_method' ) === $this->id ) {
    		$actions[ 'download-sepa' ] = array(
				'url'       => add_query_arg( array( 'download' => 'true', 'content' => 'sepa', 'sepa_order_id' => wc_gzd_get_crud_data( $order, 'id' ) ), admin_url( 'export.php' ) ),
				'name'      => __( 'SEPA XML Export', 'woocommerce-germanized' ),
				'action'    => "xml"
			);
    	}

    	return $actions;
    }

    public function export_view() {
    	include_once( 'views/html-export.php' );
    }

    public function export_args( $args = array() ) {
		if ( 'sepa' === $_GET['content'] ) {
			$args['content'] = 'sepa';
			if ( isset( $_GET['sepa_start_date'] ) || isset( $_GET['sepa_end_date'] ) ) {
				$args['start_date'] = ( isset( $_GET['sepa_start_date'] ) ? sanitize_text_field( $_GET['sepa_start_date'] ) : '' );
				$args['end_date'] = ( isset( $_GET['sepa_end_date'] ) ? sanitize_text_field( $_GET['sepa_end_date'] ) : '' );
			} elseif ( isset( $_GET[ 'sepa_order_id' ] ) ) {
				$args['order_id'] = absint( $_GET['sepa_order_id'] );
			}
		}
		return $args;
	}

	public function export( $args = array() ) {
		
		if ( $args[ 'content' ] != 'sepa' )
			return;

		$filename = '';
		$parts = array( 'SEPA-Export' );

		$query_args = array(
			'post_type'   => 'shop_order',
			'post_status' => array_keys( wc_get_order_statuses() ),
			'orderby'	  => 'post_date',
			'order'		  => 'ASC',
			'meta_query'  => array(
				array(
					'key'     => '_payment_method',
					'value'   => 'direct-debit',
					'compare' => '=',
				),
			),
		);

		if ( isset( $args['order_id'] ) ) {

			$query_args[ 'p' ] = absint( $args['order_id'] );
			array_push( $parts, 'order-' . absint( $args['order_id'] ) );

		} else {

			$query_args = array_merge( $query_args, array(
				'showposts'   => -1,
				'date_query' => array(
					array(
						'after' => $args['start_date'],
						'before' => $args['end_date'],
						'inclusive' => true,
					),
				),
			) );

			if ( ! empty( $args['start_date'] ) )
				array_push( $parts, $args['start_date'] );
			if ( ! empty( $args['end_date'] ) )
				array_push( $parts, $args['end_date'] );

		}

		$order_query = new WP_Query( $query_args );
		$filename = apply_filters( 'woocommerce_germanized_direct_debit_export_filename', implode( '-', $parts ) . '.xml', $args );

		if ( $order_query->have_posts() ) {

			$msg_id = apply_filters( 'woocommerce_gzd_direct_debit_sepa_xml_msg_id', $this->company_account_bic . '00' . date( 'YmdHis', time() ) );
			$payment_id = 'PMT-ID-' . date( 'YmdHis', time() );
			
			$directDebit = Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory::createDirectDebit( $msg_id, $this->company_account_holder, $this->pain_format );
			$directDebit = apply_filters( 'woocommerce_gzd_direct_debit_sepa_xml_exporter', $directDebit );

			$directDebit->addPaymentInfo( $payment_id, array(
			    'id'                    => $payment_id,
			    'creditorName'          => $this->company_account_holder,
			    'creditorAccountIBAN'   => $this->clean_whitespaces( $this->company_account_iban ),
			    'creditorAgentBIC'      => $this->clean_whitespaces( $this->company_account_bic ),
			    'seqType'               => Digitick\Sepa\PaymentInformation::S_ONEOFF,
			    'creditorId'            => $this->clean_whitespaces( $this->company_identification_number ),
			));

			while ( $order_query->have_posts() ) {

				$order_query->next_post();
				$order = wc_get_order( $order_query->post->ID );
				
				// Add a Single Transaction to the named payment
				$directDebit->addTransfer( $payment_id, apply_filters( 'woocommerce_gzd_direct_debit_sepa_xml_exporter_transfer_args', array(
				    'amount'                => $order->get_total(),
				    'debtorIban'            => $this->clean_whitespaces( $this->maybe_decrypt( wc_gzd_get_crud_data( $order, 'direct_debit_iban' ) ) ),
				    'debtorBic'             => $this->clean_whitespaces( $this->maybe_decrypt( wc_gzd_get_crud_data( $order, 'direct_debit_bic' ) ) ), 
				    'debtorName'            => wc_gzd_get_crud_data( $order, 'direct_debit_holder' ),
				    'debtorMandate'         => $this->get_mandate_id( wc_gzd_get_crud_data( $order, 'id' ) ), date_i18n( "Y-m-d", strtotime( wc_gzd_get_crud_data( $order, 'order_date' ) ) ),
				    'debtorMandateSignDate' => date_i18n( 'd.m.Y', strtotime( wc_gzd_get_crud_data( $order, 'order_date' ) ) ),
				    'remittanceInformation' => apply_filters( 'woocommerce_germanized_direct_debit_purpose', sprintf( __( 'Order %s', 'woocommerce-germanized' ), $order->get_order_number() ), $order ),
				) ), $this, $order );
			}

			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
			header( 'Cache-Control: no-cache, no-store, must-revalidate' ); 
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			echo $directDebit->asXML();
			exit();

		}	
	 	
	}

	public function get_mandate_id( $order_id = false ) {

		if ( ! $order_id )
			return __( 'Will be notified separately', 'woocommerce-germanized' );

		$order = wc_get_order( $order_id );

		if ( wc_gzd_get_crud_data( $order, 'direct_debit_mandate_id' ) )
			return wc_gzd_get_crud_data( $order, 'direct_debit_mandate_id' );
		
		$mandate_id = apply_filters( 'woocommerce_germanized_direct_debit_mandate_id', ( $this->generate_mandate_id === 'yes' ? str_replace( '{id}', $order->get_order_number(), $this->mandate_id_format ) : '' ) );
		update_post_meta( wc_gzd_get_crud_data( $order, 'id' ), '_direct_debit_mandate_id', $mandate_id );
		
		return $mandate_id;
	}

    public function email_sepa( $order, $sent_to_admin, $plain_text ) {

    	if ( $this->id !== wc_gzd_get_crud_data( $order, 'payment_method' ) )
    		return;

    	$sepa_fields = array(
    		__( 'Account Holder', 'woocommerce-germanized' ) 	=> wc_gzd_get_crud_data( $order, 'direct_debit_holder' ),
    		__( 'IBAN', 'woocommerce-germanized' ) 				=> $this->mask( $this->maybe_decrypt( wc_gzd_get_crud_data( $order, 'direct_debit_iban' ) ) ),
     		__( 'BIC/SWIFT', 'woocommerce-germanized' ) 		=> $this->maybe_decrypt( wc_gzd_get_crud_data( $order, 'direct_debit_bic' ) ),
    	);

    	if ( $sent_to_admin ) {
    		$sepa_fields[ __( 'Mandate Reference ID', 'woocommerce-germanized' ) ] = $this->get_mandate_id( wc_gzd_get_crud_data( $order, 'id' ) );
    	}

    	wc_get_template( 'emails/email-sepa-data.php', array( 'fields' => $sepa_fields ) );

    }

    public function set_debit_fields( $fields ) {

    	global $post;

    	if ( ! $post || ! $order = wc_get_order( $post->ID ) )
    	    return $fields;

    	if ( wc_gzd_get_crud_data( $order, 'payment_method' ) !== $this->id )
    		return $fields;

    	$fields[ 'direct_debit_holder' ] = array(
    		'label' => __( 'Account Holder', 'woocommerce-germanized' ),
    		'id'  	=> '_direct_debit_holder',
    		'class' => '',
			'show'  => false,
    	);

    	$fields[ 'direct_debit_iban' ] = array(
    		'label' => __( 'IBAN', 'woocommerce-germanized' ),
    		'id'  	=> '_direct_debit_iban',
    		'value' => $this->maybe_decrypt( wc_gzd_get_crud_data( $order, 'direct_debit_iban' ) ),
			'show'  => false,
    	);

    	$fields[ 'direct_debit_bic' ] = array(
    		'label' => __( 'BIC/SWIFT', 'woocommerce-germanized' ),
    		'id'  	=> '_direct_debit_bic',
    		'value' => $this->maybe_decrypt( wc_gzd_get_crud_data( $order, 'direct_debit_bic' ) ),
			'show'  => false,
    	);

    	$fields[ 'direct_debit_mandate_id' ] = array(
    		'label' => __( 'Mandate Reference ID', 'woocommerce-germanized' ),
    		'id'  	=> '_direct_debit_mandate_id',
			'show'  => false,
    	);

    	return $fields;

    }

    public function send_mail( $order_id ) {

    	$order = wc_get_order( $order_id );

    	if ( wc_gzd_get_crud_data( $order, 'payment_method' ) == $this->id ) {
    		if ( $mail = WC_germanized()->emails->get_email_instance_by_id( 'customer_sepa_direct_debit_mandate' ) )
    			$mail->trigger( $order );
    	}

    }

    public function clean_whitespaces( $str ) {
    	return preg_replace('/\s+/', '', $str );
    }

    public function set_order_meta( $order_id, $posted ) {

    	$order = wc_get_order( $order_id );

    	if ( ! ( wc_gzd_get_crud_data( $order, 'payment_method' ) === $this->id ) )
    		return;

    	$holder 	= ( isset( $_POST[ 'direct_debit_account_holder' ] ) ? wc_clean( $_POST[ 'direct_debit_account_holder' ] ) : '' );
    	$iban 		= ( isset( $_POST[ 'direct_debit_account_iban' ] ) ? $this->maybe_encrypt( $this->clean_whitespaces( wc_clean( $_POST[ 'direct_debit_account_iban' ] ) ) ) : '' );
    	$bic 		= ( isset( $_POST[ 'direct_debit_account_bic' ] ) ? $this->maybe_encrypt( $this->clean_whitespaces( wc_clean( $_POST[ 'direct_debit_account_bic' ] ) ) ) : '' );

    	update_post_meta( wc_gzd_get_crud_data( $order, 'id' ), '_direct_debit_holder', $holder );
    	update_post_meta( wc_gzd_get_crud_data( $order, 'id' ), '_direct_debit_iban', $iban );
    	update_post_meta( wc_gzd_get_crud_data( $order, 'id' ), '_direct_debit_bic', $bic );

    	if ( ! $this->supports_encryption() || $this->remember === 'no' || ! wc_gzd_get_crud_data( $order, 'user_id' ) )
    		return;

    	update_user_meta( wc_gzd_get_crud_data( $order, 'user_id' ), 'direct_debit_holder', $holder );
    	update_user_meta( wc_gzd_get_crud_data( $order, 'user_id' ), 'direct_debit_iban', $iban );
    	update_user_meta( wc_gzd_get_crud_data( $order, 'user_id' ), 'direct_debit_bic', $bic );

    }

    public function add_email_template( $mails ) {
    	$mails[ 'WC_GZD_Email_Customer_SEPA_Direct_Debit_Mandate' ] = include WC_germanized()->plugin_path() . '/includes/emails/class-wc-gzd-email-customer-sepa-direct-debit-mandate.php';
    	return $mails;
    }

    public function generate_mandate() {

    	if ( ! $this->is_available() )
    		exit();

    	if ( ! isset( $_GET[ '_wpnonce' ] ) || ! wp_verify_nonce( $_GET[ '_wpnonce' ], 'show_direct_debit' ) )
    		exit();

    	$params = array(
    		'account_holder' 	=> wc_clean( isset( $_GET[ 'debit_holder' ] ) ? $_GET[ 'debit_holder' ] : '' ),
    		'account_iban' 		=> wc_clean( isset( $_GET[ 'debit_iban' ] ) ? $_GET[ 'debit_iban' ] : '' ),
     		'account_swift' 	=> wc_clean( isset( $_GET[ 'debit_swift' ] ) ? $_GET[ 'debit_swift' ] : '' ),
    		'street'			=> wc_clean( isset( $_GET[ 'address' ] ) ? $_GET[ 'address' ] : '' ),
			'postcode' 			=> wc_clean( isset( $_GET[ 'postcode' ] ) ? $_GET[ 'postcode' ] : '' ),
			'city' 				=> wc_clean( isset( $_GET[ 'city' ] ) ? $_GET[ 'city' ] : '' ),
			'country'			=> ( isset( $_GET[ 'country' ] ) && isset( WC()->countries->countries[ $_GET[ 'country' ] ] ) ? WC()->countries->countries[ $_GET[ 'country' ] ] : '' ),
		);

		echo $this->generate_mandate_text( $params );
		exit();

    }

    public function generate_mandate_by_order( $order ) {

    	if ( is_numeric( $order ) )
    		$order = wc_get_order( absint( $order ) );

    	$params = array(
    		'account_holder' 	=> wc_gzd_get_crud_data( $order, 'direct_debit_holder' ),
    		'account_iban' 		=> $this->mask( $this->maybe_decrypt( wc_gzd_get_crud_data( $order, 'direct_debit_iban' ) ) ),
     		'account_swift' 	=> $this->maybe_decrypt( wc_gzd_get_crud_data( $order, 'direct_debit_bic' ) ),
    		'street'			=> wc_gzd_get_crud_data( $order, 'billing_address_1' ),
			'postcode' 			=> wc_gzd_get_crud_data( $order, 'billing_postcode' ),
			'city' 				=> wc_gzd_get_crud_data( $order, 'billing_city' ),
			'country'			=> WC()->countries->countries[ wc_gzd_get_crud_data( $order, 'billing_country' ) ],
			'date'				=> date_i18n( wc_date_format(), strtotime( wc_gzd_get_crud_data( $order, 'order_date' ) ) ),
			'mandate_id'		=> $this->get_mandate_id( wc_gzd_get_crud_data( $order, 'id' ) ),
		);

		return $this->generate_mandate_text( $params );

    }

    public function mask( $data ) {
    	
    	if ( strlen( $data ) <= 4 || $this->get_option( 'mask' ) === 'no' )
    		return $data;
    	
    	return str_repeat( apply_filters( 'woocommerce_gzd_direct_debit_mask_char', '*' ), strlen( $data ) - 4 ) . substr( $data, -4 );
    }

    public function generate_mandate_text( $args = array() ) {

    	$args = wp_parse_args( $args, array(
    		'company_info' => $this->company_info,
    		'company_identification_number' => $this->company_identification_number,
    		'date' => date_i18n( wc_date_format(), strtotime( "now" ) ),
    		'mandate_id' => $this->get_mandate_id(),
    	) );

    	$text = $this->mandate_text;

    	foreach ( $args as $key => $val )
    		$text = str_replace( '[' . $key . ']', $val, $text );

    	return apply_filters( 'the_content', $text );

    }

    public function checkbox() {

    	if ( $this->is_available() && $this->enable_checkbox === 'yes' ) : ?>
    	
    		<p class="form-row legal direct-debit-checkbox">

    			<input type="checkbox" class="input-checkbox" name="direct_debit_legal" id="direct-debit-checkbox" />

    			<label class="checkbox" for="direct-debit-checkbox">
    				<?php echo $this->get_checkbox_label(); ?>
    				<a href="" rel="prettyPhoto" id="show-direct-debit-pretty" class="hidden"></a>
    			</label>
    			
    		</p>
    	
    	<?php endif; 
    }

    public function get_checkbox_label() {
    	$ajax_url = wp_nonce_url( add_query_arg( array( 'action' => 'show_direct_debit' ), admin_url( 'admin-ajax.php' ) ), 'show_direct_debit' );
    	return apply_filters( 'woocommerce_gzd_direct_debit_ajax_url', str_replace( array( '{link}', '{/link}' ), array( '<a href="' . $ajax_url . '" id="show-direct-debit-trigger" rel="prettyPhoto">', '</a>' ), $this->checkbox_label ), $this );
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {

    	$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-germanized' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Direct Debit Payment', 'woocommerce-germanized' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => _x( 'Title', 'gateway', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-germanized' ),
				'default'     => __( 'Direct Debit', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-germanized' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-germanized' ),
				'default'     => __( 'The order amount will be debited directly from your bank account.', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce-germanized' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce-germanized' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'company_info' => array(
				'title'       => __( 'Debtee', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'Insert your company information.', 'woocommerce-germanized' ),
				'default'     => '',
				'placeholder' => __( 'Company Inc, John Doe Street, New York', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'company_account_holder' => array(
				'title'       => __( 'Account Holder', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'Insert the bank account holder name.', 'woocommerce-germanized' ),
				'default'     => '',
				'placeholder' => __( 'Company Inc', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'company_account_iban' => array(
				'title'       => __( 'IBAN', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'Insert the bank account IBAN.', 'woocommerce-germanized' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'company_account_bic' => array(
				'title'       => __( 'BIC', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'Insert the bank account BIC.', 'woocommerce-germanized' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'company_identification_number' => array(
				'title'       => __( 'Debtee identification number', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Insert your debtee indentification number. More information can be found <a href="%s">here</a>.', 'woocommerce-germanized' ), 'http://www.bundesbank.de/Navigation/DE/Aufgaben/Unbarer_Zahlungsverkehr/SEPA/Glaeubiger_Identifikationsnummer/glaeubiger_identifikationsnummer.html' ),
				'default'     => '',
			),
			'generate_mandate_id' => array(
				'title'       => __( 'Generate Mandate ID', 'woocommerce-germanized' ),
				'type'        => 'checkbox',
				'label'		  => __( 'Automatically generate Mandate ID.', 'woocommerce-germanized' ),
				'description' => __( 'Automatically generate Mandate ID after order completion (based on Order ID).', 'woocommerce-germanized' ),
				'default'     => 'yes',
			),
			'pain_format' 		=> array(
				'title'       => __( 'XML Pain Format', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'You may adjust the XML Export Pain Schema to your banks needs. Some banks may require pain.001.003.03.', 'woocommerce-germanized' ),
				'default'     => 'pain.008.002.02',
			),
			'mandate_id_format' => array(
				'title'       => __( 'Mandate ID Format', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'You may extend the Mandate ID format by adding a prefix and/or suffix. Use {id} as placeholder to insert the automatically generated ID.', 'woocommerce-germanized' ),
				'default'     => 'MANDAT{id}',
			),
			'mandate_text' => array(
				'title'       => __( 'Mandate Text', 'woocommerce-germanized' ),
				'type'        => 'textarea',
				'description' => __( 'This text will be populated with live order/checkout data. Will be used as preview direct debit mandate and as email template text.', 'woocommerce-germanized' ),
				'default'     => '',
				'css'		  => 'min-height: 250px;',
				'desc_tip'    => true,
			),
			'enable_checkbox' => array(
				'title'       => __( 'Checkbox', 'woocommerce-germanized' ),
				'label'		  => __( 'Enable "agree to SEPA mandate" checkbox', 'woocommerce-germanized' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable a checkbox linking to a SEPA direct debit mandate preview.', 'woocommerce-germanized' ),
				'default'     => 'yes',
			),
			'checkbox_label' => array(
				'title'       => __( 'Checkbox label', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'Customize the checkbox label. Use {link}link name{/link} to insert the preview link.', 'woocommerce-germanized' ),
				'default'     => __( 'I hereby agree to the {link}direct debit mandate{/link}.', 'woocommerce-germanized' ),
				'desc_tip'	  => true,
			),
			'mask' => array(
				'title'       => __( 'Mask IBAN', 'woocommerce-germanized' ),
				'label'		  => __( 'Mask the IBAN within emails.', 'woocommerce-germanized' ),
				'type'        => 'checkbox',
				'description' => __( 'This will lead to masked IBANs within emails (replaced by *). All but last 4 digits will be masked.', 'woocommerce-germanized' ),
				'default'     => 'yes',
			),

		);

		if ( $this->supports_encryption() ) {

			$this->form_fields = array_merge( $this->form_fields, array( 'remember' => array(
				'title'       => __( 'Remember', 'woocommerce-germanized' ),
				'label'		  => __( 'Remember account data for returning customers.', 'woocommerce-germanized' ),
				'type'        => 'checkbox',
				'description' => __( 'Save account data as user meta if user has/creates a customer account.', 'woocommerce-germanized' ),
				'default'     => 'no',
			) ) );

		}

    }

    public function get_user_account_data( $user_id = '' ) {
    	
    	if ( empty( $user_id ) )
    		$user_id = get_current_user_id();

    	$data = array(
    		'holder' => '',
    		'iban'	 => '',
    		'bic'	 => '',
    	);

    	if ( $this->remember !== 'yes' )
    		return $data;

    	$data = array(
    		'holder' => $this->maybe_decrypt( get_user_meta( $user_id, 'direct_debit_holder', true ) ), 
    		'iban' => $this->maybe_decrypt( get_user_meta( $user_id, 'direct_debit_iban', true ) ),
    		'bic' => $this->maybe_decrypt( get_user_meta( $user_id, 'direct_debit_bic', true ) ),
    	);

    	return $data;
    }

    /**
	 * Payment form on checkout page
	 */
	public function payment_fields() {

		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}
		
		$account_data = $this->get_user_account_data();

		$fields = array(
			'account-holder' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-account-holder">' . __( 'Account Holder', 'woocommerce-germanized' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-account-holder" class="input-text wc-gzd-' . $this->id . '-account-holder" value="' . esc_attr( $account_data[ 'holder' ] ) . '" type="text" autocomplete="off" placeholder="" name="' . str_replace( '-', '_', $this->id ) . '_account_holder' . '" />
			</p>',
			'account-iban' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-account-iban">' . __( 'IBAN', 'woocommerce-germanized' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-account-iban" class="input-text wc-gzd-' . $this->id . '-account-iban" type="text" value="' . esc_attr( $account_data[ 'iban' ] ) . '" autocomplete="off" placeholder="" name="' . str_replace( '-', '_', $this->id ) . '_account_iban' . '" />
			</p>',
			'account-bic' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-account-bic">' . __( 'BIC/SWIFT', 'woocommerce-germanized' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-account-bic" class="input-text wc-gzd-' . $this->id . '-account-bic" type="text" value="' . esc_attr( $account_data[ 'bic' ] ) . '" autocomplete="off" placeholder="" name="' . str_replace( '-', '_', $this->id ) . '_account_bic' . '" />
			</p>',
		);

		?>
		<fieldset id="<?php echo $this->id; ?>-form">
			<?php do_action( 'woocommerce_gzd_direct_debit_form_start', $this->id ); ?>
			<?php
				foreach ( $fields as $field ) {
					echo $field;
				}
			?>
			<?php do_action( 'woocommerce_gzd_direct_debit_form_end', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<?php

	}

	public function validate_fields() { 
		
		if ( ! $this->is_available() || ! isset( $_POST[ 'payment_method' ] ) || $_POST[ 'payment_method' ] != $this->id )
			return;

		$iban = ( isset( $_POST[ 'direct_debit_account_iban' ] ) ? wc_clean( $_POST[ 'direct_debit_account_iban' ] ) : '' );
		$holder = ( isset( $_POST[ 'direct_debit_account_holder' ] ) ? wc_clean( $_POST[ 'direct_debit_account_holder' ] ) : '' );
		$bic = ( isset( $_POST[ 'direct_debit_account_bic' ] ) ? wc_clean( $_POST[ 'direct_debit_account_bic' ] ) : '' );
		$country = ( isset( $_POST[ 'billing_country' ] ) ? wc_clean( $_POST[ 'billing_country' ] ) : WC()->countries->get_base_country() );

		if ( empty( $iban ) || empty( $holder ) || empty( $bic ) ) {
			wc_add_notice( __( 'Please insert your SEPA account data.', 'woocommerce-germanized' ), 'error' );
			return false;
		}

		// Validate IBAN
		include_once( WC_germanized()->plugin_path() . '/includes/libraries/iban/oophp-iban.php' );

		$iban_validator = new IBAN( $iban );

		if ( ! $iban_validator->Verify() )
			wc_add_notice( __( 'Your IBAN seems to be invalid.', 'woocommerce-germanized' ), 'error' );
		else if ( $iban_validator->Country() != $country )
			wc_add_notice( __( 'Your IBAN\'s country code doesn’t match with your billing country.', 'woocommerce-germanized' ), 'error' );

		// Validate BIC
		if ( ! preg_match( '/^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/', $bic ) ) 
			wc_add_notice( __( 'Your BIC seems to be invalid.', 'woocommerce-germanized' ), 'error' );

		// Make sure that checkbox gets validated if on woocommerce_pay for order page
		if ( isset( $_POST['woocommerce_pay'] ) ) {
			$this->validate_checkbox();
		}

	}

	public function validate_checkbox() {

		if ( isset( $_POST[ 'payment_method' ] ) && $_POST[ 'payment_method' ] === $this->id && $this->enable_checkbox === 'yes' && ( ! isset( $_POST[ 'direct_debit_legal' ] ) && empty( $_POST[ 'direct_debit_legal' ] ) ) )
			wc_add_notice( __( 'Please accept the direct debit mandate.', 'woocommerce-germanized' ), 'error' );

	}

	/**
	 * payment_scripts function.
	 *
	 * Outputs scripts used for simplify payment
	 */
	public function payment_scripts() {
		
		if ( ! is_checkout() || ! $this->is_available() ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';

		// Ensure that prettyPhoto is being loaded
		wp_register_script( 'prettyPhoto_debit', $assets_path . 'js/prettyPhoto/jquery.prettyPhoto' . $suffix . '.js', array( 'jquery' ), '3.1.6', true );
		wp_enqueue_script( 'prettyPhoto_debit' );
		wp_register_style( 'woocommerce_prettyPhoto_css_debit', $assets_path . 'css/prettyPhoto.css' );
		wp_enqueue_style( 'woocommerce_prettyPhoto_css_debit' );

		wp_register_script( 'wc-gzd-iban', WC_germanized()->plugin_url() . '/includes/gateways/direct-debit/assets/js/iban' . $suffix . '.js', array( 'wc-checkout' ), WC_GERMANIZED_VERSION, true );
		wp_enqueue_script( 'wc-gzd-iban' );

		wp_register_script( 'wc-gzd-direct-debit', WC_germanized()->plugin_url() . '/includes/gateways/direct-debit/assets/js/direct-debit' . $suffix . '.js', array( 'wc-gzd-iban' ), WC_GERMANIZED_VERSION, true );
		wp_localize_script( 'wc-gzd-direct-debit', 'direct_debit_params', array(
			'iban'   		=> __( 'IBAN', 'woocommerce-germanized' ),
			'swift' 		=> __( 'BIC/SWIFT', 'woocommerce-germanized' ),
			'is_invalid'    => __( 'is invalid', 'woocommerce-germanized' ),
		) );
		wp_enqueue_script( 'wc-gzd-direct-debit' );
	}

    /**
     * Output for the order received page.
     */
	public function thankyou_page() {
		if ( $this->instructions )
        	echo wpautop( wptexturize( $this->instructions ) );
	}

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
        if ( $this->instructions && ! $sent_to_admin && 'direct-debit' === wc_gzd_get_crud_data( $order, 'payment_method' ) && $order->has_status( 'processing' ) ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}

    /**
     * Process the payment and return the result
     *
     * @param int $order_id
     * @return array
     */
	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status( 'on-hold', __( 'Awaiting Direct Debit Payment', 'woocommerce-germanized' ) );

		// Reduce stock level
        wc_gzd_reduce_order_stock( $order_id );

        // Check if cart instance exists (frontend request only)
        if ( WC()->cart ) {
	        // Remove cart
	        WC()->cart->empty_cart();
        }

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
		);
	}

	public function maybe_encrypt( $string ) {
		if ( $this->supports_encryption() ) {	
			return WC_GZD_Gateway_Direct_Debit_Encryption_Helper::instance()->encrypt( $string );
		}
		return $string;
	}

	public function maybe_decrypt( $string ) {
		if ( $this->supports_encryption() ) {
			$decrypted = WC_GZD_Gateway_Direct_Debit_Encryption_Helper::instance()->decrypt( $string );
			
			// Maxlength of IBAN is 30 - seems like we have an encrypted string (cannot be decrypted, maybe key changed)
			if ( strlen( $decrypted ) > 40 )
				return "";

			return $decrypted;
		}

		return $string;
	}

	public function supports_encryption() {

		global $wp_version;

		if ( version_compare( phpversion(), '5.4', '<' ) )
			return false; 
		if ( ! extension_loaded( 'openssl' ) )
			return false;
		if ( version_compare( $wp_version, '4.4', '<' ) )
			return false;

		require_once( 'class-wc-gzd-gateway-direct-debit-encryption-helper.php' );

		if ( ! WC_GZD_Gateway_Direct_Debit_Encryption_Helper::instance()->is_configured() )
			return false;

		return true;
	}

}
