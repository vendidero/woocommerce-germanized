
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
                .on( 'change.dhl', self.wrapper + ' #shipping_address_2', self.onChangeAddress )
                .on( 'change.dhl', self.wrapper + ' #shipping_postcode', self.onChangeAddress )
                .on( 'change.dhl', self.wrapper + ' #shipping_country', self.onChangeAddress )
                .on( 'change.dhl', self.wrapper + ' #ship-to-different-address-checkbox', self.onChangeShipping )
                .on( 'change.dhl', self.wrapper + ' #shipping_country', self.refreshAvailability )
                .on( 'input.dhl validate.dhl change.dhl', self.wrapper + ' #shipping_dhl_postnumber', self.validatePostnumber );

            $( document.body ).on( 'payment_method_selected', self.triggerCheckoutRefresh );
            $( document.body ).on( 'updated_checkout', self.afterRefreshCheckout );

            if ( ! self.isCheckout() ) {
                $( document.body ).on( 'country_to_state_changing', function() {
                    var self = germanized.dhl_parcel_locator;

                    setTimeout( function() {
                        self.refreshAddressType();
                    }, 500 );
                } );
            }

            self.refreshAvailability();
            self.refreshAddressType();
        },

        validatePostnumber: function( e ) {
            var $this = $( this ),
                $parent = $this.closest( '.form-row' ),
                eventType = e.type;

            if ( 'input' === eventType ) {
                if ( $this.val() ) {
                    $this.val( $this.val().replace( /\D/g,'' ) );
                }
            }

            if ( 'validate' === eventType || 'change' === eventType ) {
                if ( $this.val() ) {
                    $this.val( $this.val().replace( /\D/g,'' ) );

                    if ( $this.val().toString().length < 6 || $this.val().toString().length > 12 ) {
                        $parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-postnumber' );
                    }
                }
            }
        },

        triggerCheckoutRefresh: function() {
            $( document.body ).trigger( 'update_checkout' );
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
                country         = $( self.wrapper + ' #shipping_country' ).val(),
                fieldKey        = self.getPickupFieldKey( country ),
                addressRelevant = $( self.wrapper + ' #shipping_' + fieldKey ).val();

            if ( addressRelevant.length > 0 ) {
                needsValidation = true;
            }

            if ( needsValidation ) {
                self.validateAddress( {
                    'address_1' : $( self.wrapper + ' #shipping_address_1' ).val(),
                    'address_2' : $( self.wrapper + ' #shipping_address_2' ).val(),
                    'country'   : country,
                    'postcode'  : $( self.wrapper + ' #shipping_postcode' ).val(),
                } );
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

        customerNumberIsMandatory: function( country ) {
            var self = germanized.dhl_parcel_locator;

            if ( ! self.isEnabled() ) {
                return false;
            }

            if ( 'DE' === country && self.addressIsPackstation() ) {
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

            if ( self.customerNumberIsMandatory( $( self.wrapper + ' #shipping_country' ).val() ) ) {
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
                },
                $addressField = $( self.wrapper + ' #shipping_address_1' ),
                $address2Field = $( self.wrapper + ' #shipping_address_2' ),
                $relevantAddressField = $( self.wrapper + ' #shipping_' + self.getPickupFieldKey( addressData['country'] ) );

            var $addressFieldWrapper = $relevantAddressField.closest( '.form-row' );

            $addressFieldWrapper.removeClass( 'woocommerce-validated' );
            $addressFieldWrapper.removeClass( 'woocommerce-invalid' );

            $.ajax({
                type: "POST",
                url:  self.params.ajax_url,
                data: params,
                success: function( data ) {
                    if ( data.valid ) {
                        $addressField.val( data.address_1 );

                        if ( $address2Field.length > 0 ) {
                            $address2Field.val( data.address_2 );
                        }

                        $addressFieldWrapper.addClass( 'woocommerce-validated' );
                        self.refreshCustomerNumberStatus();
                    } else {
                        if ( data.messages ) {
                            var $form = self.isCheckout() ? $( 'form.checkout' ) : $( self.wrapper ).closest( 'form' );

                            // Remove notices from all sources
                            $( '.woocommerce-NoticeGroup-pickup' ).remove();

                            // Add new errors returned by this event
                            if ( data.messages ) {
                                $form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-pickup"><ul class="woocommerce-error" role="alert">' + data.messages + '</ul></div>' ); // eslint-disable-line max-len

                                var scrollElement = $( '.woocommerce-NoticeGroup-woocommerce-NoticeGroup-pickup, .woocommerce-NoticeGroup-checkout' );

                                if ( ! scrollElement.length ) {
                                    scrollElement = $form;
                                }

                                $.scroll_to_notices( scrollElement );
                            }
                        }

                        $addressFieldWrapper.addClass( 'woocommerce-invalid' );
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

        getPickupFieldKey: function( country ) {
            var self = germanized.dhl_parcel_locator;

            if ( self.params.pickup_address_field_keys.hasOwnProperty( country ) ) {
                return self.params.pickup_address_field_keys[ country ];
            }

            return 'address_1';
        },

        refreshAddressType: function() {
            var self           = germanized.dhl_parcel_locator,
                country        = $( self.wrapper + ' #shipping_country' ).val(),
                fieldKey       = self.getPickupFieldKey( country ),
                $addressField  = $( self.wrapper + ' #shipping_' + fieldKey + '_field' ),
                $addressInput  = $( self.wrapper + ' #shipping_' + fieldKey ),
                shippingMethod = self.getShippingMethod(),
                methodData     = self.getShippingMethodData( shippingMethod ),
                address        = $addressInput.val(),
                $spans;

            $( self.wrapper + ' #shipping_dhl_postnumber_field' ).hide();

            $( self.wrapper + ' :input[data-label-dhl]' ).each( function() {
                var $input        = $( this ),
                    $inputWrapper = $input.closest( '.form-row' );

                if ( $input.data( 'label-regular' ) ) {
                    $spans = $inputWrapper.find( 'label span, label abbr' );

                    $inputWrapper.find( 'label' ).html( $input.data( 'label-regular' ) + ' ' );
                    $inputWrapper.find( 'label' ).append( $spans );
                }

                if ( $input.data( 'placeholder-regular' ) ) {
                    $input.attr( 'placeholder', $input.data( 'placeholder-regular' ) );
                }

                $inputWrapper.find( '.dhl-desc' ).remove();
            } );

            if ( self.isEnabled() ) {
                if ( methodData ) {
                    $addressInput.data( 'label-dhl', methodData.street_label );
                    $addressInput.data( 'placeholder-dhl', methodData.street_placeholder );
                    $addressInput.data( 'desc-dhl', methodData.finder_button );
                }

                if ( 'DE' === country ) {
                    $( self.wrapper + ' #shipping_dhl_postnumber_field' ).show();
                    self.refreshCustomerNumberStatus();
                }

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
            }
        },

        isEnabled: function() {
            var self = germanized.dhl_parcel_locator;

            return self.isAvailable() && $( self.wrapper + ' #shipping_address_type' ).val() === 'dhl';
        },

        getPaymentMethod: function() {
            var $selected = $( '.payment_methods .input-radio:checked' );

            if ( $selected ) {
                return $selected.val();
            }

            return '';
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
                paymentMethod   = self.getPaymentMethod(),
                methodData      = self.getShippingMethodData( shippingMethod ),
                isAvailable     = true;

            if ( $.inArray( paymentMethod, self.params.excluded_gateways ) !== -1 ) {
                isAvailable = false;
            }

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
