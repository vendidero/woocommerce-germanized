jQuery( function( $ ) {

    var wc_gzd_direct_debit = {

        $checkoutForm: $( 'form.checkout, form#order_review' ),
        params: {},

        init: function() {
            this.params = direct_debit_params;

            this.$checkoutForm.on( 'blur input change', '#direct-debit-form input#direct-debit-account-holder', this.onValidateHolder );
            this.$checkoutForm.on( 'blur input change', '#direct-debit-form input#direct-debit-account-iban', this.onValidateIBAN );
            this.$checkoutForm.on( 'blur input change', '#direct-debit-form input#direct-debit-account-bic', this.onValidateSWIFT );
            this.$checkoutForm.on( 'blur input change', 'input, select', this.maybeShowCheckbox );

            $( document.body ).on( 'updated_checkout', this.maybeShowCheckbox );
            $( document ).on( 'click', 'a#show-direct-debit-trigger', this.onPrettyPhotoOpen );

            this.initPrettyPhoto();
        },

        onValidateIBAN: function() {
            var self     = wc_gzd_direct_debit,
                $wrapper = $( this ).parents( 'p.form-row' );

            if ( ! self.isValidIBAN( $( this ).val() ) ) {
                $wrapper.removeClass( 'woocommerce-validated' );
                $wrapper.addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
            } else {
                $wrapper.addClass( 'woocommerce-validated' );
                $wrapper.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
            }
        },

        onValidateSWIFT: function() {
            var self     = wc_gzd_direct_debit,
                $wrapper = $( this ).parents( 'p.form-row' );

            if ( ! self.isValidSWIFT( $( this ).val() ) ) {
                $wrapper.removeClass( 'woocommerce-validated' );
                $wrapper.addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
            } else {
                $wrapper.addClass( 'woocommerce-validated' );
                $wrapper.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
            }
        },

        onValidateHolder: function() {
            var self     = wc_gzd_direct_debit,
                $wrapper = $( this ).parents( 'p.form-row' );

            if ( ! $( this ).val() ) {
                $wrapper.removeClass( 'woocommerce-validated' );
                $wrapper.addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
            } else {
                $wrapper.addClass( 'woocommerce-validated' );
                $wrapper.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
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
            var self = wc_gzd_direct_debit;

            e.preventDefault();

            var url  = $( this ).attr( 'href' );
            var data = {};

            $.each( self.params.mandate_fields, function( key, selector ) {
                if ( $( 'input' + selector + ', select' + selector ).length > 0 ) {
                    data[ key ] = $( 'input' + selector + ', select' + selector ).val();
                } else {
                    data[ key ] = '';
                }
            } );

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