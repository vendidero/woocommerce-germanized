window.shipments = window.shipments || {};
window.shipments.admin = window.shipments.admin || {};

( function( $, shipments ) {
    shipments.admin.shipment_settings = {
        params: {},

        init: function() {
            var self = shipments.admin.shipment_settings;
            self.params = wc_gzd_shipments_admin_settings_params;

            $( document )
                .on( 'click', 'a.woocommerce-gzd-shipment-input-toggle-trigger', self.onInputToggleClick )
                .on( 'change gzd_shipments_show_or_hide_fields', 'table.form-table :input[id]', self.onChangeInput );

            $( 'table.form-table :input[id]' ).trigger( 'gzd_shipments_show_or_hide_fields' );
        },

        getCleanInputId: function( $mainInput ) {
            var self = shipments.admin.shipment_settings,
                fieldId = $mainInput.attr( 'id' ) ? $mainInput.attr( 'id' ) : $mainInput.attr( 'name' );

            if ( self.params.hasOwnProperty( 'clean_input_callback' ) ) {
                var callback = self.params.clean_input_callback,
                    params = [],
                    objectName = '',
                    methodName = '';

                if ( callback.substring( 0, 16 ) === 'shipments.admin.' ) {
                    callback = callback.slice( 16 );

                    params = callback.split( "." );
                    objectName = shipments.admin[params[0]];
                    methodName = params[1];
                } else {
                    params = callback.split( "." );
                    objectName = window[params[0]];
                    methodName = params[1];
                }

                if ( 'object' === typeof objectName && objectName.hasOwnProperty( methodName ) ) {
                    fieldId = objectName[methodName]( $mainInput );
                }
            }

            if ( ! fieldId ) {
                return '';
            }

            return fieldId;
        },

        getInputByIdOrName: function( $wrapper , cleanName ) {
            var self = shipments.admin.shipment_settings;
            cleanName = self.getCleanDataId( cleanName );

            return $wrapper.find( ':input' ).filter( function() {
                var id = self.getCleanInputId( $( this ) );

                if ( ! id ) {
                    return false;
                }

                return self.getCleanDataId( id ) === cleanName;
            });
        },

        /**
         * Make sure to remove any hyphens as data-attributes are stored
         * camel case without hyphens in the DOM.
         */
        getCleanDataId: function( id ) {
            return id.toLowerCase().replace( /-/g, '' );
        },

        onChangeInput: function() {
            var self             = shipments.admin.shipment_settings,
                $mainInput       = $( this ),
                $wrapper         = $( this ).parents( 'form' ),
                mainId           = self.getCleanInputId( $mainInput ),
                $dependentFields = $wrapper.find( ':input[data-show_if_' + $.escapeSelector( mainId ) + ']' );

            var $input, $field, data, meetsConditions, cleanName, $dependentField, valueExpected, val, isChecked;

            $.each( $dependentFields, function () {
                $input          = $( this );
                $field          = $input.parents( 'tr' );
                data            = $input.data();
                meetsConditions = true;

                for ( var dataName in data ) {
                    if ( data.hasOwnProperty( dataName ) ) {
                        /**
                         * Check all the conditions for a dependent field.
                         */
                        if ( dataName.substring( 0, 8 ) === 'show_if_' ) {
                            cleanName       = dataName.replace( 'show_if_', '' );
                            $dependentField = self.getInputByIdOrName( $wrapper, cleanName );
                            valueExpected   = $input.data( dataName ) ? $input.data( dataName ).split(',') : [];

                            if ( $dependentField.length > 0 ) {
                                val       = $dependentField.val();
                                isChecked = false;

                                if ( $dependentField.is( ':radio' ) ) {
                                    val = $dependentField.parents( 'fieldset' ).find( ':checked' ).length > 0 ? $dependentField.parents( 'fieldset' ).find( ':checked' ).val() : 'no';

                                    if ( 'no' !== val ) {
                                        isChecked = true;
                                    }
                                } else if ( $dependentField.is( ':checkbox' ) ) {
                                    val = $dependentField.is( ':checked' ) ? 'yes' : 'no';

                                    if ( 'yes' === val ) {
                                        isChecked = true;
                                    }
                                } else {
                                    isChecked = undefined !== val && '0' !== val && '' !== val;
                                }

                                if ( valueExpected && valueExpected.length > 0 ) {
                                    if ( $.inArray( val, valueExpected ) === -1 ) {
                                        meetsConditions = false;
                                    }
                                } else if ( ! isChecked ) {
                                    meetsConditions = false;
                                }
                            }

                            if ( ! meetsConditions ) {
                                break;
                            }
                        }
                    }
                }

                if ( meetsConditions ) {
                    if ( $field.length === 0 ) {
                        // Use this markup as fallback in case field does not belong to a table, e.g. shipping method settings
                        $input.parents( 'fieldset' ).show();
                        $input.parents( 'fieldset' ).prev( 'label' ).show();
                    } else {
                        $field.show();
                    }
                } else {
                    if ( $field.length === 0 ) {
                        $input.parents( 'fieldset' ).hide();
                        $input.parents( 'fieldset' ).prev( 'label' ).hide();
                    } else {
                        $field.hide();
                    }
                }
            } );
        },

        onInputToggleClick: function() {
            var $toggle   = $( this ).find( 'span.woocommerce-gzd-input-toggle' ),
                $row      = $toggle.parents( 'fieldset' ),
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
        }
    };

    $( document ).ready( function() {
        shipments.admin.shipment_settings.init();
    });

})( jQuery, window.shipments );