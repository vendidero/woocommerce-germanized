<?php

namespace Vendidero\Germanized\Shipments\Admin;

class Tutorial {

	/**
	 * Setup pointers for screen.
	 *
	 * @param string $tab_name
	 */
	public static function setup_pointers_for_settings( $tab_name ) {
		if ( 'shipments' === $tab_name ) {
			self::general_tutorial();
		} elseif ( $tab = Settings::get_tab( $tab_name ) ) {
			$pointers = $tab->get_pointers();

			if ( ! empty( $pointers ) ) {
				self::enqueue_pointers( $pointers );
			}
		}
	}

	public static function get_tutorial_url( $tab, $section = '' ) {
		$tutorial_url = add_query_arg( array( 'tutorial' => 'yes' ), Settings::get_settings_url( $tab, $section ) );

		return $tutorial_url;
	}

	public static function get_last_tutorial_url() {
		return apply_filters( 'woocommerce_gzd_shipments_last_tutorial_url', Settings::get_settings_url() );
	}

	protected static function general_tutorial() {
		$pointers = array(
			'pointers' => array(
				'tab' => array(
					'target'       => '#wc-gzd-shipments-setting-tab-name-general .wc-gzd-shipments-setting-tab-link',
					'next'         => 'enabled',
					'next_url'     => self::get_tutorial_url( 'general' ),
					'next_trigger' => array(),
					'options'      => array(
						'content'  => '<h3>' . esc_html_x( 'Setting tabs', 'shipments', 'woocommerce-germanized' ) . '</h3><p>' . esc_html_x( 'Click on a tab to adjust the settings related to a specific section.', 'shipments', 'woocommerce-germanized' ) . '</p>',
						'position' => array(
							'edge'  => 'top',
							'align' => 'left',
						),
					),
				),
			),
		);

		self::enqueue_pointers( $pointers );
	}

	/**
	 * Enqueue pointers and add script to page.
	 *
	 * @param array $pointers
	 */
	protected static function enqueue_pointers( $pointers ) {
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
							var close   = '" . esc_js( _x( 'Dismiss', 'shipments', 'woocommerce-germanized' ) ) . "',
								next    = '" . esc_js( _x( 'Next', 'shipments', 'woocommerce-germanized' ) ) . "',
								button  = $( '<a class=\"close\" href=\"#\">' + close + '</a>' ),
								button2 = $( '<a class=\"button button-primary\" href=\"#\">' + next + '</a>' ),
								wrapper = $( '<div class=\"wc-pointer-buttons\" />' ),
								nextUrl = '';
								
							if ( pointer.hasOwnProperty( 'last_step' ) && pointer.last_step ) {
								next    = '" . esc_js( _x( 'Let\'s go', 'shipments', 'woocommerce-germanized' ) ) . "';
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
