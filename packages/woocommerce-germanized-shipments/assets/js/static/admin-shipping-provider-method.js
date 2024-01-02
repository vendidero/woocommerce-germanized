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
                .on( 'click', '.wc-gzd-shipping-provider-method-tabs .nav-tab-wrapper a.nav-tab', self.onChangeTab )
                .on( 'change', '.override-checkbox :input', self.onChangeOverride );

            $( document.body ).on( 'wc_backbone_modal_loaded', self.onShippingMethodOpen );

            if ( $( 'select[id$=shipping_provider]' ).length > 0 ) {
                $( 'select[id$=shipping_provider]' ).trigger( 'change' );
            }
        },

        parseFieldId: function( fieldId ) {
            return fieldId.replace( '[', '_' ).replace( ']', '' );
        },

        onChangeOverride: function() {
            var self      = germanized.admin.shipping_provider_method,
                $checkbox = $( this ),
                isChecked = $checkbox.is( ':checked' ),
                $parent   = $checkbox.parents( '.wc-gzd-shipping-provider-override-wrapper' );

            if ( isChecked ) {
                $parent.find( '.wc-gzd-shipping-provider-override-inner-wrapper' ).addClass( 'has-override' );
            } else {
                $parent.find( '.wc-gzd-shipping-provider-override-inner-wrapper' ).removeClass( 'has-override' );
            }
        },

        onChangeTab: function() {
            var self     = germanized.admin.shipping_provider_method,
                $navTab  = $( this ),
                $wrapper = $navTab.parents( 'form' ),
                tab      = $navTab.attr( 'href' ).replace( '#', '' ),
                $tab     = $wrapper.find( '.wc-gzd-shipping-provider-method-tab-content[data-tab="' + tab + '"]' );

            $navTab.parents( '.wc-gzd-shipping-provider-method-tabs' ).find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
            $wrapper.find( '.wc-gzd-shipping-provider-method-tab-content' ).removeClass( 'tab-content-active' );

            if ( $tab.length > 0 ) {
                $navTab.addClass( 'nav-tab-active' );
                $tab.addClass( 'tab-content-active' );
                $tab.find( ':input:visible' ).trigger( 'change' );
            }

            return false;
        },

        /**
         * Is being provided as callback for germanized.admin.shipment_settings.getCleanInputId().
         *
         * @param $mainInput
         * @returns {*|boolean}
         */
        getCleanInputId: function( $mainInput ) {
            var self            = germanized.admin.shipping_provider_method,
                currentProvider = self.currentProvider,
                fieldId         = $mainInput.attr( 'id' ) ? $mainInput.attr( 'id' ) : $mainInput.attr( 'name' );

            if ( ! fieldId ) {
                return false;
            }

            if ( currentProvider && fieldId.toLowerCase().indexOf( '-p-' + currentProvider + '-' ) >= 0 ) {
                // Remove the shipping method name prefix
                return fieldId.substring( fieldId.lastIndexOf( '-p-' + currentProvider + '-' ), fieldId.length );
            }

            return fieldId;
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

            $form.find( '.wc-gzd-shipping-provider-method-tabs' ).hide();
            $form.find( '.wc-gzd-shipping-provider-method-tab-content' ).removeClass( 'tab-content-active' );

            if ( self.currentProvider.length > 0 ) {
                $form.find( 'table.form-table' ).each( function() {
                    if ( $( this ).find( ':input[id*=_' + self.currentProvider + '_]' ).length > 0 ) {
                        self.showTable( $( this ) );
                    }
                });

                $form.find( '.wc-gzd-shipping-provider-method-tabs[data-provider="' + self.currentProvider + '"]' ).show();
                $form.find( '.wc-gzd-shipping-provider-method-tabs[data-provider="' + self.currentProvider + '"] .nav-tab-wrapper' ).find( 'a.nav-tab:first' ).trigger( 'click' );

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
