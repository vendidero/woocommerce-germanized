
window.shiptastic = window.shiptastic || {};
window.shiptastic.shipments_pickup_locations = window.shiptastic.shipments_pickup_locations || {};

( function( $, shipments ) {

    /**
     * Core
     */
    shipments.shipments_pickup_locations = {

        params: {},
        pickupLocations: {},
        available: false,
        currentProvider: '',
        hasPickupLocation: false,

        init: function () {
            var self  = shipments.shipments_pickup_locations;
            self.params  = wc_shiptastic_pickup_locations_params;

            var $pickupSelect = self.getPickupLocationSelect();

            if ( $pickupSelect.length > 0 ) {
                if ( $pickupSelect.attr( 'data-locations' ) ) {
                    try {
                        self.pickupLocations = JSON.parse( $pickupSelect.attr( 'data-locations' ) );
                    } catch (e) {
                        self.pickupLocations = {};
                    }
                }
                if ( $pickupSelect.attr( 'data-provider' ) ) {
                    self.currentProvider = $pickupSelect.attr( 'data-provider' );
                }
            }

            if ( $( '#current_pickup_location' ).length > 0 ) {
                self.available = $( '.choose-pickup-location:visible' ).length > 0 || $( '.currently-shipping-to:visible' ).length > 0;

                $( document.body ).on( 'updated_checkout', self.afterRefreshCheckout );
                $( document ).on( 'change', '#ship-to-different-address-checkbox', self.onSelectDifferentShipping );
                $( document ).on( 'submit', '#wc-shiptastic-pickup-location-search-form', self.onSearch );
                $( document ).on( 'click', '.submit-pickup-location', self.onSelectPickupLocation );
                $( document ).on( 'change', '#current_pickup_location', self.onChangeCurrentPickupLocation );
                $( document ).on( 'click', '.pickup-location-remove', self.onRemovePickupLocation );
                $( document ).on( 'change', '#pickup_location', self.onChangePickupLocation );
                $( document ).on( 'change', '#billing_postcode, #shipping_postcode', self.onChangeAddress );

                self.onChangeCurrentPickupLocation();
                self.onChangePickupLocation();
                self.maybeInitSelect2();
            }
        },

        onChangeAddress: function() {
            var self= shipments.shipments_pickup_locations,
                postcode = $( '#shipping_postcode:visible' ).val() ? $( '#shipping_postcode:visible' ).val() : $( '#billing_postcode' ).val();

            $( '#pickup-location-postcode' ).val( postcode );
        },

        onChangePickupLocation: function() {
            var self= shipments.shipments_pickup_locations,
                $pickupSelect = self.getPickupLocationSelect();

            if ( $pickupSelect.val() ) {
                $( '.pickup-location-search-actions' ).find( '.submit-pickup-location' ).removeClass( 'hidden' ).show();
            } else {
                $( '.pickup-location-search-actions' ).find( '.submit-pickup-location' ).addClass( 'hidden' ).hide();
            }
        },

        hasPickupLocationDelivery: function() {
            var self     = shipments.shipments_pickup_locations,
                $current = $( '#current_pickup_location' ),
                currentCode = $current.val();

            if ( currentCode ) {
                return true;
            }

            return false;
        },

        disablePickupLocationDelivery: function( withNotice = false ) {
            var self= shipments.shipments_pickup_locations,
                $modal = $( '.wc-stc-modal-content[data-id="pickup-location"].active' );

            $( '.wc-shiptastic-managed-by-pickup-location' ).val( '' );
            $( '#current_pickup_location' ).val( '' ).trigger( 'change' );

            if ( $modal.length > 0 ) {
                $modal.find( '.wc-stc-modal-close' ).trigger( 'click' );
            }

            if ( withNotice ) {
                var $form = $( 'form.checkout' );

                if ( $form.find( '.woocommerce-NoticeGroup-updateOrderReview' ).length <= 0 ) {
                    $form.prepend( '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-updateOrderReview"></div>' );
                }

                $form.find( '.woocommerce-NoticeGroup-updateOrderReview' ).prepend( '<div class="woocommerce-info">' + self.params.i18n_pickup_location_delivery_unavailable + '</div>' );

                var scrollElement = $( '.woocommerce-NoticeGroup-updateOrderReview' );

                $.scroll_to_notices( scrollElement );
            }
        },

        onRemovePickupLocation: function() {
            var self= shipments.shipments_pickup_locations;

            self.disablePickupLocationDelivery();

            return false;
        },

        getCustomerNumberField: function() {
            return $( '#pickup_location_customer_number_field' );
        },

        onChangeCurrentPickupLocation: function() {
            var self     = shipments.shipments_pickup_locations,
                $current = $( '#current_pickup_location' ),
                currentCode = $current.val(),
                currentPickupLocation = currentCode ? self.getPickupLocation( currentCode ) : false,
                $notice = $( '.pickup_location_notice' );

            if ( currentCode && currentPickupLocation ) {
                $current.attr( 'data-current-location', JSON.stringify( currentPickupLocation ) );

                self.replaceShippingAddress( currentPickupLocation.address_replacements );
                self.updateCustomerNumberField( currentPickupLocation );

                $notice.find( '.pickup-location-manage-link' ).text( currentPickupLocation.label );
                $notice.find( '.currently-shipping-to' ).show();
                $notice.find( '.choose-pickup-location' ).hide();

                $( '#wc-shiptastic-pickup-location-search-form .pickup-location-remove' ).removeClass( 'hidden' ).show();

                $( document.body ).trigger( 'shiptastic_current_pickup_location', currentPickupLocation );
            } else {
                $current.attr( 'data-current-location', '' );
                $current.val( '' );

                self.getCustomerNumberField().addClass( 'hidden' );
                self.getCustomerNumberField().hide();

                $( '.wc-shiptastic-managed-by-pickup-location' ).find( 'input[type=text]' ).val( '' );
                $( '.wc-shiptastic-managed-by-pickup-location' ).find( ':input' ).prop( 'readonly', false );

                $( '#wc-shiptastic-pickup-location-search-form .pickup-location-remove' ).addClass( 'hidden' ).hide();

                $( '.wc-shiptastic-managed-by-pickup-location' ).removeClass( 'wc-shiptastic-managed-by-pickup-location' );
                $( '.wc-shiptastic-managed-by-pickup-location-notice' ).remove();

                $notice.find( '.currently-shipping-to' ).hide();
                $notice.find( '.choose-pickup-location' ).show();

                $( document.body ).trigger( 'shiptastic_current_pickup_location', currentPickupLocation );
            }
        },

        onSearch: function() {
            var self     = shipments.shipments_pickup_locations,
                $form      = $( this ),
                params     = $form.serialize(),
                $pickupSelect = self.getPickupLocationSelect(),
                current = $pickupSelect.val(),
                oldLocations = self.pickupLocations;

            $( '#wc-shiptastic-pickup-location-search-form' ).block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            params += '&action=woocommerce_stc_search_pickup_locations&context=' + self.params.context;

            $.ajax({
                type: "POST",
                url:  self.params.wc_ajax_url.toString().replace('%%endpoint%%', 'woocommerce_stc_search_pickup_locations'),
                data: params,
                success: function( data ) {
                    if ( data.success ) {
                        self.pickupLocations = data.locations;
                        self.updatePickupLocationSelect( oldLocations );

                        $( '#wc-shiptastic-pickup-location-search-form' ).unblock();
                    }
                },
                error: function( data ) {},
                dataType: 'json'
            });

            return false;
        },

        updatePickupLocationSelect: function( oldLocations = [] ) {
            var self     = shipments.shipments_pickup_locations,
                $pickupSelect = self.getPickupLocationSelect(),
                current = $pickupSelect.val();

            try {
                $pickupSelect.attr('data-locations', JSON.stringify( self.pickupLocations ) );
            } catch (e) {}

            $pickupSelect.find( 'option:not([value=""])' ).remove();

            $.each( self.pickupLocations, function( code, pickupLocation ) {
                var label = $( '<textarea />' ).html( pickupLocation.formatted_address ).text();
                $pickupSelect.append( $( "<option></option>" ).attr("value", code ).text( label ) );
            });

            var currentLocation = self.getPickupLocation( current, false );

            if ( currentLocation ) {
                $pickupSelect.find( 'option[value="' + currentLocation.code + '"' )[0].selected = true;
            }

            /**
             * Do only trigger select change if available pickup locations really changed
             * to prevent possible incompatibilities with other extensions.
             */
            try {
                if ( JSON.stringify( self.pickupLocations ) !== JSON.stringify( oldLocations ) ) {
                    $pickupSelect.trigger( 'change' );
                }
            } catch (e) {}
        },

        onSelectDifferentShipping: function() {
            var self= shipments.shipments_pickup_locations;

            if ( ! $( this ).is( ':checked' ) ) {
                self.disablePickupLocationDelivery();

                if ( self.isAvailable() ) {
                    $( '#billing_pickup_location_notice' ).removeClass( 'hidden' ).show();
                }
            } else {
                $( '#billing_pickup_location_notice' ).addClass( 'hidden' ).hide();
            }
        },

        maybeInitSelect2: function() {
            if ( $().selectWoo ) {
                $( 'select#pickup_location' ).each( function() {
                    var $this = $( this );

                    var select2_args = {
                        placeholder: $this.attr( 'data-placeholder' ) || $this.attr( 'placeholder' ) || '',
                        label: $this.attr( 'data-label' ) || null,
                        width: '100%',
                        dropdownCssClass: "wc-stc-pickup-location-select-dropdown"
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
            var self = shipments.shipments_pickup_locations,
                $pickupSelect  = self.getPickupLocationSelect(),
                current = $pickupSelect.val();

            $( '#current_pickup_location' ).val( current ).trigger( 'change' );
            $( this ).parents( '.wc-stc-modal-content' ).find( '.wc-stc-modal-close' ).trigger( 'click' );

            var scrollElement = $( '#shipping_address_1_field' );

            $.scroll_to_notices( scrollElement );

            return false;
        },

        updateCustomerNumberField: function( currentLocation ) {
            var self = shipments.shipments_pickup_locations,
                $customerNumberField = self.getCustomerNumberField();

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

        getPickupLocation: function( locationCode, allowFallback = true ) {
            var self = shipments.shipments_pickup_locations;

            if ( self.pickupLocations.hasOwnProperty( locationCode ) ) {
                return self.pickupLocations[ locationCode ];
            } else if ( allowFallback ) {
                var $select = $( '#current_pickup_location' );

                if ( $select.attr( 'data-current-location' ) ) {
                    try {
                        var currentLocation = JSON.parse( $select.attr( 'data-current-location' ) );

                        if ( currentLocation.code === locationCode ) {
                            return currentLocation;
                        }
                    } catch (e) {}
                }
            }

            return false;
        },

        afterRefreshCheckout: function( e, ajaxData ) {
            var self = shipments.shipments_pickup_locations,
                supportsPickupLocationDelivery = false,
                oldLocations = self.pickupLocations,
                oldProvider = self.currentProvider;

            ajaxData = ( typeof ajaxData === 'undefined' ) ? {
                'fragments': {
                    '.wc-shiptastic-current-provider': '',
                    '.wc-shiptastic-pickup-location-supported': false,
                    '.wc-shiptastic-pickup-locations': JSON.stringify( self.pickupLocations ),
                }
            } : ajaxData;

            if ( ajaxData.hasOwnProperty( 'fragments' ) ) {
                if ( ajaxData.fragments.hasOwnProperty( '.wc-shiptastic-current-provider' ) ) {
                    self.currentProvider = ajaxData.fragments['.wc-shiptastic-current-provider'];
                }

                if ( ! self.currentProvider || oldProvider !== self.currentProvider ) {
                    if ( self.hasPickupLocationDelivery() ) {
                        self.disablePickupLocationDelivery( true );
                    }
                }

                if ( ajaxData.fragments.hasOwnProperty( '.wc-shiptastic-pickup-location-supported' ) ) {
                    supportsPickupLocationDelivery = ajaxData.fragments['.wc-shiptastic-pickup-location-supported'];
                }

                if ( ajaxData.fragments.hasOwnProperty( '.wc-shiptastic-pickup-locations' ) ) {
                    self.pickupLocations = JSON.parse( ajaxData.fragments['.wc-shiptastic-pickup-locations'] );

                    self.updatePickupLocationSelect( oldLocations );
                }
            }

            if ( ! supportsPickupLocationDelivery ) {
                self.disable();
            } else {
                self.enable();
            }
        },

        disable: function() {
            var self = shipments.shipments_pickup_locations;

            self.available = false;

            if ( self.hasPickupLocationDelivery() ) {
                self.disablePickupLocationDelivery( true );
            }

            $( '.pickup_location_notice' ).addClass( 'hidden' ).hide();
        },

        enable: function() {
            var self = shipments.shipments_pickup_locations;

            self.available = true;

            $( '.pickup_location_notice' ).removeClass( 'hidden' ).show();

            if ( $( '#ship-to-different-address-checkbox' ).is( ':checked' ) || self.hasPickupLocationDelivery() ) {
                $( '#billing_pickup_location_notice' ).addClass( 'hidden' ).hide();
            } else {
                $( '#billing_pickup_location_notice' ).removeClass( 'hidden' ).show();
            }
        },

        isAvailable: function() {
            var self= shipments.shipments_pickup_locations;

            return self.available;
        },

        replaceShippingAddress: function( replacements ) {
            var self = shipments.shipments_pickup_locations,
                $shipToDifferent = $( '#ship-to-different-address input' ),
            hasChanged = [];

            Object.keys( replacements ).forEach( addressField => {
                var value = replacements[ addressField ];

                if ( $( '#shipping_' + addressField ).length > 0 ) {
                    if ( $( '#shipping_' + addressField ).val() !== value ) {
                        hasChanged.push( addressField );
                    }

                    $( '#shipping_' + addressField ).val( value );
                    $( '#shipping_' + addressField ).prop( 'readonly', true );

                    if ( 'country' === addressField ) {
                        $( '#shipping_' + addressField ).trigger( 'change' ); // select2 needs a change event
                    }

                    var $row = $( '#shipping_' + addressField + '_field' );

                    if ( $row.length > 0 ) {
                        $row.addClass( 'wc-shiptastic-managed-by-pickup-location' );

                        if ( $row.find( '.wc-shiptastic-managed-by-pickup-location-notice' ).length <= 0 ) {
                            $row.find( 'label' ).after( '<span class="wc-shiptastic-managed-by-pickup-location-notice">' + self.params.i18n_managed_by_pickup_location + '</span>' );
                        }
                    } else {
                        $( '#shipping_' + addressField ).addClass( 'wc-shiptastic-managed-by-pickup-location' );
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
        shipments.shipments_pickup_locations.init();
    });

})( jQuery, window.shiptastic );
