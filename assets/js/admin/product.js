jQuery( function ( $ ) {
    var wc_gzd_product = {

        warranty_upload_file_frame: false,

        init: function() {
            var self = wc_gzd_product;

            $( document )
                .on( 'click', 'a.wc-gzd-add-new-country-specific-delivery-time', self.onAddNewDeliveryTime )
                .on( 'click', 'a.wc-gzd-remove-country-specific-delivery-time', self.onRemoveDeliveryTime )
                .on( 'change', 'input[name=_defective_copy]', self.onChangeDefectiveCopy )
                .on( 'click', '.upload_warranty_button', self.onUploadWarranty )
                .on( 'click', 'a.wc-gzd-warranty-delete', self.onRemoveWarranty );

            $( 'input[name=_defective_copy]' ).trigger( 'change' );
        },

        onUploadWarranty: function( e ) {
            var self            = wc_gzd_product,
                $el             = $( this ),
                $delete_btn     = $el.closest( 'p.form-field, p.form-row' ).find( 'a.wc-gzd-warranty-delete' ),
                $attach_field   = $el.closest( 'p.form-field, p.form-row' ).find( '.wc-gzd-warranty-attachment' );

            e.preventDefault();

            // Create the media frame.
            self.warranty_upload_file_frame = wp.media.frames.customHeader = wp.media({
                // Set the title of the modal.
                title: $el.data( 'choose' ),
                library: {
                    type: 'application/pdf'
                },
                button: {
                    text: $el.data( 'update' )
                },
                multiple: false,
            });

            // When an image is selected, run a callback.
            self.warranty_upload_file_frame.on( 'select', function() {
                var selection = self.warranty_upload_file_frame.state().get( 'selection' );

                selection.map( function( attachment ) {
                    attachment = attachment.toJSON();

                    if ( attachment.filename ) {
                        $el.text( attachment.filename );
                        $delete_btn.removeClass( 'file-missing' ).show();
                        $attach_field.val( attachment.id );
                    }
                });
            });

            self.warranty_upload_file_frame.on( 'open', function() {
                var selection = self.warranty_upload_file_frame.state().get( 'selection' );
                var id        = $attach_field.val();

                if ( id.length > 0 ) {
                    var attachment = wp.media.attachment( id );
                    selection.add( attachment ? [attachment] : [] );

                    self.warranty_upload_file_frame.content.mode( 'browse' );
                } else {
                    selection.remove();

                    self.warranty_upload_file_frame.content.mode( 'upload' );
                }
            });

            // Finally, open the modal.
            self.warranty_upload_file_frame.open();
        },

        onRemoveWarranty: function() {
            var $field = $( this ).closest( 'p.form-field, p.form-row' );

            $field.find( '.wc-gzd-warranty-attachment' ).val( '' );
            $field.find( 'a.upload_warranty_button' ).text( $field.find( 'a.upload_warranty_button' ).data( 'default-label' ) );
            $field.find( 'a.wc-gzd-warranty-delete' ).addClass( 'file-missing' ).hide();

            return false;
        },

        onChangeDefectiveCopy: function() {
            if ( $( this ).is( ':checked' ) ) {
                $( '#wc-gzd-product-defect-description' ).addClass( 'show' ).show();
            } else {
                $( '#wc-gzd-product-defect-description' ).removeClass( 'show' ).hide();
            }
        },

        onAddNewDeliveryTime: function() {
            var $parent = $( this ).parents( '#shipping_product_data' );

            if ( $parent.length === 0 ) {
                $parent = $( this ).parents( '.woocommerce_variable_attributes' );
            }

            var $select2 = $parent.find( '.wc-gzd-add-country-specific-delivery-time-template .wc-gzd-delivery-time-search.enhanced' );

            /**
             * Destroy the select2 element from template in case it still exists and has been initialized
             */
            if ( $select2.length > 0 ) {
                $select2.selectWoo( 'destroy' );
                $select2.removeClass( 'enhanced' );
            }

            var $template = $parent.find( '.wc-gzd-add-country-specific-delivery-time-template:first' ).clone();

            $template.removeClass( 'wc-gzd-add-country-specific-delivery-time-template' ).addClass( 'wc-gzd-country-specific-delivery-time-new' );
            $parent.find( '.wc-gzd-new-country-specific-delivery-time-placeholder' ).append( $template ).show();

            $( document.body ).trigger( 'wc-enhanced-select-init' );

            return false;
        },

        onRemoveDeliveryTime: function() {
            var $parent = $( this ).parents( '.form-row, .form-field' );

            // Trigger change to notify Woo about an update (variations).
            $parent.find( 'select' ).trigger( 'change' );
            $parent.remove();

            return false;
        }
    };

    wc_gzd_product.init();
});