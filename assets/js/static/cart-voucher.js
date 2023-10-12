/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order */
window.germanized = window.germanized || {};

( function( $, germanized ) {

    /**
     * Order Data Panel
     */
    germanized.cart_voucher = {
        params: {},
        vouchers: {},

        init: function() {
            this.params   = wc_gzd_cart_voucher_params;
            this.vouchers = wc_gzd_cart_voucher_params.vouchers;

            if ( $( '.woocommerce-checkout' ).length ) {
                this.manipulate_checkout_totals();
            }

            if ( $( '.woocommerce-cart-form' ).length ) {
                this.manipulate_cart_totals();
            }

            $( document.body ).on( 'updated_cart_totals', this.manipulate_cart_totals );
            $( document.body ).on( 'updated_checkout', this.manipulate_checkout_totals );

            $( document.body ).on( 'applied_coupon', this.refresh_cart_vouchers );
            $( document.body ).on( 'removed_coupon', this.refresh_cart_vouchers );
        },

        refresh_cart_vouchers: function() {
            var self = germanized.cart_voucher;

            setTimeout( function() {
                $.ajax({
                    type: 'POST',
                    url:  self.params.wc_ajax_url.toString().replace( '%%endpoint%%', 'gzd_refresh_cart_vouchers' ),
                    data: {
                        security: self.params.refresh_cart_vouchers_nonce,
                    },
                    success: function( data ) {
                        self.vouchers = data.vouchers;
                        self.manipulate_cart_totals();
                    },
                    dataType: 'json'
                });
            }, 75 );
        },

        manipulate_checkout_totals: function( e, ajaxData ) {
            var self = germanized.cart_voucher,
            $table   = $( '.woocommerce-checkout #order_review table' );

            ajaxData = ( typeof ajaxData === 'undefined' ) ? {} : ajaxData;

            /**
             * Refresh new voucher data by fragments
             */
            if ( ajaxData.hasOwnProperty( 'fragments' ) && ajaxData.fragments.hasOwnProperty( '.gzd-vouchers' ) ) {
                self.vouchers = ajaxData.fragments['.gzd-vouchers'];
            }

            if ( ! self.params.display_prices_including_tax ) {
                self.move_vouchers_before_total_checkout();
            }

            self.manipulate_coupons( $table );
        },

        manipulate_cart_totals: function() {
            var $table = $( '.cart_totals table' ),
                $total = $table.find( 'tr.order-total' ),
                self   = germanized.cart_voucher;

            if ( ! self.params.display_prices_including_tax ) {
                self.move_vouchers_before_total( $table, $total );

                if ( $( '.woocommerce-checkout' ).length ) {
                    self.move_vouchers_before_total_checkout();
                }
            }

            self.manipulate_coupons( $table );
        },

        manipulate_coupons: function( $table ) {
            var self = germanized.cart_voucher;

            $.each( self.vouchers, function( voucherId, voucher ) {
                var $coupon = self.get_voucher_coupon( voucher, $table ),
                    $fee    = self.get_voucher_fee( voucher, $table );

                $coupon.hide();

                if ( $fee.length > 0 && $coupon.length > 0 ) {
                    var $remove_link = $coupon.find( 'a.woocommerce-remove-coupon' );

                    if ( $remove_link.length > 0 ) {
                        $fee.find( 'td:last' ).append( " " );
                        $fee.find( 'td:last' ).append( $remove_link );
                    }
                }
            } );
        },

        move_vouchers_before_total_checkout: function() {
            var $table = $( '.woocommerce-checkout #order_review table' ),
                $total = $table.find( 'tr.order-total' ),
                self   = germanized.cart_voucher;

            self.move_vouchers_before_total( $table, $total );
        },

        get_voucher_fee: function( voucher, $table ) {
            return $table.find( 'tr.fee th:contains("' + voucher.name + '")' ).parents( 'tr' );
        },

        get_voucher_coupon: function( voucher, $table ) {
            return $table.find( 'tr.' + voucher.coupon_class );
        },

        move_vouchers_before_total: function( $table, $total ) {
            var self = germanized.cart_voucher;

            $.each( self.vouchers, function( voucherId, voucher ) {
                var $fee = self.get_voucher_fee( voucher, $table );

                if ( $fee.length > 0 ) {
                    $fee.insertBefore( $total );
                }
            } );
        },
    };

    $( document ).ready( function() {
        germanized.cart_voucher.init();
    });

})( jQuery, window.germanized );
