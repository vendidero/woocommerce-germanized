<?php

namespace Vendidero\Germanized\Shipments\Admin;

use Exception;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\ShippingProvider\Helper;
use Vendidero\Germanized\Shipments\ShippingProvider\Simple;
use WC_Admin_Settings;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin class.
 */
class ProviderSettings {

	public static function get_current_provider() {
		$provider = false;

		if ( isset( $_REQUEST['provider'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$provider_name = wc_clean( wp_unslash( $_REQUEST['provider'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$helper        = Helper::instance();

			$helper->get_shipping_providers();

			if ( ! empty( $provider_name ) && 'new' !== $provider_name ) {
				$provider = $helper->get_shipping_provider( $provider_name );
			} else {
				$provider = new Simple();
			}
		}

		return $provider;
	}

	public static function get_help_link() {
		if ( $provider = self::get_current_provider() ) {
			return $provider->get_help_link();
		} else {
			return 'https://vendidero.de/dokument/versanddienstleister-verwalten';
		}
	}

	public static function get_next_pointers_link( $provider_name = false ) {
		$providers        = wc_gzd_get_shipping_providers();
		$next_url         = admin_url( 'admin.php?page=wc-settings&tab=germanized-emails&tutorial=yes' );
		$provider_indexes = array();
		$provider_counts  = array();
		$count            = 0;

		foreach ( $providers as $provider_key => $provider ) {
			if ( is_a( $provider, '\Vendidero\Germanized\Shipments\ShippingProvider\Auto' ) && ! empty( $provider->get_settings_help_pointers() ) ) {
				$provider_indexes[ $provider_key ] = $count;
				$provider_counts[ $count ]         = $provider_key;
				$count++;
			}
		}

		$next_index = isset( $provider_indexes[ $provider_name ] ) ? $provider_indexes[ $provider_name ] + 1 : -1;

		// By default use the first provider
		if ( ! $provider_name ) {
			$next_index = 0;
		}

		if ( isset( $provider_counts[ $next_index ] ) ) {
			$next_provider = $providers[ $provider_counts[ $next_index ] ];
			$next_url      = add_query_arg( array( 'tutorial' => 'yes' ), $next_provider->get_edit_link() );
		}

		return $next_url;
	}

	public static function get_pointers( $section ) {
		$pointers = array();

		if ( $provider = self::get_current_provider() ) {
			if ( is_a( $provider, '\Vendidero\Germanized\Shipments\ShippingProvider\Auto' ) ) {
				$pointers = $provider->get_settings_help_pointers( $section );
			}
		} else {
			$pointers = array(
				'pointers' => array(
					'provider' => array(
						'target'       => '.wc-gzd-setting-tab-rows tr:first-child .wc-gzd-shipping-provider-title a.wc-gzd-shipping-provider-edit-link',
						'next'         => 'activate',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Shipping Provider', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'You may find all the available shipping providers as a list here. Click on the link to edit the provider-specific settings.', 'shipments', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'left',
								'align' => 'left',
							),
						),
					),
					'activate' => array(
						'target'       => '.wc-gzd-setting-tab-rows tr:first-child .wc-gzd-shipping-provider-activated .woocommerce-gzd-input-toggle-trigger',
						'next'         => 'new',
						'next_url'     => '',
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Activate', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Activate or deactivate a shipping provider by toggling this button.', 'shipments', 'woocommerce-germanized' ) . '</p>',
							'position' => array(
								'edge'  => 'right',
								'align' => 'left',
							),
						),
					),
					'new'      => array(
						'target'       => 'ul.wc-gzd-settings-breadcrumb .breadcrumb-item-active a.page-title-action:first',
						'next'         => '',
						'next_url'     => self::get_next_pointers_link(),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Add new', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'You may want to manually add a new shipping provider in case an automatic integration does not exist.', 'shipments', 'woocommerce-germanized' ) . '</p>',
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

	public static function get_description() {
		if ( $provider = self::get_current_provider() ) {
			return $provider->get_description( 'edit' );
		}

		return '';
	}

	public static function get_breadcrumb( $current_section = '' ) {
		$provider = self::get_current_provider();

		$breadcrumb[] = array(
			'class' => 'tab',
			'href'  => $provider ? admin_url( 'admin.php?page=wc-settings&tab=germanized-shipping_provider' ) : '',
			'title' => ! $provider ? self::get_breadcrumb_label( _x( 'Shipping Provider', 'shipments', 'woocommerce-germanized' ) ) : _x( 'Shipping Provider', 'shipments', 'woocommerce-germanized' ),
		);

		if ( $provider = self::get_current_provider() ) {
			$breadcrumb[] = array(
				'class' => 'section',
				'href'  => ! empty( $current_section ) ? $provider->get_edit_link() : '',
				'title' => ( $provider->get_id() <= 0 && '' === $provider->get_title() ) ? self::get_breadcrumb_label( _x( 'New', 'shipments-shipping-provider', 'woocommerce-germanized' ), $current_section ) : self::get_breadcrumb_label( $provider->get_title(), $current_section ),
			);
		}

		if ( ! empty( $current_section ) ) {
			$breadcrumb[] = array(
				'class' => 'section',
				'href'  => '',
				'title' => self::get_section_title( $current_section ),
			);
		}

		return $breadcrumb;
	}

	protected static function get_section_title( $section = '' ) {
		$sections      = self::get_sections();
		$section_label = isset( $sections[ $section ] ) ? $sections[ $section ] : '';

		return $section_label;
	}

	protected static function get_breadcrumb_label( $label, $current_section = '' ) {
		$help_link = self::get_help_link();
		$provider  = self::get_current_provider();

		if ( $provider && empty( $current_section ) ) {
			if ( ! empty( $help_link ) ) {
				$label = $label . '<a class="page-title-action" href="' . esc_url( self::get_help_link() ) . '" target="_blank">' . _x( 'Learn more', 'shipments', 'woocommerce-germanized' ) . '</a>';
			}

			if ( ! empty( $provider->get_signup_link() ) ) {
				$label = $label . '<a class="page-title-action" href="' . esc_url( $provider->get_signup_link() ) . '" target="_blank">' . _x( 'Not yet a customer?', 'shipments', 'woocommerce-germanized' ) . '</a>';
			}
		} elseif ( ! $provider ) {
			$label = $label . '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=germanized-shipping_provider&provider=new' ) ) . '" class="page-title-action">' . _x( 'Add provider', 'shipments', 'woocommerce-germanized' ) . '</a>';

			if ( ! empty( $help_link ) ) {
				$label = $label . '<a class="page-title-action" href="' . esc_url( self::get_help_link() ) . '" target="_blank">' . _x( 'Learn more', 'shipments', 'woocommerce-germanized' ) . '</a>';
			}
		}

		return $label;
	}

	public static function save( $section = '' ) {
		if ( $provider = self::get_current_provider() ) {
			$is_new = $provider->get_id() <= 0 ? true : false;

			$provider->update_settings( $section, null, false );

			if ( $is_new ) {
				if ( empty( $provider->get_tracking_desc_placeholder( 'edit' ) ) ) {
					$provider->set_tracking_desc_placeholder( $provider->get_default_tracking_desc_placeholder() );
				}

				if ( empty( $provider->get_tracking_url_placeholder( 'edit' ) ) ) {
					$provider->set_tracking_url_placeholder( $provider->get_default_tracking_url_placeholder() );
				}
			}

			if ( isset( $_GET['provider'] ) && 'new' === $_GET['provider'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				add_filter( 'woocommerce_gzd_shipments_shipping_provider_is_manual_creation_request', '__return_true', 15 );
			}

			$provider->save();

			if ( isset( $_GET['provider'] ) && 'new' === $_GET['provider'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				remove_filter( 'woocommerce_gzd_shipments_shipping_provider_is_manual_creation_request', '__return_true', 15 );
			}

			if ( $is_new ) {
				$url = admin_url( 'admin.php?page=wc-settings&tab=germanized-shipping_provider&provider=' . $provider->get_name() );
				wp_safe_redirect( $url );
			}
		}
	}

	public static function get_settings( $current_section = '' ) {
		if ( $provider = self::get_current_provider() ) {
			return $provider->get_settings( $current_section );
		} else {
			return array();
		}
	}

	public static function output_providers() {
		global $hide_save_button;

		$hide_save_button = true;
		self::provider_screen();
	}

	protected static function provider_screen() {
		$helper    = Helper::instance();
		$providers = $helper->get_shipping_providers();
		$providers = apply_filters( 'woocommerce_gzd_shipment_admin_provider_list', $providers );

		include_once Package::get_path() . '/includes/admin/views/html-settings-provider-list.php';
	}

	public static function get_sections() {
		if ( $provider = self::get_current_provider() ) {
			return $provider->get_setting_sections();
		} else {
			return array();
		}
	}
}
