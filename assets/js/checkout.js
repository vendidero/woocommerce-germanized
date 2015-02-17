jQuery( function( $ ) {
	$( document ).on( 'change', '.payment_methods input[name="payment_method"]', function() {
		$( 'body' ).trigger( 'update_checkout' );
	});
	$( 'body' ).bind( 'updated_checkout', function() {
		if ( $( '.wc-gzd-place-order' ).length > 0 )
			$( '.place-order:not(.wc-gzd-place-order)' ).remove();
	});
});