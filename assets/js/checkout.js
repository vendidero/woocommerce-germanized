/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order */
window.germanized = window.germanized || {};

( function( $, germanized ) {

    /**
     * Order Data Panel
     */
    germanized.checkout = {

        params: {},

        init: function() {
            this.params = wc_gzd_checkout_params;

            if ( $( '.payment_methods:first' ).parents( '#order_review' ).length ) {
                $( document ).on( 'change', '.payment_methods input[name="payment_method"]', this.triggerCheckoutRefresh );
            }

            $( 'body' ).bind( 'updated_checkout', this.onUpdateCheckout );

            if ( this.params.adjust_heading ) {
                if ( $( '.woocommerce-checkout' ).find( '#order_review_heading' ).length > 0 ) {
                    $( '.woocommerce-checkout' ).find( '#payment' ).after( $( '.woocommerce-checkout' ).find( '#order_review_heading' ) );
                    $( '.woocommerce-checkout' ).find( '#order_review_heading' ).show();
                }
            }

            if ( this.params.has_privacy_checkbox ) {
                $( document ).on( 'change', 'input#createaccount', this.triggerCheckoutRefresh );
            }

            if ( this.params.checkbox_hidden ) {
                this.maybeSetTermsCheckbox();
            } else {
                $( document ).on( 'change', 'input#' + this.params.checkbox_id, this.onChangeLegalCheckbox );
            }
        },

        maybeSetTermsCheckbox: function() {
            var self      = germanized.checkout,
                $checkbox = $( 'input#' + self.params.checkbox_id ),
                $terms    = $( 'input[name=terms]' );

            if ( $terms.length > 0 ) {
                if ( self.params.checkbox_hidden || $checkbox.is( ':checked' ) ) {
                    $terms.prop( 'checked', true ).trigger( 'change' );
                } else {
                    $terms.prop( 'checked', false ).trigger( 'change' );
                }
            }
        },

        onChangeLegalCheckbox: function() {
            var self      = germanized.checkout;

            self.maybeSetTermsCheckbox();
        },

        triggerCheckoutRefresh: function() {
            $( 'body' ).trigger( 'update_checkout' );
        },

        onUpdateCheckout: function() {
            var self      = germanized.checkout;

            if ( self.params.adjust_heading ) {
                if ( $( '.woocommerce-checkout' ).find( '#order_payment_heading' ).length > 0 ) {

                    if ( $( '.woocommerce-checkout' ).find( '.wc_payment_methods' ).length <= 0 ) {
                        $( '.woocommerce-checkout' ).find( '#order_payment_heading' ).hide();
                    } else {
                        $( '.woocommerce-checkout' ).find( '#order_payment_heading' ).show();
                    }
                }
            }

            if ( $( '.wc-gzd-place-order' ).length > 0 ) {
                if ( $( '.place-order:not(.wc-gzd-place-order)' ).length > 0 ) {
                    // Make sure we are removing the nonce from the old container to the new one.
                    $( '.place-order:not(.wc-gzd-place-order)' ).find( '#_wpnonce' ).appendTo( '.wc-gzd-place-order' );
                    // Woo 3.4
                    $( '.place-order:not(.wc-gzd-place-order)' ).find( '#woocommerce-process-checkout-nonce' ).appendTo( '.wc-gzd-place-order' );
                }
                $( '.place-order:not(.wc-gzd-place-order)' ).remove();
            }

            self.maybeSetTermsCheckbox();
        }
    };

    $( document ).ready( function() {
        germanized.checkout.init();
    });

})( jQuery, window.germanized );
