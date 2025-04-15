/*global wc_gzd_unit_price_observer_params, accounting */
;(function ( $, window, document, undefined ) {
    var GermanizedUnitPriceObserver = function( $wrapper ) {
        var self = this;

        self.params    = wc_gzd_unit_price_observer_params;
        self.$wrapper  = $wrapper.closest( self.params.wrapper );
        self.$form     = self.$wrapper.find( '.variations_form, .cart' ).length > 0 ? self.$wrapper.find( '.variations_form, .cart' ) : false;
        self.isVar     = self.$form ? self.$form.hasClass( 'variations_form' ) : false;
        self.$product  = self.$wrapper.closest( '.product' );
        self.requests  = [];
        self.observer  = {};
        self.timeout   = false;
        self.priceData = false;
        self.productId = 0;

        if ( self.$wrapper.length <= 0 ) {
            self.$wrapper = self.$product;
        }

        self.replacePrice = self.$wrapper.hasClass( 'bundled_product' ) ? false : self.params.replace_price;

        if ( "MutationObserver" in window || "WebKitMutationObserver" in window || "MozMutationObserver" in window ) {
            self.$wrapper.addClass( 'has-unit-price-observer' );
            self.initObservers( self );

            if ( self.isVar && self.$form ) {
                self.productId   = parseInt( self.$form.find( 'input[name=product_id]' ).length > 0 ? self.$form.find( 'input[name=product_id]' ).val() : self.$form.data( 'product_id' ) );
                self.variationId = parseInt( self.$form.find( 'input[name=variation_id]' ).length > 0 ? self.$form.find( 'input[name=variation_id]' ).val() : 0 );

                if ( self.$form.find( 'input[name=variation_id]' ).length <= 0 ) {
                    self.variationId = parseInt( self.$form.find( 'input.variation_id' ).length > 0 ? self.$form.find( 'input.variation_id' ).val() : 0 );
                }

                self.$form.on( 'reset_data.unit-price-observer', { GermanizedUnitPriceObserver: self }, self.onResetVariation );
                self.$form.on( 'found_variation.unit-price-observer', { GermanizedUnitPriceObserver: self }, self.onFoundVariation );
            } else {
                if ( self.$form && self.$form.find( '*[name=add-to-cart][type=submit]' ).length > 0 ) {
                    self.productId = parseInt( self.$form.find( '*[name=add-to-cart][type=submit]' ).val() );
                } else if ( self.$form && self.$form.data( 'product_id' ) ) {
                    self.productId = parseInt( self.$form.data( 'product_id' ) );
                } else {
                    var classList = self.$product.attr( 'class' ).split( /\s+/ );

                    /**
                     * Check whether we may find the post/product by a class added by Woo, e.g. post-64
                     */
                    $.each( classList, function( index, item ) {
                        if ( 'post-' === item.substring( 0, 5 ) ) {
                            var postId = parseInt( item.substring( 5 ).replace( /[^0-9]/g, '' ) );

                            if ( postId > 0 ) {
                                self.productId = postId;
                                return true;
                            }
                        }
                    });

                    /**
                     * Do only use the add to cart button attribute as fallback as there might be a lot of
                     * other product/add to cart buttons within a single product main product wrap (e.g. related products).
                     */
                    if ( self.productId <= 0 && 1 === self.$product.find( 'a.ajax_add_to_cart[data-product_id], a.add_to_cart_button[data-product_id]' ).length ) {
                        self.productId = parseInt( self.$product.find( 'a.ajax_add_to_cart, a.add_to_cart_button' ).data( 'product_id' ) );
                    }
                }
            }

            if ( self.productId <= 0 ) {
                self.destroy( self );
                return false;
            }

            if ( self.params.refresh_on_load ) {
                $.each( self.params.price_selector, function( priceSelector, priceArgs ) {
                    var isPrimary    = priceArgs.hasOwnProperty( 'is_primary_selector' ) ? priceArgs['is_primary_selector'] : false,
                        $price       = self.getPriceNode( self, priceSelector, isPrimary ),
                        $unitPrice   = self.getUnitPriceNode( self, $price );

                    /**
                     * Do only refresh primary price nodes on load.
                     */
                    if ( ! isPrimary ) {
                        return;
                    }

                    if ( $unitPrice.length > 0 ) {
                        self.stopObserver( self, priceSelector );
                        self.setUnitPriceLoading( self, $unitPrice );

                        setTimeout( function() {
                            self.stopObserver( self, priceSelector );

                            var priceData = self.getCurrentPriceData( self, $price, priceArgs['is_total_price'], isPrimary, priceArgs['quantity_selector'] );

                            if ( priceData ) {
                                self.refreshUnitPrice( self, priceData, priceSelector, isPrimary );
                            } else if ( $unitPrice.length > 0 ) {
                                self.unsetUnitPriceLoading( self, $unitPrice );
                            }

                            self.startObserver( self, priceSelector, isPrimary );
                        }, 250 );
                    }
                } );
            }
        }

        $wrapper.data( 'unitPriceObserver', self );
    };

    GermanizedUnitPriceObserver.prototype.destroy = function( self ) {
        self = self || this;

        self.cancelObservers( self );

        if ( self.$form ) {
            self.$form.off( '.unit-price-observer' );
        }

        self.$wrapper.removeClass( 'has-unit-price-observer' );
    };

    GermanizedUnitPriceObserver.prototype.getTextWidth = function( $element ) {
        var htmlOrg = $element.html();
        var html_calc = '<span>' + htmlOrg + '</span>';

        $element.html( html_calc );
        var textWidth = $element.find( 'span:first' ).width();
        $element.html( htmlOrg );

        return textWidth;
    };

    GermanizedUnitPriceObserver.prototype.getPriceNode = function( self, priceSelector, isPrimarySelector, visibleOnly ) {
        isPrimarySelector = ( typeof isPrimarySelector === 'undefined' ) ? false : isPrimarySelector;
        visibleOnly = ( typeof visibleOnly === 'undefined' ) ? true : visibleOnly;
        let visibleSelector = visibleOnly ? ':visible' : '';

        var $node = self.$wrapper.find( priceSelector + ':not(.price-unit)' + visibleSelector ).not( '.variations_form .single_variation .price' ).first();

        if ( isPrimarySelector && self.isVar && ( $node.length <= 0 || ! self.replacePrice ) ) {
            $node = self.$wrapper.find( '.woocommerce-variation-price span.price:not(.price-unit):last' + visibleSelector );
        } else if ( isPrimarySelector && $node.length <= 0 ) {
            $node = self.$wrapper.find( '.price:not(.price-unit):last' + visibleSelector );
        }

        return $node;
    };

    GermanizedUnitPriceObserver.prototype.getObserverNode = function( self, priceSelector, isPrimarySelector ) {
        var $node = self.getPriceNode( self, priceSelector, isPrimarySelector, false );

        if ( isPrimarySelector && self.isVar && ! self.replacePrice ) {
            $node = self.$wrapper.find( '.single_variation:last' );
        }

        return $node;
    };

    GermanizedUnitPriceObserver.prototype.getUnitPriceNode = function( self, $price ) {
        if ( $price.length <= 0 ) {
            return [];
        }

        var isSingleProductBlock = $price.parents( '.wp-block-woocommerce-product-price[data-is-descendent-of-single-product-template]' ).length > 0;

        if ( 'SPAN' === $price[0].tagName ) {
            return self.$wrapper.find( '.price-unit' );
        } else {
            if ( isSingleProductBlock ) {
                return self.$wrapper.find( '.wp-block-woocommerce-gzd-product-unit-price[data-is-descendent-of-single-product-template] .price-unit' );
            } else {
                return self.$wrapper.find( '.price-unit:not(.wc-gzd-additional-info-placeholder, .wc-gzd-additional-info-loop)' );
            }
        }
    };

    GermanizedUnitPriceObserver.prototype.stopObserver = function( self, priceSelector ) {
        var observer = self.getObserver( self, priceSelector );

        if ( observer ) {
            observer.disconnect();
        }
    };

    GermanizedUnitPriceObserver.prototype.startObserver = function( self, priceSelector, isPrimary ) {
        var observer = self.getObserver( self, priceSelector ),
            $node    = self.getObserverNode( self, priceSelector, isPrimary );

        if ( observer ) {
            self.stopObserver( self, priceSelector );

            if ( $node.length > 0 ) {
                observer.observe( $node[0], { attributes: true, childList: true, subtree: true, characterData: true, attributeFilter: ['style'] } );
            }

            return true;
        }

        return false;
    };

    GermanizedUnitPriceObserver.prototype.initObservers = function(self ) {
        if ( Object.keys( self.observer ).length !== 0 ) {
            return;
        }

        $.each( self.params.price_selector, function( priceSelector, priceArgs ) {
            var isPrimary       = priceArgs.hasOwnProperty( 'is_primary_selector' ) ? priceArgs['is_primary_selector'] : false,
                $observerNode   = self.getObserverNode( self, priceSelector, isPrimary ),
                currentObserver = false;

            if ( $observerNode.length > 0 && $observerNode.is( ':visible' ) ) {
                // Callback function to execute when mutations are observed
                var callback = function( mutationsList, observer ) {
                    var $priceNode = self.getPriceNode( self, priceSelector, isPrimary );

                    for ( let mutation of mutationsList ) {
                        let $element = $( mutation.target );

                        if ( $element.length > 0 ) {
                            let $priceElement;

                            if ( $element.is( priceSelector ) ) {
                                $priceElement = $element;
                            } else {
                                $priceElement = $element.parents( priceSelector );
                            }

                            if ( $priceElement.length > 0 ) {
                                $priceNode = $priceElement;
                            }
                        }
                    }

                    /**
                     * Clear the timeout and abort open AJAX requests as
                     * a new mutation has been observed
                     */
                    if ( self.timeout ) {
                        clearTimeout( self.timeout );
                    }

                    var $unitPrice   = self.getUnitPriceNode( self, $priceNode ),
                        hasRefreshed = false;

                    if ( $priceNode.length <= 0 ) {
                        return false;
                    }

                    self.stopObserver( self, priceSelector );

                    if ( $unitPrice.length > 0 ) {
                        self.setUnitPriceLoading( self, $unitPrice );

                        /**
                         * Need to use a tweak here to make sure our variation listener
                         * has already adjusted the variationId (in case necessary).
                         */
                        self.timeout = setTimeout(function() {
                            self.stopObserver( self, priceSelector );

                            var priceData = self.getCurrentPriceData( self, $priceNode, priceArgs['is_total_price'], isPrimary, priceArgs['quantity_selector'] );
                            var isVisible = $priceNode.is( ':visible' );

                            if ( priceData ) {
                                if ( self.isRefreshingUnitPrice( self.getCurrentProductId( self ) ) ) {
                                    self.abortRefreshUnitPrice( self.getCurrentProductId( self ) );
                                }

                                hasRefreshed = true;
                                self.refreshUnitPrice( self, priceData, priceSelector, isPrimary );
                            }

                            if ( ! hasRefreshed && $unitPrice.length > 0 ) {
                                self.unsetUnitPriceLoading( self, $unitPrice );

                                if ( ! isVisible && isPrimary ) {
                                    $unitPrice.hide();
                                }
                            }

                            self.startObserver( self, priceSelector, isPrimary );
                        }, 500 );
                    }
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
                    self.startObserver( self, priceSelector, isPrimary );
                }
            }
        });
    };

    GermanizedUnitPriceObserver.prototype.getObserver = function( self, priceSelector ) {
        if ( self.observer.hasOwnProperty( priceSelector ) ) {
            return self.observer[ priceSelector ];
        }

        return false;
    };

    GermanizedUnitPriceObserver.prototype.cancelObservers = function( self ) {
        for ( var key in self.observer ) {
            if ( self.observer.hasOwnProperty( key ) ) {
                self.observer[ key ].disconnect();
                delete self.observer[ key ];
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
            self.variationId = parseInt( variation.variation_id );
        }

        self.initObservers( self );
    };

    GermanizedUnitPriceObserver.prototype.getCurrentPriceData = function( self, priceSelector, isTotalPrice, isPrimary, quantitySelector ) {
        quantitySelector = quantitySelector && '' !== quantitySelector ? quantitySelector : self.params.qty_selector;
        var $price = ( typeof priceSelector === 'string' || priceSelector instanceof String ) ? self.getPriceNode( self, priceSelector, isPrimary ) : priceSelector;

        if ( $price.length > 0 ) {
            // Add a tmp hidden class to detect hidden elements in cloned obj
            $price.find( ':hidden' ).addClass( 'wc-gzd-is-hidden' );

            var $unit_price = self.getUnitPriceNode( self, $price ),
                $priceCloned = $price.clone();

            // Remove price suffix from cloned DOM element to prevent finding the wrong (sale) price
            $priceCloned.find( '.woocommerce-price-suffix' ).remove();
            $priceCloned.find( '.wc-gzd-is-hidden' ).remove();

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

            $price.find( '.wc-gzd-is-hidden' ).removeClass( 'wc-gzd-is-hidden' );

            if ( $unit_price.length > 0 && price ) {
                if ( isTotalPrice ) {
                    price = parseFloat( price ) / qty;

                    if ( sale_price ) {
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
        }

        $unit_price.data( 'org-html', unitPriceOrg );

        return unitPriceOrg;
    };

    GermanizedUnitPriceObserver.prototype.unsetUnitPriceLoading = function( self, $unit_price, newHtml ) {
        newHtml = newHtml || $unit_price.data( 'org-html' );

        $unit_price.html( newHtml );

        if ( $unit_price.hasClass( 'wc-gzd-loading' ) ) {
            $unit_price.removeClass( 'wc-gzd-loading' );
        }

        if ( typeof newHtml === "string" && newHtml.length > 0 ) {
            $unit_price.show();
        }
    };

    GermanizedUnitPriceObserver.prototype.isRefreshingUnitPrice = function( currentProductId ) {
        return germanized.unit_price_observer_queue.exists( currentProductId );
    };

    GermanizedUnitPriceObserver.prototype.abortRefreshUnitPrice = function( currentProductId ) {
        return germanized.unit_price_observer_queue.abort( currentProductId );
    };

    GermanizedUnitPriceObserver.prototype.refreshUnitPrice = function( self, priceData, priceSelector, isPrimary ) {
        germanized.unit_price_observer_queue.add( self, self.getCurrentProductId( self ), priceData, priceSelector, isPrimary );
    };

    /**
     * Function to call wc_gzd_variation_form on jquery selector.
     */
    $.fn.wc_germanized_unit_price_observer = function() {
        if ( $( this ).data( 'unitPriceObserver' ) ) {
            $( this ).data( 'unitPriceObserver' ).destroy();
        }

        new GermanizedUnitPriceObserver( this );
        return this;
    };

    $( function() {
        if ( typeof wc_gzd_unit_price_observer_params !== 'undefined' ) {
            $( wc_gzd_unit_price_observer_params.wrapper ).each( function() {
                if ( $( this ).is( 'body' ) ) {
                    return;
                }

                $( this ).wc_germanized_unit_price_observer();
            });
        }
    });

})( jQuery, window, document );

window.germanized = window.germanized || {};