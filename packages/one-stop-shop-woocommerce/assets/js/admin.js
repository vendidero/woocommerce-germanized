window.oss = window.oss || {};

( function( $, oss ) {
    oss.admin = {

        params: {},
        dates: false,

        init: function() {
            var self    = oss.admin;
            self.params = oss_admin_params;

            $( document )
                .on( 'change', 'select#oss-report-type', self.onChangeReportType )
                .on( 'click', 'a.oss-add-new-tax-class-by-country', self.onAddNewTaxClassCountry )
                .on( 'click', 'a.oss-remove-tax-class-by-country', self.onRemoveTaxClassCountry );

            if ( $( 'select#oss-report-type' ).length > 0 ) {
                $( 'select#oss-report-type' ).trigger( 'change' );
            }

            if ( $( '.oss_range_datepicker' ).length > 0 ) {
                self.initDatePicker();
            }

            $( document.body ).on( 'init_tooltips', function() {
                self.initTipTip();
            });

            self.initTipTip();
        },

        onAddNewTaxClassCountry: function() {
            var $parent = $( this ).parents( '#general_product_data' );

            if ( $parent.length === 0 ) {
                $parent = $( this ).parents( '.woocommerce_variable_attributes' );
            }

            var $template = $parent.find( '.oss-add-tax-class-by-country-template:first' ).clone();

            $template.removeClass( 'oss-add-tax-class-by-country-template' ).addClass( 'oss-add-tax-class-by-country-new' );
            $parent.find( '.oss-new-tax-class-by-country-placeholder' ).append( $template ).show();

            return false;
        },

        onRemoveTaxClassCountry: function() {
            var $parent = $( this ).parents( '.form-field' );

            // Trigger change to notify Woo about an update (variations).
            $parent.find( 'select' ).trigger( 'change' );
            $parent.remove();

            return false;
        },

        initDatePicker: function() {
            var self = oss.admin;

            self.dates = $( '.oss_range_datepicker' ).datepicker({
                changeMonth: true,
                changeYear: true,
                defaultDate: '',
                dateFormat: 'yy-mm-dd',
                numberOfMonths: 1,
                minDate: '-20Y',
                maxDate: '+0D',
                showButtonPanel: true,
                showOn: 'focus',
                buttonImageOnly: true,
                onSelect: function() {
                    var option = $( this ).is( '.from' ) ? 'minDate' : 'maxDate',
                        date   = $( this ).datepicker( 'getDate' );

                    self.dates.not( this ).datepicker( 'option', option, date );
                }
            });
        },

        onChangeReportType: function() {
            var type = $( this ).val();

            $( '.oss-report-hidden' ).hide();

            if ( $( '.oss-report-' + type ).length > 0 ) {
                $( '.oss-report-' + type ).show();
            }
        },

        initTipTip: function() {
            $( '.column-actions .oss-woo-action-button' ).tipTip( {
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            });
        }
    };

    $( document ).ready( function() {
        oss.admin.init();
    });

})( jQuery, window.oss );
