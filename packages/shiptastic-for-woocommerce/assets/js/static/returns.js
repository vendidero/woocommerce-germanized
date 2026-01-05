window.shiptastic = window.shiptastic || {};
window.shiptastic.returns = window.shiptastic.returns || {};

( function( $, shipments ) {
    /**
     * Core
     */
    shipments.returns = {
        params: {},

        init: function () {
            var self  = shipments.returns;
            self.params  = wc_shiptastic_returns_params;

            $( document ).on( 'change', '#add_return_shipment input.return-item-checkbox, #add_return_shipment input.qty', self.onChangeReturnForm );
        },

        onChangeReturnForm: function() {
            var self = shipments.returns,
                $form = $( this ).parents( 'form' ),
                $errorWrapper = $form.find( '.wc-shiptastic-error-wrapper' ),
                $mainButton = $form.find( '.button[type=submit]' ),
                data = $form.serialize();

            $errorWrapper.find( ".notice" ).remove();
            $form.addClass( 'loading' );
            $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', true );
            $mainButton.prop( 'disabled', true ).addClass( 'loading' );

            $form.find( '.return-shipment-costs' ).hide();

            $.ajax( {
                type: 'POST',
                url: self.params.wc_ajax_url.toString().replace('%%endpoint%%', 'woocommerce_stc_calculate_return_costs'),
                data: data,
                dataType: 'json',
            }).done( function ( response ) {
                $form.removeClass( 'loading' );
                $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', false );
                $mainButton.prop( 'disabled', false ).removeClass( 'loading' );

                $form.find( '.return-shipment-costs' ).html( response.cost_i18n ).show();
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
                    $errorWrapper.append( '<p class="woocommerce-error notice">' + error.message + '</p>' );
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
        shipments.returns.init();
    });
})( jQuery, window.shiptastic );
