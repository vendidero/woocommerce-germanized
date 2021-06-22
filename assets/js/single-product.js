/*global wc_gzd_single_product_params */
;(function ( $, window, document, undefined ) {
    var GermanizedSingleProductWatcher = function( $form ) {
        var self = this;

        self.$form      = $form;
        self.params     = wc_gzd_single_product_params;
        self.$wrapper   = $form.closest( wc_gzd_single_product_params.wrapper );
        self.$product   = $form.closest( '.product' );
        self.requests   = [];

        if ( self.$wrapper.length <= 0 ) {
            self.$wrapper = self.$product;
        }

        self.$wrapper.find( self.params.price_selector + ':not(.price-unit):visible' ).on( 'DOMSubtreeModified', { GermanizedSingleProductWatcher: self }, self.onChangePrice );

        if ( $form.hasClass( 'variations_form' ) ) {
            self.productId  = $form.find( 'input[name=product_id]' ).length > 0 ? $form.find( 'input[name=product_id]' ).val() : self.params.product_id;
            self.variatonId = $form.find( 'input[name=variation_id]' ).length > 0 ? $form.find( 'input[name=variation_id]' ).val() : 0;

            $form.on( 'reset_data', { GermanizedSingleProductWatcher: self }, self.onResetVariation );
            $form.on( 'found_variation.wc-variation-form', { GermanizedSingleProductWatcher: self }, self.onFoundVariation );
        } else {
            self.productId = $form.find( '*[name=add-to-cart][type=submit]' ).length > 0 ? $form.find( '*[name=add-to-cart][type=submit]' ).val() : self.params.product_id;
        }
    };

    GermanizedSingleProductWatcher.prototype.abortAjaxRequests = function( self ) {
        /**
         * Cancel requests
         */
        if ( self.requests.length > 0 ) {
            for ( var i = 0; i < self.requests.length; i++ ) {
                self.requests[i].abort();
            }
        }
    };

    /**
     * Reset all fields.
     */
    GermanizedSingleProductWatcher.prototype.onResetVariation = function( event ) {
        var self = event.data.GermanizedSingleProductWatcher;

        self.variationId = 0;
    };

    GermanizedSingleProductWatcher.prototype.onFoundVariation = function( event, variation ) {
        var self = event.data.GermanizedSingleProductWatcher;

        if ( variation.hasOwnProperty( 'variation_id' ) ) {
            self.variationId = variation.variation_id;
        }
    };

    GermanizedSingleProductWatcher.prototype.getCurrentPriceData = function( self ) {
        var $price = $( self.params.wrapper + ' ' + self.params.price_selector + ':not(.price-unit):visible' );

        if ( $price.length > 0 ) {
            var $unit_price = $price.parents( self.params.wrapper ).find( '.price-unit:first' ),
                price       = self.getRawPrice( $price.find( '.amount:first' ), self.params.price_decimal_sep ),
                sale_price  = '';

            /**
             * Is sale?
             */
            if ( $price.find( '.amount' ).length > 1 ) {
                sale_price = self.getRawPrice( $price.find( '.amount:last' ), self.params.price_decimal_sep );
            }

            if ( $unit_price.length > 0 && price ) {
                return {
                    'price': price,
                    'unit_price': $unit_price,
                    'sale_price': sale_price
                };
            }
        }

        return false;
    };

    GermanizedSingleProductWatcher.prototype.onChangePrice = function( event ) {
        var self = event.data.GermanizedSingleProductWatcher;

        /**
         * Need to use a tweak here to make sure our variation listener
         * has already adjusted the variationId (in case necessary).
         */
        setTimeout(function() {
            var priceData = self.getCurrentPriceData( self );
            event.preventDefault();

            if ( priceData ) {
                /**
                 * Unbind the event because using :first selectors will trigger DOMSubtreeModified again (infinite loop)
                 */
                self.$wrapper.find( self.params.price_selector + ':not(.price-unit):visible' ).off( 'DOMSubtreeModified', { GermanizedSingleProductWatcher: self }, self.onChangePrice );

                /**
                 * In case AJAX requests are still running (e.g. price refreshes of other plugins) wait for them to finish
                 */
                if ( $.active > 0 ) {
                    /**
                     * Do not listen to our own requests
                     */
                    if ( self.requests.length <= 0 ) {
                        $ ( document ).on( 'ajaxStop', { GermanizedSingleProductWatcher: self }, self.onAjaxStopRefresh );
                    }
                } else {
                    self.refreshUnitPrice( self, priceData.price, priceData.unit_price, priceData.sale_price );
                }
            }
        }, 500 );
    };

    GermanizedSingleProductWatcher.prototype.onAjaxStopRefresh = function( event ) {
        var self = event.data.GermanizedSingleProductWatcher;
        var priceData = self.getCurrentPriceData( self );

        if ( priceData ) {
            self.refreshUnitPrice( self, priceData.price, priceData.unit_price, priceData.sale_price );
        }

        $ ( document ).off( 'ajaxStop', { GermanizedSingleProductWatcher: self }, self.onAjaxStopRefresh );
    };

    GermanizedSingleProductWatcher.prototype.getCurrentProductId = function( self ) {
        var productId = self.productId;

        if ( self.variationId > 0 ) {
            productId = self.variationId;
        }

        return parseInt( productId );
    };

    GermanizedSingleProductWatcher.prototype.getRawPrice = function( $el, decimal_sep ) {
        var price_raw = $el.length > 0 ? $el.text() : '',
            price     = false;

        try {
            price = accounting.unformat( price_raw, decimal_sep );
        } catch (e) {
            price = false;
        }

        return price;
    };

    GermanizedSingleProductWatcher.prototype.refreshUnitPrice = function( self, price, $unit_price, sale_price ) {
        self.abortAjaxRequests( self );

        self.requests.push( $.ajax({
            type: "POST",
            url:  self.params.wc_ajax_url.toString().replace( '%%endpoint%%', 'gzd_refresh_unit_price' ),
            data: {
                'security'  : self.params.refresh_unit_price_nonce,
                'product_id': self.getCurrentProductId( self ),
                'price'     : price,
                'price_sale': sale_price
            },
            success: function( data ) {
                /**
                 * Do only adjust unit price in case current product id has not changed
                 * in the meantime (e.g. variation change).
                 */
                if ( parseInt( data.product_id ) === self.getCurrentProductId( self ) ) {
                    if ( data.hasOwnProperty( 'unit_price_html' ) ) {
                        $unit_price.html( data.unit_price_html );
                    }
                }

                /**
                 * Rebind the event
                 */
                self.$wrapper.find( self.params.price_selector + ':not(.price-unit):visible' ).on( 'DOMSubtreeModified', { GermanizedSingleProductWatcher: self }, self.onChangePrice );
            },
            error: function( data ) {},
            dataType: 'json'
        } ) );
    };

    /**
     * Function to call wc_gzd_variation_form on jquery selector.
     */
    $.fn.wc_germanized_single_product_watch = function() {
        new GermanizedSingleProductWatcher( this );
        return this;
    };

    $( function() {
        if ( typeof wc_gzd_single_product_params !== 'undefined' ) {
            $( '.variations_form, ' + wc_gzd_single_product_params.wrapper + ' .cart' ).each( function() {
                $( this ).wc_germanized_single_product_watch();
            });
        }
    });

})( jQuery, window, document );

window.germanized = window.germanized || {};