
window.germanized = window.germanized || {};
window.germanized.dhl_parcel_locator = window.germanized.dhl_parcel_locator || {};

( function( $, germanized ) {

    /**
     * Core
     */
    germanized.dhl_parcel_locator = {

        params: {},
        parcelShops: [],
        wrapper: '',

        init: function () {
            var self     = germanized.dhl_parcel_locator;
            self.params  = wc_gzd_dhl_parcel_locator_params;
            self.wrapper = self.params.wrapper;

            $( document )
                .on( 'change.dhl', self.wrapper + ' #shipping_address_type', self.refreshAddressType )
                .on( 'change.dhl', self.wrapper + ' #shipping_address_1', self.onChangeAddress )
                .on( 'change.dhl', self.wrapper + ' #ship-to-different-address-checkbox', self.onChangeShipping )
                .on( 'change.dhl', self.wrapper + ' #shipping_country', self.refreshAvailability );

            $( document.body ).on( 'updated_checkout', self.afterRefreshCheckout );

            self.refreshAvailability();
            self.refreshAddressType();
        },

        isCheckout: function() {
            var self = germanized.dhl_parcel_locator;

            return self.params.is_checkout;
        },

        afterRefreshCheckout: function() {
            var self = germanized.dhl_parcel_locator;

            var params = {
                'security': self.params.parcel_locator_data_nonce,
                'action'  : 'woocommerce_gzd_dhl_parcel_locator_refresh_shipping_data'
            };

            $.ajax({
                type: "POST",
                url:  self.params.ajax_url,
                data: params,
                success: function( data ) {
                    // Update shipping method data from session
                    self.params['methods'] = data.methods;
                    self.refreshAvailability();
                },
                error: function( data ) {
                    self.refreshAvailability();
                },
                dataType: 'json'
            });
        },

        refreshAvailability: function() {
            var self           = germanized.dhl_parcel_locator,
                shippingMethod = self.getShippingMethod(),
                methodData     = self.getShippingMethodData( shippingMethod );

            if ( ! self.isAvailable() ) {
                $( self.wrapper + ' #shipping_address_type' ).val( 'regular' ).trigger( 'change' );
                $( self.wrapper + ' #shipping_address_type_field' ).hide();
            } else {
                var $typeField = $( self.wrapper + ' #shipping_address_type' );
                var selected   = $typeField.val();

                if ( self.isCheckout() ) {
                    $typeField.html( '' );

                    if ( methodData ) {
                        $.each( methodData.address_type_options, function( name, title ) {
                            $typeField.append( $( '<option/>', {
                                value: name,
                                text : title
                            }));
                        });

                        if ( $typeField.find( 'option[value="' + selected + '"]' ).length > 0 ) {
                            $typeField.find( 'option[value="' + selected + '"]' ).prop( 'selected', true );
                        }

                        $typeField.trigger( 'change' );
                    }
                }

                if ( $typeField.find( 'option' ).length > 0 ) {
                    $( self.wrapper + ' #shipping_address_type_field' ).show();
                } else {
                    $( self.wrapper + ' #shipping_address_type_field' ).hide();
                }

                $( document.body ).trigger( 'woocommerce_gzd_dhl_location_available_pickup_types_changed' );
            }
         },

        onChangeShipping: function() {
            var self      = germanized.dhl_parcel_locator,
                $checkbox = $( this );

            if ( $checkbox.is( ':checked' ) ) {
                self.refreshAvailability();

                if ( self.isEnabled() ) {
                    self.refreshAddressType();
                }
            }
        },

        onChangeAddress: function() {
            var self = germanized.dhl_parcel_locator;

            if ( self.isEnabled() ) {
                self.formatAddress();
            }
        },

        formatAddress: function() {
            var needsValidation = false,
                self            = germanized.dhl_parcel_locator,
                $addressField   = $( self.wrapper + ' #shipping_address_1' ),
                address         = $addressField.val();

            if ( address.length > 0 ) {
                if ( $.isNumeric( address ) ) {
                    needsValidation = true;
                } else if ( self.addressIsPackstation() || self.addressIsPostOffice() || self.addressIsParcelShop() ) {

                } else {
                    $addressField.val( '' );
                }
            }

            if ( needsValidation ) {
                self.validateAddress( address );
            }

            self.refreshCustomerNumberStatus();
        },

        addressIsPackstation: function() {
            var self       = germanized.dhl_parcel_locator,
                addressVal = $( self.wrapper + ' #shipping_address_1' ).val().toLowerCase();

            if ( addressVal.indexOf( self.params.i18n.packstation.toLowerCase() ) >= 0 ) {
                return true;
            }

            return false;
        },

        addressIsPostOffice: function() {
            var self       = germanized.dhl_parcel_locator,
                addressVal = $( self.wrapper + ' #shipping_address_1' ).val().toLowerCase();

            if ( addressVal.indexOf( self.params.i18n.postoffice.toLowerCase() ) >= 0 ) {
                return true;
            }

            return false;
        },

        addressIsParcelShop: function() {
            var self       = germanized.dhl_parcel_locator,
                addressVal = $( self.wrapper + ' #shipping_address_1' ).val().toLowerCase();

            if ( addressVal.indexOf( self.params.i18n.parcelshop.toLowerCase() ) >= 0 ) {
                return true;
            }

            return false;
        },

        shippingMethodSupportsPickupType: function( method, pickupType ) {
            var self     = germanized.dhl_parcel_locator,
                data     = self.getShippingMethodData( method ),
                supports = false;

            if ( data ) {
                if ( $.inArray( pickupType, data.supports ) !== -1 ) {
                    supports = true;
                }
            }

            return supports;
        },

        customerNumberIsMandatory: function() {
            var self = germanized.dhl_parcel_locator;

            if ( ! self.isEnabled() ) {
                return false;
            }

            if ( self.addressIsPackstation() ) {
                return true;
            } else if ( self.addressIsParcelShop() ) {
                return false;
            } else if ( self.addressIsPostOffice() ) {
                return false;
            }

            return false;
        },

        refreshCustomerNumberStatus: function() {
            var self = germanized.dhl_parcel_locator,
                $field = $( self.wrapper + ' #shipping_dhl_postnumber_field' );

            if ( self.customerNumberIsMandatory() ) {
                if ( ! $field.find( 'label span' ).length || ( ! $field.find( 'label span' ).hasClass( 'required' ) ) ) {
                    $field.find( 'label' ).append( ' <span class="required">*</span>' );
                }

                $field.find( 'label span.optional' ).hide();
                $field.addClass( 'validate-required' );
            } else {
                $field.find( 'label span.required' ).remove();
                $field.find( 'label span.optional' ).show();

                $field.removeClass( 'validate-required woocommerce-invalid woocommerce-invalid-required-field' );
            }
         },

        validateAddress: function( addressData ) {
            var self   = germanized.dhl_parcel_locator,
                params = {
                    'action'  : 'woocommerce_gzd_dhl_parcel_locator_validate_address',
                    'address' : addressData,
                    'security': self.params.parcel_locator_nonce
                };

            $.ajax({
                type: "POST",
                url:  self.params.ajax_url,
                data: params,
                success: function( data ) {
                    if ( data.valid ) {
                        $( self.wrapper + ' #shipping_address_1' ).val( data.address );
                        self.refreshCustomerNumberStatus();
                    } else {
                        $( self.wrapper + ' #shipping_address_1' ).val( '' );
                    }
                },
                error: function( data ) {},
                dataType: 'json'
            });
        },

        getShippingMethodData: function( method ) {
            var self = germanized.dhl_parcel_locator;

            if ( self.params.methods.hasOwnProperty( method ) ) {
                return self.params.methods[ method ];
            }

            return false;
        },

        refreshAddressType: function() {
            var self           = germanized.dhl_parcel_locator,
                $addressField  = $( self.wrapper + ' #shipping_address_1_field' ),
                $addressInput  = $( self.wrapper + ' #shipping_address_1' ),
                shippingMethod = self.getShippingMethod(),
                methodData     = self.getShippingMethodData( shippingMethod ),
                address        = $addressInput.val(),
                $spans;

            if ( self.isEnabled() ) {

                if ( methodData ) {
                    $addressInput.data( 'label-dhl', methodData.street_label );
                    $addressInput.data( 'placeholder-dhl', methodData.street_placeholder );
                    $addressInput.data( 'desc-dhl', methodData.finder_button );
                }

                $( self.wrapper + ' #shipping_dhl_postnumber_field' ).show();

                if ( $addressInput.data( 'label-dhl' ) ) {
                    $spans = $addressField.find( 'label span, label abbr' );

                    $addressField.find( 'label' ).html( $addressInput.data( 'label-dhl' ) + ' ' );
                    $addressField.find( 'label' ).append( $spans );
                }

                if ( $addressInput.data( 'placeholder-dhl' ) ) {
                    $addressInput.attr( 'placeholder', $addressInput.data( 'placeholder-dhl' ) );
                }

                if ( $addressInput.data( 'desc-dhl' ) ) {
                    $addressField.find( '.dhl-desc' ).remove();
                    $addressField.find( '.woocommerce-input-wrapper' ).after( '<p class="desc dhl-desc">' + $addressInput.data( 'desc-dhl' ) + '</p>' );
                }

                if ( address.length > 0 ) {
                    self.formatAddress();
                }
            } else {
                $( self.wrapper + ' #shipping_dhl_postnumber_field' ).hide();

                if ( $addressInput.data( 'label-regular' ) ) {
                    $spans = $addressField.find( 'label span, label abbr' );

                    $addressField.find( 'label' ).html( $addressInput.data( 'label-regular' ) + ' ' );
                    $addressField.find( 'label' ).append( $spans );
                }

                if ( $addressInput.data( 'placeholder-regular' ) ) {
                    $addressInput.attr( 'placeholder', $addressInput.data( 'placeholder-regular' ) );
                }

                $addressField.find( '.dhl-desc' ).remove();
            }
        },

        isEnabled: function() {
            var self = germanized.dhl_parcel_locator;

            return self.isAvailable() && $( self.wrapper + ' #shipping_address_type' ).val() === 'dhl';
        },

        getShippingMethod: function( pwithInstanceId ) {
            var current        = '';
            var withInstanceId = pwithInstanceId ? pwithInstanceId : true;

            if ( $( 'select.shipping_method' ).length > 0 ) {
                current = $( 'select.shipping_method' ).val();
            } else if ( $( 'input[name^="shipping_method"]:checked' ).length > 0 ) {
                current = $( 'input[name^="shipping_method"]:checked' ).val();
            } else if ( $( 'input[name^="shipping_method"][type="hidden"]' ).length > 0 ) {
                current = $( 'input[name^="shipping_method"][type="hidden"]' ).val();
            }

            if ( ! withInstanceId ) {
                if ( 'undefined' !== typeof current && current.length > 0 ) {
                    var currentParts = current.split(':');

                    if ( currentParts.length > 0 ) {
                        current = currentParts[0];
                    }
                }
            } else {
                // In case an instance id is needed but missing - assume 0 as instance id
                if ( 'undefined' !== typeof current && current.length > 0 ) {
                    var currentParts = current.split(':');

                    if ( currentParts.length <= 1 ) {
                        current = current + ':0';
                    }
                }
            }

            return current;
        },

        pickupTypeIsAvailable: function( pickupType ) {
            var self            = germanized.dhl_parcel_locator,
                shippingMethod  = self.getShippingMethod(),
                isAvailable     = true;

            if ( ! self.shippingMethodSupportsPickupType( shippingMethod, pickupType ) ) {
                isAvailable = false;
            }

            return isAvailable;
        },

        isAvailable: function() {
            var self            = germanized.dhl_parcel_locator,
                shippingCountry = $( self.wrapper + ' #shipping_country' ).val(),
                shippingMethod  = self.getShippingMethod(),
                methodData      = self.getShippingMethodData( shippingMethod ),
                isAvailable     = true;

            if ( $.inArray( shippingCountry, self.params.supported_countries ) === -1 ) {
                isAvailable = false;
            }

            if ( self.isCheckout() ) {
                if ( ! methodData || methodData.supports.length === 0 ) {
                    isAvailable = false;
                }
            }

            return isAvailable;
        }
    };

    $( document ).ready( function() {
        germanized.dhl_parcel_locator.init();
    });

})( jQuery, window.germanized );
