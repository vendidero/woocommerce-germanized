window.eu_owb = window.eu_owb || {};
window.eu_owb.admin_order = window.eu_owb.admin_order || {};

( function( $, eu_owb ) {
    /**
     * Core
     */
    eu_owb.admin_order = {
        init: function () {
            var self  = eu_owb.admin_order;

            $( document ).on( 'click', '.eu-owb-reject-withdrawal-request-start', self.onRejectRequest );
            $( document ).on( 'click', '.eu-owb-woocommerce-needs-confirmation', self.onConfirm );
        },

        onConfirm: function( e ) {
            var self = eu_owb.admin_order,
                msg = $( this ).data( 'confirm' );

            if ( ! confirm( msg ) ) {
                e.preventDefault();
            }
        },

        onRejectRequest: function() {
            var self = eu_owb.admin_order,
                $wrapper = $( this ).parents( '.eu-owb-order-withdrawal-request' );

            $wrapper.find( '.eu-owb-reject-withdrawal-request-form' ).toggleClass( 'hidden' );
            $wrapper.find( '#eu_owb_reject_reason' ).focus();

            return false;
        },
    };

    $( document ).ready( function() {
        eu_owb.admin_order.init();
    });
})( jQuery, window.eu_owb );
