/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order */
window.germanized = window.germanized || {};

( function( $, germanized ) {

    /**
     * Order Data Panel
     */
    germanized.setup = {

        params: {},

        init: function() {
            $( '.woocommerce-help-tip' ).tipTip( {
                'attribute': 'data-tip',
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            } );
        }
    };

    $( document ).ready( function() {
        germanized.setup.init();
    });

})( jQuery, window.germanized );