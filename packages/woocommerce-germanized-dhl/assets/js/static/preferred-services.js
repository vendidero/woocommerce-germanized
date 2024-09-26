
window.germanized = window.germanized || {};
window.germanized.dhl_preferred_services = window.germanized.dhl_preferred_services || {};

( function( $, germanized ) {

    /**
     * Core
     */
    germanized.dhl_preferred_services = {

        params: {},

        init: function () {
            var self     = germanized.dhl_preferred_services;
            self.params  = wc_gzd_dhl_preferred_services_params;

            $( document.body ).on( 'updated_checkout', self.afterRefreshCheckout );

            $( document )
                .on( 'change', '.dhl-preferred-service-content .dhl-preferred-location-types input', self.onChangeLocationType )
                .on( 'change', '.woocommerce-checkout #billing_postcode', self.triggerCheckoutRefresh )
                .on( 'change', '.woocommerce-checkout #shipping_postcode', self.triggerCheckoutRefresh )
                .on( 'change', '.dhl-preferred-service-content .dhl-preferred-service-times input', self.triggerCheckoutRefresh )
                .on( 'change', '.dhl-preferred-service-content .dhl-preferred-delivery-types input', self.triggerCheckoutRefresh );

            if ( self.params.payment_gateways_excluded ) {
                $( document.body ).on( 'payment_method_selected', self.triggerCheckoutRefresh );
            }

            self.afterRefreshCheckout();
        },

        triggerCheckoutRefresh: function() {
            $( document.body ).trigger( 'update_checkout' );
        },

        afterRefreshCheckout: function() {
            var self = germanized.dhl_preferred_services;

            self.initTipTip();
            self.onChangeLocationType();
        },

        onChangeLocationType: function() {
            var self = germanized.dhl_preferred_services,
                $box = $( '.dhl-preferred-service-content .dhl-preferred-location-types input:checked' );

            $( '.dhl-preferred-service-content .dhl-preferred-service-location-data' ).hide();

            if ( $box.length > 0 ) {
                if ( 'place' === $box.val() ) {
                    $( '.dhl-preferred-service-content .dhl-preferred-service-location-place' ).show();
                } else if ( 'neighbor' === $box.val() ) {
                    $( '.dhl-preferred-service-content .dhl-preferred-service-location-neighbor' ).show();
                }
            }
        },

        initTipTip: function() {

            // Remove any lingering tooltips
            $( '#tiptip_holder' ).removeAttr( 'style' );
            $( '#tiptip_arrow' ).removeAttr( 'style' );

            $( '.dhl-preferred-service-content .woocommerce-help-tip' ).tipTip({
                'attribute': 'data-tip',
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            });
        }
    };

    $( document ).ready( function() {
        germanized.dhl_preferred_services.init();
    });

})( jQuery, window.germanized );
