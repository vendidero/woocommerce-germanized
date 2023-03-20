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

            $( document )
                .on( 'change', 'select[id$=shipping_provider]', self.showOrHideAll )
                .on( 'change', ':input:visible[id]', self.onChangeField );

            $( document.body ).on( 'wc_backbone_modal_loaded', self.onShippingMethodOpen );

            if ( $( 'select[id$=shipping_provider]' ).length > 0 ) {
                $( 'select[id$=shipping_provider]' ).trigger( 'change' );
            }
        },

        parseFieldId: function( fieldId ) {
            return fieldId.replace( '[', '_' ).replace( ']', '' );
        },

        onChangeField: function() {
            var self     = germanized.admin.shipping_provider_method,
                $wrapper = $( this ).parents( 'form' ),
                fieldId  = self.parseFieldId( $( this ).attr( 'id' ) ),
                val      = $( this ).val(),
                currentProvider = self.currentProvider;

            if ( currentProvider && fieldId.toLowerCase().indexOf( '_' + currentProvider + '_' ) >= 0 ) {
                // Remove the shipping method name prefix
                var fieldIdClean = fieldId.substring( fieldId.lastIndexOf( currentProvider + '_' ), fieldId.length );

                $wrapper.find( ':input[data-show_if_' + fieldIdClean + ']' ).parents( 'tr' ).hide();

                if ( $( this ).is( ':checkbox' ) ) {
                    if ( $( this ).is( ':checked' ) ) {
                        $wrapper.find( ':input[data-show_if_' + fieldIdClean + ']' ).parents( 'tr' ).show();
                    }
                } else {
                    $wrapper.find( ':input[data-show_if_' + fieldIdClean + '*="' + val + '"]' ).parents( 'tr' ).show();
                }
            }
        },

        onShippingMethodOpen: function( e, t ) {
            if ( 'wc-modal-shipping-method-settings' === t ) {
                $wrapper = $( '.wc-modal-shipping-method-settings' );

                if ( $( 'select[id$=shipping_provider]' ).length > 0 ) {
                    $wrapper.find( 'select[id$=shipping_provider]' ).trigger( 'change' );
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

                // Trigger show/hide
                $form.find( ':input[id*=_' + self.currentProvider + '_]:visible' ).trigger( 'change' );
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
