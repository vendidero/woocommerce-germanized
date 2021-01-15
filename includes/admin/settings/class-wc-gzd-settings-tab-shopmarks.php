<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Vendidero\Germanized\Shopmarks;
use Vendidero\Germanized\Shopmark;

/**
 * Adds Germanized Shopmark settings.
 *
 * @class        WC_GZD_Settings_Tab_Shopmarks
 * @version        3.0.0
 * @author        Vendidero
 */
class WC_GZD_Settings_Tab_Shopmarks extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Adjust shopmark related settings. Choose which and where they shall be attached to your product data.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Shopmarks', 'woocommerce-germanized' );
	}

	public function get_help_link() {
		return 'https://vendidero.de/dokumentation/woocommerce-germanized/preisauszeichnung';
	}

	public function get_name() {
		return 'shopmarks';
	}

	public function get_sections() {
		$sections = array(
			''               => __( 'General', 'woocommerce-germanized' ),
			'delivery_times' => __( 'Delivery times', 'woocommerce-germanized' ),
			'unit_prices'    => __( 'Unit prices', 'woocommerce-germanized' ),
			'price_labels'   => __( 'Price labels', 'woocommerce-germanized' ),
		);

		foreach ( Shopmarks::get_locations() as $location => $title ) {
			$sections[ $location ] = $title;
		}

		$sections = array_merge( $sections, array(
			'product_widgets' => __( 'Widgets & Blocks', 'woocommerce-germanized' ),
			'emails'          => __( 'E-Mails', 'woocommerce-germanized' ),
		) );

		return $sections;
	}

	public function get_pointers() {
		$current  = $this->get_current_section();
		$pointers = array();

		if ( '' === $current ) {
			$pointers = array(
				'pointers' => array(
					'display' => array(
						'target'       => 'ul.subsubsub li:nth-of-type(5) a',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-shopmarks&section=single_product&tutorial=yes' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Shopmark Display', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html__( 'By adjusting the display settings you might determine where to show or hide your shopmarks e.g. the tax notice on single product pages.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'top',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'single_product' === $current ) {
			$pointers = array(
				'pointers' => array(
					'location' => array(
						'target'       => '#select2-woocommerce_gzd_display_single_product_unit_price_filter-container',
						'next'         => 'priority',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Location', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html__( 'Adjust the location of the shopmark by selecting a location from the list. Some Themes might apply the locations at different positions that\'s why the result may differ from Theme to Theme.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'priority' => array(
						'target'       => '#woocommerce_gzd_display_single_product_unit_price_priority',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-shopmarks&section=delivery_times&tutorial=yes' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Priority', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html__( 'Many different information may be attached to the location selected. By adjusting the priority you can choose whether the shopmark gets applied earlier (lower) or later (higher).', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'delivery_times' === $current ) {
			$pointers = array(
				'pointers' => array(
					'default' => array(
						'target'       => '#select2-woocommerce_gzd_default_delivery_time-container',
						'next'         => 'format',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Default Delivery Time', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html__( 'Choose a delivery time that serves as fallback in case no delivery time was added to the product.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'format'  => array(
						'target'       => '#woocommerce_gzd_delivery_time_text',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-shopmarks&section=price_labels&tutorial=yes' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Format', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html__( 'You may want to adjust the delivery time output format. You might use {delivery_time} to output the current product\'s delivery time.', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'price_labels' === $current ) {
			$pointers = array(
				'pointers' => array(
					'default' => array(
						'target'       => '#select2-woocommerce_gzd_default_sale_price_label-container',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-button_solution&tutorial=yes' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Default Sale Label', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html__( 'Price labels are added to sale products to inform the customers of the different prices\' meaning. You may add a fallback label in case a product does not contain a label.', 'woocommerce-germanized' ) . '</p>',
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
		$settings         = array();
		$display_sections = Shopmarks::get_locations();

		if ( '' === $current_section ) {
			$settings = $this->get_general_settings();
		} elseif ( 'delivery_times' === $current_section ) {
			$settings = $this->get_delivery_time_settings();
		} elseif ( 'unit_prices' === $current_section ) {
			$settings = $this->get_unit_price_settings();
		} elseif ( 'price_labels' === $current_section ) {
			$settings = $this->get_price_label_settings();
		} elseif ( 'product_widgets' === $current_section ) {
			$settings = $this->get_product_widget_settings();
		} elseif ( 'emails' === $current_section ) {
			$settings = $this->get_email_settings();
		} elseif ( array_key_exists( $current_section, $display_sections ) ) {
			$settings = $this->get_display_settings( $current_section );
		}

		return $settings;
	}

	public function get_section_description( $section ) {
		$display_sections = Shopmarks::get_locations();

		if ( 'product_widgets' === $section ) {
			return __( 'Adjust Product Widgets & Blocks visibility options.', 'woocommerce-germanized' );
		} elseif ( array_key_exists( $section, $display_sections ) ) {
			$title = Shopmarks::get_location_title( $section );

			return sprintf( __( 'Adjust %s visibility options and choose which shopmarks to be displayed at which locations.', 'woocommerce-germanized' ), $title );
		}

		return '';
	}

	protected function get_product_widget_settings() {
		return array(
			array( 'title' => '', 'type' => 'title', 'id' => 'product_widget_visibility_options' ),

			array(
				'title'         => __( 'Widgets & Blocks', 'woocommerce-germanized' ),
				'desc'          => __( 'Shipping Costs notice', 'woocommerce-germanized' ),
				'id'            => 'woocommerce_gzd_display_product_widget_shipping_costs',
				'type'          => 'gzd_toggle',
				'default'       => 'yes',
				'checkboxgroup' => 'start',
			),
			array(
				'desc'          => __( 'Tax Info', 'woocommerce-germanized' ),
				'id'            => 'woocommerce_gzd_display_product_widget_tax_info',
				'type'          => 'gzd_toggle',
				'default'       => 'yes',
				'checkboxgroup' => '',
			),
			array(
				'desc'          => __( 'Unit Price', 'woocommerce-germanized' ),
				'id'            => 'woocommerce_gzd_display_product_widget_unit_price',
				'type'          => 'gzd_toggle',
				'default'       => 'yes',
				'checkboxgroup' => '',
			),
			array(
				'desc'          => __( 'Product Units', 'woocommerce-germanized' ),
				'id'            => 'woocommerce_gzd_display_product_widget_product_units',
				'type'          => 'gzd_toggle',
				'default'       => 'no',
				'checkboxgroup' => '',
			),
			array(
				'desc'          => __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id'            => 'woocommerce_gzd_display_product_widget_delivery_time',
				'type'          => 'gzd_toggle',
				'default'       => 'yes',
				'checkboxgroup' => 'end',
			),

			array( 'type' => 'sectionend', 'id' => 'product_widget_visibility_options' )
		);
	}

	protected function get_email_settings() {
		return array(
			array( 'title' => '', 'type' => 'title', 'id' => 'email_visibility_options' ),

			array(
				'title' 	    => __( 'E-Mails', 'woocommerce-germanized' ),
				'desc' 		    => __( 'Unit Price', 'woocommerce-germanized' ),
				'id' 		    => 'woocommerce_gzd_display_emails_unit_price',
				'type'          => 'gzd_toggle',
				'default'	    => 'yes',
				'checkboxgroup'	=> 'start',
			),

			array(
				'desc' 		    => __( 'Product Units', 'woocommerce-germanized' ),
				'id' 		    => 'woocommerce_gzd_display_emails_product_units',
				'type'          => 'gzd_toggle',
				'default'	    => 'yes',
				'checkboxgroup' => '',
			),

			array(
				'desc' 		    => __( 'Delivery Time Notice', 'woocommerce-germanized' ),
				'id' 		    => 'woocommerce_gzd_display_emails_delivery_time',
				'type'          => 'gzd_toggle',
				'default'	    => 'yes',
				'checkboxgroup' => '',
			),

			array(
				'desc' 		    => __( 'Short Description', 'woocommerce-germanized' ),
				'id' 		    => 'woocommerce_gzd_display_emails_product_item_desc',
				'type'          => 'gzd_toggle',
				'default'	    => 'yes',
				'checkboxgroup'	=> 'end',
			),

			array( 'type' => 'sectionend', 'id' => 'email_visibility_options' )
		);
	}

	protected function get_display_settings( $location ) {
		$title      = Shopmarks::get_location_title( $location );
		$visibility = array(
			array( 'title' => '', 'type' => 'title', 'id' => $location . '_visibility_options' )
		);

		foreach ( Shopmarks::get( $location ) as $shopmark ) {
			$title          = Shopmarks::get_type_title( $location, $shopmark->get_type() );
			$default_filter = $shopmark->get_default_filter();

			$visibility[] = array(
				'title'   => $title,
				'desc'    => sprintf( __( 'Show %s shopmark', 'woocommerce-germanized' ), $title ),
				'id'      => $shopmark->get_option_name(),
				'default' => $shopmark->is_default_enabled() ? 'yes' : 'no',
				'type'    => 'gzd_toggle',
			);

			if ( 'legal' === $shopmark->get_type() ) {
				$visibility[] = array(
					'title'             => __( 'Tax', 'woocommerce-germanized' ),
					'desc'              => __( 'Show Tax shopmark', 'woocommerce-germanized' ),
					'id'                => 'woocommerce_gzd_display_product_detail_tax_info',
					'default'           => $shopmark->is_default_enabled() ? 'yes' : 'no',
					'type'              => 'gzd_toggle',
					'custom_attributes' => array( 'data-show_if_' . $shopmark->get_option_name() => '' ),
				);

				$visibility[] = array(
					'title'             => __( 'Shipping Costs', 'woocommerce-germanized' ),
					'desc'              => __( 'Show Shipping Costs shopmark', 'woocommerce-germanized' ),
					'id'                => 'woocommerce_gzd_display_product_detail_shipping_costs_info',
					'default'           => $shopmark->is_default_enabled() ? 'yes' : 'no',
					'type'              => 'gzd_toggle',
					'custom_attributes' => array( 'data-show_if_' . $shopmark->get_option_name() => '' ),
				);
			}

			$visibility[] = array(
				'title'             => __( 'Location', 'woocommerce-germanized' ),
				'desc'              => __( 'Choose a location for the shopmark. Locations are mapped to specific WooCommerce hooks and may differ from Theme to Theme.', 'woocommerce-germanized' ),
				'desc_tip'          => true,
				'id'                => $shopmark->get_option_name( 'filter' ),
				'default'           => $default_filter,
				'type'              => 'select',
				'options'           => Shopmarks::get_filter_options( $location ),
				'class'             => 'wc-enhanced-select-nostd',
				'custom_attributes' => array(
					'data-show_if_' . $shopmark->get_option_name() => '',
					'data-placeholder'                             => Shopmarks::get_filter_title( $location, $default_filter ),
				),
			);

			$visibility[] = array(
				'title'             => __( 'Priority', 'woocommerce-germanized' ),
				'desc'              => sprintf( __( 'Choose a priority by which the shopmark should be attached to the location. The higher the priority, the later the shopmark will be attached. Defaults to %d.', 'woocommerce-germanized' ), $shopmark->get_default_priority() ),
				'desc_tip'          => true,
				'id'                => $shopmark->get_option_name( 'priority' ),
				'default'           => $shopmark->get_default_priority(),
				'type'              => 'number',
				'css'               => 'max-width: 60px',
				'custom_attributes' => array( 'data-show_if_' . $shopmark->get_option_name() => '', 'min' => 0 ),
			);
		}

		$visibility[] = array( 'type' => 'sectionend', 'id' => $location . '_visibility_options' );

		return $visibility;
	}

	protected function get_general_settings() {

		$settings = array(
			array(
				'title' => __( 'Price Ranges', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'price_range_options'
			),

			array(
				'title'   => __( 'Price Range Format', 'woocommerce-germanized' ),
				'desc'    => '<div class="wc-gzd-additional-desc">' . __( 'Adjust the price range format e.g. for variable products. Use {min_price} as placeholder for the minimum price. Use {max_price} as placeholder for the maximum price.', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'woocommerce_gzd_price_range_format_text',
				'type'    => 'text',
				'css'     => 'min-width:300px;',
				'default' => __( '{min_price} &ndash; {max_price}', 'woocommerce-germanized' ),
			),

			array( 'type' => 'sectionend', 'id' => 'price_range_options' ),

			array(
				'title' => __( 'Shipping Costs', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'shipping_costs_options'
			),

			array(
				'title'    => __( 'Notice Text', 'woocommerce-germanized' ),
				'desc'     => '<div class="wc-gzd-additional-desc">' . __( 'This text will be used to inform the customer about shipping costs. Use {link}{/link} to insert link to shipping costs page.', 'woocommerce-germanized' ) . '</div>',
				'desc_tip' => false,
				'id'       => 'woocommerce_gzd_shipping_costs_text',
				'type'     => 'text',
				'css'      => 'min-width:300px;',
				'default'  => __( 'plus {link}Shipping Costs{/link}', 'woocommerce-germanized' ),
			),
			array(
				'title'    => __( 'Free Shipping Text', 'woocommerce-germanized' ),
				'desc'     => '<div class="wc-gzd-additional-desc">' . __( 'This text will be used to inform the customer about free shipping. Leave empty to disable notice. Use {link}{/link} to insert link to shipping costs page.', 'woocommerce-germanized' ) . '</div>',
				'desc_tip' => false,
				'id'       => 'woocommerce_gzd_free_shipping_text',
				'type'     => 'text',
				'css'      => 'min-width:300px;',
				'default'  => '',
			),
			array(
				'title' 	=> __( 'Hide Notice', 'woocommerce-germanized' ),
				'desc' 		=> __( 'Select product types for which you might want to disable the shipping costs notice.', 'woocommerce-germanized' ),				'desc_tip' => true,
				'id'       => 'woocommerce_gzd_display_shipping_costs_hidden_types',
				'class'    => 'wc-enhanced-select',
				'type'     => 'multiselect',
				'options'  => $this->get_digital_type_options(),
				'default'  => array( 'downloadable', 'external', 'virtual' ),
			),

			array( 'type' => 'sectionend', 'id' => 'shipping_costs_options' ),

			array( 'title' => __( 'Footer', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'footer_options' ),

			array(
				'title'         => __( 'Notice', 'woocommerce-germanized' ),
				'desc'          => __( 'Attach a global VAT notice to your footer.', 'woocommerce-germanized' ),
				'id'            => 'woocommerce_gzd_display_footer_vat_notice',
				'default'       => 'no',
				'type'          => 'gzd_toggle',
				'checkboxgroup' => 'start'
			),
			array(
				'desc'          => __( 'Attach a global sale price notice to your footer.', 'woocommerce-germanized' ),
				'id'            => 'woocommerce_gzd_display_footer_sale_price_notice',
				'type'          => 'gzd_toggle',
				'default'       => 'no',
				'checkboxgroup' => 'end',
			),

			array( 'type' => 'sectionend', 'id' => 'footer_options' ),
		);

		return $settings;
	}

	protected function get_digital_type_options() {
		$product_types        = wc_get_product_types();
		$digital_type_options = array_merge( array(
			'downloadable' => __( 'Downloadable Product', 'woocommerce-germanized' ),
			'virtual'      => __( 'Virtual Product', 'woocommerce-germanized' ),
			'service'      => __( 'Service', 'woocommerce-germanized' )
		), $product_types );

		return $digital_type_options;
	}

	protected function get_delivery_time_settings() {
		$delivery_terms = array( '' => __( 'None', 'woocommerce-germanized' ) );
		$terms          = get_terms( 'product_delivery_time', array( 'fields' => 'id=>name', 'hide_empty' => false ) );

		if ( ! is_wp_error( $terms ) ) {
			$delivery_terms = $delivery_terms + $terms;
		}

		return array(
			array( 'title' => '', 'type' => 'title', 'id' => 'delivery_time_options', 'desc' => '' ),

			array(
				'title'    => __( 'Fallback', 'woocommerce-germanized' ),
				'desc_tip' => __( 'This delivery time will be added to every product if no delivery time has been chosen individually', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_default_delivery_time',
				'css'      => 'min-width:250px;',
				'default'  => '',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'options'  => $delivery_terms,
				'desc'     => '<a href="' . admin_url( 'edit-tags.php?taxonomy=product_delivery_time&post_type=product' ) . '">' . __( 'Manage Delivery Times', 'woocommerce-germanized' ) . '</a>',
			),
			array(
				'title'    => __( 'Format', 'woocommerce-germanized' ),
				'desc'     => '<div class="wc-gzd-additional-desc"> ' . __( 'This text will be used to indicate delivery time for products. Use {delivery_time} as placeholder.', 'woocommerce-germanized' ) . '</div>',
				'desc_tip' => false,
				'id'       => 'woocommerce_gzd_delivery_time_text',
				'type'     => 'text',
				'default'  => __( 'Delivery time: {delivery_time}', 'woocommerce-germanized' ),
			),
			array(
				'title'    => __( 'Digital text', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_gzd_display_digital_delivery_time_text',
				'default'  => '',
				'type'     => 'text',
				'desc_tip' => __( 'Enter a text which will be shown as digital delivery time text (replacement for default digital time on digital products).', 'woocommerce-germanized' ),
			),
			array(
				'title'   => __( 'Backorder', 'woocommerce-germanized' ),
				'desc'    => __( 'Hide delivery time if a product is on backorder.', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_gzd_delivery_time_disable_backorder',
				'default' => 'no',
				'type'    => 'gzd_toggle',
			),
			array(
				'title'   => __( 'Not in Stock', 'woocommerce-germanized' ),
				'desc'    => __( 'Hide delivery time if a product is not in stock.', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_gzd_delivery_time_disable_not_in_stock',
				'default' => 'no',
				'type'    => 'gzd_toggle',
			),
			array(
				'title'    => __( 'Hide Notice', 'woocommerce-germanized' ),
				'desc'     => __( 'Select product types for which you might want to disable the delivery time notice.', 'woocommerce-germanized' ),
				'desc_tip' => true,
				'id'       => 'woocommerce_gzd_display_delivery_time_hidden_types',
				'class'    => 'wc-enhanced-select',
				'type'     => 'multiselect',
				'options'  => $this->get_digital_type_options(),
				'default'  => array( 'external', 'virtual' ),
			),

			array( 'type' => 'sectionend', 'id' => 'delivery_time_options' ),
		);
	}

	protected function get_unit_price_settings() {
		return array(
			array( 'type' => 'title', 'title' => '', 'id' => 'unit_price_options' ),
			array(
				'title'    => __( 'Format', 'woocommerce-germanized' ),
				'desc'     => '<div class="wc-gzd-additional-desc">' . __( 'This text will be used to display the unit price. Use {price} to insert the price. If you want to specifically format unit price output use {base}, {unit} and {unit_price} as placeholders.', 'woocommerce-germanized' ) . '</div>',
				'desc_tip' => false,
				'id'       => 'woocommerce_gzd_unit_price_text',
				'type'     => 'text',
				'default'  => __( '{price}', 'woocommerce-germanized' ),
			),
			array(
				'title'    => __( 'Product units format', 'woocommerce-germanized' ),
				'desc'     => '<div class="wc-gzd-additional-desc">' . __( 'This text will be used to display the product units. Use {product_units} to insert the amount of product units. Use {unit} to insert the unit. Optionally display the formatted unit price with {unit_price}.', 'woocommerce-germanized' ) . '</div>',
				'desc_tip' => false,
				'id'       => 'woocommerce_gzd_product_units_text',
				'type'     => 'text',
				'default'  => __( 'Product contains: {product_units} {unit}', 'woocommerce-germanized' ),
			),
			array(
				'title'   => __( 'Variable Unit Price', 'woocommerce-germanized' ),
				'desc'    => __( 'Enable price range unit prices for variable products.', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_gzd_unit_price_enable_variable',
				'default' => 'yes',
				'type'    => 'gzd_toggle',
			),
			array( 'type' => 'sectionend', 'id' => 'unit_price_options' ),
		);
	}

	protected function get_price_label_settings() {
		$labels = array_merge( array( '' => __( 'None', 'woocommerce-germanized' ) ), WC_Germanized()->price_labels->get_labels() );

		return array(
			array( 'type' => 'title', 'title' => '', 'id' => 'price_label_options' ),
			array(
				'title'   => __( 'Fallback Sale Label', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_gzd_default_sale_price_label',
				'css'     => 'min-width:250px;',
				'default' => '',
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'options' => $labels,
				'desc'    => '<a href="' . admin_url( 'edit-tags.php?taxonomy=product_price_label&post_type=product' ) . '">' . __( 'Manage Price Labels', 'woocommerce-germanized' ) . '</a><div class="wc-gzd-additional-desc">' . __( 'Choose whether you would like to have a default sale price label to inform the customer about the regular price (e.g. Recommended Retail Price).', 'woocommerce-germanized' ) . '</div>',
			),
			array(
				'title'   => __( 'Fallback Regular Label', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_gzd_default_sale_price_regular_label',
				'css'     => 'min-width:250px;',
				'default' => '',
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'options' => $labels,
				'desc'    => '<a href="' . admin_url( 'edit-tags.php?taxonomy=product_price_label&post_type=product' ) . '">' . __( 'Manage Price Labels', 'woocommerce-germanized' ) . '</a><div class="wc-gzd-additional-desc">' . __( 'Choose whether you would like to have a default sale price regular label to inform the customer about the sale price (e.g. New Price).', 'woocommerce-germanized' ) . '</div>',
			),

			array(
				'title'   => __( 'Single Product', 'woocommerce-germanized' ),
				'desc'    => __( 'Show price labels on single product page.', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_gzd_display_product_detail_sale_price_labels',
				'type'    => 'gzd_toggle',
				'default' => 'yes',
			),

			array(
				'title'   => __( 'Loop', 'woocommerce-germanized' ),
				'desc'    => __( 'Show price labels in product loops.', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_gzd_display_listings_sale_price_labels',
				'type'    => 'gzd_toggle',
				'default' => 'yes',
			),

			array( 'type' => 'sectionend', 'id' => 'price_label_options' ),
		);
	}
}