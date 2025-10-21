<?php

namespace Vendidero\Shiptastic\Admin\Tabs;

use Vendidero\Shiptastic\Admin\Settings;
use Vendidero\Shiptastic\Admin\Tutorial;
use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\Packaging\ReportHelper;
use Vendidero\Shiptastic\Packaging\ReportQueue;

class Packaging extends Tab {

	public function get_description() {
		return _x( 'Manage available packaging options and create packaging reports.', 'shipments', 'woocommerce-germanized' );
	}

	public function get_label() {
		return _x( 'Pick & Pack', 'shipments', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'packaging';
	}

	public function get_sections() {
		$sections = array(
			''        => _x( 'Packaging', 'shipments', 'woocommerce-germanized' ),
			'reports' => _x( 'Reports', 'shipments', 'woocommerce-germanized' ),
		);

		if ( Package::is_packing_supported() ) {
			$sections['packing'] = _x( 'Packing', 'shipments', 'woocommerce-germanized' );
		}

		return $sections;
	}

	public function get_section_description( $section ) {
		return '';
	}

	protected function get_packing_settings() {
		return array(
			array(
				'title' => _x( 'Automated packing', 'shipments', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'automated_packing_options',
			),

			array(
				'title'   => _x( 'Enable', 'shipments', 'woocommerce-germanized' ),
				'desc'    => _x( 'Automatically pack orders based on available packaging options', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc">' . _x( 'By enabling this option, shipments will be packed based on your available packaging options. For that purpose a knapsack algorithm is used to best fit available order items within your packaging. <a href="https://vendidero.com/doc/shiptastic/pack-shipments-automatically" target="_blank">Learn more</a> about the feature.', 'shipments', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'woocommerce_shiptastic_enable_auto_packing',
				'default' => 'yes',
				'type'    => 'shiptastic_toggle',
			),

			array(
				'title'             => _x( 'Grouping', 'shipments', 'woocommerce-germanized' ),
				'desc'              => _x( 'Group items by shipping class.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'Use this option to prevent items with different shipping classes from being packed in the same package.', 'shipments', 'woocommerce-germanized' ) ) . '</div>',
				'id'                => 'woocommerce_shiptastic_packing_group_by_shipping_class',
				'default'           => 'no',
				'type'              => 'shiptastic_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_shiptastic_enable_auto_packing' => '',
				),
			),

			array(
				'title'             => _x( 'Balance weights', 'shipments', 'woocommerce-germanized' ),
				'desc'              => _x( 'Automatically balance weights between packages in case multiple packages are needed.', 'shipments', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_shiptastic_packing_balance_weights',
				'default'           => 'no',
				'type'              => 'shiptastic_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_shiptastic_enable_auto_packing' => '',
				),
			),

			array(
				'title'             => _x( 'Buffer type', 'shipments', 'woocommerce-germanized' ),
				'desc'              => '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'Choose a buffer type to leave space between the items and outer dimensions of your packaging.', 'shipments', 'woocommerce-germanized' ) ) . '</div>',
				'id'                => 'woocommerce_shiptastic_packing_inner_buffer_type',
				'default'           => 'fixed',
				'type'              => 'select',
				'options'           => array(
					'fixed'      => _x( 'Fixed', 'shipments', 'woocommerce-germanized' ),
					'percentage' => _x( 'Percentage', 'shipments', 'woocommerce-germanized' ),
				),
				'custom_attributes' => array(
					'data-show_if_woocommerce_shiptastic_enable_auto_packing' => '',
				),
			),

			array(
				'title'             => _x( 'Fixed Buffer', 'shipments', 'woocommerce-germanized' ),
				'desc'              => 'mm',
				'id'                => 'woocommerce_shiptastic_packing_inner_fixed_buffer',
				'default'           => '5',
				'type'              => 'number',
				'row_class'         => 'with-suffix',
				'css'               => 'max-width: 60px',
				'custom_attributes' => array(
					'data-show_if_woocommerce_shiptastic_enable_auto_packing' => '',
					'data-show_if_woocommerce_shiptastic_packing_inner_buffer_type' => 'fixed',
					'step' => 1,
				),
			),

			array(
				'title'             => _x( 'Percentage Buffer', 'shipments', 'woocommerce-germanized' ),
				'desc'              => '%',
				'id'                => 'woocommerce_shiptastic_packing_inner_percentage_buffer',
				'default'           => '0.5',
				'type'              => 'number',
				'row_class'         => 'with-suffix',
				'css'               => 'max-width: 60px',
				'custom_attributes' => array(
					'data-show_if_woocommerce_shiptastic_enable_auto_packing' => '',
					'data-show_if_woocommerce_shiptastic_packing_inner_buffer_type' => 'percentage',
					'step' => 0.1,
				),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'automated_packing_options',
			),
		);
	}

	protected function get_reports_settings() {
		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'report_options',
			),
			array(
				'type'  => 'packaging_reports',
				'title' => _x( 'Packaging Report', 'shipments', 'woocommerce-germanized' ),
				'id'    => 'packaging_reports',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'report_options',
			),
		);
	}

	protected function after_save( $settings, $current_section = '' ) {
		parent::after_save( $settings, $current_section );

		if ( 'reports' === $current_section ) {
			if ( isset( $_POST['save'] ) && 'create_report' === wc_clean( wp_unslash( $_POST['save'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$start_date = isset( $_POST['report_year'] ) ? wc_clean( wp_unslash( $_POST['report_year'] ) ) : '01-01-' . ( (int) date( 'Y' ) - 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.DateTime.RestrictedFunctions.date_date
				$start_date = ReportHelper::string_to_datetime( $start_date );

				ReportQueue::start( 'yearly', $start_date );
			}
		}
	}

	protected function get_general_settings() {
		return array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'packaging_list_options',
			),

			array(
				'type' => 'packaging_list',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'packaging_list_options',
			),

			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'packaging_options',
			),

			array(
				'title'    => _x( 'Default packaging', 'shipments', 'woocommerce-germanized' ),
				'desc_tip' => _x( 'Choose a packaging which serves as fallback or default in case no suitable packaging could be matched for a certain shipment.', 'shipments', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_shiptastic_default_packaging',
				'default'  => '',
				'type'     => 'select',
				'options'  => wc_stc_get_packaging_select(),
				'class'    => 'wc-enhanced-select',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'packaging_options',
			),
		);
	}

	public function get_pointers() {
		$section  = $this->get_current_section();
		$pointers = array();

		if ( '' === $section ) {
			$pointers = array(
				'pointers' => array(
					'packaging-edit' => array(
						'target'       => 'tbody.packaging_list .wc-stc-shipment-action-button:last',
						'next'         => 'packaging-add',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Edit packaging', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Configure additional packaging settings such as shipping class restrictions or inner dimensions.', 'shipments', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'right',
								'align' => 'left',
							),
						),
					),
					'packaging-add'  => array(
						'target'       => '#packaging_list_wrapper a.add',
						'next'         => 'auto',
						'next_url'     => Tutorial::get_tutorial_url( 'packaging', 'reports' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Add packaging', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Add all your available packaging options to make sure the packing algorithm knows about it.', 'shipments', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'reports' === $section ) {
			$pointers = array(
				'pointers' => array(
					'create_report' => array(
						'target'       => '.wc-shiptastic-create-packaging-report button',
						'next'         => '',
						'next_url'     => Tutorial::get_tutorial_url( 'packaging', 'packing' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Create packaging reports', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'You may create yearly packaging reports, e.g. for recycling purposes.', 'shipments', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'packing' === $section ) {
			$last_url = Tutorial::get_last_tutorial_url();

			$pointers = array(
				'pointers' => array(
					'auto' => array(
						'target'       => '#woocommerce_shiptastic_enable_auto_packing-toggle',
						'next'         => '',
						'next_url'     => Tutorial::get_last_tutorial_url(),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Automated packing', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'The packing algorithm will determine which packaging option to use and may split an order into multiple shipments automatically.', 'shipments', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);

			if ( strstr( $last_url, 'tab=shiptastic' ) ) {
				$pointers['pointers']['auto']['last_step'] = true;
			}
		}

		return $pointers;
	}

	public function get_tab_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = $this->get_general_settings();
		} elseif ( 'packing' === $current_section ) {
			$settings = $this->get_packing_settings();
		} elseif ( 'reports' === $current_section ) {
			$settings = $this->get_reports_settings();
		}

		return $settings;
	}
}
