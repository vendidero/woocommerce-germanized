window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.dhl_label = {

        params: {},

        init: function () {
            var self = admin.dhl_label;

            $( document ).on( 'change', '#dhl_label_dhl_product', self.onChangeProductId );
            $( document.body ).on( 'wc_gzd_shipment_label_show_if', self.onShowIf );
        },

        onShowIf: function() {
            var self = admin.dhl_label;

            if ( $( '#dhl_label_dhl_product' ).length > 0 ) {
                self.showOrHideServices( $( '#dhl_label_dhl_product' ).val() );
            }
        },

        onChangeProductId: function() {
            var self = admin.dhl_label;

            self.showOrHideServices( $( this ).val() );
        },

        showOrHideServices: function( productId ) {
            var $services = $( '.show-if-further-services' ).find( 'p.form-field' );
            console.log($services);

            $services.each( function() {
                var $service      = $( this ),
                    $serviceField = $service.find( ':input' ),
                    supported     = $serviceField.data( 'products-supported' ) ? $serviceField.data( 'products-supported' ).split( ',' ) : [],
                    isHidden      = false;

                if ( $serviceField.data( 'products-supported' ) ) {
                    isHidden = true;

                    if ( $.inArray( productId, supported ) !== -1 ) {
                        isHidden = false;
                    }
                }

                if ( isHidden ) {
                    $service.hide();
                } else {
                    $service.show();
                }
            } );
        }
    };

    $( document ).ready( function() {
        germanized.admin.dhl_label.init();
    });

})( jQuery, window.germanized.admin );