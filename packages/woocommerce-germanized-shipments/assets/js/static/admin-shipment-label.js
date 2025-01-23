window.shipments = window.shipments || {};
window.shipments.admin = window.shipments.admin || {};

( function( $, shipments ) {

    /**
     * Core
     */
    shipments.admin.shipment_label = {
        params: {},

        init: function() {
            var self = shipments.admin.shipment_label;

            $( document ).on( 'change', '.shipments-create-label #product_id', self.onChangeLabelProductId );
        },
        onChangeLabelProductId: function() {
            var self = shipments.admin.shipment_label;

            self.showOrHideByLabelProduct( $( this ).val() );
        },

        showOrHideByLabelProduct: function( productId ) {
            var $wrapper  = $( '.shipments-create-label' ),
                $fields   = $wrapper.find( 'p.form-field :input[data-products-supported]' );

            $fields.each( function() {
                var $field    = $( this ),
                    supported = $field.data( 'products-supported' );

                if ( supported.length > 0 ) {
                    if ( supported.indexOf( '&' ) > -1 && $field.is( 'select' ) ) {
                        var supportedData = supported.split( '&' ).filter( Boolean ),
                            needsReset = false;

                        $.each( supportedData, function( i, d ) {
                            var innerData = d.split( '=' ).filter( Boolean );

                            if ( innerData.length > 1 ) {
                                var optionKey = innerData[0];
                                var supportedProducts = innerData[1].split( ',' );
                                var isHidden = true;
                                var $option = $field.find( 'option[value="' + optionKey + '"]' );

                                if ( $option.length > 0 ) {
                                    if ( $.inArray( productId, supportedProducts ) !== -1 ) {
                                        isHidden = false;
                                    }

                                    if ( isHidden ) {
                                        if ( $option.is( ':selected' ) ) {
                                            needsReset = true;
                                        }

                                        $option.hide();
                                    } else {
                                        $option.show();
                                    }
                                }
                            }
                        } );

                        var isFieldHidden = true;

                        $field.find( 'option' ).each( function () {
                            if ( $( this ).css( 'display' ) !== 'none' ) {
                                if ( needsReset ) {
                                    $( this ).prop("selected", true );
                                }
                                isFieldHidden = false;
                                return false;
                            }
                        });

                        if ( isFieldHidden ) {
                            $field.parents( '.form-field' ).hide();
                            $field.trigger( 'change' );
                        } else {
                            $field.parents( '.form-field' ).show();
                            $field.trigger( 'change' );
                        }

                        $field.trigger( 'change' );
                    } else {
                        var supportedProducts = supported.split( ',' ).filter( Boolean ),
                            isHidden = supportedProducts.length > 0 ? true : false;

                        if ( $.inArray( productId, supportedProducts ) !== -1 ) {
                            isHidden = false;
                        }

                        if ( isHidden ) {
                            $field.parents( '.form-field' ).hide();
                            $field.trigger( 'change' );
                        } else {
                            $field.parents( '.form-field' ).show();
                            $field.trigger( 'change' );
                        }
                    }
                }
            } );
        }
    };

    $( document ).ready( function() {
        shipments.admin.shipment_label.init();
    });

})( jQuery, window.shipments );
