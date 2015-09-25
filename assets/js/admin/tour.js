jQuery( function ( $ ) {

	var tour = $( '.wc-gzd-tour' ).tourbus( {
		leg: {
			zindex: 99999,
			width: '450',
			scrollSpeed: 550,
		}
	} );
	tour.data( 'tourbus' ).depart();

});