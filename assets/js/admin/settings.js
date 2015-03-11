jQuery( function ( $ ) {
	$( document ).on( 'click', '#wc-gzd-trusted-shops-export', function() {
		var href_org = $( this ).data( "href-org" );
		$( this ).attr( "href", href_org + '&interval=' + $( '#woocommerce_gzd_trusted_shops_review_collector' ).val() ); 
	});
});

jQuery( window ).load( function () {
	window.scrollTo( 0, 0 );
});