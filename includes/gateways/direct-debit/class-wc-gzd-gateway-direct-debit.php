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
		$this->method_description = __( 'Allows you to offer direct debit as a payment method to your customers. Adds SEPA fields to checkout.', 'woocommerce-germanized' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->enabled          				= $this->get_option( 'enabled' );
		$this->title        					= $this->get_option( 'title' );
		$this->description  					= $this->get_option( 'description' );
		$this->instructions 					= $this->get_option( 'instructions', $this->description );
		$this->enable_checkbox					= $this->get_option( 'enable_checkbox', 'yes' );
		$this->company_info 					= $this->get_option( 'company_info' );
		$this->company_identification_number 	= $this->get_option( 'company_identification_number' );
		$this->checkbox_label					= $this->get_option( 'checkbox_label' );
		$this->mandate_text	   					= $this->get_option( 'mandate_text', __( '[company_info]
debtee identification number: [company_identification_number]
mandat reference number: will be notified separately.

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

		$this->supports         = array(
			'products',
		);

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    	add_action( 'woocommerce_thankyou_direct-debit', array( $this, 'thankyou_page' ) );
    	add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
    	add_action( 'woocommerce_review_order_after_payment', array( $this, 'checkbox' ), wc_gzd_get_hook_priority( 'checkout_direct_debit' ) );
    	add_action( 'wp_ajax_show_direct_debit', array( $this, 'generate_mandate' ) );
		add_action( 'wp_ajax_nopriv_show_direct_debit', array( $this, 'generate_mandate' ) );
		add_filter( 'woocommerce_email_classes', array( $this, 'add_email_template' ) );

		// Checkbox check
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkbox' ), 10, 1 );

		// Order Meta
    	add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'set_order_meta' ), 10, 2 );

    	// Customer Emails
    	add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
    	add_action( 'woocommerce_germanized_order_confirmation_sent', array( $this, 'send_mail' ) );

    	// Order admin
    	add_filter( 'woocommerce_admin_billing_fields', array( $this, 'set_debit_fields' ) ); 

    }

    public function set_debit_fields( $fields ) {

    	global $post;

    	$order = wc_get_order( $post->ID );

    	if ( ! $order->payment_method == $this->id )
    		return $fields;

    	$fields[ 'direct_debit_holder' ] = array(
    		'label' => __( 'Account Holder', 'woocommerce-germanized' ),
    		'id'  	=> '_direct_debit_holder',
    		'class' => '',
			'show'  => true,
    	);

    	$fields[ 'direct_debit_iban' ] = array(
    		'label' => __( 'IBAN', 'woocommerce-germanized' ),
    		'id'  	=> '_direct_debit_iban',
			'show'  => true,
    	);

    	$fields[ 'direct_debit_bic' ] = array(
    		'label' => __( 'BIC/SWIFT', 'woocommerce-germanized' ),
    		'id'  	=> '_direct_debit_bic',
			'show'  => true,
    	);

    	$fields[ 'direct_debit_reference' ] = array(
    		'label' => __( 'Mandate Reference ID', 'woocommerce-germanized' ),
    		'id'  	=> '_direct_debit_reference',
			'show'  => true,
    	);

    	return $fields;

    }

    public function send_mail( $order_id ) {

    	$order = wc_get_order( $order_id );

    	if ( $order->payment_method == $this->id )
    		WC()->mailer()->emails[ 'WC_GZD_Email_Customer_SEPA_Direct_Debit_Mandate' ]->trigger( $order );

    }

    public function set_order_meta( $order_id, $posted ) {

    	$order = wc_get_order( $order_id );

    	if ( ! $order->payment_method == $this->id )
    		return;

    	update_post_meta( $order->id, '_direct_debit_holder',  ( isset( $_POST[ 'direct_debit_account_holder' ] ) ? wc_clean( $_POST[ 'direct_debit_account_holder' ] ) : '' ) );
    	update_post_meta( $order->id, '_direct_debit_iban',  ( isset( $_POST[ 'direct_debit_account_iban' ] ) ? wc_clean( $_POST[ 'direct_debit_account_iban' ] ) : '' ) );
    	update_post_meta( $order->id, '_direct_debit_bic',  ( isset( $_POST[ 'direct_debit_account_bic' ] ) ? wc_clean( $_POST[ 'direct_debit_account_bic' ] ) : '' ) );

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
    		'account_holder' 	=> $order->direct_debit_holder,
    		'account_iban' 		=> $order->direct_debit_iban,
     		'account_swift' 	=> $order->direct_debit_bic,
    		'street'			=> $order->billing_address_1,
			'postcode' 			=> $order->billing_postcode,
			'city' 				=> $order->billing_city,
			'country'			=> WC()->countries->countries[ $order->billing_country ],
			'date'				=> date_i18n( wc_date_format(), strtotime( $order->post->post_date ) ),
		);

		return $this->generate_mandate_text( $params );

    }

    public function generate_mandate_text( $args = array() ) {

    	$args = wp_parse_args( $args, array(
    		'company_info' => $this->company_info,
    		'company_identification_number' => $this->company_identification_number,
    		'date' => date_i18n( wc_date_format(), strtotime( "now" ) ),
    	) );

    	$text = $this->mandate_text;

    	foreach ( $args as $key => $val )
    		$text = str_replace( '[' . $key . ']', $val, $text );

    	return apply_filters( 'the_content', $text );

    }

    public function checkbox() {

    	if ( $this->is_available() && $this->enable_checkbox === 'yes' ) : ?>
    	
    		<p class="form-row legal direct-debit-checkbox">
    			<label class="checkbox" for="direct-debit-checkbox">
    				<input type="checkbox" class="input-checkbox" name="direct_debit_legal" id="direct-debit-checkbox" />
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
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Direct Debit Payment', 'woocommerce-germanized' ),
				'default' => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'Direct Debit', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
				'default'     => __( 'The order amount will be debited directly from your bank account.', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce' ),
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
			'company_identification_number' => array(
				'title'       => __( 'Debtee identification number', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Insert your debtee indentification number. More information can be found <a href="%s">here</a>.', 'woocommerce-germanized' ), 'http://www.bundesbank.de/Navigation/DE/Aufgaben/Unbarer_Zahlungsverkehr/SEPA/Glaeubiger_Identifikationsnummer/glaeubiger_identifikationsnummer.html' ),
				'default'     => '',
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
		);
    }

    /**
	 * Payment form on checkout page
	 */
	public function payment_fields() {

		if ( $description = $this->get_description() ) {
			echo wpautop( wptexturize( $description ) );
		}

		$fields = array(
			'account-holder' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-account-holder">' . __( 'Account Holder', 'woocommerce-germanized' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-account-holder" class="input-text wc-gzd-' . $this->id . '-account-holder" type="text" autocomplete="off" placeholder="" name="' . str_replace( '-', '_', $this->id ) . '_account_holder' . '" />
			</p>',
			'account-iban' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-account-iban">' . __( 'IBAN', 'woocommerce-germanized' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-account-iban" class="input-text wc-gzd-' . $this->id . '-account-iban" type="text" autocomplete="off" placeholder="" name="' . str_replace( '-', '_', $this->id ) . '_account_iban' . '" />
			</p>',
			'account-bic' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-account-bic">' . __( 'BIC/SWIFT', 'woocommerce-germanized' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-account-bic" class="input-text wc-gzd-' . $this->id . '-account-bic" type="text" autocomplete="off" placeholder="" name="' . str_replace( '-', '_', $this->id ) . '_account_bic' . '" />
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

	public function validate_checkbox( $posted ) {

		if ( ! $this->is_available() || $this->enable_checkbox !== 'yes' || ! isset( $_POST[ 'payment_method' ] ) || $_POST[ 'payment_method' ] != $this->id )
			return;

		if ( ! isset( $_POST[ 'woocommerce_checkout_update_totals' ] ) ) {

			if ( ! isset( $_POST[ 'direct_debit_legal' ] ) && empty( $_POST[ 'direct_debit_legal' ] ) )
				wc_add_notice( __( 'Please accept the direct debit mandate.', 'woocommerce-germanized' ), 'error' );

		}

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

		wp_enqueue_script( 'prettyPhoto', $assets_path . 'js/prettyPhoto/jquery.prettyPhoto' . $suffix . '.js', array( 'jquery' ), '3.1.6', true );
		wp_enqueue_style( 'woocommerce_prettyPhoto_css', $assets_path . 'css/prettyPhoto.css' );

		wp_register_script( 'wc-gzd-iban', WC_germanized()->plugin_url() . '/includes/gateways/direct-debit/assets/js/iban' . $suffix . '.js', array( 'wc-checkout' ), WC_GERMANIZED_VERSION, true );
		wp_enqueue_script( 'wc-gzd-iban' );

		wp_register_script( 'wc-gzd-direct-debit', WC_germanized()->plugin_url() . '/includes/gateways/direct-debit/assets/js/direct-debit' . $suffix . '.js', array( 'wc-gzd-iban' ), WC_GERMANIZED_VERSION, true );
		wp_localize_script( 'wc-gzd-direct-debit', 'direct_debit_params', array(
			'iban'   		=> __( 'IBAN', 'woocommerce-germanized' ),
			'swift' 		=> __( 'BIC/SWIFT', 'woocommerce-germanized' ),
			'is_invalid'    => __( 'is invalid', 'woocommerce' ),
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
        if ( $this->instructions && ! $sent_to_admin && 'direct-debit' === $order->payment_method && $order->has_status( 'processing' ) ) {
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
		$order->update_status( 'processing', __( 'Processing direct debit', 'woocommerce-germanized' ) );

		// Reduce stock levels
		$order->reduce_order_stock();

		// Remove cart
		WC()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> $this->get_return_url( $order )
		);
	}
}
