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
			$tab_clean = str_replace( 'germanized-', '', $tab );
			$pages     = WC_Admin_Settings::get_settings_pages();
			$settings  = $settings = $this->get_settings();

			if ( 'germanized' === $tab ) {
				$this->general_tutorial();
			} elseif ( $settings && ( $tab = $settings->get_tab_by_name( $tab_clean ) ) ) {
				$pointers = $tab->get_pointers();

				if ( ! empty( $pointers ) ) {
					$this->enqueue_pointers( $pointers );
				}
			}
		}
	}

	/**
	 * Pointers for creating a product.
	 */
	public function general_tutorial() {
		// These pointers will chain - they will not be shown at once.
		$pointers = array(
			'pointers' => array(
				'tab'     => array(
					'target'       => '#wc-gzd-setting-tab-name-general .wc-gzd-setting-tab-link',
					'next'         => 'enabled',
					'next_url'     => '',
					'next_trigger' => array(),
					'options'      => array(
						'content'  => '<h3>' . esc_html__( 'Setting tabs', 'woocommerce-germanized' ) . '</h3><p>' . esc_html__( 'To make it more comfortable for you, we\'ve splitted the settings into multiple tabs.', 'woocommerce-germanized' ) . '</p>',
						'position' => array(
							'edge'  => 'top',
							'align' => 'left',
						),
					),
				),
				'enabled' => array(
					'target'       => '#wc-gzd-setting-tab-enabled-double_opt_in .woocommerce-input-toggle',
					'next'         => '',
					'next_url'     => admin_url( 'admin.php?page=wc-settings&tab=germanized-general&tutorial=yes' ),
					'next_trigger' => array(),
					'options'      => array(
						'content'  => '<h3>' . esc_html__( 'Status', 'woocommerce-germanized' ) . '</h3><p>' . esc_html__( 'Some features can be switched on or off explicitly by toggling the input.', 'woocommerce-germanized' ) . '</p>',
						'position' => array(
							'edge'  => 'bottom',
							'align' => 'left',
						),
					),
				),
			),
		);

		$this->enqueue_pointers( $pointers );
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
		wc_enqueue_js(
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
							t.pointer.get(0).scrollIntoView( { behavior: 'smooth' } );
						},
						buttons: function( event, t ) {
							var close   = '" . esc_js( __( 'Dismiss', 'woocommerce-germanized' ) ) . "',
								next    = '" . esc_js( __( 'Next', 'woocommerce-germanized' ) ) . "',
								button  = $( '<a class=\"close\" href=\"#\">' + close + '</a>' ),
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
							
							button.bind( 'click.pointer', function(e) {
								e.preventDefault();
								t.element.pointer('destroy');
							});

							button2.bind( 'click.pointer', function(e) {
								e.preventDefault();
								t.element.pointer('close');
							});
							
							wrapper.append( button ); 
									
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
