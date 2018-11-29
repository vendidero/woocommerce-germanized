/*global woocommerce_admin_meta_boxes, woocommerce_admin, accounting, woocommerce_admin_meta_boxes_order */
window.germanized = window.germanized || {};

( function( $, wp, germanized ) {

    /**
     * Order Data Panel
     */
    germanized.settings = {

        init: function() {

            try {
                $( document.body ).on( 'wc-enhanced-select-init wc-gzd-enhanced-select-init', this.onEnhancedSelectInit ).trigger( 'wc-gzd-enhanced-select-init' );
            } catch( err ) {
                // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
                window.console.log( err );
            }

            $( document )
                .on( 'change', 'select#woocommerce_gzd_checkboxes_parcel_delivery_show_special', this.onParcelDeliveryShowSpecial )
                .on( 'change', 'input#woocommerce_gzd_order_pay_now_button', this.onChangePayNow )
                .on( 'change', 'input[name=woocommerce_gzd_dispute_resolution_type]', this.onChangeDisputeResolutionType )
                .on( 'click', 'a.woocommerce-gzd-input-toggle-trigger', this.onInputToogleClick );

            $( 'select#woocommerce_gzd_checkboxes_parcel_delivery_show_special' ).trigger( 'change' );
            $( 'input#woocommerce_gzd_order_pay_now_button' ).trigger( 'change' );
            $( 'input[name=woocommerce_gzd_dispute_resolution_type]:checked' ).trigger( 'change' );

            this.initMailSortable();
        },

        onEnhancedSelectInit: function() {
            // Tag select
            $( ':input.wc-gzd-enhanced-tags' ).filter( ':not(.enhanced)' ).each( function () {
                var select2_args = {
                    minimumResultsForSearch: 10,
                    allowClear: $( this ).data( 'allow_clear' ) ? true : false,
                    placeholder: $( this ).data( 'placeholder' ),
                    tags: true
                };

                $( this ).selectWoo( select2_args ).addClass( 'enhanced' );
            });
        },
      
        onParcelDeliveryShowSpecial: function() {
            var val = $( this ).val();

            if ( 'shipping_methods' === val ) {
                $( 'select#woocommerce_gzd_checkboxes_parcel_delivery_show_shipping_methods' ).parents( 'tr' ).show();
            } else {
                $( 'select#woocommerce_gzd_checkboxes_parcel_delivery_show_shipping_methods' ).parents( 'tr' ).hide();
            }
        },

        onChangePayNow: function() {
            if ( $( this ).is( ':checked' ) ) {
                $( 'select#woocommerce_gzd_order_pay_now_button_disabled_methods' ).parents( 'tr' ).show();
            } else {
                $( 'select#woocommerce_gzd_order_pay_now_button_disabled_methods' ).parents( 'tr' ).hide();
            }
        },

        onChangeDisputeResolutionType: function() {
            var val = $( this ).val();
            var text = $( '#woocommerce_gzd_alternative_complaints_text_' + val );

            $( '[id^=woocommerce_gzd_alternative_complaints_text_]' ).parents( 'tr' ).hide();
            $( '#woocommerce_gzd_alternative_complaints_text_' + val ).parents( 'tr' ).show();
        },

        onInputToogleClick: function() {
            var $toggle   = $( this ).find( 'span.woocommerce-gzd-input-toggle' ),
                $row      = $toggle.parents( 'tr' ),
                $checkbox = $row.find( 'input[type=checkbox]' ),
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
        },

        initMailSortable: function() {
            if ( $( '#woocommerce_gzd_mail_attach_imprint' ).length > 0 ) {
                var table = $( '#woocommerce_gzd_mail_attach_imprint' ).parents( 'table' );
                $( table ).find( 'tbody' ).sortable({
                    items: 'tr',
                    cursor: 'move',
                    axis: 'y',
                    handle: 'td, th',
                    scrollSensitivity: 40,
                    helper:function(e,ui){
                        ui.children().each(function(){
                            jQuery(this).width(jQuery(this).width());
                        });
                        ui.css('left', '0');
                        return ui;
                    },
                    start:function(event,ui) {
                        ui.item.css('background-color','#f6f6f6');
                    },
                    stop:function(event,ui){
                        ui.item.removeAttr('style');
                        var pages = [];
                        $( table ).find( 'tr select' ).each( function() {
                            pages.push( $(this).attr( 'id' ).replace( 'woocommerce_gzd_mail_attach_', '' ) );
                        });
                        $( '#woocommerce_gzd_mail_attach_order' ).val( pages.join() );
                    }
                });
            }
        }
    };

    $( document ).ready( function() {
        germanized.settings.init();
    });

})( jQuery, wp, window.germanized );