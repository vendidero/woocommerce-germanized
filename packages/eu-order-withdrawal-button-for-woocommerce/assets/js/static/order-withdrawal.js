window.eu_owb = window.eu_owb || {};
window.eu_owb.order_withdrawal = window.eu_owb.order_withdrawal || {};

( function( $, eu_owb ) {
    /**
     * Core
     */
    eu_owb.order_withdrawal = {
        params: {},
        startTime: 0,

        init: function () {
            var self  = eu_owb.order_withdrawal;
            self.params  = eu_owb_woocommerce_order_withdrawal_params;
            self.startTime = self.getTimestampSec();

            $( document ).on( 'submit', '.order-withdrawal-request', self.onSubmitOrderRequest );
            $( document ).on( 'change', '.order-withdrawal-request #order-withdrawal-request-order', self.onChangeOrder );
            $( document ).on( 'change', '.order-withdrawal-request #manually-select-items', self.onSelectItems );
            $( document ).on( 'change', '.order-withdrawal-request #select-all-items', self.selectAllItems );
            $( document ).on( 'change', '.order-withdrawal-request #order-withdrawal-request-order-number, .order-withdrawal-request #order-withdrawal-request-email', self.onChangeInputs );

            // Inline validation
            $( document ).on( 'input validate change focusout', '.order-withdrawal-request .input-text, .order-withdrawal-request select', self.validateField );
        },

        validateField: function ( e )  {
            var $this = $( this ),
                $parent = $this.closest( '.form-row' ),
                validated = true,
                validate_required = $parent.is( '.validate-required' ),
                validate_email = $parent.is( '.validate-email' ),
                pattern = '',
                event_type = e.type;

            if ( 'input' === event_type ) {
                $this
                    .removeAttr( 'aria-invalid' )
                    .removeAttr( 'aria-describedby' );
                $parent.find( '.withdrawal-inline-error-message' ).remove();
                $parent.removeClass( 'woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email woocommerce-validated' );
            }

            if (
                'validate' === event_type ||
                'change' === event_type ||
                'focusout' === event_type
            ) {
                if ( validate_required ) {
                    if (
                        ( 'checkbox' === $this.attr( 'type' ) &&
                            ! $this.is( ':checked' ) ) ||
                        $this.val() === ''
                    ) {
                        $this.attr( 'aria-invalid', 'true' );
                        $parent
                            .removeClass( 'woocommerce-validated' )
                            .addClass(
                                'woocommerce-invalid woocommerce-invalid-required-field'
                            );
                        validated = false;
                    }
                }

                if ( validate_email ) {
                    if ( $this.val() ) {
                        /* https://stackoverflow.com/questions/2855865/jquery-validate-e-mail-address-regex */
                        pattern = new RegExp(
                            // eslint-disable-next-line max-len
                            /^([a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+(\.[a-z\d!#$%&'*+\-\/=?^_`{|}~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]+)*|"((([ \t]*\r\n)?[ \t]+)?([\x01-\x08\x0b\x0c\x0e-\x1f\x7f\x21\x23-\x5b\x5d-\x7e\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|\\[\x01-\x09\x0b\x0c\x0d-\x7f\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))*(([ \t]*\r\n)?[ \t]+)?")@(([a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[a-z\d\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.)+([a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]|[a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF][a-z\d\-._~\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]*[0-9a-z\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])\.?$/i
                        ); // eslint-disable-line max-len

                        if ( ! pattern.test( $this.val() ) ) {
                            $this.attr( 'aria-invalid', 'true' );
                            $parent
                                .removeClass( 'woocommerce-validated' )
                                .addClass(
                                    'woocommerce-invalid woocommerce-invalid-email'
                                ); // eslint-disable-line max-len
                            validated = false;
                        }
                    }
                }

                if ( validated ) {
                    $this
                        .removeAttr( 'aria-invalid' )
                        .removeAttr( 'aria-describedby' );
                    $parent.find( '.checkout-inline-error-message' ).remove();
                    $parent
                        .removeClass(
                            'woocommerce-invalid woocommerce-invalid-required-field woocommerce-invalid-email'
                        )
                        .addClass( 'woocommerce-validated' ); // eslint-disable-line max-len
                }
            }
        },

        onChangeInputs: function() {
            var self = eu_owb.order_withdrawal,
                $this = $( this ),
                $form = $( this ).parents( 'form' ),
                order = $form.find( '#order-withdrawal-request-order-number' ).val(),
                email = $form.find( '#order-withdrawal-request-email' ).val(),
                $partial = $form.find( '.order-supports-partial-withdrawal' ),
                data = $form.serialize();

            if ( $partial.length <= 0 ) {
                return;
            }

            if ( order && self.isEmail( email ) ) {
                $.ajax( {
                    type: 'POST',
                    url: self.params.wc_ajax_url.toString().replace('%%endpoint%%', 'eu_owb_woocommerce_order_withdrawal_request_supports_partial'),
                    data: data,
                    dataType: 'json',
                }).done( function ( response ) {
                    $form = $this.parents( 'form' );

                    if ( ! $form.hasClass( 'loading' ) && true === response.data['supports_partial_withdrawal'] ) {
                        $partial.removeClass( 'hidden' );
                    } else {
                        $partial.addClass( 'hidden' );
                    }
                }).fail( function ( xhr ) {
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

        isEmail: function( email ) {
            var re = /^\S+@\S+\.\S+$/;
            return re.test( email );
        },

        onChangeOrder: function() {
            var self = eu_owb.order_withdrawal,
                $form = $( this ).parents( 'form' ),
                $noticeWrapper = $form.find( '.eu-owb-notice-wrapper' ),
                $mainButton = $form.find( '.button[type=submit]' ),
                data = $form.serialize(),
                currentOrder = $( this ).val(),
                originalOrder = $form.find( '#original-order-id' ).length > 0 ? $form.find( '#original-order-id' ).val() : 0,
                $deleteCheckbox = $form.find( '.order-withdrawal-delete-original-request-checkbox' );

            $noticeWrapper.find( ".notice" ).remove();
            $form.addClass( 'loading' );
            $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', true );
            $mainButton.prop( 'disabled', true ).addClass( 'loading' );

            $form.find( '.eu-owb-order-item-select-wrapper' ).addClass( 'loading' );

            if ( $deleteCheckbox.length > 0 ) {
                if ( currentOrder === originalOrder ) {
                    $deleteCheckbox.addClass( 'hidden' );
                } else {
                    $deleteCheckbox.removeClass( 'hidden' );
                }
            }

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
                $form.find( '.eu-owb-order-item-select-wrapper' ).removeClass( 'loading' );
                $form.find( '.eu-owb-order-item-select-wrapper' ).html( '' ).hide();

                try {
                    var response = JSON.parse( xhr.responseText );
                } catch( $e ) {
                    response = {};
                }

                if ( currentOrder ) {
                    $.each( response.data, function( i, error ) {
                        $noticeWrapper.append( '<p class="woocommerce-error notice">' + error.message + '</p>' );
                    });

                    $noticeWrapper[0].scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });
                }
            });

            return false;
        },

        getTimestampSec: function() {
            return Math.floor(Date.now() / 1000 );
        },

        onSubmitOrderRequest: function() {
            var self = eu_owb.order_withdrawal,
                $form = $( this ),
                $noticeWrapper = $form.find( '.eu-owb-notice-wrapper' ),
                $mainButton = $form.find( '.button[type=submit]' ),
                data = $form.serialize(),
                endTime = self.getTimestampSec();

            $noticeWrapper.find( ".notice" ).remove();
            $form.addClass( 'loading' );
            $form.find( ':input:not(.disabled):not([type=hidden])' ).prop( 'disabled', true );
            $mainButton.prop( 'disabled', true ).addClass( 'loading' );

            $.ajax( {
                type: 'POST',
                url: self.params.wc_ajax_url.toString().replace('%%endpoint%%', 'eu_owb_woocommerce_order_withdrawal_request'),
                data: data + '&start_timestamp=' + self.startTime + '&end_timestamp=' + endTime,
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

                self.startTime = self.getTimestampSec();
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

                    if ( error.hasOwnProperty( 'field' ) ) {
                        const fieldName = error['field'].replace( '_', '-' );
                        const $field = $form.find( '#order-withdrawal-request-' + fieldName + '_field' )

                        if ( $field.length > 0 ) {
                            $field.addClass( 'woocommerce-invalid woocommerce-invalid-required-field' );
                        }
                    }
                });

                $noticeWrapper[0].scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

                self.startTime = self.getTimestampSec();
            });

            return false;
        },
    };

    $( document ).ready( function() {
        eu_owb.order_withdrawal.init();
    });
})( jQuery, window.eu_owb );
