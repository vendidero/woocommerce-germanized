/*global wc_gzd_add_to_cart_variation_params */
;(function ( $, window, document, undefined ) {
    /**
     * VariationForm class which handles variation forms and attributes.
     */
    var GermanizedVariationForm = function( $form ) {
        var self = this;

        self.$form                = $form;
        self.$wrapper             = $form.closest( wc_gzd_add_to_cart_variation_params.wrapper );
        self.$product             = $form.closest( '.product' );
        self.variationData        = $form.data( 'product_variations' );
        self.$singleVariation     = $form.find( '.single_variation' );
        self.$singleVariationWrap = $form.find( '.single_variation_wrap' );
        self.$resetVariations     = $form.find( '.reset_variations' );
        self.$button              = $form.find( '.single_add_to_cart_button' );

        self.$form.addClass( 'has-gzd-variation-form' );
        self.$form.off( '.wc-gzd-variation-form' );

        if ( self.$wrapper.length <= 0 ) {
            self.$wrapper = self.$product;
        }

        self.replacePrice = self.$wrapper.hasClass( 'bundled_product' ) ? false : wc_gzd_add_to_cart_variation_params.replace_price;

        $form.on( 'click.wc-gzd-variation-form', '.reset_variations', { GermanizedvariationForm: self }, self.onReset );
        $form.on( 'reset_data.wc-gzd-variation-form', { GermanizedvariationForm: self }, self.onReset );
        $form.on( 'show_variation.wc-gzd-variation-form', { GermanizedvariationForm: self }, self.onShowVariation );

        self.$wrapper.find( '' +
            '.woocommerce-product-attributes-item--food_description, ' +
            '.woocommerce-product-attributes-item--alcohol_content, ' +
            '.woocommerce-product-attributes-item--net_filling_quantity, ' +
            '.woocommerce-product-attributes-item--drained_weight, ' +
            '.woocommerce-product-attributes-item--food_place_of_origin, ' +
            '.woocommerce-product-attributes-item--food_distributor'
        ).each( function() {
            var $tr = $( this );

            if ( $tr.find( '.woocommerce-product-attributes-item__value' ).is( ':empty' ) || $tr.find( '.woocommerce-product-attributes-item__value .wc-gzd-additional-info-placeholder' ).is( ':empty' ) ) {
                $tr.addClass( 'wc-gzd-additional-info-placeholder' );
            }
        } );
    };

    GermanizedVariationForm.prototype.getPriceElement = function( self ) {
        var $wrapper = self.$wrapper;

        /**
         * Ignore the price wrapper inside the variation form to make sure the right
         * price is being replaced even if the price element is located beneath the form.
         */
        return $wrapper.find( wc_gzd_add_to_cart_variation_params.price_selector + ':not(.price-unit):visible' ).not( '.variations_form .single_variation .price' ).first();
    };

    /**
     * Reset all fields.
     */
    GermanizedVariationForm.prototype.onReset = function( event ) {
        var form     = event.data.GermanizedvariationForm,
            $wrapper = form.$wrapper;

        $wrapper.find( '.variation_gzd_modified' ).each( function() {
            $( this ).wc_gzd_reset_content();
        } );

        $wrapper.find( '.variation_gzd_modified' ).remove();

        event.data.GermanizedvariationForm.$form.trigger( 'germanized_reset_data' );
    };

    GermanizedVariationForm.prototype.onUpdate = function( event ) {

        setTimeout( function() {
            if( typeof event.data === 'undefined' || ! event.data.hasOwnProperty( 'GermanizedvariationForm' ) ) {
                return;
            } else if ( typeof event.data.GermanizedvariationForm === 'undefined' ) {
                return;
            }

            // If the button is diabled (or has disabled class) no variation can be added to the cart - reset has been triggered
            if ( event.data.GermanizedvariationForm.$button.is( '[disabled]' ) || event.data.GermanizedvariationForm.$button.hasClass( 'disabled' ) ) {
                event.data.GermanizedvariationForm.onReset( event );
            }
        }, 250);
    };

    GermanizedVariationForm.prototype.onShowVariation = function( event, variation, purchasable ) {
        var form            = event.data.GermanizedvariationForm,
            $wrapper        = form.$wrapper,
            hasCustomPrice  = variation.hasOwnProperty( 'price_html' ) && variation.price_html !== '',
            hasDisplayPrice = variation.hasOwnProperty( 'display_price' ) && variation.display_price !== '';

        if ( hasCustomPrice && form.replacePrice ) {
            var $priceElement = form.getPriceElement( form );

            form.$singleVariation.find( '.price' ).hide();

            $priceElement.wc_gzd_set_content( variation.price_html );
            $priceElement.find( '.price' ).contents().unwrap();
        }

        if ( variation.delivery_time !== '' ) {
            $wrapper.find( 'p.delivery-time-info' ).wc_gzd_set_content( variation.delivery_time );
        } else {
            $wrapper.find( 'p.delivery-time-info' ).wc_gzd_reset_content();
        }

        if ( variation.defect_description !== '' ) {
            $wrapper.find( 'p.defect-description' ).wc_gzd_set_content( variation.defect_description );
        } else {
            $wrapper.find( 'p.defect-description' ).wc_gzd_reset_content();
        }

        if ( variation.tax_info !== '' && hasDisplayPrice ) {
            $wrapper.find( '.tax-info' ).wc_gzd_set_content( variation.tax_info );
        } else {
            $wrapper.find( '.tax-info' ).wc_gzd_reset_content();
        }

        if ( variation.deposit_amount !== '' && hasDisplayPrice ) {
            $wrapper.find( '.deposit-amount' ).wc_gzd_set_content( variation.deposit_amount );
        } else {
            $wrapper.find( '.deposit-amount' ).wc_gzd_reset_content();
        }

        if ( variation.deposit_packaging_type !== '' && hasDisplayPrice ) {
            $wrapper.find( '.deposit-packaging-type' ).wc_gzd_set_content( variation.deposit_packaging_type );
        } else {
            $wrapper.find( '.deposit-packaging-type' ).wc_gzd_reset_content();
        }

        if ( variation.food_description !== '' ) {
            $wrapper.find( '.wc-gzd-food-description' ).wc_gzd_set_content( variation.food_description );
        } else {
            $wrapper.find( '.wc-gzd-food-description' ).wc_gzd_reset_content();
        }

        if ( variation.nutri_score !== '' ) {
            $wrapper.find( '.wc-gzd-nutri-score' ).wc_gzd_set_content( variation.nutri_score );
        } else {
            $wrapper.find( '.wc-gzd-nutri-score' ).wc_gzd_reset_content();
        }

        if ( variation.food_distributor !== '' ) {
            $wrapper.find( '.wc-gzd-food-distributor' ).wc_gzd_set_content( variation.food_distributor );
        } else {
            $wrapper.find( '.wc-gzd-food-distributor' ).wc_gzd_reset_content();
        }

        if ( variation.food_place_of_origin !== '' ) {
            $wrapper.find( '.wc-gzd-food-place-of-origin' ).wc_gzd_set_content( variation.food_place_of_origin );
        } else {
            $wrapper.find( '.wc-gzd-food-place-of-origin' ).wc_gzd_reset_content();
        }

        if ( variation.net_filling_quantity !== '' ) {
            $wrapper.find( '.wc-gzd-net-filling-quantity' ).wc_gzd_set_content( variation.net_filling_quantity );
        } else {
            $wrapper.find( '.wc-gzd-net-filling-quantity' ).wc_gzd_reset_content();
        }

        if ( variation.drained_weight !== '' ) {
            $wrapper.find( '.wc-gzd-drained-weight' ).wc_gzd_set_content( variation.drained_weight );
        } else {
            $wrapper.find( '.wc-gzd-drained-weight' ).wc_gzd_reset_content();
        }

        if ( variation.alcohol_content !== '' || 'no' === variation.includes_alcohol ) {
            $wrapper.find( '.wc-gzd-alcohol-content' ).wc_gzd_set_content( variation.alcohol_content );
        } else {
            $wrapper.find( '.wc-gzd-alcohol-content' ).wc_gzd_reset_content();
        }

        if ( variation.nutrients !== '' ) {
            $wrapper.find( '.wc-gzd-nutrients' ).wc_gzd_set_content( variation.nutrients );
            $wrapper.find( '.wc-gzd-nutrients-heading' ).wc_gzd_set_content( variation.nutrients_heading );
        } else {
            $wrapper.find( '.wc-gzd-nutrients' ).wc_gzd_reset_content();
            $wrapper.find( '.wc-gzd-nutrients-heading' ).wc_gzd_reset_content();
        }

        if ( variation.ingredients !== '' ) {
            $wrapper.find( '.wc-gzd-ingredients' ).wc_gzd_set_content( variation.ingredients );
            $wrapper.find( '.wc-gzd-ingredients-heading' ).wc_gzd_set_content( variation.ingredients_heading );
        } else {
            $wrapper.find( '.wc-gzd-ingredients' ).wc_gzd_reset_content();
            $wrapper.find( '.wc-gzd-ingredients-heading' ).wc_gzd_reset_content();
        }

        if ( variation.allergenic !== '' ) {
            $wrapper.find( '.wc-gzd-allergenic' ).wc_gzd_set_content( variation.allergenic );
            $wrapper.find( '.wc-gzd-allergenic-heading' ).wc_gzd_set_content( variation.allergenic_heading );
        } else {
            $wrapper.find( '.wc-gzd-allergenic' ).wc_gzd_reset_content();
            $wrapper.find( '.wc-gzd-allergenic-heading' ).wc_gzd_reset_content();
        }

        if ( variation.shipping_costs_info !== '' && hasDisplayPrice ) {
            $wrapper.find( '.shipping-costs-info' ).wc_gzd_set_content( variation.shipping_costs_info );
        } else {
            $wrapper.find( '.shipping-costs-info' ).wc_gzd_reset_content();
        }

        if ( variation.unit_price !== '' && hasDisplayPrice ) {
            $wrapper.find( '.price-unit' ).wc_gzd_set_content( variation.unit_price );
        } else {
            $wrapper.find( '.price-unit' ).wc_gzd_reset_content();
        }

        if ( variation.product_units !== '' ) {
            $wrapper.find( '.product-units' ).wc_gzd_set_content( variation.product_units );
        } else {
            $wrapper.find( '.product-units' ).wc_gzd_reset_content();
        }

        form.$form.trigger( 'germanized_variation_data', variation, $wrapper );
    };

    /**
     * Function to call wc_gzd_variation_form on jquery selector.
     */
    $.fn.wc_germanized_variation_form = function() {
        new GermanizedVariationForm( this );
        return this;
    };

    /**
     * Stores the default text for an element so it can be reset later
     */
    $.fn.wc_gzd_set_content = function( content ) {
        /**
         * Explicitly exclude loop wrappers to prevent information
         * to be replaced within the main product wrapper (e.g. cross-sells).
         */
        var $this = this.not( '.wc-gzd-additional-info-loop' );

        if ( undefined === $this.attr( 'data-o_content' ) ) {
            $this.attr( 'data-o_content', $this.html() );
        }

        $this.html( content );

        $this.addClass( 'variation_modified variation_gzd_modified' ).removeClass( 'wc-gzd-additional-info-placeholder' ).show();

        if ( $this.is( ':empty' ) ) {
            $this.hide();

            if ( $this.parents( '.woocommerce-product-attributes-item' ).length > 0 ) {
                $this.parents( '.woocommerce-product-attributes-item' ).hide();
            }
        } else {
            if ( $this.parents( '.woocommerce-product-attributes-item' ).length > 0 ) {
                $this.parents( '.woocommerce-product-attributes-item' ).show();
            }
        }
    };

    /**
     * Stores the default text for an element so it can be reset later
     */
    $.fn.wc_gzd_reset_content = function() {
        var $this = this.not( '.wc-gzd-additional-info-loop' );

        if ( undefined !== $this.attr( 'data-o_content' ) ) {
            $this.html( $this.attr( 'data-o_content' ) );

            $this.removeClass( 'variation_modified variation_gzd_modified' ).show();
        }

        if ( $this.is( ':empty' ) ) {
            $this.addClass( 'wc-gzd-additional-info-placeholder' ).hide();

            if ( $this.parents( '.woocommerce-product-attributes-item' ).length > 0 ) {
                $this.parents( '.woocommerce-product-attributes-item' ).hide();
            }
        } else {
            if ( $this.parents( '.woocommerce-product-attributes-item' ).length > 0 ) {
                $this.parents( '.woocommerce-product-attributes-item' ).show();
            }
        }
    };

    $( function() {
        if ( typeof wc_gzd_add_to_cart_variation_params !== 'undefined' ) {
            $( '.variations_form' ).each( function() {
                $( this ).wc_germanized_variation_form();
            });

            /**
             * Improve compatibility with custom implementations which might
             * manually construct wc_variation_form() (e.g. quick view).
             */
            $( document.body ).on( 'wc_variation_form', function( e, variationForm ) {
                var $form;

                if ( typeof variationForm === 'undefined' ) {
                    $form = $( e.target );
                } else {
                    $form = $( variationForm.$form );
                }

                if ( $form.length > 0 ) {
                    if ( ! $form.hasClass( 'has-gzd-variation-form' ) ) {
                        $form.wc_germanized_variation_form();
                        // Make sure to reload variation to apply our logic
                        $form.trigger( 'check_variations' );
                    }
                }
            } );
        }
    });

})( jQuery, window, document );