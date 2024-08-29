window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.shipping_providers = {

        params: {},
        $wrapper: '',

        init: function() {
            var self      = germanized.admin.shipping_providers;
            self.params   = wc_gzd_admin_shipping_providers_params;
            self.$wrapper = $( '.wc-gzd-shipping-providers' );

            $( document )
                .on( 'click', '.wc-gzd-shipping-provider-delete', self.onRemoveProvider )
                .on( 'change', '.wc-gzd-shipping-providers input.wc-gzd-shipping-provider-activated-checkbox', this.onChangeProviderStatus );

            // Use load event to prevent firing during initial (after ready) phase
            $( window ).on( "load", function() {
                $( document ).on( 'updateMoveButtons', 'table.wc-gzd-shipping-providers', self.onChangeSort );
            });

            // Sorting
            $( 'table.wc-gzd-shipping-providers tbody' ).sortable({
                items: 'tr',
                cursor: 'move',
                axis: 'y',
                handle: 'td.sort',
                scrollSensitivity: 40,
                helper: function ( event, ui ) {
                    ui.children().each( function () {
                        $( this ).width( $( this ).width() );
                    } );
                    ui.css( 'left', '0' );
                    return ui;
                },
                start: function ( event, ui ) {
                    ui.item.css( 'background-color', '#f6f6f6' );
                },
                stop: function ( event, ui ) {
                    ui.item.removeAttr( 'style' );
                    ui.item.trigger( 'updateMoveButtons' );
                },
            } );
        },

        onChangeSort: function() {
            var sort = $( this ).find( 'input[name="provider_order[]"]' ).map( function () {
                return $( this ).val();
            }).get();

            var self = germanized.admin.shipping_providers,
                params = {
                    'action'  : 'woocommerce_gzd_sort_shipping_provider',
                    'order'   : sort,
                    'security': self.getParams().sort_shipping_provider_nonce
                };

            self.doAjax( params );
        },

        onChangeProviderStatus: function() {
            var self      = germanized.admin.shipping_providers,
                $checkbox = $( this ),
                provider  = self.getProviderName( $checkbox ),
                $toggle   = $checkbox.parents( 'td' ).find( '.woocommerce-gzd-input-toggle' ),
                isEnabled = $checkbox.is( ':checked' ) ? 'yes' : 'no';

            var params = {
                action     : 'woocommerce_gzd_edit_shipping_provider_status',
                enable     : isEnabled,
                provider   : provider
            };

            // Prevent change setting warning
            window.onbeforeunload = '';

            $toggle.addClass( 'woocommerce-input-toggle--loading' );

            self.doAjax( params, self.onChangeProviderStatusSucess );
        },

        onChangeProviderStatusSucess: function( data ) {
            var self      = germanized.admin.shipping_providers,
                $provider = self.$wrapper.find( 'tr[data-shipping-provider="' + data['provider'] + '"]' ),
                $toggle   = $provider.find( '.woocommerce-gzd-input-toggle' );

            $toggle.removeClass( 'woocommerce-input-toggle--loading' );
            $toggle.removeClass( 'woocommerce-input-toggle--enabled, woocommerce-input-toggle--disabled' );

            if ( 'yes' === data['activated'] ) {
                $toggle.addClass( 'woocommerce-input-toggle--enabled' );
            } else {
                $toggle.addClass( 'woocommerce-input-toggle--disabled' );
            }
        },

        getProviderName: function( $element ) {
            var self = germanized.admin.shipping_providers;

            if ( $element.data( 'shipping-provider' ) ) {
                return $element.data( 'shipping-provider' );
            } else if ( $element.parents( 'tr' ).length > 0 ) {
                return $element.parents( 'tr' ).data( 'shipping-provider' );
            }

            return false;
        },

        onRemoveProvider: function() {
            var self     = germanized.admin.shipping_providers,
                provider = self.getProviderName( $( this ) );

            if ( provider ) {
                var answer = window.confirm( self.getParams().i18n_remove_shipping_provider_notice );

                if ( answer ) {
                    self.removeProvider( provider );
                }
            }

            return false;
        },

        removeProvider: function( id ) {
            var self = germanized.admin.shipping_providers,
                params = {
                    'action'  : 'woocommerce_gzd_remove_shipping_provider',
                    'provider': id,
                    'security': self.getParams().remove_shipping_provider_nonce
                };

            self.block();
            self.doAjax( params, self.onRemoveProviderSuccess );
        },

        onRemoveProviderSuccess: function( data ) {
            var self      = germanized.admin.shipping_providers,
                $provider = self.$wrapper.find( 'tr[data-shipping-provider="' + data['provider'] + '"]' );

            if ( $provider.length > 0 ) {
                $provider.remove();
            }
        },

        block: function() {
            var self = germanized.admin.shipping_providers;

            self.$wrapper.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },

        unblock: function() {
            var self = germanized.admin.shipping_providers;

            self.$wrapper.unblock();
        },

        doAjax: function( params, cSuccess, cError ) {
            var self     = germanized.admin.shipping_providers,
                url      = self.params.ajax_url,
                $wrapper = self.$wrapper;

            cSuccess = cSuccess || self.onAjaxSuccess;
            cError   = cError || self.onAjaxError;

            if ( ! params.hasOwnProperty( 'security' ) ) {
                params['security'] = self.params.edit_shipping_providers_nonce;
            }

            $.ajax({
                type: "POST",
                url:  url,
                data: params,
                success: function( data ) {
                    if ( data.success ) {
                        cSuccess.apply( $wrapper, [ data ] );
                        self.unblock();
                    } else {
                        cError.apply( $wrapper, [ data ] );
                        self.unblock();
                    }
                },
                error: function( data ) {
                    cError.apply( $wrapper, [ data ] );
                },
                dataType: 'json'
            });
        },

        onAjaxError: function( data ) {

        },

        onAjaxSuccess: function( data ) {

        },

        getParams: function() {
            var self = germanized.admin.shipping_providers;

            return self.params;
        }
    };

    $( document ).ready( function() {
        germanized.admin.shipping_providers.init();
    });

})( jQuery, window.germanized.admin );
