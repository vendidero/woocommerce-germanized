jQuery( function( $ ) {
	if ( $( '.payment_methods:first' ).parents( '#order_review' ).length ) {

		$( document ).on( 'change', '.payment_methods input[name="payment_method"]', function() {
			$( 'body' ).trigger( 'update_checkout' );
		});

	}
	
	$( 'body' ).bind( 'updated_checkout', function() {
		if ( $( '.wc-gzd-place-order' ).length > 0 ) {
			if ( $( '.place-order' ).length > 0 ) {
				// Make sure we are removing the nonce from the old container to the new one.
                $( '.place-order' ).find( '#_wpnonce' ).appendTo( '.wc-gzd-place-order' );
			}
			$( '.place-order:not(.wc-gzd-place-order)' ).remove();
		}
	});
	
	if ( $( '.woocommerce-checkout' ).find( '#order_review_heading' ).length > 0 ) {
		$( '.woocommerce-checkout' ).find( '#payment' ).after( $( '.woocommerce-checkout' ).find( '#order_review_heading' ) ); 
		$( '.woocommerce-checkout' ).find( '#order_review_heading' ).show();
	}
});
