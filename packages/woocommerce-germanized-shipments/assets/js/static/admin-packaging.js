window.shipments = window.shipments || {};
window.shipments.admin = window.shipments.admin || {};

( function( $, shipments ) {
    shipments.admin.packaging = {
        params: {},

        init: function() {
            var self = shipments.admin.packaging;

            $( document )
                .on( 'change', 'input.gzd-override-toggle', self.onChangeOverride );
        },

        onChangeOverride: function() {
            var $checkbox = $( this ),
                $wrapper = $checkbox.parents( '.wc-gzd-shipping-provider-override-title-wrapper' ),
                $next = $wrapper.next( '.wc-gzd-packaging-zone-wrapper' );

            $next.removeClass( 'zone-wrapper-has-override' );

            if ( $checkbox.is( ':checked' ) ) {
                $next.addClass( 'zone-wrapper-has-override' );
            }
        },
    };

    $( document ).ready( function() {
        shipments.admin.packaging.init();
    });

})( jQuery, window.shipments );