jQuery( function( $ ) {

    var wc_gzd_direct_debit = {

        $checkoutForm: $( 'form.checkout, form#order_review' ),

        init: function() {
            this.$checkoutForm.on( 'blur input change', '#direct-debit-form input#direct-debit-account-holder', this.onValidateHolder );
            this.$checkoutForm.on( 'blur input change', '#direct-debit-form input#direct-debit-account-iban', this.onValidateIBAN );
            this.$checkoutForm.on( 'blur input change', '#direct-debit-form input#direct-debit-account-bic', this.onValidateSWIFT );
            this.$checkoutForm.on( 'blur input change', 'input, select', this.maybeShowCheckbox );

            $( 'body' ).bind( 'updated_checkout', this.maybeShowCheckbox );
            $( document ).on( 'click', 'a#show-direct-debit-trigger', this.onPrettyPhotoOpen );

            this.initPrettyPhoto();
        },

        onValidateIBAN: function() {
            var self = wc_gzd_direct_debit;

            if ( ! self.isValidIBAN( $( this ).val() ) ) {
                $( this ).parents( 'p.form-row' ).removeClass( 'woocommerce-validated' );
                $( this ).parents( 'p.form-row' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
            }
        },

        onValidateSWIFT: function() {
            var self = wc_gzd_direct_debit;

            if ( ! self.isValidSWIFT( $( this ).val() ) ) {
                $( this ).parents( 'p.form-row' ).removeClass( 'woocommerce-validated' );
                $( this ).parents( 'p.form-row' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
            }
        },

        onValidateHolder: function() {
            if ( ! $( this ).val() ) {
                $( this ).parents( 'p.form-row' ).removeClass( 'woocommerce-validated' );
                $( this ).parents( 'p.form-row' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
            }
        },

        isValidIBAN: function( iban ) {
            return IBAN.isValid( iban );
        },

        isValidSWIFT: function( swift ) {
            var regSWIFT = /^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/;
            return regSWIFT.test( swift );
        },

        maybeShowCheckbox: function() {
            var self = wc_gzd_direct_debit;

            // Hide by default
            $( '.direct-debit-checkbox' ).hide();

            if ( $( '#direct-debit-form' ).length ) {

                if ( $( '#payment_method_direct-debit' ).is( ':checked' ) &&
                    $( 'input#direct-debit-account-holder' ).val() &&
                    $( 'input#direct-debit-account-iban' ).val() &&
                    $( 'input#direct-debit-account-bic' ).val()
                ) {
                    $( '.direct-debit-checkbox' ).show();
                    self.initPrettyPhoto();
                }
            }
        },

        onPrettyPhotoOpen: function( e ) {
            e.preventDefault();

            var url = $( this ).attr( 'href' );

            var data		= {
                country: 		$( '#billing_country' ).val(),
                postcode:		$( 'input#billing_postcode' ).val(),
                city:			$( '#billing_city' ).val(),
                address:		$( 'input#billing_address_1' ).val(),
                address_2:		$( 'input#billing_address_2' ).val(),
                debit_holder:	$( 'input#direct-debit-account-holder' ).val(),
                debit_iban: 	$( 'input#direct-debit-account-iban' ).val(),
                debit_swift: 	$( 'input#direct-debit-account-bic' ).val(),
                user:			$( 'input#createaccount' ).val()
            };

            url += '&ajax=true&' + jQuery.param( data );

            $( '#show-direct-debit-pretty' ).attr( 'href', url );
            $( '#show-direct-debit-pretty' ).trigger( 'click' );
        },

        initPrettyPhoto: function() {
            $( 'a#show-direct-debit-pretty' ).prettyPhoto({
                social_tools: false,
                theme: 'pp_woocommerce',
                horizontal_padding: 20,
                opacity: 0.8,
                deeplinking: false
            });
        }
    };

    wc_gzd_direct_debit.init();
});