<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Tax settings.
 *
 * @class        WC_GZD_Settings_Tab_Taxes
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_General extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Adjust general options e.g. legal pages.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'General', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'general';
	}

	public function get_sections() {
		return array(
			''               => __( 'Legal Pages', 'woocommerce-germanized' ),
			'disputes'       => __( 'Dispute Resolution', 'woocommerce-germanized' ),
			'small_business' => __( 'Small Businesses', 'woocommerce-germanized' ),
			'checkout'       => __( 'Checkout', 'woocommerce-germanized' ),
			'shop'           => __( 'Shop', 'woocommerce-germanized' ),
		);
	}

	public function get_section_description( $section ) {
		if ( 'disputes' === $section ) {
			return sprintf( __( 'Since Feb. 1 2017 regulations regarding alternative dispute resolution take effect. Further information regarding your duty to supply information can be found <a href="%s" target="_blank">here</a>.', 'woocommerce-germanized' ), 'http://shopbetreiber-blog.de/2017/01/05/streitschlichtung-neue-infopflichten-fuer-alle-online-haendler-ab-1-februar/' );
		}

		return '';
	}

	protected function get_legal_page_settings() {
		$additional_data_privacy_info = sprintf( __( 'Checkbox status: %s', 'woocommerce-germanized' ), WC_GZD_Admin_Legal_Checkboxes::instance()->get_terms_policy_status_html() );

		return array(
			array( 'title' => '', 'type' => 'title', 'id' => 'legal_page_options' ),

			array(
				'title'    => __( 'Terms & Conditions', 'woocommerce-germanized' ),
				'desc_tip' => __( 'This page should contain your terms & conditions.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_terms_page_id',
				'type'     => 'single_select_page',
				'default'  => '',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
				'desc'     => ( ! get_option( 'woocommerce_terms_page_id' ) ? sprintf( __( 'Don\'t have terms & conditions yet? <a href="%s">Generate now</a>!', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-terms_generator' ) ) : '' ),
			),
			array(
				'title'    => __( 'Cancellation Policy', 'woocommerce-germanized' ),
				'desc_tip' => __( 'This page should contain information regarding your customer\'s Right of Withdrawal.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_revocation_page_id',
				'type'     => 'single_select_page',
				'default'  => '',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
				'desc'     => ( ! get_option( 'woocommerce_revocation_page_id' ) ? sprintf( __( 'Don\'t have a revocation page yet? <a href="%s">Generate now</a>!', 'woocommerce-germanized' ), admin_url( 'admin.php?page=wc-settings&tab=germanized-revocation_generator' ) ) : '' ),
			),

			array(
				'title'    => __( 'Send withdrawal to', 'woocommerce-germanized' ),
				'desc'     => __( 'Type in an address, telephone/telefax number, email address which is to be used as the recipient address of the withdrawal.', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'css'      => 'width:100%; height: 65px;',
				'id'       => 'woocommerce_gzd_revocation_address',
				'type'     => 'textarea',
				'default'  => wc_gzd_get_default_revocation_address(),
			),

			array(
				'title'    => __( 'Imprint', 'woocommerce-germanized' ),
				'desc'     => __( 'This page should contain an imprint with your company\'s information.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_imprint_page_id',
				'type'     => 'single_select_page',
				'default'  => '',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Privacy Policy', 'woocommerce-germanized' ),
				'desc_tip' => __( 'This page should contain information regarding your privacy policy.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_data_security_page_id',
				'type'     => 'single_select_page',
				'default'  => '',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
				'desc'     => '<div class="wc-gzd-additional-desc">' . $additional_data_privacy_info . '</div>',
			),
			array(
				'title'    => __( 'Payment Methods', 'woocommerce-germanized' ),
				'desc'     => __( 'This page should contain information regarding the Payment Methods that are chooseable during checkout.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_payment_methods_page_id',
				'type'     => 'single_select_page',
				'default'  => '',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
				'desc_tip' => true,
			),
			array(
				'title'    => __( 'Shipping Methods', 'woocommerce-germanized' ),
				'desc'     => __( 'This page should contain information regarding shipping methods that are chooseable during checkout.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_shipping_costs_page_id',
				'type'     => 'single_select_page',
				'default'  => '',
				'class'    => 'wc-enhanced-select-nostd',
				'css'      => 'min-width:300px;',
				'desc_tip' => true,
			),

			array( 'type' => 'sectionend', 'id' => 'legal_page_options' ),
		);
	}

	protected function get_dispute_resolution_settings() {
		$complaints_pages                 = WC_GZD_Admin::instance()->get_complaints_shortcode_pages();
		$is_complaints_shortcode_inserted = true;
		$complaints_shortcode_missing     = array();

		foreach ( $complaints_pages as $page => $page_id ) {
			if ( ! WC_GZD_Admin::instance()->is_complaints_shortcode_inserted( $page_id ) ) {
				$is_complaints_shortcode_inserted = false;
				array_push( $complaints_shortcode_missing, ( $page === 'terms' ? __( 'Terms & Conditions', 'woocommerce-germanized' ) : __( 'Imprint', 'woocommerce-germanized' ) ) );
			}
		}

		$additional_shortcode_info = __( 'This status indicates whether your terms & conditions contain the [gzd_complaints] shortcode which outputs the complaints options chosen from above or not. If you\'ve added the text manually, you might ignore this status.', 'woocommerce-germanized' );

		return array(
			array( 'title' => '', 'type' => 'title', 'id' => 'complaints_options' ),

			array(
				'title'    => __( 'Dispute Resolution', 'woocommerce-germanized' ),
				'desc'     => __( 'You may select whether you are willing, obliged or not willing to participate in dispute settlement proceeedings before a consumer arbitration board. The corresponding Resolution Text is attached to the [gzd_complaints] shortcode which you should add to your imprint. Trusted Shops advises you to add that text to your Terms & Conditions as well.', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'       => 'woocommerce_gzd_dispute_resolution_type',
				'type'     => 'radio',
				'default'  => 'none',
				'options'  => array(
					'none'    => __( 'Not obliged, not willing', 'woocommerce-germanized' ),
					'willing' => __( 'Not obliged, willing', 'woocommerce-germanized' ),
					'obliged' => __( 'Obliged', 'woocommerce-germanized' ),
				),
			),
			array(
				'title'    => __( 'Resolution Text', 'woocommerce-germanized' ),
				'desc'     => __( 'Adapt this example text regarding alternative dispute resolution to your needs. Text will be added to the [gzd_complaints] Shortcode. You may as well add this text to your terms & conditions.', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'default'  => __( 'The european commission provides a platform for online dispute resolution (OS) which is accessible at https://ec.europa.eu/consumers/odr. We are not obliged nor willing to participate in dispute settlement proceedings before a consumer arbitration board.', 'woocommerce-germanized' ),
				'css'      => 'width:100%; height: 65px;',
				'id'       => 'woocommerce_gzd_alternative_complaints_text_none',
				'type'     => 'textarea',
			),
			array(
				'title'    => __( 'Resolution Text', 'woocommerce-germanized' ),
				'desc'     => __( 'Adapt this example text regarding alternative dispute resolution to your needs. Text will be added to the [gzd_complaints] Shortcode. You may as well add this text to your terms & conditions.', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'default'  => __( 'The european commission provides a platform for online dispute resolution (OS) which is accessible at https://ec.europa.eu/consumers/odr. Consumers may use this platform for the settlements of their disputes. We are in principle prepared to participate in an extrajudicial arbitration proceeding.', 'woocommerce-germanized' ),
				'css'      => 'width:100%; height: 65px;',
				'id'       => 'woocommerce_gzd_alternative_complaints_text_willing',
				'type'     => 'textarea',
			),
			array(
				'title'    => __( 'Resolution Text', 'woocommerce-germanized' ),
				'desc'     => __( 'Adapt this example text regarding alternative dispute resolution to your needs. Text will be added to the [gzd_complaints] Shortcode. You may as well add this text to your terms & conditions.', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'default'  => __( 'The european commission provides a platform for online dispute resolution (OS) which is accessible at https://ec.europa.eu/consumers/odr. Consumers may contact [Name, Address, Website of arbitration board] for the settlements of their disputes. We are obliged to participate in arbitration proceeding before that board.', 'woocommerce-germanized' ),
				'css'      => 'width:100%; height: 65px;',
				'id'       => 'woocommerce_gzd_alternative_complaints_text_obliged',
				'type'     => 'textarea',
			),
			array(
				'title'    => __( 'Shortcode Status', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_complaints_procedure_status',
				'type'     => 'html',
				'desc_tip' => false,
				'html'     => '<p><span class="wc-gzd-status-text wc-gzd-text-' . ( $is_complaints_shortcode_inserted ? 'green' : 'red' ) . '"> ' . ( $is_complaints_shortcode_inserted ? __( 'Found', 'woocommerce-germanized' ) : sprintf( __( 'Not found within %s', 'woocommerce-germanized' ), implode( ', ', $complaints_shortcode_missing ) ) ) . '</span> ' . ( ! $is_complaints_shortcode_inserted ? '<a class="button button-secondary" style="margin-left: 1em" href="' . wp_nonce_url( add_query_arg( array( 'complaints' => 'add' ) ), 'append-complaints-shortcode' ) . '">' . __( 'Append it now', 'woocommerce-germanized' ) . '</a></p>' : '' ) . '<div class="wc-gzd-additional-desc">' . $additional_shortcode_info . '</div>',
			),

			array( 'type' => 'sectionend', 'id' => 'complaints_options' ),
		);
	}

	protected function get_small_business_settings() {
		return array(
			array( 'title' => '', 'type' => 'title', 'desc' => '', 'id' => 'small_business_options' ),

			array(
				'title'   => __( 'Small-Enterprise-Regulation', 'woocommerce-germanized' ),
				'desc'    => __( 'VAT based on &#167;19 UStG', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'Enable this option if you have chosen to apply to <a href="%s" target="_blank">&#167;19 UStG</a>.', 'woocommerce-germanized' ), esc_url( 'http://www.gesetze-im-internet.de/ustg_1980/__19.html' ) ) . '</div>',
				'id'      => 'woocommerce_gzd_small_enterprise',
				'default' => 'no',
				'type'    => 'gzd_toggle',
			),
			array(
				'title'             => __( 'Notice Text', 'woocommerce-germanized' ),
				'desc'              => __( 'You may want to adjust the small buisness notice text to meet your criteria.', 'woocommerce-germanized' ),
				'desc_tip'          => true,
				'id'                => 'woocommerce_gzd_small_enterprise_text',
				'type'              => 'textarea',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_small_enterprise' => '',
				),
				'default'           => __( 'Value added tax is not collected, as small businesses according to §19 (1) UStG.', 'woocommerce-germanized' ),
				'css'               => 'width:100%; height: 50px;',
			),

			array( 'type' => 'sectionend', 'id' => 'small_business_options' ),
		);
	}

	protected function get_checkout_settings() {
		$shipping_methods_options = WC_GZD_Admin::instance()->get_shipping_method_instances_options();

		return array(
			array( 'title' => '', 'type' => 'title', 'desc' => '', 'id' => 'checkout_options' ),

			array(
				'title'   => __( 'Title', 'woocommerce-germanized' ),
				'desc'    => __( 'Add a title field to the address within checkout.', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_gzd_checkout_address_field',
				'type'    => 'gzd_toggle',
				'default' => 'no',
			),
			array(
				'title'   => __( 'Validate street number', 'woocommerce-germanized' ),
				'desc'    => __( 'Force the existence of a street number within the first address field.', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_gzd_checkout_validate_street_number',
				'type'    => 'select',
				'default' => 'never',
				'options' => array(
					'never'      => __( 'Never', 'woocommerce-germanized' ),
					'always'     => __( 'Always', 'woocommerce-germanized' ),
					'base_only'  => __( 'Base country only', 'woocommerce-germanized' ),
					'eu_only'    => __( 'EU countries only', 'woocommerce-germanized' ),
				),
			),
			array(
				'title'   => __( 'Disallow cancellations', 'woocommerce-germanized' ),
				'desc'    => __( 'Don\'t allow customers to manually cancel orders.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . __( 'By default payment methods like PayPal allow order cancellation by clicking the abort link. This option will stop customers from manually cancel orders.', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'woocommerce_gzd_checkout_stop_order_cancellation',
				'type'    => 'gzd_toggle',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Disallow gateway choosing', 'woocommerce-germanized' ),
				'desc'    => __( 'Don\'t allow customers to change the payment gateway after ordering.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . __( 'Customers paying through a gateway which allows later payment (e.g. PayPal) will find a link within their customer account which redirects them to a pay page. This page offers the possibility to choose another gateway than before which may lead to further problems e.g. additional gateway costs etc. which would require a new order submittal. This option makes sure the customer gets redirected directly to the gateways payment page, e.g. to PayPal.', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'woocommerce_gzd_checkout_disallow_belated_payment_method_selection',
				'type'    => 'gzd_toggle',
				'default' => 'no',
			),
			array(
				'title'   => __( 'Free shipping', 'woocommerce-germanized' ),
				'desc'    => __( 'Force free shipping method if available.', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . __( 'By default WooCommerce will let customers choose other shipping methods than free shipping (if available). This option will force free shipping if available.', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'woocommerce_gzd_display_checkout_free_shipping_select',
				'default' => 'no',
				'type'    => 'gzd_toggle',
			),

			array(
				'title'             => __( 'Exclude Methods', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_gzd_display_checkout_free_shipping_excluded',
				'default'           => array(),
				'class'             => 'wc-enhanced-select',
				'type'              => 'multiselect',
				'options'           => $shipping_methods_options,
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_display_checkout_free_shipping_select' => '',
				),
				'desc'              => '<div class="wc-gzd-additional-desc">' . __( 'Optionally choose methods which should be excluded from hiding when free shipping is available (e.g. express shipping options).', 'woocommerce-germanized' ) . '</div>',
			),

			array( 'type' => 'sectionend', 'id' => 'checkout_options' ),
		);
	}

	protected function get_shop_settings() {
		return array(
			array( 'title' => '', 'type' => 'title', 'desc' => '', 'id' => 'shop_options' ),

			array(
				'title'    => __( 'Add to Cart', 'woocommerce-germanized' ),
				'desc'     => __( 'Show add to cart button on listings.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_display_listings_add_to_cart',
				'default'  => 'yes',
				'type'     => 'gzd_toggle',
				'desc_tip' => __( 'unset this option if you don\'t want to show the add to cart button within the product listings', 'woocommerce-germanized' ),
			),
			array(
				'title'    => __( 'Link', 'woocommerce-germanized' ),
				'desc'     => __( 'Link to product details page instead of add to cart within listings.', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_display_listings_link_details',
				'default'  => 'no',
				'type'     => 'gzd_toggle',
				'desc_tip' => __( 'Decide whether you like to link to your product\'s details page instead of displaying an add to cart button within product listings.', 'woocommerce-germanized' ),
			),
			array(
				'title'             => __( 'Product Details Text', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_gzd_display_listings_link_details_text',
				'default'           => __( 'Details', 'woocommerce-germanized' ),
				'type'              => 'text',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzd_display_listings_link_details' => '',
				),
				'desc_tip'          => __( 'If you have chosen to link to product details page instead of add to cart URL you may want to change the button text.', 'woocommerce-germanized' ),
				'css'               => 'min-width:300px;',
			),

			array( 'type' => 'sectionend', 'id' => 'shop_options' ),
		);
	}

	public function get_pointers() {
		$current  = $this->get_current_section();
		$pointers = array();

		if ( '' === $current ) {
			$pointers = array(
				'pointers' => array(
					'breadcrumb'  => array(
						'target'       => '.breadcrumb-item-main a',
						'next'         => 'tab',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Overview', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html__( 'You can always return to the settings overview by navigating through the breadcrumb navigation.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'top',
								'align' => 'left',
							),
						),
					),
					'tab'         => array(
						'target'       => 'ul.subsubsub li:nth-of-type(2) a',
						'next'         => 'legal_pages',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Sections', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html__( 'Each setting tab might have sub sections containing more specific options.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'top',
								'align' => 'left',
							),
						),
					),
					'legal_pages' => array(
						'target'       => '#select2-woocommerce_terms_page_id-container',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-shopmarks&tutorial=yes' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Legal Pages', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html__( 'Adjust legal pages e.g. terms and conditions. These pages are used to add links within checkboxes and text attachments to emails.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		}

		return $pointers;
	}

	public function get_tab_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = $this->get_legal_page_settings();
		} elseif ( 'disputes' === $current_section ) {
			$settings = $this->get_dispute_resolution_settings();
		} elseif ( 'small_business' === $current_section ) {
			$settings = $this->get_small_business_settings();
		} elseif ( 'checkout' === $current_section ) {
			$settings = $this->get_checkout_settings();
		} elseif ( 'shop' === $current_section ) {
			$settings = $this->get_shop_settings();
		}

		return $settings;
	}

	protected function before_save( $settings, $current_section = '' ) {
		if ( 'small_business' === $current_section ) {
			if ( ! wc_gzd_is_small_business() && ! empty( $_POST['woocommerce_gzd_small_enterprise'] ) ) {
				WC_GZD_Admin::instance()->enable_small_business_options();
			} elseif ( wc_gzd_is_small_business() && ! isset( $_POST['woocommerce_gzd_small_enterprise'] ) ) {
				WC_GZD_Admin::instance()->disable_small_business_options();
			}
		}

		parent::before_save( $settings, $current_section );
	}
}