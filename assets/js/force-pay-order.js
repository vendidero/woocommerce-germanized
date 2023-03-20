jQuery( function( $ ) {
    if ( $( '#order_review' ).length > 0 && $( 'input#payment_method_' + wc_gzd_force_pay_order_params.gateway ).length > 0 ) {
        $payment_box = $( 'div.payment_method_' + wc_gzd_force_pay_order_params.gateway );
        do_submit    = wc_gzd_force_pay_order_params.auto_submit;

        if ( $payment_box.length > 0 ) {
            if ( $payment_box.find( ':input' ).length > 0 ) {
                // Do not submit if the payment requires inputs
                do_submit = false;
            }
        }

        // Trigger click event because Woo listens to that
        $( 'input#payment_method_' + wc_gzd_force_pay_order_params.gateway ).prop( 'checked', true ).trigger( 'click' );

        if ( do_submit ) {
            $( '#order_review' ).block({
                message: wc_gzd_force_pay_order_params.block_message,
                css: {
                    padding: '1em',
                    'white-space': 'pre-wrap'
                },
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6,
                }
            });

            $( '#order_review' ).trigger( 'submit' );
        }
    }
});
