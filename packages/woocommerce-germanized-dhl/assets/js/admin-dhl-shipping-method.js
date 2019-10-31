window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.dhl_shipping_method = {

        params: {},

        init: function () {
            var self = germanized.admin.dhl_shipping_method;

            $( document ).on( 'change', 'input[id$=dhl_enable]', self.showOrHideAll );
            $( document.body ).on( 'wc_backbone_modal_loaded', self.onShippingMethodOpen );
        },

        onShippingMethodOpen: function( e, t ) {
            if ( 'wc-modal-shipping-method-settings' === t ) {
                if ( $( 'input[id$=dhl_enable]' ).length > 0 ) {
                    $( 'input[id$=dhl_enable]' ).trigger( 'change' );
                }
            }
        },

        showOrHideAll: function() {
            var self      = germanized.admin.dhl_shipping_method,
                $input    = $( this ),
                $form     = $input.parents( 'form' );

            if ( ! $input.is( ':checked' ) ) {
                $form.find( 'table.form-table' ).each( function() {
                    if ( $( this ).find( 'input[id*=_dhl_]' ).length > 0 ) {
                        self.hideTable( $( this ) );
                    }
                });
            } else {
                $form.find( 'table.form-table' ).each( function() {
                    if ( $( this ).find( 'input[id*=_dhl_]' ).length > 0 ) {
                        self.showTable( $( this ) );
                    }
                });
            }
        },

        hideTable: function( $table ) {

            if ( $table.find( 'input[id$=dhl_enable]' ).length > 0 ) {
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
        germanized.admin.dhl_shipping_method.init();
    });

})( jQuery, window.germanized.admin );
