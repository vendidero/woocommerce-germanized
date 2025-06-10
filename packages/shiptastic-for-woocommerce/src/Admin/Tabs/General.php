<?php

namespace Vendidero\Shiptastic\Admin\Tabs;

use Vendidero\Shiptastic\Admin\Settings;
use Vendidero\Shiptastic\Admin\Tutorial;

class General extends Tab {

	public function get_description() {
		return _x( 'Configure when and how to create shipments and manage your addresses.', 'shipments', 'woocommerce-germanized' );
	}

	public function get_label() {
		return _x( 'General', 'shipments', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'general';
	}

	public function get_sections() {
		$sections = array(
			''                     => _x( 'General', 'shipments', 'woocommerce-germanized' ),
			'automation'           => _x( 'Automation', 'shipments', 'woocommerce-germanized' ),
			'return'               => _x( 'Returns', 'shipments', 'woocommerce-germanized' ),
			'business_information' => _x( 'Business Information', 'shipments', 'woocommerce-germanized' ),
		);

		return $sections;
	}

	public function get_section_description( $section ) {
		return '';
	}

	public function get_pointers() {
		$current_section = $this->get_current_section();
		$pointers        = array();

		if ( '' === $current_section ) {
			$next_url = Tutorial::get_tutorial_url( 'general', 'automation' );

			$pointers = array(
				'pointers' => array(
					'default' => array(
						'target'       => '#woocommerce_shiptastic_notify_enable-toggle',
						'next'         => '',
						'next_url'     => $next_url,
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'E-Mail Notification', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'By enabling this option customers receive an email notification as soon as a shipment is marked as shipped.', 'shipments', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'automation' === $current_section ) {
			$next_url = Tutorial::get_tutorial_url( 'general', 'return' );

			$pointers = array(
				'pointers' => array(
					'auto' => array(
						'target'       => '#woocommerce_shiptastic_auto_enable-toggle',
						'next'         => '',
						'next_url'     => $next_url,
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Automation', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Decide whether you want to automatically create shipments to orders reaching a specific status. You can always adjust your shipments by manually editing the shipment within the edit order screen.', 'shipments', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif ( 'return' === $current_section ) {
			$next_url = Tutorial::get_tutorial_url( 'general', 'business_information' );

			$pointers = array(
				'pointers' => array(
					'returns' => array(
						'target'       => '#shipments_return_options-description',
						'next'         => '',
						'next_url'     => $next_url,
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Returns', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . wp_kses_post( _x( 'Minimize manual work while handling customer returns. Learn more about returns within our <a target="_blank" href="https://vendidero.com/doc/shiptastic/manage-returns">docs</a>.', 'shipments', 'woocommerce-germanized' ) ) . '</p>',
							'position' => array(
								'edge'  => 'top',
								'align' => 'top',
							),
						),
					),
				),
			);
		} elseif ( 'business_information' === $current_section ) {
			$next_url = Tutorial::get_tutorial_url( 'shipping_provider' );

			$pointers = array(
				'pointers' => array(
					'returns' => array(
						'target'       => '#woocommerce_shiptastic_shipper_address_first_name',
						'next'         => '',
						'next_url'     => $next_url,
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Shipper Address', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . _x( 'Make sure to keep your business information up-to-date as the data will be used within labels and returns.', 'shipments', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'top',
								'align' => 'top',
							),
						),
					),
				),
			);
		}

		return $pointers;
	}

	public static function get_address_label_by_prop( $prop, $type = 'shipper' ) {
		$label  = '';
		$fields = wc_stc_get_shipment_setting_default_address_fields( $type );

		if ( array_key_exists( $prop, $fields ) ) {
			$label = $fields[ $prop ];
		}

		return $label;
	}

	protected static function get_address_field_type_by_prop( $prop ) {
		$type = 'text';

		if ( 'country' === $prop ) {
			$type = 'shipments_country_select';
		}

		return $type;
	}

	protected static function get_address_desc_by_prop( $prop ) {
		$desc = false;

		if ( 'customs_reference_number' === $prop ) {
			$desc = _x( 'Your customs reference number, e.g. EORI number', 'shipments', 'woocommerce-germanized' );
		} elseif ( 'customs_uk_vat_id' === $prop ) {
			$desc = _x( 'Your UK VAT ID, e.g. for UK exports <= 135 GBP.', 'shipments', 'woocommerce-germanized' );
		}

		return $desc;
	}

	protected static function get_address_fields_to_skip() {
		return array( 'state', 'street', 'street_number', 'full_name' );
	}

	protected function get_business_information_settings() {
		$shipper_fields = wc_stc_get_shipment_setting_address_fields( 'shipper' );
		$return_fields  = wc_stc_get_shipment_setting_address_fields( 'return' );

		$settings = array(
			array(
				'title' => _x( 'Shipper Address', 'shipments', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'shipments_shipper_address',
			),
		);

		foreach ( $shipper_fields as $field => $value ) {
			if ( in_array( $field, $this->get_address_fields_to_skip(), true ) ) {
				continue;
			}

			$settings = array_merge(
				$settings,
				array(
					array(
						'title'        => $this->get_address_label_by_prop( $field ),
						'type'         => $this->get_address_field_type_by_prop( $field ),
						'id'           => "woocommerce_shiptastic_shipper_address_{$field}",
						'default'      => 'country' === $field ? $value . ':' . $shipper_fields['state'] : $value,
						'desc_tip'     => $this->get_address_desc_by_prop( $field ),
						'skip_install' => true,
					),
				)
			);
		}

		$settings = array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'shipments_shipper_address',
				),
				array(
					'title' => _x( 'Return Address', 'shipments', 'woocommerce-germanized' ),
					'type'  => 'title',
					'id'    => 'shipments_return_address',
				),
				array(
					'title'   => _x( 'Alternate return?', 'shipments', 'woocommerce-germanized' ),
					'desc'    => _x( 'Optionally configure a separate return address', 'shipments', 'woocommerce-germanized' ),
					'id'      => 'woocommerce_shiptastic_use_alternate_return',
					'default' => ! empty( get_option( 'woocommerce_shiptastic_return_address_address_1', '' ) ) ? 'yes' : 'no',
					'type'    => 'shiptastic_toggle',
				),
			)
		);

		foreach ( $return_fields as $field => $value ) {
			if ( in_array( $field, $this->get_address_fields_to_skip(), true ) ) {
				continue;
			}

			$settings = array_merge(
				$settings,
				array(
					array(
						'title'             => $this->get_address_label_by_prop( $field ),
						'type'              => $this->get_address_field_type_by_prop( $field ),
						'id'                => "woocommerce_shiptastic_return_address_{$field}",
						'default'           => 'country' === $field ? $value . ':' . $return_fields['state'] : $value,
						'desc_tip'          => $this->get_address_desc_by_prop( $field ),
						'skip_install'      => true,
						'custom_attributes' => array(
							'data-show_if_woocommerce_shiptastic_use_alternate_return' => '',
						),
					),
				)
			);
		}

		$settings = array_merge(
			$settings,
			array(
				array(
					'type' => 'sectionend',
					'id'   => 'shipments_shipper_address',
				),
			)
		);

		return $settings;
	}

	protected function get_automation_settings() {
		$statuses = array_diff_key( wc_stc_get_shipment_statuses(), array_flip( array( 'requested' ) ) );

		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'shipments_auto_options',
			),

			array(
				'title'   => _x( 'Enable', 'shipments', 'woocommerce-germanized' ),
				'desc'    => _x( 'Automatically create shipments for orders.', 'shipments', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_shiptastic_auto_enable',
				'default' => 'yes',
				'type'    => 'shiptastic_toggle',
			),

			array(
				'title'             => _x( 'Order statuses', 'shipments', 'woocommerce-germanized' ),
				'desc_tip'          => _x( 'Create shipments as soon as the order reaches one of the following status(es).', 'shipments', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_shiptastic_auto_statuses',
				'default'           => array( 'wc-processing', 'wc-on-hold' ),
				'class'             => 'wc-enhanced-select-nostd',
				'options'           => wc_get_order_statuses(),
				'type'              => 'multiselect',
				'custom_attributes' => array(
					'data-show_if_woocommerce_shiptastic_auto_enable' => '',
					'data-placeholder' => _x( 'On new order creation', 'shipments', 'woocommerce-germanized' ),
				),
			),

			array(
				'title'             => _x( 'Default status', 'shipments', 'woocommerce-germanized' ),
				'desc_tip'          => _x( 'Choose a default status for the automatically created shipment.', 'shipments', 'woocommerce-germanized' ),
				'id'                => 'woocommerce_shiptastic_auto_default_status',
				'default'           => 'processing',
				'class'             => 'wc-enhanced-select',
				'options'           => $statuses,
				'type'              => 'select',
				'custom_attributes' => array(
					'data-show_if_woocommerce_shiptastic_auto_enable' => '',
				),
			),

			array(
				'title'   => _x( 'Update status', 'shipments', 'woocommerce-germanized' ),
				'desc'    => _x( 'Mark order as completed after order is fully shipped.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc">' . _x( 'This option will automatically update the order status to completed as soon as all required shipments have been marked as shipped.', 'shipments', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'woocommerce_shiptastic_auto_order_shipped_completed_enable',
				'default' => 'no',
				'type'    => 'shiptastic_toggle',
			),

			array(
				'title'   => _x( 'Mark as shipped', 'shipments', 'woocommerce-germanized' ),
				'desc'    => _x( 'Mark shipments as shipped after order completion.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc">' . _x( 'This option will automatically update contained shipments to shipped (if possible, e.g. not yet delivered) as soon as the order was marked as completed.', 'shipments', 'woocommerce-germanized' ) . '</div>',
				'id'      => 'woocommerce_shiptastic_auto_order_completed_shipped_enable',
				'default' => 'no',
				'type'    => 'shiptastic_toggle',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'shipments_auto_options',
			),
		);

		return $settings;
	}

	protected function get_return_settings() {
		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'shipments_return_options',
				'desc'  => sprintf( _x( 'Returns can be added manually by the shop manager or by the customer. Choose what suits you best by adjusting your <a href="%s">shipping service provider settings</a>.', 'shipments', 'woocommerce-germanized' ), esc_url( Settings::get_settings_url( 'shipping_provider' ) ) ),
			),

			array(
				'type' => 'shipment_return_reasons',
			),

			array(
				'title'   => _x( 'Days to return', 'shipments', 'woocommerce-germanized' ),
				'desc'    => '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'In case one of your <a href="%s">shipping service providers</a> supports returns added by customers you might want to limit the number of days a customer is allowed to add returns to an order. The days are counted starting with the date the order was shipped, completed or created (by checking for existance in this order).', 'shipments', 'woocommerce-germanized' ), esc_url( Settings::get_settings_url( 'shipping_provider' ) ) ) . '</div>',
				'css'     => 'max-width: 60px;',
				'type'    => 'number',
				'id'      => 'woocommerce_shiptastic_customer_return_open_days',
				'default' => '14',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'shipments_return_options',
			),
		);

		return $settings;
	}

	protected function get_general_settings() {
		$settings = array(
			array(
				'title' => '',
				'type'  => 'title',
				'id'    => 'shipments_options',
			),

			array(
				'title'   => _x( 'Notification', 'shipments', 'woocommerce-germanized' ),
				'desc'    => _x( 'Send shipping notification to customers.', 'shipments', 'woocommerce-germanized' ) . '<div class="wc-shiptastic-additional-desc">' . sprintf( _x( 'Notify customers by email as soon as a shipment is marked as shipped. %s the notification email.', 'shipments', 'woocommerce-germanized' ), '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=wc_stc_email_customer_shipment' ) ) . '" target="_blank">' . _x( 'Manage', 'shipments notification', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'id'      => 'woocommerce_shiptastic_notify_enable',
				'default' => 'yes',
				'type'    => 'shiptastic_toggle',
			),

			array(
				'title'    => _x( 'Default provider', 'shipments', 'woocommerce-germanized' ),
				'desc_tip' => _x( 'Select a default shipping service provider which will be selected by default in case no provider could be determined automatically.', 'shipments', 'woocommerce-germanized' ),
				'id'       => 'woocommerce_shiptastic_default_shipping_provider',
				'default'  => '',
				'type'     => 'select',
				'options'  => wc_stc_get_shipping_provider_select(),
				'class'    => 'wc-enhanced-select',
			),

			array(
				'title'   => _x( 'Customer Account', 'shipments', 'woocommerce-germanized' ),
				'desc'    => _x( 'List shipments and return options, if available, within customer account.', 'shipments', 'woocommerce-germanized' ),
				'id'      => 'woocommerce_shiptastic_customer_account_enable',
				'default' => 'yes',
				'type'    => 'shiptastic_toggle',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'shipments_options',
			),
		);

		return $settings;
	}

	public function get_tab_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = $this->get_general_settings();
		} elseif ( 'automation' === $current_section ) {
			$settings = $this->get_automation_settings();
		} elseif ( 'return' === $current_section ) {
			$settings = $this->get_return_settings();
		} elseif ( 'business_information' === $current_section ) {
			$settings = $this->get_business_information_settings();
		}

		return $settings;
	}
}
