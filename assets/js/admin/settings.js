jQuery( function ( $ ) {

	$( document ).on( 'change', 'input[name=woocommerce_gzd_dispute_resolution_type]', function() {

		var val = $( this ).val();
		var text = $( '#woocommerce_gzd_alternative_complaints_text_' + val );

		$( '[id^=woocommerce_gzd_alternative_complaints_text_' ).parents( 'tr' ).hide();
		$( '#woocommerce_gzd_alternative_complaints_text_' + val ).parents( 'tr' ).show();
		
	});

	$( 'input[name=woocommerce_gzd_dispute_resolution_type]:checked' ).trigger( 'change' );

	if ( $( '#woocommerce_gzd_mail_attach_imprint' ).length > 0 ) {

		var table = $( '#woocommerce_gzd_mail_attach_imprint' ).parents( 'table' );

		$( table ).find( 'tbody' ).sortable({

			items: 'tr',
			cursor: 'move',
			axis: 'y',
			handle: 'td, th',
			scrollSensitivity: 40,
			helper:function(e,ui){
				ui.children().each(function(){
					jQuery(this).width(jQuery(this).width());
				});
				ui.css('left', '0');
				return ui;
			},
			start:function(event,ui) {
				ui.item.css('background-color','#f6f6f6');
			},
			stop:function(event,ui){
				ui.item.removeAttr('style');
				var pages = [];
				$( table ).find( 'tr select' ).each( function() {
					pages.push( $(this).attr( 'id' ).replace( 'woocommerce_gzd_mail_attach_', '' ) );
				});
				$( '#woocommerce_gzd_mail_attach_order' ).val( pages.join() );
			}

		});

	}

});