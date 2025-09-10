window.shiptastic = window.shiptastic || {};
window.shiptastic.admin = window.shiptastic.admin || {};

( function( $, shipments ) {
    shipments.admin.shipments_admin = {
        params: {},

        init: function() {
            var self = shipments.admin.shipments_admin;
            self.params = wc_shiptastic_admin_params;

            $( document )
                .on( 'click', 'a.woocommerce-shiptastic-input-toggle-trigger', this.onInputToggleClick )
                .on( 'change', '.wc-shiptastic-toggle-input', self.onChangeToggleInput )
                .on( 'click', 'a.wc-shiptastic-ajax-action', self.ajaxAction );
        },

        ajaxAction: function() {
            var self  = shipments.admin.shipments_admin,
                $this = $( this );

            var params = { ...Object.fromEntries( new URLSearchParams( $this.data( 'args' ) ) ), ...{
                action: 'woocommerce_stc_' + $this.data( 'action' ),
                security: $this.data( 'nonce' ),
                args: $this.data( 'args' )
            } };

            $this.addClass( 'wc-shiptastic-is-loading' );
            $this.append( '<span class="spinner is-active"></span>' );
            $this.prop( 'disabled', true );

            $( '.wp-core-ui' ).find( '.wc-shiptastic-ajax-error' ).remove();

            self.doAjax( params, function( data ) {
                $this.find( '.spinner' ).remove();
                $this.removeClass( 'wc-shiptastic-is-loading' );
                $this.prop( 'disabled', false );

                if ( data.hasOwnProperty( 'redirect' ) && true === data.redirect ) {
                    var url = data.url.length > 0 ? data.url : $this.attr( 'href' );

                    window.location.href = url;
                } else if ( data.hasOwnProperty( 'success_text' ) && data.success_text.length > 0 ) {
                    $this.addClass( 'wc-shiptastic-success' );
                    $this.text( data['success_text'] );
                }
            }, function( data ) {
                $this.find( '.spinner' ).remove();
                $this.removeClass( 'wc-shiptastic-is-loading' );
                $this.prop( 'disabled', false );

                var $wrapper = $( '#wpbody-content' ).find( '.wrap' ),
                    append = false,
                    notice = '<div class="notice notice-error wc-shiptastic-ajax-error"><p>' + data.message + '</p></div>';

                if ( $( '.wc-shiptastic-error-wrapper' ).length > 0 ) {
                    $wrapper = $( '.wc-shiptastic-error-wrapper' );
                    append = true;
                }

                if ( append ) {
                    $wrapper.append( notice );
                } else {
                    $wrapper.before( notice );
                }

                $( '.wc-shiptastic-ajax-error' )[0].scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });
            } );

            return false;
        },

        doAjax: function( params, cSuccess, cError ) {
            var self     = shipments.admin.shipments_admin,
                url = self.params.ajax_url,
                $wrapper = self.$wrapper;

            cSuccess = cSuccess || self.onAjaxSuccess;
            cError   = cError || self.onAjaxError;

            $.ajax({
                type: "POST",
                url:  url,
                data: params,
                success: function( data ) {
                    if ( data.success ) {
                        cSuccess.apply( $wrapper, [ data ] );
                    } else {
                        cError.apply( $wrapper, [ data ] );
                    }
                },
                error: function( data ) {
                    cError.apply( $wrapper, [ data ] );
                },
                dataType: 'json'
            });
        },

        onAjaxError: function( data ) {

        },

        onAjaxSuccess: function( data ) {

        },

        onChangeToggleInput: function() {
            var $checkbox = $( this ),
                $row = $checkbox.parents( 'fieldset' ),
                $toggle   = $row.find( 'span.woocommerce-shiptastic-input-toggle' ),
                isChecked = $checkbox.is( ':checked' );

            if ( isChecked && ! $toggle.hasClass( 'woocommerce-input-toggle--enabled' ) ) {
                $toggle.removeClass( 'woocommerce-input-toggle--disabled' );
                $toggle.addClass( 'woocommerce-input-toggle--enabled' );
            } else if ( ! isChecked && ! $toggle.hasClass( 'woocommerce-input-toggle--disabled' ) ) {
                $toggle.removeClass( 'woocommerce-input-toggle--enabled' );
                $toggle.addClass( 'woocommerce-input-toggle--disabled' );
            }
        },

        onInputToggleClick: function() {
            var $toggle   = $( this ).find( 'span.woocommerce-shiptastic-input-toggle' ),
                $row      = $toggle.parents( 'fieldset' ),
                $checkbox = $row.find( 'input[type=checkbox]' ).length > 0 ? $row.find( 'input[type=checkbox]' ) : $toggle.parent().nextAll( 'input[type=checkbox]:first' ),
                $enabled  = $toggle.hasClass( 'woocommerce-input-toggle--enabled' );

            $toggle.removeClass( 'woocommerce-input-toggle--enabled' );
            $toggle.removeClass( 'woocommerce-input-toggle--disabled' );

            if ( $enabled ) {
                $checkbox.prop( 'checked', false );
                $toggle.addClass( 'woocommerce-input-toggle--disabled' );
            } else {
                $checkbox.prop( 'checked', true );
                $toggle.addClass( 'woocommerce-input-toggle--enabled' );
            }

            $checkbox.trigger( 'change' );

            return false;
        }
    };

    $( document ).ready( function() {
        shipments.admin.shipments_admin.init();
    });

})( jQuery, window.shiptastic );