/*global wc_gzd_unit_price_observer_params, accounting */
;(function ( $, window, document, undefined ) {
    var GermanizedUnitPriceObserver = function( $form ) {
        var self = this;

        self.$form    = $form;
        self.params   = wc_gzd_unit_price_observer_params;
        self.$wrapper = $form.closest( self.params.wrapper );
        self.$product = $form.closest( '.product' );
        self.requests = [];
        self.observer = {};
        self.timeout  = false;

        if ( self.$wrapper.length <= 0 ) {
            self.$wrapper = self.$product;
        }

        if ( "MutationObserver" in window || "WebKitMutationObserver" in window || "MozMutationObserver" in window ) {
            self.$form.addClass( 'has-unit-price-observer' );
            self.initObserver( self );

            if ( $form.hasClass( 'variations_form' ) ) {
                self.productId   = $form.find( 'input[name=product_id]' ).length > 0 ? $form.find( 'input[name=product_id]' ).val() : $form.data( 'product_id' );
                self.variationId = $form.find( 'input[name=variation_id]' ).length > 0 ? $form.find( 'input[name=variation_id]' ).val() : 0;

                if ( $form.find( 'input[name=variation_id]' ).length <= 0 ) {
                    self.variationId = $form.find( 'input.variation_id' ).length > 0 ? $form.find( 'input.variation_id' ).val() : 0;
                }

                $form.on( 'reset_data', { GermanizedUnitPriceObserver: self }, self.onResetVariation );
                $form.on( 'found_variation.wc-variation-form', { GermanizedUnitPriceObserver: self }, self.onFoundVariation );
            } else {
                self.productId = $form.find( '*[name=add-to-cart][type=submit]' ).length > 0 ? $form.find( '*[name=add-to-cart][type=submit]' ).val() : self.params.product_id;
            }

            if ( self.params.refresh_on_load ) {
                $.each( self.params.price_selector, function( priceSelector, priceArgs ) {
                    var isPrimary    = priceArgs.hasOwnProperty( 'is_primary_selector' ) ? priceArgs['is_primary_selector'] : false,
                        $price       = self.getPriceNode( self, priceSelector, isPrimary ),
                        $unitPrice   = self.getUnitPriceNode( self, $price );

                    if ( $unitPrice.length > 0 ) {
                        self.setUnitPriceLoading( self, $unitPrice );

                        setTimeout( function() {
                            var priceData = self.getCurrentPriceData( self, priceSelector, priceArgs['is_total_price'], isPrimary, priceArgs['quantity_selector'] );

                            if ( priceData ) {
                                self.refreshUnitPrice( self, priceData.price, priceData.unit_price, priceData.sale_price, priceData.quantity );
                            } else if ( $unitPrice.length > 0 ) {
                                self.unsetUnitPriceLoading( self, $unitPrice );
                            }
                        }, 250 );
                    }
                } );
            }
        }
    };

    GermanizedUnitPriceObserver.prototype.getTextWidth = function( $element ) {
        var htmlOrg = $element.html();
        var html_calc = '<span>' + htmlOrg + '</span>';

        $element.html( html_calc );
        var textWidth = $element.find( 'span:first' ).width();
        $element.html( htmlOrg );

        return textWidth;
    };

    GermanizedUnitPriceObserver.prototype.getPriceNode = function( self, priceSelector, isPrimarySelector ) {
        isPrimarySelector = ( typeof isPrimarySelector === 'undefined' ) ? false : isPrimarySelector;

        var $node = self.$wrapper.find( priceSelector + ':not(.price-unit):visible:last' );

        if ( isPrimarySelector && $node.length <= 0 && self.$form.hasClass( 'variations_form' ) ) {
            $node = self.$wrapper.find( '.woocommerce-variation-price span.price:not(.price-unit):visible:last' );
        }

        return $node;
    };

    GermanizedUnitPriceObserver.prototype.getUnitPriceNode = function( self, $price ) {
        if ( $price.length <= 0 ) {
            return [];
        }

        return $price.parents( self.params.wrapper ).find( '.price-unit:not(.wc-gzd-additional-info-placeholder, .wc-gzd-additional-info-loop)' );
    };

    GermanizedUnitPriceObserver.prototype.initObserver = function( self ) {
        if ( Object.keys( self.observer ).length !== 0 ) {
            return;
        }

        $.each( self.params.price_selector, function( priceSelector, priceArgs ) {
            var isPrimary       = priceArgs.hasOwnProperty( 'is_primary_selector' ) ? priceArgs['is_primary_selector'] : false,
                $node           = self.getPriceNode( self, priceSelector, isPrimary ),
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

                    var $unitPrice   = self.getUnitPriceNode( self, $node ),
                        hasRefreshed = false;

                    if ( $unitPrice.length > 0 ) {
                        self.setUnitPriceLoading( self, $unitPrice );
                    }

                    /**
                     * Need to use a tweak here to make sure our variation listener
                     * has already adjusted the variationId (in case necessary).
                     */
                    self.timeout = setTimeout(function() {
                        var priceData = self.getCurrentPriceData( self, priceSelector, priceArgs['is_total_price'], isPrimary, priceArgs['quantity_selector'] );

                        if ( priceData ) {
                            /**
                             * Do only fire AJAX requests in case no other requests (e.g. from other plugins) are currently running.
                             */
                            if ( $.active <= 0 ) {
                                hasRefreshed = true;
                                self.refreshUnitPrice( self, priceData.price, priceData.unit_price, priceData.sale_price, priceData.quantity );
                            }
                        }

                        if ( ! hasRefreshed && $unitPrice.length > 0 ) {
                            self.unsetUnitPriceLoading( self, $unitPrice );
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

    GermanizedUnitPriceObserver.prototype.cancelObserver = function( self ) {
        if ( self.observer.length > 0 ) {
            for ( var key in self.observer ) {
                if ( self.observer.hasOwnProperty( key ) ) {
                    self.observer[ key ].disconnect();
                    delete self.observer[ key ];
                }
            }
        }
    };

    GermanizedUnitPriceObserver.prototype.abortAjaxRequests = function( self ) {
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
    GermanizedUnitPriceObserver.prototype.onResetVariation = function( event ) {
        var self = event.data.GermanizedUnitPriceObserver;

        self.variationId = 0;
    };

    GermanizedUnitPriceObserver.prototype.onFoundVariation = function( event, variation ) {
        var self = event.data.GermanizedUnitPriceObserver;

        if ( variation.hasOwnProperty( 'variation_id' ) ) {
            self.variationId = variation.variation_id;
        }

        self.initObserver( self );
    };

    GermanizedUnitPriceObserver.prototype.getCurrentPriceData = function( self, priceSelector, isTotalPrice, isPrimary, quantitySelector ) {
        quantitySelector = quantitySelector && '' !== quantitySelector ? quantitySelector : self.params.qty_selector;
        var $price       = self.getPriceNode( self, priceSelector, isPrimary );

        if ( $price.length > 0 ) {
            var $unit_price = self.getUnitPriceNode( self, $price ),
                $priceCloned = $price.clone();

            // Remove price suffix from cloned DOM element to prevent finding the wrong price within suffix
            $priceCloned.find( '.woocommerce-price-suffix' ).remove();

            var sale_price  = '',
                $priceInner = $priceCloned.find( '.amount:first' ),
                $qty        = $( self.params.wrapper + ' ' + quantitySelector + ':first' ),
                qty         = 1;

            if ( $qty.length > 0 ) {
                qty = parseFloat( $qty.val() );
            }

            /**
             * In case the price element does not contain the default Woo price structure
             * search the whole element.
             */
            if ( $priceInner.length <= 0 ) {
                if ( $priceCloned.find( '.price' ).length > 0 ) {
                    $priceInner = $priceCloned.find( '.price' );
                } else {
                    $priceInner = $priceCloned;
                }
            }

            var price = self.getRawPrice( $priceInner, self.params.price_decimal_sep );

            /**
             * Is sale?
             */
            if ( $priceCloned.find( '.amount' ).length > 1 ) {
                // The second .amount element is the sale price
                var $sale_price = $( $priceCloned.find( '.amount' )[1] );

                sale_price = self.getRawPrice( $sale_price, self.params.price_decimal_sep );
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

    GermanizedUnitPriceObserver.prototype.getCurrentProductId = function( self ) {
        var productId = self.productId;

        if ( self.variationId > 0 ) {
            productId = self.variationId;
        }

        return parseInt( productId );
    };

    GermanizedUnitPriceObserver.prototype.getRawPrice = function( $el, decimal_sep ) {
        var price_raw = $el.length > 0 ? $el.text() : '',
            price     = false;

        try {
            price = accounting.unformat( price_raw, decimal_sep );
        } catch (e) {
            price = false;
        }

        return price;
    };

    GermanizedUnitPriceObserver.prototype.setUnitPriceLoading = function( self, $unit_price ) {
        var unitPriceOrg = $unit_price.html();

        if ( ! $unit_price.hasClass( 'wc-gzd-loading' ) ) {
            var textWidth  = self.getTextWidth( $unit_price ),
                textHeight = $unit_price.find( 'span' ).length > 0 ? $unit_price.find( 'span' ).innerHeight() : $unit_price.height();
            /**
             * @see https://github.com/zalog/placeholder-loading
             */
            $unit_price.html( '<span class="wc-gzd-placeholder-loading"><span class="wc-gzd-placeholder-row" style="height: ' + $unit_price.height() + 'px;"><span class="wc-gzd-placeholder-row-col-4" style="width: ' + textWidth + 'px; height: ' + textHeight + 'px;"></span></span></span>' );
            $unit_price.addClass( 'wc-gzd-loading' );
            $unit_price.data( 'org-html', unitPriceOrg );
        } else {
            unitPriceOrg = $unit_price.data( 'org-html' );
        }

        return unitPriceOrg;
    };

    GermanizedUnitPriceObserver.prototype.unsetUnitPriceLoading = function( self, $unit_price, newHtml ) {
        newHtml = newHtml || $unit_price.data( 'org-html' );

        if ( $unit_price.hasClass( 'wc-gzd-loading' ) ) {
            $unit_price.html( newHtml );
            $unit_price.removeClass( 'wc-gzd-loading' ).show();
        }
    };

    GermanizedUnitPriceObserver.prototype.refreshUnitPrice = function( self, price, $unit_price, sale_price, quantity ) {
        self.abortAjaxRequests( self );

        var unitPriceOrgHtml = self.setUnitPriceLoading( self, $unit_price );

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
                        self.unsetUnitPriceLoading( self, $unit_price, data.unit_price_html );
                    } else {
                        self.unsetUnitPriceLoading( self, $unit_price );
                    }
                } else {
                    self.unsetUnitPriceLoading( self, $unit_price );
                }
            },
            error: function( data ) {
                self.unsetUnitPriceLoading( self, $unit_price );
            },
            dataType: 'json'
        } ) );
    };

    /**
     * Function to call wc_gzd_variation_form on jquery selector.
     */
    $.fn.wc_germanized_unit_price_observer = function() {
        new GermanizedUnitPriceObserver( this );
        return this;
    };

    $( function() {
        if ( typeof wc_gzd_unit_price_observer_params !== 'undefined' ) {
            $( '.variations_form, ' + wc_gzd_unit_price_observer_params.wrapper + ' form.cart' ).each( function() {
                $( this ).wc_germanized_unit_price_observer();
            });
        }
    });

})( jQuery, window, document );

window.germanized = window.germanized || {};