window.eu_owb = window.eu_owb || {};
window.eu_owb.order_withdrawal = window.eu_owb.order_withdrawal || {};

( function( $, eu_owb ) {
    /**
     * Core
     */
    eu_owb.order_withdrawal = {
        params: {},

        init: function () {
            var self  = eu_owb.order_withdrawal;
            self.params  = eu_owb_woocommerce_order_withdrawal_params;

            $( document ).on( 'submit', '.order-withdrawal-request', self.onSubmitOrderRequest );
            $( document ).on( 'change', '.order-withdrawal-request #order-withdrawal-request-order', self.onChangeOrder );
            $( document ).on( 'change', '.order-withdrawal-request #manually-select-items', self.onSelectItems );
            $( document ).on( 'change', '.order-withdrawal-request #select-all-items', self.selectAllItems );
            $( document ).on( 'change', '.order-withdrawal-request #order-withdrawal-request-order-number, .order-withdrawal-request #order-withdrawal-request-email', self.onChangeInputs );
        },

        onChangeInputs: function() {
            var self = eu_owb.order_withdrawal,
                $form = $( this ).parents( 'form' ),
                order = $form.find( '#order-withdrawal-request-order-number' ).val(),
                email = $form.find( '#order-withdrawal-request-email' ).val(),
                $partial = $form.find( '.order-supports-partial-withdrawal' ),
                $mainButton = $form.find( '.button[type=submit]' ),
                data = $form.serialize();

            if ( $partial.length <= 0 ) {
                return;
            }

            if ( order && email ) {
                $form.addClass( 'loading' );
                $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', true );
                $mainButton.prop( 'disabled', true ).addClass( 'loading' );

                $.ajax( {
                    type: 'POST',
                    url: self.params.wc_ajax_url.toString().replace('%%endpoint%%', 'eu_owb_woocommerce_order_withdrawal_request_supports_partial'),
                    data: data,
                    dataType: 'json',
                }).done( function ( response ) {
                    $form.removeClass( 'loading' );
                    $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', false );
                    $mainButton.prop( 'disabled', false ).removeClass( 'loading' );

                    if ( true === response.data['supports_partial_withdrawal'] ) {
                        $partial.removeClass( 'hidden' );
                    } else {
                        $partial.addClass( 'hidden' );
                    }
                }).fail( function ( xhr ) {
                    $form.removeClass( 'loading' );
                    $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', false );
                    $mainButton.prop( 'disabled', false ).removeClass( 'loading' );

                    $partial.addClass( 'hidden' );
                });
            } else {
                $partial.addClass( 'hidden' );
            }
        },

        selectAllItems: function() {
            var self = eu_owb.order_withdrawal,
                $form = $( this ).parents( 'form' ),
                $table = $form.find( '.order-withdrawal-request-items-table' );

            if ( $( this ).is( ':checked' ) ) {
                $table.find( '.order-withdrawal-request-item-checkbox' ).prop( 'checked', true );
            } else {
                $table.find( '.order-withdrawal-request-item-checkbox' ).prop( 'checked', false );
            }
        },

        onSelectItems: function() {
            var self = eu_owb.order_withdrawal,
                $form = $( this ).parents( 'form' ),
                $table = $form.find( '.order-withdrawal-request-items-table' );

            $table.toggleClass( 'hidden' );
        },

        onChangeOrder: function() {
            var self = eu_owb.order_withdrawal,
                $form = $( this ).parents( 'form' ),
                $noticeWrapper = $form.find( '.eu-owb-notice-wrapper' ),
                $mainButton = $form.find( '.button[type=submit]' ),
                data = $form.serialize();

            $noticeWrapper.find( ".notice" ).remove();
            $form.addClass( 'loading' );
            $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', true );
            $mainButton.prop( 'disabled', true ).addClass( 'loading' );

            $form.find( '.eu-owb-order-item-select-wrapper' ).addClass( 'loading' );

            $.ajax( {
                type: 'POST',
                url: self.params.wc_ajax_url.toString().replace('%%endpoint%%', 'eu_owb_woocommerce_order_withdrawal_request_select_order'),
                data: data,
                dataType: 'json',
            }).done( function ( response ) {
                $form.removeClass( 'loading' );
                $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', false );
                $form.find( '.eu-owb-order-item-select-wrapper' ).removeClass( 'loading' );
                $mainButton.prop( 'disabled', false ).removeClass( 'loading' );

                $form.find( '.eu-owb-order-item-select-wrapper' ).html( response.html ).show();
            }).fail( function ( xhr ) {
                $form.removeClass( 'loading' );
                $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', false );
                $mainButton.prop( 'disabled', false ).removeClass( 'loading' );

                try {
                    var response = JSON.parse( xhr.responseText );
                } catch( $e ) {
                    response = {};
                }

                $.each( response.data, function( i, error ) {
                    $noticeWrapper.append( '<p class="woocommerce-error notice">' + error.message + '</p>' );
                });

                $noticeWrapper[0].scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            });

            return false;
        },

        onSubmitOrderRequest: function() {
            var self = eu_owb.order_withdrawal,
                $form = $( this ),
                $noticeWrapper = $form.find( '.eu-owb-notice-wrapper' ),
                $mainButton = $form.find( '.button[type=submit]' ),
                data = $form.serialize();

            $noticeWrapper.find( ".notice" ).remove();
            $form.addClass( 'loading' );
            $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', true );
            $mainButton.prop( 'disabled', true ).addClass( 'loading' );

            $.ajax( {
                type: 'POST',
                url: self.params.wc_ajax_url.toString().replace('%%endpoint%%', 'eu_owb_woocommerce_order_withdrawal_request'),
                data: data,
                dataType: 'json',
            }).done( function ( response ) {
                $form.removeClass( 'loading' );
                $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', false );
                $form.find( '.eu-owb-form-fields' ).hide();
                $mainButton.hide();

                $noticeWrapper.append( '<p class="woocommerce-message">' + response.data + '</p>' );

                $noticeWrapper[0].scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            }).fail( function ( xhr ) {
                $form.removeClass( 'loading' );
                $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', false );
                $mainButton.prop( 'disabled', false ).removeClass( 'loading' );

                try {
                    var response = JSON.parse( xhr.responseText );
                } catch( $e ) {
                    response = {};
                }

                $.each( response.data, function( i, error ) {
                    $noticeWrapper.append( '<p class="woocommerce-error notice">' + error.message + '</p>' );
                });

                $noticeWrapper[0].scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            });

            return false;
        },
    };

    $( document ).ready( function() {
        eu_owb.order_withdrawal.init();
    });
})( jQuery, window.eu_owb );
