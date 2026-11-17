window.eu_owb = window.eu_owb || {};
window.eu_owb.admin_orders = window.eu_owb.admin_orders || {};

( function( $, eu_owb ) {
    /**
     * Core
     */
    eu_owb.admin_orders = {
        params: {},

        init: function () {
            var self  = eu_owb.admin_orders;
            self.params = eu_owb_woocommerce_admin_orders_params;

            $( document ).on( 'click', '.wp-list-table .order-preview-trigger:not(.disabled)', self.onPreview );
        },

        onPreview: function () {
            var $previewButton = $( this ),
                self  = eu_owb.admin_orders,
                $order_id      = $previewButton.data( 'orderId' );

            if ( $previewButton.data( 'eu-owb-preview-loaded' ) && $previewButton.data( 'orderData' ) ) {
                $( this ).WCBackboneModal({
                    template: 'wc-modal-view-order',
                    variable : $previewButton.data( 'orderData' )
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
                            $previewButton.data( 'orderData', response.data );
                            $previewButton.data( 'eu-owb-preview-loaded', true );

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
