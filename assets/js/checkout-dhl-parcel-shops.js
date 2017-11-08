jQuery( function( $ ) {

    var wc_gzd_dhl_parcel_shops = {

        $parcelFinderButtonWrapper: $( wc_gzd_dhl_parcel_shops_params.button_wrapper ),
        $parcelFinderWrapper: $( wc_gzd_dhl_parcel_shops_params.iframe_wrapper ),
        $parcelCheckboxField: $( '#shipping_parcelshop_field' ),
        params: wc_gzd_dhl_parcel_shops_params,
        defaultAddressFieldLabel: '',
        defaultAddressFieldPlaceholder: '',
        supportedCountries: [],

        init: function() {

            if ( wc_gzd_dhl_parcel_shops.params.enable_finder ) {
                this.initParcelFinder();
            }

            this.initDefaultData();

            this.$parcelCheckboxField.on( 'change', '#shipping_parcelshop', this.parcelCheckboxChanged );
            $( document ).on( 'change', '#shipping_country', this.shippingCountryChanged );

            this.parcelCheckboxChanged( true );
        },

        initDefaultData: function() {
            this.supportedCountries = this.params.supported_countries;
            this.defaultAddressFieldLabel = $( '#shipping_address_1_field' ).find( 'label' ).contents().filter( function() {
                return this.nodeType == 3;
            }).text();
            this.defaultAddressFieldPlaceholder = $( '#shipping_address_1' ).attr( 'placeholder' );
        },

        shippingCountryChanged: function() {

            var country = $( '#shipping_country' ).val();

            if ( $.inArray( country, wc_gzd_dhl_parcel_shops.params.supported_countries ) !== -1 ) {
                wc_gzd_dhl_parcel_shops.$parcelCheckboxField.show();
            } else {
                $( '#shipping_parcelshop' ).prop( 'checked', false );
                wc_gzd_dhl_parcel_shops.$parcelCheckboxField.hide();
            }

            wc_gzd_dhl_parcel_shops.parcelCheckboxChanged( false );
        },

        parcelCheckboxChanged: function( afterLoad ) {

            if ( typeof afterLoad === 'undefined' || $.type( afterLoad ) !== 'boolean' ) {
                afterLoad = false;
            }

            var label = $( '#shipping_address_1_field' ).find( 'label' );

            if ( $( '#shipping_parcelshop' ).is( ':checked' ) ) {

                wc_gzd_dhl_parcel_shops.showFinderButton();

                // Check if checkbox is checked on load (customer has already chosen option before so there is no need to clean the address input)
                if ( ! afterLoad && wc_gzd_dhl_parcel_shops.$parcelCheckboxField.hasClass( 'first-check' ) ) {
                    $( '#shipping_address_1' ).val( '' );
                    wc_gzd_dhl_parcel_shops.$parcelCheckboxField.removeClass( 'first-check' );
                } else if ( afterLoad ) {
                    wc_gzd_dhl_parcel_shops.$parcelCheckboxField.removeClass( 'first-check' );
                }

                $( '#shipping_parcelshop_post_number_field' ).show();

                label.contents().filter( function() {
                    return this.nodeType == 3;
                }).first().replaceWith( wc_gzd_dhl_parcel_shops.params.address_field_title + ' ' );

                $( '#shipping_address_1' ).attr( 'placeholder', wc_gzd_dhl_parcel_shops.params.address_field_placeholder );

                if ( $( '#shipping_country' ).val() === 'DE' ) {
                    $( '#shipping_address_2' ).val( '' );
                    $( '#shipping_address_2' ).addClass( 'gzd-hidden' ).hide();
                } else {
                    $( '#shipping_address_2' ).show();
                }

            } else {

                wc_gzd_dhl_parcel_shops.hideFinderButton();

                label.contents().filter( function() {
                    return this.nodeType == 3;
                }).first().replaceWith( wc_gzd_dhl_parcel_shops.defaultAddressFieldLabel );

                if ( $( '#shipping_address_2' ).hasClass( 'gzd-hidden' ) ) {
                    $( '#shipping_address_2' ).show();
                    $( '#shipping_address_2' ).removeClass( 'gzd-hidden' );
                }

                $( '#shipping_address_1' ).attr( 'placeholder', wc_gzd_dhl_parcel_shops.defaultAddressFieldPlaceholder );
                $( '#shipping_parcelshop_post_number_field' ).hide();
            }

        },

        initParcelFinder: function() {

            this.$parcelFinderButtonWrapper.hide();
            this.$parcelFinderWrapper.find( '#wc-gzd-parcel-finder-background-overlay' ).hide();
            this.$parcelFinderWrapper.on( 'click', '#wc-gzd-parcel-finder-background-overlay', this.closeParcelFinder );
            this.$parcelFinderWrapper.on( 'click', '#wc-gzd-parcel-finder-close-btn', this.closeParcelFinder );
            this.$parcelFinderButtonWrapper.on( 'click', '.wc-gzd-parcel-finder-open-button', this.openParcelFinder );

            $( window ).on( 'message', this.saveParcelFinder );
        },

        showFinderButton: function() {

            if ( wc_gzd_dhl_parcel_shops.params.enable_finder ) {
                wc_gzd_dhl_parcel_shops.$parcelFinderButtonWrapper.show();
            }

        },

        hideFinderButton: function() {

            if ( wc_gzd_dhl_parcel_shops.params.enable_finder ) {
                wc_gzd_dhl_parcel_shops.$parcelFinderButtonWrapper.hide();
            }

        },

        isValidJSON: function(str) {
            try {
                JSON.parse(str);
            } catch (e) {
                return false;
            }
            return true;
        },

        saveParcelFinder: function(e) {

            if ( e.originalEvent.data === "undefined" ) {
                return;
            }

            if ( ! wc_gzd_dhl_parcel_shops.isValidJSON( e.originalEvent.data ) ) {
                return;
            }

            var c = JSON.parse( e.originalEvent.data );

            if ( typeof c !== 'object' )
                return;

            if ( ! c.countryCode )
                return;

            var country = c.countryCode.toUpperCase();

            $( '.wc-gzd-parcel-finder-shipping-country-error' ).remove();

            if ( $.inArray( country, wc_gzd_dhl_parcel_shops.supportedCountries ) !== -1 ) {
                $( '#shipping_country' ).val( c.countryCode.toUpperCase() ).trigger( 'change' );
                $( '#shipping_address_1' ).val( c.keyWord + ' ' + c.primaryKeyZipRegion );
                $( '#shipping_address_2' ).val( c.street + ' ' + c.houseNo );
                $( '#shipping_city' ).val( c.city );
                $( '#shipping_postcode' ).val( c.zipCode );
            } else {

                if ( $( '.woocommerce-error' ).length > 0 ) {
                    $( '.woocommerce-error' ).append( '<li class="wc-gzd-parcel-finder-shipping-country-error">' + wc_gzd_dhl_parcel_shops.params.shipping_country_error + '</li>' );
                } else {
                    $( 'form.woocommerce-checkout' ).prepend( '<ul class="woocommerce-error wc-gzd-parcel-finder-shipping-country-error"><li>' + wc_gzd_dhl_parcel_shops.params.shipping_country_error + '</li></ul>' );
                }

                // Scroll to top
                $( 'html, body' ).animate( {
                    scrollTop: ( $( 'form.checkout' ).offset().top - 100 )
                }, 1000 );
            }

            if( c.countryCode === "de" ) {
                $( '#shipping_address_2' ).val( "" );
            }

            wc_gzd_dhl_parcel_shops.closeParcelFinder();

        },

        openParcelFinder: function() {
            wc_gzd_dhl_parcel_shops.$parcelFinderWrapper.find( 'iframe' ).attr( 'src', wc_gzd_dhl_parcel_shops.params.iframe_src );
            wc_gzd_dhl_parcel_shops.$parcelFinderWrapper.find( '#wc-gzd-parcel-finder-background-overlay' ).show();
            return false;
        },

        closeParcelFinder: function() {
            wc_gzd_dhl_parcel_shops.$parcelFinderWrapper.find( '#wc-gzd-parcel-finder-background-overlay' ).hide();
        }
    };

    wc_gzd_dhl_parcel_shops.init();

});