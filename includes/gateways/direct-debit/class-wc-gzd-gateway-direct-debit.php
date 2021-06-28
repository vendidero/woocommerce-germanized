<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Direct Debit Payment Gateway
 *
 * Provides a Direct Debit Payment Gateway.
 *
 * @class        WC_GZD_Gateway_Direct_Debit
 * @extends        WC_Payment_Gateway
 * @version        2.1.0
 * @author        Vendidero, holzhannes
 */
class WC_GZD_Gateway_Direct_Debit extends WC_Payment_Gateway {

	public static $has_loaded = false;

	public $admin_fields = array();

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id = 'direct-debit';

		/**
		 * Filter to allow adding an icon to direct debit gateway.
		 *
		 * @param string $icon_url The icon URL.
		 *
		 * @since 1.8.5
		 *
		 */
		$this->icon               = apply_filters( 'woocommerce_gzd_direct_debit_icon', '' );
		$this->has_fields         = true;
		$this->method_title       = __( 'Direct Debit', 'woocommerce-germanized' );
		$this->method_description = sprintf( __( 'Allows you to offer direct debit as a payment method to your customers. Adds SEPA fields to checkout. %s', 'woocommerce-germanized' ), '<a class="button button-secondary" href="' . admin_url( 'export.php' ) . '">' . __( 'SEPA XML Bulk Export', 'woocommerce-germanized' ) . '</a>' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->enabled                       = $this->get_option( 'enabled' );
		$this->title                         = $this->get_option( 'title' );
		$this->description                   = $this->get_option( 'description' );
		$this->instructions                  = $this->get_option( 'instructions', $this->description );
		$this->enable_checkbox               = $this->get_option( 'enable_checkbox', 'yes' );
		$this->enable_pre_notification       = $this->get_option( 'enable_pre_notification', 'yes' );
		$this->debit_days                    = $this->get_option( 'debit_days', 5 );
		$this->generate_mandate_id           = $this->get_option( 'generate_mandate_id', 'yes' );
		$this->mandate_id_format             = $this->get_option( 'mandate_id_format', 'MANDAT{id}' );
		$this->company_info                  = $this->get_option( 'company_info' );
		$this->company_identification_number = $this->get_option( 'company_identification_number' );
		$this->company_account_holder        = $this->get_option( 'company_account_holder' );
		$this->company_account_iban          = $this->get_option( 'company_account_iban' );
		$this->company_account_bic           = $this->get_option( 'company_account_bic' );
		$this->pain_format                   = $this->get_option( 'pain_format', 'pain.008.002.02' );
		$this->checkbox_label                = $this->get_option( 'checkbox_label' );
		$this->remember                      = $this->get_option( 'remember', 'no' );
		$this->mask                          = $this->get_option( 'mask', 'yes' );
		$this->mandate_text                  = $this->get_option( 'mandate_text', __( '[company_info]
debtee identification number: [company_identification_number]
mandat reference number: [mandate_id].

<h3>SEPA Direct Debit Mandate</h3>

I hereby authorize the payee to [mandate_type_text] draft from my savings account listed below for the specified amount. I further authorize my bank to accept the direct debit from this account.

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

		if ( ! self::$has_loaded ) {
			$this->init();
		}

		$this->admin_fields = array(
			'direct_debit_holder'     => array(
				'label'   => __( 'Account Holder', 'woocommerce-germanized' ),
				'id'      => '_direct_debit_holder',
				'class'   => '',
				'type'    => 'text',
				'encrypt' => false,
			),
			'direct_debit_iban'       => array(
				'label'   => __( 'IBAN', 'woocommerce-germanized' ),
				'id'      => '_direct_debit_iban',
				'type'    => 'text',
				'encrypt' => true,
				'toupper' => true,
			),
			'direct_debit_bic'        => array(
				'label'   => __( 'BIC/SWIFT', 'woocommerce-germanized' ),
				'id'      => '_direct_debit_bic',
				'type'    => 'text',
				'encrypt' => true,
				'toupper' => true,
			),
			'direct_debit_mandate_id' => array(
				'label'   => __( 'Mandate Reference ID', 'woocommerce-germanized' ),
				'id'      => '_direct_debit_mandate_id',
				'type'    => 'text',
				'encrypt' => false,
			),
		);

		self::$has_loaded = true;
	}

	public function init() {
		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		add_action( 'woocommerce_thankyou_direct-debit', array( $this, 'thankyou_page' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

		add_action( 'woocommerce_gzd_legal_checkbox_checkout_sepa_validate', array( $this, 'validate_checkbox' ) );
		add_action( 'woocommerce_gzd_legal_checkbox_pay_for_order_sepa_validate', array(
			$this,
			'validate_pay_order_checkbox'
		) );

		// Order Meta - use woocommerce_checkout_update_order_meta to make sure order id exists when updating mandate id
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'set_order_meta' ), 10, 1 );
		add_action( 'woocommerce_before_pay_action', array( $this, 'on_pay_for_order' ), 10, 1 );
		add_action( 'woocommerce_scheduled_subscription_payment', array( $this, 'set_order_meta' ), 10, 2 );

		// Customer Emails
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'woocommerce_germanized_order_confirmation_sent', array( $this, 'send_mail' ) );
		add_action( 'woocommerce_email_customer_details', array( $this, 'email_sepa' ), 15, 3 );

		// Order admin
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'print_debit_fields' ), 10, 1 );
		add_action( 'woocommerce_before_order_object_save', array( $this, 'save_debit_fields' ), 10, 1 );

		add_action( 'wp_ajax_show_direct_debit', array( $this, 'generate_mandate' ) );
		add_action( 'wp_ajax_nopriv_show_direct_debit', array( $this, 'generate_mandate' ) );

		// Admin order table download actions
		add_filter( 'woocommerce_admin_order_actions', array( $this, 'order_actions' ), 0, 2 );

		// Export filters
		add_action( 'export_filters', array( $this, 'export_view' ) );
		add_action( 'export_wp', array( $this, 'export' ), 0, 1 );
		add_filter( 'export_args', array( $this, 'export_args' ), 0, 1 );
	}

	public function validate_pay_order_checkbox() {
		return $this->validate_checkbox();
	}

	/**
	 * @param WC_Order $order
	 */
	public function print_debit_fields( $order ) {
		if ( $order->get_payment_method() !== $this->id ) {
			return;
		}
		?>
        <h3 id="gzd-admin-sepa">
			<?php _e( 'SEPA', 'woocommerce-germanized' ); ?>

			<?php if ( ! wc_gzd_order_is_anonymized( $order ) ) : ?>
                <a href="<?php echo add_query_arg( array(
					'download'      => 'true',
					'content'       => 'sepa',
					'sepa_order_id' => $order->get_id()
				), admin_url( 'export.php' ) ); ?>" target="_blank"
                   class="download_sepa_xml"><?php _e( 'SEPA XML', 'woocommerce-germanized' ); ?></a>
			<?php endif; ?>
        </h3>

		<?php foreach ( $this->admin_fields as $key => $field ) :
			$field['value'] = $this->maybe_decrypt( $order->get_meta( $field['id'] ) );

			switch ( $field['type'] ) {
				case 'select' :
					woocommerce_wp_select( $field );
					break;
				default :
					woocommerce_wp_text_input( $field );
					break;
			}
			?>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * @param WC_Order $order
	 */
	public function save_debit_fields( $order ) {

		// Check the nonce
		if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) {
			return;
		}

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( $order->get_payment_method() !== $this->id ) {
			return;
		}

		foreach ( $this->admin_fields as $key => $field ) {
			if ( isset( $_POST[ $field['id'] ] ) ) {
				$data = wc_clean( $_POST[ $field['id'] ] );

				if ( ! empty( $data ) && isset( $field['toupper'] ) && $field['toupper'] ) {
					$data = strtoupper( $data );
				}

				if ( ! empty( $data ) && $field['encrypt'] ) {
					$data = $this->maybe_encrypt( $data );
				}

				$order->update_meta_data( $field['id'], $data );
			}
		}
	}

	/**
	 * @param $actions
	 * @param WC_Order $order
	 *
	 * @return mixed
	 */
	public function order_actions( $actions, $order ) {

		if ( ! wc_gzd_order_is_anonymized( $order ) && $order->get_payment_method() === $this->id ) {
			$actions['download-sepa'] = array(
				'url'    => add_query_arg( array(
					'download'      => 'true',
					'content'       => 'sepa',
					'sepa_order_id' => $order->get_id()
				), admin_url( 'export.php' ) ),
				'name'   => __( 'SEPA XML Export', 'woocommerce-germanized' ),
				'action' => "xml"
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
				$args['start_date']  = ( isset( $_GET['sepa_start_date'] ) ? sanitize_text_field( $_GET['sepa_start_date'] ) : '' );
				$args['end_date']    = ( isset( $_GET['sepa_end_date'] ) ? sanitize_text_field( $_GET['sepa_end_date'] ) : '' );
				$args['unpaid_only'] = ( isset( $_GET['sepa_unpaid_only'] ) ? 1 : 0 );
			} elseif ( isset( $_GET['sepa_order_id'] ) ) {
				$args['order_id'] = absint( $_GET['sepa_order_id'] );
			}
		}

		return $args;
	}

	public function export( $args = array() ) {
		if ( $args['content'] != 'sepa' ) {
			return;
		}

		$filename = '';
		$parts    = array( 'SEPA-Export' );

		$meta_query = array(
			array(
				'key'     => '_payment_method',
				'value'   => 'direct-debit',
				'compare' => '=',
			),
		);

		if ( isset( $args['unpaid_only'] ) && $args['unpaid_only'] === 1 ) {
		    $meta_query[] = array(
		        'relation' => 'OR',
			    array(
				    'key'     => '_date_completed',
				    'compare' => 'NOT EXISTS'
			    ),
			    array(
				    'key'     => '_date_completed',
				    'compare' => '=',
                    'value'   => '',
			    ),
            );
		}

		$query_args = array(
			'post_type'   => 'shop_order',
			'post_status' => array_keys( wc_get_order_statuses() ),
			'orderby'     => 'post_date',
			'order'       => 'ASC',
			'meta_query'  => $meta_query,
		);

		if ( isset( $args['order_id'] ) ) {
			$query_args['p'] = absint( $args['order_id'] );
			array_push( $parts, 'order-' . absint( $args['order_id'] ) );
		} else {
			$query_args = array_merge( $query_args, array(
				'showposts'   => - 1,
				/**
				 * Filter to adjust direct debit export valid order statuses.
				 *
				 * @param array $order_statuses Valid order statuses to be exported.
				 *
				 * @since 1.8.5
				 */
				'post_status' => apply_filters( 'woocommerce_gzd_direct_debit_export_order_statuses', array(
					'wc-pending',
					'wc-processing',
					'wc-on-hold'
				) ),
				'date_query'  => array(
					array(
						'after'     => $args['start_date'],
						'before'    => $args['end_date'],
						'inclusive' => true,
					),
				),
			) );
		}

		/**
		 * Filter to adjust direct debit export arguments passed to `WP_Query`.
		 *
		 * @param array $query_args The query arguments.
		 * @param array $args Export arguments.
		 *
		 * @since 1.8.5
		 *
		 */
		$order_query = new WP_Query( apply_filters( 'woocommerce_gzd_direct_debit_export_query_args', $query_args, $args ) );

		/**
		 * Filter that allows adjusting the direct debit export filename.
		 *
		 * @param string $filename The filename.
		 * @param array $args The export arguments.
		 *
		 * @since 1.8.5
		 *
		 */
		$filename = apply_filters( 'woocommerce_germanized_direct_debit_export_filename', implode( '-', $parts ) . '.xml', $args );

		$directDebit      = false;
		$direct_debit_xml = '';

		if ( $order_query->have_posts() ) {

			/**
			 * Filter to adjust direct debit SEPA XML message id.
			 *
			 * @param string $message_id The message id.
			 *
			 * @since 1.8.5
			 *
			 */
			$msg_id      = apply_filters( 'woocommerce_gzd_direct_debit_sepa_xml_msg_id', $this->company_account_bic . '00' . date( 'YmdHis', time() ) );
			$directDebit = Digitick\Sepa\TransferFile\Factory\TransferFileFacadeFactory::createDirectDebit( $msg_id, $this->company_account_holder, $this->pain_format );

			/**
			 * Filter to adjust the direct debit SEPA XML exporter instance.
			 *
			 * @param Digitick\Sepa\TransferFile\Facade\CustomerDirectDebitFacade $directDebit Exporter instance.
			 *
			 * @since 1.8.5
			 *
			 */
			$directDebit = apply_filters( 'woocommerce_gzd_direct_debit_sepa_xml_exporter', $directDebit );

			// Group orders by their mandate type to only add one payment per mandate type group.
			$mandate_type_groups = array();

			/**
			 * The XML to output
			 */
			$direct_debit_xml = '';

			while ( $order_query->have_posts() ) {

				$order_query->next_post();
				$order = wc_get_order( $order_query->post->ID );

				if ( ! $order ) {
					continue;
				}

				$mandate_type = $this->get_mandate_type( $order );

				if ( ! array_key_exists( $mandate_type, $mandate_type_groups ) ) {
					$mandate_type_groups[ $mandate_type ] = array();
				}

				array_push( $mandate_type_groups[ $mandate_type ], $order );
			}

			try {
				foreach ( $mandate_type_groups as $mandate_type => $orders ) {

					$payment_id = 'PMT-ID-' . date( 'YmdHis', time() ) . '-' . strtolower( $mandate_type );

					/**
					 * Filter that allows adjusting direct debit SEPA XML Export payment data.
					 *
					 * @param array $args Payment arguments.
					 * @param WC_GZD_Gateway_Direct_Debit $gateway The gateway instance.
					 * @param string $mandate_type The mandate type.
					 *
					 * @since 1.8.5
					 *
					 */
					$directDebit->addPaymentInfo( $payment_id, apply_filters( 'woocommerce_gzd_direct_debit_sepa_xml_exporter_payment_args', array(
						'id'                  => $payment_id,
						'creditorName'        => $this->company_account_holder,
						'creditorAccountIBAN' => strtoupper( $this->clean_whitespaces( $this->company_account_iban ) ),
						'creditorAgentBIC'    => strtoupper( $this->clean_whitespaces( $this->company_account_bic ) ),
						'seqType'             => $mandate_type,
						'creditorId'          => $this->clean_whitespaces( $this->company_identification_number ),
						'dueDate'             => date_i18n( 'Y-m-d', $this->get_debit_date( $order ) ),
					), $this, $mandate_type ) );

					foreach ( $orders as $order ) {

						/**
						 * Filter that allows adjusting direct debit SEPA XML Export transfer data per order.
						 *
						 * @param array $args Transfer data.
						 * @param WC_GZD_Gateway_Direct_Debit $gateway The gateway instance.
						 * @param WC_Order $order The order object.
						 *
						 * @since 1.8.5
						 *
						 */
						$directDebit->addTransfer( $payment_id, apply_filters( 'woocommerce_gzd_direct_debit_sepa_xml_exporter_transfer_args', array(
							'amount'                => ( $order->get_total() - $order->get_total_refunded() ),
							'debtorIban'            => strtoupper( $this->clean_whitespaces( $this->maybe_decrypt( $order->get_meta( '_direct_debit_iban' ) ) ) ),
							'debtorBic'             => strtoupper( $this->clean_whitespaces( $this->maybe_decrypt( $order->get_meta( '_direct_debit_bic' ) ) ) ),
							'debtorName'            => $order->get_meta( '_direct_debit_holder' ),
							'debtorMandate'         => $this->get_mandate_id( $order ),
							'debtorMandateSignDate' => date_i18n( 'Y-m-d', $this->get_mandate_sign_date( $order ) ),
							/**
							 * Filter that allows adjusting the purpose of a SEPA direct debit.
							 *
							 * @param string $purpose The SEPA purpose.
							 * @param WC_Order $order The order object.
							 *
							 * @since 1.8.5
							 *
							 */
							'remittanceInformation' => apply_filters( 'woocommerce_germanized_direct_debit_purpose', sprintf( __( 'Order %s', 'woocommerce-germanized' ), $order->get_order_number() ), $order ),
						), $this, $order ) );
					}
				}

				/**
				 * Generate XML
				 */
				$direct_debit_xml = $directDebit->asXML();
            } catch( Exception $e ) {
			    wp_die( $e->getMessage() );
            }
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		echo $direct_debit_xml;
		exit();
	}

	/**
	 * @param WC_Order|false $order
	 *
	 * @return mixed|void
	 */
	public function get_mandate_id( $order = false ) {
		if ( ! $order ) {
			$id = __( 'Will be notified separately', 'woocommerce-germanized' );
		} else {
			$mandate_id = $order->get_meta( '_direct_debit_mandate_id' );

			if ( $mandate_id && ! empty( $mandate_id ) ) {
				$id = $mandate_id;
			} else {
				$id = ( $this->generate_mandate_id === 'yes' ? str_replace( '{id}', $order->get_order_number(), $this->mandate_id_format ) : '' );
			}
		}

		/**
		 * Filter to adjust the direct debit mandate id.
		 *
		 * @param string $id The mandate id.
		 * @param bool|WC_Order $order The order if available. `false` otherwise.
		 *
		 * @since 1.8.5
		 *
		 */
		return apply_filters( 'woocommerce_germanized_direct_debit_mandate_id', $id, $order );
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return false|int
	 */
	public function get_debit_date( $order ) {
		$order_date_formatted = $order->get_date_created()->format( 'Y-m-d' );

		return strtotime( '+' . $this->debit_days . ' days', strtotime( $order_date_formatted ) );
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return false|int|null
	 */
	public function get_mandate_sign_date( $order ) {
		$date = $order->get_meta( '_direct_debit_mandate_date' ) ? $order->get_meta( '_direct_debit_mandate_date' ) : $order->get_date_created()->getTimestamp();

		return $date;
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public function get_mandate_type( $order ) {
		$type = $order->get_meta( '_direct_debit_mandate_type' );

		return ( empty( $type ) ? Digitick\Sepa\PaymentInformation::S_ONEOFF : $type );
	}

	/**
	 * @param WC_Order $order
	 * @param $sent_to_admin
	 * @param $plain_text
	 */
	public function email_sepa( $order, $sent_to_admin, $plain_text ) {

		if ( $this->id !== $order->get_payment_method() ) {
			return;
		}

		$sepa_fields = array(
			__( 'Account Holder', 'woocommerce-germanized' ) => $order->get_meta( '_direct_debit_holder' ),
			__( 'IBAN', 'woocommerce-germanized' )           => $this->mask( $this->maybe_decrypt( $order->get_meta( '_direct_debit_iban' ) ) ),
			__( 'BIC/SWIFT', 'woocommerce-germanized' )      => $this->maybe_decrypt( $order->get_meta( '_direct_debit_bic' ) ),
		);

		if ( $sent_to_admin ) {
			$sepa_fields[ __( 'Mandate Reference ID', 'woocommerce-germanized' ) ] = $this->get_mandate_id( $order );
		}

		$debit_date = $this->get_debit_date( $order );

		/**
		 * Filter to adjust the direct debit pre notification text.
		 *
		 * @param string $text The notification text.
		 * @param WC_Order $order The order object.
		 * @param int $debit_date The debit date as timestamp.
		 *
		 * @since 1.8.5
		 *
		 */
		$pre_notification_text = apply_filters( 'woocommerce_gzd_direct_debit_pre_notification_text', sprintf( __( 'We will debit %s from your account by direct debit on or shortly after %s.', 'woocommerce-germanized' ), wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ), date_i18n( wc_date_format(), $debit_date ) ), $order, $debit_date );

		wc_get_template( 'emails/email-sepa-data.php', array(
			'fields'                => $sepa_fields,
			'send_pre_notification' => apply_filters( 'woocommerce_gzd_direct_debit_send_pre_notification', ( $this->enable_pre_notification === 'yes' && ! $sent_to_admin ), $this ),
			'pre_notification_text' => $pre_notification_text,
		) );
	}

	public function set_debit_fields( $fields ) {
		global $post;

		if ( ! $post || ! $order = wc_get_order( $post->ID ) ) {
			return $fields;
		}

		if ( $order->get_payment_method() !== $this->id ) {
			return $fields;
		}

		return $fields;
	}

	public function send_mail( $order_id ) {
	    if ( $order = wc_get_order( $order_id ) ) {
		    if ( $order->get_payment_method() === $this->id ) {

			    if ( $mail = WC_germanized()->emails->get_email_instance_by_id( 'customer_sepa_direct_debit_mandate' ) ) {
				    $mail->trigger( $order );
			    }
		    }
        }
	}

	public function clean_whitespaces( $str ) {
		return preg_replace( '/\s+/', '', $str );
	}

	/**
	 * @param WC_Order $order
	 */
	public function on_pay_for_order( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order ) {
			return;
		}

		$payment_method_id = isset( $_POST['payment_method'] ) ? wc_clean( wp_unslash( $_POST['payment_method'] ) ) : false;

		if ( $payment_method_id !== $this->id) {
			return;
		}

		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		$payment_method     = isset( $available_gateways[ $payment_method_id ] ) ? $available_gateways[ $payment_method_id ] : false;

		if ( ! $payment_method ) {
			return;
		}

		$this->update_order( $order );
	}

	/**
	 * @param WC_Order $order
	 */
	protected function update_order( $order, $save = false ) {
		$holder  = ( isset( $_POST['direct_debit_account_holder'] ) ? wc_clean( $_POST['direct_debit_account_holder'] ) : '' );
		$iban    = ( isset( $_POST['direct_debit_account_iban'] ) ? $this->maybe_encrypt( strtoupper( $this->clean_whitespaces( wc_clean( $_POST['direct_debit_account_iban'] ) ) ) ) : '' );
		$bic     = ( isset( $_POST['direct_debit_account_bic'] ) ? $this->maybe_encrypt( strtoupper( $this->clean_whitespaces( wc_clean( $_POST['direct_debit_account_bic'] ) ) ) ) : '' );
		$user_id = $order->get_customer_id();

		// Always save account details to order
		$order->update_meta_data( '_direct_debit_holder', $holder );
		$order->update_meta_data( '_direct_debit_iban', $iban );
		$order->update_meta_data( '_direct_debit_bic', $bic );

		// Generate mandate id if applicable
		$mandate_id = $this->get_mandate_id( $order );

		$order->update_meta_data( '_direct_debit_mandate_id', $mandate_id );
		$order->update_meta_data( '_direct_debit_mandate_type', Digitick\Sepa\PaymentInformation::S_ONEOFF );
		$order->update_meta_data( '_direct_debit_mandate_date', current_time( 'timestamp', true ) );
		$order->update_meta_data( '_direct_debit_mandate_mail', $order->get_billing_email() );

		if ( $save ) {
			// Save the order data
			$order->save();
		}

		/**
		 * Updated direct debit order data.
		 *
		 * Fires after Germanized has updated direct debit data for a specific order.
		 *
		 * @param WC_Order $order The order object.
		 * @param int $user_id The user id.
		 * @param WC_GZD_Gateway_Direct_Debit $this The gateway instance.
		 *
		 * @since 1.9.2
		 *
		 */
		do_action( 'woocommerce_gzd_direct_debit_order_data_updated', $order, $user_id, $this );

		if ( $this->supports_encryption() && $this->remember === 'yes' && ! empty( $user_id ) && ! empty( $iban ) ) {

			update_user_meta( $user_id, 'direct_debit_holder', $holder );
			update_user_meta( $user_id, 'direct_debit_iban', $iban );
			update_user_meta( $user_id, 'direct_debit_bic', $bic );

			/**
			 * Updated direct debit user data.
			 *
			 * Fires after Germanized has updated direct debit data for a specific user.
			 *
			 * @param WC_Order $order The order object.
			 * @param int $user_id The user id.
			 * @param WC_GZD_Gateway_Direct_Debit $this The gateway instance.
			 *
			 * @since 1.9.2
			 *
			 */
			do_action( 'woocommerce_gzd_direct_debit_user_data_updated', $order, $user_id, $this );
		}
	}

	/**
	 * @param WC_Order $order
	 */
	public function set_order_meta( $order ) {

	    if ( is_numeric( $order ) ) {
	        $order = wc_get_order( $order );
        }

	    if ( ! $order ) {
	        return;
        }

		if ( $order->get_payment_method() !== $this->id) {
			return;
		}

		$this->update_order( $order, true );
	}

	public function generate_mandate() {

		if ( ! $this->is_available() ) {
			exit();
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'show_direct_debit' ) ) {
			exit();
		}

		$params = array(
			'account_holder'    => wc_clean( isset( $_GET['debit_holder'] ) ? $_GET['debit_holder'] : '' ),
			'account_iban'      => strtoupper( wc_clean( isset( $_GET['debit_iban'] ) ? $_GET['debit_iban'] : '' ) ),
			'account_swift'     => strtoupper( wc_clean( isset( $_GET['debit_swift'] ) ? $_GET['debit_swift'] : '' ) ),
			'street'            => wc_clean( isset( $_GET['address'] ) ? $_GET['address'] : '' ),
			'postcode'          => wc_clean( isset( $_GET['postcode'] ) ? $_GET['postcode'] : '' ),
			'city'              => wc_clean( isset( $_GET['city'] ) ? $_GET['city'] : '' ),
			'country'           => ( isset( $_GET['country'] ) && isset( WC()->countries->countries[ $_GET['country'] ] ) ? WC()->countries->countries[ $_GET['country'] ] : '' ),
			/**
			 * Filter to adjust the default mandate type text.
			 *
			 * @param string $text The mandate type text.
			 *
			 * @since 1.8.5
			 *
			 */
			'mandate_type_text' => apply_filters( 'woocommerce_gzd_direct_debit_mandate_type_text', __( 'a single payment', 'woocommerce-germanized' ) ),
		);

		$order_key = isset( $_GET['order_key'] ) ? wc_clean( wp_unslash( $_GET['order_key'] ) ) : '';

		if ( ! empty( $order_key ) ) {
            $order_id = wc_get_order_id_by_order_key( $order_key );

		    if ( $order_id && ( $order = wc_get_order( $order_id ) ) ) {
			    if ( current_user_can( 'pay_for_order', $order_id ) ) {
				    $params['street']   = $order->get_billing_address_1();
				    $params['postcode'] = $order->get_billing_postcode();
				    $params['city']     = $order->get_billing_city();
				    $params['country']  = $order->get_billing_country();
			    }
		    }
		}

		echo $this->generate_mandate_text( $params );
		exit();
	}

	public function generate_mandate_by_order( $order ) {

		if ( is_numeric( $order ) ) {
			$order = wc_get_order( absint( $order ) );
		}

		$params = array(
			'account_holder'    => $order->get_meta( '_direct_debit_holder' ),
			'account_iban'      => $this->mask( $this->maybe_decrypt( $order->get_meta( '_direct_debit_iban' ) ) ),
			'account_swift'     => $this->maybe_decrypt( $order->get_meta( '_direct_debit_bic' ) ),
			'street'            => $order->get_billing_address_1(),
			'postcode'          => $order->get_billing_postcode(),
			'city'              => $order->get_billing_city(),
			'country'           => WC()->countries->countries[ $order->get_billing_country() ],
			'date'              => date_i18n( wc_date_format(), $this->get_mandate_sign_date( $order ) ),
			'mandate_id'        => $this->get_mandate_id( $order ),
			/**
			 * Filter to adjust mandate type text for a certain order.
			 *
			 * @param string $text The mandate type text.
			 * @param WC_Order $order The order object.
			 *
			 * @since 1.8.5
			 *
			 */
			'mandate_type_text' => apply_filters( 'woocommerce_gzd_direct_debit_mandate_type_order_text', __( 'a single payment', 'woocommerce-germanized' ), $order ),
		);

		return $this->generate_mandate_text( $params );
	}

	public function mask( $data ) {
		if ( strlen( $data ) <= 4 || $this->get_option( 'mask' ) === 'no' ) {
			return $data;
		}

		/**
		 * Filter to adjust the char replacement for masked direct debit fields.
		 *
		 * @param string $char The char to masked data e.g. `*`.
		 *
		 * @since 1.8.5
		 *
		 */
		return str_repeat( apply_filters( 'woocommerce_gzd_direct_debit_mask_char', '*' ), strlen( $data ) - 4 ) . substr( $data, - 4 );
	}

	public function generate_mandate_text( $args = array() ) {

		// temporarily reset global $post variable if available to ensure Pagebuilder compatibility
		$tmp_post        = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : false;
		$GLOBALS['post'] = false;

		$args = wp_parse_args( $args, array(
			'company_info'                  => $this->company_info,
			'company_identification_number' => $this->company_identification_number,
			'date'                          => date_i18n( wc_date_format(), strtotime( "now" ) ),
			'mandate_id'                    => $this->get_mandate_id(),
			'mandate_type_text'             => __( 'a single payment', 'woocommerce-germanized' ),
		) );

		$text = $this->mandate_text;

		foreach ( $args as $key => $val ) {
			$text = str_replace( '[' . $key . ']', $val, $text );
		}

		$content = apply_filters( 'the_content', $text );

		// Enable $post again
		$GLOBALS['post'] = $tmp_post;

		return $content;
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled'                       => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-germanized' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Direct Debit Payment', 'woocommerce-germanized' ),
				'default' => 'no'
			),
			'title'                         => array(
				'title'       => _x( 'Title', 'gateway', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-germanized' ),
				'default'     => __( 'Direct Debit', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'description'                   => array(
				'title'       => __( 'Description', 'woocommerce-germanized' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce-germanized' ),
				'default'     => __( 'The order amount will be debited directly from your bank account.', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'instructions'                  => array(
				'title'       => __( 'Instructions', 'woocommerce-germanized' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page and emails.', 'woocommerce-germanized' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'company_info'                  => array(
				'title'       => __( 'Debtee', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'Insert your company information.', 'woocommerce-germanized' ),
				'default'     => '',
				'placeholder' => __( 'Company Inc, John Doe Street, New York', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'company_account_holder'        => array(
				'title'       => __( 'Account Holder', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'Insert the bank account holder name.', 'woocommerce-germanized' ),
				'default'     => '',
				'placeholder' => __( 'Company Inc', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'company_account_iban'          => array(
				'title'       => __( 'IBAN', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'Insert the bank account IBAN.', 'woocommerce-germanized' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'company_account_bic'           => array(
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
			'generate_mandate_id'           => array(
				'title'       => __( 'Generate Mandate ID', 'woocommerce-germanized' ),
				'type'        => 'checkbox',
				'label'       => __( 'Automatically generate Mandate ID.', 'woocommerce-germanized' ),
				'description' => __( 'Automatically generate Mandate ID after order completion (based on Order ID).', 'woocommerce-germanized' ),
				'default'     => 'yes',
			),
			'pain_format'                   => array(
				'title'       => __( 'XML Pain Format', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'You may adjust the XML Export Pain Schema to your banks needs. Some banks may require pain.001.003.03.', 'woocommerce-germanized' ),
				'default'     => 'pain.008.002.02',
			),
			'mandate_id_format'             => array(
				'title'       => __( 'Mandate ID Format', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'You may extend the Mandate ID format by adding a prefix and/or suffix. Use {id} as placeholder to insert the automatically generated ID.', 'woocommerce-germanized' ),
				'default'     => 'MANDAT{id}',
			),
			'mandate_text'                  => array(
				'title'       => __( 'Mandate Text', 'woocommerce-germanized' ),
				'type'        => 'textarea',
				'description' => __( 'This text will be populated with live order/checkout data. Will be used as preview direct debit mandate and as email template text.', 'woocommerce-germanized' ),
				'default'     => '',
				'css'         => 'min-height: 250px;',
				'desc_tip'    => true,
			),
			'enable_checkbox'               => array(
				'title'       => __( 'Checkbox', 'woocommerce-germanized' ),
				'label'       => __( 'Enable "agree to SEPA mandate" checkbox', 'woocommerce-germanized' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable a checkbox linking to a SEPA direct debit mandate preview.', 'woocommerce-germanized' ),
				'default'     => 'yes',
			),
			'checkbox_label'                => array(
				'title'       => __( 'Checkbox label', 'woocommerce-germanized' ),
				'type'        => 'text',
				'description' => __( 'Customize the checkbox label. Use {link}link name{/link} to insert the preview link.', 'woocommerce-germanized' ),
				'default'     => __( 'I hereby agree to the {link}direct debit mandate{/link}.', 'woocommerce-germanized' ),
				'desc_tip'    => true,
			),
			'enable_pre_notification'       => array(
				'title'       => __( 'Enable pre-notification', 'woocommerce-germanized' ),
				'label'       => __( 'Insert pre-notification text within the order confirmation email.', 'woocommerce-germanized' ),
				'type'        => 'checkbox',
				'description' => __( 'This option inserts a standard text containing a pre-notification for the customer.', 'woocommerce-germanized' ),
				'default'     => 'yes',
			),
			'debit_days'                    => array(
				'title'       => __( 'Debit days', 'woocommerce-germanized' ),
				'type'        => 'number',
				'description' => __( 'This option is used to calculate the debit date and is added to the order date.', 'woocommerce-germanized' ),
				'default'     => 5,
			),
			'mask'                          => array(
				'title'       => __( 'Mask IBAN', 'woocommerce-germanized' ),
				'label'       => __( 'Mask the IBAN within emails.', 'woocommerce-germanized' ),
				'type'        => 'checkbox',
				'description' => __( 'This will lead to masked IBANs within emails (replaced by *). All but last 4 digits will be masked.', 'woocommerce-germanized' ),
				'default'     => 'yes',
			),

		);

		if ( $this->supports_encryption() ) {

			$this->form_fields = array_merge( $this->form_fields, array(
				'remember' => array(
					'title'       => __( 'Remember', 'woocommerce-germanized' ),
					'label'       => __( 'Remember account data for returning customers.', 'woocommerce-germanized' ),
					'type'        => 'checkbox',
					'description' => __( 'Save account data as user meta if user has/creates a customer account.', 'woocommerce-germanized' ),
					'default'     => 'no',
				)
			) );

		}

	}

	public function get_user_account_data( $user_id = '' ) {

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$data = array(
			'holder' => '',
			'iban'   => '',
			'bic'    => '',
		);

		if ( $this->remember !== 'yes' ) {
			return $data;
		}

		$data = array(
			'holder' => $this->maybe_decrypt( get_user_meta( $user_id, 'direct_debit_holder', true ) ),
			'iban'   => $this->maybe_decrypt( get_user_meta( $user_id, 'direct_debit_iban', true ) ),
			'bic'    => $this->maybe_decrypt( get_user_meta( $user_id, 'direct_debit_bic', true ) ),
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
		$id           = $this->id;

		$fields = array(
			'account-holder' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $id ) . '-account-holder">' . __( 'Account Holder', 'woocommerce-germanized' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $id ) . '-account-holder" class="input-text wc-gzd-' . $id . '-account-holder" value="' . esc_attr( $account_data['holder'] ) . '" type="text" autocomplete="off" placeholder="" name="' . str_replace( '-', '_', $id ) . '_account_holder' . '" />
			</p>',
			'account-iban'   => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $id ) . '-account-iban">' . __( 'IBAN', 'woocommerce-germanized' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $id ) . '-account-iban" class="input-text wc-gzd-' . $id . '-account-iban" type="text" value="' . esc_attr( $account_data['iban'] ) . '" autocomplete="off" placeholder="" name="' . str_replace( '-', '_', $id ) . '_account_iban' . '" />
			</p>',
			'account-bic'    => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $id ) . '-account-bic">' . __( 'BIC/SWIFT', 'woocommerce-germanized' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $id ) . '-account-bic" class="input-text wc-gzd-' . $id . '-account-bic" type="text" value="' . esc_attr( $account_data['bic'] ) . '" autocomplete="off" placeholder="" name="' . str_replace( '-', '_', $id ) . '_account_bic' . '" />
			</p>',
		);

		?>
        <fieldset id="<?php echo $id; ?>-form">
			<?php
			/**
			 * Before direct debit checkout form.
			 *
			 * Fires before the direct debit checkout form is being rendered.
			 *
			 * @param string $id The gateway id.
			 *
			 * @since 1.4.0
			 *
			 */
			do_action( 'woocommerce_gzd_direct_debit_form_start', $id );

			foreach ( $fields as $field ) : ?>
				<?php echo $field; ?>
			<?php endforeach;

			/**
			 * After direct debit checkout form.
			 *
			 * Fires after the direct debit checkout form is being rendered.
			 *
			 * @param string $id The gateway id.
			 *
			 * @since 1.4.0
			 *
			 */
			do_action( 'woocommerce_gzd_direct_debit_form_end', $id );
			?>
            <div class="clear"></div>
        </fieldset>
		<?php

	}

	public function validate_fields() {

		if ( ! $this->is_available() || ! isset( $_POST['payment_method'] ) || $_POST['payment_method'] != $this->id ) {
			return;
		}

		$iban    = ( isset( $_POST['direct_debit_account_iban'] ) ? wc_clean( $_POST['direct_debit_account_iban'] ) : '' );
		$holder  = ( isset( $_POST['direct_debit_account_holder'] ) ? wc_clean( $_POST['direct_debit_account_holder'] ) : '' );
		$bic     = ( isset( $_POST['direct_debit_account_bic'] ) ? wc_clean( $_POST['direct_debit_account_bic'] ) : '' );
		$country = ( isset( $_POST['billing_country'] ) ? wc_clean( $_POST['billing_country'] ) : WC()->countries->get_base_country() );

		if ( empty( $iban ) || empty( $holder ) || empty( $bic ) ) {
			wc_add_notice( __( 'Please insert your SEPA account data.', 'woocommerce-germanized' ), 'error' );

			return false;
		}

		// Validate IBAN
		include_once WC_GERMANIZED_ABSPATH . 'includes/libraries/iban/oophp-iban.php';
		$iban_validator = new IBAN( $iban );

		/**
		 * Filter that allows enabling IBAN country validation.
		 *
		 * By enabling this option the IBAN country must match the billing country.
		 *
		 * @param bool $enable Whether to enable the check or not.
		 *
		 * @since 1.8.5
		 *
		 */
		$verify_iban_country = apply_filters( 'woocommerce_gzd_direct_debit_verify_iban_country', false );

		if ( ! $iban_validator->Verify() ) {
			wc_add_notice( __( 'Your IBAN seems to be invalid.', 'woocommerce-germanized' ), 'error' );
		} elseif ( $verify_iban_country && $iban_validator->Country() != $country ) {
			wc_add_notice( __( 'Your IBAN\'s country code doesn’t match with your billing country.', 'woocommerce-germanized' ), 'error' );
		}

		// Validate BIC
		if ( ! preg_match( '/^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/', $bic ) ) {
			wc_add_notice( __( 'Your BIC seems to be invalid.', 'woocommerce-germanized' ), 'error' );
		}

		// Make sure that checkbox gets validated if on woocommerce_pay for order page
		if ( isset( $_POST['woocommerce_pay'] ) ) {
			// Gateways are being lazy loaded - make sure that the checkbox exists even after submit
			$this->validate_checkbox();
		}
	}

	public function validate_checkbox() {
		if ( isset( $_POST['payment_method'] ) && $_POST['payment_method'] === $this->id && $this->enable_checkbox === 'yes' && ( ! isset( $_POST['direct_debit_legal'] ) && empty( $_POST['direct_debit_legal'] ) ) ) {
			return false;
		}

		return true;
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

		$suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$assets_path = WC()->plugin_url() . '/assets/';

		// Ensure that prettyPhoto is being loaded
		wp_register_script( 'prettyPhoto_debit', $assets_path . 'js/prettyPhoto/jquery.prettyPhoto' . $suffix . '.js', array( 'jquery' ), '3.1.6', true );
		wp_enqueue_script( 'prettyPhoto_debit' );
		wp_register_style( 'woocommerce_prettyPhoto_css_debit', $assets_path . 'css/prettyPhoto.css' );
		wp_enqueue_style( 'woocommerce_prettyPhoto_css_debit' );

		wp_register_script( 'wc-gzd-iban', WC_germanized()->plugin_url() . '/includes/gateways/direct-debit/assets/js/iban' . $suffix . '.js', array( 'wc-checkout' ), WC_GERMANIZED_VERSION, true );
		wp_enqueue_script( 'wc-gzd-iban' );

		wp_register_script( 'wc-gzd-direct-debit', WC_germanized()->plugin_url() . '/includes/gateways/direct-debit/assets/js/direct-debit' . $suffix . '.js', array( 'wc-gzd-iban' ), WC_GERMANIZED_VERSION, true );
		wp_localize_script( 'wc-gzd-direct-debit', 'direct_debit_params', array(
			'iban'       => __( 'IBAN', 'woocommerce-germanized' ),
			'swift'      => __( 'BIC/SWIFT', 'woocommerce-germanized' ),
			'is_invalid' => __( 'is invalid', 'woocommerce-germanized' ),
		) );
		wp_enqueue_script( 'wc-gzd-direct-debit' );
	}

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
			echo wpautop( wptexturize( $this->instructions ) );
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 *
	 * @param WC_Order $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'direct-debit' === $order->get_payment_method() && $order->has_status( 'processing' ) ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}

	public function checkbox() {
		wc_deprecated_function( 'WC_GZD_Gateway_Direct_Debit::checkbox', '3.5.0' );

		if ( $this->is_available() && $this->enable_checkbox === 'yes' ) {
			wc_get_template( 'checkout/terms-sepa.php' );
		}
	}

	public function get_checkbox_label() {
		wc_deprecated_function( 'WC_GZD_Gateway_Direct_Debit::get_checkbox_label', '3.5.0' );

		$ajax_url = wp_nonce_url( add_query_arg( array( 'action' => 'show_direct_debit' ), admin_url( 'admin-ajax.php' ) ), 'show_direct_debit' );

		/**
		 * Filter to adjust the direct debit mandate link.
		 *
		 * @param string $link The link.
		 * @param WC_GZD_Gateway_Direct_Debit $gateway The gateway instance.
		 *
		 * @since 1.8.5
		 *
		 */
		return apply_filters( 'woocommerce_gzd_direct_debit_ajax_url', str_replace( array(
			'{link}',
			'{/link}'
		), array(
			'<a href="' . $ajax_url . '" id="show-direct-debit-trigger" rel="prettyPhoto">',
			'</a>'
		), $this->checkbox_label ), $this );
	}

	/**
	 * Process the payment and return the result
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		/**
		 * Filter that allows default direct debit gateway order status.
		 *
		 * @param string $status The default order status.
		 *
		 * @since 1.8.5
		 *
		 */
		$order->update_status( apply_filters( 'woocommerce_gzd_direct_debit_default_status', 'on-hold' ), __( 'Awaiting Direct Debit Payment', 'woocommerce-germanized' ) );

		/**
		 * Manually trigger the mandate mail for custom order pay actions which is by default only triggered for the order confirmation mail
		 */
		if ( did_action( 'woocommerce_before_pay_action' ) ) {
		    $this->send_mail( $order_id );
		}

		// Reduce stock level
		wc_maybe_reduce_stock_levels( $order_id );

		// Check if cart instance exists (frontend request only)
		if ( WC()->cart ) {
			// Remove cart
			WC()->cart->empty_cart();
		}

		// Return thankyou redirect
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
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
			if ( strlen( $decrypted ) > 40 ) {
				return "";
			}

			return $decrypted;
		}

		return $string;
	}

	public function supports_encryption() {

		global $wp_version;

		if ( version_compare( phpversion(), '5.4', '<' ) ) {
			return false;
		}

		if ( ! extension_loaded( 'openssl' ) ) {
			return false;
		}

		if ( version_compare( $wp_version, '4.4', '<' ) ) {
			return false;
		}

		require_once WC_GERMANIZED_ABSPATH . 'includes/gateways/direct-debit/class-wc-gzd-gateway-direct-debit-encryption-helper.php';

		if ( ! WC_GZD_Gateway_Direct_Debit_Encryption_Helper::instance()->is_configured() ) {
			return false;
		}

		return true;
	}

}
