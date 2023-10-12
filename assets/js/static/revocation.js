jQuery( function( $ ) {
	
	$( 'form#woocommerce-gzd-revocation' )

	/* Inline validation */

	.on( 'blur input change', '.input-text, select', function() {
		var $this = $( this ),
			$parent = $this.closest( '.form-row' ),
			validated = true;

		if ( $parent.is( '.validate-required' ) ) {
			if ( $this.val() === '' ) {
				$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
				validated = false;
			}
		}

		if ( $parent.is( '.validate-email' ) ) {
			if ( $this.val() ) {

				/* http://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
				var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);

				if ( ! pattern.test( $this.val()  ) ) {
					$parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-email' );
					validated = false;
				}
			}
		}

		if ( validated ) {
			$parent.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' ).addClass( 'woocommerce-validated' );
		}
	
	});

	$( 'form#woocommerce-gzd-revocation' ).submit( function() {
		var $form = $( this );

		if ( $form.is( '.processing' ) ) {
			return false;
		}

		$form.addClass( 'processing' ).block({
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		});

		$.ajax({
			type:		'POST',
			url:		wc_gzd_revocation_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'gzd_revocation' ),
			data:		$form.serialize(),
            dataType:   'json',
			success:	function( data ) {

				$( '.woocommerce-error, .woocommerce-message' ).remove();
				$form.removeClass( 'processing' ).unblock();

				if ( data.result === 'success' ) {
                    $form.before( data.messages );
                    $form.fadeOut( 'fast' );
                    $( 'html, body' ).animate({
                        scrollTop: ( $( '.woocommerce-message' ).offset().top - 100 )
                    }, 1000 );
				} else {
                    // Add new errors
                    if ( data.messages ) {
                        $form.prepend( data.messages );
                    }
                    $( 'html, body' ).animate({
                        scrollTop: ( $( 'form#woocommerce-gzd-revocation' ).offset().top - 100 )
                    }, 1000 );
				}
			},
		});

		return false;
	});

});