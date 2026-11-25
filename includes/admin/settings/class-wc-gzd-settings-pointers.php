<?php
/**
 * Adds and controls pointers for contextual help/tutorials
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Pointers Class.
 */
class WC_GZD_Settings_Pointers {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'setup_pointers_for_screen' ) );
	}

	protected function get_settings() {
		$pages = WC_Admin_Settings::get_settings_pages();

		foreach ( $pages as $page ) {
			if ( is_a( $page, 'WC_GZD_Settings_Germanized' ) ) {
				return $page;
			}
		}

		return false;
	}

	/**
	 * Setup pointers for screen.
	 */
	public function setup_pointers_for_screen() {
		if ( ! $screen = get_current_screen() ) {
			return;
		}

		if ( 'woocommerce_page_wc-settings' === $screen->id && isset( $_GET['tab'] ) && strpos( wc_clean( wp_unslash( $_GET['tab'] ) ), 'germanized' ) !== false && isset( $_GET['tutorial'] ) && current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab       = wc_clean( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$tab_clean = 'germanized' === $tab ? '' : str_replace( 'germanized-', '', $tab );
			$pointers  = array();

			if ( '' === $tab_clean ) {
				$step     = isset( $_GET['tutorial_step'] ) ? wc_clean( wp_unslash( $_GET['tutorial_step'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$pointers = self::get_pointers_for_page( '', '', $step );
			} elseif ( ( $settings = $this->get_settings() ) && ( $tab_instance = $settings->get_tab_by_name( $tab_clean ) ) ) {
				$pointers = $tab_instance->get_pointers();
			}

			if ( ! empty( $pointers ) ) {
				$this->enqueue_pointers( $pointers );
			}
		}
	}

	/**
	 * Build the tutorial URL for a certain Germanized settings tab (and section).
	 *
	 * @param string $tab Tab name without the germanized- prefix, e.g. shopmarks. Empty for the overview.
	 * @param string $section
	 * @param array  $args Additional query args.
	 *
	 * @return string
	 */
	public static function get_tutorial_url( $tab = '', $section = '', $args = array() ) {
		$query_args = array(
			'page'     => 'wc-settings',
			'tab'      => empty( $tab ) ? 'germanized' : 'germanized-' . $tab,
			'tutorial' => 'yes',
		);

		if ( ! empty( $section ) ) {
			$query_args['section'] = $section;
		}

		return add_query_arg( array_merge( $query_args, $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * The URL the tutorial returns to after the last step.
	 *
	 * @return string
	 */
	public static function get_tutorial_end_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=germanized' );
	}

	/**
	 * Whether a certain settings tab is registered, e.g. to skip
	 * tutorial pages for tabs which are not available.
	 *
	 * @param string $tab_name
	 *
	 * @return bool
	 */
	protected static function has_tab( $tab_name ) {
		if ( '' === $tab_name ) {
			return true;
		}

		foreach ( WC_Admin_Settings::get_settings_pages() as $page ) {
			if ( is_a( $page, 'WC_GZD_Settings_Germanized' ) ) {
				return $page->get_tab_by_name( $tab_name ) ? true : false;
			}
		}

		return false;
	}

	/**
	 * The tutorial tour. Each entry represents a (settings) page containing a chain of steps.
	 * Pages are visited in the order defined. In case Germanized Pro is not active, pro features
	 * are presented as a preview. Germanized Pro registers its actual steps via the
	 * woocommerce_gzd_settings_tutorial_tour filter.
	 *
	 * @return array
	 */
	public static function get_tour() {
		$is_pro     = WC_germanized()->is_pro();
		$pro_target = '.wc-gzd-pro-preview-card h3';
		$tour       = array();

		$tour['overview'] = array(
			'tab'   => '',
			'steps' => array(
				'tab'     => array(
					'target'  => '#wc-gzd-setting-tab-name-general .wc-gzd-setting-tab-link',
					'title'   => __( 'Setting tabs', 'woocommerce-germanized' ),
					'content' => __( 'Germanized settings are grouped into tabs. Click a tab to manage the options of a certain feature. This tutorial guides you through the most important settings.', 'woocommerce-germanized' ),
					'edge'    => 'top',
				),
				'enabled' => array(
					'target'  => '#wc-gzd-setting-tab-enabled-double_opt_in .woocommerce-input-toggle',
					'title'   => __( 'Status', 'woocommerce-germanized' ),
					'content' => __( 'Some features can be switched on or off explicitly by toggling the input.', 'woocommerce-germanized' ),
					'edge'    => 'bottom',
				),
			),
		);

		$tour['general'] = array(
			'tab'   => 'general',
			'steps' => array(
				'breadcrumb'  => array(
					'target'  => '.breadcrumb-item-main a',
					'title'   => __( 'Overview', 'woocommerce-germanized' ),
					'content' => __( 'You can always return to the settings overview by navigating through the breadcrumb navigation.', 'woocommerce-germanized' ),
					'edge'    => 'top',
				),
				'sections'    => array(
					'target'  => 'ul.subsubsub a[href*="section=disputes"]',
					'title'   => __( 'Sections', 'woocommerce-germanized' ),
					'content' => __( 'Each setting tab might have sub sections containing more specific options.', 'woocommerce-germanized' ),
					'edge'    => 'top',
				),
				'legal_pages' => array(
					'target'  => '#select2-woocommerce_terms_page_id-container',
					'title'   => __( 'Legal Pages', 'woocommerce-germanized' ),
					'content' => __( 'Adjust legal pages e.g. terms and conditions. These pages are used to add links within checkboxes and text attachments to emails.', 'woocommerce-germanized' ),
				),
			),
		);

		if ( ! $is_pro ) {
			$tour['terms_generator'] = array(
				'tab'   => 'terms_generator',
				'steps' => array(
					'generator' => array(
						'target'  => $pro_target,
						'title'   => __( 'Legal text generator', 'woocommerce-germanized' ),
						'content' => __( 'Generate terms and conditions and revocation terms tailored to your shop by answering a few questions - and insert them into your legal pages with a single click. Available with Germanized Pro.', 'woocommerce-germanized' ),
						'pro'     => true,
					),
				),
			);
		}

		if ( class_exists( '\Vendidero\OrderWithdrawalButton\Settings' ) ) {
			$tour['withdrawal_button'] = array(
				'tab'     => 'general',
				'section' => 'withdrawal_button',
				'steps'   => array(
					'page' => array(
						'target'  => '#select2-woocommerce_withdraw_from_contract_page_id-container',
						'title'   => __( 'Withdrawal Button', 'woocommerce-germanized' ),
						'content' => __( 'Starting June 19, 2026 consumers must be able to withdraw from a contract by using a withdrawal button. Choose the page containing the withdrawal form, publish it and link it e.g. within your footer.', 'woocommerce-germanized' ),
					),
					'days' => array(
						'target'  => '#eu_owb_woocommerce_number_of_days_to_withdraw',
						'title'   => __( 'Withdrawal period', 'woocommerce-germanized' ),
						'content' => __( 'Adjust the withdrawal period. Withdrawals received are listed within the Withdrawals screen and confirmed to your customers by email.', 'woocommerce-germanized' ),
					),
				),
			);
		}

		$tour['shopmarks'] = array(
			'tab'   => 'shopmarks',
			'steps' => array(
				'display' => array(
					'target'  => 'ul.subsubsub a[href$="section=single_product"]',
					'title'   => __( 'Shopmark Display', 'woocommerce-germanized' ),
					'content' => __( 'By adjusting the display settings you might determine where to show or hide your shopmarks e.g. the tax notice on single product pages.', 'woocommerce-germanized' ),
					'edge'    => 'top',
				),
			),
		);

		$tour['shopmarks_single_product'] = array(
			'tab'     => 'shopmarks',
			'section' => 'single_product',
			'steps'   => array(
				'location' => array(
					'target'  => '#select2-woocommerce_gzd_display_single_product_unit_price_filter-container',
					'title'   => __( 'Location', 'woocommerce-germanized' ),
					'content' => __( 'Adjust the location of the shopmark by selecting a location from the list. Some Themes might apply the locations at different positions that\'s why the result may differ from Theme to Theme.', 'woocommerce-germanized' ),
				),
				'priority' => array(
					'target'  => '#woocommerce_gzd_display_single_product_unit_price_priority',
					'title'   => __( 'Priority', 'woocommerce-germanized' ),
					'content' => __( 'Many different information may be attached to the location selected. By adjusting the priority you can choose whether the shopmark gets applied earlier (lower) or later (higher).', 'woocommerce-germanized' ),
				),
			),
		);

		$tour['guarantee_labels'] = array(
			'tab'     => 'shopmarks',
			'section' => 'guarantee_labels',
			'steps'   => array(
				'legal_guarantee' => array(
					'target'  => '#woocommerce_gzd_legal_guarantee_enabled-toggle',
					'title'   => __( 'EU legal guarantee label', 'woocommerce-germanized' ),
					'content' => __( 'Starting September 27, 2026 shops selling physical goods to consumers must inform about the legal guarantee by using the harmonised EU label. Choose where to display the label, e.g. within your footer and checkout.', 'woocommerce-germanized' ),
				),
				'garan'           => array(
					'target'  => '#woocommerce_gzd_garan_label_enabled-toggle',
					'title'   => __( 'GARAN label', 'woocommerce-germanized' ),
					'content' => __( 'The GARAN label is shown on a per-product basis for products offering a guarantee of more than 24 months. Make sure these products have a manufacturer linked and a valid model id (GTIN, MPN or SKU).', 'woocommerce-germanized' ),
				),
			),
		);

		$tour['delivery_times'] = array(
			'tab'     => 'shopmarks',
			'section' => 'delivery_times',
			'steps'   => array(
				'default' => array(
					'target'  => '#select2-woocommerce_gzd_default_delivery_time-container',
					'title'   => __( 'Default Delivery Time', 'woocommerce-germanized' ),
					'content' => __( 'Choose a delivery time that serves as fallback in case no delivery time was added to the product.', 'woocommerce-germanized' ),
				),
				'format'  => array(
					'target'  => '#woocommerce_gzd_delivery_time_text',
					'title'   => __( 'Format', 'woocommerce-germanized' ),
					'content' => __( 'You may want to adjust the delivery time output format. You might use {delivery_time} to output the current product\'s delivery time.', 'woocommerce-germanized' ),
				),
			),
		);

		$tour['price_labels'] = array(
			'tab'     => 'shopmarks',
			'section' => 'price_labels',
			'steps'   => array(
				'default' => array(
					'target'  => '#select2-woocommerce_gzd_default_sale_price_label-container',
					'title'   => __( 'Default Price Label', 'woocommerce-germanized' ),
					'content' => __( 'Price labels are added to sale products to inform the customers of the different prices\' meaning. You may add a fallback label in case a product does not contain a label.', 'woocommerce-germanized' ),
				),
			),
		);

		$tour['checkboxes'] = array(
			'tab'   => 'checkboxes',
			'steps' => array(
				'default' => array(
					'target'  => '.wc-gzd-legal-checkbox-rows tr:first td.wc-gzd-legal-checkbox-name a:first',
					'title'   => __( 'Edit checkbox', 'woocommerce-germanized' ),
					'content' => __( 'Legal checkboxes help you obtain consent from your customers. You might edit a checkbox\' label and other options by clicking on the link.', 'woocommerce-germanized' ),
					'edge'    => 'top',
				),
			),
		);

		$tour['checkbox_terms'] = array(
			'tab'     => 'checkboxes',
			'section' => 'terms',
			'args'    => array( 'checkbox_id' => 'terms' ),
			'steps'   => array(
				'default' => array(
					'target'  => '#woocommerce_gzd_checkboxes_terms_label',
					'title'   => __( 'Label', 'woocommerce-germanized' ),
					'content' => __( 'Adjust the label of your checkbox which will be shown within your shop (e.g. checkout). Use placeholders to add links to your legal pages.', 'woocommerce-germanized' ),
					'edge'    => 'bottom',
				),
			),
		);

		$tour['button_solution'] = array(
			'tab'   => 'button_solution',
			'steps' => array(
				'default' => array(
					'target'  => '#woocommerce_gzd_order_submit_btn_text',
					'title'   => __( 'Buy now button', 'woocommerce-germanized' ),
					'content' => __( 'These settings help you comply to the button solution. The buy now button text is forced and static so that no payment gateway might override it.', 'woocommerce-germanized' ),
				),
				'color'   => array(
					'target'  => '#woocommerce_gzd_display_checkout_table_color',
					'title'   => __( 'Product table background', 'woocommerce-germanized' ),
					'content' => __( 'The product table within your checkout should be noticeable for your customers. You might want to choose a different background color for it.', 'woocommerce-germanized' ),
				),
			),
		);

		if ( ! $is_pro ) {
			$tour['multistep_checkout'] = array(
				'tab'   => 'multistep_checkout',
				'steps' => array(
					'multistep' => array(
						'target'  => $pro_target,
						'title'   => __( 'Multistep checkout', 'woocommerce-germanized' ),
						'content' => __( 'Split your checkout into clear steps, e.g. address, payment and order confirmation - including a summary of the customer\'s input. Optimized for mobile devices and the block-based checkout. Available with Germanized Pro.', 'woocommerce-germanized' ),
						'pro'     => true,
					),
				),
			);

			$tour['b2b'] = array(
				'tab'   => 'b2b',
				'steps' => array(
					'b2b' => array(
						'target'  => $pro_target,
						'title'   => __( 'B2B', 'woocommerce-germanized' ),
						'content' => __( 'Sell to business customers: customer groups with individual and tiered prices, a separate B2B registration with manual review, payment and shipping restrictions per group and more. Available with Germanized Pro.', 'woocommerce-germanized' ),
						'pro'     => true,
					),
				),
			);
		}

		$tour['taxes'] = array(
			'tab'   => 'taxes',
			'steps' => array(),
		);

		if ( ! $is_pro ) {
			$tour['taxes']['steps']['vat_id'] = array(
				'target'  => $pro_target,
				'title'   => __( 'VAT ID check', 'woocommerce-germanized' ),
				'content' => __( 'Validate the VAT IDs of your business customers during checkout and sell tax-exempt within the EU (reverse charge). Available with Germanized Pro.', 'woocommerce-germanized' ),
				'pro'     => true,
			);
		}

		$tour['taxes']['steps']['additional_costs'] = array(
			'target'  => 'ul.subsubsub a[href$="section=additional_costs"]',
			'title'   => __( 'Additional Costs', 'woocommerce-germanized' ),
			'content' => __( 'Choose how taxes for shipping costs and fees are calculated, e.g. split based on the tax rates of the products within the cart.', 'woocommerce-germanized' ),
			'edge'    => 'top',
		);

		if ( ! $is_pro ) {
			$tour['invoices'] = array(
				'tab'   => 'invoices',
				'steps' => array(
					'invoices' => array(
						'target'  => $pro_target,
						'title'   => __( 'Accounting', 'woocommerce-germanized' ),
						'content' => __( 'Create invoices, cancellations and e-invoices (ZUGFeRD, XRechnung) automatically and transfer them to lexware office, sevdesk or DATEV. Available with Germanized Pro.', 'woocommerce-germanized' ),
						'pro'     => true,
					),
				),
			);
		}

		$tour['emails'] = array(
			'tab'   => 'emails',
			'steps' => array(
				'default' => array(
					'target'  => '#woocommerce_gzd_mail_attach_terms ~ .select2-container',
					'title'   => __( 'Email attachments', 'woocommerce-germanized' ),
					'content' => __( 'Choose which of your email templates (e.g. order confirmation) should contain your legal page content e.g. terms and conditions within it\'s footer.', 'woocommerce-germanized' ),
				),
			),
		);

		if ( ! $is_pro ) {
			$tour['emails']['steps']['pdf'] = array(
				'target'  => 'ul.subsubsub a[href$="section=attachments"]',
				'title'   => __( 'PDF Attachments', 'woocommerce-germanized' ),
				'content' => __( 'Customers of our pro version may attach PDF files instead of plain text content to emails.', 'woocommerce-germanized' ),
				'edge'    => 'top',
				'pro'     => true,
			);
		}

		$tour['double_opt_in'] = array(
			'tab'   => 'double_opt_in',
			'steps' => array(
				'activation' => array(
					'target'  => '#woocommerce_gzd_customer_activation-toggle',
					'title'   => __( 'Double Opt In', 'woocommerce-germanized' ),
					'content' => __( 'Make sure that new customer accounts are confirmed via email before customers are able to log in. Unconfirmed accounts may be deleted automatically after a certain time.', 'woocommerce-germanized' ),
				),
			),
		);

		$tour['finish'] = array(
			'tab'   => '',
			'step'  => 'finish',
			'args'  => array( 'tutorial_step' => 'finish' ),
			'steps' => array(),
		);

		if ( \Vendidero\Germanized\PluginsHelper::is_shiptastic_plugin_active() ) {
			$tour['finish']['steps']['shiptastic'] = array(
				'target'  => '#wc-gzd-setting-tab-name-shiptastic',
				'title'   => __( 'Shiptastic', 'woocommerce-germanized' ),
				'content' => __( 'Manage shipments, returns, packaging and shipping service providers within the Shiptastic settings.', 'woocommerce-germanized' ),
			);
		} else {
			$tour['finish']['steps']['shiptastic'] = array(
				'target'  => '#wc-gzd-setting-tab-name-shiptastic',
				'title'   => __( 'Shipments & returns with Shiptastic', 'woocommerce-germanized' ),
				'content' => __( 'Create shipments and returns for your orders, manage packaging and create labels via integrations, e.g. for DHL, DPD, GLS, Hermes or UPS. Shiptastic is free and can be installed with a single click.', 'woocommerce-germanized' ),
			);
		}

		if ( ! $is_pro ) {
			$tour['finish']['steps']['pro'] = array(
				'target'  => '.wc-gzd-setting-header .page-title-action',
				'title'   => __( 'Germanized Pro', 'woocommerce-germanized' ),
				'content' => __( 'Upgrade to Germanized Pro to unlock accounting incl. e-invoices & DATEV, B2B, the multistep checkout, legal text generators, the VAT ID check and professional support.', 'woocommerce-germanized' ),
				'edge'    => 'top',
				'pro'     => true,
			);
		}

		$tour['finish']['steps']['done'] = array(
			'target'  => '.wc-gzd-setting-header',
			'title'   => __( 'All set', 'woocommerce-germanized' ),
			'content' => __( 'You now know the most important settings. Use the settings overview to explore further features, e.g. taxes, the One Stop Shop or Shiptastic for shipping.', 'woocommerce-germanized' ),
			'edge'    => 'top',
		);

		/**
		 * Filter the Germanized settings tutorial tour, e.g. to add pages or steps.
		 *
		 * The tour is an ordered array of page_id => page. Each page consists of:
		 * - tab: The Germanized settings tab name (empty for the settings overview)
		 * - section: The tab section (optional)
		 * - args: Additional query args used to build the page URL (optional)
		 * - step: Identifier to differentiate multiple tour pages on the same screen (optional)
		 * - steps: Ordered array of step_id => array( target, title, content, edge, align, pro )
		 *
		 * Core page ids: overview, general, withdrawal_button, shopmarks, shopmarks_single_product,
		 * guarantee_labels, delivery_times, price_labels, checkboxes, checkbox_terms, button_solution,
		 * taxes, emails, double_opt_in, finish.
		 *
		 * @param array $tour
		 *
		 * @since 4.2.0
		 */
		$tour = apply_filters( 'woocommerce_gzd_settings_tutorial_tour', $tour );

		// Remove pages for tabs which are not available or do not contain steps.
		$tour = array_filter(
			$tour,
			function ( $page ) {
				return ! empty( $page['steps'] ) && self::has_tab( isset( $page['tab'] ) ? $page['tab'] : '' );
			}
		);

		return array_values( $tour );
	}

	/**
	 * Get the pointers for a certain tutorial page.
	 *
	 * @param string $tab_name
	 * @param string $section
	 * @param string $step Used to differentiate between multiple tour pages on the same screen.
	 *
	 * @return array
	 */
	public static function get_pointers_for_page( $tab_name, $section = '', $step = '' ) {
		$tour = self::get_tour();

		foreach ( $tour as $index => $page ) {
			$page = wp_parse_args(
				$page,
				array(
					'tab'     => '',
					'section' => '',
					'step'    => '',
					'args'    => array(),
					'steps'   => array(),
				)
			);

			if ( $page['tab'] !== $tab_name || (string) $page['section'] !== (string) $section || $page['step'] !== $step ) {
				continue;
			}

			$is_last  = ! isset( $tour[ $index + 1 ] );
			$next_url = self::get_tutorial_end_url();

			if ( ! $is_last ) {
				$next_page = wp_parse_args(
					$tour[ $index + 1 ],
					array(
						'section' => '',
						'args'    => array(),
					)
				);

				// The section is passed as custom arg (e.g. checkbox_id) in some cases.
				$next_url = self::get_tutorial_url( $next_page['tab'], empty( $next_page['args'] ) ? $next_page['section'] : '', $next_page['args'] );
			}

			return self::build( $page['steps'], $next_url, $is_last );
		}

		return array();
	}

	/**
	 * Build a chain of pointers. Steps are shown in the order passed. After the last step
	 * the tutorial continues with $next_url.
	 *
	 * @param array  $steps Array of step_id => array( 'target', 'title', 'content', 'edge', 'align', 'pro' ).
	 * @param string $next_url URL of the next tutorial page.
	 * @param bool   $is_last Whether this chain finishes the tutorial.
	 *
	 * @return array
	 */
	public static function build( $steps, $next_url = '', $is_last = false ) {
		$pointers = array();
		$step_ids = array_keys( $steps );

		foreach ( $step_ids as $index => $step_id ) {
			$step = wp_parse_args(
				$steps[ $step_id ],
				array(
					'target'  => '',
					'title'   => '',
					'content' => '',
					'edge'    => 'left',
					'align'   => 'left',
					'pro'     => false,
				)
			);

			$is_final_step = ( count( $step_ids ) - 1 ) === $index;
			$pointer       = array(
				'target'       => $step['target'],
				'next'         => $is_final_step ? '' : $step_ids[ $index + 1 ],
				'next_url'     => $is_final_step ? $next_url : '',
				'next_trigger' => array(),
				'options'      => array(
					'content'  => '<h3>' . esc_html( $step['title'] ) . '</h3><p>' . wp_kses_post( $step['content'] ) . '</p>',
					'position' => array(
						'edge'  => $step['edge'],
						'align' => $step['align'],
					),
				),
			);

			if ( $step['pro'] ) {
				$pointer['pro'] = true;
			}

			if ( $is_final_step && $is_last ) {
				$pointer['last_step'] = true;
			}

			$pointers[ $step_id ] = $pointer;
		}

		/**
		 * Filter tutorial pointers for the current Germanized settings screen.
		 *
		 * @param array  $pointers
		 * @param string $next_url
		 *
		 * @since 4.2.0
		 */
		return array( 'pointers' => apply_filters( 'woocommerce_gzd_settings_tutorial_pointers', $pointers, $next_url ) );
	}

	/**
	 * Enqueue pointers and add script to page.
	 *
	 * @param array $pointers
	 */
	public function enqueue_pointers( $pointers ) {
		$pointers = rawurlencode( wp_json_encode( $pointers ) );
		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
		wp_add_inline_style(
			'wp-pointer',
			'.wc-pointer .wp-pointer-content h3 { position: relative; padding-right: 120px; }
			.wc-pointer .wp-pointer-content h3 .wc-pointer-dismiss { position: absolute; top: 50%; right: 16px; transform: translateY(-50%); display: inline-flex; align-items: center; gap: 2px; color: #fff; font-size: 13px; font-weight: 400; line-height: 1; text-decoration: none; opacity: .85; }
			.wc-pointer .wp-pointer-content h3 .wc-pointer-dismiss::before { content: "\\f153"; font: normal 16px/1 dashicons; }
			.wc-pointer .wp-pointer-content h3 .wc-pointer-dismiss:hover, .wc-pointer .wp-pointer-content h3 .wc-pointer-dismiss:focus { color: #fff; opacity: 1; }'
		);
		wp_add_inline_script(
			'wp-pointer',
			"jQuery( function( $ ) {
				var wc_pointers = JSON.parse( decodeURIComponent( '{$pointers}' ) );

				setTimeout( init_wc_pointers, 800 );

				function init_wc_pointers() {
					$.each( wc_pointers.pointers, function( i ) {
						show_wc_pointer( i );
						return false;
					});
				}

				function show_wc_pointer( id ) {
					var pointer = wc_pointers.pointers[ id ];

					if ( ! pointer ) {
						return;
					}

					/**
					 * Skip steps whose target is not available (e.g. a disabled feature)
					 * instead of breaking the tutorial chain.
					 */
					if ( ! $( pointer.target ).filter( ':visible' ).length ) {
						if ( pointer.next ) {
							show_wc_pointer( pointer.next );
						} else if ( pointer.next_url ) {
							window.location.href = pointer.next_url;
						}

						return;
					}
					var options = $.extend( pointer.options, {
						pointerClass: 'wp-pointer wc-pointer',
						next_url: '',
						close: function() {
							if ( pointer.hasOwnProperty( 'next_url' ) && pointer.next_url.length ) {
								window.location.href = pointer.next_url;
							} else if ( pointer.next ) {
								show_wc_pointer( pointer.next );
							}
						},
						open: function( e, t ) {
							var heading = t.pointer.find( '.wp-pointer-content h3' ).first();

							if ( heading.length && ! heading.find( '.wc-pointer-dismiss' ).length ) {
								var dismiss = $( '<a class=\"wc-pointer-dismiss\" href=\"#\">" . esc_js( __( 'Dismiss', 'woocommerce-germanized' ) ) . "</a>' );

								dismiss.on( 'click.pointer', function( ev ) {
									ev.preventDefault();
									t.element.pointer( 'destroy' );
								});

								heading.append( dismiss );
							}

							t.pointer.get(0).scrollIntoView( { behavior: 'smooth' } );
						},
						buttons: function( event, t ) {
							var next    = '" . esc_js( __( 'Next', 'woocommerce-germanized' ) ) . "',
								button2 = $( '<a class=\"button button-primary\" href=\"#\">' + next + '</a>' ),
								wrapper = $( '<div class=\"wc-pointer-buttons\" />' ),
								nextUrl = '';
								
							if ( pointer.hasOwnProperty( 'last_step' ) && pointer.last_step ) {
								next    = '" . esc_js( __( 'Let\'s go', 'woocommerce-germanized' ) ) . "';
							}
							
							if ( pointer.hasOwnProperty( 'next_url' ) && pointer.next_url.length ) {
								nextUrl = pointer.next_url;
								button2 = $( '<a class=\"button button-primary\" href=\"' + pointer.next_url + '\">' + next + '</a>' );
							} 
							
							button2.bind( 'click.pointer', function(e) {
								e.preventDefault();
								t.element.pointer('close');
							});
							
							if ( pointer.next.length || nextUrl.length ) {
								wrapper.append( button2 );
							}	
							
							if ( pointer.hasOwnProperty( 'pro' ) ) {
								var button_pro = $( '<a class=\"button button-secondary is-gzd-pro button-wc-gzd-pro\" target=\"_blank\" style=\"margin-right: 1em;\" href=\"https://vendidero.de/woocommerce-germanized\">" . esc_js( __( 'Upgrade now', 'woocommerce-germanized' ) ) . "</a>' );
								wrapper.append( button_pro );
							}			

							return wrapper;
						},
					} );
					var this_pointer = $( pointer.target ).pointer( options );
					this_pointer.pointer( 'open' );

					if ( pointer.next_trigger ) {
						$( pointer.next_trigger.target ).on( pointer.next_trigger.event, function() {
							setTimeout( function() { this_pointer.pointer( 'close' ); }, 400 );
						});
					}
				}
			});"
		);
	}
}

new WC_GZD_Settings_Pointers();
