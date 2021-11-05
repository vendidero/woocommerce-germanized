;(function ( $, window, document, undefined ) {
    $( function() {
        /**
         * Use a timeout here to allow custom scripts (e.g. bundles) to dynamically instantiate variation forms
         */
        setTimeout( function() {
            $( '.variations_form:not(.has-unit-price-observer)' ).each( function() {
                $( this ).wc_germanized_unit_price_observer();
            });
        }, 250 );
    });

})( jQuery, window, document );

window.germanized = window.germanized || {};