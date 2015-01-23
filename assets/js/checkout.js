jQuery( function( $ ) {
	$( document ).on( 'change', '.payment_methods input[name="payment_method"]', function() {
		$( 'body' ).trigger( 'update_checkout' );
	});
});