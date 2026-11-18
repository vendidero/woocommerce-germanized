window.eu_owb = window.eu_owb || {};
window.eu_owb.admin_orders = window.eu_owb.admin_orders || {};

( function( $, eu_owb ) {
    /**
     * Core
     */
    eu_owb.admin_orders = {
        params: {},

        /**
         * Preview data is cached in this private map (keyed by withdrawal id) and only ever
         * populated from the authenticated AJAX response. Never read preview data from
         * jQuery .data() as that falls back to (potentially injected) data-* attributes.
         */
        previewCache: {},

        init: function () {
            var self  = eu_owb.admin_orders;
            self.params = eu_owb_woocommerce_admin_orders_params;

            $( document ).on( 'click', '.wp-list-table .order-preview-trigger:not(.disabled)', self.onPreview );
        },

        onPreview: function () {
            var $previewButton = $( this ),
                self           = eu_owb.admin_orders,
                $order_id      = parseInt( $previewButton.attr( 'data-order-id' ), 10 );

            if ( ! $order_id ) {
                return false;
            }

            if ( Object.prototype.hasOwnProperty.call( self.previewCache, $order_id ) ) {
                $( this ).WCBackboneModal({
                    template: 'wc-modal-view-order',
                    variable : self.previewCache[ $order_id ]
                });
            } else {
                $previewButton.addClass( 'disabled' );

                $.ajax({
                    url:     self.params.ajax_url,
                    data:    {
                        order_id: $order_id,
                        action  : 'eu_owb_woocommerce_get_withdrawal_details',
                        security: self.params.preview_nonce
                    },
                    type:    'GET',
                    success: function( response ) {
                        $( '.order-preview' ).removeClass( 'disabled' );

                        if ( response.success ) {
                            self.previewCache[ $order_id ] = response.data;

                            $( this ).WCBackboneModal({
                                template: 'wc-modal-view-order',
                                variable : response.data
                            });
                        }
                    }
                });
            }
            return false;
        },
    };

    $( document ).ready( function() {
        eu_owb.admin_orders.init();
    });
})( jQuery, window.eu_owb );
