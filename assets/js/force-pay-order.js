jQuery( function( $ ) {
    if ( $( '#order_review' ).length > 0 && $( 'input#payment_method_' + wc_gzd_force_pay_order_params.gateway ).length > 0 ) {

        $( '#order_review' ).block({
            message: wc_gzd_force_pay_order_params.block_message,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        $( 'input#payment_method_' + wc_gzd_force_pay_order_params.gateway ).attr( 'checked', 'checked' );
        $( '#order_review' ).trigger( 'submit' );
    }
});
