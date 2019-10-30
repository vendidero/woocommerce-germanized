window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.dhl_table = {

        params: {},

        init: function () {
            var self = germanized.admin.dhl_table;

            $( document )
                .on( 'click', '.wc-gzd-shipment-action-button-generate-dhl-label', self.onCreateLabel )
        },

        onCreateLabel: function() {
            var self       = germanized.admin.dhl_table,
                shipmentId = $( this ).parents( 'tr' ).find( 'th.check-column input' ).val();

            $( this ).parents( 'td' ).WCBackboneModal({
                template: 'wc-gzd-modal-create-shipment-label-' + shipmentId
            });

            return false;
        }
    };

    $( document ).ready( function() {
        germanized.admin.dhl_table.init();
    });

})( jQuery, window.germanized.admin );
