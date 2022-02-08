/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order */
window.germanized = window.germanized || {};

( function( $, germanized ) {

    /**
     * Order Data Panel
     */
    germanized.cart_voucher = {
        params: {},

        init: function() {
            this.params = wc_gzd_cart_voucher_params;

            if ( ! this.params.display_prices_including_tax ) {
                if ( $( '.woocommerce-checkout' ).length ) {
                    this.move_vouchers_before_total_checkout();
                } else if ( $( '.woocommerce-cart-form' ).length ) {
                    this.move_vouchers_before_total_cart();
                }

                $( document.body ).on( 'updated_cart_totals', this.move_vouchers_before_total_cart );
                $( document.body ).on( 'updated_checkout', this.move_vouchers_before_total_checkout );
            }
        },

        move_vouchers_before_total_checkout: function() {
            var $table = $( '.woocommerce-checkout #order_review table' ),
                $total = $table.find( 'tr.order-total' ),
                self   = germanized.cart_voucher;

            self.move_vouchers_before_total( $table, $total );
        },

        move_vouchers_before_total: function( $table, $total ) {
            var self = germanized.cart_voucher;

            $table.find( 'tr.fee' ).each( function() {
                var $fee = $( this );

                if ( $fee.find( 'td[data-title^="' + self.params.voucher_prefix + '"]' ) ) {
                    $fee.insertBefore( $total );
                }
            } );
        },

        move_vouchers_before_total_cart: function() {
            var $table = $( '.cart_totals table' ),
                $total = $table.find( 'tr.order-total' ),
                self   = germanized.cart_voucher;

            self.move_vouchers_before_total( $table, $total );

            if ( $( '.woocommerce-checkout' ).length ) {
                self.move_vouchers_before_total_checkout();
            }
        }
    };

    $( document ).ready( function() {
        germanized.cart_voucher.init();
    });

})( jQuery, window.germanized );
