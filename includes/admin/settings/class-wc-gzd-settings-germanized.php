<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_GZD_Settings_Germanized' ) ) :

/**
 * Adds Settings Interface to WooCommerce Settings Tabs
 *
 * @class 		WC_GZD_Settings_Germanized
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Germanized extends WC_Settings_Page {

	public $premium_sections = array();

	/**
	 * Adds Hooks to output and save settings
	 */
	public function __construct() {
		$this->id    = 'germanized';
		$this->label = __( 'Germanized', 'woocommerce-germanized' );

		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		add_filter( 'woocommerce_gzd_get_settings_display', array( $this, 'get_display_settings' ) );
		add_filter( 'woocommerce_gzd_get_settings_email', array( $this, 'get_email_settings' ) );
		add_action( 'woocommerce_gzd_before_save_section_', array( $this, 'before_save' ), 0, 1 );
		add_action( 'woocommerce_gzd_after_save_section_', array( $this, 'after_save' ), 0, 1 );
		add_action( 'woocommerce_admin_field_image', array( $this, 'image_field' ), 0, 1 );
		add_action( 'woocommerce_admin_field_html', array( $this, 'html_field' ), 0, 1 );
		add_action( 'woocommerce_admin_field_hidden', array( $this, 'hidden_field' ), 0, 1 );
		add_action( 'woocommerce_gzd_before_section_output', array( $this, 'init_tour_data' ), 0, 1 );

		if ( ! WC_Germanized()->is_pro() ) {
			// Premium sections
			$this->premium_sections = array(
				'invoices' => sprintf( __( 'Invoices & Packing Slips %s', 'woocommerce-germanized' ), '<span class="wc-gzd-premium-section-tab">pro</span>' ),
				'pdf' 	   => sprintf( __( 'PDF %s', 'woocommerce-germanized' ), '<span class="wc-gzd-premium-section-tab">pro</span>' ),
				'checkout' => sprintf( __( 'Multistep Checkout %s', 'woocommerce-germanized' ), '<span class="wc-gzd-premium-section-tab">pro</span>' ),
				'agbs'     => sprintf( __( 'Terms & Conditions generator %s', 'woocommerce-germanized' ), '<span class="wc-gzd-premium-section-tab">pro</span>' ),
				'widerruf' => sprintf( __( 'Revocation generator %s', 'woocommerce-germanized' ), '<span class="wc-gzd-premium-section-tab">pro</span>' ),
			);

			add_filter( 'woocommerce_gzd_settings_sections', array( $this, 'set_premium_sections' ), 4 );

			foreach ( $this->premium_sections as $key => $section ) {
				add_filter( 'woocommerce_gzd_get_settings_' . $key, array( $this, 'get_premium_settings' ), 0 );
				add_filter( 'wc_germanized_settings_section_before_' . $key, array( $this, 'output_premium_section' ), 0 );
				add_filter( 'woocommerce_gzd_get_sidebar_' . $key, array( $this, 'get_premium_sidebar' ), 0 );
			}
		}
	}

	public function image_field( $value ) {
		?>
		<tr valign="top">
			<th class="forminp forminp-image" colspan="2" id="<?php echo $value[ 'id' ]; ?>">
				<a href="<?php echo $value[ 'href' ]; ?>" target="_blank"><img src="<?php echo $value[ 'img' ]; ?>" /></a>
			</th>
		</tr>
		<?php
	}

	public function html_field( $value ) {
		?>
		<tr valign="top">
			<th class="forminp forminp-html" id="<?php echo $value[ 'id' ]; ?>"><?php echo $value[ 'title' ]; ?></th>
			<td class="forminp"><?php echo $value[ 'html' ]; ?></td>
		</tr>
		<?php
	}

	public function hidden_field( $value ) {
		$option_value = WC_Admin_Settings::get_option( $value[ 'id' ], $value[ 'default' ] );
		?>
		<tr valign="top" style="display: none">
			<th class="forminp forminp-image">
				<input type="hidden" id="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( $option_value ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>" />
			</th>
		</tr>
		<?php
	}

	/**
	 * Gets setting sections
	 */
	public function get_sections() {
		$sections = apply_filters( 'woocommerce_gzd_settings_sections', array(
			''   		 	=> __( 'General Options', 'woocommerce-germanized' ),
			'display'       => __( 'Display Options', 'woocommerce-germanized' ),
			'email'			=> __( 'Email Options', 'woocommerce-germanized' ),
		) );
		return $sections;
	}

	public function init_tour_data( $section = 'general' ) {

		if ( empty( $section ) )
			$section = 'general';

		if ( ! WC_GZD_Admin::instance()->is_tour_enabled( $section ) )
			return;

		$tour = WC_germanized()->plugin_path() . '/includes/admin/views/html-tour-' . $section . '.php';

		if ( file_exists( $tour ) )
			include( $tour );

	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		
		$delivery_terms = array( '' => __( 'None', 'woocommerce-germanized' ) );
		$terms = get_terms( 'product_delivery_time', array('fields' => 'id=>name', 'hide_empty' => false) );
		
		if ( ! is_wp_error( $terms ) )
			$delivery_terms = $delivery_terms + $terms;

		$labels = array_merge( array( '' => __( 'None', 'woocommerce-germanized' ) ), WC_Germanized()->price_labels->get_labels() );

		$complaints_pages = WC_GZD_Admin::instance()->get_complaints_shortcode_pages();
        $is_complaints_shortcode_inserted = true;
        $complaints_shortcode_missing = array();

		foreach( $complaints_pages as $page => $page_id ) {
            if ( ! WC_GZD_Admin::instance()->is_complaints_shortcode_inserted( $page_id ) ) {
                $is_complaints_shortcode_inserted = false;
                array_push( $complaints_shortcode_missing, ( $page === 'terms' ? __( 'Terms & Conditions', 'woocommerce-germanized' ) : __( 'Imprint', 'woocommerce-germanized' ) ) );
            }
        }

		$settings = array(

			array(	'title' => __( 'General', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'general_options' ),

			array(
				'title' 	=> __( 'Submit Order Button Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text serves as Button text for the Order Submit Button.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_order_submit_btn_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( 'Buy Now', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Phone as required field', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Should phone number be a required field within checkout?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_phone_required',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
			),

			array(
				'title' 	=> __( 'Add title field', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Add a title field to the address within checkout?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_address_field',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
			),

			array(
				'title' 	=> __( 'Disallow cancellations', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Don’t allow customers to manually cancel orders.', 'woocommerce-germanized' ),
				'desc_tip'	=> __( 'By default payment methods like PayPal allow order cancellation by clicking the abort link. This option will stop customers from manually cancel orders.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_stop_order_cancellation',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
			),

			array(
				'title' 	=> __( 'Disallow gateway choosing', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Don’t allow customers to change the payment gateway after ordering.', 'woocommerce-germanized' ),
				'desc_tip'	=> __( 'Customers paying through a gateway which allows later payment (e.g. PayPal) will find a link within their customer account which redirects them to a pay page. This page offers the possibility to choose another gateway than before which may lead to further problems e.g. additional gateway costs etc. which would require a new order submittal. This option makes sure the customer gets redirected directly to the gateways payment page, e.g. to PayPal.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_disallow_belated_payment_method_selection',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
			),

			array( 'type' => 'sectionend', 'id' => 'general_options' ),

			array(	'title' => __( 'Contract', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'contract_options', 'desc' => '<div class="notice inline notice-warning"><p>' . sprintf( __( '%sUpgrade to %spro%s%s to unlock this feature and get premium support.', 'woocommerce-germanized' ), '<a href="https://vendidero.de/woocommerce-germanized" class="button">', '<span class="wc-gzd-pro">', '</span>', '</a>' ) . '</p></div>' ),

			array(
				'title' 	=> '',
				'id' 		=> 'woocommerce_gzdp_contract_after_confirmation',
				'img'		=> WC_Germanized()->plugin_url() . '/assets/images/pro/settings-inline-contract.png',
				'href'      => 'https://vendidero.de/woocommerce-germanized#contract',
				'type' 		=> 'image',
			),

			array( 'type' => 'sectionend', 'id' => 'contract_options' ),

			array(	'title' => __( 'Legal Pages', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'legal_pages_options' ),

			array(
				'title' 	=> __( 'Terms & Conditions', 'woocommerce-germanized' ),
				'desc_tip' 	=> __( 'This page should contain your terms & conditions.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_terms_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc'		=> ( ! get_option( 'woocommerce_terms_page_id' ) ? sprintf( __( 'Don\'t have terms & conditions yet? <a href="%s">Generate now</a>!', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized&section=agbs' ) ) : '' ),
			),

			array(
				'title' 	=> __( 'Power of Revocation', 'woocommerce-germanized' ),
				'desc_tip' 	=> __( 'This page should contain information regarding your customer\'s Right of Revocation.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_revocation_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc'		=> ( ! get_option( 'woocommerce_revocation_page_id' ) ? sprintf( __( 'Don\'t have a revocation page yet? <a href="%s">Generate now</a>!', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized&section=widerruf' ) ) : '' ),
			),

			array(
				'title' 	=> __( 'Imprint', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain an imprint with your company\'s information.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_imprint_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' 	=> __( 'Data Security Statement', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain information regarding your data security policy.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_data_security_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' 	=> __( 'Payment Methods', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain information regarding the Payment Methods that are chooseable during checkout.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_payment_methods_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array(
				'title' 	=> __( 'Shipping Methods', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This page should contain information regarding shipping methods that are chooseable during checkout.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_shipping_costs_page_id',
				'type' 		=> 'single_select_page',
				'default'	=> '',
				'class'		=> 'chosen_select_nostd',
				'css' 		=> 'min-width:300px;',
				'desc_tip'	=> true,
			),

			array( 'type' => 'sectionend', 'id' => 'legal_pages_options' ),

			array( 'title' => __( 'Dispute Resolution', 'woocommerce-germanized' ), 'type' => 'title', 'desc' => sprintf( __( 'As with Feb. 1 2017 new regulations regarding alternative dispute resolution take effect. Further information regarding your duty to supply information can be found <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'http://shopbetreiber-blog.de/2017/01/05/streitschlichtung-neue-infopflichten-fuer-alle-online-haendler-ab-1-februar/' ), 'id' => 'complaints_options' ),

			array(
				'title' 	 => __( 'Dispute Resolution', 'woocommerce-germanized' ),
				'desc' 		 => __( 'You may select whether you are willing, obliged or not willing to participate in dispute settlement proceeedings before a consumer arbitration board. The corresponding Resolution Text is attached to the [gzd_complaints] shortcode which you should add to your imprint. Trusted Shops advises you to add that text to your Terms & Conditions as well.', 'woocommerce-germanized' ),
				'desc_tip'	 => true,
				'id' 		 => 'woocommerce_gzd_dispute_resolution_type',
				'type' 		 => 'radio',
				'default'	 => 'none',
				'options'	 => array(
					'none'   	=> __( 'Not obliged, not willing', 'woocommerce-germanized' ),
					'willing'   => __( 'Not obliged, willing', 'woocommerce-germanized' ),
					'obliged'   => __( 'Obliged', 'woocommerce-germanized' ),
				),
			),

			array(
				'title' 	=> __( 'Resolution Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Adapt this example text regarding alternative dispute resolution to your needs. Text will be added to the [gzd_complaints] Shortcode. You may as well add this text to your terms & conditions.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'The european commission provides a platform for online dispute resolution (OS) which is accessible at http://ec.europa.eu/consumers/odr/. We are not obliged nor willing to participate in dispute settlement proceedings before a consumer arbitration board.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_alternative_complaints_text_none',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Resolution Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Adapt this example text regarding alternative dispute resolution to your needs. Text will be added to the [gzd_complaints] Shortcode. You may as well add this text to your terms & conditions.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'The european commission provides a platform for online dispute resolution (OS) which is accessible at http://ec.europa.eu/consumers/odr/. Consumers may use this platform for the settlements of their disputes. We are in principle prepared to participate in an extrajudicial arbitration proceeding.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_alternative_complaints_text_willing',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Resolution Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Adapt this example text regarding alternative dispute resolution to your needs. Text will be added to the [gzd_complaints] Shortcode. You may as well add this text to your terms & conditions.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'The european commission provides a platform for online dispute resolution (OS) which is accessible at http://ec.europa.eu/consumers/odr/. Consumers may contact [Name, Address, Website of arbitration board] for the settlements of their disputes. We are obliged to participate in arbitration proceeding before that board.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_alternative_complaints_text_obliged',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Shortcode Status', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_complaints_procedure_status',
				'type' 		=> 'html',
				'html' 		=> '<p>' . ( wc_get_page_id( 'imprint' ) == -1 ? '<span class="wc-gzd-status-text wc-gzd-text-red">' . __( 'Please choose a page as your imprint first.', 'woocommerce-germanized' ) . '</span>' : '<span class="wc-gzd-status-text wc-gzd-text-' . ( $is_complaints_shortcode_inserted ? 'green' : 'red' ) . '"> ' . ( $is_complaints_shortcode_inserted ? __( 'Found', 'woocommerce-germanized' ) : sprintf( __( 'Not found within %s', 'woocommerce-germanized' ), implode( ', ', $complaints_shortcode_missing ) ) ) . '</span> ' . ( ! $is_complaints_shortcode_inserted ? '<a class="button button-secondary" style="margin-left: 1em" href="' . wp_nonce_url( add_query_arg( array( 'complaints' => 'add' ) ), 'append-complaints-shortcode' ). '">' . __( 'Append it now', 'woocommerce-germanized' ) . '</a></p>' : '' ) ),
			),

			array( 'type' => 'sectionend', 'id' => 'complaints_options' ),

			array( 'title' => __( 'Small Businesses', 'woocommerce-germanized' ), 'type' => 'title', 'desc' => '', 'id' => 'small_business_options' ),

			array(
				'title' 	=> __( 'Small-Enterprise-Regulation', 'woocommerce-germanized' ),
				'desc' 		=> __( 'VAT based on &#167;19 UStG', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_small_enterprise',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> sprintf( __( 'set this Option if you have chosen <a href="%s" target="_blank">&#167;19 UStG</a>', 'woocommerce-germanized' ), esc_url( 'http://www.gesetze-im-internet.de/ustg_1980/__19.html' ) )
			),

			array(
				'title' 	=> __( 'Show no VAT notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show no VAT &#167;19 UStG notice on single product', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_small_enterprise',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
			),

			array(
				'title' 	=> __( 'Notice Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'You may want to adjust the small buisness notice text to meet your criteria.', 'woocommerce-germanized' ),
				'desc_tip'  => true,
                'id' 		=> 'woocommerce_gzd_small_enterprise_text',
				'type' 		=> 'textarea',
				'default'	=> __( 'Value added tax is not collected, as small businesses according to §19 (1) UStG.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 50px;',
			),

			array( 'type' => 'sectionend', 'id' => 'differential_taxation_options' ),

			array( 'title' => __( 'Differential Taxation', 'woocommerce-germanized' ), 'type' => 'title', 'desc' => '', 'id' => 'differential_taxation_options' ),

			array(
				'title' 	=> __( 'Taxation Notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable differential taxation text notice beneath product price.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_differential_taxation_show_notice',
				'desc_tip'  => __( 'If you have disabled this option, a normal VAT notice will be displayed, which is sufficient as Trusted Shops states. To further inform your customers you may enable this notice.', 'woocommerce-germanized' ),
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Notice Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be shown as a further notice for the customer to inform him about differential taxation.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_differential_taxation_notice_text',
				'type' 		=> 'textarea',
				'css' 		=> 'width:100%; height: 50px;',
				'default'	=> __( 'incl. VAT (differential taxation according to §25a UStG.)', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Checkout Notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable differential taxation notice during checkout and in emails.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_differential_taxation_checkout_notices',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
			),

			array( 'type' => 'sectionend', 'id' => 'differential_taxation_options' ),

			array( 'title' => __( 'Delivery Times', 'woocommerce-germanized' ), 'type' => 'title', 'desc' => '', 'id' => 'delivery_times_options' ),

			array(
				'title' 	=> __( 'Default Delivery Time', 'woocommerce-germanized' ),
				'desc_tip' 	=> __( 'This delivery time will be added to every product if no delivery time has been chosen individually', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_default_delivery_time',
				'css' 		=> 'min-width:250px;',
				'default'	=> '',
				'type' 		=> 'select',
				'class'		=> 'chosen_select',
				'options'	=>	$delivery_terms,
				'desc'		=>  '<a href="' . admin_url( 'edit-tags.php?taxonomy=product_delivery_time&post_type=product' ) . '">' . __( 'Manage Delivery Times', 'woocommerce-germanized' ) . '</a>',
			),

			array(
				'title' 	=> __( 'Delivery Time Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be used to indicate delivery time for products. Use {delivery_time} as placeholder.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_delivery_time_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( 'Delivery time: {delivery_time}', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'delivery_times_options' ),

			array( 'title' => __( 'Sale Price Labels', 'woocommerce-germanized' ), 'type' => 'title', 'desc' => '', 'id' => 'sale_price_labels_options' ),

			array(
				'title' 	=> __( 'Default Sale Label', 'woocommerce-germanized' ),
				'desc_tip' 	=> __( 'Choose whether you would like to have a default sale price label to inform the customer about the regular price (e.g. Recommended Retail Price).', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_default_sale_price_label',
				'css' 		=> 'min-width:250px;',
				'default'	=> '',
				'type' 		=> 'select',
				'class'		=> 'chosen_select',
				'options'	=>	$labels,
				'desc'		=>  '<a href="' . admin_url( 'edit-tags.php?taxonomy=product_price_label&post_type=product' ) . '">' . __( 'Manage Price Labels', 'woocommerce-germanized' ) . '</a>',
			),

			array(
				'title' 	=> __( 'Default Sale Regular Label', 'woocommerce-germanized' ),
				'desc_tip' 	=> __( 'Choose whether you would like to have a default sale price regular label to inform the customer about the sale price (e.g. New Price).', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_default_sale_price_regular_label',
				'css' 		=> 'min-width:250px;',
				'default'	=> '',
				'type' 		=> 'select',
				'class'		=> 'chosen_select',
				'options'	=>	$labels,
				'desc'		=>  '<a href="' . admin_url( 'edit-tags.php?taxonomy=product_price_label&post_type=product' ) . '">' . __( 'Manage Price Labels', 'woocommerce-germanized' ) . '</a>',
			),

			array( 'type' => 'sectionend', 'id' => 'delivery_times_options' ),


			array(	'title' => __( 'Shipping Costs', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'shipping_costs_options' ),

			array(
				'title' 	=> __( 'Shipping Costs Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be used to inform the customer about shipping costs. Use {link}{/link} to insert link to shipping costs page.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_shipping_costs_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( 'plus {link}Shipping Costs{/link}', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Free Shipping Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be used to inform the customer about free shipping. Leave empty to disable notice. Use {link}{/link} to insert link to shipping costs page.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_free_shipping_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> '',
			),

			array(
				'title' 	=> __( 'Shipping Costs Tax', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable better taxation for shpping costs?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_shipping_tax',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> sprintf( __( 'By choosing this option shipping cost taxation will be calculated based on tax rates within cart. Imagine the following example. Further information can be found <a href="%s" target="_blank">here</a>. %s', 'woocommerce-germanized' ), 'http://www.it-recht-kanzlei.de/umsatzsteuer-versandkosten-mehrwertsteuer.html', '<table class="wc-gzd-tax-example"><thead><tr><th>Produkt</th><th>Preis</th><th>MwSt.-Satz</th><th>Anteil</th><th>MwSt.</th></tr></thead><tbody><tr><td>Buch</td><td>' . wc_price( 40 ) . '</td><td>7%</td><td>40%</td><td>' . wc_price( 2.62 ) . '</td></tr><tr><td>DVD</td><td>' . wc_price( 60 ) . '</td><td>19%</td><td>60%</td><td>' . wc_price( 9.58 ) . '</td></tr><tr><td>Versand</td><td>' . wc_price( 5 ) . '</td><td>7% | 19%</td><td>40% | 60%</td><td>' . wc_price( 0.13 ) . ' | ' . wc_price( 0.48 ) . '</td></tr></tbody></table>' ),
			),

			array(
				'title' 	=> __( 'Force Tax Calculation', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Force shipping costs tax calculation for every method?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_shipping_tax_force',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This option will overwrite settings for each individual shipping method to force tax calculation (instead of only calculating tax for those methods which are taxeable).', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_costs_options' ),

			array(	'title' => __( 'Fees', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'fees_options' ),

			array(
				'title' 	=> __( 'Fee Tax', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable better taxation for fees?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_fee_tax',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'  => __( 'By choosing this option fee taxation will be calculated based on tax rates within cart. See shipping costs taxation for more information.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Force Tax Calculation', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Force fee tax calculation for every fee?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_fee_tax_force',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This option will overwrite settings for each individual fee to force tax calculation (instead of only calculating tax for those fees which are taxeable).', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'fees_options' ),

			array( 'title' => __( 'Customers', 'woocommerce-germanized' ), 'type' => 'title', 'desc' => '', 'id' => 'customer_options' ),

			array(
				'title' 	=> __( 'Checkbox', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Add a checkbox to customer registration form.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_customer_account_checkbox',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
			),

			array(
				'title' 	=> __( 'Checkbox text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a Plain Text which will be shown as checkbox text for customer account creation. Use {term_link}{/term_link}, {data_security_link}{/data_security_link}, {revocation_link}{/revocation_link} as Placeholders for the links to legal pages.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'Yes, I’d like create a new account and have read and understood the {data_security_link}data privacy statement{/data_security_link}.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_customer_account_text',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Checkout', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Replace default WooCommerce text regarding account creation during checkout.', 'woocommerce-germanized' ),
				'desc_tip'	=> __( 'Use the text from above instead of the default WooCommerce text regarding account creation during checkout. This checkbox is only show if you have activated guest accounts.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_customer_account_checkout_checkbox',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
			),

			array(
				'title' 	=> __( 'Customer Double Opt In', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable customer double opt in on registration?', 'woocommerce-germanized' ),
				'desc_tip'  => sprintf( __( 'If customer chooses to create a customer account an email with an activation link will be sent by mail. Customer account will be marked as activated if user clicks on the link within the email. More information on this topic can be found <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'http://t3n.de/news/urteil-anmeldebestatigungen-double-opt-in-pflicht-592304/' ),
				'id' 		=> 'woocommerce_gzd_customer_activation',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Disable Login and Checkout', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Disable login and checkout for unactivated customers.', 'woocommerce-germanized' ),
				'desc_tip'  => __( 'Customers that did not click on the activation link will not be able to complete checkout nor login to their account.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_customer_activation_login_disabled',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Delete Unactivated After', 'woocommerce-germanized' ),
				'desc_tip' 	=> __( 'This will make sure unactivated customer accounts will be deleted after X days. Set to 0 if you don\'t want to automatically delete unactivated customers.', 'woocommerce-germanized' ),
				'desc'		=> __( 'days', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_customer_cleanup_interval',
				'type' 		=> 'number',
				'custom_attributes' => array( 'min' => 0, 'step' => 1 ),
				'default'	=> 7,
			),

			array( 'type' => 'sectionend', 'id' => 'customer_options' ),

			array(	'title' => __( 'Right of Recission', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'recission_options' ),

			array(
				'title' 	=> __( 'Revocation Address', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Type in an address, telephone/telefax number, email address which is to be used as revocation address', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_revocation_address',
				'type' 		=> 'textarea',
			),

			array( 'type' => 'sectionend', 'id' => 'recission_options' ),

			array(	'title' => __( 'Virtual VAT', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'virtual_vat_options' ),

			array(
				'title' 	=> __( 'Enable Virtual VAT', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable if you want to charge your customer\'s countries\' VAT for virtual products.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_enable_virtual_vat',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> sprintf( __( 'New EU VAT rule applies on 01.01.2015. Make sure that every digital or virtual product has chosen the right tax class (Virtual Rate or Virtual Reduced Rate). Gross prices will not differ from the prices you have chosen for affected products. In fact the net price will differ depending on the VAT rate of your customers\' country. Shop settings will be adjusted to show prices including tax. More information can be found <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'http://ec.europa.eu/taxation_customs/taxation/vat/how_vat_works/telecom/index_de.htm#new_rules' ),
			),

			array( 'type' => 'sectionend', 'id' => 'virtual_vat_options' ),

			array(	'title' => _x( 'Invoices', 'invoices', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'invoice_options', 'desc' => '<div class="notice inline notice-warning"><p>' . sprintf( __( '%sUpgrade to %spro%s%s to unlock this feature and get premium support.', 'woocommerce-germanized' ), '<a href="https://vendidero.de/woocommerce-germanized" class="button">', '<span class="wc-gzd-pro">', '</span>', '</a>' ) . '</p></div>' ),

			array(
				'title' 	=> '',
				'id' 		=> 'woocommerce_gzdp_invoice_enable',
				'img'		=> WC_Germanized()->plugin_url() . '/assets/images/pro/settings-inline-invoices.png',
				'href'      => 'https://vendidero.de/woocommerce-germanized#accounting',
				'type' 		=> 'image',
			),

			array( 'type' => 'sectionend', 'id' => 'invoice_options' ),

			array(	'title' => __( 'VAT', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'vat_options', 'desc' => '<div class="notice inline notice-warning"><p>' . sprintf( __( '%sUpgrade to %spro%s%s to unlock this feature and get premium support.', 'woocommerce-germanized' ), '<a href="https://vendidero.de/woocommerce-germanized" class="button">', '<span class="wc-gzd-pro">', '</span>', '</a>' ) . '</p></div>' ),

			array(
				'title' 	=> '',
				'id' 		=> 'woocommerce_gzdp_enable_vat_check',
				'img'		=> WC_Germanized()->plugin_url() . '/assets/images/pro/settings-inline-vat.png',
				'href'      => 'https://vendidero.de/woocommerce-germanized#vat',
				'type' 		=> 'image',
			),

			array( 'type' => 'sectionend', 'id' => 'vat_options' ),

		); // End general settings
	
		return apply_filters( 'woocommerce_germanized_settings', $settings );

	}

	public function get_email_settings() {

		$mailer 			= WC()->mailer();
		$email_templates 	= $mailer->get_emails();
		$email_select 		= array();

		foreach ( $email_templates as $email ) {

		    $customer = false;

		    if ( is_callable( array( $email, 'is_customer_email' ) ) ) {
		        $customer = $email->is_customer_email();
            }

			$email_select[ $email->id ] = empty( $email->title ) ? ucfirst( $email->id ) : ucfirst( $email->title ) . ' (' . ( $customer ? __( 'Customer', 'woocommerce-germanized' ) : __( 'Admin', 'woocommerce-germanized' ) ) . ')';
		}

		$email_order = wc_gzd_get_email_attachment_order();

		$email_settings = array();

		foreach ( $email_order as $key => $order ) {

			array_push( $email_settings, array(

				'title' 	=> sprintf( __( 'Attach %s', 'woocommerce-germanized' ), $order ),
				'desc' 		=> sprintf( __( 'Attach %s to the following email templates', 'woocommerce-germanized' ), $order ),
				'id' 		=> 'woocommerce_gzd_mail_attach_' . $key,
				'type' 		=> 'multiselect',
				'class'		=> 'chosen_select',
				'desc_tip'	=> true,
				'options'	=> $email_select,

			) );

		}

		$settings = array(

			array(	'title' => __( 'E-Mails', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'email_options', 'desc' => __( 'Use drag & drop to customize attachment order. Don\'t forget to save your changes.', 'woocommerce-germanized' ) ),

			array(
				'title' 	=> '',
				'id' 		=> 'woocommerce_gzd_mail_attach_order',
				'type' 		=> 'hidden',
				'default'	=> 'terms,revocation,data_security,imprint',
			),

		);

		$settings = array_merge( $settings, $email_settings );

		$settings = array_merge( $settings, array(

			array( 'type' => 'sectionend', 'id' => 'email_options' ),

			array(	'title' => __( 'Email Display Options', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'email_display_options' ),

			array(
				'title' 	=> __( 'Show within Emails', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Base Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_emails_unit_price',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),

			array(
				'desc' 		=> __( 'Product Units', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_emails_product_units',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_emails_delivery_time',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Short Description', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_emails_product_item_desc',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),
        ) );

		$settings = array_merge( $settings, array(

			array(
				'title' 	=> __( 'Hide Username', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide username from email content if password or password reset link is embedded.', 'woocommerce-germanized' ),
				'desc_tip'  => __( 'Trusted Shops advises to not show the username together with an account password or password reset link. This option hides (or masks) the username in those specific cases.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_hide_username_with_password',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
			),

			array( 'type' => 'sectionend', 'id' => 'email_display_options' ),

			array(	'title' => __( 'Email Attachment Options', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'email_attachment_options', 'desc' => '<div class="notice inline notice-warning"><p>' . sprintf( __( 'Want to attach automatically generated PDF files to emails instead of plain text? %sUpgrade to %spro%s%s', 'woocommerce-germanized' ), '<a style="margin-left: 1em" href="https://vendidero.de/woocommerce-germanized" class="button">', '<span class="wc-gzd-pro">', '</span>', '</a>' ) . '</p></div>' ),

			array(
				'title' 	=> '',
				'id' 		=> 'woocommerce_gzdp_legal_page_terms_enabled',
				'img'		=> WC_Germanized()->plugin_url() . '/assets/images/pro/settings-inline-emails.png',
				'href'      => 'https://vendidero.de/woocommerce-germanized#legal-page',
				'type' 		=> 'image',
			),

			array( 'type' => 'sectionend', 'id' => 'email_attachment_options' ),

		) );

		return apply_filters( 'woocommerce_germanized_settings_email', $settings );

	}

	public function get_display_settings() {

		$product_types = wc_get_product_types();

		$digital_type_options = array_merge( array(
			'downloadable'  => __( 'Downloadable Product', 'woocommerce-germanized' ),
			'virtual'		=> __( 'Virtual Product', 'woocommerce-germanized' ),
		), $product_types );

		$shipping_methods_options = WC_GZD_Admin::instance()->get_shipping_method_instances_options();

		$settings = array(

			array(	'title' => __( 'General', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'general_options' ),

			array(
				'title' 	=> __( 'Add to Cart', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show add to cart button on listings?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_add_to_cart',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'unset this option if you don\'t want to show the add to cart button within the product listings', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Link to Details', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Want to link to product details page instead of add to cart within listings?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_link_details',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'Decide whether you like to link to your product\'s details page instead of displaying an add to cart button within product listings.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Product Details Text', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_link_details_text',
				'default'	=> __( 'Details', 'woocommerce-germanized' ),
				'type' 		=> 'text',
				'desc_tip'	=> __( 'If you have chosen to link to product details page instead of add to cart URL you may want to change the button text.', 'woocommerce-germanized' ),
				'css' 		=> 'min-width:300px;',
			),

			array(
				'title' 	=> __( 'Digital Delivery Time Text', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_digital_delivery_time_text',
				'default'	=> '',
				'type' 		=> 'text',
				'desc_tip'	=> __( 'Enter a text which will be shown as digital delivery time text (replacement for default digital time on digital products).', 'woocommerce-germanized' ),
				'css' 		=> 'min-width:300px;',
			),

			array(
				'title' 	=> __( 'Notice Footer', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show a global VAT notice within footer', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_footer_vat_notice',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'checkboxgroup'	=> 'start'
			),

			array(
				'desc' 		=> __( 'Show a global sale price notice within footer', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_footer_sale_price_notice',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
				'checkboxgroup'		=> 'end',
			),

			array( 'type' => 'sectionend', 'id' => 'general_options' ),

			array(	'title' => __( 'Products', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'product_options' ),

			array(
				'title' 	=> __( 'Show within Product Listings', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Shipping Costs notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_shipping_costs',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),

			array(
				'desc' 		=> __( 'Tax Info', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_tax_info',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Base Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_unit_price',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Product Units', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_product_units',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_delivery_time',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 	=> __( 'Price Labels', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_listings_sale_price_labels',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'	=> 'end',
			),

			array(
				'title' 	=> __( 'Show on Product Detail Page', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Shipping Costs notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_shipping_costs',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'	=> 'start',
			),

			array(
				'desc' 		=> __( 'Tax Info', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_tax_info',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Base Price', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_unit_price',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Product Units', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_product_units',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 		=> __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_delivery_time',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
				'checkboxgroup'		=> '',
			),

			array(
				'desc' 	=> __( 'Price Labels', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_product_detail_sale_price_labels',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'checkboxgroup'	=> 'end',
			),

			array(
				'title' 	=> __( 'Hide Tax Rate', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide specific tax rate within shop pages.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_hide_tax_rate_shop',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This option will make sure that within shop pages no specific tax rates are shown. Instead only incl. tax or excl. tax notice is shown.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Hide Shipping Costs Notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Select product types for which you might want to disable the shipping costs notice.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_display_shipping_costs_hidden_types',
				'class' 	=> 'chosen_select',
				'type'		=> 'multiselect',
				'options'	=> $digital_type_options,
				'default'	=> array( 'downloadable', 'external', 'virtual' ),
			),

			array(
				'title' 	=> __( 'Hide Delivery Time Notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Select product types for which you might want to disable the delivery time notice.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_display_delivery_time_hidden_types',
				'class' 	=> 'chosen_select',
				'type'		=> 'multiselect',
				'options'	=> $digital_type_options,
				'default'	=> array( 'external', 'virtual' ),
			),

			array( 'type' => 'sectionend', 'id' => 'product_options' ),

			array(	'title' => __( 'Base Price', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'unit_price_options' ),

			array(
				'title' 	=> __( 'Base Price Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be used to display the base price. Use {price} to insert the price. If you want to specifically format base price output use {base}, {unit} and {base_price} as placeholders.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_unit_price_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( '{price}', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Variable Base Price', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable price range base prices for variable products.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_unit_price_enable_variable',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Product Units Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be used to display the product units. Use {product_units} to insert the amount of product units. Use {unit} to insert the unit. Optionally display the formatted unit price with {unit_price}.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_product_units_text',
				'type' 		=> 'text',
				'css' 		=> 'min-width:300px;',
				'default'	=> __( 'Product contains: {product_units} {unit}', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'unit_price_options' ),

			array(	'title' => __( 'Checkout & Cart', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'checkout_options' ),

			array(
				'title' 	=> __( 'DHL Parcel Shops', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Allow customers to choose a DHL parcel shop or packing station as delivery address.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_dhl_parcel_shops',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This option adds a checkbox to your checkout shipping fields which allows the customer to optionally choose a DHL packing station or parcel shop for delivery. A PostNumber is required.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Supported Countries', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_dhl_parcel_shop_supported_countries',
				'default'	=> array( 'DE', 'AT' ),
				'type' 		=> 'multi_select_countries',
				'desc_tip'	=> __( 'Choose countries which support Parcel Shop delivery.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Disabled Methods', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_dhl_parcel_shop_disabled_shipping_methods',
				'default'	=> array(),
				'class' 	=> 'chosen_select',
				'type'      => 'multiselect',
				'options'   => $shipping_methods_options,
				'desc_tip'	=> __( 'Optionally choose methods for which DHL Parcel Shop Delivery should be disabled. Does only work if you have disabled choosing shipping methods within checkout.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Parcel Shop Finder', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable DHL Parcel Shop Finder to let customers choose a parcel shop nearby.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_dhl_parcel_shop_finder',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> sprintf( __( 'You may enable this option to add a <a href="%s" target="_blank">Parcel Shop Finder</a> to your checkout. Adds an link next to the checkbox. The finder (DHL API) opens in an overlay and lets the customer find and choose a parcel shop or packing station nearby.', 'woocommerce-germanized' ), 'https://parcelshopfinder.dhlparcel.com/' ),
			),

			array(
				'title' 	=> __( 'Fallback Mode', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Enable to make sure default checkout template is not being overriden by theme.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_fallback',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'If you are facing problems within your checkout e.g. legally relevant data is not showing (terms, delivery time, unit price etc.) your theme seems to be incompatible (not using default WooCommerce hooks and filters). As a workaround you may use this fallback which ensures default review-order.php and form-checkout.php is used.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Force free shipping', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Force free shipping method if available?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_free_shipping_select',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'By default WooCommerce will let customers choose other shipping methods than free shipping (if available). This option will force free shipping if available.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Hide taxes estimated', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Do you want to hide the "taxes and shipping estimated" text from your cart?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_hide_cart_tax_estimated',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'By default WooCommerce adds a "taxes and shipping estimated" text to your cart. This might puzzle your customers and may not meet german law.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Show Thumbnails', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show product thumbnails on checkout page?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_thumbnails',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'Uncheck if you don\'t want to show your product thumbnails within checkout table.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Hide Shipping Select', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide shipping rate selection from checkout?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_shipping_rate_select',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This option will hide shipping rate selection from checkout. By then customers will only be able to change their shipping rate on cart page.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Show back to cart button', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show back to cart button within your checkout table?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_back_to_cart_button',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This button may let your customer edit their order before submitting. Some people state that this button should be hidden to avoid legal problems.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Show edit data notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show a "check-your-entries" notice to the user?', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_edit_data_notice',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
				'desc_tip'	=> __( 'This notice will be added right before the order comments field.', 'woocommerce-germanized' ),
			),

			array(
				'title' 	=> __( 'Checkout Table Color', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_table_color',
				'desc_tip'	=> __( 'Choose the color of your checkout product table. This table should be highlighted within your checkout page.', 'woocommerce-germanized' ),
				'default'	=> '#eeeeee',
				'type' 		=> 'color',
			),

			array(
				'title' 	=> __( 'Checkout Legal Display', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Use Text without Checkbox', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_display_checkout_legal_no_checkbox',
				'desc_tip'	=> __( 'This version will remove checkboxes from Checkout and display a text instead. This seems to be legally compliant (Zalando & Co are using this option).', 'woocommerce-germanized' ),
				'default'	=> 'no',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Legal Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a Plain Text which will be shown right above checkout submit button. Use {term_link}{/term_link}, {data_security_link}{/data_security_link}, {revocation_link}{/revocation_link} as Placeholders for the links to legal pages.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'With your order, you agree to have read and understood our {term_link}Terms and Conditions{/term_link} and your {revocation_link}Right of Recission{/revocation_link}.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Legal Text Error', 'woocommerce-germanized' ),
				'desc' 		=> __( 'If you have chosen to use checkbox validation please choose a error message which will be shown if the user doesn\'t check checkbox. Use {term_link}{/term_link}, {data_security_link}{/data_security_link}, {revocation_link}{/revocation_link} as Placeholders for the links to legal pages.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'To finish the order you have to accept to our {term_link}Terms and Conditions{/term_link} and {revocation_link}Right of Recission{/revocation_link}.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text_error',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Show digital notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show checkbox for digital products.', 'woocommerce-germanized' ),
				'desc_tip'	=> __( 'Disable this option if you want your customers to obtain their right of recission even if digital products are being bought.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_legal_digital_checkbox',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Digital Product types', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Select product types for which the loss of recission notice is shown. Product types like "simple product" may be redudant because they include virtual and downloadable products.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_checkout_legal_digital_types',
				'default'	=> array( 'downloadable' ),
				'class'		=> 'chosen_select',
				'options'	=> $digital_type_options,
				'type' 		=> 'multiselect',
			),

			array(
				'title' 	=> __( 'Legal Digital Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a Plain Text which will be shown right above checkout submit button if a user has picked a digital product. See legal text option for possible placeholders.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'For digital products: I strongly agree that the execution of the agreement starts before the revocation period has expired. I am aware that my right of withdrawal ceases with the beginning of the agreement.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text_digital',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Legal Digital Error', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be shown as error message if customer has not checked the corresponding checkbox. See legal text option for possible placeholders.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'To retrieve direct access to digital content you have to agree to the loss of your right of withdrawal.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text_digital_error',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Digital Confirmation Notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be appended to your order processing email if the order contains digital products. Use placeholders {link}{/link} to insert link to right of withdrawal page.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_order_confirmation_legal_digital_notice',
				'default'	=> __( 'Furthermore you have expressly agreed to start the performance of the contract for digital items (e.g. downloads) before expiry of the withdrawal period. I have noted to lose my {link}right of withdrawal{/link} with the beginning of the performance of the contract.', 'woocommerce-germanized' ),
				'type' 		=> 'textarea',
				'css' 		=> 'width:100%; height: 65px;',
			),

			array(
				'title' 	=> __( 'Show service notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show checkbox for service products.', 'woocommerce-germanized' ),
				'desc_tip'	=> __( 'Disable this option if you want your customers to obtain their right of recission even if service products are being bought.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_legal_service_checkbox',
				'default'	=> 'yes',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Legal Service Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a Plain Text which will be shown right above checkout submit button if a user has picked a service product. See legal text option for possible placeholders.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   => __( 'For services: I demand and acknowledge the immediate performance of the service before the expiration of the withdrawal period. I acknowledge that thereby I lose my right to cancel once the service has begun.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text_service',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Legal Service Error', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be shown as error message if customer has not checked the corresponding checkbox. See legal text option for possible placeholders.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'To allow the immediate performance of the services you have to agree to the loss of your right of withdrawal.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text_service_error',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Service Confirmation Notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'This text will be appended to your order processing email if the order contains service products. Use placeholders {link}{/link} to insert link to right of withdrawal page.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_order_confirmation_legal_service_notice',
				'default'	=> __( 'Furthermore you have expressly agreed to start the performance of the contract for services before expiry of the withdrawal period. I have noted to lose my {link}right of withdrawal{/link} with the beginning of the performance of the contract.', 'woocommerce-germanized' ),
				'type' 		=> 'textarea',
				'css' 		=> 'width:100%; height: 65px;',
			),

			array(
				'title' 	=> __( 'Parcel Delivery Checkbox', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Show checkbox for data transmission to third party parcel service providers.', 'woocommerce-germanized' ),
				'desc_tip'	=> __( 'You may optionally choose to show a checkbox which lets the customer accept data transmission to a third party parcel service provider to receive parcel delivery reminders.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_legal_parcel_delivery_checkbox',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Checkbox required', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Make the parcel delivery checkbox a required field.', 'woocommerce-germanized' ),
				'desc_tip'	=> __( 'For some reason you may want to force your customers to Opt-In to the data transmission to a third party parcel service provider.', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_checkout_legal_parcel_delivery_checkbox_required',
				'default'	=> 'no',
				'type' 		=> 'checkbox',
			),

			array(
				'title' 	=> __( 'Parcel Delivery Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a Plain Text which will be shown right next to the corresponding checkbox to inform the customer about the data being transfered to the third party shipping supplier. Use {shipping_method_title} to insert the shipping method title.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'default'   => __( 'Yes, I would like to be reminded via E-mail about parcel delivery ({shipping_method_title}). Your E-mail Address will only be transferred to our parcel service provider for that particular reason.', 'woocommerce-germanized' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_checkout_legal_text_parcel_delivery',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Shipping Methods', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Select shipping methods which are applicable for the Opt-In Checkbox.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzd_checkout_legal_parcel_delivery_checkbox_methods',
				'default'	=> array(),
				'class'		=> 'chosen_select',
				'options'	=> $shipping_methods_options,
				'type' 		=> 'multiselect',
			),

			array(
				'title' 	=> __( 'Pay now Button', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Add a pay now button to emails and order success page.', 'woocommerce-germanized' ),
				'desc_tip' 	=> __( 'Add a pay now button to order confirmation email and order success page if the order awaits payment (PayPal etc).', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_order_pay_now_button',
				'type' 		=> 'checkbox',
				'default'	=> 'yes',
			),

			array(
				'title' 	=> __( 'Order Success Text', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Choose a custom text to display on order success page.', 'woocommerce-germanized' ),
				'desc_tip'	=> true,
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzd_order_success_text',
				'type' 		=> 'textarea',
			),

			array(
				'title' 	=> __( 'Order Success Data', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Hide product table and customer data on order success page', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_hide_order_success_details',
				'type' 		=> 'checkbox',
				'default'	=> 'no',
			),

			array( 'type' => 'sectionend', 'id' => 'checkout_options' ),

		);

		return apply_filters( 'woocommerce_germanized_settings_display', $settings );

	}

	public function output() {
		
		global $current_section;
		$settings = $this->get_settings();
		$sidebar = $this->get_sidebar();
		
		if ( $this->get_sections() ) {
			foreach ( $this->get_sections() as $section => $name ) {
				if ( $section == $current_section ) {
					$settings = apply_filters( 'woocommerce_gzd_get_settings_' . $section, $this->get_settings() );
					$sidebar = apply_filters( 'woocommerce_gzd_get_sidebar_' . $section, $sidebar );
				}
			}
		}

		do_action( 'woocommerce_gzd_before_section_output', $current_section );

		include_once( WC_Germanized()->plugin_path() . '/includes/admin/views/html-settings-section.php' );
	}

	public function get_sidebar() {
		ob_start();
		include_once( WC_Germanized()->plugin_path() . '/includes/admin/views/html-settings-sidebar.php' );
		$content = ob_get_clean();
		return $content;
	}

	/**
	 * Save settings
	 */
	public function save() {

		global $current_section;

		$settings = array();

		if ( $this->get_sections() ) {
			foreach ( $this->get_sections() as $section => $name ) {
				if ( $section == $current_section ) {
					$settings = apply_filters( 'woocommerce_gzd_get_settings_' . $section, $this->get_settings() );
				}
			}
		}
		if ( empty( $settings ) )
			return;

		do_action( 'woocommerce_gzd_before_save_section_' . $current_section, $settings );

		if ( apply_filters( 'wc_germanized_show_settings_' . $current_section, true ) )
			WC_Admin_Settings::save_fields( $settings );

		do_action( 'woocommerce_gzd_after_save_section_' . $current_section, $settings );
	}

	public function before_save( $settings ) {
		if ( !empty( $settings ) ) {
			foreach ( $settings as $setting ) {
				if ( $setting[ 'id' ] == 'woocommerce_gzd_small_enterprise' ) {
					if ( get_option('woocommerce_gzd_small_enterprise') == 'no' && !empty( $_POST['woocommerce_gzd_small_enterprise'] ) ) {
						// Update woocommerce options to not show tax
						update_option( 'woocommerce_calc_taxes', 'no' );
						update_option( 'woocommerce_prices_include_tax', 'yes' );
						update_option( 'woocommerce_tax_display_shop', 'incl' );
						update_option( 'woocommerce_tax_display_cart', 'incl' );
						update_option( 'woocommerce_price_display_suffix', '' );
					} elseif ( get_option('woocommerce_gzd_small_enterprise') == 'yes' && ! isset( $_POST['woocommerce_gzd_small_enterprise'] ) ) {
						// Update woocommerce options to show tax
						update_option( 'woocommerce_calc_taxes', 'yes' );
						update_option( 'woocommerce_prices_include_tax', 'yes' );
					}
				} elseif ( $setting[ 'id' ] == 'woocommerce_gzd_enable_virtual_vat' ) {
					if ( get_option( 'woocommerce_gzd_enable_virtual_vat' ) != 'yes' && ! empty( $_POST[ 'woocommerce_gzd_enable_virtual_vat' ] ) ) {
						if ( ! empty( $_POST[ 'woocommerce_gzd_small_enterprise' ] ) )
							continue;
						// Update WooCommerce options to show prices including taxes
						// Check if is small business
						update_option( 'woocommerce_prices_include_tax', 'yes' );
						update_option( 'woocommerce_tax_display_shop', 'incl' );
						update_option( 'woocommerce_tax_display_cart', 'incl' );
						update_option( 'woocommerce_tax_total_display', 'itemized' );
					}
				}
			}
		}
	}

	public function after_save( $settings ) {
		if ( ! empty( $_POST[ 'woocommerce_gzd_small_enterprise' ] ) ) {
			update_option( 'woocommerce_gzd_shipping_tax', 'no' );
			update_option( 'woocommerce_gzd_shipping_tax_force', 'no' );
			update_option( 'woocommerce_gzd_fee_tax', 'no' );
			update_option( 'woocommerce_gzd_fee_tax_force', 'no' );
			if ( ! empty( $_POST[ 'woocommerce_gzd_enable_virtual_vat' ] ) ) {
				update_option( 'woocommerce_gzd_enable_virtual_vat', 'no' );
				WC_Admin_Settings::add_error( __( 'Sorry, but the new Virtual VAT rules cannot be applied to small business.', 'woocommerce-germanized' ) );
			}
		}
		if ( 'yes' === get_option( 'woocommerce_gzd_enable_virtual_vat' ) ) {
			// Make sure that tax based location is set to billing address
			if ( 'base' === get_option( 'woocommerce_tax_based_on' ) )
				update_option( 'woocommerce_tax_based_on', 'billing' );
		}
	}

	public function output_premium_section() {
		global $current_section;
		if ( ! isset( $this->premium_sections[ $current_section ] ) )
			return;
		$GLOBALS[ 'hide_save_button' ] = true;
		$section_title = $this->premium_sections[ $current_section ];
		include_once( WC_Germanized()->plugin_path() . '/includes/admin/views/html-settings-pro.php' );
	}

	public function set_premium_sections( $sections ) {
		return $sections + $this->premium_sections;
	}

	public function get_premium_settings() {
		return array();
	}

	public function get_premium_sidebar() {
		return '';
	}

}

endif;

?>