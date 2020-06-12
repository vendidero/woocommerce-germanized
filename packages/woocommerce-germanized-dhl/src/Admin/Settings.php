<?php

namespace Vendidero\Germanized\DHL\Admin;
use Vendidero\Germanized\DHL\Package;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class Settings {

	public static function get_section_description( $section ) {
		return '';
	}

	public static function get_pointers( $section ) {
		$pointers = array();

		if ( '' === $section ) {
			$pointers = array(
				'pointers' => array(
					'enable'           => array(
						'target'       => '#woocommerce_gzd_dhl_enable-toggle',
						'next'         => 'account',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Enable DHL', 'dhl', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'If you want to ship your shipments via DHL and create labels to your shipments please enable the DHL integration.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'account'          => array(
						'target'       => '#woocommerce_gzd_dhl_account_number',
						'next'         => 'api',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Customer Number', 'dhl', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'Insert your DHL business customer number (EKP) here. If you are not yet a business customer you might want to create a new account first.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'api'              => array(
						'target'       => Package::is_debug_mode() ? '#woocommerce_gzd_dhl_api_sandbox_username' : '#woocommerce_gzd_dhl_api_username',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl&section=labels&tutorial=yes' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'API Access', 'dhl', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'To create labels and embed DHL services, our software needs access to the API. You will need to fill out the username and password fields accordingly.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				),
			);
		} elseif( 'labels' === $section ) {
			$pointers = array(
				'pointers' => array(
					'inlay'          => array(
						'target'       => '#woocommerce_gzd_dhl_label_auto_inlay_return_label-toggle',
						'next'         => 'retoure',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Inlay Returns', 'dhl', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'If you want to provide your customers with inlay return labels for your shipments you might enable this feature by default here.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'retoure'          => array(
						'target'       => '#woocommerce_gzd_dhl_label_retoure_enable-toggle',
						'next'         => 'age_check',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Retoure', 'dhl', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'If you want to create DHL labels to returns you should activate this feature. Make sure that you have DHL Online Retoure activated in your contract.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'age_check'          => array(
						'target'       => '#woocommerce_gzd_dhl_label_auto_age_check_sync-toggle',
						'next'         => 'auto',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Age verification', 'dhl', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'Use this feature to sync the Germanized age verification checkbox with the DHL visual minimum age verification service. As soon as applicable products are contained within the shipment, the service will be booked by default.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'auto'          => array(
						'target'       => '#woocommerce_gzd_dhl_label_auto_enable-toggle',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl&section=services&tutorial=yes' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Automation', 'dhl', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'You might want to save some time and let Germanized generate labels automatically as soon as a shipment switches to a certain status.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					)
				)
			);
		} elseif( 'services' === $section ) {
			$pointers = array(
				'pointers' => array(
					'day'          => array(
						'target'       => '#woocommerce_gzd_dhl_PreferredDay_enable-toggle',
						'next'         => 'fee',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Preferred Day', 'dhl', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'Let your customers choose a preferred day (if the service is available at the customer\'s location) of delivery within your checkout.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'fee'          => array(
						'target'       => '#woocommerce_gzd_dhl_PreferredDay_cost',
						'next'         => 'location',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Fee', 'dhl', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'Optionally charge your customers an additional fee for preferred services like preferred day.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'location'          => array(
						'target'       => '#woocommerce_gzd_dhl_PreferredLocation_enable-toggle',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl&section=pickup&tutorial=yes' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Preferred Location', 'dhl', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'Allow your customers to send their parcels to a preferred location e.g. a neighbor. This service is free of charge for DHL shipments.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				)
			);
		} elseif( 'pickup' === $section ) {
			$pointers = array(
				'pointers' => array(
					'day'          => array(
						'target'       => '#woocommerce_gzd_dhl_parcel_pickup_packstation_enable-toggle',
						'next'         => 'map',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Packstation', 'dhl', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'Allow your customers to choose packstation (and/or other DHL location types as configured below) as shipping address.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'map'          => array(
						'target'       => '#woocommerce_gzd_dhl_parcel_pickup_map_enable-toggle',
						'next'         => '',
						'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-emails&tutorial=yes' ),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Map', 'dhl', 'woocommerce-germanized' ) . '</h3>' .
							              '<p>' . esc_html_x( 'This option adds a map overlay view to let your customers choose a DHL location from a map nearby. You\'ll need a valid Google Maps API key to enable the map view.', 'dhl', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
				)
			);
		}

		return $pointers;
	}

	public static function get_setup_settings( $is_settings_page = false ) {

		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'dhl_general_options' ),

			array(
				'title' 	        => _x( 'Enable', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Enable DHL integration.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_enable',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title'             => _x( 'Customer Number (EKP)', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Your 10 digits DHL customer number, also called "EKP". Find your %s in the DHL business portal.', 'dhl', 'woocommerce-germanized' ), '<a href="' . Package::get_geschaeftskunden_portal_url() .'" target="_blank">' . _x(  'customer number', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_dhl_account_number',
				'default'           => '',
				'placeholder'		=> '1234567890',
				'custom_attributes'	=> array( 'maxlength' => '10' )
			),

			array( 'type' => 'sectionend', 'id' => 'dhl_general_options' ),

			array( 'title' => _x( 'API', 'dhl', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'dhl_api_options' ),

			array(
				'title' 	=> _x( 'Enable Sandbox', 'dhl', 'woocommerce-germanized' ),
				'desc' 		=> _x( 'Activate Sandbox mode for testing purposes.', 'dhl', 'woocommerce-germanized' ),
				'id' 		=> 'woocommerce_gzd_dhl_sandbox_mode',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title'             => _x( 'Live Username', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Your username for the DHL business customer portal. Please note the lower case and test your access data in advance %s.', 'dhl', 'woocommerce-germanized' ), '<a href="' . Package::get_geschaeftskunden_portal_url() . '" target = "_blank">' . _x(  'here', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_dhl_api_username',
				'default'           => '',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_sandbox_mode' => 'no', 'autocomplete' => 'new-password' )
			),

			array(
				'title'             => _x( 'Live Password', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'password',
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Your password for the DHL business customer portal. Please note the new assignment of the password to 3 (Standard User) or 12 (System User) months and test your access data in advance %s.', 'dhl', 'woocommerce-germanized' ), '<a href="' . Package::get_geschaeftskunden_portal_url() . '" target = "_blank">' . _x(  'here', 'dhl', 'woocommerce-germanized' ) .'</a>' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_dhl_api_password',
				'default'           => '',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_sandbox_mode' => 'no', 'autocomplete' => 'new-password' )
			),

			array(
				'title'             => _x( 'Sandbox Username', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Your username for the DHL developer portal. Please note the lower case and test your access data in advance %s.', 'dhl', 'woocommerce-germanized' ), '<a href="https://entwickler.dhl.de" target = "_blank">' . _x(  'here', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_dhl_api_sandbox_username',
				'default'           => '',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_sandbox_mode' => '', 'autocomplete' => 'new-password' )
			),

			array(
				'title'             => _x( 'Sandbox Password', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'password',
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Your password for the DHL developer portal. Please test your access data in advance %s.', 'dhl', 'woocommerce-germanized' ), '<a href="https://entwickler.dhl.de" target = "_blank">' . _x(  'here', 'dhl', 'woocommerce-germanized' ) .'</a>' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_dhl_api_sandbox_password',
				'default'           => '',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_sandbox_mode' => '', 'autocomplete' => 'new-password' )
			),

			array( 'type' => 'sectionend', 'id' => 'dhl_api_options' ),
		);

		if ( ! $is_settings_page ) {
			$domestic = wc_gzd_dhl_get_products_domestic();

			$settings = array_merge( $settings, array(
				array( 'title' => _x( 'Products and Participation Numbers', 'dhl', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'dhl_product_options' ),

				array(
					'title'             => $domestic['V01PAK'],
					'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Please enter your participation number to the corresponding product. You can add other participation numbers later %s.', 'dhl', 'woocommerce-germanized' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=germanized-dhl' ) . '" target = "_blank">' . _x(  'here', 'dhl', 'woocommerce-germanized' ) .'</a>' ) . '</div>',
					'type'              => 'text',
					'default'           => '01',
					'placeholder'       => '01',
					'id'                => 'woocommerce_gzd_dhl_participation_V01PAK',
					'custom_attributes'	=> array( 'maxlength' => '2' ),
				),

				array( 'type' => 'sectionend', 'id' => 'dhl_product_options' ),
			) );
		}

		return $settings;
	}

	protected static function get_general_settings() {
		$dhl_products = array();

		foreach( ( wc_gzd_dhl_get_products_domestic() + wc_gzd_dhl_get_products_international() ) as $product => $title ) {
			$dhl_products[] = array(
				'title'             => $title,
				'type'              => 'text',
				'default'           => '',
				'id'                => 'woocommerce_gzd_dhl_participation_' . $product,
				'custom_attributes'	=> array( 'maxlength' => '2' ),
			);
		}

		$dhl_products[] = array(
			'title'             => _x( 'Inlay Returns', 'dhl', 'woocommerce-germanized' ),
			'type'              => 'text',
			'default'           => '',
			'id'                => 'woocommerce_gzd_dhl_participation_return',
			'custom_attributes'	=> array( 'maxlength' => '2' ),
		);

		$settings = self::get_setup_settings( true );

		$settings = array_merge( $settings, array(
			array( 'title' => _x( 'Products and Participation Numbers', 'dhl', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'dhl_product_options', 'desc' => sprintf( _x(  'For each DHL product that you would like to use, please enter your participation number here. The participation number consists of the last two characters of the respective accounting number, which you will find in your %s (e.g.: 01).', 'dhl', 'woocommerce-germanized' ), '<a href="' . Package::get_geschaeftskunden_portal_url() . '" target="_blank">' . _x(  'contract data', 'dhl', 'woocommerce-germanized' ) . '</a>' ) ),
		) );

		$settings = array_merge( $settings, $dhl_products );

		$settings = array_merge( $settings, array(
			array( 'type' => 'sectionend', 'id' => 'dhl_product_options' ),
		) );

		return $settings;
	}

	protected static function get_default_bank_account_data( $data_key = '' ) {
		$bacs = get_option( 'woocommerce_bacs_accounts' );

		if ( ! empty( $bacs ) && is_array( $bacs ) ) {
			$data = $bacs[0];

			if ( isset( $data[ 'account_' . $data_key ] ) ) {
				return $data[ 'account_' . $data_key ];
			} elseif ( isset( $data[ $data_key ] ) ) {
				return $data[ $data_key ];
			}
		}

		return '';
	}

	protected static function get_store_address_country() {
		$default = get_option( 'woocommerce_store_country' );

		return in_array( $default, Package::get_available_countries() ) ? $default : 'DE';
	}

	protected static function get_store_address_street() {
		$store_address = wc_gzd_split_shipment_street( get_option( 'woocommerce_store_address' ) );

		return $store_address['street'];
	}

	protected static function get_store_address_street_number() {
		$store_address = wc_gzd_split_shipment_street( get_option( 'woocommerce_store_address' ) );

		return $store_address['number'];
	}

	public static function get_label_default_settings( $for_shipping_method = false ) {

		$select_dhl_product_dom = wc_gzd_dhl_get_products_domestic();
		$select_dhl_product_int = wc_gzd_dhl_get_products_international();
		$duties                 = wc_gzd_dhl_get_duties();

		$settings = array(
			array(
				'title'             => _x( 'Domestic Default Service', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'select',
				'default'           => 'V01PAK',
				'id'                => 'woocommerce_gzd_dhl_label_default_product_dom',
				'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'Please select your default DHL shipping service for domestic shipments that you want to offer to your customers (you can always change this within each individual shipment afterwards).', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'options'           => $select_dhl_product_dom,
				'class'             => 'wc-enhanced-select',
			),

			array(
				'title'             => _x( 'Int. Default Service', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'select',
				'default'           => 'V55PAK',
				'id'                => 'woocommerce_gzd_dhl_label_default_product_int',
				'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'Please select your default DHL shipping service for cross-border shipments that you want to offer to your customers (you can always change this within each individual shipment afterwards).', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'options'           => $select_dhl_product_int,
				'class'             => 'wc-enhanced-select',
			),

			array(
				'title'             => _x( 'Default Duty', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'select',
				'default'           => 'DDP',
				'id'                => 'woocommerce_gzd_dhl_label_default_duty',
				'desc'              => _x( 'Please select a default duty type.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => true,
				'options'           => $duties,
				'class'             => 'wc-enhanced-select',
			),

			array(
				'title' 	        => _x( 'Codeable', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Generate label only if address can be automatically retrieved DHL.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_label_address_codeable_only',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
				'desc_tip'          => _x( 'Choose this option if you want to make sure that by default labels are only generated for codeable addresses.', 'dhl', 'woocommerce-germanized' ),
			),

			array(
				'title'             => _x( 'Default weight (kg)', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => _x( 'Choose a default shipment weight to be used for labels if no weight has been applied to the shipment.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => true,
				'id' 		        => 'woocommerce_gzd_dhl_label_default_shipment_weight',
				'default'           => '2',
				'css'               => 'max-width: 60px;',
				'class'             => 'wc_input_decimal',
			),

			array(
				'title'             => _x( 'Minimum weight (kg)', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => _x( 'Choose a minimum weight to be used for labels e.g. to prevent low shipment weight errors.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => true,
				'id' 		        => 'woocommerce_gzd_dhl_label_minimum_shipment_weight',
				'default'           => '0.5',
				'css'               => 'max-width: 60px;',
				'class'             => 'wc_input_decimal',
			),
		);

		if ( ! $for_shipping_method ) {
			$settings = array_merge( $settings, array(
				array(
					'title' 	        => _x( 'Street number', 'dhl', 'woocommerce-germanized' ),
					'desc' 		        => _x( 'Force existence of a street number within the first address field during checkout for EU countries.', 'dhl', 'woocommerce-germanized' ),
					'desc_tip'          => _x( 'Enabling this option will force a street number to be provided during checkout within the first address field to prevent missing or wrong data sets.', 'dhl', 'woocommerce-germanized' ),
					'id' 		        => 'woocommerce_gzd_dhl_label_checkout_validate_street_number_address',
					'default'	        => 'no',
					'type' 		        => 'gzd_toggle',
				),
			) );
		}

		if ( Package::base_country_supports( 'returns' ) ) {
			$settings = array_merge( $settings, array(
				array(
					'title' 	        => _x( 'Inlay Returns', 'dhl', 'woocommerce-germanized' ),
					'desc' 		        => _x( 'Additionally create inlay return labels for shipments that support returns.', 'dhl', 'woocommerce-germanized' ),
					'id' 		        => 'woocommerce_gzd_dhl_label_auto_inlay_return_label',
					'default'	        => 'no',
					'type' 		        => 'gzd_toggle',
				),
			) );
		}

		if ( $for_shipping_method ) {
			$settings = self::convert_for_shipping_method( $settings );
		}

		return $settings;
	}

	public static function get_parcel_pickup_type_settings( $for_shipping_method = false ) {
		$settings = array(
			array(
				'title' 	        => _x( 'Packstation', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Enable delivery to Packstation.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => _x( 'Let customers choose a Packstation as delivery address.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_parcel_pickup_packstation_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title' 	        => _x( 'Postoffice', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Enable delivery to Post Offices.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => _x( 'Let customers choose a Post Office as delivery address.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_parcel_pickup_postoffice_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title' 	        => _x( 'Parcel Shop', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Enable delivery to Parcel Shops.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => _x( 'Let customers choose a Parcel Shop as delivery address.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_parcel_pickup_parcelshop_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),
		);

		if ( $for_shipping_method ) {
			$settings = self::convert_for_shipping_method( $settings );
		}

		return $settings;
	}

	public static function get_label_default_services_settings( $for_shipping_method = false ) {

		$settings = array(
			array(
				'title' 	        => _x( 'Visual minimum age', 'dhl', 'woocommerce-germanized' ),
				'id'          		=> 'woocommerce_gzd_dhl_label_visual_min_age',
				'type' 		        => 'select',
				'default'           => '0',
				'options'			=> wc_gzd_dhl_get_visual_min_ages(),
				'desc_tip'          => _x( 'Choose this option if you want to let DHL check your customer\'s age.', 'dhl', 'woocommerce-germanized' ),
			),
			array(
				'title' 	        => _x( 'GoGreen', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Enable the GoGreen Service by default.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_label_service_GoGreen',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),
			array(
				'title' 	        => _x( 'Additional Insurance', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Add an additional insurance to labels.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_label_service_AdditionalInsurance',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),
			array(
				'title' 	        => _x( 'Retail Outlet Routing', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Send undeliverable items to nearest retail outlet instead of immediate return.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_label_service_ParcelOutletRouting',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),
			array(
				'title' 	        => _x( 'No Neighbor', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Do not deliver to neighbors.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_label_service_NoNeighbourDelivery',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),
			array(
				'title' 	        => _x( 'Named person only', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Do only delivery to named person.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_label_service_NamedPersonOnly',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),
			array(
				'title' 	        => _x( 'Bulky Goods', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Deliver as bulky goods.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_label_service_BulkyGoods',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),
			array(
				'title' 	        => _x( 'Age Verification', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Verify ages if shipment contains applicable items.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . sprintf( _x(  'Germanized offers an %s to be enabled for certain products and/or product categories. By checking this option labels for shipments with applicable items will automatically have the visual age check service enabled.', 'dhl', 'woocommerce-germanized' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=germanized-checkboxes&checkbox_id=age_verification' ) . '">' . _x( 'age verification checkbox', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_dhl_label_auto_age_check_sync',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),
			array(
				'title' 	        => _x( 'Premium', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Premium delivery for international shipments.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_label_service_Premium',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),
		);

		if ( $for_shipping_method ) {
			$settings = self::convert_for_shipping_method( $settings );
		}

		return $settings;
	}

	public static function get_automation_settings( $for_shipping_method = false ) {
		$shipment_statuses = array_diff_key( wc_gzd_get_shipment_statuses(), array_fill_keys( array( 'gzd-draft', 'gzd-delivered', 'gzd-returned', 'gzd-requested' ), '' ) );

		$settings = array(
			array(
				'title' 	        => _x( 'Labels', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Automatically create labels for shipments.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_label_auto_enable',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title'             => _x( 'Status', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'select',
				'default'           => 'gzd-processing',
				'id'                => 'woocommerce_gzd_dhl_label_auto_shipment_status',
				'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'Choose a shipment status which should trigger generation of a label.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'options'           => $shipment_statuses,
				'class'             => 'wc-enhanced-select',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_label_auto_enable' => '' )
			),

			array(
				'title' 	        => _x( 'Shipment Status', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Mark shipment as shipped after label has been created successfully.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_label_auto_shipment_status_shipped',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title' 	        => _x( 'Returns', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Automatically create labels for returns.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_label_return_auto_enable',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title'             => _x( 'Status', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'select',
				'default'           => 'gzd-processing',
				'id'                => 'woocommerce_gzd_dhl_label_return_auto_shipment_status',
				'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'Choose a shipment status which should trigger generation of a return label.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'options'           => $shipment_statuses,
				'class'             => 'wc-enhanced-select',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_label_return_auto_enable' => '' )
			)
		);

		if ( $for_shipping_method ) {
			$settings = self::convert_for_shipping_method( $settings );
		}

		return $settings;
	}

	protected static function convert_for_shipping_method( $settings ) {
		$new_settings = array();

		foreach( $settings as $setting ) {
			$new_setting            = array();
			$new_setting['id']      = str_replace( 'woocommerce_gzd_dhl_', 'dhl_', $setting['id'] );
			$new_setting['type']    = str_replace( 'gzd_toggle', 'checkbox', $setting['type'] );
			$new_setting['default'] = Package::get_setting( $new_setting['id'] );

			if ( 'checkbox' === $new_setting['type'] ) {
				$new_setting['label'] = $setting['desc'];
			} elseif ( isset( $setting['desc'] ) ) {
				$new_setting['description'] = $setting['desc'];
			}

			$copy = array( 'options', 'title', 'desc_tip' );

			foreach ( $copy as $cp ) {
				if ( isset( $setting[ $cp ] ) ) {
					$new_setting[ $cp ] = $setting[ $cp ];
				}
			}

			$new_settings[ $new_setting['id'] ] = $new_setting;
		}

		return $new_settings;
	}

	protected static function get_label_settings() {

		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'dhl_label_options', 'desc' => sprintf( _x( 'Adjust options for label creation. Settings may be overridden by more specific %s settings.', 'dhl', 'woocommerce-germanized' ), '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping' ) . '" target="_blank">' . _x(  'shipping method', 'dhl', 'woocommerce-germanized' ) . '</a>' ) ),
		);

		$settings = array_merge( $settings, self::get_label_default_settings() );

		$settings = array_merge( $settings, array(
			array( 'type' => 'sectionend', 'id' => 'dhl_label_options' ),
		) );

		$settings = array_merge( $settings, array(
			array( 'title' => _x( 'Retoure', 'dhl', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'dhl_retoure_options', 'desc' => sprintf( _x(  'Adjust handling of return shipments through the DHL Retoure API. Make sure that your %s contains DHL Retoure Online.', 'dhl', 'woocommerce-germanized' ), '<a href="' . Package::get_geschaeftskunden_portal_url() . '">' . _x(  'contract', 'dhl', 'woocommerce-germanized' ) . '</a>' ) ),
		) );

		$settings = array_merge( $settings, self::get_retoure_settings() );

		$settings = array_merge( $settings, array(
			array( 'type' => 'sectionend', 'id' => 'dhl_retoure_options' ),
		) );

		if ( Package::base_country_supports( 'services' ) ) {

			$settings = array_merge( $settings, array(
				array( 'title' => _x( 'Default Services', 'dhl', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'dhl_label_default_services_options', 'desc' => sprintf( _x(  'Adjust services to be added to your labels by default. Find out more about these %s.', 'dhl', 'woocommerce-germanized' ), '<a href="https://www.dhl.de/de/geschaeftskunden/paket/leistungen-und-services/services/service-loesungen.html" target="_blank">' . _x(  'nationwide services', 'dhl', 'woocommerce-germanized' ) . '</a>' ) ),
			) );

			$settings = array_merge( $settings, self::get_label_default_services_settings() );

			$settings = array_merge( $settings, array(
				array( 'type' => 'sectionend', 'id' => 'dhl_label_default_services_options' ),
			) );

		}

		$settings = array_merge( $settings, array(
			array( 'title' => _x( 'Automation', 'dhl', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'dhl_automation_options', 'desc' => _x(  'Choose whether and under which conditions labels for your shipments shall be requested and generated automatically.', 'dhl', 'woocommerce-germanized' ) ),
		) );

		$settings = array_merge( $settings, self::get_automation_settings() );

		$settings = array_merge( $settings, array(
			array( 'type' => 'sectionend', 'id' => 'dhl_automation_options' ),
		) );

		$settings = array_merge( $settings, array(

			array( 'title' => _x( 'Shipper Address', 'dhl', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'dhl_shipper_address_options' ),

			array(
				'title'             => _x( 'Name', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_shipper_name',
				'default'           => '',
			),

			array(
				'title'             => _x( 'Company', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_shipper_company',
				'default'           => get_bloginfo( 'name' ),
			),

			array(
				'title'             => _x( 'Street', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_shipper_street',
				'default'           => self::get_store_address_street(),
			),

			array(
				'title'             => _x( 'Street Number', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_shipper_street_no',
				'default'           => self::get_store_address_street_number(),
			),

			array(
				'title'             => _x( 'City', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_shipper_city',
				'default'           => get_option( 'woocommerce_store_city' ),
			),

			array(
				'title'             => _x( 'Postcode', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_shipper_postcode',
				'default'           => get_option( 'woocommerce_store_postcode' ),
			),

			array(
				'title'             => _x( 'Country', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'select',
				'class'		        => 'wc-enhanced-select',
				'options'           => Package::get_available_countries(),
				'id' 		        => 'woocommerce_gzd_dhl_shipper_country',
				'default'           => self::get_store_address_country(),
			),

			array(
				'title'             => _x( 'Phone', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_shipper_phone',
				'default'           => '',
			),

			array(
				'title'             => _x( 'Email', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_shipper_email',
				'default'           => get_option( 'admin_email' ),
			),

			array( 'type' => 'sectionend', 'id' => 'dhl_shipper_address_options' ),

			array( 'title' => _x( 'Inlay Return Address', 'dhl', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'dhl_return_address_options' ),

			array(
				'title'             => _x( 'Name', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_return_address_name',
				'default'           => '',
			),

			array(
				'title'             => _x( 'Company', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_return_address_company',
				'default'           => get_bloginfo( 'name' ),
			),

			array(
				'title'             => _x( 'Street', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_return_address_street',
				'default'           => self::get_store_address_street(),
			),

			array(
				'title'             => _x( 'Street Number', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_return_address_street_no',
				'default'           => self::get_store_address_street_number(),
			),

			array(
				'title'             => _x( 'City', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_return_address_city',
				'default'           => get_option( 'woocommerce_store_city' ),
			),

			array(
				'title'             => _x( 'Postcode', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_return_address_postcode',
				'default'           => get_option( 'woocommerce_store_postcode' ),
			),

			array(
				'title'             => _x( 'Country', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'select',
				'class'		        => 'chosen_select',
				'options'           => Package::get_available_countries(),
				'id' 		        => 'woocommerce_gzd_dhl_return_address_country',
				'default'           => self::get_store_address_country(),
			),

			array(
				'title'             => _x( 'Phone', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_return_address_phone',
				'default'           => '',
			),

			array(
				'title'             => _x( 'Email', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_return_address_email',
				'default'           => get_option( 'admin_email' ),
			),

			array( 'type' => 'sectionend', 'id' => 'dhl_return_address_options' ),

			array( 'title' => _x( 'Bank Account', 'dhl', 'woocommerce-germanized' ), 'type' => 'title', 'id' => 'dhl_bank_account_options', 'desc' => _x(  'Enter your bank details needed for services that use COD.', 'dhl', 'woocommerce-germanized' ) ),

			array(
				'title'             => _x( 'Holder', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_bank_holder',
				'default'           => self::get_default_bank_account_data( 'name' ),
			),

			array(
				'title'             => _x( 'Bank Name', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_bank_name',
				'default'           => self::get_default_bank_account_data( 'bank_name' ),
			),

			array(
				'title'             => _x( 'IBAN', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_bank_iban',
				'default'           => self::get_default_bank_account_data( 'iban' ),
			),

			array(
				'title'             => _x( 'BIC', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_bank_bic',
				'default'           => self::get_default_bank_account_data( 'bic' ),
			),

			array(
				'title'             => _x( 'Payment Reference', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_bank_ref',
				'custom_attributes'	=> array( 'maxlength' => '35' ),
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Use these placeholders to add info to the payment reference: %s. This text is limited to 35 characters.', 'dhl', 'woocommerce-germanized' ), '<code>{shipment_id}, {order_id}, {email}</code>' ) . '</div>',
				'default'           => '{shipment_id}'
			),

			array(
				'title'             => _x( 'Payment Reference 2', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_bank_ref_2',
				'custom_attributes'	=> array( 'maxlength' => '35' ),
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'Use these placeholders to add info to the payment reference: %s. This text is limited to 35 characters.', 'dhl', 'woocommerce-germanized' ), '<code>{shipment_id}, {order_id}, {email}</code>' ) . '</div>',
				'default'           => '{email}'
			),

			array( 'type' => 'sectionend', 'id' => 'dhl_bank_account_options' ),
		) );

		return $settings;
	}

	public static function get_retoure_settings( $for_shipping_method = false ) {
		$settings = array(
			array(
				'title' 	        => _x( 'Retoure', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Enable creating labels for return shipments.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x(  'By enabling this option you might generate retoure labels for return shipments and send them to your customer via email.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_dhl_label_retoure_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),
			array(
				'type' => 'dhl_receiver_ids',
			),
		);

		if ( $for_shipping_method ) {
			$settings = self::convert_for_shipping_method( $settings );
		}

		return $settings;
	}

	public static function get_preferred_services_settings( $for_shipping_method = false ) {
		$settings = array(
			array(
				'title' 	        => _x( 'Preferred Day', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Enable preferred day delivery.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x(  'Enabling this option will display options for the user to select their preferred day of delivery during the checkout.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_dhl_PreferredDay_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title'             => _x( 'Fee', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'desc'              => _x( 'Insert gross value as surcharge for preferred day delivery. Insert 0 to offer service for free.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => true,
				'id' 		        => 'woocommerce_gzd_dhl_PreferredDay_cost',
				'default'           => '1.2',
				'css'               => 'max-width: 60px;',
				'class'             => 'wc_input_decimal',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_PreferredDay_enable' => '' )
			),

			array(
				'title' 	        => _x( 'Preferred Location', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Enable preferred location delivery.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x(  'Enabling this option will display options for the user to select their preferred delivery location during the checkout.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_dhl_PreferredLocation_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title' 	        => _x( 'Preferred Neighbor', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Enable preferred neighbor delivery.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x(  'Enabling this option will display options for the user to deliver to their preferred neighbor during the checkout.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_dhl_PreferredNeighbour_enable',
				'default'	        => 'yes',
				'type' 		        => 'gzd_toggle',
			),
		);

		if ( $for_shipping_method ) {
			$settings = self::convert_for_shipping_method( $settings );
		}

		return $settings;
	}

	protected static function get_service_settings() {
		$wc_payment_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		$wc_gateway_titles   = wp_list_pluck( $wc_payment_gateways, 'method_title', 'id' );
		$settings            = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'dhl_preferred_options' ),
		);

		$settings = array_merge( $settings, self::get_preferred_services_settings() );

		$settings = array_merge( $settings, array(

			array(
				'title'             => _x( 'Cut-off time', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'time',
				'id'                => 'woocommerce_gzd_dhl_PreferredDay_cutoff_time',
				'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'The cut-off time is the latest possible order time up to which the minimum preferred day (day of order + 2 working days) can be guaranteed. As soon as the time is exceeded, the earliest preferred day displayed in the frontend will be shifted to one day later (day of order + 3 working days).', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'default'           => '12:00',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_PreferredDay_enable' => '' )
			),

			array(
				'title'             => _x( 'Preparation days', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'number',
				'id'                => 'woocommerce_gzd_dhl_PreferredDay_preparation_days',
				'desc'              => '<div class="wc-gzd-additional-desc">' . _x( 'If you need more time to prepare your shipments you might want to add a static preparation time to the possible starting date for preferred day delivery.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'default'           => '0',
				'css'               => 'max-width: 60px',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_PreferredDay_enable' => '', 'min' => 0, 'max' => 3 )
			),

			array(
				'title' 	        => _x( 'Exclude days of transfer', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Monday', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => _x( 'Exclude days from transferring shipments to DHL.', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_PreferredDay_exclusion_mon',
				'type' 		        => 'gzd_toggle',
				'default'	        => 'no',
				'checkboxgroup'	    => 'start',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_PreferredDay_enable' => '' )
			),

			array(
				'desc' 		        => _x( 'Tuesday', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_PreferredDay_exclusion_tue',
				'type' 		        => 'gzd_toggle',
				'default'	        => 'no',
				'checkboxgroup'	    => '',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_PreferredDay_enable' => '' )
			),

			array(
				'desc' 		        => _x( 'Wednesday', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_PreferredDay_exclusion_wed',
				'type' 		        => 'gzd_toggle',
				'default'	        => 'no',
				'checkboxgroup'	    => '',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_PreferredDay_enable' => '' )
			),

			array(
				'desc' 		        => _x( 'Thursday', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_PreferredDay_exclusion_thu',
				'type' 		        => 'gzd_toggle',
				'default'	        => 'no',
				'checkboxgroup'	    => '',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_PreferredDay_enable' => '' )
			),

			array(
				'desc' 		        => _x( 'Friday', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_PreferredDay_exclusion_fri',
				'type' 		        => 'gzd_toggle',
				'default'	        => 'no',
				'checkboxgroup'	    => '',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_PreferredDay_enable' => '' )
			),

			array(
				'desc' 		        => _x( 'Saturday', 'dhl', 'woocommerce-germanized' ),
				'id' 		        => 'woocommerce_gzd_dhl_PreferredDay_exclusion_sat',
				'type' 		        => 'gzd_toggle',
				'default'	        => 'no',
				'checkboxgroup'	    => 'end',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_PreferredDay_enable' => '' )
			),

			array(
				'title'             => _x( 'Exclude gateways', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'multiselect',
				'desc'              => _x( 'Select payment gateways to be excluded from showing preferred services.', 'dhl', 'woocommerce-germanized' ),
				'desc_tip'          => true,
				'id'                => 'woocommerce_gzd_dhl_preferred_payment_gateways_excluded',
				'options'           => $wc_gateway_titles,
				'class'             => 'wc-enhanced-select',
			),

			array( 'type' => 'sectionend', 'id' => 'dhl_preferred_options' ),
		) );

		return $settings;
	}

	protected static function get_pickup_settings() {

		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'dhl_pickup_options' ),
		);

		$settings = array_merge( $settings, self::get_parcel_pickup_type_settings() );

		$settings = array_merge( $settings, array(

			array(
				'title' 	        => _x( 'Map', 'dhl', 'woocommerce-germanized' ),
				'desc' 		        => _x( 'Let customers find a DHL location on a map.', 'dhl', 'woocommerce-germanized' ) . '<div class="wc-gzd-additional-desc">' . _x(  'Enable this option to let your customers choose a pickup option from a map within the checkout. If this option is disabled a link to the DHL website is placed instead.', 'dhl', 'woocommerce-germanized' ) . '</div>',
				'id' 		        => 'woocommerce_gzd_dhl_parcel_pickup_map_enable',
				'default'	        => 'no',
				'type' 		        => 'gzd_toggle',
			),

			array(
				'title'             => _x( 'Google Maps Key', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'text',
				'id' 		        => 'woocommerce_gzd_dhl_parcel_pickup_map_api_key',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_parcel_pickup_map_enable' => '' ),
				'desc'              => '<div class="wc-gzd-additional-desc">' . sprintf( _x( 'To integrate a map within your checkout you\'ll need a valid API key for Google Maps. You may %s.', 'dhl', 'woocommerce-germanized' ), '<a href="" target="_blank">' . _x(  'retrieve a new one', 'dhl', 'woocommerce-germanized' ) . '</a>' ) . '</div>',
				'default'           => ''
			),

			array(
				'title'             => _x( 'Limit results', 'dhl', 'woocommerce-germanized' ),
				'type'              => 'number',
				'id' 		        => 'woocommerce_gzd_dhl_parcel_pickup_map_max_results',
				'custom_attributes'	=> array( 'data-show_if_woocommerce_gzd_dhl_parcel_pickup_map_enable' => '' ),
				'desc_tip'          => _x( 'Limit the number of DHL locations shown on the map', 'dhl', 'woocommerce-germanized' ),
				'default'           => 20,
				'css'               => 'max-width: 60px;',
			),

			array( 'type' => 'sectionend', 'id' => 'dhl_pickup_options' ),
		) );

		return $settings;
	}

	public static function get_settings( $current_section = '' ) {
		$settings = array();

		if ( '' === $current_section ) {
			$settings = self::get_general_settings();
		} elseif( 'labels' === $current_section ) {
			$settings = self::get_label_settings();
		} elseif( 'services' === $current_section && Package::base_country_supports( 'services' ) ) {
			$settings = self::get_service_settings();
		} elseif( 'pickup' === $current_section && Package::base_country_supports( 'pickup' ) ) {
			$settings = self::get_pickup_settings();
		}

		return $settings;
	}

	public static function get_sections() {
		$sections = array(
			''          => _x( 'General', 'dhl', 'woocommerce-germanized' ),
			'labels'    => _x( 'Labels', 'dhl', 'woocommerce-germanized' ),
			'services'  => _x( 'Preferred Services', 'dhl', 'woocommerce-germanized' ),
			'pickup'    => _x( 'Parcel Pickup', 'dhl', 'woocommerce-germanized' ),
		);

		if ( ! Package::base_country_supports( 'services' ) ) {
			unset( $sections['services'] );
		}

		if ( ! Package::base_country_supports( 'pickup' ) ) {
			unset( $sections['pickup'] );
		}

		return $sections;
	}
}
