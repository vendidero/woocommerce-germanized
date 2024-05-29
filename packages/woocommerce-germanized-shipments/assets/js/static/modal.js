
window.germanized = window.germanized || {};
window.germanized.shipments_modal = window.germanized.shipments_modal || {};

( function( $, germanized ) {

    /**
     * Core
     */
    germanized.shipments_modal = {
        params: {},

        init: function () {
            var self  = germanized.shipments_modal;

            $( document ).on( 'click', '.wc-gzd-modal-launcher, .wc-gzd-modal-background, .wc-gzd-modal-close', self.onTriggerModal );
            $( document ).on( 'click', '.wc-gzd-modal-content.active', self.detectBgClick );
        },

        detectBgClick: function( e ) {
            if ( ! $( e.target ).closest('.wc-gzd-modal-content-inner' ).length && ! $( e.target ).is( '.wc-gzd-modal-content-inner' ) ) {
                $( this ).find( '.wc-gzd-modal-close' ).trigger( 'click' );
            }
        },

        onTriggerModal: function() {
            var modalId = false;

            if ( $( this ).parents( '.wc-gzd-modal-content' ).length > 0 ) {
                modalId = $( this ).parents( '.wc-gzd-modal-content' ).data( 'id' );
            } else {
                modalId = $( this ).data( 'modal-id' );
            }

            if ( modalId ) {
                if ( $( '.wc-gzd-modal-content[data-id="' + modalId + '"]' ).length > 0 ) {
                    var $modal = $( '.wc-gzd-modal-content[data-id="' + modalId + '"]' );

                    $modal.toggleClass( "active" );
                    $( '.wc-gzd-modal-background' ).toggleClass( "active" );

                    if ( $modal.hasClass( 'active' ) ) {
                        $( '.wc-gzd-modal-background' ).attr( 'data-id', modalId );
                        $( 'body' ).addClass( 'wc-gzd-body-modal-active' );

                        $( document.body ).trigger( 'wc_gzd_shipments_modal_open', [ modalId, $modal ] );
                    } else {
                        $( 'body' ).removeClass( 'wc-gzd-body-modal-active' );

                        $( document.body ).trigger( 'wc_gzd_shipments_modal_close', [ modalId, $modal ] );
                    }
                }
            }

            return false;
        },
    };

    $( document ).ready( function() {
        germanized.shipments_modal.init();
    });

})( jQuery, window.germanized );
