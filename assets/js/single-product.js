/*global wc_gzd_single_product_params, accounting */
;(function ( $, window, document, undefined ) {
    var GermanizedSingleProductWatcher = function( $form ) {
        var self = this;

        self.$form      = $form;
        self.params     = wc_gzd_single_product_params;
        self.$wrapper   = $form.closest( wc_gzd_single_product_params.wrapper );
        self.$product   = $form.closest( '.product' );
        self.requests   = [];
        self.observer   = {};
        self.timeout    = false;

        if ( self.$wrapper.length <= 0 ) {
            self.$wrapper = self.$product;
        }

        if ( "MutationObserver" in window || "WebKitMutationObserver" in window || "MozMutationObserver" in window ) {
            self.initObserver( self );

            if ( $form.hasClass( 'variations_form' ) ) {
                self.productId  = $form.find( 'input[name=product_id]' ).length > 0 ? $form.find( 'input[name=product_id]' ).val() : self.params.product_id;
                self.variatonId = $form.find( 'input[name=variation_id]' ).length > 0 ? $form.find( 'input[name=variation_id]' ).val() : 0;

                $form.on( 'reset_data', { GermanizedSingleProductWatcher: self }, self.onResetVariation );
                $form.on( 'found_variation.wc-variation-form', { GermanizedSingleProductWatcher: self }, self.onFoundVariation );
            } else {
                self.productId = $form.find( '*[name=add-to-cart][type=submit]' ).length > 0 ? $form.find( '*[name=add-to-cart][type=submit]' ).val() : self.params.product_id;
            }
        }
    };

    GermanizedSingleProductWatcher.prototype.initObserver = function( self ) {
        if ( Object.keys( self.observer ).length !== 0 ) {
            return;
        }

        $.each( self.params.price_selector, function( priceSelector, priceArgs ) {
            var $node           = self.$wrapper.find( priceSelector + ':not(.price-unit):visible' ),
                currentObserver = false;

            if ( $node.length > 0 ) {

                // Callback function to execute when mutations are observed
                var callback = function( mutationsList, observer ) {
                    /**
                     * Clear the timeout and abort open AJAX requests as
                     * a new mutation has been observed
                     */
                    if ( self.timeout ) {
                        clearTimeout( self.timeout );
                        self.abortAjaxRequests( self );
                    }

                    /**
                     * Need to use a tweak here to make sure our variation listener
                     * has already adjusted the variationId (in case necessary).
                     */
                    self.timeout = setTimeout(function() {
                        var priceData = self.getCurrentPriceData( self, priceSelector, priceArgs['is_total_price'] );

                        if ( priceData ) {
                            /**
                             * Do only fire AJAX requests in case no other requests (e.g. from other plugins) are currently running.
                             */
                            if ( $.active <= 0 ) {
                                self.refreshUnitPrice( self, priceData.price, priceData.unit_price, priceData.sale_price, priceData.quantity );
                            }
                        }
                    }, 500 );
                };

                if ( "MutationObserver" in window ) {
                    currentObserver = new window.MutationObserver( callback );
                } else if ( "WebKitMutationObserver" in window ) {
                    currentObserver = new window.WebKitMutationObserver( callback );
                } else if ( "MozMutationObserver" in window ) {
                    currentObserver = new window.MozMutationObserver( callback );
                }

                if ( currentObserver ) {
                    self.observer[ priceSelector ] = currentObserver;
                    self.observer[ priceSelector ].observe( $node[0], { childList: true, subtree: true, characterData: true } );
                }
            }
        });
    };

    GermanizedSingleProductWatcher.prototype.cancelObserver = function( self ) {
        if ( self.observer.length > 0 ) {
            for ( var key in self.observer ) {
                if ( self.observer.hasOwnProperty( key ) ) {
                    self.observer[ key ].disconnect();
                    delete self.observer[ key ];
                }
            }
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

    GermanizedSingleProductWatcher.prototype.getCurrentPriceData = function( self, priceSelector, isTotalPrice ) {
        var $price = $( self.params.wrapper + ' ' + priceSelector + ':not(.price-unit):visible' );

        if ( $price.length > 0 ) {
            var $unit_price = $price.parents( self.params.wrapper ).find( '.price-unit:first' ),
                sale_price  = '',
                $priceInner = $price.find( '.amount:first' ),
                $qty        = $( self.params.wrapper + ' ' + self.params.qty_selector + ':first' ),
                qty         = 1;

            if ( $qty.length > 0 ) {
                qty = parseFloat( $qty.val() );
            }

            /**
             * In case the price element does not contain the default Woo price structure
             * search the whole element.
             */
            if ( $priceInner.length <= 0 ) {
                if ( $price.find( '.price' ).length > 0 ) {
                    $priceInner = $price.find( '.price' );
                } else {
                    $priceInner = $price;
                }
            }

            var price = self.getRawPrice( $priceInner, self.params.price_decimal_sep );

            /**
             * Is sale?
             */
            if ( $price.find( '.amount' ).length > 1 ) {
                sale_price = self.getRawPrice( $price.find( '.amount:last' ), self.params.price_decimal_sep );
            }

            if ( $unit_price.length > 0 && price ) {
                if ( isTotalPrice ) {
                    price = parseFloat( price ) / qty;

                    if ( sale_price.length > 0 ) {
                        sale_price = parseFloat( sale_price ) / qty;
                    }
                }

                return {
                    'price'     : price,
                    'unit_price': $unit_price,
                    'sale_price': sale_price,
                    'quantity'  : qty,
                };
            }
        }

        return false;
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

    GermanizedSingleProductWatcher.prototype.refreshUnitPrice = function( self, price, $unit_price, sale_price, quantity ) {
        self.abortAjaxRequests( self );

        self.requests.push( $.ajax({
            type: "POST",
            url:  self.params.wc_ajax_url.toString().replace( '%%endpoint%%', 'gzd_refresh_unit_price' ),
            data: {
                'security'  : self.params.refresh_unit_price_nonce,
                'product_id': self.getCurrentProductId( self ),
                'price'     : price,
                'price_sale': sale_price,
                'quantity'  : quantity,
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
            $( 'form.variations_form, ' + wc_gzd_single_product_params.wrapper + ' form.cart' ).each( function() {
                $( this ).wc_germanized_single_product_watch();
            });
        }
    });

})( jQuery, window, document );

window.germanized = window.germanized || {};