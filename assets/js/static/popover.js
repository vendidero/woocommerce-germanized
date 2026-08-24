/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order */
window.germanized = window.germanized || {};

( function( $, germanized ) {
    germanized.popover = {
        params: {},

        init: function() {
            const isTouch = !!( 'ontouchstart' in window );

            if ( ! isTouch ) {
                $( document ).on( 'mouseenter', '.wc-gzd-popover-trigger', function( e ) {
                    let $popover = $( this ).next( '.wc-gzd-popover' );
                    const isOpen = $popover[0].matches(':popover-open');

                    if ( false === isOpen ) {
                        $popover.addClass( 'wc-gzd-popover-hover' );
                        console.log('hover');
                        console.log(e);
                        germanized.popover.showPopover( $popover );
                    }
                } );

                $( document ).on( 'mouseleave', '.wc-gzd-popover-trigger', function(e) {
                    let $popover = $( this ).next( '.wc-gzd-popover' );

                    if ( $popover.hasClass( 'wc-gzd-popover-hover' ) ) {
                        germanized.popover.hidePopover( $popover );
                    }
                } );
            }

            $( document ).on( 'click', '.wc-gzd-popover-trigger', function( e ) {
                let $popover = $( this ).next( '.wc-gzd-popover' );

                $popover.removeClass( 'wc-gzd-popover-hover' );
                germanized.popover.showPopover( $popover );

                return false;
            } );

            $( document ).on( 'click', '.wc-gzd-popover-close', function() {
                let $popover = false;

                if ( $( this ).hasClass( 'wc-gzd-popover' ) ) {
                    $popover = $( this );
                } else {
                    $popover = $( this ).parents( '.wc-gzd-popover' );
                }

                germanized.popover.hidePopover( $popover );

                return false;
            } );
        },

        showPopover( $popover ) {
            if ( 'popover' in HTMLElement.prototype ) {
                $popover[0].showPopover();
            } else {
                $popover.show();
            }
        },

        hidePopover( $popover ) {
            $popover.removeClass( 'wc-gzd-popover-hover' );

            if ( 'popover' in HTMLElement.prototype ) {
                $popover[0].hidePopover();
            } else {
                $popover.hide();
            }
        },
    };

    $( document ).ready( function() {
        germanized.popover.init();
    });

})( jQuery, window.germanized );
