window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.shipping_provider_method = {

        params: {},
        currentProvider: '',

        init: function () {
            var self = germanized.admin.shipping_provider_method;

            self.params = wc_gzd_admin_shipping_provider_method_params;

            $( document ).on( 'change', 'select[id$=shipping_provider]', self.showOrHideAll );
            $( document.body ).on( 'wc_backbone_modal_loaded', self.onShippingMethodOpen );

            if ( $( 'select[id$=shipping_provider]' ).length > 0 ) {
                $( 'select[id$=shipping_provider]' ).trigger( 'change' );
            }
        },

        onShippingMethodOpen: function( e, t ) {
            if ( 'wc-modal-shipping-method-settings' === t ) {
                if ( $( 'select[id$=shipping_provider]' ).length > 0 ) {
                    $( 'select[id$=shipping_provider]' ).trigger( 'change' );
                }
            }
        },

        showOrHideAll: function() {
            var self       = germanized.admin.shipping_provider_method,
                $select    = $( this ),
                $providers = $select.find( 'option' ),
                $form      = $select.parents( 'form' );

            self.currentProvider = $select.val();

            $providers.each( function() {
                var $provider               = $( this ),
                    provider_setting_prefix = $provider.val();

                if ( provider_setting_prefix.length > 0 ) {
                    $form.find( 'table.form-table' ).each( function() {
                        if ( $( this ).find( ':input[id*=_' + provider_setting_prefix + '_]' ).length > 0 ) {
                            self.hideTable( $( this ) );
                        }
                    });
                }
            });

            if ( self.currentProvider.length > 0 ) {
                $form.find( 'table.form-table' ).each( function() {
                    if ( $( this ).find( ':input[id*=_' + self.currentProvider + '_]' ).length > 0 ) {
                        self.showTable( $( this ) );
                    }
                });
            }
        },

        hideTable: function( $table ) {

            if ( $table.find( 'select[id$=shipping_provider]' ).length > 0 ) {
                return false;
            }

            $table.prevUntil( 'table.form-table' ).hide();
            $table.hide();
        },

        showTable: function( $table ) {
            $table.prevUntil( 'table.form-table' ).show();
            $table.show();
        }
    };

    $( document ).ready( function() {
        germanized.admin.shipping_provider_method.init();
    });

})( jQuery, window.germanized.admin );
