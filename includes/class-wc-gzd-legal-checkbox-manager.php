<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Legal_Checkbox_Manager {

	protected $checkboxes = array();

	protected static $_instance = null;

	protected $options = null;

	protected $core_checkboxes = array(
		'terms',
		'download',
		'service',
		'parcel_delivery',
		'privacy',
		'sepa',
		'review_reminder',
		'used_goods_warranty',
		'defective_copy',
		'photovoltaic_systems',
	);

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
		$this->checkboxes = array();

		add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_checkout' ), 1, 2 );
		add_filter( 'woocommerce_process_registration_errors', array( $this, 'validate_register' ), 10, 1 );
		add_action( 'woocommerce_before_pay_action', array( $this, 'validate_pay_for_order' ), 10, 1 );
		add_filter( 'pre_comment_approved', array( $this, 'validate_reviews' ), 10, 2 );
		add_action( 'before_woocommerce_pay', array( $this, 'maybe_hide_terms_checkbox' ), 10 );

		// Cannot use after_setup_theme here because language packs are not yet loaded
		add_action( 'init', array( $this, 'do_register_action' ), 50 );

		add_action(
			'woocommerce_gzd_run_legal_checkboxes_checkout',
			array(
				$this,
				'show_conditionally_checkout',
			),
			10
		);

		add_action(
			'woocommerce_gzd_run_legal_checkboxes_pay_for_order',
			array(
				$this,
				'show_conditionally_pay_for_order',
			),
			10
		);

		add_action(
			'woocommerce_gzd_run_legal_checkboxes_register',
			array(
				$this,
				'show_conditionally_register',
			),
			10
		);

		add_action(
			'woocommerce_gzd_run_legal_checkboxes_reviews',
			array(
				$this,
				'show_conditionally_reviews',
			),
			10
		);

		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'refresh_fragments_checkout' ), 10, 1 );
	}

	public function maybe_hide_terms_checkbox() {
		/**
		 * Disable terms checkbox on pay for order page in case redirection is forced.
		 */
		if ( defined( 'WC_GZD_FORCE_PAY_ORDER' ) && WC_GZD_FORCE_PAY_ORDER ) {
			foreach ( $this->get_checkboxes( array( 'locations' => 'pay_for_order' ) ) as $checkbox_id => $checkbox ) {
				$locations = array_diff( $checkbox->get_locations(), array( 'pay_for_order' ) );
				$checkbox->set_locations( $locations );
			}
		}
	}

	public function refresh_fragments_checkout( $fragments ) {
		$this->maybe_do_hooks( 'checkout' );

		foreach (
			$this->get_checkboxes(
				array(
					'locations'         => 'checkout',
					'refresh_fragments' => true,
				)
			) as $id => $checkbox
		) {
			ob_start();
			$checkbox->render();
			$html = ob_get_clean();

			$fragments[ '.wc-gzd-checkbox-placeholder-' . esc_attr( $checkbox->get_html_id() ) ] = $html;
		}

		return $fragments;
	}

	public function get_core_checkbox_ids() {
		/**
		 * Filter that returns the core checkbox ids.
		 *
		 * @param array $checkbox_ids Array containg checkbox ids.
		 *
		 * @since 2.0.0
		 */
		return apply_filters( 'woocommerce_gzd_legal_checkbox_core_ids', $this->core_checkboxes );
	}

	protected function get_legal_label_args() {
		return array(
			'{term_link}'           => '<a href="' . esc_url( wc_gzd_get_page_permalink( 'terms' ) ) . '" target="_blank">',
			'{/term_link}'          => '</a>',
			'{revocation_link}'     => '<a href="' . esc_url( wc_gzd_get_page_permalink( 'revocation' ) ) . '" target="_blank">',
			'{/revocation_link}'    => '</a>',
			'{data_security_link}'  => '<a href="' . esc_url( wc_gzd_get_page_permalink( 'data_security' ) ) . '" target="_blank">',
			'{/data_security_link}' => '</a>',
		);
	}

	public function register_core_checkboxes() {
		wc_gzd_register_legal_checkbox(
			'terms',
			array(
				'html_id'              => 'legal',
				'html_name'            => 'legal',
				'html_wrapper_classes' => array( 'legal' ),
				'hide_input'           => false,
				'label'                => __( 'With your order, you agree to have read and understood our {term_link}Terms and Conditions{/term_link} and {revocation_link}Cancellation Policy{/revocation_link}.', 'woocommerce-germanized' ),
				'error_message'        => __( 'To complete the order you have to accept to our {term_link}Terms and Conditions{/term_link} and {revocation_link}Cancellation Policy{/revocation_link}.', 'woocommerce-germanized' ),
				'is_mandatory'         => true,
				'priority'             => 0,
				'template_name'        => 'checkout/terms.php',
				'template_args'        => array( 'gzd_checkbox' => true ),
				'is_core'              => true,
				'admin_name'           => __( 'Legal', 'woocommerce-germanized' ),
				'admin_desc'           => __( 'General legal checkbox which shall include terms and cancellation policy.', 'woocommerce-germanized' ),
				'locations'            => array( 'checkout', 'pay_for_order' ),
			)
		);

		wc_gzd_register_legal_checkbox(
			'download',
			array(
				'html_id'              => 'data-download',
				'html_name'            => 'download-revocate',
				'html_wrapper_classes' => array( 'legal' ),
				'label'                => __( 'For digital products: I strongly agree that the execution of the agreement starts before the revocation period has expired. I am aware that my right of withdrawal ceases with the beginning of the agreement.', 'woocommerce-germanized' ),
				'error_message'        => __( 'To retrieve direct access to digital content you have to agree to the loss of your right of withdrawal.', 'woocommerce-germanized' ),
				'is_mandatory'         => true,
				'priority'             => 1,
				'is_enabled'           => true,
				'is_core'              => true,
				'is_shown'             => false,
				'admin_name'           => __( 'Digital', 'woocommerce-germanized' ),
				'admin_desc'           => __( 'Asks the customer to skip revocation period for digital products.', 'woocommerce-germanized' ),
				'locations'            => array( 'checkout' ),
				'types'                => array( 'downloadable' ),
			)
		);

		wc_gzd_register_legal_checkbox(
			'service',
			array(
				'html_id'              => 'data-service',
				'html_name'            => 'service-revocate',
				'html_wrapper_classes' => array( 'legal' ),
				'label'                => __( 'For services: I demand and acknowledge the immediate performance of the service before the expiration of the withdrawal period. I acknowledge that thereby I lose my right to cancel once the service has begun.', 'woocommerce-germanized' ),
				'error_message'        => __( 'To allow the immediate performance of the services you have to agree to the loss of your right of withdrawal.', 'woocommerce-germanized' ),
				'is_mandatory'         => true,
				'priority'             => 2,
				'is_enabled'           => true,
				'is_core'              => true,
				'is_shown'             => false,
				'admin_name'           => __( 'Service', 'woocommerce-germanized' ),
				'admin_desc'           => __( 'Asks the customer to skip revocation period for services.', 'woocommerce-germanized' ),
				'locations'            => array( 'checkout' ),
			)
		);

		wc_gzd_register_legal_checkbox(
			'parcel_delivery',
			array(
				'html_id'              => 'parcel-delivery-checkbox',
				'html_name'            => 'parcel_delivery_checkbox',
				'html_wrapper_classes' => array( 'legal' ),
				'label'                => __( 'Yes, I would like to be reminded via E-mail about parcel delivery ({shipping_method_title}). Your E-mail Address will only be transferred to our parcel service provider for that particular reason.', 'woocommerce-germanized' ),
				'label_args'           => array( '{shipping_method_title}' => '' ),
				'is_mandatory'         => false,
				'priority'             => 4,
				'is_enabled'           => false,
				'error_message'        => __( 'Please accept our parcel delivery agreement', 'woocommerce-germanized' ),
				'is_core'              => true,
				'is_shown'             => false,
				'supporting_locations' => array( 'checkout' ),
				'admin_name'           => __( 'Parcel Delivery', 'woocommerce-germanized' ),
				'admin_desc'           => __( 'Asks the customer to hand over data to the parcel delivery service provider.', 'woocommerce-germanized' ),
				'locations'            => array( 'checkout' ),
			)
		);

		// Age verification
		wc_gzd_register_legal_checkbox(
			'age_verification',
			array(
				'html_id'              => 'data-age-verification',
				'html_name'            => 'age-verification',
				'html_wrapper_classes' => array( 'legal' ),
				'label'                => __( 'I hereby confirm that I\'m at least {age} years old.', 'woocommerce-germanized' ),
				'label_args'           => array( '{age}' => '' ),
				'error_message'        => __( 'Please confirm your age.', 'woocommerce-germanized' ),
				'is_mandatory'         => true,
				'priority'             => 5,
				'is_enabled'           => true,
				'is_core'              => true,
				'is_shown'             => false,
				'admin_name'           => __( 'Age Verification', 'woocommerce-germanized' ),
				'admin_desc'           => __( 'Asks the customer to confirm a minimum age.', 'woocommerce-germanized' ),
				'locations'            => array( 'checkout' ),
			)
		);

		// New account
		wc_gzd_register_legal_checkbox(
			'privacy',
			array(
				'html_id'              => 'reg_data_privacy',
				'html_name'            => 'privacy',
				'html_wrapper_classes' => array( 'legal', 'form-row-wide', 'terms-privacy-policy' ),
				'label'                => __( 'Yes, I’d like create a new account and have read and understood the {data_security_link}data privacy statement{/data_security_link}.', 'woocommerce-germanized' ),
				'is_mandatory'         => true,
				'is_enabled'           => false,
				'error_message'        => __( 'Please accept our privacy policy to create a new customer account', 'woocommerce-germanized' ),
				'is_core'              => true,
				'is_shown'             => true,
				'priority'             => 4,
				'admin_name'           => __( 'New account', 'woocommerce-germanized' ),
				'admin_desc'           => __( 'Let customers accept your privacy policy before creating a new account.', 'woocommerce-germanized' ),
				'locations'            => array( 'register' ),
			)
		);

		$direct_debit_settings = get_option( 'woocommerce_direct-debit_settings' );

		// For validation, refresh and adjustments see WC_GZD_Gateway_Direct_Debit
		if ( is_array( $direct_debit_settings ) && 'yes' === $direct_debit_settings['enabled'] ) {
			$order_secret = isset( $_GET['key'], $_GET['pay_for_order'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$ajax_url     = wp_nonce_url(
				add_query_arg(
					array(
						'action'    => 'show_direct_debit',
						'order_key' => $order_secret,
					),
					admin_url( 'admin-ajax.php' )
				),
				'show_direct_debit'
			);

			/**
			 * Filter to adjust the direct debit mandate link.
			 *
			 * @param string $link The link.
			 *
			 * @since 1.8.5
			 */
			$ajax_url = apply_filters( 'woocommerce_gzd_direct_debit_ajax_url', $ajax_url );

			wc_gzd_register_legal_checkbox(
				'sepa',
				array(
					'html_id'              => 'direct-debit-checkbox',
					'html_name'            => 'direct_debit_legal',
					'html_wrapper_classes' => array( 'legal', 'direct-debit-checkbox' ),
					'label'                => __( 'I hereby agree to the {link}direct debit mandate{/link}.', 'woocommerce-germanized' ),
					'label_args'           => array(
						'{link}'  => '<a href="' . esc_url( $ajax_url ) . '" id="show-direct-debit-trigger" rel="prettyPhoto">',
						'{/link}' => '</a>',
					),
					'is_mandatory'         => true,
					'error_message'        => __( 'Please accept the direct debit mandate.', 'woocommerce-germanized' ),
					'priority'             => 5,
					'template_name'        => 'checkout/terms-sepa.php',
					'is_enabled'           => true,
					'is_core'              => true,
					'admin_name'           => __( 'SEPA', 'woocommerce-germanized' ),
					'admin_desc'           => __( 'Asks the customer to issue the SEPA mandate.', 'woocommerce-germanized' ),
					'locations'            => array( 'checkout', 'pay_for_order' ),
				)
			);
		}

		wc_gzd_register_legal_checkbox(
			'used_goods_warranty',
			array(
				'html_id'              => 'data-used-goods-warranty',
				'html_name'            => 'used-goods-warranty',
				'html_wrapper_classes' => array( 'legal' ),
				'label'                => __( 'For used goods: I have taken note that my warranty period is shortened to 12 months.', 'woocommerce-germanized' ),
				'error_message'        => __( 'Please make sure to check our warranty note on used goods.', 'woocommerce-germanized' ),
				'is_mandatory'         => true,
				'priority'             => 6,
				'is_enabled'           => false,
				'is_core'              => true,
				'is_shown'             => false,
				'admin_name'           => __( 'Used Goods', 'woocommerce-germanized' ),
				'admin_desc'           => __( 'Inform customers about shortened warranty for used goods.', 'woocommerce-germanized' ),
				'locations'            => array( 'checkout' ),
			)
		);

		wc_gzd_register_legal_checkbox(
			'defective_copy',
			array(
				'html_id'              => 'data-defective-copy',
				'html_name'            => 'defective-copy',
				'html_wrapper_classes' => array( 'legal' ),
				'label_args'           => array( '{defect_descriptions}' => '' ),
				'label'                => __( 'I have taken note of the following defects: {defect_descriptions}.', 'woocommerce-germanized' ),
				'error_message'        => __( 'Please make sure to check our note on defective copies.', 'woocommerce-germanized' ),
				'is_mandatory'         => true,
				'priority'             => 7,
				'is_enabled'           => true,
				'is_core'              => true,
				'is_shown'             => false,
				'admin_name'           => __( 'Defective Copies', 'woocommerce-germanized' ),
				'admin_desc'           => __( 'Inform customers about product defects.', 'woocommerce-germanized' ),
				'locations'            => array( 'checkout' ),
			)
		);

		if ( wc_gzd_base_country_supports_photovoltaic_system_vat_exempt() ) {
			wc_gzd_register_legal_checkbox(
				'photovoltaic_systems',
				array(
					'html_id'              => 'photovoltaic_systems',
					'html_name'            => 'photovoltaic_systems',
					'html_wrapper_classes' => array( 'photovoltaic_systems' ),
					'hide_input'           => false,
					'label'                => __( 'I hereby confirm that I am aware of the requirements for VAT exemption (based on §12 paragraph 3 UStG) and that they are met for this order.', 'woocommerce-germanized' ),
					'error_message'        => '',
					'is_mandatory'         => false,
					'is_shown'             => false,
					'priority'             => 8,
					'is_core'              => true,
					'admin_name'           => __( 'Photovoltaic Systems', 'woocommerce-germanized' ),
					'admin_desc'           => __( 'Let customers confirm that they are aware of the requirements for a VAT exemption.', 'woocommerce-germanized' ),
					'locations'            => array( 'checkout', 'pay_for_order' ),
				)
			);
		}

		/**
		 * After core checkbox registration.
		 *
		 * Fires after Germanized has registered it's core legal checkboxes.
		 * Might be used to register additional checkboxes.
		 *
		 * ```php
		 * function ex_after_register_checkboxes( $manager ) {
		 *      wc_gzd_register_legal_checkbox( array() );
		 * }
		 * add_action( 'woocommerce_gzd_register_legal_core_checkboxes', 'ex_after_register_checkboxes', 10, 1 );
		 * ```
		 *
		 * @param WC_GZD_Legal_Checkbox_Manager $this The legal checkbox manager instance.
		 *
		 * @since 2.0.0
		 *
		 */
		do_action( 'woocommerce_gzd_register_legal_core_checkboxes', $this );
	}

	public function show_conditionally_register() {
		$args = $this->get_cart_product_data();

		if ( WC()->customer ) {
			$args['country']  = WC()->customer->get_shipping_country() ? WC()->customer->get_shipping_country() : WC()->customer->get_billing_country();
			$args['postcode'] = WC()->customer->get_shipping_postcode() ? WC()->customer->get_shipping_postcode() : WC()->customer->get_billing_postcode();
		}

		$this->update_show_conditionally( 'register', $args );
	}

	public function show_conditionally_reviews() {
		$args = $this->get_cart_product_data();

		if ( WC()->customer ) {
			$args['country']  = WC()->customer->get_shipping_country() ? WC()->customer->get_shipping_country() : WC()->customer->get_billing_country();
			$args['postcode'] = WC()->customer->get_shipping_postcode() ? WC()->customer->get_shipping_postcode() : WC()->customer->get_billing_postcode();
		}

		$this->update_show_conditionally( 'reviews', $args );
	}

	/**
	 * @param WC_Product $_product
	 *
	 * @return []
	 */
	protected function get_product_category_ids( $_product ) {
		$category_ids = $_product->get_category_ids();

		/**
		 * Variations do not inherit parent category ids.
		 */
		if ( $_product->get_parent_id() > 0 ) {
			if ( $_parent = wc_get_product( $_product->get_parent_id() ) ) {
				$category_ids = array_unique( array_merge( $category_ids, $_parent->get_category_ids() ) );
			}
		}

		return $category_ids;
	}

	protected function get_cart_product_data() {
		$args = array(
			'is_downloadable'        => false,
			'is_service'             => false,
			'is_photovoltaic_system' => false,
			'has_defective_copies'   => false,
			'has_used_goods'         => false,
			'product_category_ids'   => array(),
		);

		if ( WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$_product = apply_filters( 'woocommerce_cart_item_product', $values['data'], $values, $cart_item_key );

				if ( wc_gzd_is_revocation_exempt( $_product ) ) {
					$args['is_downloadable'] = true;
				}

				if ( wc_gzd_is_revocation_exempt( $_product, 'service' ) ) {
					$args['is_service'] = true;
				}

				if ( wc_gzd_get_product( $_product )->is_used_good() ) {
					$args['has_used_goods'] = true;
				}

				if ( wc_gzd_get_product( $_product )->is_photovoltaic_system() ) {
					$args['is_photovoltaic_system'] = true;
				}

				if ( wc_gzd_get_product( $_product )->is_defective_copy() ) {
					$args['has_defective_copies'] = true;
				}

				if ( $_product ) {
					$args['product_category_ids'] = array_unique( array_merge( $args['product_category_ids'], $this->get_product_category_ids( $_product ) ) );
				}
			}
		}

		return $args;
	}

	public function show_conditionally_pay_for_order() {
		global $wp;

		$order_id = absint( $wp->query_vars['order-pay'] );

		if ( ! $order_id || ! ( $order = wc_get_order( $order_id ) ) ) {
			return;
		}

		$items = $order->get_items();

		$args = array(
			'is_downloadable'        => false,
			'is_service'             => false,
			'has_defective_copies'   => false,
			'has_used_goods'         => false,
			'is_photovoltaic_system' => false,
			'product_category_ids'   => array(),
			'country'                => $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country(),
			'postcode'               => $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode(),
			'company'                => $order->get_shipping_company() ? $order->get_shipping_company() : $order->get_billing_company(),
			'create_account'         => false,
			'order'                  => $order,
			'needs_age_verification' => wc_gzd_order_has_age_verification( $order_id ),
		);

		foreach ( $items as $key => $item ) {
			if ( $item && is_callable( array( $item, 'get_product' ) ) && ( $_product = $item->get_product() ) ) {
				if ( wc_gzd_is_revocation_exempt( $_product ) ) {
					$args['is_downloadable'] = true;
				}

				if ( wc_gzd_is_revocation_exempt( $_product, 'service' ) ) {
					$args['is_service'] = true;
				}

				if ( wc_gzd_get_product( $_product )->is_used_good() ) {
					$args['has_used_goods'] = true;
				}

				if ( wc_gzd_get_product( $_product )->is_photovoltaic_system() ) {
					$args['is_photovoltaic_system'] = true;
				}

				if ( wc_gzd_get_product( $_product )->is_defective_copy() ) {
					$args['has_defective_copies'] = true;
				}

				if ( $_product ) {
					$args['product_category_ids'] = array_unique( array_merge( $args['product_category_ids'], $this->get_product_category_ids( $_product ) ) );
				}
			}
		}

		$this->update_show_conditionally( 'pay_for_order', $args );
	}

	public function show_conditionally_checkout() {
		$args = array(
			'country'                => WC_GZD_Checkout::instance()->get_checkout_value( 'shipping_country' ) ? WC_GZD_Checkout::instance()->get_checkout_value( 'shipping_country' ) : WC_GZD_Checkout::instance()->get_checkout_value( 'billing_country' ),
			'postcode'               => WC_GZD_Checkout::instance()->get_checkout_value( 'shipping_postcode' ) ? WC_GZD_Checkout::instance()->get_checkout_value( 'shipping_postcode' ) : WC_GZD_Checkout::instance()->get_checkout_value( 'billing_postcode' ),
			'company'                => WC_GZD_Checkout::instance()->get_checkout_value( 'shipping_company' ) ? WC_GZD_Checkout::instance()->get_checkout_value( 'shipping_company' ) : WC_GZD_Checkout::instance()->get_checkout_value( 'billing_company' ),
			'create_account'         => WC_GZD_Checkout::instance()->get_checkout_value( 'createaccount' ) ? WC_GZD_Checkout::instance()->get_checkout_value( 'createaccount' ) : false,
			'needs_age_verification' => WC()->cart && wc_gzd_cart_needs_age_verification(),
		);

		$args = array_merge( $args, $this->get_cart_product_data() );

		$this->update_show_conditionally( 'checkout', $args );
	}

	protected function update_show_conditionally( $location, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'is_downloadable'        => false,
				'is_service'             => false,
				'has_defective_copies'   => false,
				'has_used_goods'         => false,
				'is_photovoltaic_system' => false,
				'product_category_ids'   => array(),
				'country'                => '',
				'postcode'               => '',
				'company'                => '',
				'create_account'         => false,
				'order'                  => false,
				'needs_age_verification' => false,
			)
		);

		foreach ( $this->get_checkboxes( array( 'locations' => $location ) ) as $checkbox_id => $checkbox ) {
			if ( $checkbox->is_enabled() ) {
				$checkbox_args = array(
					'is_shown' => $checkbox->is_shown(),
				);

				if ( 'download' === $checkbox_id && $args['is_downloadable'] ) {
					$checkbox_args['is_shown'] = true;
				}

				if ( 'service' === $checkbox_id && $args['is_service'] ) {
					$checkbox_args['is_shown'] = true;
				}

				if ( 'used_goods_warranty' === $checkbox_id && $args['has_used_goods'] ) {
					$checkbox_args['is_shown'] = true;
				}

				if ( 'age_verification' === $checkbox_id && $args['needs_age_verification'] ) {
					$checkbox_args['is_shown'] = true;

					if ( 'checkout' === $location ) {
						$checkbox_args['label_args'] = array( '{age}' => wc_gzd_cart_get_age_verification_min_age() );
					} elseif ( 'pay_for_order' === $location ) {
						$checkbox_args['label_args'] = array( '{age}' => wc_gzd_get_order_min_age( $args['order'] ) );
					}
				}

				if ( 'defective_copy' === $checkbox_id && $args['has_defective_copies'] ) {
					$checkbox_args['is_shown'] = true;

					if ( 'checkout' === $location ) {
						$checkbox_args['label_args'] = array( '{defect_descriptions}' => wc_gzd_print_item_defect_descriptions( wc_gzd_get_cart_defect_descriptions() ) );
					} elseif ( 'pay_for_order' === $location ) {
						$checkbox_args['label_args'] = array( '{defect_descriptions}' => wc_gzd_print_item_defect_descriptions( wc_gzd_get_order_defect_descriptions( $args['order'] ) ) );
					}
				}

				if ( 'privacy' === $checkbox_id && 'checkout' === $location ) {
					$create_account = $args['create_account'];

					/**
					 * This option will force creating a user within checkout.
					 */
					if ( 'no' === get_option( 'woocommerce_enable_guest_checkout' ) ) {
						$create_account = true;
					}

					if ( is_user_logged_in() || ( WC()->checkout() && ! WC()->checkout()->is_registration_enabled() ) || ! $create_account ) {
						$checkbox_args['is_shown'] = false;
					} else {
						$checkbox_args['is_shown'] = true;
					}
				}

				if ( 'parcel_delivery' === $checkbox_id && in_array( $location, array( 'checkout', 'pay_for_order' ), true ) ) {
					$enable_check = false;

					if ( 'checkout' === $location ) {
						if ( WC()->cart && WC()->cart->needs_shipping() ) {
							$enable_check = true;
							$rates        = wc_gzd_get_chosen_shipping_rates();
							$ids          = array();
							$titles       = array();

							foreach ( $rates as $rate ) {
								array_push( $ids, $rate->id );
								if ( method_exists( $rate, 'get_label' ) ) {
									array_push( $titles, $rate->get_label() );
								} else {
									array_push( $titles, $rate->label );
								}
							}
						}
					} elseif ( 'pay_for_order' === $location ) {
						if ( $args['order']->has_shipping_address() ) {
							$enable_check = true;
							$ids          = array();
							$items        = $args['order']->get_shipping_methods();
							$titles       = array();

							foreach ( $items as $item ) {
								$ids[]    = $item->get_method_id();
								$titles[] = $item->get_method_title();
							}
						}
					}

					if ( $enable_check ) {
						$is_enabled = wc_gzd_is_parcel_delivery_data_transfer_checkbox_enabled( $ids );

						if ( $is_enabled ) {
							$checkbox_args['is_shown']   = true;
							$checkbox_args['label_args'] = array( '{shipping_method_title}' => implode( ', ', $titles ) );
						}
					}
				}

				if ( 'photovoltaic_systems' === $checkbox_id && true === $args['is_photovoltaic_system'] && wc_gzd_customer_applies_for_photovoltaic_system_vat_exemption( $args ) ) {
					$checkbox_args['is_shown'] = true;
				}

				/**
				 * Do only apply global hide/show logic in case the checkbox is visible by default
				 */
				if ( $checkbox_args['is_shown'] && ( $checkbox->get_show_for_countries() || $checkbox->get_show_for_categories() ) ) {
					$show_for_country_is_valid    = $checkbox->get_show_for_countries() ? false : true;
					$show_for_categories_is_valid = $checkbox->get_show_for_categories() ? false : true;

					if ( $checkbox->get_show_for_countries() && $checkbox->show_for_country( $args['country'] ) ) {
						$show_for_country_is_valid = true;
					}

					if ( $category_ids = $checkbox->get_show_for_categories() ) {
						$intersected = array_intersect( $category_ids, $args['product_category_ids'] );

						if ( ! empty( $intersected ) ) {
							$show_for_categories_is_valid = true;
						}
					}

					if ( $show_for_country_is_valid && $show_for_categories_is_valid ) {
						$checkbox_args['is_shown'] = true;
					} else {
						$checkbox_args['is_shown'] = false;
					}
				}

				/**
				 * Filter to adjust conditional arguments passed to checkboxes based on certain locations.
				 *
				 * The dynamic portion of the hook name, `$location` refers to the checkbox location, e.g. checkout or pay_for_order.
				 *
				 * @param array $checkbox_args Arguments to be passed.
				 * @param WC_GZD_Legal_Checkbox $checkbox Checkbox object.
				 * @param string $checkbox_id The checkbox id.
				 * @param WC_GZD_Legal_Checkbox_Manager $instance The checkbox manager instance.
				 *
				 * @since 3.11.5
				 */
				$checkbox_args = apply_filters( "woocommerce_gzd_checkbox_show_conditionally_{$location}_args", $checkbox_args, $checkbox, $checkbox_id, $this );

				wc_gzd_update_legal_checkbox( $checkbox_id, $checkbox_args );
			}
		}
	}

	public function get_options( $force_refresh = false ) {
		if ( is_null( $this->options ) || ! is_array( $this->options ) || $force_refresh ) {
			wp_cache_delete( 'woocommerce_gzd_legal_checkboxes_settings', 'options' );
			$this->options = get_option( 'woocommerce_gzd_legal_checkboxes_settings', array() );
		}

		return (array) $this->options;
	}

	public function update_options( $options ) {
		$result        = update_option( 'woocommerce_gzd_legal_checkboxes_settings', $options, false );
		$this->options = $options;

		return $result;
	}

	public function do_register_action() {
		// Reload checkbox data
		$this->checkboxes = array();
		$this->register_core_checkboxes();

		/**
		 * Before legal checkbox registration.
		 *
		 * Register legal checkboxes and populate settings.
		 *
		 * @param WC_GZD_Legal_Checkbox_Manager $this The checkboxes manager instance.
		 *
		 * @since 2.0.0
		 */
		do_action( 'woocommerce_gzd_register_legal_checkboxes', $this );

		// Make sure we are not registering core checkboxes again
		foreach ( $this->get_options() as $id => $checkbox_args ) {
			if ( isset( $checkbox_args['id'] ) ) {
				unset( $checkbox_args['id'] );
			}

			if ( $checkbox = $this->get_checkbox( $id ) ) {
				$checkbox->update( $checkbox_args );
			} elseif ( ! in_array( (string) $id, $this->get_core_checkbox_ids(), true ) ) {
				$this->register( $id, $checkbox_args );
			}
		}

		/**
		 * After legal checkbox registration.
		 *
		 * Fires after the registration is completed. Might be used to alter settings and registered checkboxes.
		 *
		 * @param WC_GZD_Legal_Checkbox_Manager $this The checkboxes manager instance.
		 *
		 * @since 2.0.0
		 *
		 */
		do_action( 'woocommerce_gzd_registered_legal_checkboxes', $this );
	}

	public function validate_pay_for_order( $order ) {
		$this->maybe_do_hooks( 'pay_for_order' );

		foreach ( $this->get_checkboxes( array( 'locations' => 'pay_for_order' ) ) as $id => $checkbox ) {
			$value   = isset( $_POST[ $checkbox->get_html_name() ] ) ? wc_clean( wp_unslash( $_POST[ $checkbox->get_html_name() ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$visible = ! empty( $_POST[ $checkbox->get_html_name() . '-field' ] ) ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( $visible && ! $checkbox->validate( $value, 'pay_for_order' ) ) {
				wc_add_notice( $checkbox->get_error_message(), 'error' );
			}
		}
	}

	/**
	 * @param array $data
	 * @param WP_Error $errors
	 */
	public function validate_checkout( $data, $errors ) {
		if ( isset( $_POST['woocommerce_checkout_update_totals'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$this->maybe_do_hooks( 'checkout' );

		foreach ( $this->get_checkboxes( array( 'locations' => 'checkout' ) ) as $id => $checkbox ) {
			$value   = isset( $_POST[ $checkbox->get_html_name() ] ) ? wc_clean( wp_unslash( $_POST[ $checkbox->get_html_name() ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$visible = ! empty( $_POST[ $checkbox->get_html_name() . '-field' ] ) ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( $visible && ! $checkbox->validate( $value, 'checkout' ) ) {
				$errors->add( 'checkbox', $checkbox->get_error_message(), array( 'id' => $checkbox->get_html_id() ) );
			}
		}
	}

	public function validate_reviews( $approved, $comment_data ) {
		if ( 'product' !== get_post_type( $comment_data['comment_post_ID'] ) ) {
			return $approved;
		}

		$this->maybe_do_hooks( 'reviews' );

		foreach ( $this->get_checkboxes( array( 'locations' => 'reviews' ) ) as $id => $checkbox ) {
			$value   = isset( $_POST[ $checkbox->get_html_name() ] ) ? wc_clean( wp_unslash( $_POST[ $checkbox->get_html_name() ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$visible = ! empty( $_POST[ $checkbox->get_html_name() . '-field' ] ) ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( $visible && ! $checkbox->validate( $value, 'reviews' ) ) {
				return new WP_Error( $checkbox->get_html_name(), $checkbox->get_error_message(), 409 );
			}
		}

		return $approved;
	}

	public function validate_register( $validation_error ) {
		$this->maybe_do_hooks( 'register' );

		foreach ( $this->get_checkboxes( array( 'locations' => 'register' ) ) as $id => $checkbox ) {
			$value   = isset( $_POST[ $checkbox->get_html_name() ] ) ? wc_clean( wp_unslash( $_POST[ $checkbox->get_html_name() ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$visible = ! empty( $_POST[ $checkbox->get_html_name() . '-field' ] ) ? true : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			if ( $visible && ! $checkbox->validate( $value, 'register' ) ) {
				return new WP_Error( $checkbox->get_html_name(), $checkbox->get_error_message() );
			}
		}

		return $validation_error;
	}

	public function get_locations() {
		/**
		 * Filter to add/remove legal checkbox locations.
		 *
		 * @param array $locations Key => value array containing location id and title.
		 *
		 * @since 2.0.0
		 */
		return apply_filters(
			'woocommerce_gzd_legal_checkbox_locations',
			array(
				'checkout'      => __( 'Checkout', 'woocommerce-germanized' ),
				'register'      => __( 'Register form', 'woocommerce-germanized' ),
				'pay_for_order' => __( 'Pay for order', 'woocommerce-germanized' ),
				'reviews'       => __( 'Reviews', 'woocommerce-germanized' ),
			)
		);
	}

	public function update( $id, $args ) {

		if ( $this->get_checkbox( $id ) ) {
			$this->checkboxes[ $id ]->update( $args );

			return true;
		}

		return false;
	}

	public function delete( $id ) {
		if ( $checkbox = $this->get_checkbox( $id ) ) {
			unset( $this->checkboxes[ $id ] );

			return true;
		}

		return false;
	}

	public function register( $id, $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'html_name'            => '',
				'html_id'              => '',
				'is_mandatory'         => false,
				'locations'            => array(),
				'supporting_locations' => array(),
				'html_wrapper_classes' => array(),
				'html_classes'         => array(),
				'label_args'           => array(),
				'hide_input'           => false,
				'error_message'        => '',
				'admin_name'           => '',
				'show_for_categories'  => array(),
				'show_for_countries'   => array(),
				'refresh_fragments'    => true,
				'is_shown'             => true,
			)
		);

		$bools = array(
			'is_mandatory',
			'hide_input',
		);

		// Make sure we do understand yes and no as bools
		foreach ( $bools as $bool ) {
			$args[ $bool ] = wc_string_to_bool( $args[ $bool ] );
		}

		if ( empty( $args['html_name'] ) ) {
			$args['html_name'] = $id;
		}

		if ( empty( $args['html_id'] ) ) {
			$args['html_id'] = $args['html_name'];
		}

		if ( ! is_array( $args['locations'] ) ) {
			$args['locations'] = array( $args['locations'] );
		}

		if ( ! is_array( $args['show_for_categories'] ) ) {
			$args['show_for_categories'] = array_filter( array( $args['show_for_categories'] ) );
		}

		if ( ! is_array( $args['show_for_countries'] ) ) {
			$args['show_for_countries'] = array_filter( array( $args['show_for_countries'] ) );
		}

		$args['label_args'] = array_merge( $args['label_args'], $this->get_legal_label_args() );

		foreach ( $args['locations'] as $location ) {
			if ( ! in_array( $location, array_keys( $this->get_locations() ), true ) ) {
				return new WP_Error( 'checkbox_location_inexistent', sprintf( __( 'Checkbox location %s does not exist.', 'woocommerce-germanized' ), $location ) );
			}
		}

		if ( empty( $args['supporting_locations'] ) ) {
			$args['supporting_locations'] = array_keys( $this->get_locations() );
		}

		$args['html_wrapper_classes'] = array_merge(
			$args['html_wrapper_classes'],
			array(
				'form-row',
				'checkbox-' . $args['html_id'],
			)
		);
		$args['html_classes']         = array_merge(
			$args['html_classes'],
			array(
				'woocommerce-form__input',
				'woocommerce-form__input-checkbox',
				'input-checkbox',
			)
		);

		if ( $args['hide_input'] ) {
			$args['is_mandatory'] = false;
		}

		if ( $args['is_mandatory'] ) {
			$args['html_wrapper_classes'] = array_merge( $args['html_wrapper_classes'], array( 'validate-required' ) );

			if ( empty( $args['error_message'] ) ) {
				$args['error_message'] = sprintf( __( 'Please make sure to check %s checkbox.', 'woocommerce-germanized' ), esc_attr( $args['admin_name'] ) );
			}
		}

		if ( isset( $this->checkboxes[ $id ] ) ) {
			return new WP_Error( 'checkbox_exists', sprintf( __( 'Checkbox with name %s does already exist.', 'woocommerce-germanized' ), $id ) );
		}

		/**
		 * Filter legal checkbox arguments before registering.
		 *
		 * @param array $args Arguments passed to register checkbox.
		 * @param int $id Checkbox id.
		 *
		 * @since 2.0.0
		 */
		$args = apply_filters( 'woocommerce_gzd_register_legal_checkbox_args', $args, $id );

		/**
		 * Filter to adjust default checkbox classname. Defaults to `WC_GZD_Legal_Checkbox`.
		 *
		 * @param string $classname The name of the checkbox classname.
		 *
		 * @since 2.0.0
		 */
		$classname = apply_filters( 'woocommerce_gzd_legal_checkbox_classname', 'WC_GZD_Legal_Checkbox' );

		$this->checkboxes[ $id ] = new $classname( $id, $args );

		return true;
	}

	public function remove( $id ) {
		if ( isset( $this->checkboxes[ $id ] ) ) {
			unset( $this->checkboxes[ $id ] );
		}
	}

	/**
	 * @param $id
	 *
	 * @return false|WC_GZD_Legal_Checkbox
	 */
	public function get_checkbox( $id ) {
		if ( isset( $this->checkboxes[ $id ] ) ) {
			return $this->checkboxes[ $id ];
		}

		return false;
	}

	/**
	 * @param $args
	 * @param $context
	 *
	 * @return WC_GZD_Legal_Checkbox[]
	 */
	public function get_checkboxes( $args = array(), $context = '' ) {
		if ( ! did_action( 'woocommerce_gzd_register_legal_checkboxes' ) ) {
			$this->do_register_action();
		}

		$checkboxes = $this->filter( $args, 'AND' );

		if ( ! empty( $context ) && 'json' === $context ) {
			foreach ( $checkboxes as $id => $checkbox ) {
				$checkboxes[ $id ] = $checkbox->get_data();
			}
		}

		return $checkboxes;
	}

	protected function filter( $args = array(), $operator = 'AND' ) {
		$filtered = array();
		$count    = count( $args );

		foreach ( $this->checkboxes as $key => $obj ) {
			$matched = 0;

			foreach ( $args as $m_key => $m_value ) {
				$getter_bool = $m_key;
				$getter      = 'get_' . $m_key;
				$obj_value   = null;

				if ( is_callable( array( $obj, $getter_bool ) ) ) {
					$obj_value = $obj->$getter_bool();
				} elseif ( is_callable( array( $obj, $getter ) ) ) {
					$obj_value = $obj->$getter();
				} else {
					$obj_value = $obj->$m_key;
				}

				if ( ! is_null( $obj_value ) ) {
					if ( is_array( $obj_value ) && ! is_array( $m_value ) ) {
						if ( in_array( $m_value, $obj_value ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
							$matched ++;
						}
					} else {
						if ( $m_value == $obj_value ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
							$matched ++;
						}
					}
				}
			}

			if (
				( 'AND' === $operator && $matched === $count ) ||
				( 'OR' === $operator && $matched > 0 ) ||
				( 'NOT' === $operator && 0 === $matched )
			) {
				$filtered[ $key ] = $obj;
			}
		}

		return $filtered;
	}

	public function render( $location = 'checkout' ) {
		$this->maybe_do_hooks( $location );

		$checkboxes = $this->get_checkboxes( array( 'locations' => $location ) );

		if ( ! empty( $checkboxes ) ) {
			$checkboxes = $this->sort( $checkboxes );

			foreach ( $checkboxes as $id => $checkbox ) {
				$checkbox->render();
			}
		}
	}

	protected function sort( $checkboxes = array() ) {
		uasort(
			$checkboxes,
			function( $checkbox1, $checkbox2 ) {
				if ( $checkbox1->get_priority() === $checkbox2->get_priority() ) {
					return 0;
				}

				return ( $checkbox1->get_priority() < $checkbox2->get_priority() ) ? - 1 : 1;
			}
		);

		return $checkboxes;
	}

	private function maybe_do_hooks( $location = 'checkout' ) {
		if ( ! did_action( 'woocommerce_gzd_run_legal_checkboxes' ) ) {

			/**
			 * Before render checkboxes.
			 *
			 * This hook is used to alter checkboxes before rendering and to
			 * dynamically choose whether to display or hide them.
			 *
			 * @param WC_GZD_Legal_Checkbox_Manager $this The checkboxes manager instance.
			 *
			 * @since 2.0.0
			 *
			 */
			do_action( 'woocommerce_gzd_run_legal_checkboxes', $this );
		}

		if ( ! did_action( 'woocommerce_gzd_run_legal_checkboxes_' . $location ) ) {

			/**
			 * Before render checkboxes location.
			 *
			 * This hook is used to alter checkboxes before rendering a specific location `$location`
			 * e.g. checkout and to dynamically choose whether to display or hide them.
			 *
			 * @param WC_GZD_Legal_Checkbox_Manager $this The checkboxes manager instance.
			 *
			 * @since 2.0.0
			 *
			 * @see WC_GZD_Legal_Checkbox_Manager::get_locations()
			 *
			 * ```php
			 * function ex_filter_checkboxes_checkout( $manager ) {
			 *      if ( $manager = $this->get_checkbox( 'download' ) ) {
			 *          wc_gzd_update_legal_checkbox( 'download', array(
			 *               'is_shown' => true,
			 *          ) );
			 *      }
			 * }
			 * add_action( 'woocommerce_gzd_run_legal_checkboxes_checkout', 'ex_filter_checkboxes_checkout', 10, 1 );
			 * ```
			 *
			 */
			do_action( 'woocommerce_gzd_run_legal_checkboxes_' . $location, $this );
		}
	}
}

WC_GZD_Legal_Checkbox_Manager::instance();
