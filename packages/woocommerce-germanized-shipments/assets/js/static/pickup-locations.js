
window.germanized = window.germanized || {};
window.germanized.shipments_pickup_locations = window.germanized.shipments_pickup_locations || {};

( function( $, germanized ) {

    /**
     * Core
     */
    germanized.shipments_pickup_locations = {

        params: {},
        pickupLocations: {},

        init: function () {
            var self  = germanized.shipments_pickup_locations;
            self.params  = wc_gzd_shipments_pickup_locations_params;

            var $pickupSelect = self.getPickupLocationSelect();

            if ( $pickupSelect.length > 0 ) {
                self.pickupLocations = $pickupSelect.data( 'locations' );

                $( document.body ).on( 'updated_checkout', self.afterRefreshCheckout );
                $( document ).on( 'change', '#pickup_location_field #pickup_location', self.onSelectPickupLocation );

                self.afterRefreshCheckout();
            }
        },

        maybeInitSelect2: function() {
            if ( $().selectWoo ) {
                $( 'select#pickup_location:visible, select#pickup_location:visible' ).each( function() {
                    var $this = $( this );

                    var select2_args = {
                        placeholder: $this.attr( 'data-placeholder' ) || $this.attr( 'placeholder' ) || '',
                        label: $this.attr( 'data-label' ) || null,
                        width: '100%',
                        allowClear: true
                    };

                    $( this )
                        .on( 'select2:select', function() {
                            $( this ).trigger( 'focus' ); // Maintain focus after select https://github.com/select2/select2/issues/4384
                        } )
                        .selectWoo( select2_args );
                });
            }
        },

        onSelectPickupLocation: function() {
            var self = germanized.shipments_pickup_locations,
                $pickupSelect  = self.getPickupLocationSelect(),
                $customerNumberField = $( '#pickup_location_customer_number_field' ),
                current = $pickupSelect.val();

            if ( ! $pickupSelect.val() ) {
                $customerNumberField.addClass( 'hidden' );
                $customerNumberField.hide();
            } else {
                var currentLocation = self.getPickupLocation( current );

                if ( currentLocation ) {
                    self.updateCustomerNumberField( currentLocation );
                }
            }

            $( document.body ).trigger( 'update_checkout' );
        },

        updateCustomerNumberField: function( currentLocation ) {
            var $customerNumberField = $( '#pickup_location_customer_number_field' );

            if ( currentLocation.supports_customer_number ) {
                // Do not replace via .text() to prevent removing inner html elements, e.g. optional label.
                $customerNumberField.find( 'label' )[0].firstChild.nodeValue = currentLocation.customer_number_field_label + ' ';

                if ( currentLocation.customer_number_is_mandatory ) {
                    if ( ! $customerNumberField.find( 'label .required' ).length ) {
                        $customerNumberField.find( 'label' ).append( ' <abbr class="required">*</abbr>' );
                    }

                    $customerNumberField.find( 'label .optional' ).hide();
                    $customerNumberField.addClass( 'validate-required' );
                } else {
                    $customerNumberField.find( 'label .required' ).remove();
                    $customerNumberField.find( 'label .optional' ).show();

                    $customerNumberField.removeClass( 'validate-required woocommerce-invalid woocommerce-invalid-required-field' );
                }

                $customerNumberField.removeClass( 'hidden' );
                $customerNumberField.show();
            } else {
                $customerNumberField.addClass( 'hidden' );
                $customerNumberField.hide();
            }
        },

        getPickupLocationSelect: function() {
            return $( '#pickup_location' );
        },

        getPickupLocation: function( locationCode ) {
            var self = germanized.shipments_pickup_locations;

            if ( self.pickupLocations.hasOwnProperty( locationCode ) ) {
                return self.pickupLocations[ locationCode ];
            }

            return false;
        },

        afterRefreshCheckout: function( e, ajaxData ) {
            var self = germanized.shipments_pickup_locations,
                $pickupSelect = self.getPickupLocationSelect(),
                $pickupSelectField = $pickupSelect.parents( '#pickup_location_field' ),
                $customerNumberField = $( '#pickup_location_customer_number_field' ),
                current = $pickupSelect.val();

            ajaxData = ( typeof ajaxData === 'undefined' ) ? {
                'fragments': {
                    '.gzd-shipments-pickup-locations': JSON.stringify( self.pickupLocations ),
                }
            } : ajaxData;

            if ( ajaxData.hasOwnProperty( 'fragments' ) && ajaxData.fragments.hasOwnProperty( '.gzd-shipments-pickup-locations' ) ) {
                self.pickupLocations = JSON.parse( ajaxData.fragments['.gzd-shipments-pickup-locations'] );
            } else {
                self.pickupLocations = {};
            }

            $pickupSelect.attr('data-locations', self.pickupLocations );

            if ( Object.keys( self.pickupLocations ).length ) {
                $pickupSelectField.show();
                $pickupSelectField.removeClass( 'hidden' );

                $pickupSelect.find( 'option:not([value=""])' ).remove();

                $.each( self.pickupLocations, function( code, pickupLocation ) {
                    var label = $( '<textarea />' ).html( pickupLocation.formatted_address).text();
                    $pickupSelect.append( $( "<option></option>" ).attr("value", code ).text( label ) );
                });

                var currentLocation = self.getPickupLocation( current );

                if ( currentLocation ) {
                    $pickupSelect.find( 'option[value="' + currentLocation.code + '"' )[0].selected = true;

                    self.replaceShippingAddress( currentLocation.address_replacements );
                    self.updateCustomerNumberField( currentLocation );
                } else {
                    $customerNumberField.addClass( 'hidden' );
                    $customerNumberField.hide();

                    $pickupSelect.val( "" );
                }
            } else {
                if ( "" !== current ) {
                    $( '#shipping_address_1' ).val( "" );

                    var $form = $( 'form.checkout' );

                    if ( $form.find( '.woocommerce-NoticeGroup-updateOrderReview' ).length <= 0 ) {
                        $form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-updateOrderReview"></div>' );
                    }

                    $form.find( '.woocommerce-NoticeGroup-updateOrderReview' ).prepend( '<div class="woocommerce-info">Your selected pickup location is not available any longer. Please review your shipping address.</div>' );

                    var scrollElement = $( '.woocommerce-NoticeGroup-updateOrderReview' );

                    $.scroll_to_notices( scrollElement );
                }

                $pickupSelectField.addClass( 'hidden' );
                $pickupSelectField.hide();

                $customerNumberField.addClass( 'hidden' );
                $customerNumberField.hide();

                $pickupSelect.val( "" );
            }

            self.maybeInitSelect2();
        },

        replaceShippingAddress: function( replacements ) {
            var self = germanized.shipments_pickup_locations,
                $shipToDifferent = $( '#ship-to-different-address input' ),
            hasChanged = [];

            Object.keys( replacements ).forEach( addressField => {
                var value = replacements[ addressField ];

                if ( value ) {
                    if ( $( '#shipping_' + addressField ).length > 0 ) {
                        if ( $( '#shipping_' + addressField ).val() !== value ) {
                            hasChanged.push( addressField );
                        }

                        $( '#shipping_' + addressField ).val( value );
                    }
                }
            });

            if ( ! $shipToDifferent.is( ':checked' ) ) {
                $shipToDifferent.prop( 'checked', true );
                $shipToDifferent.trigger( 'change' );
            }

            if ( hasChanged.length > 0 && $.inArray( "postcode", hasChanged ) !== -1 ) {
                $( '#shipping_postcode' ).trigger( 'change' );
            }
        }
    };

    $( document ).ready( function() {
        germanized.shipments_pickup_locations.init();
    });

})( jQuery, window.germanized );
