jQuery( function ( $ ) {
	
	if( $( '#template code' ).length > 0 ) {

		$( '#template code' ).each( function() {

			if ( $( this ).html().indexOf( "woocommerce/" ) >= 0 )
				$( this ).html( $( this ).html().replace( 'woocommerce/', 'woocommerce-germanized/' ) );

		});

	}

	if ( $( '#template .button:not(.toggle_editor)' ).length > 0 ) {

		$( '#template .button:not(.toggle_editor)' ).remove();

	}

});