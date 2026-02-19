window.shiptastic = window.shiptastic || {};
window.shiptastic.admin = window.shiptastic.admin || {};

( function( $, shipments ) {
    shipments.admin.wizard = {
        params: {},

        init: function() {
            var self = shipments.admin.wizard;
            self.params = wc_shiptastic_admin_wizard_params;

            $( document )
                .on( 'submit', '.wc-shiptastic-wizard-form', self.onSubmit );
        },

        onSubmit: function( e ) {
            var self = shipments.admin.wizard,
                $form = $( this ),
                $errorWrapper = $form.find( '.wc-shiptastic-error-wrapper' ),
                $mainButton = $( '#wc-shiptastic-wizard-links' ).find( '.button-submit' ),
                data = $form.serialize();

            $errorWrapper.find( ".notice" ).remove();
            $form.addClass( 'loading' );
            $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', true );
            $mainButton.prop( 'disabled', true ).addClass( 'loading' );

            $.ajax( {
                type: 'POST',
                url: self.params.ajax_url,
                data: data,
                dataType: 'json',
            }).done( function ( response ) {
                $form.removeClass( 'loading' );
                $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', false );
                $mainButton.prop( 'disabled', false ).removeClass( 'loading' );

                window.location = response.redirect;
            }).fail( function ( xhr ) {
                $form.removeClass( 'loading' );
                $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', false );
                $mainButton.prop( 'disabled', false ).removeClass( 'loading' );

                try {
                    var response = JSON.parse( xhr.responseText );
                } catch( $e ) {
                    response = {};
                }

                $.each( response.data, function( i, error ) {
                    $errorWrapper.append( '<div class="notice notice-error"><p>' + error.message + '</p></div>' );
                });

                $errorWrapper[0].scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            });

            return false;
        },
    };

    $( document ).ready( function() {
        shipments.admin.wizard.init();
    });

})( jQuery, window.shiptastic );