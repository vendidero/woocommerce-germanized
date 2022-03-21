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

        if ( self.$wrapper.length <= 0 ) {
            self.$wrapper = self.$product;
        }

        self.replacePrice = self.$wrapper.hasClass( 'bundled_product' ) ? false : wc_gzd_add_to_cart_variation_params.replace_price;

        $form.on( 'click', '.reset_variations', { GermanizedvariationForm: self }, self.onReset );
        $form.on( 'reset_data', { GermanizedvariationForm: self }, self.onReset );
        $form.on( 'show_variation', { GermanizedvariationForm: self }, self.onShowVariation );

        self.$wrapper.find( '' +
            '.woocommerce-product-attributes-item--food_description, ' +
            '.woocommerce-product-attributes-item--alcohol_content, ' +
            '.woocommerce-product-attributes-item--food_place_of_origin, ' +
            '.woocommerce-product-attributes-item--food_distributor'
        ).each( function() {
            var $tr = $( this );

            if ( $tr.find( '.woocommerce-product-attributes-item__value' ).is( ':empty' ) ) {
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

        if ( ! $wrapper.hasClass( 'is-food' ) && $wrapper.hasClass( 'product-type-variable' ) ) {
            $wrapper.find( '.ingredients_nutrients_tab' ).hide();
        }

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

        if ( 'yes' === variation.is_food ) {
            $wrapper.find( '.ingredients_nutrients_tab' ).css( 'display', 'inline-block' );
        } else {
            $wrapper.find( '.ingredients_nutrients_tab' ).hide();
        }

        if ( variation.delivery_time !== '' ) {
            $wrapper.find( 'p.delivery-time-info:first' ).wc_gzd_set_content( variation.delivery_time );
        } else {
            $wrapper.find( 'p.delivery-time-info:first' ).wc_gzd_reset_content();
        }

        if ( variation.defect_description !== '' ) {
            $wrapper.find( 'p.defect-description:first' ).wc_gzd_set_content( variation.defect_description );
        } else {
            $wrapper.find( 'p.defect-description:first' ).wc_gzd_reset_content();
        }

        if ( variation.tax_info !== '' && hasDisplayPrice ) {
            $wrapper.find( '.tax-info:first' ).wc_gzd_set_content( variation.tax_info );
        } else {
            $wrapper.find( '.tax-info:first' ).wc_gzd_reset_content();
        }

        if ( variation.deposit_amount !== '' && hasDisplayPrice ) {
            $wrapper.find( '.deposit-amount:first' ).wc_gzd_set_content( variation.deposit_amount );
        } else {
            $wrapper.find( '.deposit-amount:first' ).wc_gzd_reset_content();
        }

        if ( variation.deposit_packaging_type !== '' && hasDisplayPrice ) {
            $wrapper.find( '.deposit-packaging-type:first' ).wc_gzd_set_content( variation.deposit_packaging_type );
        } else {
            $wrapper.find( '.deposit-packaging-type:first' ).wc_gzd_reset_content();
        }

        if ( variation.food_description !== '' ) {
            $wrapper.find( '.food-description:first' ).wc_gzd_set_content( variation.food_description );
            $wrapper.find( '.woocommerce-product-attributes-item--food_description:first' ).wc_gzd_set_content( variation.food_description );
        } else {
            $wrapper.find( '.food-description:first' ).wc_gzd_reset_content();
            $wrapper.find( '.woocommerce-product-attributes-item--food_description:first' ).wc_gzd_reset_content();
        }

        if ( variation.food_distributor !== '' ) {
            $wrapper.find( '.food-distributor:first' ).wc_gzd_set_content( variation.food_distributor );
            $wrapper.find( '.woocommerce-product-attributes-item--food_distributor:first' ).wc_gzd_set_content( variation.food_distributor );
        } else {
            $wrapper.find( '.food-distributor:first' ).wc_gzd_reset_content();
            $wrapper.find( '.woocommerce-product-attributes-item--food_distributor:first' ).wc_gzd_reset_content();
        }

        if ( variation.food_place_of_origin !== '' ) {
            $wrapper.find( '.food-place-of-origin:first' ).wc_gzd_set_content( variation.food_place_of_origin );
            $wrapper.find( '.woocommerce-product-attributes-item--food_place_of_origin:first' ).wc_gzd_set_content( variation.food_place_of_origin );
        } else {
            $wrapper.find( '.food-place-of-origin:first' ).wc_gzd_reset_content();
            $wrapper.find( '.woocommerce-product-attributes-item--food_place_of_origin:first' ).wc_gzd_reset_content();
        }

        if ( variation.alcohol_content !== '' ) {
            $wrapper.find( '.alcohol-content:first' ).wc_gzd_set_content( variation.alcohol_content );
            $wrapper.find( '.woocommerce-product-attributes-item--alcohol_content:first' ).wc_gzd_set_content( variation.alcohol_content );
        } else {
            $wrapper.find( '.alcohol-content:first' ).wc_gzd_reset_content();
            $wrapper.find( '.woocommerce-product-attributes-item--alcohol_content:first' ).wc_gzd_reset_content();
        }

        if ( variation.nutrients !== '' ) {
            $wrapper.find( '.wc-gzd-nutrients:first' ).wc_gzd_set_content( variation.nutrients );
            $wrapper.find( '.wc-gzd-nutrients-heading:first' ).wc_gzd_set_content( variation.nutrients_heading );
        } else {
            $wrapper.find( '.wc-gzd-nutrients:first' ).wc_gzd_reset_content();
            $wrapper.find( '.wc-gzd-nutrients-heading:first' ).wc_gzd_reset_content();
        }

        if ( variation.ingredients !== '' ) {
            $wrapper.find( '.wc-gzd-ingredients:first' ).wc_gzd_set_content( variation.ingredients );
            $wrapper.find( '.wc-gzd-ingredients-heading:first' ).wc_gzd_set_content( variation.ingredients_heading );
        } else {
            $wrapper.find( '.wc-gzd-ingredients:first' ).wc_gzd_reset_content();
            $wrapper.find( '.wc-gzd-ingredients-heading:first' ).wc_gzd_reset_content();
        }

        if ( variation.allergenic !== '' ) {
            $wrapper.find( '.wc-gzd-allergenic:first' ).wc_gzd_set_content( variation.allergenic );
            $wrapper.find( '.wc-gzd-allergenic-heading:first' ).wc_gzd_set_content( variation.allergenic_heading );
        } else {
            $wrapper.find( '.wc-gzd-allergenic:first' ).wc_gzd_reset_content();
            $wrapper.find( '.wc-gzd-allergenic-heading:first' ).wc_gzd_reset_content();
        }

        if ( variation.shipping_costs_info !== '' && hasDisplayPrice ) {
            $wrapper.find( '.shipping-costs-info:first' ).wc_gzd_set_content( variation.shipping_costs_info );
        } else {
            $wrapper.find( '.shipping-costs-info:first' ).wc_gzd_reset_content();
        }

        if ( variation.unit_price !== '' && hasDisplayPrice ) {
            // Check if unit price for variable product exists and replace instead of insert
            if ( $wrapper.find( '.price-unit:first' ).length ) {
                $wrapper.find( '.price-unit:first' ).wc_gzd_set_content( variation.unit_price );
            } else {
                $wrapper.find( '.price-unit:first' ).remove();
                $wrapper.find( 'p.price:first' ).after( '<p class="price price-unit smaller variation_modified variation_gzd_modified">' + variation.unit_price + '</p>' ).show();
            }
        } else {
            $wrapper.find( '.price-unit:first' ).wc_gzd_reset_content();
        }

        if ( variation.product_units !== '' ) {
            // Check if product units for variable product exist and replace instead of insert
            if ( $wrapper.find( '.product-units:first' ).length ) {
                $wrapper.find( '.product-units:first' ).wc_gzd_set_content( variation.product_units );
            } else {
                $wrapper.find( '.product-units:first' ).remove();
                $wrapper.find( '.product_meta:first' ).prepend( '<p class="wc-gzd-additional-info product-units-wrapper product-units variation_modified variation_gzd_modified">' + variation.product_units + '</p>' ).show();
            }
        } else {
            $wrapper.find( '.product-units:first' ).wc_gzd_reset_content();
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
        var $content_elem = this;

        if ( this.hasClass( 'woocommerce-product-attributes-item' ) ) {
            $content_elem = this.find( '.woocommerce-product-attributes-item__value' );
        }

        if ( undefined === this.attr( 'data-o_content' ) ) {
            this.attr( 'data-o_content', $content_elem.html() );
        }

        $content_elem.html( content );

        this.addClass( 'variation_modified variation_gzd_modified' ).removeClass( 'wc-gzd-additional-info-placeholder' ).show();
    };

    /**
     * Stores the default text for an element so it can be reset later
     */
    $.fn.wc_gzd_reset_content = function() {
        var $content_elem = this;

        if ( this.hasClass( 'woocommerce-product-attributes-item' ) ) {
            $content_elem = this.find( '.woocommerce-product-attributes-item__value' );
        }

        if ( undefined !== this.attr( 'data-o_content' ) ) {
            $content_elem.html( this.attr( 'data-o_content' ) );

            this.removeClass( 'variation_modified variation_gzd_modified' ).show();
        }

        if ( $content_elem.is( ':empty' ) ) {
            this.addClass( 'wc-gzd-additional-info-placeholder' ).hide();
        }
    };

    $( function() {
        if ( typeof wc_gzd_add_to_cart_variation_params !== 'undefined' ) {
            $( '.variations_form' ).each( function() {
                $( this ).wc_germanized_variation_form();
            });
        }
    });

})( jQuery, window, document );