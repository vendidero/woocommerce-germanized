
window.shiptastic = window.shiptastic || {};
window.shiptastic.shipments_modal = window.shiptastic.shipments_modal || {};

( function( $, shipments ) {

    /**
     * Core
     */
    shipments.shipments_modal = {
        params: {},

        init: function () {
            var self  = shipments.shipments_modal;

            $( document ).on( 'click', '.wc-stc-modal-launcher, .wc-stc-modal-background, .wc-stc-modal-close', self.onTriggerModal );
            $( document ).on( 'click', '.wc-stc-modal-content.active', self.detectBgClick );
        },

        detectBgClick: function( e ) {
            if ( ! $( e.target ).closest('.wc-stc-modal-content-inner' ).length && ! $( e.target ).is( '.wc-stc-modal-content-inner' ) ) {
                $( this ).find( '.wc-stc-modal-close' ).trigger( 'click' );
            }
        },

        onTriggerModal: function() {
            var modalId = false;

            if ( $( this ).parents( '.wc-stc-modal-content' ).length > 0 ) {
                modalId = $( this ).parents( '.wc-stc-modal-content' ).data( 'id' );
            } else {
                modalId = $( this ).data( 'modal-id' );
            }

            if ( modalId ) {
                if ( $( '.wc-stc-modal-content[data-id="' + modalId + '"]' ).length > 0 ) {
                    var $modal = $( '.wc-stc-modal-content[data-id="' + modalId + '"]' );

                    $modal.toggleClass( "active" );
                    $( '.wc-stc-modal-background' ).toggleClass( "active" );

                    if ( $modal.hasClass( 'active' ) ) {
                        $( '.wc-stc-modal-background' ).attr( 'data-id', modalId );
                        $( 'body' ).addClass( 'wc-stc-body-modal-active' );

                        $( document.body ).trigger( 'wc_shiptastic_modal_open', [ modalId, $modal ] );
                    } else {
                        $( 'body' ).removeClass( 'wc-stc-body-modal-active' );

                        $( document.body ).trigger( 'wc_shiptastic_modal_close', [ modalId, $modal ] );
                    }
                }
            }

            return false;
        },
    };

    $( document ).ready( function() {
        shipments.shipments_modal.init();
    });

})( jQuery, window.shiptastic );
