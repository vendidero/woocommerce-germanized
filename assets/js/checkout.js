jQuery( function( $ ) {
	$( '.payment_methods' ).addClass( 'update_totals_on_change' );
	$( 'body' ).bind( 'updated_checkout', function() {
		$( '.payment_methods' ).addClass( 'update_totals_on_change' );
	});
});