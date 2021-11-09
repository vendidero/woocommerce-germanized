jQuery( function ( $ ) {
    var wc_gzd_product = {

        init: function() {
            var self    = wc_gzd_product;

            $( document )
                .on( 'click', 'a.wc-gzd-add-new-country-specific-delivery-time', self.onAddNewDeliveryTime )
                .on( 'click', 'a.wc-gzd-remove-country-specific-delivery-time', self.onRemoveDeliveryTime );
        },

        onAddNewDeliveryTime: function() {
            var $parent = $( this ).parents( '#shipping_product_data' );

            if ( $parent.length === 0 ) {
                $parent = $( this ).parents( '.woocommerce_variable_attributes' );
            }

            var $select2 = $parent.find( '.wc-gzd-add-country-specific-delivery-time-template .wc-gzd-delivery-time-search.enhanced' );

            /**
             * Destroy the select2 element from template in case it still exists and has been initialized
             */
            if ( $select2.length > 0 ) {
                $select2.selectWoo( 'destroy' );
                $select2.removeClass( 'enhanced' );
            }

            var $template = $parent.find( '.wc-gzd-add-country-specific-delivery-time-template:first' ).clone();

            $template.removeClass( 'wc-gzd-add-country-specific-delivery-time-template' ).addClass( 'wc-gzd-country-specific-delivery-time-new' );
            $parent.find( '.wc-gzd-new-country-specific-delivery-time-placeholder' ).append( $template ).show();

            $( document.body ).trigger( 'wc-enhanced-select-init' );

            return false;
        },

        onRemoveDeliveryTime: function() {
            var $parent = $( this ).parents( '.form-row, .form-field' );

            // Trigger change to notify Woo about an update (variations).
            $parent.find( 'select' ).trigger( 'change' );
            $parent.remove();

            return false;
        }
    };

    wc_gzd_product.init();
});