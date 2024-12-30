window.shipments = window.shipments || {};
window.shipments.admin = window.shipments.admin || {};

( function( $, shipments ) {

    /**
     * Core
     */
    shipments.admin.shipping_provider_method = {

        params: {},
        currentProvider: '',

        init: function () {
            var self = shipments.admin.shipping_provider_method;

            self.params = wc_gzd_shipments_admin_shipping_provider_method_params;

            $( document )
                .on( 'change', 'select[id$=shipping_provider]', self.showOrHideAll )
                .on( 'click', '.wc-gzd-shipping-provider-method-tabs .nav-tab-wrapper a.nav-tab', self.onChangeTab )
                .on( 'change', '.override-checkbox :input', self.onChangeOverride )
                .on( 'change', '.wc-gzd-shipping-provider-method-tab-content :input[id]', self.onChangeInput );

            $( document.body ).on( 'wc_backbone_modal_loaded', self.onShippingMethodOpen );

            if ( $( 'select[id$=shipping_provider]' ).length > 0 ) {
                $( 'select[id$=shipping_provider]' ).trigger( 'change' );
            }
        },

        onChangeInput: function() {
            var settings = shipments.admin.shipment_settings;

            settings.onChangeInput.call( $( this ) );
        },

        parseFieldId: function( fieldId ) {
            return fieldId.replace( '[', '_' ).replace( ']', '' );
        },

        onChangeOverride: function() {
            var self      = shipments.admin.shipping_provider_method,
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
            var self     = shipments.admin.shipping_provider_method,
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
         * Is being provided as callback for shipments.admin.shipment_settings.getCleanInputId().
         *
         * @param $mainInput
         * @returns {*|boolean}
         */
        getCleanInputId: function( $mainInput ) {
            var self            = shipments.admin.shipping_provider_method,
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
            var self       = shipments.admin.shipping_provider_method,
                $select    = $( this ),
                $form      = $select.parents( 'form' );

            self.currentProvider = $select.val();

            $form.find( '.wc-gzd-shipping-provider-method-tabs' ).hide();
            $form.find( '.wc-gzd-shipping-provider-method-tab-content' ).removeClass( 'tab-content-active' );

            if ( self.currentProvider.length > 0 ) {
                $form.find( '.wc-gzd-shipping-provider-method-tabs[data-provider="' + self.currentProvider + '"]' ).show();
                $form.find( '.wc-gzd-shipping-provider-method-tabs[data-provider="' + self.currentProvider + '"] .nav-tab-wrapper' ).find( 'a.nav-tab:first' ).trigger( 'click' );
            }
        }
    };

    $( document ).ready( function() {
        shipments.admin.shipping_provider_method.init();
    });

})( jQuery, window.shipments );
