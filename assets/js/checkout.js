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

            /**
             * Support lazy-disabling the checkout adjustments e.g. within compatibility scripts.
             * Lazy-loading will lead to the input#wc_gzd_checkout_disabled to be rendered.
             */
            if ( this.checkoutAdjustmentsDisabled() ) {
                $( 'body' ).removeClass( 'woocommerce-gzd-checkout' );
            }

            if ( $( '.payment_methods:first' ).parents( '#order_review' ).length ) {
                $( document.body ).on( 'payment_method_selected', this.triggerCheckoutRefresh );
            }

            $( document.body )
                .on( 'updated_checkout', this.onUpdateCheckout )
                .on( 'checkout_error', this.onCheckoutError );

            if ( this.params.has_privacy_checkbox ) {
                $( document ).on( 'change', 'input#createaccount', this.triggerCheckoutRefresh );
            }

            $( document ).on( 'change', 'input#' + this.params.checkbox_photovoltaic_systems_id, this.triggerCheckoutRefresh );

            if ( this.params.checkbox_hidden ) {
                this.maybeSetTermsCheckbox();
            } else {
                $( document ).on( 'change', 'input#' + this.params.checkbox_id, this.onChangeLegalCheckbox );
            }

            this.adjustHeading();
        },

        checkoutAdjustmentsDisabled() {
            return ! $( 'body' ).hasClass( 'woocommerce-gzd-checkout' ) || $( 'input#wc_gzd_checkout_disabled' ).length > 0;
        },

        adjustHeading() {
            var self = germanized.checkout;

            if ( self.params.adjust_heading && ! self.checkoutAdjustmentsDisabled() ) {
                var $form = $( 'form.checkout:visible' ),
                    $heading = $form.find( '#order_review_heading:first' );

                if ( $heading ) {
                    $heading.removeClass( 'wc-gzd-heading-moved' );
                }

                if ( ! self.params.custom_heading_container ) {
                    var $theFirst = $form.find( '.shop_table:visible, #payment:visible' ).first();

                    if ( $heading.length > 0 )  {
                        // Move heading after payment block
                        if ( $theFirst.length > 0 && 'payment' === $theFirst.attr( 'id' ) ) {
                            $heading.addClass( 'wc-gzd-heading-moved' );
                            $theFirst.after( $heading );
                        }

                        $form.find( '#order_review_heading:first' ).show();
                    }
                } else {
                    var $wrapper = $form.find( this.params.custom_heading_container );

                    if ( $wrapper.length > 0 && $heading.length > 0 ) {
                        $wrapper.prepend( $heading );

                        $form.find( '#order_review_heading:first' ).show();
                    }
                }

                $( document.body ).trigger( 'wc_gzd_updated_checkout_heading' );
            }
        },

        onCheckoutError: function( e, errors ) {
            var self = germanized.checkout;

            if ( ! self.params.mark_checkout_error_fields ) {
                return;
            }

            var $checkoutForm = $( 'form.checkout' ),
                $errorWrapper = $( errors ),
                $errors       = $errorWrapper.length > 0 ? $errorWrapper.find( '[data-id]' ) : null;

            if ( $errors && $errors.length > 0 ) {
                $errors.each( function() {
                    var $el = $( this );

                    if ( $el.data( 'id' ) ) {
                        var error_id = $el.data( 'id' ),
                            $input   = $checkoutForm.find( '#' + error_id );

                        if ( $input.length > 0 ) {
                            var $parent = $input.closest( '.form-row' );

                            if ( $parent.length > 0 ) {
                                $parent.removeClass( 'woocommerce-validated' ).addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
                            }
                        }
                    }
                });
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

                self.adjustHeading();
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
