<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Vendidero\Germanized\Shipments\Admin\Settings;

/**
 * Adds Germanized Shipments settings.
 *
 * @class 		WC_GZD_Settings_Tab_Shipments
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZD_Settings_Tab_Shipments extends WC_GZD_Settings_Tab {

	public function get_description() {
		return __( 'Create shipments for your orders and improve default shipment handling.', 'woocommerce-germanized' );
	}

	public function get_label() {
		return __( 'Shipments', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'shipments';
	}

	public function get_sections() {
		return Settings::get_sections();
	}

	protected function get_breadcrumb_label( $label ) {
		$current_section = $this->get_current_section();

		if ( empty( $current_section ) ) {
			return $label . '<a href="' . admin_url( 'admin.php?page=wc-gzd-shipments' ) . '" class="page-title-action" target="_blank">' . __( 'Manage', 'woocommerce-germanized-shipments' ) . '</a>';
		}

		return $label;
	}

	public function get_pointers() {
		$current  = $this->get_current_section();
		$pointers = array();

		if ( '' === $current ) {
			$pointers = array(
				'pointers' => array(
					'display'          => array(
						'target'       => 'ul.subsubsub li:nth-of-type(2) a',
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
		} elseif( 'single_product' === $current ) {
			$pointers = array(
				'pointers' => array(
					'location'         => array(
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
					'priority'         => array(
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
		} elseif( 'delivery_times' === $current ) {
			$pointers = array(
				'pointers' => array(
					'default'          => array(
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
					'format'           => array(
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
		} elseif( 'price_labels' === $current ) {
			$pointers = array(
				'pointers' => array(
					'default'          => array(
						'target'       => '#select2-woocommerce_gzd_default_sale_price_label-container',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-button_solution&tutorial=yes' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html__( 'Default Sale Label', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html__( 'Price labels are added to sale products to inform the customers of the differnt prices\' meaning. You may add a fallback label in case a product does not contain a label.', 'woocommerce-germanized' ) . '</p>',
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
		return Settings::get_settings( $current_section );
	}
}