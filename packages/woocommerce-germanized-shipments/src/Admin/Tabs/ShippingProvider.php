<?php

namespace Vendidero\Germanized\Shipments\Admin\Tabs;

use Vendidero\Germanized\Shipments\Admin\Settings;
use Vendidero\Germanized\Shipments\Admin\Tutorial;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\ShippingProvider\Helper;
use Vendidero\Germanized\Shipments\ShippingProvider\Simple;

class ShippingProvider extends Tab {

	public function get_label() {
		return _x( 'Shipping Provider', 'shipments', 'woocommerce-germanized' );
	}

	public function get_name() {
		return 'shipping_provider';
	}

	public function get_description() {
		$desc = '';

		if ( $provider = $this->get_current_provider() ) {
			$desc = $provider->get_description( 'edit' );
		} elseif ( empty( $_GET['provider'] ) ) { /* phpcs:disable WordPress.Security.NonceVerification */
			$provider_available = Helper::instance()->get_available_shipping_provider_integrations();
			$desc               = _x( 'Manage your shipping provider integrations.', 'shipments', 'woocommerce-germanized' );

			if ( ! empty( $provider_available ) ) {
				$provider_name_list = array();

				foreach ( $provider_available as $provider ) {
					$provider_name_list[] = $provider->get_title();
				}

				$provider_list = implode( ', ', $provider_name_list );
				$pos           = strrpos( $provider_list, ', ' );

				if ( false !== $pos ) {
					$provider_list = substr_replace( $provider_list, ' & ', $pos, strlen( ', ' ) );
				}

				$desc = sprintf( _x( 'Manage your shipping provider integrations, e.g. for %s.', 'shipments', 'woocommerce-germanized' ), trim( $provider_list ) );
			}
		}

		return $desc;
	}

	protected function get_breadcrumb() {
		$breadcrumb = Settings::get_main_breadcrumb();
		$provider   = $this->get_current_provider();

		$breadcrumb[] = array(
			'class' => 'tab',
			'href'  => $provider ? $this->get_url() : '',
			'title' => ! $provider ? $this->get_breadcrumb_label( _x( 'Shipping Provider', 'shipments', 'woocommerce-germanized' ) ) : _x( 'Shipping Provider', 'shipments', 'woocommerce-germanized' ),
		);

		if ( $provider ) {
			$breadcrumb[] = array(
				'class' => 'section',
				'href'  => ! empty( $current_section ) ? $provider->get_edit_link() : '',
				'title' => ( $provider->get_id() <= 0 && '' === $provider->get_title() ) ? $this->get_breadcrumb_label( _x( 'New', 'shipments-shipping-provider', 'woocommerce-germanized' ) ) : $this->get_breadcrumb_label( $provider->get_title() ),
			);
		}

		if ( ! empty( $current_section ) ) {
			$breadcrumb[] = array(
				'class' => 'section',
				'href'  => '',
				'title' => $this->get_section_title( $current_section ),
			);
		}

		return $breadcrumb;
	}

	protected function get_breadcrumb_label( $label ) {
		$current_section = $this->get_current_section();
		$help_link       = $this->get_help_link();
		$provider        = $this->get_current_provider();

		if ( $provider && empty( $current_section ) ) {
			if ( ! empty( $help_link ) ) {
				$label = $label . '<a class="page-title-action" href="' . esc_url( $this->get_help_link() ) . '" target="_blank">' . _x( 'Learn more', 'shipments', 'woocommerce-germanized' ) . '</a>';
			}

			if ( ! empty( $provider->get_signup_link() ) ) {
				$label = $label . '<a class="page-title-action" href="' . esc_url( $provider->get_signup_link() ) . '" target="_blank">' . _x( 'Not yet a customer?', 'shipments', 'woocommerce-germanized' ) . '</a>';
			}

			if ( is_a( $provider, 'Vendidero\Germanized\Shipments\ShippingProvider\Auto' ) ) {
				$connection_test_result = $provider->test_connection();

				if ( $provider->is_activated() && null !== $connection_test_result ) {
					$is_error      = is_wp_error( $connection_test_result ) || false === $connection_test_result;
					$error_message = is_wp_error( $connection_test_result ) ? $connection_test_result->get_error_message() : '';
					$error_message = empty( $error_message ) ? _x( 'Not connected', 'shipments', 'woocommerce-germanized' ) : $error_message;

					$label = $label . '<span class="page-title-action wc-gzd-shipment-api-connection-status ' . ( $is_error ? 'connection-status-error help_tip' : 'connection-status-success' ) . '" data-tip="' . esc_html( $is_error ? $error_message : '' ) . '">' . esc_html( $is_error ? _x( 'Status: Not Connected', 'shipments', 'woocommerce-germanized' ) : _x( 'Status: Connected', 'shipments', 'woocommerce-germanized' ) ) . '</span>';
				}
			}
		} elseif ( ! $provider ) {
			$label = $label . '<a href="' . esc_url( add_query_arg( array( 'provider' => 'new' ), $this->get_url() ) ) . '" class="page-title-action">' . _x( 'Add provider', 'shipments', 'woocommerce-germanized' ) . '</a>';

			if ( ! empty( $help_link ) ) {
				$label = $label . '<a class="page-title-action" href="' . esc_url( $this->get_help_link() ) . '" target="_blank">' . _x( 'Learn more', 'shipments', 'woocommerce-germanized' ) . '</a>';
			}
		}

		return $label;
	}

	public function get_pointers() {
		$pointers = array();

		if ( $provider = $this->get_current_provider() ) {
			if ( is_a( $provider, '\Vendidero\Germanized\Shipments\ShippingProvider\Auto' ) ) {
				$pointers = $provider->get_settings_help_pointers( $this->get_current_section() );
			}
		} else {
			$pointers = array(
				'pointers' => array(
					'provider' => array(
						'target'       => '.wc-gzd-shipments-setting-tab-rows tr:first-child .wc-gzd-shipping-provider-title a.wc-gzd-shipping-provider-edit-link',
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
						'target'       => '.wc-gzd-shipments-setting-tab-rows tr:first-child .wc-gzd-shipping-provider-activated .woocommerce-gzd-shipments-input-toggle-trigger',
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
						'target'       => 'ul.wc-gzd-shipments-settings-breadcrumb .breadcrumb-item-active a.page-title-action:first',
						'next'         => '',
						'next_url'     => $this->get_next_pointers_link(),
						'next_trigger' => array(),
						'options'      => array(
							'content'  => '<h3>' . esc_html_x( 'Add new', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'You may want to manually add a new shipping provider in case a built-in integration is not available.', 'shipments', 'woocommerce-germanized' ) . '</p>',
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

	public function get_next_pointers_link( $provider_name = false ) {
		$providers        = wc_gzd_get_shipping_providers();
		$next_url         = Tutorial::get_tutorial_url( 'packaging' );
		$provider_indexes = array();
		$provider_counts  = array();
		$count            = 0;

		foreach ( $providers as $provider_key => $provider ) {
			if ( is_a( $provider, '\Vendidero\Germanized\Shipments\ShippingProvider\Auto' ) && ! empty( $provider->get_settings_help_pointers() ) ) {
				$provider_indexes[ $provider_key ] = $count;
				$provider_counts[ $count ]         = $provider_key;
				++$count;
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

	public function get_help_link() {
		if ( $provider = $this->get_current_provider() ) {
			return $provider->get_help_link();
		} else {
			return 'https://vendidero.de/dokument/versanddienstleister-verwalten';
		}
	}

	public function get_sections() {
		if ( $provider = $this->get_current_provider() ) {
			return $provider->get_setting_sections();
		} else {
			return array();
		}
	}

	public function get_current_provider() {
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

	public function save() {
		global $current_section;

		if ( $provider = $this->get_current_provider() ) {
			$is_new = $provider->get_id() <= 0 ? true : false;

			$provider->update_settings( $current_section, null, false );

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
				$url = add_query_arg( array( 'provider' => $provider->get_name() ), $this->get_url() );
				wp_safe_redirect( $url );
			}
		}
	}

	protected function get_section_url( $section_id ) {
		$section_url = parent::get_section_url( $section_id );

		if ( $provider = $this->get_current_provider() ) {
			$section_url = esc_url_raw( add_query_arg( array( 'provider' => $provider->get_name() ), $section_url ) );
		}

		return $section_url;
	}

	public function output() {
		$current_section = $this->get_current_section();

		if ( '' === $current_section && empty( $_GET['provider'] ) ) {
			global $hide_save_button;

			$hide_save_button = true;
			$helper           = Helper::instance();
			$providers        = $helper->get_shipping_providers();
			$integrations     = $helper->get_available_shipping_provider_integrations( true );

			foreach ( $integrations as $integration ) {
				$providers[ $integration->get_name() ] = $integration;
			}

			$providers = apply_filters( 'woocommerce_gzd_shipment_admin_provider_list', $providers );

			include_once Package::get_path() . '/includes/admin/views/html-settings-provider-list.php';
		} else {
			parent::output();
		}
	}

	public function get_tab_settings( $current_section = '' ) {
		if ( $provider = $this->get_current_provider() ) {
			return $provider->get_settings( $current_section );
		} else {
			return array();
		}
	}
}
